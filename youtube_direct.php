<?php
// Sofortige Ausgabe aktivieren
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Maximale Ausführungszeit auf 5 Minuten setzen
ini_set('max_execution_time', 300);
set_time_limit(300);

// Fehlerbehandlung verbessern
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug-Modus aktivieren
$DEBUG = true;

// Verzeichnisse festlegen
$outputDir = __DIR__ . '/output';
$logsDir = __DIR__ . '/logs';
$tempDir = __DIR__ . '/temp';
$cookieDir = __DIR__ . '/cookies';

// Verzeichnisse erstellen, falls sie nicht existieren
foreach ([$outputDir, $logsDir, $tempDir, $cookieDir] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Logging-Funktion
function logMessage($message, $level = 'INFO') {
    global $logsDir;
    $logFile = $logsDir . '/youtube_direct_' . date('Y-m-d') . '.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp [$level] $message";
    
    // Log in Datei schreiben
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
    
    // Ausgabe im Browser mit Farben
    $color = '#333';
    switch ($level) {
        case 'ERROR': $color = '#f44336'; break;
        case 'WARNING': $color = '#ff9800'; break;
        case 'SUCCESS': $color = '#4caf50'; break;
        case 'INFO': $color = '#2196f3'; break;
        case 'DEBUG': $color = '#9e9e9e'; break;
    }
    
    echo "<div style='color:$color; margin:2px 0;'>$logEntry</div>";
    
    // Sofort ausgeben für Live-Debugging
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
    
    return $logEntry;
}

// Hilfsfunktion: Video-ID aus YouTube-URL extrahieren
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// cURL-basierte Funktion für HTTP-Anfragen mit Proxy-Unterstützung
function makeRequest($url, $method = 'GET', $headers = [], $postData = null, $proxy = null, $cookies = null) {
    global $tempDir, $DEBUG;
    
    $ch = curl_init();
    
    // Basis-Optionen
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // User-Agent setzen
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0'
    ];
    $userAgent = $userAgents[array_rand($userAgents)];
    
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    
    // HTTP-Methode und Daten
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
    }
    
    // Headers
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    // Proxy verwenden
    if ($proxy) {
        logMessage("Verwende Proxy: $proxy", "INFO");
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        
        // Falls Proxy Authentifizierung enthält, wird dies automatisch verarbeitet
    }
    
    // Cookies verwenden
    if ($cookies) {
        if (is_string($cookies) && file_exists($cookies)) {
            // Cookie-Datei verwenden
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        } elseif (is_array($cookies)) {
            // Cookie-String erstellen
            $cookieStr = '';
            foreach ($cookies as $name => $value) {
                $cookieStr .= "$name=$value; ";
            }
            curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
        }
    } else {
        // Temp Cookie-Datei erstellen
        $cookieFile = $tempDir . '/cookies_' . uniqid() . '.txt';
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    
    // Header für Debugging
    if ($DEBUG) {
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verboseOutput = fopen($tempDir . '/curl_verbose_' . uniqid() . '.log', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verboseOutput);
    }
    
    // Zufälligen Referer setzen
    $referers = [
        'https://www.google.com/',
        'https://www.bing.com/',
        'https://search.yahoo.com/',
        'https://duckduckgo.com/'
    ];
    curl_setopt($ch, CURLOPT_REFERER, $referers[array_rand($referers)]);
    
    // Anfrage ausführen
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = null;
    
    if ($response === false) {
        $error = curl_error($ch);
        logMessage("cURL-Fehler: $error", "ERROR");
    }
    
    if ($DEBUG) {
        logMessage("HTTP-Status: $httpCode für URL: $url", $httpCode >= 200 && $httpCode < 300 ? "SUCCESS" : "WARNING");
        fclose($verboseOutput);
    }
    
    curl_close($ch);
    
    return [
        'body' => $response,
        'status' => $httpCode,
        'error' => $error
    ];
}

// YouTube-Video-Informationen abrufen
function getYoutubeVideoInfo($videoId, $proxy = null) {
    global $cookieDir;
    
    $cookieFile = $cookieDir . '/youtube_cookies.txt';
    $embed_url = "https://www.youtube.com/embed/$videoId";
    $watch_url = "https://www.youtube.com/watch?v=$videoId";
    
    // Ergebnisstruktur
    $result = [
        'videoId' => $videoId,
        'success' => false,
        'title' => null,
        'description' => null,
        'hasSubtitles' => false,
        'captionText' => '',
        'error' => null
    ];
    
    // 1. Versuche zuerst die Embed-Seite zu laden (geringere Wahrscheinlichkeit für Blockierung)
    logMessage("Lade Embed-Seite für Video $videoId...", "INFO");
    $embed_response = makeRequest($embed_url, 'GET', [], null, $proxy, $cookieFile);
    
    if ($embed_response['status'] >= 400) {
        logMessage("Fehler beim Laden der Embed-Seite: HTTP " . $embed_response['status'], "ERROR");
        $result['error'] = "HTTP-Fehler " . $embed_response['status'] . " beim Laden der Embed-Seite";
        return $result;
    }
    
    // Titel aus der Embed-Seite extrahieren
    if (preg_match('/<title>(.*?)<\/title>/i', $embed_response['body'], $titleMatches)) {
        $embed_title = trim($titleMatches[1]);
        $embed_title = str_replace(' - YouTube', '', $embed_title);
        $result['title'] = $embed_title;
        logMessage("Titel aus Embed-Seite: $embed_title", "INFO");
    }
    
    // Prüfen auf Bot-Erkennung oder Blockierung
    if (strpos($embed_response['body'], 'captcha') !== false || 
        strpos($embed_response['body'], 'robot') !== false ||
        strpos($embed_response['body'], 'unusual traffic') !== false) {
        logMessage("Bot-Erkennung in der Embed-Seite gefunden!", "WARNING");
        
        // Trotzdem versuchen, die Haupt-Seite zu laden
        logMessage("Versuche trotzdem die Hauptseite zu laden...", "INFO");
    }
    
    // 2. Hauptseite laden für mehr Informationen
    logMessage("Lade Hauptseite für Video $videoId...", "INFO");
    
    // Zusätzliche Header für bessere Tarnung
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: de,en-US;q=0.7,en;q=0.3',
        'DNT: 1',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1'
    ];
    
    $response = makeRequest($watch_url, 'GET', $headers, null, $proxy, $cookieFile);
    
    if ($response['status'] >= 400) {
        logMessage("Fehler beim Laden der Hauptseite: HTTP " . $response['status'], "ERROR");
        
        // Wenn wir zumindest einen Titel haben, können wir weitermachen
        if (!$result['title']) {
            $result['error'] = "HTTP-Fehler " . $response['status'] . " beim Laden der Hauptseite";
            return $result;
        }
        
        logMessage("Fortfahren mit eingeschränkten Informationen aus der Embed-Seite...", "WARNING");
    } else {
        logMessage("Hauptseite erfolgreich geladen", "SUCCESS");
        
        // Prüfen auf Bot-Erkennung
        if (strpos($response['body'], 'captcha') !== false || 
            strpos($response['body'], 'robot') !== false ||
            strpos($response['body'], 'unusual traffic') !== false) {
            logMessage("Bot-Erkennung in der Hauptseite gefunden!", "WARNING");
            $result['botDetection'] = true;
        }
        
        // Titel extrahieren (falls nicht aus Embed-Seite)
        if (!$result['title'] && preg_match('/<title>(.*?)<\/title>/i', $response['body'], $titleMatches)) {
            $result['title'] = trim(str_replace(' - YouTube', '', $titleMatches[1]));
            logMessage("Titel aus Hauptseite: " . $result['title'], "INFO");
        }
        
        // Beschreibung extrahieren
        if (preg_match('/"description":{"simpleText":"(.*?)"}/', $response['body'], $descMatches)) {
            $description = str_replace('\n', "\n", $descMatches[1]);
            $description = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '&#x\1;', $description);
            $description = html_entity_decode($description);
            $result['description'] = $description;
            logMessage("Beschreibung gefunden, Länge: " . strlen($description) . " Zeichen", "SUCCESS");
        } elseif (preg_match('/<meta name="description" content="(.*?)">/', $response['body'], $metaDescMatches)) {
            $result['description'] = html_entity_decode($metaDescMatches[1]);
            logMessage("Beschreibung aus Meta-Tag gefunden", "INFO");
        } else {
            logMessage("Keine Beschreibung gefunden", "WARNING");
        }
        
        // Prüfen, ob Untertitel verfügbar sind
        if (strpos($response['body'], '"hasCaptions":true') !== false) {
            $result['hasSubtitles'] = true;
            logMessage("Video hat Untertitel laut Metadaten", "INFO");
        }
    }
    
    // 3. Versuche, Untertitel zu laden
    if ($result['hasSubtitles'] || !isset($result['hasSubtitles'])) {
        logMessage("Versuche, Untertitel zu laden...", "INFO");
        
        // Versuchen wir verschiedene Methoden für Untertitel
        
        // Methode 1: Direkte Untertitel-URL (funktioniert oft nicht wegen Auth)
        $captionUrl = "https://www.youtube.com/api/timedtext?v=$videoId&lang=de";
        $captionResponse = makeRequest($captionUrl, 'GET', [], null, $proxy, $cookieFile);
        
        if ($captionResponse['status'] == 200 && !empty($captionResponse['body']) && strpos($captionResponse['body'], '<text ') !== false) {
            logMessage("Untertitel direkt geladen", "SUCCESS");
            
            // XML Untertitel parsen
            $xml = simplexml_load_string($captionResponse['body']);
            $captions = [];
            
            if ($xml) {
                foreach ($xml->text as $text) {
                    $captions[] = (string)$text;
                }
                
                $result['captionText'] = implode(' ', $captions);
                logMessage("Untertitel extrahiert, " . count($captions) . " Segmente", "SUCCESS");
            }
        } else {
            logMessage("Direkte Untertitel konnten nicht geladen werden, versuche alternative Methode...", "WARNING");
            
            // Methode 2: Invidious API probieren (erfordert keine Authentifizierung)
            $invidiousInstances = [
                'https://inv.riverside.rocks',
                'https://invidious.snopyta.org',
                'https://invidious.kavin.rocks',
                'https://vid.puffyan.us'
            ];
            
            $invidiousInstance = $invidiousInstances[array_rand($invidiousInstances)];
            $captionUrl = "$invidiousInstance/api/v1/captions/$videoId";
            
            logMessage("Versuche Invidious API für Untertitel: $captionUrl", "INFO");
            $invidiousResponse = makeRequest($captionUrl, 'GET', [], null, $proxy);
            
            if ($invidiousResponse['status'] == 200 && !empty($invidiousResponse['body'])) {
                $captionData = json_decode($invidiousResponse['body'], true);
                
                if (is_array($captionData) && !empty($captionData)) {
                    // Finde die bevorzugte Sprache (Deutsch oder Englisch)
                    $preferredCaptions = null;
                    foreach ($captionData as $caption) {
                        if (isset($caption['languageCode'])) {
                            if ($caption['languageCode'] == 'de') {
                                $preferredCaptions = $caption;
                                break;
                            } elseif ($caption['languageCode'] == 'en' && !$preferredCaptions) {
                                $preferredCaptions = $caption;
                            }
                        }
                    }
                    
                    if ($preferredCaptions && isset($preferredCaptions['url'])) {
                        logMessage("Gefundene Untertitel in Sprache: " . $preferredCaptions['languageCode'], "INFO");
                        
                        // Lade die eigentlichen Untertitel
                        $captionUrl = $preferredCaptions['url'];
                        if (strpos($captionUrl, '//') === 0) {
                            $captionUrl = 'https:' . $captionUrl;
                        }
                        
                        $captionFileResponse = makeRequest($captionUrl, 'GET', [], null, $proxy);
                        
                        if ($captionFileResponse['status'] == 200 && !empty($captionFileResponse['body'])) {
                            // Parse VTT/SRT Format
                            $captionText = $captionFileResponse['body'];
                            $captionLines = [];
                            
                            // Einfache Extraktion des Textes aus VTT/SRT
                            preg_match_all('/-->.*\n(.*)\n/U', $captionText, $matches);
                            if (isset($matches[1]) && !empty($matches[1])) {
                                $captionLines = $matches[1];
                                $result['captionText'] = implode(' ', $captionLines);
                                logMessage("Untertitel extrahiert, " . count($captionLines) . " Segmente", "SUCCESS");
                            }
                        }
                    }
                }
            } else {
                logMessage("Invidious API für Untertitel fehlgeschlagen", "WARNING");
            }
        }
    }
    
    // Erfolg, wenn wir zumindest den Titel haben
    if ($result['title']) {
        $result['success'] = true;
        logMessage("Videoanalyse abgeschlossen", "SUCCESS");
    } else {
        $result['error'] = $result['error'] ?? "Konnte keine Video-Informationen extrahieren";
        logMessage("Videoanalyse fehlgeschlagen: " . $result['error'], "ERROR");
    }
    
    return $result;
}

// Hauptfunktion zur Verarbeitung eines YouTube-Videos
function processYoutubeVideo($url, $proxy = null) {
    global $outputDir, $tempDir;
    
    // Videoanalyse starten
    logMessage("Starte Analyse für URL: $url", "INFO");
    
    // Video-ID extrahieren
    $videoId = extractYoutubeId($url);
    if (!$videoId) {
        logMessage("Keine gültige YouTube-ID gefunden in: $url", "ERROR");
        return [
            'success' => false,
            'error' => 'Keine gültige YouTube-URL'
        ];
    }
    
    logMessage("Extrahierte YouTube-ID: $videoId", "INFO");
    
    // Versuche, Video-Informationen zu laden
    $result = getYoutubeVideoInfo($videoId, $proxy);
    
    // Speichern der Ergebnisse
    if ($result['success']) {
        // Speichere Text in Datei
        $outputFile = $outputDir . "/youtube_transcript_$videoId.txt";
        $outputContent = "Titel: " . $result['title'] . "\n\n";
        
        if (!empty($result['description'])) {
            $outputContent .= "Beschreibung:\n" . $result['description'] . "\n\n";
        }
        
        if (!empty($result['captionText'])) {
            $outputContent .= "Untertitel:\n" . $result['captionText'] . "\n";
        }
        
        file_put_contents($outputFile, $outputContent);
        logMessage("Ergebnis gespeichert in: $outputFile", "INFO");
        
        // Speichere JSON mit Metadaten
        $jsonFile = $outputDir . "/youtube_data_$videoId.json";
        file_put_contents($jsonFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        logMessage("Metadaten gespeichert in: $jsonFile", "INFO");
    }
    
    return $result;
}

// HTML-Formular und Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formular wurde abgeschickt
    $youtubeUrl = $_POST['youtube_url'] ?? '';
    $proxyUrl = $_POST['proxy_url'] ?? '';
    
    if (!empty($youtubeUrl)) {
        echo "<div class='processing-status'>";
        echo "<h3>Verarbeite Video...</h3>";
        echo "<div id='status-updates'></div>";
        
        // Sofortiges Flushen der Ausgabe
        echo str_pad('', 4096) . "\n";
        ob_flush();
        flush();
        
        // Verarbeite das Video mit Proxy
        $result = processYoutubeVideo($youtubeUrl, $proxyUrl);
        
        // Zeige Ergebnisse an
        echo "<div class='results'>";
        if ($result['success']) {
            echo "<h3>Videoanalyse erfolgreich</h3>";
            
            echo "<div class='video-info'>";
            echo "<h4>Titel:</h4>";
            echo "<p>" . htmlspecialchars($result['title']) . "</p>";
            
            echo "<h4>Beschreibung:</h4>";
            if (!empty($result['description'])) {
                echo "<pre>" . htmlspecialchars($result['description']) . "</pre>";
            } else {
                echo "<p>Keine Beschreibung gefunden</p>";
            }
            
            echo "<h4>Untertitel/Transkript:</h4>";
            if (!empty($result['captionText'])) {
                echo "<pre>" . htmlspecialchars($result['captionText']) . "</pre>";
            } else {
                echo "<p>Keine Untertitel gefunden</p>";
            }
            echo "</div>";
            
            // Download-Links
            $videoId = extractYoutubeId($youtubeUrl);
            if ($videoId) {
                echo "<div class='download-links'>";
                echo "<h4>Downloads:</h4>";
                echo "<p><a href='output/youtube_transcript_$videoId.txt' download>Transkript als Text-Datei</a></p>";
                echo "<p><a href='output/youtube_data_$videoId.json' download>Metadaten als JSON-Datei</a></p>";
                echo "</div>";
            }
        } else {
            echo "<h3>Fehler bei der Videoanalyse</h3>";
            echo "<p>Fehler: " . htmlspecialchars($result['error'] ?? 'Unbekannter Fehler') . "</p>";
            
            if (isset($result['botDetection']) && $result['botDetection']) {
                echo "<div class='warning-box'>";
                echo "<h4>Bot-Erkennung:</h4>";
                echo "<p>YouTube hat erkannt, dass wir automatisierte Anfragen senden. Bitte versuchen Sie:</p>";
                echo "<ul>";
                echo "<li>Einen anderen Proxy-Server zu verwenden</li>";
                echo "<li>Später erneut zu versuchen</li>";
                echo "<li>Die Anzahl der Anfragen zu reduzieren</li>";
                echo "</ul>";
                echo "</div>";
            }
        }
        echo "</div>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Direkter Extraktor</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 1000px; margin: 0 auto; padding: 20px; }
        h1, h2, h3, h4 { color: #333; }
        form { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4285f4; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #3367d6; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; max-height: 300px; }
        .hint { font-size: 0.9em; color: #666; margin-top: -10px; margin-bottom: 10px; }
        .proxy-section { margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        .processing-status { background: #e8f4fd; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .results { background: #f9f9f9; padding: 20px; border-radius: 5px; }
        .download-links { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; }
        .video-info { margin: 15px 0; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
        .alternatives { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>YouTube Direkter Extraktor</h1>
    <p>Dieses Tool extrahiert Informationen von YouTube-Videos direkt mit PHP.</p>
    
    <?php if (!isset($_POST['youtube_url'])): ?>
    <form method="post" action="">
        <div>
            <label for="youtube_url">YouTube-URL:</label>
            <input type="text" name="youtube_url" id="youtube_url" required placeholder="https://www.youtube.com/watch?v=...">
        </div>
        
        <div class="proxy-section">
            <h3>Proxy-Einstellungen (Empfohlen)</h3>
            <p>Falls Ihre IP blockiert wird, sollten Sie einen Proxy-Server verwenden.</p>
            <div>
                <label for="proxy_url">Proxy-URL:</label>
                <input type="text" name="proxy_url" id="proxy_url" placeholder="http://username:password@proxy.example.com:8080">
                <p class="hint">Format: http://username:password@host:port oder http://host:port</p>
            </div>
        </div>
        
        <button type="submit">Video analysieren</button>
    </form>
    
    <div>
        <h3>Hinweise:</h3>
        <ul>
            <li>Dieses Tool verwendet PHP cURL, um YouTube-Inhalte zu analysieren.</li>
            <li>Die Ergebnisse werden im Ordner "output" gespeichert.</li>
            <li>Die Verwendung eines Proxys wird empfohlen, um Blockierungen zu vermeiden.</li>
            <li>Kostenlose Proxy-Listen finden Sie bei <a href="https://free-proxy-list.net/" target="_blank">free-proxy-list.net</a> oder <a href="https://www.proxynova.com/" target="_blank">ProxyNova</a>.</li>
        </ul>
    </div>
    
    <div class="alternatives">
        <h3>Alternative Extraktoren:</h3>
        <ul>
            <li><a href="youtube_headless.php">Headless Browser Extraktor</a> - Verwende Puppeteer für komplexere Szenarien</li>
        </ul>
    </div>
    <?php endif; ?>
</body>
</html> 