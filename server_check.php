<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Serverüberprüfung ===\n\n";

// PHP-Version prüfen
echo "PHP-Version: " . phpversion() . "\n";

// Erforderliche PHP-Erweiterungen prüfen
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'curl', 'xml', 'mbstring'];
echo "\nPHP-Erweiterungen:\n";
foreach ($requiredExtensions as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? "OK" : "FEHLT") . "\n";
}

// Externe Programme prüfen
echo "\nExterne Programme:\n";

// Tesseract OCR
exec('tesseract --version 2>&1', $tesseractOutput, $tesseractReturnVar);
echo "- Tesseract OCR: " . ($tesseractReturnVar === 0 ? "OK (" . trim($tesseractOutput[0]) . ")" : "FEHLT - Bitte installieren") . "\n";

// Ghostscript
exec('gs --version 2>&1', $gsOutput, $gsReturnVar);
if ($gsReturnVar !== 0) {
    // Versuche alternative Befehle für Windows
    exec('gswin64c --version 2>&1', $gsOutput, $gsReturnVar);
    if ($gsReturnVar !== 0) {
        exec('gswin32c --version 2>&1', $gsOutput, $gsReturnVar);
    }
}
echo "- Ghostscript: " . ($gsReturnVar === 0 ? "OK (v" . trim($gsOutput[0]) . ")" : "FEHLT - Bitte installieren") . "\n";

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
        echo "- $dir: NICHT GEFUNDEN - Bitte erstellen\n";
        continue;
    }
    echo "- $dir: " . (is_writable($dir) ? "Schreibbar" : "NICHT SCHREIBBAR - Bitte Berechtigungen anpassen") . "\n";
}

// Datenbankverbindung testen
echo "\nDatenbankverbindung:\n";
try {
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

echo "\n=== Überprüfung abgeschlossen ===\n";
echo "\nBitte stellen Sie sicher, dass alle externen Programme (Tesseract, Ghostscript) auf dem Server installiert sind.\n";
echo "Wenn Programme fehlen, installieren Sie diese bitte gemäß der Installationsanleitung in der README.md.\n"; 