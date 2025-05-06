<?php
// Löschfunktion für Testergebnisgruppen im Teacher-Dashboard

require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== Start delete_test_group.php ===");

// Überprüfen, ob es sich um eine AJAX-Anfrage handelt
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    writeLog("Kein AJAX-Aufruf - Zugriff verweigert");
    http_response_code(403);
    echo "Zugriff verweigert";
    exit;
}

// Überprüfen, ob der Zugangscode übergeben wurde
if (!isset($_POST['access_code']) || empty($_POST['access_code'])) {
    writeLog("Kein Zugangscode angegeben");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Kein Zugangscode angegeben'
    ]);
    exit;
}

$accessCode = $_POST['access_code'];
writeLog("Anfrage zum Löschen der Testergebnisgruppe mit Code: " . $accessCode);

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");
    
    // Beginne eine Transaktion
    $db->beginTransaction();
    
    // Suche alle Testergebnisse für diesen Test-Code
    writeLog("Suche alle Testergebnisse für den Test-Code: " . $accessCode);
    
    // Zuerst Test-ID anhand des Zugangscodes ermitteln
    $testIdQuery = $db->prepare("SELECT test_id FROM tests WHERE access_code = ?");
    $testIdQuery->execute([$accessCode]);
    $testId = $testIdQuery->fetchColumn();
    
    if (!$testId) {
        throw new Exception("Test mit dem angegebenen Zugangscode nicht gefunden");
    }
    
    writeLog("Test-ID ermittelt: " . $testId);
    
    // Finde alle Dateipfade der zu löschenden XML-Dateien
    $filePathsQuery = $db->prepare("SELECT xml_file_path FROM test_attempts WHERE test_id = ?");
    $filePathsQuery->execute([$testId]);
    $filePaths = $filePathsQuery->fetchAll(PDO::FETCH_COLUMN);
    
    writeLog("Gefundene XML-Dateien: " . count($filePaths));
    
    // Lösche alle Dateien
    $deletedFiles = 0;
    foreach ($filePaths as $filePath) {
        if (empty($filePath)) continue;
        
        // Normalisiere den Pfad
        $filePath = str_replace('\\', '/', $filePath);
        $fullPath = __DIR__ . '/../../' . ltrim($filePath, '/');
        
        writeLog("Versuche zu löschen: " . $fullPath);
        
        if (file_exists($fullPath)) {
            if (unlink($fullPath)) {
                $deletedFiles++;
                writeLog("Datei gelöscht: " . $fullPath);
            } else {
                writeLog("Fehler beim Löschen der Datei: " . $fullPath);
                // Wir setzen fort, auch wenn das Löschen einer Datei fehlschlägt
            }
        } else {
            writeLog("Datei existiert nicht: " . $fullPath);
        }
    }
    
    // Lösche alle Datenbankeinträge für diesen Test
    $deleteQuery = $db->prepare("DELETE FROM test_attempts WHERE test_id = ?");
    $deleteQuery->execute([$testId]);
    $deletedRecords = $deleteQuery->rowCount();
    
    writeLog("Gelöschte Datenbankeinträge: " . $deletedRecords);
    
    // Commit der Transaktion
    $db->commit();
    
    // Erfolgreiche Antwort
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Testergebnisgruppe gelöscht',
        'count' => $deletedRecords,
        'files_deleted' => $deletedFiles
    ]);
    
} catch (Exception $e) {
    // Rollback bei Fehler
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    writeLog("Fehler: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

writeLog("=== Ende delete_test_group.php ===");
?> 