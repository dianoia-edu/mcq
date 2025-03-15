<?php
// Aktiviere Error Reporting für Entwicklung
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

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
        throw new Exception("Tesseract ist nicht verfügbar. Bitte installieren Sie Tesseract-OCR.");
    }
    
    exec('gswin64c --version', $output, $returnVar);
    if ($returnVar !== 0) {
        throw new Exception("Ghostscript ist nicht verfügbar. Bitte installieren Sie Ghostscript.");
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

// Funktion zum Abrufen der Untertitel eines YouTube-Videos
function getYoutubeTranscript($videoId) {
    // Setze die PATH-Variable für die aktuelle Ausführung
    $path = getenv('PATH');
    $winget_path = 'C:\Users\kaaag\AppData\Local\Microsoft\WinGet\Packages\yt-dlp.yt-dlp_Microsoft.Winget.Source_8wekyb3d8bbwe';
    putenv("PATH=$path;$winget_path");
    
    // Erstelle ein temporäres Verzeichnis für die Ausgabedateien
    $temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'youtube_' . uniqid();
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0777, true);
    }
    
    // Wechsle in das temporäre Verzeichnis
    $current_dir = getcwd();
    chdir($temp_dir);
    
    // Verwende youtube-dl um die Untertitel zu extrahieren, ohne Konvertierung zu SRT
    $command = sprintf('yt-dlp --write-auto-sub --skip-download --sub-lang de,en --output "%%(id)s.%%(ext)s" "https://www.youtube.com/watch?v=%s" 2>&1', 
        escapeshellarg($videoId)
    );
    
    error_log("Executing command: " . $command); // Debug-Ausgabe
    exec($command, $output, $returnVar);
    
    // Debug-Ausgabe
    error_log("Command output: " . implode("\n", $output));
    error_log("Return value: " . $returnVar);
    error_log("Current directory: " . getcwd());
    error_log("Directory contents: " . implode("\n", glob("*")));
    
    // Stelle den ursprünglichen PATH wieder her
    putenv("PATH=$path");
    
    if ($returnVar !== 0) {
        // Wechsle zurück zum ursprünglichen Verzeichnis
        chdir($current_dir);
        // Aufräumen
        array_map('unlink', glob($temp_dir . DIRECTORY_SEPARATOR . "*"));
        rmdir($temp_dir);
        throw new Exception('Fehler beim Abrufen der Untertitel: ' . implode("\n", $output));
    }
    
    // Suche nach der generierten VTT-Datei (zuerst deutsch, dann englisch)
    $vttFiles = glob("*.{de,en}.vtt", GLOB_BRACE);
    if (empty($vttFiles)) {
        // Wechsle zurück zum ursprünglichen Verzeichnis
        chdir($current_dir);
        // Aufräumen
        array_map('unlink', glob($temp_dir . DIRECTORY_SEPARATOR . "*"));
        rmdir($temp_dir);
        throw new Exception('Keine Untertitel gefunden. Verfügbare Dateien: ' . implode(", ", glob("*")));
    }
    
    // Lese den Inhalt der VTT-Datei
    $vttContent = file_get_contents($vttFiles[0]);
    
    // Bereinige die Untertitel
    // Entferne den VTT-Header
    $vttContent = preg_replace('/WEBVTT.*?\n\n/s', '', $vttContent);
    // Entferne Zeitstempel und Formatierung
    $vttContent = preg_replace('/\d{2}:\d{2}:\d{2}\.\d{3} --> \d{2}:\d{2}:\d{2}\.\d{3}.*?\n/m', '', $vttContent);
    // Entferne leere Zeilen und Zeilennummern
    $vttContent = preg_replace('/^\d+\n/m', '', $vttContent);
    $vttContent = preg_replace('/\n{2,}/m', "\n", $vttContent);
    // Entferne HTML-Tags und Formatierungen
    $vttContent = strip_tags($vttContent);
    $vttContent = trim($vttContent);
    
    // Wechsle zurück zum ursprünglichen Verzeichnis
    chdir($current_dir);
    
    // Lösche die temporären Dateien
    array_map('unlink', glob($temp_dir . DIRECTORY_SEPARATOR . "*"));
    rmdir($temp_dir);
    
    return $vttContent;
}

try {
    // Debug-Logging
    error_log("Request started");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Überprüfe, ob die Konfigurationsdatei existiert
    if (!file_exists('../includes/config/openai_config.php')) {
        throw new Exception('Konfigurationsdatei nicht gefunden');
    }

    // Lade Konfiguration
    $config = require_once('../includes/config/openai_config.php');
    
    if (!isset($config['api_key']) || empty($config['api_key'])) {
        throw new Exception('OpenAI API Key nicht in der Konfiguration gefunden');
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
            $combinedContent .= performOCR($_FILES['source_file']['tmp_name'], $mime_type) . "\n\n";
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
        $videoId = extractYoutubeId($_POST['youtube_url']);
        $combinedContent .= getYoutubeTranscript($videoId) . "\n\n";
    }

    // Prüfe, ob mindestens eine Quelle verarbeitet wurde
    if (empty($combinedContent)) {
        throw new Exception('Keine Inhaltsquelle gefunden. Bitte laden Sie eine Datei hoch oder geben Sie eine URL ein.');
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

    // Erhöhe die maximale Chunk-Größe deutlich, um unnötiges Aufteilen zu vermeiden
    $max_words_per_chunk = 6000; // GPT-4 kann bis zu ~8000 Tokens verarbeiten

    // Teile nur auf, wenn wirklich notwendig
    $words = str_word_count($file_content, 1);
    $total_words = count($words);

    // Erstelle nur dann Chunks, wenn der Text die maximale Größe überschreitet
    if ($total_words > $max_words_per_chunk) {
        $chunks = array_chunk($words, $max_words_per_chunk);
    } else {
        $chunks = [$words]; // Behalte den gesamten Text als einen einzigen Chunk
    }

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
        CURLOPT_TIMEOUT => 120
    ]);

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

    $all_responses = [];
    
    // Erstelle den Prompt für die API-Anfrage
    $chunk_text = implode(' ', $chunks[0]);
        
        $messages = [
            [
                "role" => "system",
                "content" => "Du bist ein Lehrer, der Multiple-Choice-Tests erstellt. Erstelle einen Test mit GENAU " . $_POST['question_count'] . 
                        " Fragen zu einem gegebenen Text. Jede Frage soll genau " . $_POST['answer_count'] . " Antwortmöglichkeiten haben. " .
                        $answer_type_instructions . ".\n\n" .
                        "Formatiere deine Antwort als nummerierte Liste von Fragen mit Antwortoptionen, die mit Buchstaben gekennzeichnet sind. " .
                        "Markiere die richtigen Antworten mit [correct] am Ende. Beispiel:\n\n" .
                        "Titel: [Titel des Tests passend zum Inhalt]\n\n" .
                        "1. Frage 1\n" .
                        "A) Antwort 1 [correct]\n" .
                        "B) Antwort 2\n" .
                        "C) Antwort 3\n" .
                        "D) Antwort 4\n\n" .
                        "2. Frage 2\n" .
                        "A) Antwort 1\n" .
                        "B) Antwort 2 [correct]\n" .
                        "C) Antwort 3\n" .
                        "D) Antwort 4\n\n" .
                        "WICHTIG: Deine Antwort MUSS ALLE " . $_POST['question_count'] . " Fragen enthalten, nummeriert von 1 bis " . $_POST['question_count'] . ". " .
                        "Stelle sicher, dass jede Frage genau " . $_POST['answer_count'] . " Antwortmöglichkeiten hat. " .
                        "Verwende keine Platzhalter oder Kommentare. Generiere den vollständigen Test mit allen " . $_POST['question_count'] . " Fragen."
            ],
            [
                "role" => "user",
                "content" => "Erstelle einen vollständigen Multiple-Choice-Test mit GENAU " . $_POST['question_count'] . " Fragen zu folgendem Text. " .
                        "Jede Frage soll genau " . $_POST['answer_count'] . " Antwortmöglichkeiten haben. " .
                        $answer_type_instructions . ".\n\n" .
                        "Stelle sicher, dass du ALLE " . $_POST['question_count'] . " Fragen generierst und keine Platzhalter verwendest. " .
                        "Der Test muss vollständig sein und darf keine Kommentare oder Auslassungen enthalten.\n\n" . $chunk_text
            ]
        ];

        $data = [
            "model" => "gpt-4o",
            "messages" => $messages,
            "temperature" => 0.1,
            "max_tokens" => 8000,
            "presence_penalty" => 0.2,
            "frequency_penalty" => 0.2
        ];
        
        // Debug: Überprüfe das JSON
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON encoding error: ' . json_last_error_msg() . "\nData: " . print_r($data, true));
        }
        
        // Vollständigen Prompt in die Protokolldatei schreiben
        debug_log("===== VOLLSTÄNDIGER PROMPT AN CHATGPT =====");
        debug_log("System-Nachricht: " . $messages[0]['content']);
        debug_log("Benutzer-Nachricht: " . $messages[1]['content']);
        debug_log("Modell: " . $data['model']);
        debug_log("Temperatur: " . $data['temperature']);
        debug_log("Max Tokens: " . $data['max_tokens']);
        
        debug_log("Request payload: " . $jsonData);

        // Sende die API-Anfrage
        curl_setopt_array($ch, [
            CURLOPT_POSTFIELDS => $jsonData
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception("Curl error: " . curl_error($ch));
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200) {
            throw new Exception("API error (HTTP $http_code): " . $response);
        }
        
        $result = json_decode($response, true);
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception("Unerwartetes API-Antwortformat: " . print_r($result, true));
        }
        
        // Vollständige Antwort in die Protokolldatei schreiben
        debug_log("===== VOLLSTÄNDIGE ANTWORT VON CHATGPT =====");
        debug_log($result['choices'][0]['message']['content']);
        
        $text_content = $result['choices'][0]['message']['content'];
    
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