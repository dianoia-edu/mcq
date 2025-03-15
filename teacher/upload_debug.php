<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Upload-Debugging ===\n\n";

// PHP-Upload-Konfiguration prüfen
echo "PHP-Upload-Konfiguration:\n";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- post_max_size: " . ini_get('post_max_size') . "\n";
echo "- max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "- upload_tmp_dir: " . ini_get('upload_tmp_dir') . " (leer = System-Standard)\n";
echo "- max_execution_time: " . ini_get('max_execution_time') . " Sekunden\n";
echo "- memory_limit: " . ini_get('memory_limit') . "\n\n";

// Temporäres Verzeichnis prüfen
$tempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "Temporäres Verzeichnis: $tempDir\n";
echo "- Existiert: " . (file_exists($tempDir) ? "JA" : "NEIN") . "\n";
echo "- Schreibbar: " . (is_writable($tempDir) ? "JA" : "NEIN") . "\n\n";

// Eigenes temporäres Verzeichnis erstellen und testen
$customTempDir = __DIR__ . '/../temp';
if (!file_exists($customTempDir)) {
    if (mkdir($customTempDir, 0777, true)) {
        echo "Eigenes temporäres Verzeichnis erstellt: $customTempDir\n";
    } else {
        echo "FEHLER: Konnte eigenes temporäres Verzeichnis nicht erstellen\n";
    }
} else {
    echo "Eigenes temporäres Verzeichnis existiert bereits: $customTempDir\n";
}

// Setze Berechtigungen
if (chmod($customTempDir, 0777)) {
    echo "Berechtigungen für temporäres Verzeichnis gesetzt (0777)\n";
} else {
    echo "WARNUNG: Konnte Berechtigungen für temporäres Verzeichnis nicht setzen\n";
}

// Teste Schreibzugriff
$testFile = $customTempDir . '/test_' . uniqid() . '.txt';
$testContent = "Test " . date('Y-m-d H:i:s');
$writeSuccess = @file_put_contents($testFile, $testContent);

if ($writeSuccess !== false) {
    echo "Schreibtest erfolgreich\n";
    $readContent = @file_get_contents($testFile);
    echo "Lesetest: " . ($readContent === $testContent ? "ERFOLGREICH" : "FEHLGESCHLAGEN") . "\n";
    @unlink($testFile);
} else {
    echo "FEHLER: Schreibtest fehlgeschlagen\n";
}

// Externe Programme prüfen
echo "\nExterne Programme:\n";

// Tesseract OCR
exec('tesseract --version 2>&1', $tesseractOutput, $tesseractReturnVar);
echo "- Tesseract OCR: " . ($tesseractReturnVar === 0 ? "OK (" . trim($tesseractOutput[0]) . ")" : "NICHT GEFUNDEN") . "\n";

// Ghostscript
exec('gs --version 2>&1', $gsOutput, $gsReturnVar);
if ($gsReturnVar !== 0) {
    // Versuche alternative Befehle für Windows
    exec('gswin64c --version 2>&1', $gsOutput, $gsReturnVar);
    if ($gsReturnVar !== 0) {
        exec('gswin32c --version 2>&1', $gsOutput, $gsReturnVar);
    }
}
echo "- Ghostscript: " . ($gsReturnVar === 0 ? "OK (v" . trim($gsOutput[0]) . ")" : "NICHT GEFUNDEN") . "\n\n";

// Upload-Formular für Tests
echo "Upload-Test:\n";
echo "Bitte verwenden Sie das folgende Formular, um einen Upload-Test durchzuführen:\n";
echo "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/upload_test.php\n\n";

// Erstelle ein einfaches Upload-Testformular
$uploadTestFile = __DIR__ . '/upload_test.php';
$uploadTestContent = '<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["test_file"])) {
    header("Content-Type: text/plain");
    
    echo "=== Upload-Test-Ergebnisse ===\n\n";
    
    // Verwende eigenes temporäres Verzeichnis
    $customTempDir = __DIR__ . "/../temp";
    if (file_exists($customTempDir) && is_writable($customTempDir)) {
        ini_set("upload_tmp_dir", $customTempDir);
        echo "Eigenes temporäres Verzeichnis wird verwendet: $customTempDir\n";
    }
    
    // Zeige Upload-Informationen
    echo "Datei-Informationen:\n";
    echo "- Name: " . $_FILES["test_file"]["name"] . "\n";
    echo "- Typ: " . $_FILES["test_file"]["type"] . "\n";
    echo "- Größe: " . $_FILES["test_file"]["size"] . " Bytes\n";
    echo "- Temporärer Pfad: " . $_FILES["test_file"]["tmp_name"] . "\n";
    echo "- Fehlercode: " . $_FILES["test_file"]["error"] . "\n";
    
    // Prüfe Fehlercode
    switch($_FILES["test_file"]["error"]) {
        case UPLOAD_ERR_OK:
            echo "- Status: OK - Upload erfolgreich\n";
            break;
        case UPLOAD_ERR_INI_SIZE:
            echo "- Status: FEHLER - Die Datei überschreitet die in php.ini festgelegte upload_max_filesize\n";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            echo "- Status: FEHLER - Die Datei überschreitet die im Formular festgelegte MAX_FILE_SIZE\n";
            break;
        case UPLOAD_ERR_PARTIAL:
            echo "- Status: FEHLER - Die Datei wurde nur teilweise hochgeladen\n";
            break;
        case UPLOAD_ERR_NO_FILE:
            echo "- Status: FEHLER - Es wurde keine Datei hochgeladen\n";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            echo "- Status: FEHLER - Temporäres Verzeichnis fehlt\n";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            echo "- Status: FEHLER - Fehler beim Schreiben der Datei auf die Festplatte\n";
            break;
        case UPLOAD_ERR_EXTENSION:
            echo "- Status: FEHLER - Eine PHP-Erweiterung hat den Upload gestoppt\n";
            break;
        default:
            echo "- Status: FEHLER - Unbekannter Fehler\n";
    }
    
    // Prüfe, ob die temporäre Datei existiert
    if (file_exists($_FILES["test_file"]["tmp_name"])) {
        echo "- Temporäre Datei existiert: JA\n";
        
        // Versuche, die Datei zu lesen
        $fileContent = @file_get_contents($_FILES["test_file"]["tmp_name"]);
        if ($fileContent !== false) {
            echo "- Datei lesbar: JA (" . strlen($fileContent) . " Bytes)\n";
            
            // Versuche, die Datei in ein Zielverzeichnis zu verschieben
            $uploadDir = __DIR__ . "/../uploads";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $targetFile = $uploadDir . "/" . basename($_FILES["test_file"]["name"]);
            if (move_uploaded_file($_FILES["test_file"]["tmp_name"], $targetFile)) {
                echo "- Datei verschoben: JA (nach $targetFile)\n";
            } else {
                echo "- Datei verschoben: NEIN (Fehler beim Verschieben)\n";
            }
        } else {
            echo "- Datei lesbar: NEIN (Fehler beim Lesen)\n";
        }
    } else {
        echo "- Temporäre Datei existiert: NEIN\n";
    }
    
    echo "\n=== Test abgeschlossen ===\n";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload-Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        .btn { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload-Test</h1>
        <p>Dieses Formular testet den Datei-Upload-Prozess.</p>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="test_file">Wählen Sie eine Datei aus:</label>
                <input type="file" name="test_file" id="test_file" required>
            </div>
            <button type="submit" class="btn">Datei hochladen</button>
        </form>
    </div>
</body>
</html>';

if (file_put_contents($uploadTestFile, $uploadTestContent)) {
    echo "Upload-Testformular erstellt: $uploadTestFile\n";
} else {
    echo "FEHLER: Konnte Upload-Testformular nicht erstellen\n";
}

echo "\n=== Debugging abgeschlossen ===\n";
echo "Bitte führen Sie den Upload-Test durch und prüfen Sie die Ergebnisse.\n";
echo "Wenn der Upload-Test erfolgreich ist, aber die Testgenerierung weiterhin fehlschlägt,\n";
echo "könnte das Problem mit der Verarbeitung der hochgeladenen Datei zusammenhängen.\n"; 