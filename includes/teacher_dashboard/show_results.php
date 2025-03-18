<?php
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== Start show_results.php ===");

$file = isset($_GET['file']) ? $_GET['file'] : '';
writeLog("Angefragter Dateiname: " . $file);

if (empty($file)) {
    writeLog("Kein Dateiname angegeben, leite um");
    header('Location: index.php');
    exit;
}

// Verbindung zur Datenbank herstellen
try {
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");
    
    // Dateiinformationen aus der Datenbank holen
    $stmt = $db->prepare("SELECT xml_file_path FROM test_attempts WHERE xml_file_path LIKE ?");
    $stmt->execute(['%' . $file . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $xmlPath = $result['xml_file_path'];
        writeLog("Datenbankpfad: " . $xmlPath);
        
        // Baue den vollständigen Pfad
        $fullPath = __DIR__ . '/../../' . $xmlPath;
        writeLog("Vollständiger Pfad: " . $fullPath);
        
        if (file_exists($fullPath)) {
            writeLog("Datei existiert");
            // Datei verarbeiten...
        } else {
            writeLog("Datei existiert nicht: " . $fullPath);
            // Fehlerbehandlung...
        }
    } else {
        writeLog("Kein Datensatz in der Datenbank gefunden");
        // Fehlerbehandlung...
    }
} catch (Exception $e) {
    writeLog("Datenbankfehler: " . $e->getMessage());
    // Fehlerbehandlung...
} 