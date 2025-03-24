// METHODE 6: Direkte yt-dlp-Integration für Untertitel
logMessage("METHODE 6: Verwende yt-dlp für direkten Untertitel-Download (mit Browser-Cookies)...");
$baseFilename = $tempDir . '/yt_' . $videoId;
$ytdlpPath = '/home/mcqadmin/.local/bin/yt-dlp';

// Browser-Namen und deren Cookies-Pfade (anpassen je nach Server-Umgebung)
$browsers = [
    'chrome' => '--cookies-from-browser chrome',
    'firefox' => '--cookies-from-browser firefox',
    'edge' => '--cookies-from-browser edge',
    'safari' => '--cookies-from-browser safari',
    'opera' => '--cookies-from-browser opera',
    'brave' => '--cookies-from-browser brave'
];

$cookiesSuccess = false;

// Versuche nacheinander verschiedene Browser-Cookies
foreach ($browsers as $browser => $cookieOption) {
    logMessage("Versuche $browser Cookies...");
    
    // Versuche, Untertitel mit Browser-Cookies zu extrahieren
    $cmd = "$ytdlpPath $cookieOption --write-auto-sub --sub-format vtt --skip-download -o " . 
          escapeshellarg($baseFilename) . " " . 
          escapeshellarg("https://www.youtube.com/watch?v=$videoId") . " 2>&1";
    
    logMessage("Ausführen: $cmd");
    $output = shell_exec($cmd);
    logMessage("yt-dlp Ausgabe ($browser): " . substr($output, 0, 500) . (strlen($output) > 500 ? "..." : ""));
    
    // Prüfe auf Erfolg (keine Bot-Erkennung)
    if (strpos($output, "Sign in to confirm you're not a bot") === false) {
        $cookiesSuccess = true;
        logMessage("Erfolgreich mit $browser Cookies!");
        break;
    }
}

// Wenn die Browser-Cookies nicht funktionieren, versuche es mit einer Invidious-Instanz als Proxy
if (!$cookiesSuccess) {
    logMessage("Browser-Cookies funktionieren nicht. Versuche Invidious als Proxy...");
    $invidious_instances = [
        'https://yewtu.be/watch?v=',
        'https://invidious.snopyta.org/watch?v=',
        'https://vid.puffyan.us/watch?v='
    ];
    
    foreach ($invidious_instances as $instance) {
        $proxyUrl = $instance . $videoId;
        logMessage("Versuche Invidious-Instanz: $proxyUrl");
        
        $cmd = "$ytdlpPath --write-auto-sub --sub-format vtt --skip-download -o " . 
              escapeshellarg($baseFilename) . " " . 
              escapeshellarg($proxyUrl) . " 2>&1";
        
        logMessage("Ausführen: $cmd");
        $output = shell_exec($cmd);
        logMessage("yt-dlp Ausgabe (Invidious): " . substr($output, 0, 500) . (strlen($output) > 500 ? "..." : ""));
        
        if (strpos($output, "Sign in to confirm you're not a bot") === false) {
            $cookiesSuccess = true;
            logMessage("Erfolgreich mit Invidious-Instanz!");
            break;
        }
    }
}

// Suche nach VTT-Untertiteldateien
$subtitleFiles = glob($baseFilename . "*.vtt");
if (empty($subtitleFiles)) {
    logMessage("Keine VTT-Untertiteldateien gefunden. Versuche SRT-Format...");
    $subtitleFiles = glob($baseFilename . "*.srt");
}

if (!empty($subtitleFiles)) {
    $subtitleFile = $subtitleFiles[0];
    logMessage("Untertiteldatei gefunden: $subtitleFile");
    
    // Untertitel in Text konvertieren
    $subtitleContent = file_get_contents($subtitleFile);
    $plainText = '';
    
    // VTT oder SRT Format verarbeiten
    if (strpos($subtitleFile, '.vtt') !== false) {
        $lines = explode("\n", $subtitleContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && 
                strpos($line, 'WEBVTT') !== 0 && 
                !preg_match('/^\d{2}:\d{2}/', $line) &&
                strpos($line, ' --> ') === false) {
                $plainText .= strip_tags($line) . ' ';
            }
        }
    } else {
        // SRT Format
        $content = preg_replace('/\d+\s+\d{2}:\d{2}:\d{2},\d{3}\s+-->\s+\d{2}:\d{2}:\d{2},\d{3}\s+/s', "\n", $subtitleContent);
        $lines = explode("\n", strip_tags($content));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !is_numeric($line)) {
                $plainText .= $line . ' ';
            }
        }
    }
    
    $transcript = trim($plainText);
    
    if (!empty($transcript)) {
        logMessage("Transkript erfolgreich extrahiert mit yt-dlp. Länge: " . strlen($transcript) . " Zeichen");
        
        // Speichern und Ausgabe
        $transcriptFile = "$outputDir/transcript_$videoId.txt";
        file_put_contents($transcriptFile, $transcript);
        
        echo "<h3 class='success'>Transkript erfolgreich abgerufen (mit yt-dlp):</h3>";
        echo "<pre>" . htmlspecialchars(substr($transcript, 0, 1000)) . 
            (strlen($transcript) > 1000 ? "..." : "") . "</pre>";
        echo "<p>Vollständiger Text gespeichert in: $transcriptFile</p>";
        
        // Temporäre Dateien löschen
        array_map('unlink', $subtitleFiles);
    } else {
        logMessage("Konnte keinen Text aus der Untertiteldatei extrahieren.");
    }
} else {
    logMessage("Keine Untertiteldateien gefunden mit yt-dlp.");
    
    // Alternative: Versuche die Videobeschreibung zu bekommen
    logMessage("Versuche die Videobeschreibung zu erhalten...");
    $cmd = "$ytdlpPath --cookies-from-browser chrome --get-description " . 
          escapeshellarg("https://www.youtube.com/watch?v=$videoId") . " 2>&1";
    
    $description = shell_exec($cmd);
    
    if (!empty($description) && strpos($description, "Sign in to confirm you're not a bot") === false) {
        logMessage("Videobeschreibung erhalten. Länge: " . strlen($description) . " Zeichen");
        echo "<h3 class='warning'>Untertitel nicht verfügbar. Hier ist die Videobeschreibung:</h3>";
        echo "<pre>" . htmlspecialchars($description) . "</pre>";
    } else {
        logMessage("Konnte keine Videobeschreibung erhalten.");
    }
}

// METHODE 7: Python youtube-transcript-api
logMessage("METHODE 7: Verwende Python youtube-transcript-api...");

// Python-Skript erstellen
$pythonScript = <<<EOT
#!/usr/bin/env python3
import sys
import json
from youtube_transcript_api import YouTubeTranscriptApi

try:
    video_id = sys.argv[1]
    try:
        # Versuche deutsche Untertitel
        transcript_list = YouTubeTranscriptApi.list_transcripts(video_id)
        
        # Versuche zuerst manuell erstellte deutsche Untertitel
        try:
            transcript = transcript_list.find_transcript(['de'])
            is_generated = False
        except:
            # Fallback: Automatisch generierte oder andere Sprache
            try:
                transcript = transcript_list.find_generated_transcript(['de'])
                is_generated = True
            except:
                # Letzter Fallback: Englisch oder andere verfügbare Sprache
                available_transcripts = list(transcript_list)
                if available_transcripts:
                    transcript = available_transcripts[0]
                    is_generated = transcript.is_generated
                else:
                    raise Exception("Keine Untertitel verfügbar")
        
        # JSON-Antwort mit Metadaten
        result = {
            "success": True,
            "language": transcript.language,
            "language_code": transcript.language_code,
            "is_generated": is_generated,
            "transcript": transcript.fetch()
        }
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
except Exception as e:
    print(json.dumps({"success": False, "error": "Allgemeiner Fehler: " + str(e)}))
EOT;

$pythonFile = $tempDir . '/yt_transcript_' . uniqid() . '.py';
file_put_contents($pythonFile, $pythonScript);
chmod($pythonFile, 0755);

// Python-Skript ausführen
$pythonCmd = "python3 " . escapeshellarg($pythonFile) . " " . escapeshellarg($videoId) . " 2>&1";
logMessage("Ausführen: $pythonCmd");
$pythonOutput = shell_exec($pythonCmd);
logMessage("Python-Ausgabe erhalten: " . (strlen($pythonOutput) > 300 ? substr($pythonOutput, 0, 300) . "..." : $pythonOutput));

try {
    $result = json_decode($pythonOutput, true);
    if ($result && isset($result['success']) && $result['success'] === true) {
        // Erfolgreiche Python-Transkription
        $pythonTranscript = '';
        $language = $result['language'] ?? 'unbekannt';
        $isGenerated = $result['is_generated'] ? 'ja' : 'nein';
        
        foreach ($result['transcript'] as $item) {
            if (isset($item['text'])) {
                $pythonTranscript .= $item['text'] . ' ';
            }
        }
        
        $pythonTranscript = trim($pythonTranscript);
        
        if (!empty($pythonTranscript)) {
            logMessage("Python Transkript erfolgreich abgerufen. Sprache: $language, Automatisch generiert: $isGenerated");
            
            // Speichern des Transkripts
            $pythonTranscriptFile = "$outputDir/transcript_python_$videoId.txt";
            file_put_contents($pythonTranscriptFile, $pythonTranscript);
            
            echo "<h3 class='success'>Transkript erfolgreich abgerufen (mit Python):</h3>";
            echo "<p>Sprache: $language, Automatisch generiert: $isGenerated</p>";
            echo "<pre>" . htmlspecialchars(substr($pythonTranscript, 0, 1000)) . 
                 (strlen($pythonTranscript) > 1000 ? "..." : "") . "</pre>";
            echo "<p>Vollständiger Text gespeichert in: $pythonTranscriptFile</p>";
        }
    } else {
        $errorMsg = $result['error'] ?? 'Unbekannter Fehler';
        logMessage("Python-Skript fehlgeschlagen: $errorMsg");
        echo "<h3 class='error'>Python YouTube Transcript API: $errorMsg</h3>";
    }
} catch (Exception $e) {
    logMessage("Fehler bei der Verarbeitung der Python-Ausgabe: " . $e->getMessage());
}

// Aufräumen
@unlink($pythonFile);

// Am Ende alle Logs anzeigen
echo "<h3>Verarbeitungslogs:</h3>";
echo "<pre class='logs'>";
foreach ($logs as $log) {
    echo htmlspecialchars($log) . "\n";
}
echo "</pre>"; 