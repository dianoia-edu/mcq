<?php
/**
 * Downsub.com Reverse Engineering
 * Analysiere genau wie downsub das macht
 */

echo "<h1>🕵️ Downsub.com Reverse Engineering</h1>\n";

$testVideo = 'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s';
$videoId = 'uCGJr448RgI';

echo "<h2>🎯 Test-Video: $testVideo</h2>\n";

// 1. Analysiere downsub.com direkt
echo "<h2>1. 🔍 Downsub.com API-Analyse</h2>\n";

$downsub_url = 'https://downsub.com';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: de,en-US;q=0.7,en;q=0.3',
        ],
        'timeout' => 15
    ]
]);

echo "<h3>📄 Lade downsub.com Hauptseite...</h3>\n";
$downsub_html = file_get_contents($downsub_url, false, $context);

if ($downsub_html) {
    echo "<p>✅ Downsub-Seite geladen: " . number_format(strlen($downsub_html)) . " Zeichen</p>\n";
    
    // Suche nach API-Endpoints
    if (preg_match_all('/\/api\/[^"\']+/', $downsub_html, $api_matches)) {
        echo "<h4>🎯 Gefundene API-Endpoints:</h4>\n";
        foreach (array_unique($api_matches[0]) as $endpoint) {
            echo "<code>https://downsub.com$endpoint</code><br>\n";
        }
    }
    
    // Suche nach JavaScript-API-Calls
    if (preg_match_all('/fetch\([\'"]([^\'"]+)[\'"]/', $downsub_html, $fetch_matches)) {
        echo "<h4>🚀 JavaScript API-Calls:</h4>\n";
        foreach (array_unique($fetch_matches[1]) as $api_call) {
            echo "<code>$api_call</code><br>\n";
        }
    }
    
    // Suche nach POST-Endpoints
    if (preg_match_all('/action=[\'"]([^\'"]+)[\'"]/', $downsub_html, $action_matches)) {
        echo "<h4>📋 Form-Actions:</h4>\n";
        foreach (array_unique($action_matches[1]) as $action) {
            echo "<code>$action</code><br>\n";
        }
    }
} else {
    echo "<p>❌ Downsub-Seite nicht erreichbar</p>\n";
}

// 2. Simuliere downsub API-Call
echo "<h2>2. 🎭 Simuliere Downsub API-Call</h2>\n";

$possible_endpoints = [
    'https://downsub.com/api/download',
    'https://downsub.com/download',
    'https://downsub.com/api/subtitle',
    'https://downsub.com/subtitle',
    'https://api.downsub.com/download'
];

foreach ($possible_endpoints as $endpoint) {
    echo "<h3>🧪 Teste Endpoint: $endpoint</h3>\n";
    
    $post_data = json_encode([
        'url' => $testVideo,
        'format' => 'txt',
        'lang' => 'de'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json, text/plain, */*',
                'Origin: https://downsub.com',
                'Referer: https://downsub.com/',
                'X-Requested-With: XMLHttpRequest'
            ],
            'content' => $post_data,
            'timeout' => 15
        ]
    ]);
    
    $response = @file_get_contents($endpoint, false, $context);
    
    if ($response) {
        echo "<p>✅ Response erhalten: " . strlen($response) . " Zeichen</p>\n";
        
        $json_response = json_decode($response, true);
        if ($json_response) {
            echo "<h4>📋 JSON-Response:</h4>\n";
            echo "<pre>" . htmlspecialchars(json_encode($json_response, JSON_PRETTY_PRINT)) . "</pre>\n";
            
            // Suche nach Download-URLs
            if (isset($json_response['download_url']) || isset($json_response['url'])) {
                $download_url = $json_response['download_url'] ?? $json_response['url'];
                echo "<h4>🎯 Download-URL gefunden!</h4>\n";
                echo "<code>$download_url</code><br>\n";
                
                // Lade den Transcript-Content
                $transcript_content = file_get_contents($download_url);
                if ($transcript_content) {
                    echo "<h4>🎉 TRANSCRIPT ERFOLGREICH!</h4>\n";
                    echo "<p><strong>Länge:</strong> " . strlen($transcript_content) . " Zeichen</p>\n";
                    echo "<details><summary>Transcript anzeigen</summary>\n";
                    echo "<pre>" . htmlspecialchars(substr($transcript_content, 0, 1000)) . "...</pre>\n";
                    echo "</details>\n";
                    
                    echo "<div style='background: #e8f5e8; padding: 20px; border: 2px solid #4caf50; border-radius: 8px; margin: 20px 0;'>\n";
                    echo "<h3>🎯 DOWNSUB-METHODE GEFUNDEN!</h3>\n";
                    echo "<p><strong>Endpoint:</strong> $endpoint</p>\n";
                    echo "<p><strong>Methode:</strong> POST mit JSON</p>\n";
                    echo "<p><strong>Header:</strong> application/json + Origin</p>\n";
                    echo "</div>\n";
                    break;
                }
            }
        } else {
            echo "<h4>📄 Text-Response:</h4>\n";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>\n";
        }
    } else {
        $error = error_get_last();
        echo "<p>❌ Fehler: " . ($error['message'] ?? 'Unbekannt') . "</p>\n";
    }
    
    echo "<hr>\n";
}

// 3. Alternative: Network-Analysis-Simulation
echo "<h2>3. 🔬 Network-Traffic-Simulation</h2>\n";

echo "<h3>📡 Häufige YouTube-Transcript-API-Patterns:</h3>\n";

$common_patterns = [
    "https://www.youtube.com/api/timedtext?v={$videoId}&lang=de&fmt=json3",
    "https://www.youtube.com/api/timedtext?v={$videoId}&lang=en&fmt=json3",
    "https://youtubei.googleapis.com/youtubei/v1/get_transcript?videoId={$videoId}",
    "https://www.youtube.com/youtubei/v1/player?videoId={$videoId}",
    "https://m.youtube.com/api/timedtext?v={$videoId}&lang=de",
    "https://music.youtube.com/youtubei/v1/player?videoId={$videoId}"
];

foreach ($common_patterns as $pattern) {
    echo "<h4>🧪 Teste Pattern: $pattern</h4>\n";
    
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/json, text/plain, */*',
        'Accept-Language: de,en;q=0.9',
        'Origin: https://www.youtube.com',
        'Referer: https://www.youtube.com/watch?v=' . $videoId
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($pattern, false, $context);
    
    if ($response && strlen($response) > 100) {
        echo "<p>✅ Erfolgreiche Response: " . strlen($response) . " Zeichen</p>\n";
        
        // Prüfe ob es JSON ist
        $json_data = json_decode($response, true);
        if ($json_data) {
            echo "<p>📋 JSON-Format erkannt</p>\n";
            if (isset($json_data['events'])) {
                echo "<p>🎯 Transcript-Events gefunden!</p>\n";
                
                $transcript_text = '';
                foreach ($json_data['events'] as $event) {
                    if (isset($event['segs'])) {
                        foreach ($event['segs'] as $seg) {
                            if (isset($seg['utf8'])) {
                                $transcript_text .= $seg['utf8'] . ' ';
                            }
                        }
                    }
                }
                
                if (strlen($transcript_text) > 100) {
                    echo "<div style='background: #e8f5e8; padding: 15px; border: 2px solid #4caf50; border-radius: 5px;'>\n";
                    echo "<h4>🎉 TRANSCRIPT GEFUNDEN!</h4>\n";
                    echo "<p><strong>URL:</strong> $pattern</p>\n";
                    echo "<p><strong>Länge:</strong> " . strlen($transcript_text) . " Zeichen</p>\n";
                    echo "<details><summary>Transcript anzeigen</summary>\n";
                    echo "<pre>" . htmlspecialchars(substr($transcript_text, 0, 800)) . "...</pre>\n";
                    echo "</details>\n";
                    echo "</div>\n";
                }
            }
        } else {
            echo "<p>📄 XML/Text-Format</p>\n";
            // Prüfe auf XML-Transcript
            if (strpos($response, '<text') !== false) {
                echo "<p>🎯 XML-Transcript-Format erkannt!</p>\n";
                
                if (preg_match_all('/<text[^>]*>(.*?)<\/text>/s', $response, $matches)) {
                    $transcript_text = implode(' ', $matches[1]);
                    $transcript_text = html_entity_decode($transcript_text);
                    
                    if (strlen($transcript_text) > 100) {
                        echo "<div style='background: #e8f5e8; padding: 15px; border: 2px solid #4caf50; border-radius: 5px;'>\n";
                        echo "<h4>🎉 XML-TRANSCRIPT GEFUNDEN!</h4>\n";
                        echo "<p><strong>URL:</strong> $pattern</p>\n";
                        echo "<p><strong>Länge:</strong> " . strlen($transcript_text) . " Zeichen</p>\n";
                        echo "<details><summary>Transcript anzeigen</summary>\n";
                        echo "<pre>" . htmlspecialchars(substr($transcript_text, 0, 800)) . "...</pre>\n";
                        echo "</details>\n";
                        echo "</div>\n";
                    }
                }
            }
        }
    } else {
        echo "<p>❌ Keine brauchbare Response</p>\n";
    }
    
    echo "<hr>\n";
}

echo "<h2>💡 Erkenntnisse</h2>\n";
echo "<p>Wenn hier ein Transcript gefunden wurde, dann haben wir <strong>downsubs Geheimnis geknackt!</strong></p>\n";
echo "<p>Diese URL/Methode kann dann direkt implementiert werden.</p>\n";

?>
