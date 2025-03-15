$data = [
    'model' => 'whisper-1',
    'file' => curl_file_create($audioFile),
    'response_format' => 'json',
    'language' => 'de',  // Explizit Deutsch als Sprache setzen
    'temperature' => 0.2 // Niedrigere Temperatur für genauere Transkription
];

$ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Whisper API Fehler: " . $response);
    throw new Exception("Fehler bei der Transkription (HTTP $httpCode)");
}

$result = json_decode($response, true);

if (!isset($result['text'])) {
    error_log("Whisper API unerwartete Antwort: " . print_r($result, true));
    throw new Exception("Unerwartete Antwort von Whisper API");
}

// Nachbearbeitung des Textes
$text = $result['text'];
$text = preg_replace('/\s+/', ' ', $text); // Mehrfache Leerzeichen entfernen
$text = trim($text);

// Debug-Log
error_log("Whisper Transkription (" . strlen($text) . " Zeichen): " . substr($text, 0, 200) . "...");

return $text;

function getYouTubeSubtitles($videoId) {
    // YouTube Data API Endpoint für Untertitel
    $url = "https://youtube.googleapis.com/youtube/v3/captions?part=snippet&videoId=" . $videoId;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . YOUTUBE_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("YouTube API Fehler: " . $response);
        return false;
    }
    
    $data = json_decode($response, true);
    if (!isset($data['items']) || empty($data['items'])) {
        return false;
    }
    
    // Suche nach deutschen Untertiteln
    $captionId = null;
    foreach ($data['items'] as $item) {
        if ($item['snippet']['language'] === 'de') {
            $captionId = $item['id'];
            break;
        }
    }
    
    if (!$captionId) {
        return false;
    }
    
    // Hole den Untertiteltext
    $url = "https://youtube.googleapis.com/youtube/v3/captions/" . $captionId;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . YOUTUBE_API_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function isTranscriptionUsable($text) {
    // Mindestlänge prüfen
    if (strlen($text) < 100) {
        return false;
    }
    
    // Prüfe auf zusammengeklebte Wörter (mehr als 30 Zeichen)
    if (preg_match('/\b\w{30,}\b/', $text)) {
        return false;
    }
    
    // Prüfe Satzzeichen-Verhältnis
    $punctuation = substr_count($text, '.') + substr_count($text, '!') + substr_count($text, '?');
    $words = str_word_count($text);
    if ($words > 50 && $punctuation < ($words / 50)) { // Erwarten mindestens 1 Satzzeichen pro 50 Wörter
        return false;
    }
    
    return true;
}

function transcribeVideo($videoId, $audioFile) {
    // Erste Transkription mit Whisper
    try {
        $whisperText = transcribeWithWhisper($audioFile);
        if (isTranscriptionUsable($whisperText)) {
            error_log("Whisper Transkription erfolgreich");
            return $whisperText;
        }
        error_log("Whisper Transkription unbrauchbar, versuche YouTube Untertitel");
    } catch (Exception $e) {
        error_log("Whisper Fehler: " . $e->getMessage());
    }
    
    // Fallback: YouTube Untertitel
    $subtitles = getYouTubeSubtitles($videoId);
    if ($subtitles) {
        error_log("YouTube Untertitel erfolgreich abgerufen");
        return $subtitles;
    }
    
    throw new Exception("Keine brauchbare Transkription verfügbar");
}

function transcribeWithWhisper($audioFile) {
    $data = [
        'model' => 'whisper-1',
        'file' => curl_file_create($audioFile),
        'response_format' => 'json',
        'language' => 'de',
        'temperature' => 0.2
    ];
    
    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Whisper API Fehler (HTTP $httpCode)");
    }
    
    $result = json_decode($response, true);
    if (!isset($result['text'])) {
        throw new Exception("Unerwartete Antwort von Whisper API");
    }
    
    $text = $result['text'];
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    error_log("Whisper Transkription (" . strlen($text) . " Zeichen): " . substr($text, 0, 200) . "...");
    
    return $text;
} 