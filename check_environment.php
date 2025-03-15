<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Umgebungsprüfung ===\n\n";

// PHP-Version prüfen
echo "PHP-Version: " . phpversion() . "\n";

// Erforderliche PHP-Erweiterungen prüfen
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'curl', 'xml', 'mbstring'];
echo "\nPHP-Erweiterungen:\n";
foreach ($requiredExtensions as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? "OK" : "FEHLT") . "\n";
}

// Verzeichnisberechtigungen prüfen
$directories = [
    'logs',
    'results',
    'uploads',
    sys_get_temp_dir()
];

echo "\nVerzeichnisberechtigungen:\n";
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        echo "- $dir: NICHT GEFUNDEN\n";
        continue;
    }
    echo "- $dir: " . (is_writable($dir) ? "Schreibbar" : "NICHT SCHREIBBAR") . "\n";
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
echo "- Ghostscript: " . ($gsReturnVar === 0 ? "OK (v" . trim($gsOutput[0]) . ")" : "NICHT GEFUNDEN") . "\n";

// Datenbankverbindung testen
echo "\nDatenbankverbindung:\n";
try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/includes/database_config.php';
    
    $dbConfig = DatabaseConfig::getInstance();
    $conn = $dbConfig->getConnection();
    
    echo "- Verbindung: OK\n";
    
    // Tabellen prüfen
    $tables = ['tests', 'test_attempts', 'test_statistics', 'daily_attempts'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        echo "- Tabelle '$table': " . ($stmt->rowCount() > 0 ? "OK" : "NICHT GEFUNDEN") . "\n";
    }
} catch (Exception $e) {
    echo "- Verbindung: FEHLER (" . $e->getMessage() . ")\n";
}

// Upload-Konfiguration prüfen
echo "\nPHP-Upload-Konfiguration:\n";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- post_max_size: " . ini_get('post_max_size') . "\n";
echo "- max_execution_time: " . ini_get('max_execution_time') . " Sekunden\n";
echo "- memory_limit: " . ini_get('memory_limit') . "\n";

// Temporäres Verzeichnis
echo "\nTemporäres Verzeichnis: " . sys_get_temp_dir() . "\n";
echo "- Existiert: " . (file_exists(sys_get_temp_dir()) ? "JA" : "NEIN") . "\n";
echo "- Schreibbar: " . (is_writable(sys_get_temp_dir()) ? "JA" : "NEIN") . "\n";

// Test-Datei erstellen
$testFile = sys_get_temp_dir() . '/test_' . uniqid() . '.txt';
$testContent = "Test " . date('Y-m-d H:i:s');
$writeSuccess = @file_put_contents($testFile, $testContent);
echo "- Testdatei schreiben: " . ($writeSuccess !== false ? "OK" : "FEHLGESCHLAGEN") . "\n";

if ($writeSuccess !== false) {
    $readContent = @file_get_contents($testFile);
    echo "- Testdatei lesen: " . ($readContent === $testContent ? "OK" : "FEHLGESCHLAGEN") . "\n";
    @unlink($testFile);
}

echo "\n=== Prüfung abgeschlossen ===\n"; 