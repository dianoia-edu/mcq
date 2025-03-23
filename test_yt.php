<?php
// yt_test.php
header('Content-Type: text/html; charset=utf-8');

// API-Keys laden
require_once __DIR__ . '/includes/config/openai_config.php';
require_once __DIR__ . '/includes/config/youtube_config.php';

// Annahme: Die Variable heißt $youtube_api_key
if (!isset($youtube_api_key)) {
    die("YouTube API Key nicht gefunden. Überprüfe die Datei youtube_config.php.");
}

// Konfiguration
$videoUrl = isset($_GET['url']) ? $_GET['url'] : 'https://youtu.be/R6ahSCdPjF8?si=fTuowcPnRBL5bz2C';
$outputDir = __DIR__ . '/output';
$logFile = __DIR__ . '/logs/youtube-api.log';

// Verzeichnisse erstellen, falls nicht vorhanden
if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);
if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0755, true);

// Funktion zum Loggen
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage . "<br>";
    return $logMessage;
}

// Funktion zum Extrahieren der Video-ID
function extractVideoId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return null;
}

// HTML-Header ausgeben
echo "<!DOCTYPE html>
<html>
<head>
    <title>YouTube Transkript API Test</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        .logs { background: #333; color: #fff; padding: 15px; border-radius: 5px; margin-top: 20px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>YouTube Transkript API Test</h1>
        <form method='get'>
            <input type='text' name='url' placeholder='YouTube URL' value='$videoUrl' style='width: 80%; padding: 8px;'>
            <button type='submit'>Transkript abrufen</button>
        </form>
        <div id='results'>";

// Start des Verarbeitungsprozesses
try {
    logMessage("Starte Verarbeitung für: $videoUrl");
    
    // Video-ID extrahieren
    $videoId = extractVideoId($videoUrl);
    if (!$videoId) {
        throw new Exception("Konnte keine gültige YouTube Video-ID aus der URL extrahieren.");
    }
    
    logMessage("Extrahierte Video-ID: $videoId");
    
    // ------ METHODE 1: YouTube Data API für Grunddaten ------
    logMessage("METHODE 1: Verwende YouTube Data API...");
    
    // Video-Informationen (Titel, Beschreibung, usw.) abrufen
    $videoInfoUrl = "https://www.googleapis.com/youtube/v3/videos?id=$videoId&part=snippet&key=" . urlencode($youtube_api_key);
    
    $ch = curl_init($videoInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $videoInfo = curl_exec($ch);
    $videoInfoHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($videoInfoHttpCode !== 200) {
        logMessage("Fehler beim Abrufen der Video-Informationen: HTTP $videoInfoHttpCode");
        logMessage("API-Antwort: $videoInfo");
    } else {
        $videoInfoData = json_decode($videoInfo, true);
        
        if (!isset($videoInfoData['items'][0])) {
            logMessage("Video nicht gefunden oder nicht verfügbar.");
        } else {
            $videoTitle = $videoInfoData['items'][0]['snippet']['title'];
            $videoDescription = $videoInfoData['items'][0]['snippet']['description'];
            
            logMessage("Video gefunden: \"$videoTitle\"");
            echo "<h2>Video: " . htmlspecialchars($videoTitle) . "</h2>";
        }
    }
    
    // ------ METHODE 2: YouTube Captions API für Untertitel ------
    logMessage("METHODE 2: Verwende YouTube Captions API...");
    
    // Liste der verfügbaren Untertitel abrufen
    $captionsListUrl = "https://www.googleapis.com/youtube/v3/captions?videoId=$videoId&part=snippet&key=" . urlencode($youtube_api_key);
    
    $ch = curl_init($captionsListUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $captionsList = curl_exec($ch);
    $captionsListHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($captionsListHttpCode !== 200) {
        logMessage("Fehler beim Abrufen der Untertitelliste: HTTP $captionsListHttpCode");
        logMessage("API-Antwort: $captionsList");
    } else {
        $captionsData = json_decode($captionsList, true);
        
        if (!isset($captionsData['items']) || count($captionsData['items']) === 0) {
            logMessage("Keine Untertitel für dieses Video gefunden. YouTube API unterstützt nicht den direkten Zugriff auf automatisch generierte Untertitel ohne OAuth2-Authentifizierung.");
        } else {
            logMessage("Untertitel gefunden: " . count($captionsData['items']));
            
            // Hier würden wir normalerweise die Untertitel herunterladen,
            // aber das erfordert OAuth2-Authentifizierung, was für einen Produktionsserver komplex ist
            logMessage("Zum direkten Herunterladen von Untertiteln ist OAuth2-Authentifizierung erforderlich.");
        }
    }
    
    // ------ METHODE 3: YouTube Transcript API (3rd-Party) ------
    logMessage("METHODE 3: Verwende YouTube Transcript API (3rd-Party)...");
    
    // Für diese Methode verwenden wir einen öffentlichen Service
    // Hinweis: Dieser ist ein Beispiel und könnte instabil sein
    $transcriptApiUrl = "https://zenuzon-yt-transcriptor.hf.space/api/transcript?videoId=$videoId";
    
    $ch = curl_init($transcriptApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $transcriptResponse = curl_exec($ch);
    $transcriptHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $transcriptError = curl_error($ch);
    curl_close($ch);
    
    if ($transcriptError) {
        logMessage("Fehler bei Transkript-API-Anfrage: $transcriptError");
    } elseif ($transcriptHttpCode !== 200) {
        logMessage("Fehler beim Abrufen des Transkripts: HTTP $transcriptHttpCode");
        logMessage("API-Antwort: $transcriptResponse");
    } else {
        $transcriptData = json_decode($transcriptResponse, true);
        
        if (isset($transcriptData['transcript']) && !empty($transcriptData['transcript'])) {
            $transcript = $transcriptData['transcript'];
            logMessage("Transkript erfolgreich abgerufen! Länge: " . strlen($transcript) . " Zeichen");
            
            // Speichern des Transkripts
            $transcriptFile = "$outputDir/transcript_$videoId.txt";
            file_put_contents($transcriptFile, $transcript);
            logMessage("Transkript gespeichert in: $transcriptFile");
            
            // Ausgabe des Transkripts
            echo "<h3 class='success'>Transkript erfolgreich abgerufen:</h3>";
            echo "<pre>" . htmlspecialchars($transcript) . "</pre>";
        } else {
            logMessage("Kein Transkript in der API-Antwort gefunden.");
            if (isset($transcriptData['error'])) {
                logMessage("API-Fehler: " . $transcriptData['error']);
            }
            throw new Exception("Transkript konnte nicht abgerufen werden.");
        }
    }
    
    // ------ METHODE 4: Fallback - yt-dlp versuchen ------
    if (!isset($transcript)) {
        logMessage("METHODE 4: Fallback zu yt-dlp...");
        
        $audioFile = tempnam(sys_get_temp_dir(), 'youtube_audio_');
        $ytdlpPath = '/home/mcqadmin/.local/bin/yt-dlp';
        
        $ytdlpCommand = "$ytdlpPath --write-auto-sub --sub-lang de --skip-download -o " . escapeshellarg(pathinfo($audioFile, PATHINFO_DIRNAME) . '/' . pathinfo($audioFile, PATHINFO_FILENAME)) . " " . escapeshellarg("https://www.youtube.com/watch?v=$videoId") . " 2>&1";
        
        logMessage("Ausführen von: $ytdlpCommand");
        $ytdlpOutput = shell_exec($ytdlpCommand);
        logMessage("yt-dlp Ausgabe: $ytdlpOutput");
        
        $vttFile = pathinfo($audioFile, PATHINFO_DIRNAME) . '/' . pathinfo($audioFile, PATHINFO_FILENAME) . '.de.vtt';
        
        if (file_exists($vttFile)) {
            logMessage("Untertiteldatei gefunden: $vttFile");
            $vttContent = file_get_contents($vttFile);
            
            // VTT zu Text konvertieren
            $lines = explode("\n", $vttContent);
            $plainText = '';
            $inTextBlock = false;
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, 'WEBVTT') === 0 || preg_match('/^\d{2}:\d{2}:\d{2}/', $line)) {
                    $inTextBlock = false;
                    continue;
                }
                
                if (!$inTextBlock) {
                    $inTextBlock = true;
                }
                
                if ($inTextBlock) {
                    $plainText .= $line . ' ';
                }
            }
            
            $transcript = trim($plainText);
            
            // Speichern des Transkripts
            $transcriptFile = "$outputDir/transcript_$videoId.txt";
            file_put_contents($transcriptFile, $transcript);
            logMessage("Transkript gespeichert in: $transcriptFile");
            
            // Ausgabe des Transkripts
            echo "<h3 class='success'>Transkript erfolgreich abgerufen (mit yt-dlp):</h3>";
            echo "<pre>" . htmlspecialchars($transcript) . "</pre>";
            
            // Temporäre Dateien löschen
            @unlink($audioFile);
            @unlink($vttFile);
        } else {
            logMessage("Keine Untertiteldatei gefunden mit yt-dlp.");
        }
    }
    
    // Falls wir Transkript haben und OpenAI API Key verfügbar ist, bieten wir die Option zur Zusammenfassung
    if (isset($transcript) && isset($openai_api_key)) {
        logMessage("Transkript kann mit OpenAI API zusammengefasst werden.");
        echo "<p>Das Transkript kann mit OpenAI API zusammengefasst werden, um das Wesentliche zu extrahieren.</p>";
    }
    
} catch (Exception $e) {
    logMessage("FEHLER: " . $e->getMessage());
    echo "<h3 class='error'>Fehler: " . htmlspecialchars($e->getMessage()) . "</h3>";
}

// HTML-Footer ausgeben
echo "</div>
        <h3>Logs:</h3>
        <div class='logs'>
            <pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>
        </div>
    </div>
</body>
</html>";
?>
