<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Server-Setup ===\n\n";

// Verzeichnisse erstellen
$directories = [
    'logs',
    'results',
    'uploads'
];

echo "Erstelle Verzeichnisse:\n";
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "- $dir: ERSTELLT\n";
        } else {
            echo "- $dir: FEHLER BEIM ERSTELLEN\n";
        }
    } else {
        echo "- $dir: EXISTIERT BEREITS\n";
    }
    
    // Setze Berechtigungen
    chmod($dir, 0755);
}

// Erstelle .htaccess-Dateien für Sicherheit
$htaccessContent = "# Verbiete Verzeichnisauflistung
Options -Indexes

# Verbiete Zugriff auf alle Dateien
<FilesMatch \".*\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Erlaube Zugriff auf bestimmte Dateitypen
<FilesMatch \"\.(jpg|jpeg|png|gif|css|js|html|xml)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>";

echo "\nErstelle .htaccess-Dateien für Sicherheit:\n";
foreach (['results', 'logs'] as $dir) {
    $htaccessFile = "$dir/.htaccess";
    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "- $htaccessFile: ERSTELLT\n";
    } else {
        echo "- $htaccessFile: FEHLER BEIM ERSTELLEN\n";
    }
}

// Erstelle Konfigurationsdatei
echo "\nPrüfe Konfigurationsdatei:\n";
if (!file_exists('config/config.php')) {
    echo "- config/config.php: NICHT GEFUNDEN\n";
    
    // Erstelle Verzeichnis, falls es nicht existiert
    if (!file_exists('config')) {
        mkdir('config', 0755, true);
    }
    
    // Erstelle Standardkonfiguration
    $configContent = '<?php
class Config {
    private static $config = [
        \'local\' => [
            \'db_host\' => \'localhost\',
            \'db_name\' => \'mcq_test_system\',
            \'db_user\' => \'root\',
            \'db_password\' => \'\',
            \'base_url\' => \'http://localhost/mcq-test-system\'
        ],
        \'production\' => [
            \'db_host\' => \'localhost\',
            \'db_name\' => \'mcq_test_system\',
            \'db_user\' => \'PRODUCTION_USER\',  // Auf dem Server anzupassen
            \'db_password\' => \'PRODUCTION_PW\', // Auf dem Server anzupassen
            \'base_url\' => \'https://ihre-domain.de/mcq-test-system\'  // Auf dem Server anzupassen
        ]
    ];

    public static function get($environment = \'local\') {
        if (!isset(self::$config[$environment])) {
            throw new Exception("Ungültige Umgebung: $environment");
        }
        return self::$config[$environment];
    }

    public static function getEnvironment() {
        // Prüfe ob wir uns auf dem Produktionsserver befinden
        return (strpos($_SERVER[\'HTTP_HOST\'], \'localhost\') !== false) ? \'local\' : \'production\';
    }
}';
    
    if (file_put_contents('config/config.php', $configContent)) {
        echo "- config/config.php: ERSTELLT\n";
    } else {
        echo "- config/config.php: FEHLER BEIM ERSTELLEN\n";
    }
} else {
    echo "- config/config.php: EXISTIERT BEREITS\n";
}

// Erstelle API-Konfigurationsdatei
echo "\nPrüfe API-Konfigurationsdatei:\n";
if (!file_exists('config/api_config.json')) {
    $apiConfigContent = '{
    "api_key": "YOUR_OPENAI_API_KEY"
}';
    
    if (file_put_contents('config/api_config.json', $apiConfigContent)) {
        echo "- config/api_config.json: ERSTELLT\n";
    } else {
        echo "- config/api_config.json: FEHLER BEIM ERSTELLEN\n";
    }
} else {
    echo "- config/api_config.json: EXISTIERT BEREITS\n";
}

// Initialisiere Datenbank
echo "\nInitialisiere Datenbank:\n";
try {
    require_once 'includes/init_database.php';
    
    $initializer = new DatabaseInitializer();
    $initializer->initializeTables();
    
    echo "- Datenbank-Initialisierung: ERFOLGREICH\n";
} catch (Exception $e) {
    echo "- Datenbank-Initialisierung: FEHLER (" . $e->getMessage() . ")\n";
}

// Prüfe Umgebung
echo "\nPrüfe Serverumgebung:\n";

// PHP-Version
echo "- PHP-Version: " . phpversion() . "\n";

// Erforderliche PHP-Erweiterungen
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'curl', 'xml', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    echo "- Extension $ext: " . (extension_loaded($ext) ? "OK" : "FEHLT") . "\n";
}

// Externe Programme
echo "\nExterne Programme (optional):\n";

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
echo "- Ghostscript: " . ($gsReturnVar === 0 ? "OK (v" . trim($gsOutput[0]) . ")" : "NICHT GEFUNDEN") . "\n";

// pdftotext
exec('pdftotext -v 2>&1', $pdftotextOutput, $pdftotextReturnVar);
echo "- pdftotext: " . ($pdftotextReturnVar === 0 ? "OK" : "NICHT GEFUNDEN") . "\n";

echo "\nHinweis: Wenn externe Programme nicht gefunden wurden, kann der verbesserte Test-Generator";
echo "\ntrotzdem mit OpenAI Vision API funktionieren, wenn ein gültiger API-Key konfiguriert ist.\n";

// Erstelle eine Testdatei im temporären Verzeichnis
echo "\nPrüfe Schreibzugriff auf temporäres Verzeichnis:\n";
$tempDir = sys_get_temp_dir();
echo "- Temporäres Verzeichnis: $tempDir\n";

$testFile = $tempDir . '/test_' . uniqid() . '.txt';
$testContent = "Test " . date('Y-m-d H:i:s');
$writeSuccess = @file_put_contents($testFile, $testContent);
echo "- Testdatei schreiben: " . ($writeSuccess !== false ? "OK" : "FEHLGESCHLAGEN") . "\n";

if ($writeSuccess !== false) {
    $readContent = @file_get_contents($testFile);
    echo "- Testdatei lesen: " . ($readContent === $testContent ? "OK" : "FEHLGESCHLAGEN") . "\n";
    @unlink($testFile);
}

echo "\n=== Setup abgeschlossen ===\n";
echo "\nBitte führen Sie folgende Schritte manuell durch:\n";
echo "1. Passen Sie die Datenbankverbindungsdaten in config/config.php an\n";
echo "2. Fügen Sie Ihren OpenAI API-Key in config/api_config.json ein\n";
echo "3. Führen Sie deploy.php aus, um die Datenbank zu synchronisieren\n";
echo "4. Testen Sie die Anwendung, indem Sie die Startseite aufrufen\n"; 