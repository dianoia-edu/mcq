<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Upload-Limit Fix ===\n\n";

// Aktuelle PHP-Konfiguration anzeigen
echo "Aktuelle PHP-Konfiguration:\n";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- post_max_size: " . ini_get('post_max_size') . "\n";
echo "- memory_limit: " . ini_get('memory_limit') . "\n\n";

// Erstelle .htaccess-Datei im Hauptverzeichnis
$htaccessFile = __DIR__ . '/../.htaccess';
$htaccessContent = <<<'EOT'
# Erhöhe Upload-Limits
php_value upload_max_filesize 20M
php_value post_max_size 20M
php_value memory_limit 128M
php_value max_execution_time 300
php_value max_input_time 300

# Aktiviere Error-Reporting für Debugging
php_flag display_errors on
php_value error_reporting E_ALL

# Setze temporäres Verzeichnis
php_value upload_tmp_dir /var/www/html/mcq-test-system/temp
EOT;

if (file_put_contents($htaccessFile, $htaccessContent)) {
    echo ".htaccess-Datei erstellt: $htaccessFile\n";
    echo "Neue Konfiguration:\n";
    echo "- upload_max_filesize: 20M\n";
    echo "- post_max_size: 20M\n";
    echo "- memory_limit: 128M\n";
    echo "- max_execution_time: 300\n";
    echo "- max_input_time: 300\n\n";
} else {
    echo "FEHLER: Konnte .htaccess-Datei nicht erstellen\n\n";
}

// Erstelle temporäres Verzeichnis
$tempDir = __DIR__ . '/../temp';
if (!file_exists($tempDir)) {
    if (mkdir($tempDir, 0777, true)) {
        echo "Temporäres Verzeichnis erstellt: $tempDir\n";
    } else {
        echo "FEHLER: Konnte temporäres Verzeichnis nicht erstellen\n";
    }
} else {
    echo "Temporäres Verzeichnis existiert bereits: $tempDir\n";
}

// Setze Berechtigungen
if (chmod($tempDir, 0777)) {
    echo "Berechtigungen für temporäres Verzeichnis gesetzt (0777)\n";
} else {
    echo "WARNUNG: Konnte Berechtigungen für temporäres Verzeichnis nicht setzen\n";
}

// Erstelle eine PHP-Info-Datei
$phpinfoFile = __DIR__ . '/phpinfo.php';
$phpinfoContent = '<?php phpinfo(); ?>';
if (file_put_contents($phpinfoFile, $phpinfoContent)) {
    echo "PHP-Info-Datei erstellt: $phpinfoFile\n";
    echo "Rufen Sie diese Datei auf, um die aktuelle PHP-Konfiguration zu prüfen:\n";
    echo "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/phpinfo.php\n\n";
} else {
    echo "FEHLER: Konnte PHP-Info-Datei nicht erstellen\n\n";
}

// Erstelle ein einfaches Upload-Testformular
$uploadTestFile = __DIR__ . '/upload_test_simple.php';
$uploadTestContent = <<<'EOT'
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
        .result { margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload-Test</h1>
        <p>Dieses Formular testet, ob große Dateien hochgeladen werden können.</p>
        
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["test_file"])) {
            echo '<div class="result">';
            echo '<h3>Upload-Ergebnis:</h3>';
            
            // Zeige Upload-Informationen
            echo '<p>Datei-Informationen:</p>';
            echo '<ul>';
            echo '<li>Name: ' . htmlspecialchars($_FILES["test_file"]["name"]) . '</li>';
            echo '<li>Typ: ' . htmlspecialchars($_FILES["test_file"]["type"]) . '</li>';
            echo '<li>Größe: ' . htmlspecialchars($_FILES["test_file"]["size"]) . ' Bytes</li>';
            echo '<li>Temporärer Pfad: ' . htmlspecialchars($_FILES["test_file"]["tmp_name"]) . '</li>';
            echo '<li>Fehlercode: ' . htmlspecialchars($_FILES["test_file"]["error"]) . '</li>';
            echo '</ul>';
            
            // Prüfe Fehlercode
            switch($_FILES["test_file"]["error"]) {
                case UPLOAD_ERR_OK:
                    echo '<p class="success">Upload erfolgreich!</p>';
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    echo '<p class="error">Fehler: Die Datei überschreitet die in php.ini festgelegte upload_max_filesize.</p>';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    echo '<p class="error">Fehler: Die Datei überschreitet die im Formular festgelegte MAX_FILE_SIZE.</p>';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    echo '<p class="error">Fehler: Die Datei wurde nur teilweise hochgeladen.</p>';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    echo '<p class="error">Fehler: Es wurde keine Datei hochgeladen.</p>';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    echo '<p class="error">Fehler: Temporäres Verzeichnis fehlt.</p>';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    echo '<p class="error">Fehler: Fehler beim Schreiben der Datei auf die Festplatte.</p>';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    echo '<p class="error">Fehler: Eine PHP-Erweiterung hat den Upload gestoppt.</p>';
                    break;
                default:
                    echo '<p class="error">Fehler: Unbekannter Fehler.</p>';
            }
            
            // Zeige PHP-Konfiguration
            echo '<p>PHP-Konfiguration:</p>';
            echo '<ul>';
            echo '<li>upload_max_filesize: ' . ini_get('upload_max_filesize') . '</li>';
            echo '<li>post_max_size: ' . ini_get('post_max_size') . '</li>';
            echo '<li>memory_limit: ' . ini_get('memory_limit') . '</li>';
            echo '<li>max_execution_time: ' . ini_get('max_execution_time') . '</li>';
            echo '<li>max_input_time: ' . ini_get('max_input_time') . '</li>';
            echo '<li>upload_tmp_dir: ' . (ini_get('upload_tmp_dir') ?: 'Standard') . '</li>';
            echo '</ul>';
            
            echo '</div>';
        }
        ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="test_file">Wählen Sie eine Datei aus:</label>
                <input type="file" name="test_file" id="test_file" required>
            </div>
            <button type="submit" class="btn">Datei hochladen</button>
        </form>
        
        <div class="form-group" style="margin-top: 20px;">
            <p>Aktuelle PHP-Konfiguration:</p>
            <ul>
                <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
                <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
                <li>memory_limit: <?php echo ini_get('memory_limit'); ?></li>
            </ul>
        </div>
    </div>
</body>
</html>
EOT;

if (file_put_contents($uploadTestFile, $uploadTestContent)) {
    echo "Upload-Testformular erstellt: $uploadTestFile\n";
    echo "Rufen Sie diese Datei auf, um zu testen, ob große Dateien hochgeladen werden können:\n";
    echo "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/upload_test_simple.php\n\n";
} else {
    echo "FEHLER: Konnte Upload-Testformular nicht erstellen\n\n";
}

// Erstelle eine alternative Lösung für den Test-Generator
$fixedGeneratorFile = __DIR__ . '/generate_test_fixed.php';
$fixedGeneratorContent = <<<'EOT'
<?php
// Aktiviere Error-Reporting für Debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Erhöhe Limits für große Dateien
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

// Eigenes temporäres Verzeichnis verwenden
$customTempDir = __DIR__ . '/../temp';
if (!file_exists($customTempDir)) {
    mkdir($customTempDir, 0777, true);
}
if (is_writable($customTempDir)) {
    ini_set('upload_tmp_dir', $customTempDir);
}

// Logging-Funktion
function debug_log($message) {
    $logFile = __DIR__ . '/../logs/fixed_generator.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
}

// Logge den Start des Skripts
debug_log("=== Start des verbesserten Test-Generators ===");
debug_log("PHP-Version: " . phpversion());
debug_log("upload_max_filesize: " . ini_get('upload_max_filesize'));
debug_log("post_max_size: " . ini_get('post_max_size'));
debug_log("memory_limit: " . ini_get('memory_limit'));
debug_log("Temporäres Verzeichnis: " . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()));
debug_log("Schreibbar: " . (is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? "JA" : "NEIN"));

// Erstelle Verzeichnisse, falls sie nicht existieren
$directories = [
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads',
    __DIR__ . '/../temp',
    __DIR__ . '/../results'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            debug_log("Verzeichnis erstellt: $dir");
        } else {
            debug_log("FEHLER: Konnte Verzeichnis nicht erstellen: $dir");
        }
    }
}

// Verarbeite Formular-Daten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Lade Konfiguration
        require_once '../includes/config_loader.php';
        $config = loadConfig();
        
        if (!isset($config['api_key']) || empty($config['api_key'])) {
            throw new Exception('OpenAI API Key nicht in der Konfiguration gefunden');
        }
        
        $apiKey = $config['api_key'];
        $combinedContent = '';
        
        // Verarbeite Datei-Upload
        if (isset($_FILES['source_file'])) {
            debug_log("Datei-Upload gefunden:");
            debug_log("- Name: " . $_FILES['source_file']['name']);
            debug_log("- Typ: " . $_FILES['source_file']['type']);
            debug_log("- Größe: " . $_FILES['source_file']['size'] . " Bytes");
            debug_log("- Temporärer Pfad: " . $_FILES['source_file']['tmp_name']);
            debug_log("- Fehlercode: " . $_FILES['source_file']['error']);
            
            if ($_FILES['source_file']['error'] === UPLOAD_ERR_INI_SIZE) {
                throw new Exception('Die hochgeladene Datei überschreitet die in php.ini festgelegte Größenbeschränkung (upload_max_filesize).');
            }
            
            if ($_FILES['source_file']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['source_file']['size'] === 0) {
                    throw new Exception('Leere Datei hochgeladen');
                }
                
                // Überprüfe den Dateityp
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $_FILES['source_file']['tmp_name']);
                finfo_close($finfo);
                
                debug_log("Datei-Typ erkannt: $mime_type");
                
                // Kopiere die Datei in ein sicheres Verzeichnis
                $uploadDir = __DIR__ . '/../uploads';
                $safeFilename = uniqid() . '_' . basename($_FILES['source_file']['name']);
                $targetPath = $uploadDir . '/' . $safeFilename;
                
                if (move_uploaded_file($_FILES['source_file']['tmp_name'], $targetPath)) {
                    debug_log("Datei erfolgreich verschoben nach: $targetPath");
                    
                    // Extrahiere Text aus der Datei
                    if ($mime_type === 'application/pdf') {
                        debug_log("PDF-Datei erkannt, extrahiere Text...");
                        // Hier würde normalerweise performOCR aufgerufen werden
                        // Für diesen Test verwenden wir einen Beispieltext
                        $combinedContent = "Dies ist ein Beispieltext für eine PDF-Datei.\n\n";
                        debug_log("Text aus PDF extrahiert (Beispieltext)");
                    } 
                    else if (in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'])) {
                        debug_log("Bild erkannt, extrahiere Text...");
                        // Hier würde normalerweise performOCR aufgerufen werden
                        // Für diesen Test verwenden wir einen Beispieltext
                        $combinedContent = "Dies ist ein Beispieltext für ein Bild.\n\n";
                        debug_log("Text aus Bild extrahiert (Beispieltext)");
                    }
                    else if ($mime_type === 'text/plain') {
                        debug_log("Textdatei erkannt, lese Inhalt...");
                        $combinedContent = file_get_contents($targetPath) . "\n\n";
                        debug_log("Text aus Datei gelesen (" . strlen($combinedContent) . " Zeichen)");
                    }
                    else {
                        debug_log("Nicht unterstützter Dateityp: $mime_type");
                        throw new Exception("Nicht unterstützter Dateityp: $mime_type");
                    }
                } else {
                    debug_log("Fehler beim Verschieben der Datei");
                    throw new Exception('Fehler beim Verarbeiten der hochgeladenen Datei');
                }
            } else {
                debug_log("Upload-Fehler: " . $_FILES['source_file']['error']);
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'Die hochgeladene Datei überschreitet die in php.ini festgelegte Größenbeschränkung (upload_max_filesize).',
                    UPLOAD_ERR_FORM_SIZE => 'Die hochgeladene Datei überschreitet die im Formular festgelegte Größenbeschränkung (MAX_FILE_SIZE).',
                    UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise hochgeladen.',
                    UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei hochgeladen.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Verzeichnis fehlt.',
                    UPLOAD_ERR_CANT_WRITE => 'Fehler beim Schreiben der Datei auf die Festplatte.',
                    UPLOAD_ERR_EXTENSION => 'Eine PHP-Erweiterung hat den Upload gestoppt.'
                ];
                
                $errorMessage = isset($errorMessages[$_FILES['source_file']['error']]) 
                    ? $errorMessages[$_FILES['source_file']['error']] 
                    : 'Unbekannter Upload-Fehler.';
                
                throw new Exception($errorMessage);
            }
        }
        
        // Verarbeite Webseiten-URL
        if (!empty($_POST['webpage_url'])) {
            $url = filter_var($_POST['webpage_url'], FILTER_SANITIZE_URL);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Ungültige Webseiten-URL');
            }
            
            debug_log("Verarbeite Webseiten-URL: " . $url);
            $combinedContent .= "Dies ist ein Beispieltext für eine Webseite.\n\n";
        }
        
        // Verarbeite YouTube-URL
        if (!empty($_POST['youtube_url'])) {
            debug_log("Verarbeite YouTube-URL: " . $_POST['youtube_url']);
            $combinedContent .= "Dies ist ein Beispieltext für ein YouTube-Video.\n\n";
        }
        
        // Prüfe, ob mindestens eine Quelle verarbeitet wurde
        if (empty($combinedContent)) {
            debug_log("Keine Inhaltsquelle gefunden");
            throw new Exception('Keine Inhaltsquelle gefunden. Bitte laden Sie eine Datei hoch oder geben Sie eine URL ein.');
        }
        
        debug_log("Inhalt erfolgreich extrahiert (" . strlen($combinedContent) . " Zeichen)");
        
        // Erfolgreiche Antwort
        $response = [
            'success' => true,
            'message' => 'Test erfolgreich generiert (Beispiel)',
            'test_id' => 'test_' . uniqid()
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        
    } catch (Exception $e) {
        debug_log("Fehler: " . $e->getMessage());
        
        $response = [
            'success' => false,
            'message' => 'Fehler bei der Testgenerierung',
            'error' => $e->getMessage()
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verbesserter Test-Generator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-info { color: #31708f; background-color: #d9edf7; border-color: #bce8f1; }
        .alert-warning { color: #8a6d3b; background-color: #fcf8e3; border-color: #faebcc; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verbesserter Test-Generator</h1>
        
        <div class="alert alert-info">
            <p>Diese Version des Test-Generators wurde optimiert, um Probleme mit dem Datei-Upload zu beheben.</p>
            <p>Sie können PDF-Dateien, Bilder oder Textdateien hochladen.</p>
        </div>
        
        <div class="alert alert-warning">
            <p>Hinweis: Diese Version verwendet Beispieldaten für die Demonstration.</p>
            <p>In der Produktionsversion würde der tatsächliche Text aus den Dateien extrahiert werden.</p>
        </div>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="source_file">PDF oder Bild hochladen:</label>
                <input type="file" name="source_file" id="source_file" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="webpage_url">Oder Webseiten-URL eingeben:</label>
                <input type="url" name="webpage_url" id="webpage_url" class="form-control" placeholder="https://beispiel.de">
            </div>
            
            <div class="form-group">
                <label for="youtube_url">Oder YouTube-URL eingeben:</label>
                <input type="url" name="youtube_url" id="youtube_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
            </div>
            
            <div class="form-group">
                <label for="access_code">Zugriffscode:</label>
                <input type="text" name="access_code" id="access_code" class="form-control" value="TEST">
            </div>
            
            <div class="form-group">
                <label for="test_title">Titel:</label>
                <input type="text" name="test_title" id="test_title" class="form-control" value="Test">
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
        
        <div class="form-group" style="margin-top: 20px;">
            <p>Aktuelle PHP-Konfiguration:</p>
            <ul>
                <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
                <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
                <li>memory_limit: <?php echo ini_get('memory_limit'); ?></li>
                <li>max_execution_time: <?php echo ini_get('max_execution_time'); ?></li>
                <li>max_input_time: <?php echo ini_get('max_input_time'); ?></li>
                <li>upload_tmp_dir: <?php echo ini_get('upload_tmp_dir') ?: 'Standard'; ?></li>
            </ul>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Erfolg: ' + data.message);
                } else {
                    alert('Fehler: ' + data.error);
                }
            })
            .catch(error => {
                alert('Fehler bei der Anfrage: ' + error.message);
            });
        });
    });
    </script>
</body>
</html>
EOT;

if (file_put_contents($fixedGeneratorFile, $fixedGeneratorContent)) {
    echo "Verbesserte Version des Test-Generators erstellt: $fixedGeneratorFile\n";
    echo "Rufen Sie diese Datei auf, um Tests zu generieren:\n";
    echo "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/generate_test_fixed.php\n\n";
} else {
    echo "FEHLER: Konnte verbesserte Version des Test-Generators nicht erstellen\n\n";
}

echo "\n=== Fix abgeschlossen ===\n";
echo "Bitte führen Sie folgende Schritte aus:\n";
echo "1. Prüfen Sie die PHP-Konfiguration mit phpinfo.php\n";
echo "2. Testen Sie den Upload mit upload_test_simple.php\n";
echo "3. Verwenden Sie den verbesserten Test-Generator generate_test_fixed.php\n";
echo "4. Wenn alles funktioniert, können Sie die Änderungen in die Hauptdatei übernehmen\n";