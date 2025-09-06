<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Fehler-Logging aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function logError($message) {
    error_log("YouTube Transcript API: " . $message);
}

function sendJsonResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'transcript' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Nur POST-Requests erlaubt');
}

// Parameter validieren
$videoId = $_POST['video_id'] ?? '';
$format = $_POST['format'] ?? 'txt';

if (empty($videoId)) {
    sendJsonResponse(false, null, 'Video ID fehlt');
}

// YouTube Video ID validieren
if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
    sendJsonResponse(false, null, 'Ungültige YouTube Video ID');
}

logError("Transcript-Request für Video: $videoId, Format: $format");

try {
    // METHODE 1: YouTube Transcript API über Python-Script
    $transcript = getTranscriptViaPython($videoId);
    
    if ($transcript) {
        logError("Python-Transcript erfolgreich für: $videoId");
        sendJsonResponse(true, $transcript);
    }
    
    // METHODE 2: Alternative API
    $transcript = getTranscriptViaAlternativeAPI($videoId);
    
    if ($transcript) {
        logError("Alternative-API erfolgreich für: $videoId");
        sendJsonResponse(true, $transcript);
    }
    
    // METHODE 3: Fallback - Direct YouTube API
    $transcript = getTranscriptViaYouTubeAPI($videoId);
    
    if ($transcript) {
        logError("YouTube-API erfolgreich für: $videoId");
        sendJsonResponse(true, $transcript);
    }
    
    // Alle Methoden fehlgeschlagen
    logError("Alle Transcript-Methoden fehlgeschlagen für: $videoId");
    sendJsonResponse(false, null, 'Transcript nicht verfügbar - alle Methoden fehlgeschlagen');
    
} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    sendJsonResponse(false, null, 'Server-Fehler: ' . $e->getMessage());
}

function getTranscriptViaPython($videoId) {
    try {
        logError("Versuche Python-Transcript für: $videoId");
        
        // Python-Script aufrufen (falls vorhanden)
        $pythonScript = __DIR__ . '/../../scripts/youtube_transcript.py';
        
        if (!file_exists($pythonScript)) {
            logError("Python-Script nicht gefunden: $pythonScript");
            return false;
        }
        
        $command = "python \"$pythonScript\" \"$videoId\" 2>&1";
        $output = shell_exec($command);
        
        if (!$output) {
            logError("Python-Script produzierte keine Ausgabe");
            return false;
        }
        
        $result = json_decode($output, true);
        
        if ($result && isset($result['transcript'])) {
            return $result['transcript'];
        }
        
        logError("Python-Script Fehler: $output");
        return false;
        
    } catch (Exception $e) {
        logError("Python-Methode Fehler: " . $e->getMessage());
        return false;
    }
}

function getTranscriptViaAlternativeAPI($videoId) {
    try {
        logError("Versuche Alternative-API für: $videoId");
        
        // Alternative APIs versuchen
        $apis = [
            "https://subtitles-for-youtube.p.rapidapi.com/subtitles/$videoId",
            "https://youtube-transcriptor.p.rapidapi.com/transcript?video_id=$videoId",
            // Weitere APIs können hier hinzugefügt werden
        ];
        
        foreach ($apis as $apiUrl) {
            $result = fetchFromAPI($apiUrl, $videoId);
            if ($result) {
                return $result;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        logError("Alternative-API Fehler: " . $e->getMessage());
        return false;
    }
}

function getTranscriptViaYouTubeAPI($videoId) {
    try {
        logError("Versuche YouTube-API für: $videoId");
        
        // Direkte YouTube API - benötigt API Key
        $apiKey = ''; // TODO: API Key konfigurieren
        
        if (empty($apiKey)) {
            logError("YouTube API Key nicht konfiguriert");
            return false;
        }
        
        $url = "https://www.googleapis.com/youtube/v3/captions?part=snippet&videoId=$videoId&key=$apiKey";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: MCQ-Test-System/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 30
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if (!$response) {
            logError("YouTube API Request fehlgeschlagen");
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['items'])) {
            logError("YouTube API: Keine Captions gefunden");
            return false;
        }
        
        // Caption Download implementieren
        foreach ($data['items'] as $caption) {
            if ($caption['snippet']['language'] === 'de' || $caption['snippet']['language'] === 'en') {
                $captionId = $caption['id'];
                $captionUrl = "https://www.googleapis.com/youtube/v3/captions/$captionId?key=$apiKey";
                
                $captionData = file_get_contents($captionUrl, false, $context);
                
                if ($captionData) {
                    return parseCaption($captionData);
                }
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        logError("YouTube-API Fehler: " . $e->getMessage());
        return false;
    }
}

function fetchFromAPI($url, $videoId) {
    try {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: MCQ-Test-System/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 15
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if (!$response) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        if ($data && isset($data['transcript'])) {
            return $data['transcript'];
        }
        
        return false;
        
    } catch (Exception $e) {
        logError("API-Fetch Fehler für $url: " . $e->getMessage());
        return false;
    }
}

function parseCaption($captionData) {
    try {
        // Einfache Caption-Parsing-Logik
        $lines = explode("\n", $captionData);
        $transcript = [];
        
        $index = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || is_numeric($line) || strpos($line, '-->') !== false) {
                continue;
            }
            
            // Bereinige HTML-Tags
            $text = strip_tags($line);
            $text = html_entity_decode($text);
            
            if (!empty($text)) {
                $transcript[] = [
                    'start' => $index * 5, // Geschätzte Zeit
                    'duration' => 5,
                    'text' => $text
                ];
                $index++;
            }
        }
        
        return $transcript;
        
    } catch (Exception $e) {
        logError("Caption-Parse Fehler: " . $e->getMessage());
        return false;
    }
}

?>
