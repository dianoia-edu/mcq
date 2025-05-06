<?php
// Löschfunktion für Testergebnisse im Teacher-Dashboard

require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== Start delete_test_result.php ===");

// Überprüfen, ob es sich um eine AJAX-Anfrage handelt
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    writeLog("Kein AJAX-Aufruf - Zugriff verweigert");
    http_response_code(403);
    echo "Zugriff verweigert";
    exit;
}

// Überprüfen, ob der Dateiname übergeben wurde
if (!isset($_POST['file']) || empty($_POST['file'])) {
    writeLog("Kein Dateiname angegeben");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Kein Dateiname angegeben'
    ]);
    exit;
}

$file = $_POST['file'];
writeLog("Anfrage zum Löschen der Datei: " . $file);

// Normalisiere den Dateipfad und sichere ihn ab
$file = str_replace('\\', '/', $file);
$file = str_replace('../', '', $file); // Vermeide Directory Traversal
$fullPath = __DIR__ . '/../../' . ltrim($file, '/');

writeLog("Normalisierter Pfad: " . $fullPath);

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");
    
    // Beginne eine Transaktion
    $db->beginTransaction();
    
    // Datenbankeinträge löschen
    $fileName = basename($file);
    writeLog("Suche in Datenbank nach Dateiname: " . $fileName);
    
    // Lösche alle Datenbankeinträge, die auf diese Datei verweisen
    $stmt = $db->prepare("DELETE FROM test_attempts WHERE xml_file_path LIKE ?");
    $stmt->execute(['%' . $fileName . '%']);
    $count = $stmt->rowCount();
    writeLog("Gelöschte Datenbankeinträge: " . $count);
    
    // Überprüfe, ob die Datei existiert
    if (file_exists($fullPath)) {
        writeLog("Datei existiert: " . $fullPath);
        
        // Lösche die XML-Datei
        if (unlink($fullPath)) {
            writeLog("Datei erfolgreich gelöscht");
        } else {
            // Dateifehler, aber Datenbank wurde bereits bereinigt
            writeLog("Fehler beim Löschen der Datei. Berechtigungsproblem?");
            throw new Exception("Die Datei konnte nicht gelöscht werden. Prüfen Sie die Berechtigungen.");
        }
    } else {
        writeLog("Datei existiert nicht: " . $fullPath);
        // Wir löschen trotzdem nur die Datenbankeinträge
    }
    
    // Commit der Transaktion
    $db->commit();
    
    // Erfolgreiche Antwort
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Testergebnis gelöscht',
        'deletedInDb' => $count,
        'fileDeleted' => file_exists($fullPath) ? false : true
    ]);
    
} catch (Exception $e) {
    // Rollback bei Fehler
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    writeLog("Fehler: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

writeLog("=== Ende delete_test_result.php ===");
?> 