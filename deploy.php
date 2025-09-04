<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

// Logging-Funktion
function deployLog($message) {
    $logFile = __DIR__ . '/logs/deploy.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    echo $logMessage;
    
    // Stelle sicher, dass das Logs-Verzeichnis existiert
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    deployLog("Starte Deployment-Prozess...");
    
    // 1. Prüfe Verzeichnisse
    $directories = ['logs', 'results', 'uploads'];
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            deployLog("Verzeichnis '$dir' erstellt");
        } else if (!is_writable($dir)) {
            throw new Exception("Verzeichnis '$dir' ist nicht beschreibbar");
        }
    }
    
    // 2. Initialisiere Datenbank
    deployLog("Initialisiere Datenbank...");
    require_once __DIR__ . '/includes/database_config.php';
    
    $dbConfig = DatabaseConfig::getInstance();
    $dbConfig->createDatabase();
    $dbConfig->initializeTables();
    deployLog("Datenbank-Initialisierung abgeschlossen");
    
    // 3. Synchronisiere Datenbank mit XML-Dateien
    deployLog("Synchronisiere Datenbank mit XML-Dateien...");
    require_once __DIR__ . '/includes/teacher_dashboard/sync_database.php';
    
    $sync = new DatabaseSync();
    $result = $sync->synchronize();
    
    if (isset($result['success']) && $result['success']) {
        deployLog("Synchronisation erfolgreich:");
        deployLog("- Hinzugefügte Einträge: " . $result['added']);
        deployLog("- Gelöschte Einträge: " . $result['deleted']);
        deployLog("- Gesamtanzahl: " . $result['total']);
    } else {
        $errorMsg = isset($result['error']) ? $result['error'] : "Unbekannter Fehler";
        throw new Exception("Synchronisation fehlgeschlagen: " . $errorMsg);
    }
    
    // 4. Prüfe externe Programme
    deployLog("Prüfe externe Programme...");
    
    // Tesseract OCR
    exec('tesseract --version 2>&1', $tesseractOutput, $tesseractReturnVar);
    if ($tesseractReturnVar === 0) {
        deployLog("Tesseract OCR: OK (" . trim($tesseractOutput[0]) . ")");
    } else {
        deployLog("WARNUNG: Tesseract OCR nicht gefunden. PDF- und Bildverarbeitung wird nicht funktionieren.");
    }
    
    // Ghostscript
    exec('gs --version 2>&1', $gsOutput, $gsReturnVar);
    if ($gsReturnVar !== 0) {
        // Versuche alternative Befehle für Windows
        exec('gswin64c --version 2>&1', $gsOutput, $gsReturnVar);
        if ($gsReturnVar !== 0) {
            exec('gswin32c --version 2>&1', $gsOutput, $gsReturnVar);
        }
    }
    
    if ($gsReturnVar === 0) {
        deployLog("Ghostscript: OK (v" . trim($gsOutput[0]) . ")");
    } else {
        deployLog("WARNUNG: Ghostscript nicht gefunden. PDF-Verarbeitung wird nicht funktionieren.");
    }
    
    deployLog("Deployment erfolgreich abgeschlossen!");
    
} catch (Exception $e) {
    deployLog("FEHLER: " . $e->getMessage());
    exit(1);
} 