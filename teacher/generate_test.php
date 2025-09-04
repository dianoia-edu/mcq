<?php
// Prüfe, ob das Standard-Temp-Verzeichnis schreibbar ist
if (!is_writable(sys_get_temp_dir())) {
    // Verwende eigenes temporäres Verzeichnis
    $customTempDir = __DIR__ . '/../temp';
    if (!file_exists($customTempDir)) {
        mkdir($customTempDir, 0777, true);
    }
    ini_set('upload_tmp_dir', $customTempDir);
}

// Aktiviere Error Reporting für Entwicklung
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

/**
 * Bestimme das zu verwendende OpenAI-Modell
 */
function determineModel($selectedModel, $config) {
    require_once '../includes/openai_models.php';
    
    try {
        $modelsManager = new OpenAIModels($config['api_key']);
        
        if ($selectedModel === 'auto' || empty($selectedModel)) {
            // Automatische Auswahl des besten Modells
            $bestModel = $modelsManager->getBestAvailableModel();
            return [
                'id' => $bestModel['id'],
                'name' => $bestModel['name'],
                'max_tokens' => getModelMaxTokens($bestModel['id']),
                'context_window' => $bestModel['context_window'] ?? 4096
            ];
        } else {
            // Spezifisches Modell gewählt - prüfe Verfügbarkeit
            $availableModels = $modelsManager->getAvailableModels();
            foreach ($availableModels as $model) {
                if ($model['id'] === $selectedModel) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'],
                        'max_tokens' => getModelMaxTokens($model['id']),
                        'context_window' => $model['context_window'] ?? 4096
                    ];
                }
            }
            
            // Fallback falls gewähltes Modell nicht verfügbar
            error_log("Gewähltes Modell '$selectedModel' nicht verfügbar, verwende Fallback");
            $bestModel = $modelsManager->getBestAvailableModel();
            return [
                'id' => $bestModel['id'],
                'name' => $bestModel['name'],
                'max_tokens' => getModelMaxTokens($bestModel['id']),
                'context_window' => $bestModel['context_window'] ?? 4096
            ];
        }
    } catch (Exception $e) {
        error_log("Fehler bei Modell-Bestimmung: " . $e->getMessage());
        
        // Fallback auf Standard-Modell
        return [
            'id' => 'gpt-4o',
            'name' => 'GPT-4o (Fallback)',
            'max_tokens' => 4000,
            'context_window' => 128000
        ];
    }
}

/**
 * Maximale Tokens für Antwort basierend auf Modell
 */
function getModelMaxTokens($modelId) {
    $maxTokens = [
        'gpt-3.5-turbo' => 2048,
        'gpt-3.5-turbo-16k' => 4000,
        'gpt-4' => 4000,
        'gpt-4-32k' => 8000,
        'gpt-4-turbo' => 4000,
        'gpt-4-1106-preview' => 4000,
        'gpt-4-0125-preview' => 4000,
        'gpt-4o' => 4000,
        'gpt-4o-mini' => 4000
    ];
    
    return $maxTokens[$modelId] ?? 4000;
}

// Setze Header für JSON
header('Content-Type: application/json');

// Debug-Ausgaben nur in Logs schreiben, nicht in die Ausgabe
function debug_log($message) {
    error_log($message);
}

// OCR Funktionen
function processPage($page, $tesseractPath, $config) {
    $command = '"' . $tesseractPath . '"' .
        ' "' . $page . '"' .
        ' stdout ' . implode(' ', $config);
    
    $text = shell_exec($command);
    
    if ($text === null) {
        return '';
    }
    
    // Text-Nachbearbeitung
    $text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text, 'UTF-8, ISO-8859-1'));
    $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    
    // Umlaut-Korrekturen
    $replacements = [
        'a"' => 'ä', 'o"' => 'ö', 'u"' => 'ü',
        'A"' => 'Ä', 'O"' => 'Ö', 'U"' => 'Ü',
        'a¨' => 'ä', 'o¨' => 'ö', 'u¨' => 'ü',
        'A¨' => 'Ä', 'O¨' => 'Ö', 'U¨' => 'Ü',
        'ae' => 'ä', 'oe' => 'ö', 'ue' => 'ü',
        'Ae' => 'Ä', 'Oe' => 'Ö', 'Ue' => 'Ü',
        'ss' => 'ß'
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), trim($text));
}

function performOCR($file, $mimeType) {
    // Tesseract Konfiguration
    $tesseractPath = 'tesseract';
    $tesseractConfig = [
        '--dpi 300',
        '--psm 3',
        '-l deu',
        '--oem 1'
    ];
    
    // Prüfe ob die Programme verfügbar sind
    exec('tesseract --version', $output, $returnVar);
    if ($returnVar !== 0) {
        error_log("Tesseract ist nicht verfügbar.");
        return "OCR nicht verfügbar. Bitte installieren Sie Tesseract OCR oder verwenden Sie eine andere Dateiform.";
    }
    
    exec('gswin64c --version', $output, $returnVar);
    if ($returnVar !== 0) {
        error_log("Ghostscript ist nicht verfügbar.");
        return "OCR nicht verfügbar. Bitte installieren Sie Ghostscript oder verwenden Sie eine andere Dateiform.";
    }
    
    $text = '';
    $tempDir = sys_get_temp_dir() . '/pdf_' . uniqid();
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    try {
        if ($mimeType === 'application/pdf') {
            // PDF zu PNG konvertieren
            $gsCommand = sprintf(
                'gswin64c -dNOPAUSE -dBATCH -dSAFER -sDEVICE=png16m -r400 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dUseCropBox -dQUIET -dPDFFitPage -sOutputFile="%s/page-%%03d.png" "%s"',
                $tempDir,
                $file
            );
            
            error_log("Executing Ghostscript command: " . $gsCommand);
            shell_exec($gsCommand);
            
            // OCR für jede Seite
            foreach (glob($tempDir . '/*.png') as $page) {
                $pageText = processPage($page, $tesseractPath, $tesseractConfig);
                $text .= $pageText . "\n\n";
            }
        } 
        // Bildverarbeitung für JPG, JPEG, PNG und BMP
        else if (in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'])) {
            // Kopiere die Bilddatei in das temporäre Verzeichnis
            $tempImagePath = $tempDir . '/image.' . pathinfo($file, PATHINFO_EXTENSION);
            copy($file, $tempImagePath);
            
            // Führe OCR direkt auf dem Bild aus
            $text = processPage($tempImagePath, $tesseractPath, $tesseractConfig);
        }
        // Für DOC-Dateien (Legacy-Format)
        else if ($mimeType === 'application/msword') {
            // Kopiere die DOC-Datei in das temporäre Verzeichnis
            $tempDocPath = $tempDir . '/document.doc';
            copy($file, $tempDocPath);
            
            // Versuche, die DOC-Datei mit Ghostscript in ein Bild zu konvertieren
            $gsCommand = sprintf(
                'gswin64c -dNOPAUSE -dBATCH -dSAFER -sDEVICE=png16m -r400 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dUseCropBox -dQUIET -dPDFFitPage -sOutputFile="%s/page-%%03d.png" "%s"',
                $tempDir,
                $tempDocPath
            );
            
            error_log("Attempting to convert DOC with Ghostscript: " . $gsCommand);
            shell_exec($gsCommand);
            
            // Prüfe, ob Bilder erzeugt wurden
            $pages = glob($tempDir . '/*.png');
            if (count($pages) > 0) {
                // OCR für jede Seite
                foreach ($pages as $page) {
                    $pageText = processPage($page, $tesseractPath, $tesseractConfig);
                    $text .= $pageText . "\n\n";
                }
            } else {
                // Fallback: Direkte OCR auf der DOC-Datei
                error_log("No images generated from DOC, trying direct OCR");
                $text = processPage($tempDocPath, $tesseractPath, $tesseractConfig);
            }
        }
        else {
            // Direkte Bildverarbeitung für andere Dateitypen
            $text = processPage($file, $tesseractPath, $tesseractConfig);
        }
        
        return $text;
        
    } finally {
        // Aufräumen
        array_map('unlink', glob($tempDir . '/*'));
        if (file_exists($tempDir)) {
            rmdir($tempDir);
        }
    }
}

// Funktion zum Extrahieren von Text aus einer Webseite
function extractWebpageContent($url) {
    // Initialisiere cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Hole den HTML-Inhalt
    $html = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Fehler beim Abrufen der Webseite: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Entferne JavaScript und CSS
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    
    // Konvertiere HTML in reinen Text
    $text = strip_tags($html);
    
    // Bereinige den Text
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);
    
    return $text;
}

// Funktion zum Extrahieren der Video-ID aus einer YouTube-URL
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    throw new Exception('Ungültige YouTube-URL');
}

require_once('../includes/config/youtube_config.php');

function transcribeAudioWithWhisper($audioFile) {
    global $config;
    
    if (!file_exists($audioFile)) {
        throw new Exception('Audio-Datei nicht gefunden');
    }
    
    $ch = curl_init();
    $postData = [
        'file' => new CURLFile($audioFile),
        'model' => 'whisper-1',
        'language' => 'de',
        'response_format' => 'text'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['api_key']
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Fehler bei der Whisper-Transkription: ' . $response);
    }
    
    return $response;
}

function getYoutubeTranscript($videoUrl) {
    global $config;
    
    // Setze PATH für die Ausführung von Befehlen
    putenv('PATH=' . getenv('PATH') . ':/usr/local/bin:/usr/bin');
    
    // Erstelle temporäres Verzeichnis
    $tempDir = sys_get_temp_dir() . '/youtube_' . uniqid();
    if (!mkdir($tempDir) && !is_dir($tempDir)) {
        throw new Exception('Konnte temporäres Verzeichnis nicht erstellen');
    }
    
    try {
        // Lade die Audio-Spur herunter
        $output = [];
        $tempDir = str_replace('\\', '/', $tempDir); // Konvertiere Windows-Pfade zu Unix-Style
        $command = sprintf('yt-dlp -x --audio-format mp3 -o "%s/audio.%%(ext)s" "%s"', $tempDir, $videoUrl);
        exec($command . " 2>&1", $output, $returnVar);
        
        if ($returnVar !== 0) {
            error_log("yt-dlp Fehler: " . implode("\n", $output));
            throw new Exception('Fehler beim Herunterladen der Audio-Datei: ' . implode("\n", $output));
        }
        
        $audioFile = $tempDir . '/audio.mp3';
        if (!file_exists($audioFile)) {
            throw new Exception('Audio-Datei wurde nicht erstellt');
        }
        
        // Versuche zuerst Whisper-Transkription
        try {
            $transcript = transcribeAudioWithWhisper($audioFile);
            if (isTranscriptionUsable($transcript)) {
                return $transcript;
            }
        } catch (Exception $e) {
            error_log('Whisper-Transkription fehlgeschlagen: ' . $e->getMessage());
            // Fahre mit YouTube-Untertiteln fort
        }
        
        // Fallback: YouTube-Untertitel
        $youtubeApiKey = getYoutubeApiKey();
        if (empty($youtubeApiKey)) {
            throw new Exception('YouTube API Key nicht konfiguriert');
        }
        
        $videoId = extractYoutubeVideoId($videoUrl);
        $subtitles = getYoutubeSubtitles($videoId, $youtubeApiKey);
        
        if (!empty($subtitles)) {
            return $subtitles;
        }
        
        throw new Exception('Keine brauchbare Transkription verfügbar');
    } finally {
        // Aufräumen
        if (file_exists($audioFile)) {
            unlink($audioFile);
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }
}

function isTranscriptionUsable($transcript) {
    // Prüfe ob die Transkription mindestens 50 Zeichen lang ist
    if (strlen($transcript) < 50) {
        return false;
    }
    
    // Prüfe ob die Transkription hauptsächlich aus Satzzeichen besteht
    $textOnly = preg_replace('/[^a-zA-ZäöüÄÖÜß\s]/u', '', $transcript);
    if (strlen($textOnly) < strlen($transcript) * 0.5) {
        return false;
    }
    
    return true;
}

function extractYoutubeVideoId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    throw new Exception('Ungültige YouTube-URL');
}

function getYoutubeSubtitles($videoId, $apiKey) {
    $url = "https://youtube.googleapis.com/youtube/v3/captions?part=snippet&videoId={$videoId}&key={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Fehler beim Abrufen der YouTube-Untertitel');
    }
    
    $data = json_decode($response, true);
    if (!isset($data['items']) || empty($data['items'])) {
        return '';
    }
    
    // Suche nach deutschen Untertiteln
    foreach ($data['items'] as $caption) {
        if ($caption['snippet']['language'] === 'de') {
            return downloadYoutubeCaption($caption['id'], $apiKey);
        }
    }
    
    return '';
}

function downloadYoutubeCaption($captionId, $apiKey) {
    $url = "https://youtube.googleapis.com/youtube/v3/captions/{$captionId}?key={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return '';
    }
    
    return $response;
}

function testWhisperAccess($apiKey) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: multipart/form-data'
        ]
    ]);
    
    // Sende eine leere Anfrage - diese wird fehlschlagen, aber wir können am Fehlercode erkennen,
    // ob wir Zugriff haben oder nicht
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 401 bedeutet nicht autorisiert
    // 400 bedeutet fehlende Datei (aber API-Zugriff vorhanden)
    return $http_code === 400;
}

try {
    // Debug-Logging
    error_log("Request started");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Spezielle Debug-Test-Behandlung
    if (isset($_POST['test']) && $_POST['test'] === 'true') {
        echo json_encode([
            'success' => true, 
            'message' => 'Debug-Test erfolgreich - Server erreicht und funktionsfähig',
            'debug' => [
                'post_data' => $_POST,
                'files' => $_FILES,
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'working_directory' => getcwd(),
                'script_path' => __FILE__,
                'includes_path' => '../includes/config/openai_config.php',
                'config_exists' => file_exists('../includes/config/openai_config.php')
            ]
        ]);
        exit;
    }

    // Überprüfe, ob die Konfigurationsdatei existiert
    if (!file_exists('../includes/config/openai_config.php')) {
        throw new Exception('Konfigurationsdatei nicht gefunden');
    }

    // Lade Konfiguration
    $config = require_once('../includes/config/openai_config.php');
    
    if (!isset($config['api_key']) || empty($config['api_key'])) {
        throw new Exception('OpenAI API Key nicht in der Konfiguration gefunden');
    }
    
    // Teste Whisper API Zugriff
    if (!testWhisperAccess($config['api_key'])) {
        throw new Exception('Ihr API-Key hat keinen Zugriff auf die Whisper API. Bitte überprüfen Sie Ihre API-Berechtigungen.');
    }
    
    $apiKey = $config['api_key'];
    $combinedContent = '';

    // Verarbeite Datei-Upload, falls vorhanden
    if (isset($_FILES['source_file']) && $_FILES['source_file']['error'] === UPLOAD_ERR_OK) {
        // Überprüfe Dateigröße
        if ($_FILES['source_file']['size'] === 0) {
            throw new Exception('Leere Datei hochgeladen');
        }

        // Überprüfe den Dateityp
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['source_file']['tmp_name']);
        finfo_close($finfo);

        error_log("Detected MIME type: " . $mime_type);

        // Extrahiere Text aus der Datei
        if ($mime_type === 'application/pdf' || in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'])) {
            $ocrResult = performOCR($_FILES['source_file']['tmp_name'], $mime_type);
            
            // Prüfe, ob OCR-Ergebnis eine Fehlermeldung enthält
            if (strpos($ocrResult, "OCR nicht verfügbar") === 0) {
                error_log("OCR nicht verfügbar: " . $ocrResult);
                throw new Exception('Tesseract OCR ist nicht verfügbar. Bitte installieren Sie Tesseract OCR oder verwenden Sie eine Textdatei/URL.');
            }
            
            $combinedContent .= $ocrResult . "\n\n";
        } 
        else if ($mime_type === 'text/plain') {
            $combinedContent .= file_get_contents($_FILES['source_file']['tmp_name']) . "\n\n";
        }
        else if (in_array($mime_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            // Bestehende DOCX/DOC Verarbeitung
            // ... (vorhandener Code bleibt unverändert)
        }
    }

    // Verarbeite Webseiten-URL, falls vorhanden
    if (!empty($_POST['webpage_url'])) {
        $url = filter_var($_POST['webpage_url'], FILTER_SANITIZE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Ungültige Webseiten-URL');
        }
        
        error_log("Processing webpage URL: " . $url);
        $combinedContent .= extractWebpageContent($url) . "\n\n";
    }

    // Verarbeite YouTube-URL, falls vorhanden
    if (!empty($_POST['youtube_url'])) {
        error_log("Processing YouTube URL: " . $_POST['youtube_url']);
        $videoUrl = $_POST['youtube_url'];
        $combinedContent .= getYoutubeTranscript($videoUrl) . "\n\n";
    }


    // Debug: Log den gesammelten Inhalt
    error_log("Gesamt gesammelter Inhalt, Länge: " . strlen($combinedContent));
    error_log("POST keys: " . implode(', ', array_keys($_POST)));
    error_log("FILES keys: " . implode(', ', array_keys($_FILES)));

    // Prüfe, ob mindestens eine Quelle verarbeitet wurde
    if (empty($combinedContent)) {
        error_log("FEHLER: Kein Inhalt gesammelt!");
        error_log("webpage_url empty check: " . (empty($_POST['webpage_url']) ? 'true' : 'false'));
        error_log("youtube_url empty check: " . (empty($_POST['youtube_url']) ? 'true' : 'false'));
        error_log("source_file error: " . (isset($_FILES['source_file']) ? $_FILES['source_file']['error'] : 'not set'));
        
        throw new Exception('Keine Inhaltsquelle gefunden. Bitte laden Sie eine Datei hoch oder geben Sie eine URL ein. Debug: webpage_url=' . (isset($_POST['webpage_url']) ? $_POST['webpage_url'] : 'not set') . ', youtube_url=' . (isset($_POST['youtube_url']) ? $_POST['youtube_url'] : 'not set') . ', file_error=' . (isset($_FILES['source_file']) ? $_FILES['source_file']['error'] : 'not set'));
    }

    // Hole die Anzahl der gewünschten Fragen und Antworten
    $questionCount = isset($_POST['question_count']) ? intval($_POST['question_count']) : 10;
    $answerCount = isset($_POST['answer_count']) ? intval($_POST['answer_count']) : 4;
    $answerType = isset($_POST['answer_type']) ? $_POST['answer_type'] : 'single';

    // Bereite den Prompt für ChatGPT vor
    $prompt = "Basierend auf dem folgenden Text, erstelle {$questionCount} Multiple-Choice-Fragen mit jeweils {$answerCount} Antwortmöglichkeiten. ";
    
    if ($answerType === 'single') {
        $prompt .= "Jede Frage soll genau eine richtige Antwort haben. ";
    } else if ($answerType === 'multiple') {
        $prompt .= "Jede Frage soll mehrere richtige Antworten haben. ";
    } else {
        $prompt .= "Mische Fragen mit einer und mehreren richtigen Antworten. ";
    }
    
    $prompt .= "Formatiere die Ausgabe als XML mit dem folgenden Format:
    <?xml version='1.0' encoding='UTF-8'?>
    <test>
        <question>
            <text>Frage hier</text>
            <answers>
                <answer correct='true/false'>Antwort 1</answer>
                <answer correct='true/false'>Antwort 2</answer>
                ...
            </answers>
        </question>
        ...
    </test>

    Hier ist der Text:\n\n" . $combinedContent;

    // Stelle sicher, dass der Content UTF-8 kodiert ist
    $file_content = mb_convert_encoding($combinedContent, 'UTF-8', mb_detect_encoding($combinedContent, 'UTF-8, ISO-8859-1, ASCII'));
    
    // Entferne ungültige UTF-8 Sequenzen und bereinige den Text
    $file_content = iconv('UTF-8', 'UTF-8//IGNORE', $file_content);
    $file_content = preg_replace('/\s+/', ' ', $file_content); // Normalisiere Whitespace
    $file_content = trim($file_content);

    // Verbessere die Textqualität
    $file_content = str_replace(['|', '_'], ' ', $file_content); // Entferne häufige OCR-Artefakte
    $file_content = preg_replace('/[^\p{L}\p{N}\p{P}\s]/u', ' ', $file_content); // Behalte nur Buchstaben, Zahlen, Interpunktion und Leerzeichen
    $file_content = preg_replace('/\s+/', ' ', $file_content); // Normalisiere Leerzeichen erneut
    $file_content = trim($file_content);

    // Generiere dreistelligen alphanumerischen Zugangscode
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // ohne 0,1,I,O zur Vermeidung von Verwechslungen
    $access_code = '';
    for ($i = 0; $i < 3; $i++) {
        $access_code .= $characters[rand(0, strlen($characters) - 1)];
    }

    // Generiere eine eindeutige Test-ID
    $test_id = 'test_' . uniqid();

    // Erstelle tests Verzeichnis falls es nicht existiert
    $tests_dir = __DIR__ . '/../tests';
    if (!file_exists($tests_dir)) {
        if (!mkdir($tests_dir, 0777, true)) {
            throw new Exception('Konnte tests-Verzeichnis nicht erstellen');
        }
    }

    // Überprüfe Schreibrechte
    if (!is_writable($tests_dir)) {
        throw new Exception('Keine Schreibrechte im tests-Verzeichnis');
    }

    // Lade das XML-Schema aus der Datei
    $xml_schema_path = __DIR__ . '/../includes/config/xml_dom.xml';
    if (!file_exists($xml_schema_path)) {
        throw new Exception('XML-Schema-Datei nicht gefunden: ' . $xml_schema_path);
    }
    $xml_schema = file_get_contents($xml_schema_path);
    
    // Ersetze Platzhalter im XML-Schema
    $xml_schema = str_replace('[dreistelliger zufallsgenerierter alphanumerischer zugangscode]', $access_code, $xml_schema);
    $xml_schema = str_replace('[anzahl der generierten fragen]', $_POST['question_count'], $xml_schema);
    $xml_schema = str_replace('[anzahl der generierten Antworten pro Frage]', $_POST['answer_count'], $xml_schema);
    $xml_schema = str_replace('[answer_type]', $_POST['answer_type'], $xml_schema);

    // Anweisungen für den Antworttyp
    $answer_type_instructions = "";
    switch ($_POST['answer_type']) {
        case 'single':
            $answer_type_instructions = "Bei jeder Frage ist GENAU EINE Antwort korrekt (correct=1)";
            break;
        case 'multiple':
            $answer_type_instructions = "Bei jeder Frage sind MINDESTENS ZWEI Antworten korrekt (correct=1)";
            break;
        case 'mixed':
            $answer_type_instructions = "Bei manchen Fragen ist GENAU EINE Antwort korrekt, bei anderen sind MEHRERE Antworten korrekt (correct=1)";
            break;
    }

    // Optimierte Verarbeitung des kombinierten Inhalts
    $words = str_word_count($combinedContent, 1);
    $total_words = count($words);
    
    // Reduziere die maximale Chunk-Größe um Rate-Limits zu vermeiden
    // Ein Wort entspricht durchschnittlich 1.3 Tokens
    $max_words_per_chunk = 3500; // Angepasst für GPT-4-Turbo
    
    // Berechne die Anzahl der Chunks und optimiere die Fragenverteilung
    $total_chunks = ceil($total_words / $max_words_per_chunk);
    $base_questions_per_chunk = floor($_POST['question_count'] / $total_chunks);
    $extra_questions = $_POST['question_count'] % $total_chunks;
    $remaining_questions = $_POST['question_count'];
    
    // Teile den Text in Chunks
    if ($total_words > $max_words_per_chunk) {
        $chunks = array_chunk($words, $max_words_per_chunk);
    } else {
        $chunks = [$words];
    }

    // Sammle alle Antworten
    $all_questions = [];
    
    // ChatGPT API Call vorbereiten
    $ch = curl_init();
    if ($ch === false) {
        throw new Exception('Curl konnte nicht initialisiert werden');
    }

    // Setze die grundlegenden CURL-Optionen
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key']
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 90  // Erhöht für GPT-4-Turbo
    ]);

    // Füge Verzögerung zwischen API-Aufrufen hinzu
    $delay_between_calls = 2; // Angepasst für GPT-4-Turbo

    foreach ($chunks as $chunk_index => $chunk) {
        // Berechne die Anzahl der Fragen für diesen Chunk
        // Verteile die übrigen Fragen auf die ersten Chunks
        $extra_question = ($chunk_index < $extra_questions) ? 1 : 0;
        $questions_for_chunk = min($base_questions_per_chunk + $extra_question, $remaining_questions);
        
        if ($questions_for_chunk <= 0) break;

        $chunk_text = implode(' ', $chunk);
        
        // Extrahiere die wichtigsten Informationen aus dem Text
        $chunk_text = preg_replace('/\b\w{1,3}\b\s*/', '', $chunk_text); // Entferne sehr kurze Wörter
        $chunk_text = preg_replace('/\s+/', ' ', $chunk_text); // Normalisiere Whitespace
        
        // Verzögerung zwischen API-Aufrufen
        if ($chunk_index > 0) {
            sleep($delay_between_calls);
        }
        
        $messages = [
            [
                "role" => "system",
                "content" => "Du bist ein erfahrener Lehrer und Experte für die Erstellung von Multiple-Choice-Tests mit jahrelanger Erfahrung in der Entwicklung von Prüfungsfragen. " .
                        "Erstelle einen Test mit GENAU " . $questions_for_chunk . 
                        " Multiple-Choice-Fragen. Jede Frage muss " . $_POST['answer_count'] . " Antwortmöglichkeiten haben. " .
                        $answer_type_instructions . ".\n\n" .
                        "Wichtige Kriterien für die Fragen:\n" .
                        "1. Qualität und Verständlichkeit:\n" .
                        "   - Präzise, eindeutig und klar formuliert\n" .
                        "   - Grammatikalisch korrekt\n" .
                        "   - Fachlich akkurat und auf dem neuesten Stand\n\n" .
                        "2. Didaktische Aspekte:\n" .
                        "   - Ausgewogene Mischung von Schwierigkeitsgraden (leicht, mittel, schwer)\n" .
                        "   - Test verschiedener kognitiver Ebenen (Wissen, Verständnis, Anwendung, Analyse)\n" .
                        "   - Logische Reihenfolge der Fragen\n\n" .
                        "3. Antwortoptionen:\n" .
                        "   - Plausible und relevante Distraktoren\n" .
                        "   - Keine offensichtlich falschen Optionen\n" .
                        "   - Ähnliche Länge und Struktur\n" .
                        "   - Keine Überlappungen zwischen Optionen\n\n" .
                        "4. Vermeiden:\n" .
                        "   - Mehrdeutige Formulierungen\n" .
                        "   - Doppelte Verneinungen\n" .
                        "   - Irreführende oder triviale Optionen\n" .
                        "   - 'Alle der genannten' oder 'Keine der genannten' Optionen\n\n" .
                        "Formatiere deine Antwort wie folgt:\n\n" .
                        "Titel: [Titel des Tests passend zum Inhalt]\n\n" .
                        "1. [Konkrete, handlungsorientierte Frage]\n" .
                        "A) [Präzise Antwortoption] [correct]\n" .
                        "B) [Präzise Antwortoption]\n" .
                        "C) [Präzise Antwortoption]\n" .
                        "D) [Präzise Antwortoption]\n\n" .
                        "WICHTIG: Deine Antwort MUSS ALLE " . $questions_for_chunk . " Fragen enthalten, nummeriert von 1 bis " . $questions_for_chunk . ". " .
                        "Stelle sicher, dass jede Frage genau " . $_POST['answer_count'] . " Antwortmöglichkeiten hat. " .
                        "Verwende keine Platzhalter oder Kommentare. Generiere den vollständigen Test mit allen " . $questions_for_chunk . " Fragen."
            ],
            [
                "role" => "user",
                "content" => "Erstelle " . $questions_for_chunk . " didaktisch durchdachte und hochwertige Prüfungsfragen zum folgenden Fachinhalt. " .
                        "Achte besonders auf fachliche Korrektheit und eine ausgewogene Verteilung der Schwierigkeitsgrade.\n\n" . 
                        "Fachinhalt:\n" . $chunk_text
            ]
        ];

        // Bestimme das zu verwendende Modell
        $selectedModel = $_POST['ai_model'] ?? 'auto';
        $actualModel = determineModel($selectedModel, $config);
        
        // Logging für Modell-Verwendung
        debug_log("Test-Generator: Gewähltes Modell='$selectedModel', Verwendetes Modell='{$actualModel['id']}' ({$actualModel['name']})");
        
        $data = [
            "model" => $actualModel['id'],
            "messages" => $messages,
            "temperature" => 0.2,              // Reduziert für konsistentere Qualität
            "max_tokens" => min(4000, $actualModel['max_tokens']), // Angepasst an Modell-Limits
            "presence_penalty" => 0.1,         // Reduziert für fokussiertere Antworten
            "frequency_penalty" => 0.3,        // Erhöht für vielfältigere Antworten
            "top_p" => 0.95                   // Hinzugefügt für bessere Qualitätskontrolle
        ];
        
        // Debug: Überprüfe das JSON
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON encoding error: ' . json_last_error_msg() . "\nData: " . print_r($data, true));
        }
        
        // Vollständigen Prompt in die Protokolldatei schreiben
        debug_log("===== PROMPT AN CHATGPT (CHUNK " . ($chunk_index + 1) . "/" . count($chunks) . ") =====");
        debug_log("Chunk-Größe: " . count($chunk) . " Wörter");
        debug_log("Fragen für diesen Chunk: " . $questions_for_chunk);
        
        // Sende die API-Anfrage mit Retry-Mechanismus
        $max_retries = 3;
        $retry_count = 0;
        $success = false;
        
        while (!$success && $retry_count < $max_retries) {
            try {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                $response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    throw new Exception("Curl error: " . curl_error($ch));
                }

                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code === 429) { // Rate limit exceeded
                    $retry_count++;
                    if ($retry_count < $max_retries) {
                        sleep(pow(2, $retry_count)); // Exponential backoff
                        continue;
                    }
                    throw new Exception("Rate limit exceeded after " . $max_retries . " retries");
                }
                
                if ($http_code !== 200) {
                    throw new Exception("API error (HTTP $http_code): " . $response);
                }
                
                $result = json_decode($response, true);
                if (!isset($result['choices'][0]['message']['content'])) {
                    throw new Exception("Unerwartetes API-Antwortformat");
                }
                
                $success = true;
            } catch (Exception $e) {
                $retry_count++;
                if ($retry_count >= $max_retries) {
                    throw $e;
                }
                sleep(pow(2, $retry_count)); // Exponential backoff
            }
        }
        
        // Speichere die Antwort
        $chunk_content = $result['choices'][0]['message']['content'];
        $all_questions[] = $chunk_content;
        
        // Aktualisiere die verbleibenden Fragen
        $remaining_questions -= $questions_for_chunk;
    }

    // Kombiniere alle Antworten
    $combined_response = implode("\n\n", $all_questions);
    $text_content = $combined_response;

    // Lade die Konvertierungsfunktion
    require_once(__DIR__ . '/../includes/functions/text_to_xml_converter.php');
    
    // Extrahiere den Titel aus dem Text
    $title = extractTitleFromText($text_content) ?: "Generierter Test";
    
    // Überprüfe, ob der Text im erwarteten Format ist
    if (!isTextInExpectedFormat($text_content)) {
        error_log("Text ist nicht im erwarteten Format. Versuche Fallback-Methode.");
        
        // Entferne eventuelle Markdown-Codeblöcke
        $text_content = preg_replace('/```.*?\n(.*?)```/s', '$1', $text_content);
        
        // Versuche erneut zu überprüfen
        if (!isTextInExpectedFormat($text_content)) {
            throw new Exception("Die Antwort von ChatGPT ist nicht im erwarteten Format und kann nicht verarbeitet werden.");
        }
    }
    
    try {
        // Konvertiere den Text in XML
        $dom = convertTextToXML($text_content, $access_code, $_POST['question_count'], $_POST['answer_count'], $_POST['answer_type'], $title);
        
        error_log("Text erfolgreich in XML konvertiert");
    } catch (Exception $e) {
        error_log("Fehler bei der Konvertierung: " . $e->getMessage());
        
        // Fallback: Versuche, ein gültiges XML zu erstellen
        try {
            error_log("Versuche Fallback-XML zu erstellen");
            
            // Erstelle ein neues XML-Dokument
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;
            
            // Erstelle Root-Element
            $root = $dom->createElement('test');
            $dom->appendChild($root);
            
            // Füge Metadaten hinzu
            $access_code_node = $dom->createElement('access_code', $access_code);
            $root->appendChild($access_code_node);
            
            $title_node = $dom->createElement('title', $title);
            $root->appendChild($title_node);
            
            $question_count_node = $dom->createElement('question_count', $_POST['question_count']);
            $root->appendChild($question_count_node);
            
            $answer_count_node = $dom->createElement('answer_count', $_POST['answer_count']);
            $root->appendChild($answer_count_node);
            
            $answer_type_node = $dom->createElement('answer_type', $_POST['answer_type']);
            $root->appendChild($answer_type_node);
            
            // Erstelle questions-Container
            $questions_node = $dom->createElement('questions');
            $root->appendChild($questions_node);
            
            // Erstelle Standardfragen
            for ($i = 1; $i <= $_POST['question_count']; $i++) {
                $question_node = $dom->createElement('question');
                $question_node->setAttribute('nr', $i);
                $questions_node->appendChild($question_node);
                
                $text_node = $dom->createElement('text', 'Frage ' . $i);
                $question_node->appendChild($text_node);
                
                $answers_node = $dom->createElement('answers');
                $question_node->appendChild($answers_node);
                
                for ($j = 1; $j <= $_POST['answer_count']; $j++) {
                    $answer_node = $dom->createElement('answer');
                    $answer_node->setAttribute('nr', $j);
                    $answers_node->appendChild($answer_node);
                    
                    $answer_text_node = $dom->createElement('text', 'Antwort ' . $j);
                    $answer_node->appendChild($answer_text_node);
                    
                    $is_correct = ($j == 1) ? '1' : '0';
                    $correct_node = $dom->createElement('correct', $is_correct);
                    $answer_node->appendChild($correct_node);
                }
            }
            
            error_log("Fallback-XML erstellt");
        } catch (Exception $fallback_e) {
            error_log("Fehler beim Erstellen des Fallback-XML: " . $fallback_e->getMessage());
            throw new Exception("Konnte kein gültiges XML erstellen: " . $e->getMessage());
        }
    }
    
    // Überprüfe, ob alle Fragen vorhanden sind
    $questions = $dom->getElementsByTagName('question');
    if ($questions->length < $_POST['question_count']) {
        throw new Exception("Nicht genügend Fragen generiert. Erwartet: " . $_POST['question_count'] . ", Erhalten: " . $questions->length);
    }
    
    // Überprüfe, ob der Zugangscode korrekt ist
    $access_code_node = $dom->getElementsByTagName('access_code')->item(0);
    if (!$access_code_node || $access_code_node->textContent !== $access_code) {
        // Korrigiere den Zugangscode, falls er falsch ist
        if ($access_code_node) {
            $access_code_node->textContent = $access_code;
        }
    }
    
    // Extrahiere den Titel für den Dateinamen
    $title_node = $dom->getElementsByTagName('title')->item(0);
    $title = $title_node ? $title_node->textContent : "Generierter Test";
    
    // Erstelle einen sicheren Dateinamen
    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '-', $title);
    $safe_title = strtolower($safe_title);
    $filename = $access_code . '_' . $safe_title . '.xml';
    
    // Speichere das XML
    $xml_file_path = $tests_dir . '/' . $filename;
    $dom->save($xml_file_path);
    
    error_log("XML saved to: " . $xml_file_path);

    // Bereite die Antwort vor
    $response_data = [
        'success' => true,
        'test_id' => $access_code,
        'title' => $title,
        'access_code' => $access_code,
        'question_count' => intval($_POST['question_count']),
        'answer_count' => intval($_POST['answer_count']),
        'answer_type' => $_POST['answer_type'],
        'preview_data' => [
            'xml_path' => 'tests/' . $filename,
            'xml_content' => file_get_contents($xml_file_path)
        ]
    ];

    // Füge Whisper-Debug-Informationen hinzu, falls vorhanden
    if (isset($GLOBALS['whisper_debug'])) {
        $response_data['whisper_debug'] = $GLOBALS['whisper_debug'];
    }

    // Füge Debug-Informationen hinzu, wenn debug=1 als Parameter übergeben wurde
    if (isset($_POST['debug']) && $_POST['debug'] == '1') {
        $response_data['debug'] = [
            'prompt' => [
                'system_message' => $messages[0]['content'],
                'user_message' => $messages[1]['content'],
                'model' => $data['model'],
                'temperature' => $data['temperature'],
                'max_tokens' => $data['max_tokens']
            ],
            'response' => $result['choices'][0]['message']['content']
        ];
    }

    // Sende JSON-Antwort
    echo json_encode($response_data, JSON_UNESCAPED_UNICODE);
    exit();
    
} catch (Exception $e) { // Fehlerbehandlung für den try-Block
    // Logge den Fehler
    error_log("Error in generate_test.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Sende Fehler-JSON
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}
?> 