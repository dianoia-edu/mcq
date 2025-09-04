<?php
/**
 * API-Endpoint für OpenAI Modelle
 * Liefert verfügbare Modelle für die Test-Generator UI
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Lade Konfiguration
    require_once __DIR__ . '/../config_loader.php';
    require_once __DIR__ . '/../openai_models.php';
    
    $config = loadConfig();
    
    if (!isset($config['api_key']) || empty($config['api_key'])) {
        throw new Exception('OpenAI API-Schlüssel nicht konfiguriert');
    }
    
    $modelsManager = new OpenAIModels($config['api_key']);
    
    // Parameter auswerten
    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';
    $testModel = isset($_GET['test_model']) ? $_GET['test_model'] : null;
    
    if ($testModel) {
        // Teste spezifisches Modell
        $isWorking = $modelsManager->testModel($testModel);
        echo json_encode([
            'success' => true,
            'model_test' => [
                'model' => $testModel,
                'working' => $isWorking,
                'message' => $isWorking ? 'Modell funktioniert' : 'Modell nicht verfügbar oder fehlerhaft'
            ]
        ]);
        exit;
    }
    
    // Lade verfügbare Modelle
    $models = $modelsManager->getAvailableModels($forceRefresh);
    $bestModel = $modelsManager->getBestAvailableModel();
    
    // Zusätzliche Statistiken
    $stats = [
        'total_models' => count($models),
        'recommended_models' => count(array_filter($models, function($m) { return $m['recommended']; })),
        'last_updated' => $forceRefresh ? time() : null,
        'cache_file' => file_exists(__DIR__ . '/../cache/openai_models.json')
    ];
    
    echo json_encode([
        'success' => true,
        'models' => $models,
        'best_model' => $bestModel,
        'statistics' => $stats,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("OpenAI Models API Fehler: " . $e->getMessage());
    
    // Fallback-Antwort mit Standard-Modellen
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'fallback_models' => [
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
                'name' => 'GPT-4 Turbo',
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
        ],
        'message' => 'Verwende vordefinierte Modelle als Fallback'
    ]);
}
?>
