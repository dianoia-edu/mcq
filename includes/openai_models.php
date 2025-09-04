<?php
/**
 * OpenAI Models Management
 * Dynamische Abfrage und Verwaltung verfügbarer OpenAI-Modelle
 */

class OpenAIModels {
    private $apiKey;
    private $modelsCache;
    private $cacheFile;
    private $cacheExpiration = 3600; // 1 Stunde Cache
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->cacheFile = __DIR__ . '/cache/openai_models.json';
        $this->ensureCacheDirectory();
    }
    
    private function ensureCacheDirectory() {
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }
    
    /**
     * Lade verfügbare Modelle von OpenAI API
     */
    public function getAvailableModels($forceRefresh = false) {
        // Prüfe Cache zuerst
        if (!$forceRefresh && $this->isCacheValid()) {
            return $this->loadFromCache();
        }
        
        try {
            $models = $this->fetchModelsFromAPI();
            $this->saveToCache($models);
            return $models;
        } catch (Exception $e) {
            // Fallback auf Cache falls API nicht erreichbar
            if ($this->cacheExists()) {
                error_log("OpenAI Models API Fehler, verwende Cache: " . $e->getMessage());
                return $this->loadFromCache();
            }
            
            // Fallback auf vordefinierte Modelle
            error_log("OpenAI Models API und Cache nicht verfügbar: " . $e->getMessage());
            return $this->getFallbackModels();
        }
    }
    
    /**
     * Hole Modelle direkt von der OpenAI API
     */
    private function fetchModelsFromAPI() {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/models',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode . " - " . $response);
        }
        
        $data = json_decode($response, true);
        if (!isset($data['data'])) {
            throw new Exception("Invalid API response format");
        }
        
        // Filtere nur Chat-Modelle
        $chatModels = [];
        foreach ($data['data'] as $model) {
            if ($this->isChatModel($model['id'])) {
                $chatModels[] = [
                    'id' => $model['id'],
                    'name' => $this->getModelDisplayName($model['id']),
                    'context_window' => $this->getModelContextWindow($model['id']),
                    'recommended' => $this->isRecommendedModel($model['id']),
                    'created' => $model['created'] ?? 0
                ];
            }
        }
        
        // Sortiere nach Empfehlung und dann nach Name
        usort($chatModels, function($a, $b) {
            if ($a['recommended'] !== $b['recommended']) {
                return $b['recommended'] - $a['recommended']; // Empfohlene zuerst
            }
            return strcmp($a['name'], $b['name']);
        });
        
        return $chatModels;
    }
    
    /**
     * Prüfe ob Modell ein Chat-Modell ist
     */
    private function isChatModel($modelId) {
        $chatPrefixes = [
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4o',
            'gpt-4-turbo',
            'chatgpt'
        ];
        
        foreach ($chatPrefixes as $prefix) {
            if (strpos($modelId, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Benutzerfreundlicher Anzeigename für Modell
     */
    private function getModelDisplayName($modelId) {
        $names = [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Standard)',
            'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K (Erweitert)',
            'gpt-4' => 'GPT-4 (Premium)',
            'gpt-4-32k' => 'GPT-4 32K (Erweitert)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Schnell)',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo Preview',
            'gpt-4-1106-preview' => 'GPT-4 Turbo (November)',
            'gpt-4-0125-preview' => 'GPT-4 Turbo (Januar)',
            'gpt-4o' => 'GPT-4o (Neueste)',
            'gpt-4o-mini' => 'GPT-4o Mini (Effizient)'
        ];
        
        return $names[$modelId] ?? $modelId;
    }
    
    /**
     * Context Window für Modell
     */
    private function getModelContextWindow($modelId) {
        $contexts = [
            'gpt-3.5-turbo' => 4096,
            'gpt-3.5-turbo-16k' => 16384,
            'gpt-4' => 8192,
            'gpt-4-32k' => 32768,
            'gpt-4-turbo' => 128000,
            'gpt-4-1106-preview' => 128000,
            'gpt-4-0125-preview' => 128000,
            'gpt-4o' => 128000,
            'gpt-4o-mini' => 128000
        ];
        
        return $contexts[$modelId] ?? 4096;
    }
    
    /**
     * Ist Modell empfohlen für Test-Generation?
     */
    private function isRecommendedModel($modelId) {
        $recommended = [
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4-turbo',
            'gpt-4-0125-preview',
            'gpt-3.5-turbo'
        ];
        
        return in_array($modelId, $recommended);
    }
    
    /**
     * Bestes verfügbares Modell automatisch wählen
     */
    public function getBestAvailableModel() {
        $models = $this->getAvailableModels();
        
        // Priorisierte Liste der besten Modelle
        $preferredModels = [
            'gpt-4o',
            'gpt-4o-mini', 
            'gpt-4-turbo',
            'gpt-4-0125-preview',
            'gpt-4-1106-preview',
            'gpt-4',
            'gpt-3.5-turbo'
        ];
        
        // Finde das beste verfügbare Modell
        foreach ($preferredModels as $preferred) {
            foreach ($models as $model) {
                if ($model['id'] === $preferred) {
                    return $model;
                }
            }
        }
        
        // Fallback: Erstes verfügbares Modell
        return !empty($models) ? $models[0] : $this->getFallbackModels()[0];
    }
    
    /**
     * Cache-Verwaltung
     */
    private function isCacheValid() {
        return $this->cacheExists() && 
               (time() - filemtime($this->cacheFile)) < $this->cacheExpiration;
    }
    
    private function cacheExists() {
        return file_exists($this->cacheFile);
    }
    
    private function loadFromCache() {
        if (!$this->cacheExists()) {
            return $this->getFallbackModels();
        }
        
        $data = json_decode(file_get_contents($this->cacheFile), true);
        return is_array($data) ? $data : $this->getFallbackModels();
    }
    
    private function saveToCache($models) {
        file_put_contents($this->cacheFile, json_encode($models, JSON_PRETTY_PRINT));
    }
    
    /**
     * Fallback-Modelle falls API nicht verfügbar
     */
    private function getFallbackModels() {
        return [
            [
                'id' => 'gpt-4o',
                'name' => 'GPT-4o (Neueste)',
                'context_window' => 128000,
                'recommended' => true,
                'created' => time()
            ],
            [
                'id' => 'gpt-4o-mini',
                'name' => 'GPT-4o Mini (Effizient)',
                'context_window' => 128000,
                'recommended' => true,
                'created' => time()
            ],
            [
                'id' => 'gpt-4-turbo',
                'name' => 'GPT-4 Turbo (Schnell)',
                'context_window' => 128000,
                'recommended' => true,
                'created' => time()
            ],
            [
                'id' => 'gpt-3.5-turbo',
                'name' => 'GPT-3.5 Turbo (Standard)',
                'context_window' => 4096,
                'recommended' => true,
                'created' => time()
            ]
        ];
    }
    
    /**
     * Teste ob ein Modell funktioniert
     */
    public function testModel($modelId) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $modelId,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test']
                ],
                'max_tokens' => 5,
                'temperature' => 0
            ]),
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * Erstelle Cache-Verzeichnis
     */
    public function clearCache() {
        if ($this->cacheExists()) {
            unlink($this->cacheFile);
        }
    }
}

/**
 * Helper-Funktion für einfachen Zugriff
 */
function getOpenAIModels($apiKey) {
    return new OpenAIModels($apiKey);
}
?>
