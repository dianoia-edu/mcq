<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Upload-Debugging für generate_test.php ===\n\n";

// Debugging-Funktion
function debug_log($message) {
    echo $message . "\n";
    error_log($message);
}

// Prüfe, ob die Datei existiert
$targetFile = __DIR__ . '/generate_test.php';
if (!file_exists($targetFile)) {
    debug_log("FEHLER: Die Datei generate_test.php wurde nicht gefunden!");
    exit(1);
}

// Zeige die problematische Zeile
$fileContent = file($targetFile);
$errorLine = 460;
$startLine = max(1, $errorLine - 10);
$endLine = min(count($fileContent), $errorLine + 10);

debug_log("Inhalt von generate_test.php um Zeile $errorLine:\n");
debug_log("--------------------------------------------------");
for ($i = $startLine - 1; $i < $endLine; $i++) {
    $lineNumber = $i + 1;
    $prefix = ($lineNumber == $errorLine) ? ">>> " : "    ";
    debug_log($prefix . $lineNumber . ": " . rtrim($fileContent[$i]));
}
debug_log("--------------------------------------------------\n");

// Prüfe die Upload-Konfiguration
debug_log("PHP-Upload-Konfiguration:");
debug_log("- upload_max_filesize: " . ini_get('upload_max_filesize'));
debug_log("- post_max_size: " . ini_get('post_max_size'));
debug_log("- max_file_uploads: " . ini_get('max_file_uploads'));
debug_log("- upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: "System-Standard"));
debug_log("- max_execution_time: " . ini_get('max_execution_time') . " Sekunden");
debug_log("- memory_limit: " . ini_get('memory_limit') . "\n");

// Prüfe das temporäre Verzeichnis
$tempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
debug_log("Temporäres Verzeichnis: $tempDir");
debug_log("- Existiert: " . (file_exists($tempDir) ? "JA" : "NEIN"));
debug_log("- Schreibbar: " . (is_writable($tempDir) ? "JA" : "NEIN") . "\n");

// Erstelle ein eigenes temporäres Verzeichnis
$customTempDir = __DIR__ . '/../temp';
if (!file_exists($customTempDir)) {
    if (mkdir($customTempDir, 0777, true)) {
        debug_log("Eigenes temporäres Verzeichnis erstellt: $customTempDir");
    } else {
        debug_log("FEHLER: Konnte eigenes temporäres Verzeichnis nicht erstellen");
    }
} else {
    debug_log("Eigenes temporäres Verzeichnis existiert bereits: $customTempDir");
}

// Setze Berechtigungen
if (chmod($customTempDir, 0777)) {
    debug_log("Berechtigungen für temporäres Verzeichnis gesetzt (0777)");
} else {
    debug_log("WARNUNG: Konnte Berechtigungen für temporäres Verzeichnis nicht setzen");
}

// Erstelle einen Patch für generate_test.php
debug_log("\nErstelle Patch für generate_test.php...");

// Lese die Datei
$content = file_get_contents($targetFile);

// Füge Debugging-Code am Anfang ein
$debugHeader = <<<'EOT'
<?php
// Temporäres Debug-Logging aktivieren
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Eigenes temporäres Verzeichnis verwenden
$customTempDir = __DIR__ . '/../temp';
if (!file_exists($customTempDir)) {
    mkdir($customTempDir, 0777, true);
}
if (is_writable($customTempDir)) {
    ini_set('upload_tmp_dir', $customTempDir);
}

// Debug-Logging-Funktion
function debug_upload($message) {
    $logFile = __DIR__ . '/../logs/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
}

// Logge den Start des Skripts
debug_upload("=== Start des Test-Generators ===");
debug_upload("Temporäres Verzeichnis: " . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()));
debug_upload("Schreibbar: " . (is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? "JA" : "NEIN"));

EOT;

// Ersetze den PHP-Öffnungs-Tag
$content = str_replace('<?php', $debugHeader, $content);

// Füge Debugging-Code vor der problematischen Zeile ein
$debugBeforeError = <<<'EOT'
    // Debug-Informationen vor der Prüfung auf leeren Inhalt
    debug_upload("Prüfe auf leeren Inhalt...");
    debug_upload("combinedContent ist " . (empty($combinedContent) ? "LEER" : "NICHT LEER (" . strlen($combinedContent) . " Bytes)"));
    
    // Prüfe Upload-Informationen
    if (isset($_FILES['source_file'])) {
        debug_upload("Upload-Informationen:");
        debug_upload("- Name: " . $_FILES['source_file']['name']);
        debug_upload("- Typ: " . $_FILES['source_file']['type']);
        debug_upload("- Größe: " . $_FILES['source_file']['size'] . " Bytes");
        debug_upload("- Temporärer Pfad: " . $_FILES['source_file']['tmp_name']);
        debug_upload("- Fehlercode: " . $_FILES['source_file']['error']);
        
        // Prüfe, ob die temporäre Datei existiert
        if ($_FILES['source_file']['error'] === UPLOAD_ERR_OK) {
            debug_upload("- Temporäre Datei existiert: " . (file_exists($_FILES['source_file']['tmp_name']) ? "JA" : "NEIN"));
            if (file_exists($_FILES['source_file']['tmp_name'])) {
                debug_upload("- Temporäre Datei lesbar: " . (is_readable($_FILES['source_file']['tmp_name']) ? "JA" : "NEIN"));
                debug_upload("- Temporäre Datei Größe: " . filesize($_FILES['source_file']['tmp_name']) . " Bytes");
            }
        }
    } else {
        debug_upload("Keine Datei hochgeladen");
    }
    
    // Prüfe andere Eingabequellen
    debug_upload("Andere Eingabequellen:");
    debug_upload("- webpage_url: " . (empty($_POST['webpage_url']) ? "LEER" : "NICHT LEER"));
    debug_upload("- youtube_url: " . (empty($_POST['youtube_url']) ? "LEER" : "NICHT LEER"));

EOT;

// Finde die Zeile, die den Fehler auslöst
$errorPattern = '/if\s*\(empty\s*\(\s*\$combinedContent\s*\)\s*\)\s*\{/';
if (preg_match($errorPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    $position = $matches[0][1];
    $content = substr($content, 0, $position) . $debugBeforeError . substr($content, $position);
}

// Speichere die modifizierte Datei als generate_test_debug.php
$debugFile = __DIR__ . '/generate_test_debug.php';
if (file_put_contents($debugFile, $content)) {
    debug_log("Debug-Version erstellt: $debugFile");
} else {
    debug_log("FEHLER: Konnte Debug-Version nicht erstellen");
}

// Erstelle ein einfaches Test-Formular
$testFormFile = __DIR__ . '/test_form.php';
$testFormContent = <<<'EOT'
<!DOCTYPE html>
<html>
<head>
    <title>Test-Generator Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        .btn { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-info { color: #31708f; background-color: #d9edf7; border-color: #bce8f1; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test-Generator Debug</h1>
        
        <div class="alert alert-info">
            <p>Dieses Formular verwendet die Debug-Version des Test-Generators.</p>
            <p>Bitte überprüfen Sie nach dem Test die Datei <code>logs/upload_debug.log</code> für detaillierte Informationen.</p>
        </div>
        
        <form action="generate_test_debug.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="source_file">PDF oder Bild hochladen:</label>
                <input type="file" name="source_file" id="source_file">
            </div>
            
            <div class="form-group">
                <label for="webpage_url">Oder Webseiten-URL eingeben:</label>
                <input type="url" name="webpage_url" id="webpage_url" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="youtube_url">Oder YouTube-URL eingeben:</label>
                <input type="url" name="youtube_url" id="youtube_url" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="access_code">Zugriffscode:</label>
                <input type="text" name="access_code" id="access_code" class="form-control" value="TEST">
            </div>
            
            <div class="form-group">
                <label for="test_title">Titel:</label>
                <input type="text" name="test_title" id="test_title" class="form-control" value="Debug Test">
            </div>
            
            <div class="form-group">
                <label for="question_count">Anzahl der Fragen:</label>
                <input type="number" name="question_count" id="question_count" class="form-control" value="5" min="1" max="20">
            </div>
            
            <div class="form-group">
                <label for="answer_count">Anzahl der Antworten pro Frage:</label>
                <input type="number" name="answer_count" id="answer_count" class="form-control" value="4" min="2" max="6">
            </div>
            
            <div class="form-group">
                <label for="answer_type">Antworttyp:</label>
                <select name="answer_type" id="answer_type" class="form-control">
                    <option value="single">Einzelauswahl</option>
                    <option value="multiple">Mehrfachauswahl</option>
                    <option value="mixed">Gemischt</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Test generieren</button>
        </form>
    </div>
</body>
</html>
EOT;

if (file_put_contents($testFormFile, $testFormContent)) {
    debug_log("Test-Formular erstellt: $testFormFile");
} else {
    debug_log("FEHLER: Konnte Test-Formular nicht erstellen");
}

// Erstelle das Logs-Verzeichnis, falls es nicht existiert
$logsDir = __DIR__ . '/../logs';
if (!file_exists($logsDir)) {
    if (mkdir($logsDir, 0755, true)) {
        debug_log("Logs-Verzeichnis erstellt: $logsDir");
    } else {
        debug_log("FEHLER: Konnte Logs-Verzeichnis nicht erstellen");
    }
}

echo "\n=== Debugging-Setup abgeschlossen ===\n";
echo "Bitte führen Sie folgende Schritte aus:\n";
echo "1. Öffnen Sie im Browser: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/test_form.php\n";
echo "2. Laden Sie eine Datei hoch oder geben Sie eine URL ein\n";
echo "3. Prüfen Sie die Datei logs/upload_debug.log für detaillierte Informationen\n"; 