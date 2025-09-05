<?php
/**
 * Einfacher YouTube Test - zeigt was YouTube zurückgibt
 */

echo "<h1>🔍 Einfacher YouTube Test</h1>\n";

$testVideoId = 'dQw4w9WgXcQ'; // Rick Roll
$testUrls = [
    "Direkte Caption API" => "https://www.youtube.com/api/timedtext?lang=de&v={$testVideoId}&fmt=json3",
    "YouTube Hauptseite" => "https://www.youtube.com/watch?v={$testVideoId}",
    "Mobile YouTube" => "https://m.youtube.com/watch?v={$testVideoId}",
];

foreach ($testUrls as $name => $url) {
    echo "<h2>🧪 $name</h2>\n";
    echo "<p><strong>URL:</strong> <code>" . htmlspecialchars($url) . "</code></p>\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: de,en-US;q=0.7,en;q=0.3',
                'DNT: 1',
                'Connection: close'
            ],
            'timeout' => 10
        ]
    ]);
    
    $startTime = microtime(true);
    $content = @file_get_contents($url, false, $context);
    $duration = microtime(true) - $startTime;
    
    echo "<p><strong>Dauer:</strong> " . round($duration, 2) . "s</p>\n";
    
    if ($content === false) {
        $error = error_get_last();
        echo "<div style='background: #ffebee; padding: 10px;'>❌ <strong>Fehler:</strong> " . htmlspecialchars($error['message'] ?? 'Unbekannter Fehler') . "</div>\n";
    } else {
        $length = strlen($content);
        echo "<p><strong>Antwort-Länge:</strong> $length Zeichen</p>\n";
        
        if ($length > 0) {
            echo "<div style='background: #e8f5e8; padding: 10px;'>✅ <strong>Antwort erhalten!</strong></div>\n";
            
            // Erste 500 Zeichen anzeigen
            echo "<details><summary><strong>Erste 500 Zeichen</strong></summary>\n";
            echo "<pre style='background: #f5f5f5; padding: 10px;'>";
            echo htmlspecialchars(substr($content, 0, 500));
            echo "</pre></details>\n";
            
            // Bei Caption-API: JSON-Check
            if (strpos($name, 'Caption') !== false) {
                $json = json_decode($content, true);
                if ($json) {
                    echo "<div style='background: #e3f2fd; padding: 10px;'>📋 <strong>Gültiges JSON!</strong></div>\n";
                    if (isset($json['events'])) {
                        echo "<p>🎯 <strong>Events gefunden:</strong> " . count($json['events']) . "</p>\n";
                    }
                } else {
                    echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ <strong>Kein gültiges JSON</strong></div>\n";
                }
            }
            
            // Bei HTML: Caption-Suche
            if (strpos($name, 'YouTube') !== false) {
                if (strpos($content, '"captionTracks"') !== false) {
                    echo "<div style='background: #e8f5e8; padding: 10px;'>🎯 <strong>captionTracks gefunden!</strong></div>\n";
                } else {
                    echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ <strong>Keine captionTracks gefunden</strong></div>\n";
                }
                
                if (strpos($content, 'Sign in to confirm') !== false) {
                    echo "<div style='background: #ffebee; padding: 10px;'>🤖 <strong>Bot-Erkennung!</strong> YouTube fordert Login</div>\n";
                }
                
                if (strpos($content, 'Video unavailable') !== false) {
                    echo "<div style='background: #ffebee; padding: 10px;'>📹 <strong>Video nicht verfügbar</strong></div>\n";
                }
            }
        } else {
            echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ <strong>Leere Antwort</strong></div>\n";
        }
    }
    
    echo "<hr>\n";
}

echo "<h2>💡 Interpretation</h2>\n";
echo "<ul>\n";
echo "<li><strong>Wenn Caption-API funktioniert:</strong> Direkte Integration möglich</li>\n";
echo "<li><strong>Wenn nur HTML-Seiten funktionieren:</strong> Parsing nötig</li>\n";
echo "<li><strong>Wenn 'Bot-Erkennung':</strong> Server wird blockiert</li>\n";
echo "<li><strong>Wenn alles fehlschlägt:</strong> IP-Sperre oder Firewall</li>\n";
echo "</ul>\n";

// CURL-Test als Alternative
echo "<h2>🔧 CURL-Test</h2>\n";

if (function_exists('curl_init')) {
    $curlUrl = "https://www.youtube.com/api/timedtext?lang=de&v={$testVideoId}&fmt=json3";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $curlUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: de,en;q=0.9',
        ]
    ]);
    
    $curlContent = curl_exec($ch);
    $curlInfo = curl_getinfo($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>CURL Status:</strong> {$curlInfo['http_code']}</p>\n";
    echo "<p><strong>CURL Dauer:</strong> " . round($curlInfo['total_time'], 2) . "s</p>\n";
    
    if ($curlError) {
        echo "<div style='background: #ffebee; padding: 10px;'>❌ <strong>CURL-Fehler:</strong> $curlError</div>\n";
    } elseif ($curlContent) {
        echo "<div style='background: #e8f5e8; padding: 10px;'>✅ <strong>CURL erfolgreich!</strong> (" . strlen($curlContent) . " Zeichen)</div>\n";
        
        $json = json_decode($curlContent, true);
        if ($json && isset($json['events'])) {
            echo "<p>🎯 <strong>CURL: Events gefunden!</strong> " . count($json['events']) . "</p>\n";
        }
    } else {
        echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ <strong>CURL: Leere Antwort</strong></div>\n";
    }
} else {
    echo "<p>❌ CURL nicht verfügbar</p>\n";
}

?>
