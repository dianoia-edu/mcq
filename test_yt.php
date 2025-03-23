<?php
// yt-dlp-whisper-test.php
header('Content-Type: text/html; charset=utf-8');

// OpenAI API-Key aus Konfigurationsdatei laden
require_once __DIR__ . '/includes/config/openai_config.php';
// Annahme: Die Variable $openai_api_key existiert in dieser Datei

// Konfiguration
$videoUrl = 'https://youtu.be/R6ahSCdPjF8?si=X0MHc3V4nFoeq6bj';
$tempDir = __DIR__ . '/temp';
$outputDir = __DIR__ . '/output';
$logFile = __DIR__ . '/youtube-whisper.log';

// Vollständiger Pfad zu yt-dlp
$ytdlpPath = '/home/mcqadmin/.local/bin/yt-dlp';

// Funktion zum Loggen
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage . "<br>";
}

// Verzeichnisse erstellen, falls nicht vorhanden
if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

// Eindeutige ID für diesen Prozess
$processId = uniqid();
$audioFile = "$tempDir/audio_$processId.mp3";
$transcriptionFile = "$outputDir/transcript_$processId.txt";

// Ausgabe starten
echo "<h1>YouTube Audio Extrahieren und mit OpenAI Whisper API Transkribieren</h1>";
echo "<p>Verarbeite Video: $videoUrl</p>";

try {
    // 1. Versuche Audio mit mehreren Methoden herunterzuladen
    logMessage("Starte Download von Audio aus YouTube-Video (mit erweiterten Optionen)...");
    
    // Mehrere Versuche mit verschiedenen Optionen
    $downloadSuccessful = false;
    
    // Versuch 1: Mit --no-check-certificate und erweiterten Optionen
    $ytdlpCommand1 = "$ytdlpPath -x --audio-format mp3 --audio-quality 0 --no-check-certificate --force-ipv4 --extractor-retries 3 --fragment-retries 3 -o '$audioFile' '$videoUrl' 2>&1";
    logMessage("Versuch 1: Mit erweiterten Optionen");
    $ytdlpOutput = shell_exec($ytdlpCommand1);
    logMessage("yt-dlp Ausgabe (Versuch 1): " . $ytdlpOutput);
    
    if (file_exists($audioFile)) {
        $downloadSuccessful = true;
        logMessage("Download mit Versuch 1 erfolgreich.");
    } else {
        // Versuch 2: Mit alternativer Extraktionsmethode
        logMessage("Versuch 2: Mit alternativer Extraktionsmethode");
        $ytdlpCommand2 = "$ytdlpPath -x --audio-format mp3 --audio-quality 0 --extractor-args 'youtube:player_client=android' -o '$audioFile' '$videoUrl' 2>&1";
        $ytdlpOutput = shell_exec($ytdlpCommand2);
        logMessage("yt-dlp Ausgabe (Versuch 2): " . $ytdlpOutput);
        
        if (file_exists($audioFile)) {
            $downloadSuccessful = true;
            logMessage("Download mit Versuch 2 erfolgreich.");
        } else {
            // Versuch 3: Mit Proxy-Option (falls verfügbar)
            logMessage("Versuch 3: Mit --geo-bypass-country Option");
            $ytdlpCommand3 = "$ytdlpPath -x --audio-format mp3 --audio-quality 0 --geo-bypass-country DE -o '$audioFile' '$videoUrl' 2>&1";
            $ytdlpOutput = shell_exec($ytdlpCommand3);
            logMessage("yt-dlp Ausgabe (Versuch 3): " . $ytdlpOutput);
            
            if (file_exists($audioFile)) {
                $downloadSuccessful = true;
                logMessage("Download mit Versuch 3 erfolgreich.");
            }
        }
    }
    
    if (!$downloadSuccessful) {
        throw new Exception("Fehler: Audiodatei konnte mit keiner Methode erstellt werden");
    }
    
    $fileSize = filesize($audioFile);
    logMessage("Audio erfolgreich gespeichert. Dateigröße: " . round($fileSize / 1024 / 1024, 2) . " MB");
    
    // 2. Audio mit OpenAI Whisper API transkribieren
    logMessage("Starte Transkription mit OpenAI Whisper API...");
    
    // Datei für den Upload vorbereiten
    if (!function_exists('curl_file_create')) {
        function curl_file_create($filename, $mimetype = '', $postname = '') {
            return "@$filename;filename="
                . ($postname ?: basename($filename))
                . ($mimetype ? ";type=$mimetype" : '');
        }
    }
    
    $curl = curl_init();
    $cFile = curl_file_create($audioFile, 'audio/mpeg', basename($audioFile));
    
    $postFields = [
        'file' => $cFile,
        'model' => 'whisper-1',
        'language' => 'de', // Deutsch
        'response_format' => 'text'
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 300, // Längeres Timeout für große Dateien
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $openai_api_key
        ],
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        throw new Exception("Fehler bei API-Anfrage: $error");
    }
    
    if ($httpCode != 200) {
        throw new Exception("API-Fehler mit Code $httpCode: $response");
    }
    
    // 3. Transkription speichern und ausgeben
    file_put_contents($transcriptionFile, $response);
    
    logMessage("Transkription erfolgreich abgeschlossen und gespeichert in $transcriptionFile");
    
    echo "<h2>Transkription:</h2>";
    echo "<div style='background-color: #f0f0f0; padding: 15px; border-radius: 5px;'>";
    echo "<pre>$response</pre>";
    echo "</div>";
    
    // 4. Temporäre Dateien bereinigen
    unlink($audioFile);
    logMessage("Temporäre Audiodatei gelöscht.");
    
} catch (Exception $e) {
    logMessage("FEHLER: " . $e->getMessage());
    echo "<div style='color: red; font-weight: bold;'>" . $e->getMessage() . "</div>";
}

// Systeminfo zur Diagnose
echo "<h3>Systeminformationen:</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Betriebssystem: " . PHP_OS . "\n";
echo "yt-dlp Pfad: $ytdlpPath\n";
echo "yt-dlp Version: " . trim(shell_exec("$ytdlpPath --version 2>&1")) . "\n";
echo "cURL Version: " . curl_version()['version'] . "\n";
echo "Ausgeführt als: " . trim(shell_exec("whoami 2>&1")) . "\n";
echo "</pre>";
?>
