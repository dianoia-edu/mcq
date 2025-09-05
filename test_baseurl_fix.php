<?php
/**
 * Test: BaseURL-Zugriff mit besseren Headers
 */

echo "<h1>🔧 BaseURL Header-Fix Test</h1>\n";

$testVideoId = 'dQw4w9WgXcQ';
$testUrl = "https://www.youtube.com/watch?v={$testVideoId}";

// 1. HTML laden und Caption-Tracks extrahieren
echo "<h2>1. 📥 Caption-Tracks extrahieren...</h2>\n";

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

$html = file_get_contents($testUrl, false, $context);

if (!$html) {
    echo "❌ Konnte HTML nicht laden<br>\n";
    exit;
}

// Caption-Tracks Pattern
$pattern = '/"captions":\{"playerCaptionsTracklistRenderer":\{"captionTracks":\[(.*?)\]/';

if (!preg_match($pattern, $html, $matches)) {
    echo "❌ Keine Caption-Tracks gefunden<br>\n";
    exit;
}

$captionData = '[' . $matches[1] . ']';
$captions = json_decode($captionData, true);

if (!$captions) {
    echo "❌ JSON-Parse-Fehler<br>\n";
    exit;
}

echo "✅ " . count($captions) . " Caption-Tracks gefunden<br>\n";

// 2. BaseURLs mit verschiedenen Header-Kombinationen testen
echo "<h2>2. 🧪 BaseURL-Tests mit verschiedenen Headers</h2>\n";

$headerVariants = [
    'Standard' => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/xml,text/xml,*/*'
    ],
    'Mit Referer' => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/xml,text/xml,*/*',
        'Referer: https://www.youtube.com/'
    ],
    'Mit Origin' => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/xml,text/xml,*/*',
        'Referer: https://www.youtube.com/',
        'Origin: https://www.youtube.com'
    ],
    'Vollständig' => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/xml,text/xml,*/*',
        'Accept-Language: de,en;q=0.9',
        'Referer: https://www.youtube.com/',
        'Origin: https://www.youtube.com',
        'DNT: 1',
        'Connection: close'
    ],
    'YouTube-Player' => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/xml,text/xml,*/*',
        'Referer: https://www.youtube.com/embed/' . $testVideoId,
        'Origin: https://www.youtube.com',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin'
    ]
];

// Teste ersten deutschen Caption-Track
$germanTrack = null;
foreach ($captions as $caption) {
    if (isset($caption['languageCode']) && strpos($caption['languageCode'], 'de') === 0) {
        $germanTrack = $caption;
        break;
    }
}

if (!$germanTrack && isset($captions[0])) {
    $germanTrack = $captions[0]; // Fallback: erster Track
}

if (!$germanTrack || !isset($germanTrack['baseUrl'])) {
    echo "❌ Kein verwendbarer Caption-Track gefunden<br>\n";
    exit;
}

$baseUrl = $germanTrack['baseUrl'];
$trackLang = $germanTrack['languageCode'] ?? 'unknown';
$trackName = $germanTrack['name']['simpleText'] ?? 'unknown';

echo "<p><strong>Test-Track:</strong> $trackName ($trackLang)</p>\n";
echo "<p><strong>BaseURL:</strong> " . htmlspecialchars(substr($baseUrl, 0, 80)) . "...</p>\n";

foreach ($headerVariants as $variantName => $headers) {
    echo "<h3>🧪 $variantName</h3>\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headers,
            'timeout' => 10
        ]
    ]);
    
    $startTime = microtime(true);
    $transcript = @file_get_contents($baseUrl, false, $context);
    $duration = microtime(true) - $startTime;
    
    echo "<p><strong>Dauer:</strong> " . round($duration, 2) . "s</p>\n";
    
    if ($transcript === false) {
        $error = error_get_last();
        echo "<div style='background: #ffebee; padding: 10px;'>❌ <strong>Fehler:</strong> " . htmlspecialchars($error['message'] ?? 'Unbekannt') . "</div>\n";
    } else {
        $length = strlen($transcript);
        echo "<p><strong>Antwort-Länge:</strong> $length Zeichen</p>\n";
        
        if ($length > 100) {
            echo "<div style='background: #e8f5e8; padding: 10px;'>✅ <strong>Transcript erhalten!</strong></div>\n";
            
            // XML parsen
            $xml = @simplexml_load_string($transcript);
            if ($xml) {
                $textContent = '';
                foreach ($xml->text as $text) {
                    $textContent .= (string)$text . ' ';
                }
                
                $cleanText = trim(html_entity_decode($textContent));
                echo "<p><strong>Geparster Text:</strong> " . strlen($cleanText) . " Zeichen</p>\n";
                
                if (strlen($cleanText) > 50) {
                    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>\n";
                    echo "<h4>🎉 ERFOLG!</h4>\n";
                    echo "<p><strong>Transcript-Vorschau:</strong></p>\n";
                    echo "<pre style='max-height: 150px; overflow-y: auto;'>" . htmlspecialchars(substr($cleanText, 0, 300)) . "...</pre>\n";
                    echo "</div>\n";
                    
                    // Das war's - Headers funktionieren!
                    echo "<div style='background: #4caf50; color: white; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
                    echo "<h3>🎯 LÖSUNG GEFUNDEN!</h3>\n";
                    echo "<p>Header-Kombination '$variantName' funktioniert!</p>\n";
                    echo "<p>Kann jetzt in Python-Script übernommen werden.</p>\n";
                    echo "</div>\n";
                    break 2; // Stoppe beide Schleifen
                }
            } else {
                echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ XML-Parse-Fehler</div>\n";
                echo "<details><summary>Rohdaten</summary><pre>" . htmlspecialchars(substr($transcript, 0, 500)) . "</pre></details>\n";
            }
        } else {
            echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ Transcript zu kurz</div>\n";
        }
    }
    
    echo "<hr>\n";
}

echo "<h2>💡 Ergebnis</h2>\n";
echo "<p>Wenn eine Header-Kombination funktioniert hat, kann ich das Python-Script entsprechend anpassen!</p>\n";

?>
