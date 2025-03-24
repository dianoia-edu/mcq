<?php
// YouTube Transkript-Tool mit Python-Backend
header('Content-Type: text/html; charset=utf-8');

// Konfiguration
$outputDir = __DIR__ . '/output';
$logsDir = __DIR__ . '/logs';
$pythonScript = __DIR__ . '/youtube_transcript.py';

// Verzeichnisse erstellen
foreach ([$outputDir, $logsDir] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Logging-Funktion
function logMessage($message) {
    global $logsDir;
    $logFile = $logsDir . '/transcript_' . date('Y-m-d') . '.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp $message";
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
    return $logEntry;
}

// Hilfsfunktion: Video-ID aus YouTube-URL extrahieren
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// Python-Script ausführen
function executeScript($videoId, $languages = 'de,en') {
    global $pythonScript;
    
    $log = logMessage("Führe Python-Script aus für Video-ID: $videoId, Sprachen: $languages");
    $command = "python3 " . escapeshellarg($pythonScript) . " " . 
               escapeshellarg($videoId) . " --languages=" . escapeshellarg($languages) . " 2>&1";
    
    $log = logMessage("Befehl: $command");
    $output = shell_exec($command);
    $log = logMessage("Rohdaten erhalten: " . substr($output, 0, 500) . (strlen($output) > 500 ? "..." : ""));
    
    return $output;
}

// Form verarbeiten
$videoUrl = isset($_POST['video_url']) ? trim($_POST['video_url']) : '';
$languages = isset($_POST['languages']) ? trim($_POST['languages']) : 'de,en';

// HTML-Header ausgeben
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Transkript-Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .success {
            color: green;
            border-left: 4px solid green;
            padding-left: 10px;
        }
        .error {
            color: red;
            border-left: 4px solid red;
            padding-left: 10px;
        }
        .warning {
            color: orange;
            border-left: 4px solid orange;
            padding-left: 10px;
        }
        .info {
            color: #0066cc;
            border-left: 4px solid #0066cc;
            padding-left: 10px;
        }
        form {
            background: #f4f4f4;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        button {
            padding: 10px 15px;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        pre {
            background: #f9f9f9;
            padding: 10px;
            overflow-x: auto;
            border: 1px solid #ddd;
            max-height: 500px;
            overflow-y: auto;
        }
        .description {
            white-space: pre-line;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        .transcript {
            line-height: 1.8;
            padding: 10px;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .chapter {
            font-weight: bold;
            color: #333;
            margin-top: 10px;
            cursor: pointer;
        }
        .chapter:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>YouTube Transkript-Tool</h1>
    
    <div class="info">
        <p>Dieses Tool extrahiert Untertitel und Video-Informationen von YouTube-Videos. 
        Es verwendet die youtube-transcript-api Python-Bibliothek.</p>
    </div>
    
    <form method="post" action="">
        <h3>YouTube-URL eingeben:</h3>
        <input type="text" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($videoUrl); ?>" required>
        
        <h3>Bevorzugte Sprachen (Komma-getrennt):</h3>
        <input type="text" name="languages" placeholder="de,en,fr,..." value="<?php echo htmlspecialchars($languages); ?>">
        
        <button type="submit">Transkript abrufen</button>
    </form>

<?php
// Wenn ein Video-URL übermittelt wurde
if (!empty($videoUrl)) {
    $videoId = extractYoutubeId($videoUrl);
    
    if (!$videoId) {
        echo "<h3 class='error'>Ungültige YouTube-URL! Bitte gib eine gültige URL ein.</h3>";
    } else {
        echo "<h2>Verarbeite Video: ID $videoId</h2>";
        
        // Python installiert?
        $pythonCheck = shell_exec("python3 --version 2>&1");
        if (strpos($pythonCheck, "Python 3") === false) {
            echo "<h3 class='error'>Python 3 nicht gefunden. Bitte installiere Python 3 auf dem Server.</h3>";
            exit;
        }
        
        // youtube-transcript-api installiert?
        if (!file_exists($pythonScript)) {
            echo "<h3 class='error'>Python-Script nicht gefunden: $pythonScript</h3>";
            exit;
        }
        
        echo "<div class='info'>Rufe Videoinformationen und Transkript ab...</div>";
        // Ausgabepuffer leeren für Live-Updates
        ob_flush();
        flush();
        
        // Script ausführen
        $jsonOutput = executeScript($videoId, $languages);
        
        // Ergebnisse verarbeiten
        try {
            $data = json_decode($jsonOutput, true);
            
            if ($data && isset($data['title'])) {
                // Video-Informationen anzeigen
                echo "<h3 class='success'>Video gefunden:</h3>";
                echo "<p><strong>Titel:</strong> " . htmlspecialchars($data['title']) . "</p>";
                
                // Beschreibung anzeigen
                echo "<h3>Videobeschreibung:</h3>";
                echo "<div class='description'>" . nl2br(htmlspecialchars($data['description'])) . "</div>";
                
                // Kapitel extrahieren
                $chapters = [];
                preg_match_all('/(\d+:\d+(?::\d+)?)\s+(.+)/', $data['description'], $matches, PREG_SET_ORDER);
                
                if (!empty($matches)) {
                    echo "<h3 class='info'>Gefundene Kapitel:</h3>";
                    echo "<ul>";
                    foreach ($matches as $match) {
                        $timestamp = $match[1];
                        $chapterTitle = $match[2];
                        echo "<li><strong>$timestamp</strong> - " . htmlspecialchars($chapterTitle) . "</li>";
                        $chapters[] = ['time' => $timestamp, 'title' => $chapterTitle];
                    }
                    echo "</ul>";
                }
                
                // Untertitel anzeigen, falls vorhanden
                if (isset($data['transcript']) && isset($data['transcript']['success']) && $data['transcript']['success']) {
                    $transcript = $data['transcript'];
                    echo "<h3 class='success'>Transkript gefunden:</h3>";
                    echo "<p><strong>Sprache:</strong> " . htmlspecialchars($transcript['language']) . " (" . 
                         ($transcript['is_generated'] ? "automatisch generiert" : "manuell erstellt") . ")</p>";
                    
                    // Alle verfügbaren Untertitel anzeigen
                    if (!empty($transcript['available_transcripts'])) {
                        echo "<h4>Verfügbare Untertitel:</h4>";
                        echo "<ul>";
                        foreach ($transcript['available_transcripts'] as $t) {
                            echo "<li>" . htmlspecialchars($t['language']) . " (" . 
                                 ($t['is_generated'] ? "automatisch" : "manuell") . ")</li>";
                        }
                        echo "</ul>";
                    }
                    
                    // Transkript-Text anzeigen
                    echo "<h4>Transkript-Text:</h4>";
                    echo "<div class='transcript'>" . nl2br(htmlspecialchars($transcript['full_text'])) . "</div>";
                    
                    // Transkript speichern
                    $transcriptFile = "$outputDir/transcript_$videoId.txt";
                    file_put_contents($transcriptFile, $transcript['full_text']);
                    echo "<p>Transkript gespeichert in: $transcriptFile</p>";
                    
                    // Alle Daten als JSON speichern
                    $allDataFile = "$outputDir/video_$videoId.json";
                    file_put_contents($allDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    echo "<p>Alle Daten gespeichert in: $allDataFile</p>";
                    
                } else {
                    $errorMsg = isset($data['transcript']['error']) ? $data['transcript']['error'] : "Unbekannter Fehler";
                    echo "<h3 class='error'>Konnte kein Transkript finden: " . htmlspecialchars($errorMsg) . "</h3>";
                    
                    // Videodaten ohne Transkript speichern
                    $dataFile = "$outputDir/info_$videoId.json";
                    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    echo "<p>Videoinformationen gespeichert in: $dataFile</p>";
                }
                
            } else {
                echo "<h3 class='error'>Fehler beim Abrufen der Videoinformationen:</h3>";
                echo "<pre>" . htmlspecialchars($jsonOutput) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<h3 class='error'>Fehler beim Verarbeiten der JSON-Daten:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($jsonOutput) . "</pre>";
        }
    }
}
?>

</body>
</html> 