<?php
// YouTube Transcript Extraction Tool
// Dieses Skript ermöglicht das Abrufen von Transkripten aus YouTube-Videos

// Fehlerberichterstattung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(120); // 2 Minuten Zeitlimit

// Verzeichnisse einrichten
$outputDir = __DIR__ . '/output';
$tempDir = sys_get_temp_dir();
$logsDir = __DIR__ . '/logs';

// Erstelle Verzeichnisse, falls nicht vorhanden
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0755, true);
}
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Log-Datei einrichten
$logFile = $logsDir . '/yt_transcript_' . date('Y-m-d') . '.log';
$logs = [];

// Logger-Funktion
function logMessage($message) {
    global $logs, $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp $message";
    $logs[] = $logEntry;
    
    // Auch in Datei loggen
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
}

// YouTube-Config laden (falls vorhanden)
$youtubeApiKey = '';
$youtubeConfigFile = __DIR__ . '/includes/config/youtube_config.php';
if (file_exists($youtubeConfigFile)) {
    include_once($youtubeConfigFile);
    if (isset($youtube_api_key)) {
        $youtubeApiKey = $youtube_api_key;
    } elseif (isset($api_key)) {
        $youtubeApiKey = $api_key;
    }
}

// Hilfsfunktion: Video-ID aus YouTube-URL extrahieren
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// Form verarbeiten
$videoUrl = isset($_POST['video_url']) ? trim($_POST['video_url']) : '';
$videoId = '';
$showForm = true;

// HTML-Header ausgeben
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Transkript Tool</title>
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
        form {
            background: #f4f4f4;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 80%;
            padding: 10px;
            margin-right: 10px;
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
        .logs {
            font-size: 12px;
            color: #666;
            max-height: 300px;
        }
    </style>
</head>
<body>
    <h1>YouTube Transkript-Extraktion</h1>
    
    <form method="post" action="">
        <h3>YouTube-URL eingeben:</h3>
        <input type="text" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($videoUrl); ?>" required>
        <button type="submit">Transkript abrufen</button>
    </form>

<?php
// Wenn ein Video-URL übermittelt wurde
if (!empty($videoUrl)) {
    $videoId = extractYoutubeId($videoUrl);
    
    if (!$videoId) {
        echo "<h3 class='error'>Ungültige YouTube-URL! Bitte gib eine gültige URL ein.</h3>";
    } else {
        $showForm = false;
        logMessage("Starte Verarbeitung für: $videoUrl");
        logMessage("Extrahierte Video-ID: $videoId");
        
        echo "<h2>Verarbeite Video: ID $videoId</h2>";
        
        // METHODE 1: YouTube Data API
        logMessage("METHODE 1: Verwende YouTube Data API...");
        if (!empty($youtubeApiKey)) {
            $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=$videoId&key=$youtubeApiKey&part=snippet";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                $data = json_decode($response, true);
                if (isset($data['items']) && !empty($data['items'])) {
                    $videoTitle = $data['items'][0]['snippet']['title'];
                    logMessage("Video gefunden: \"$videoTitle\"");
                    echo "<h3>Video: $videoTitle</h3>";
                } else {
                    logMessage("Video nicht gefunden oder API-Fehler");
                }
            } else {
                logMessage("Fehler beim Abrufen der Video-Informationen: HTTP $httpCode");
                logMessage("API-Antwort: " . substr($response, 0, 500));
                
                if ($httpCode == 403) {
                    echo "<h3 class='error'>API-Schlüssel ungültig oder Zugriff verweigert (HTTP 403)</h3>";
                    if (strpos($response, "keyExpired") !== false) {
                        echo "<p class='error'>API-Schlüssel abgelaufen. Bitte erneuere den API-Schlüssel.</p>";
                    }
                    if (strpos($response, "referer") !== false) {
                        echo "<p class='error'>HTTP-Referer-Beschränkungen sind aktiviert. Bitte konfiguriere den API-Schlüssel.</p>";
                    }
                }
            }
        } else {
            logMessage("Kein YouTube API-Schlüssel gefunden");
            echo "<p class='warning'>Kein YouTube API-Schlüssel konfiguriert. Methode 1 übersprungen.</p>";
        }
        
        // METHODE 2: YouTube Captions API
        logMessage("METHODE 2: Verwende YouTube Captions API...");
        if (!empty($youtubeApiKey)) {
            $captionsUrl = "https://www.googleapis.com/youtube/v3/captions?videoId=$videoId&key=$youtubeApiKey&part=snippet";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $captionsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                $data = json_decode($response, true);
                if (isset($data['items']) && !empty($data['items'])) {
                    $captionsCount = count($data['items']);
                    logMessage("Untertitel gefunden: $captionsCount");
                    
                    // OAuth2 erforderlich für direkten Download
                    logMessage("Zum direkten Herunterladen von Untertiteln ist OAuth2-Authentifizierung erforderlich.");
                    echo "<p class='warning'>Untertitel gefunden, aber zum direkten Herunterladen ist OAuth2-Authentifizierung erforderlich.</p>";
                } else {
                    logMessage("Keine Untertitel gefunden");
                }
            } else {
                logMessage("Fehler beim Abrufen der Untertitel-Informationen: HTTP $httpCode");
            }
        } else {
            logMessage("Methode 2 übersprungen (kein API-Schlüssel)");
        }
        
        // METHODE 3: 3rd-Party YouTube Transcript API
        logMessage("METHODE 3: Verwende YouTube Transcript API (3rd-Party)...");
        $transcriptApiUrl = "https://yt-transcript-api.vercel.app/api?videoId=$videoId";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $transcriptApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['transcript'])) {
                logMessage("Transkript erfolgreich abgerufen von 3rd-Party API");
                
                $transcript = $data['transcript'];
                $transcriptFile = "$outputDir/transcript_$videoId.txt";
                file_put_contents($transcriptFile, $transcript);
                
                echo "<h3 class='success'>Transkript erfolgreich abgerufen (mit 3rd-Party API):</h3>";
                echo "<pre>" . htmlspecialchars(substr($transcript, 0, 1000)) . 
                     (strlen($transcript) > 1000 ? "..." : "") . "</pre>";
                echo "<p>Vollständiger Text gespeichert in: $transcriptFile</p>";
            } else {
                logMessage("Kein Transkript in der API-Antwort gefunden");
            }
        } else {
            logMessage("Fehler beim Abrufen des Transkripts: HTTP $httpCode");
            logMessage("API-Antwort: " . substr($response, 0, 500));
        }
        
        // METHODE 4: Fallback zu yt-dlp
        logMessage("METHODE 4: Fallback zu yt-dlp...");
        $ytdlpPath = '/home/mcqadmin/.local/bin/yt-dlp';
        if (!file_exists($ytdlpPath)) {
            $ytdlpPath = 'yt-dlp'; // Versuche in PATH zu finden
        }
        
        $tempAudioFile = $tempDir . '/youtube_audio_' . substr(md5(uniqid()), 0, 7);
        $cmd = "$ytdlpPath --write-auto-sub --sub-lang de --skip-download -o " . 
               escapeshellarg($tempAudioFile) . " " . 
               escapeshellarg("https://www.youtube.com/watch?v=$videoId") . " 2>&1";
        
        logMessage("Ausführen von: $cmd");
        $output = shell_exec($cmd);
        logMessage("yt-dlp Ausgabe: " . substr($output, 0, 500) . (strlen($output) > 500 ? "..." : ""));
        
        // Prüfe auf Untertiteldateien
        $subtitleFile = '';
        $possibleExtensions = ['de.vtt', 'en.vtt', 'de.srt', 'en.srt'];
        foreach ($possibleExtensions as $ext) {
            if (file_exists($tempAudioFile . '.' . $ext)) {
                $subtitleFile = $tempAudioFile . '.' . $ext;
                break;
            }
        }
        
        if (!empty($subtitleFile)) {
            logMessage("Untertiteldatei gefunden: $subtitleFile");
            
            // Untertitel verarbeiten
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
                $transcriptFile = "$outputDir/transcript_$videoId.txt";
                file_put_contents($transcriptFile, $transcript);
                
                echo "<h3 class='success'>Transkript erfolgreich abgerufen (mit yt-dlp):</h3>";
                echo "<pre>" . htmlspecialchars(substr($transcript, 0, 1000)) . 
                     (strlen($transcript) > 1000 ? "..." : "") . "</pre>";
                echo "<p>Vollständiger Text gespeichert in: $transcriptFile</p>";
            }
            
            // Aufräumen
            @unlink($subtitleFile);
        } else {
            logMessage("Keine Untertiteldatei gefunden mit yt-dlp.");
        }

        // METHODE 6: Direkte yt-dlp-Integration für Untertitel
        logMessage("METHODE 6: Verwende yt-dlp für direkten Untertitel-Download (mit Cookie-Datei)...");
        $baseFilename = $tempDir . '/yt_' . $videoId;
        $ytdlpPath = '/home/mcqadmin/.local/bin/yt-dlp';
        
        // Pfad zur Cookie-Datei, die manuell hochgeladen wurde
        // TODO: Lade eine YouTube-Cookie-Datei hoch und gib hier den Pfad an
        $cookiePath = __DIR__ . '/youtube_cookies.txt';
        
        $cookiesSuccess = false;
        
        // Prüfe ob die Cookie-Datei existiert
        if (file_exists($cookiePath)) {
            logMessage("Verwende Cookie-Datei: $cookiePath");
            
            // Versuche, Untertitel mit der Cookie-Datei zu extrahieren
            $cmd = "$ytdlpPath --cookies " . escapeshellarg($cookiePath) . 
                   " --write-auto-sub --sub-format vtt --skip-download -o " . 
                   escapeshellarg($baseFilename) . " " . 
                   escapeshellarg("https://www.youtube.com/watch?v=$videoId") . " 2>&1";
            
            logMessage("Ausführen: $cmd");
            $output = shell_exec($cmd);
            logMessage("yt-dlp Ausgabe (mit Cookie-Datei): " . substr($output, 0, 500) . (strlen($output) > 500 ? "..." : ""));
            
            // Prüfe auf Erfolg (keine Bot-Erkennung)
            if (strpos($output, "Sign in to confirm you're not a bot") === false) {
                $cookiesSuccess = true;
                logMessage("Erfolgreich mit Cookie-Datei!");
            } else {
                logMessage("Cookie-Datei hat nicht funktioniert oder ist abgelaufen.");
            }
        } else {
            logMessage("Keine Cookie-Datei gefunden unter: $cookiePath");
            logMessage("Bitte lade eine YouTube-Cookie-Datei hoch und gib den Pfad im Skript an.");
            echo "<div class='error'><p>Cookie-Datei nicht gefunden. Bitte folge diesen Schritten, um eine YouTube-Cookie-Datei zu erstellen:</p>";
            echo "<ol>";
            echo "<li>Installiere die Browser-Erweiterung 'Get cookies.txt' für Chrome oder Firefox</li>";
            echo "<li>Besuche YouTube und melde dich an</li>";
            echo "<li>Verwende die Erweiterung, um Cookies als .txt-Datei zu exportieren</li>";
            echo "<li>Lade die Datei als 'youtube_cookies.txt' in das gleiche Verzeichnis wie dieses Skript hoch</li>";
            echo "</ol></div>";
        }
        
        // Wenn die Cookie-Datei nicht funktioniert, versuche es mit einer Invidious-Instanz als Proxy
        if (!$cookiesSuccess) {
            logMessage("Cookie-Datei nicht verfügbar oder funktioniert nicht. Versuche Invidious als Proxy...");
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
                
                if (strpos($output, "Sign in to confirm you're not a bot") === false && 
                    strpos($output, "ERROR") === false) {
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
            
            // Alternative: Versuche die Videobeschreibung zu bekommen (falls Cookie-Datei existiert)
            if (file_exists($cookiePath)) {
                logMessage("Versuche die Videobeschreibung zu erhalten...");
                $cmd = "$ytdlpPath --cookies " . escapeshellarg($cookiePath) . 
                      " --get-description " . 
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
        }
        
        // METHODE 7: Python youtube-transcript-api mit Proxy
        logMessage("METHODE 7: Verwende Python youtube-transcript-api mit Proxy...");
        
        // Python-Skript erstellen
        $pythonScript = <<<EOT
#!/usr/bin/env python3
import sys
import json
import random
import urllib.request
import urllib.error
import urllib.parse
import ssl
import socket

try:
    from youtube_transcript_api import YouTubeTranscriptApi
    
    # Proxy-Liste aus öffentlichen Quellen
    # Diese Liste kann je nach Verfügbarkeit aktualisiert werden
    proxy_list = [
        'https://proxy.scrapeops.io/v1/',
        'https://api.scrapeops.io/proxy/v1/',
        'https://proxy.webshare.io/',
        'https://proxy.zenrows.com/'
    ]

    def get_with_proxy(video_id):
        # Versuche direkt ohne Proxy
        try:
            transcript_list = YouTubeTranscriptApi.list_transcripts(video_id)
            
            # Versuche zuerst deutsche Untertitel
            try:
                transcript = transcript_list.find_transcript(['de'])
                is_generated = False
            except:
                # Fallback: Automatisch generiert
                try:
                    transcript = transcript_list.find_generated_transcript(['de', 'en'])
                    is_generated = True
                except:
                    # Letzter Fallback: Verfügbare Sprache
                    available_transcripts = list(transcript_list)
                    if available_transcripts:
                        transcript = available_transcripts[0]
                        is_generated = transcript.is_generated
                    else:
                        raise Exception("Keine Untertitel verfügbar")
            
            # JSON-Antwort
            return {
                "success": True,
                "language": transcript.language,
                "language_code": transcript.language_code,
                "is_generated": is_generated,
                "transcript": transcript.fetch()
            }
        except Exception as direct_error:
            print(f"Direkter Zugriff fehlgeschlagen: {str(direct_error)}", file=sys.stderr)
            
            # Versuche es über alternative Methoden, wenn direkt fehlschlägt
            try:
                # Invidious Instance API verwenden als Alternative
                invidious_instances = [
                    "https://invidious.snopyta.org/api/v1/captions/",
                    "https://yewtu.be/api/v1/captions/",
                    "https://vid.puffyan.us/api/v1/captions/"
                ]
                
                for instance in invidious_instances:
                    try:
                        print(f"Versuche Invidious-Instanz: {instance}", file=sys.stderr)
                        url = f"{instance}{video_id}"
                        
                        # SSL-Zertifikatsprüfung deaktivieren für einige Instanzen
                        context = ssl.create_default_context()
                        context.check_hostname = False
                        context.verify_mode = ssl.CERT_NONE
                        
                        req = urllib.request.Request(url)
                        req.add_header('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                        
                        with urllib.request.urlopen(req, context=context, timeout=10) as response:
                            data = json.loads(response.read().decode())
                            if data:
                                # Invidious liefert Untertitel in einem anderen Format
                                print(f"Invidious Daten erhalten: {len(data)} Einträge", file=sys.stderr)
                                
                                # Konvertiere Invidious-Format in youtube-transcript-api-Format
                                transcript_data = []
                                language = "unbekannt"
                                
                                for caption in data:
                                    if 'label' in caption:
                                        language = caption['label']
                                    
                                    if 'snippet' in caption:
                                        text = caption['snippet']['text']
                                        transcript_data.append({
                                            'text': text,
                                            'duration': 5.0,
                                            'start': 0.0
                                        })
                                
                                return {
                                    "success": True,
                                    "language": language,
                                    "language_code": "auto",
                                    "is_generated": True,
                                    "transcript": transcript_data,
                                    "source": "invidious"
                                }
                        
                    except Exception as invidious_error:
                        print(f"Fehler mit Invidious: {str(invidious_error)}", file=sys.stderr)
                
                # Wenn Invidious fehlschlägt, melden wir den ursprünglichen Fehler
                raise direct_error
                
            except Exception as proxy_error:
                print(f"Alle Methoden fehlgeschlagen: {str(proxy_error)}", file=sys.stderr)
                raise proxy_error
    
    video_id = sys.argv[1]
    result = get_with_proxy(video_id)
    print(json.dumps(result))
    
except ModuleNotFoundError:
    print(json.dumps({
        "success": False, 
        "error": "Das Modul 'youtube_transcript_api' ist nicht installiert. Bitte installieren Sie es mit: pip install youtube-transcript-api"
    }))
except Exception as e:
    print(json.dumps({
        "success": False,
        "error": str(e)
    }))
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
                $source = $result['source'] ?? 'youtube-transcript-api';
                
                foreach ($result['transcript'] as $item) {
                    if (isset($item['text'])) {
                        $pythonTranscript .= $item['text'] . ' ';
                    }
                }
                
                $pythonTranscript = trim($pythonTranscript);
                
                if (!empty($pythonTranscript)) {
                    logMessage("Python Transkript erfolgreich abgerufen. Sprache: $language, Automatisch generiert: $isGenerated, Quelle: $source");
                    
                    // Speichern des Transkripts
                    $pythonTranscriptFile = "$outputDir/transcript_python_$videoId.txt";
                    file_put_contents($pythonTranscriptFile, $pythonTranscript);
                    
                    echo "<h3 class='success'>Transkript erfolgreich abgerufen (mit Python):</h3>";
                    echo "<p>Sprache: $language, Automatisch generiert: $isGenerated, Quelle: $source</p>";
                    echo "<pre>" . htmlspecialchars(substr($pythonTranscript, 0, 1000)) . 
                         (strlen($pythonTranscript) > 1000 ? "..." : "") . "</pre>";
                    echo "<p>Vollständiger Text gespeichert in: $pythonTranscriptFile</p>";
                }
            } else {
                $errorMsg = $result['error'] ?? 'Unbekannter Fehler';
                logMessage("Python-Skript fehlgeschlagen: $errorMsg");
                echo "<h3 class='error'>Python YouTube Transcript API: $errorMsg</h3>";
                
                // Wenn alles fehlschlägt, versuchen wir die Videobeschreibung zu retten
                if (strpos($pythonOutput, "IP") !== false && strpos($pythonOutput, "YouTube is blocking") !== false) {
                    logMessage("YouTube blockiert die IP-Adresse des Servers. Fallback auf Videobeschreibung.");
                }
            }
        } catch (Exception $e) {
            logMessage("Fehler bei der Verarbeitung der Python-Ausgabe: " . $e->getMessage());
        }
        
        // Aufräumen
        @unlink($pythonFile);
        
        // METHODE 8: Fallback auf Videobeschreibung, wenn verfügbar
        if (isset($description) && !empty($description)) {
            logMessage("METHODE 8: Verwende Videobeschreibung als Fallback");
            
            // Verschönere die Beschreibung für bessere Lesbarkeit
            $cleanDescription = str_replace("\n\n", "<br><br>", $description);
            $cleanDescription = preg_replace('/(\bhttps?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $cleanDescription);
            
            // Extrahiere mögliche Kapitelmarken
            $chapters = [];
            preg_match_all('/(\d+:\d+(?::\d+)?)\s+(.+)/', $description, $matches, PREG_SET_ORDER);
            
            if (!empty($matches)) {
                echo "<h3 class='info'>Gefundene Kapitel:</h3>";
                echo "<ul>";
                foreach ($matches as $match) {
                    $timestamp = $match[1];
                    $chapterTitle = $match[2];
                    echo "<li><strong>$timestamp</strong> - $chapterTitle</li>";
                    $chapters[] = ['time' => $timestamp, 'title' => $chapterTitle];
                }
                echo "</ul>";
            }
            
            // Speichern der strukturierten Daten
            $descriptionData = [
                'title' => isset($videoTitle) ? $videoTitle : "Video $videoId",
                'description' => $description,
                'chapters' => $chapters,
                'retrieved_at' => date('Y-m-d H:i:s')
            ];
            
            $descriptionFile = "$outputDir/description_$videoId.json";
            file_put_contents($descriptionFile, json_encode($descriptionData, JSON_PRETTY_PRINT));
            
            echo "<div class='info'>";
            echo "<h3>Videobeschreibung:</h3>";
            echo "<p class='description'>" . nl2br(htmlspecialchars($description)) . "</p>";
            echo "<p>Beschreibungsdaten gespeichert in: $descriptionFile</p>";
            echo "</div>";
            
            // Zusätzliche Informationen anbieten
            echo "<h3>Nächste Schritte:</h3>";
            echo "<p>Da YouTube die Anfragen vom Server blockiert, gibt es folgende Optionen:</p>";
            echo "<ol>";
            echo "<li>Verwenden Sie dieses Tool auf einem lokalen Computer anstatt auf dem Server</li>";
            echo "<li>Verwenden Sie einen VPN-Dienst oder Proxy für den Server</li>";
            echo "<li>Kopieren Sie die Videobeschreibung und verwenden Sie OpenAI, um den Inhalt zu analysieren</li>";
            echo "</ol>";
        }
        
        // Am Ende alle Logs anzeigen
        echo "<h3>Verarbeitungslogs:</h3>";
        echo "<pre class='logs'>";
        foreach ($logs as $log) {
            echo htmlspecialchars($log) . "\n";
        }
        echo "</pre>";
    }
}
?> 

</body>
</html> 