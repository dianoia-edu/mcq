<?php
/**
 * Test: Caption-Tracks aus YouTube-HTML extrahieren
 */

echo "<h1>🎯 Caption-Track Extraktion Test</h1>\n";

$testVideoId = 'dQw4w9WgXcQ';
$testUrl = "https://www.youtube.com/watch?v={$testVideoId}";

echo "<p><strong>Video:</strong> <code>$testUrl</code></p>\n";

// YouTube-HTML laden
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

echo "<h2>1. 📥 HTML laden...</h2>\n";

$html = file_get_contents($testUrl, false, $context);

if (!$html) {
    echo "<div style='background: #ffebee; padding: 10px;'>❌ Konnte HTML nicht laden</div>\n";
    exit;
}

echo "<p>✅ HTML geladen: " . number_format(strlen($html)) . " Zeichen</p>\n";

// Caption-Tracks suchen
echo "<h2>2. 🔍 Caption-Tracks suchen...</h2>\n";

$patterns = [
    'Pattern 1' => '/"captions":\{"playerCaptionsTracklistRenderer":\{"captionTracks":\[(.*?)\]/',
    'Pattern 2' => '/"captionTracks":\[(.*?)\]/',
    'Pattern 3' => '/ytInitialPlayerResponse.*?"captions".*?"captionTracks":\[(.*?)\]/',
    'Pattern 4' => '/"captionTracks":\[([^\]]+)\]/',
];

$captionData = null;
$usedPattern = null;

foreach ($patterns as $patternName => $pattern) {
    echo "<h3>🧪 $patternName</h3>\n";
    echo "<code>" . htmlspecialchars($pattern) . "</code><br>\n";
    
    if (preg_match($pattern, $html, $matches)) {
        $captionData = '[' . $matches[1] . ']';
        $usedPattern = $patternName;
        echo "<div style='background: #e8f5e8; padding: 10px;'>✅ <strong>Match gefunden!</strong></div>\n";
        echo "<p><strong>Rohdaten:</strong> " . strlen($matches[1]) . " Zeichen</p>\n";
        echo "<details><summary>Erste 300 Zeichen</summary><pre>" . htmlspecialchars(substr($matches[1], 0, 300)) . "</pre></details>\n";
        break;
    } else {
        echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ Kein Match</div>\n";
    }
}

if (!$captionData) {
    echo "<div style='background: #ffebee; padding: 10px;'>❌ <strong>Keine Caption-Tracks gefunden!</strong></div>\n";
    
    // Debug: Suche nach "caption" generell
    $captionCount = substr_count(strtolower($html), 'caption');
    echo "<p>Debug: Das Wort 'caption' kommt $captionCount mal vor.</p>\n";
    
    if (preg_match('/caption[^"]*"[^"]*"[^"]*"[^"]*baseUrl[^"]*"/i', $html, $debugMatch)) {
        echo "<p>Debug-Match: " . htmlspecialchars(substr($debugMatch[0], 0, 200)) . "</p>\n";
    }
    exit;
}

// JSON parsen
echo "<h2>3. 📋 JSON parsen...</h2>\n";
echo "<p><strong>Verwendetes Pattern:</strong> $usedPattern</p>\n";

$captions = json_decode($captionData, true);

if ($captions === null) {
    echo "<div style='background: #ffebee; padding: 10px;'>❌ <strong>JSON-Parse-Fehler:</strong> " . json_last_error_msg() . "</div>\n";
    echo "<details><summary>JSON-Daten (erste 1000 Zeichen)</summary><pre>" . htmlspecialchars(substr($captionData, 0, 1000)) . "</pre></details>\n";
    exit;
}

echo "<div style='background: #e8f5e8; padding: 10px;'>✅ <strong>JSON erfolgreich geparst!</strong></div>\n";
echo "<p><strong>Caption-Tracks gefunden:</strong> " . count($captions) . "</p>\n";

// Caption-Tracks analysieren
echo "<h2>4. 🎯 Caption-Tracks analysieren...</h2>\n";

foreach ($captions as $i => $caption) {
    echo "<h3>Track " . ($i + 1) . "</h3>\n";
    
    $lang = $caption['languageCode'] ?? 'unbekannt';
    $name = $caption['name']['simpleText'] ?? ($caption['name'] ?? 'unbekannt');
    $baseUrl = $caption['baseUrl'] ?? null;
    
    echo "<p><strong>Sprache:</strong> $lang</p>\n";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>\n";
    echo "<p><strong>BaseURL:</strong> " . ($baseUrl ? '✅ Vorhanden' : '❌ Fehlt') . "</p>\n";
    
    if ($baseUrl) {
        echo "<details><summary>BaseURL anzeigen</summary><pre>" . htmlspecialchars($baseUrl) . "</pre></details>\n";
        
        // Transcript laden
        echo "<h4>📥 Transcript laden...</h4>\n";
        
        $transcriptContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Referer: https://www.youtube.com/'
                ],
                'timeout' => 10
            ]
        ]);
        
        $transcript = file_get_contents($baseUrl, false, $transcriptContext);
        
        if ($transcript) {
            echo "<div style='background: #e8f5e8; padding: 10px;'>✅ <strong>Transcript geladen!</strong> (" . strlen($transcript) . " Zeichen)</div>\n";
            
            // XML parsen
            $xml = simplexml_load_string($transcript);
            if ($xml) {
                $textContent = '';
                foreach ($xml->text as $text) {
                    $textContent .= (string)$text . ' ';
                }
                
                $cleanText = trim(html_entity_decode($textContent));
                echo "<p><strong>Geparster Text:</strong> " . strlen($cleanText) . " Zeichen</p>\n";
                
                if (strlen($cleanText) > 100) {
                    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>\n";
                    echo "<h4>🎉 ERFOLG! Transcript gefunden:</h4>\n";
                    echo "<pre style='max-height: 200px; overflow-y: auto;'>" . htmlspecialchars(substr($cleanText, 0, 500)) . "...</pre>\n";
                    echo "</div>\n";
                    
                    // Das war's - wir haben den Transcript!
                    break;
                }
            } else {
                echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ XML-Parse-Fehler</div>\n";
                echo "<details><summary>XML-Inhalt</summary><pre>" . htmlspecialchars(substr($transcript, 0, 500)) . "</pre></details>\n";
            }
        } else {
            echo "<div style='background: #ffebee; padding: 10px;'>❌ Konnte Transcript nicht laden</div>\n";
        }
    }
    
    echo "<hr>\n";
}

echo "<h2>💡 Nächste Schritte</h2>\n";
echo "<p>Wenn hier ein Transcript gefunden wurde, kann ich das Python-Script entsprechend anpassen!</p>\n";

?>
