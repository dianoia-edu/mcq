<?php
class OpenAIClient {
    private $apiKey;
    private $model = 'gpt-3.5-turbo';
    private $maxTokens = 2048;
    private $temperature = 0.7;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function generateCompletion($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Du bist ein Assistent, der Multiple-Choice-Fragen erstellt.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature
        ];
        
        $response = $this->makeRequest($url, $headers, $data);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return '';
    }
    
    public function extractTextFromDocument($base64Content) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        // Erstelle einen Prompt für die Dokumentenanalyse
        $prompt = "Extrahiere den Text aus dem folgenden Dokument und gib nur den extrahierten Text zurück, ohne zusätzliche Kommentare oder Formatierungen.";
        
        $data = [
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:application/pdf;base64,' . $base64Content,
                                'detail' => 'high'
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 4096
        ];
        
        $response = $this->makeRequest($url, $headers, $data);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return '';
    }
    
    private function makeRequest($url, $headers, $data) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('API-Anfrage fehlgeschlagen: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function testWhisperAccess() {
        $url = 'https://api.openai.com/v1/audio/transcriptions';
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 200 oder 401 bedeutet, dass der Endpunkt existiert (401 = Unauthorized, aber Endpunkt existiert)
        return ($httpCode == 200 || $httpCode == 401);
    }
} 