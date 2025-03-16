<?php
// Aktiviere Error-Reporting für Entwicklung
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Setze Header für JSON
header('Content-Type: application/json');

// Lade Datenbankverbindung
require_once dirname(__DIR__) . '/includes/database_config.php';

try {
    // Überprüfe, ob Test-IDs übergeben wurden
    if (!isset($_POST['test_ids']) || empty($_POST['test_ids'])) {
        throw new Exception('Keine Tests zum Löschen ausgewählt');
    }
    
    $testData = json_decode($_POST['test_ids'], true);
    
    if (!is_array($testData) || empty($testData)) {
        throw new Exception('Ungültiges Format für Test-IDs');
    }
    
    // Initialisiere Zähler
    $deletedFiles = 0;
    $deletedDbTests = 0;
    $deletedAttempts = 0;
    $debugInfo = [];
    
    // Hole Datenbankverbindung
    $dbConfig = DatabaseConfig::getInstance();
    $db = $dbConfig->getConnection();
    
    // Starte Transaktion
    $db->beginTransaction();
    
    try {
        // Für jeden Test
        foreach ($testData as $test) {
            $testId = $test['test_id'];
            $fileName = isset($test['file_name']) ? $test['file_name'] : $testId;
            
            // Debug-Info für diesen Test
            $testDebug = [
                'test_id' => $testId,
                'file_name' => $fileName
            ];
            
            // 1. Hole Informationen zum Test aus der Datenbank
            $stmt = $db->prepare("SELECT test_id, access_code, title FROM tests WHERE test_id = ?");
            $stmt->execute([$testId]);
            $dbTest = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$dbTest) {
                $testDebug['db_found'] = false;
                
                // Versuche, die Datei trotzdem zu löschen, falls sie existiert
                $testFilePath = dirname(__DIR__) . '/tests/' . $fileName . '.xml';
                $testDebug['file_path'] = $testFilePath;
                $testDebug['file_exists'] = file_exists($testFilePath);
                
                if (file_exists($testFilePath)) {
                    if (unlink($testFilePath)) {
                        $deletedFiles++;
                        $testDebug['file_deleted'] = true;
                    } else {
                        $testDebug['file_deleted'] = false;
                        $testDebug['delete_error'] = error_get_last();
                    }
                }
                
                $debugInfo[] = $testDebug;
                continue; // Test nicht in der Datenbank, überspringe weitere DB-Operationen
            }
            
            $testDebug['db_found'] = true;
            $testDebug['access_code'] = $dbTest['access_code'];
            $testDebug['title'] = $dbTest['title'];
            
            // 2. Lösche die XML-Datei, wenn vorhanden
            // Versuche verschiedene Dateinamenvarianten
            $possibleFilePaths = [
                dirname(__DIR__) . '/tests/' . $fileName . '.xml',
                dirname(__DIR__) . '/tests/' . $testId . '.xml',
                dirname(__DIR__) . '/tests/' . $dbTest['access_code'] . '.xml',
                dirname(__DIR__) . '/tests/' . $dbTest['access_code'] . '_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $dbTest['title'])) . '.xml'
            ];
            
            $testDebug['possible_paths'] = $possibleFilePaths;
            $fileDeleted = false;
            
            foreach ($possibleFilePaths as $filePath) {
                if (file_exists($filePath)) {
                    $testDebug['found_at'] = $filePath;
                    if (unlink($filePath)) {
                        $deletedFiles++;
                        $fileDeleted = true;
                        $testDebug['file_deleted'] = true;
                        break;
                    } else {
                        $testDebug['delete_error'] = error_get_last();
                    }
                }
            }
            
            if (!$fileDeleted) {
                $testDebug['file_deleted'] = false;
            }
            
            // 3. Hole alle Testergebnisse für diesen Test
            $stmt = $db->prepare("SELECT xml_file_path FROM test_attempts WHERE test_id = ?");
            $stmt->execute([$testId]);
            $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $testDebug['attempt_count'] = count($attempts);
            $deletedResultFiles = 0;
            
            // 4. Lösche die Testergebnis-XML-Dateien
            foreach ($attempts as $attempt) {
                $xmlPath = $attempt['xml_file_path'];
                if (file_exists($xmlPath)) {
                    if (unlink($xmlPath)) {
                        $deletedResultFiles++;
                    }
                }
            }
            
            $testDebug['deleted_result_files'] = $deletedResultFiles;
            
            // 5. Lösche die Einträge in der daily_attempts Tabelle
            $stmt = $db->prepare("DELETE FROM daily_attempts WHERE test_id = ?");
            $stmt->execute([$testId]);
            $testDebug['deleted_daily_attempts'] = $stmt->rowCount();
            
            // 6. Lösche die Einträge in der test_statistics Tabelle
            $stmt = $db->prepare("DELETE FROM test_statistics WHERE test_id = ?");
            $stmt->execute([$testId]);
            $testDebug['deleted_statistics'] = $stmt->rowCount();
            
            // 7. Lösche die Einträge in der test_attempts Tabelle
            $stmt = $db->prepare("DELETE FROM test_attempts WHERE test_id = ?");
            $stmt->execute([$testId]);
            $deletedAttempts += $stmt->rowCount();
            $testDebug['deleted_attempts'] = $stmt->rowCount();
            
            // 8. Lösche den Test aus der tests Tabelle
            $stmt = $db->prepare("DELETE FROM tests WHERE test_id = ?");
            $stmt->execute([$testId]);
            $deletedDbTests += $stmt->rowCount();
            $testDebug['deleted_from_db'] = $stmt->rowCount();
            
            $debugInfo[] = $testDebug;
        }
        
        // Commit der Transaktion
        $db->commit();
        
        // Erfolg melden
        echo json_encode([
            'success' => true,
            'message' => 'Tests erfolgreich gelöscht',
            'deleted_files' => $deletedFiles,
            'deleted_db_tests' => $deletedDbTests,
            'deleted_attempts' => $deletedAttempts,
            'debug_info' => $debugInfo
        ]);
        
    } catch (Exception $e) {
        // Rollback bei Fehler
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Fehler melden
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 