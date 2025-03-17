<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database_config.php';

// Setze HTTP_HOST für CLI-Ausführung
if (!isset($_SERVER['HTTP_HOST']) && php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/sync.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    
    // Sammle alle vorhandenen Test-Codes aus den XML-Dateien
    $resultsDir = __DIR__ . '/../../tests';
    $existingTestCodes = [];
    $testMetadata = [];
    
    // Durchsuche alle XML-Dateien im tests-Verzeichnis
    if (is_dir($resultsDir)) {
        $files = glob($resultsDir . '/*.xml');
        foreach ($files as $file) {
            $filename = basename($file);
            // Extrahiere den Test-Code aus dem Dateinamen (erstes Segment vor dem Unterstrich)
            if (preg_match('/^([A-Z0-9]+)_/', $filename, $matches)) {
                $accessCode = $matches[1];
                $existingTestCodes[$accessCode] = true;
                
                // Lese XML-Datei für Metadaten
                $xml = @simplexml_load_file($file);
                if ($xml !== false) {
                    $testMetadata[$accessCode] = [
                        'title' => (string)$xml->title,
                        'question_count' => (int)$xml->question_count,
                        'answer_count' => (int)$xml->answer_count,
                        'answer_type' => (string)$xml->answer_type
                    ];
                    writeLog("Metadaten für Test $accessCode gelesen: " . json_encode($testMetadata[$accessCode]));
                }
            }
        }
    } else {
        writeLog("Verzeichnis $resultsDir existiert nicht oder ist nicht lesbar");
    }
    
    writeLog("Gefundene Test-Codes in XML-Dateien: " . implode(", ", array_keys($existingTestCodes)));
    
    // Lösche Tests ohne zugehörige XML-Dateien
    $allTestsStmt = $db->query("SELECT test_id, access_code FROM tests");
    $testsToDelete = [];
    
    while ($test = $allTestsStmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($existingTestCodes[$test['access_code']])) {
            $testsToDelete[] = $test['test_id'];
            writeLog("Test zum Löschen markiert: ID=" . $test['test_id'] . ", Code=" . $test['access_code']);
        }
    }
    
    if (!empty($testsToDelete)) {
        // Lösche zuerst die Teststatistiken
        $deleteStatsStmt = $db->prepare("DELETE FROM test_statistics WHERE test_id = ?");
        foreach ($testsToDelete as $testId) {
            $deleteStatsStmt->execute([$testId]);
        }
        
        // Dann lösche die Testversuche
        $deleteAttemptsStmt = $db->prepare("DELETE FROM test_attempts WHERE test_id = ?");
        foreach ($testsToDelete as $testId) {
            $deleteAttemptsStmt->execute([$testId]);
        }
        
        // Zuletzt lösche die Tests
        $deleteTestsStmt = $db->prepare("DELETE FROM tests WHERE test_id = ?");
        foreach ($testsToDelete as $testId) {
            $deleteTestsStmt->execute([$testId]);
        }
        
        writeLog("Gelöschte Tests: " . count($testsToDelete));
    }
    
    // Füge Tests hinzu oder aktualisiere sie, wenn sie nicht existieren
    $testCheck = $db->prepare("SELECT test_id FROM tests WHERE access_code = ?");
    
    foreach ($existingTestCodes as $accessCode => $value) {
        $testCheck->execute([$accessCode]);
        $existingTest = $testCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingTest) {
            // Test existiert nicht, füge ihn hinzu
            if (isset($testMetadata[$accessCode])) {
                $meta = $testMetadata[$accessCode];
                $testId = $accessCode . '_' . uniqid();
                
                $createTest = $db->prepare("INSERT INTO tests (test_id, access_code, title, question_count, answer_count, answer_type) VALUES (?, ?, ?, ?, ?, ?)");
                $createTest->execute([
                    $testId, 
                    $accessCode, 
                    $meta['title'], 
                    $meta['question_count'], 
                    $meta['answer_count'], 
                    $meta['answer_type']
                ]);
                writeLog("Test hinzugefügt: ID=$testId, Code=$accessCode, Title={$meta['title']}");
            } else {
                writeLog("Keine Metadaten für Test $accessCode gefunden, überspringe");
            }
        } else {
            // Test existiert, aktualisiere ihn wenn nötig
            if (isset($testMetadata[$accessCode])) {
                $meta = $testMetadata[$accessCode];
                $updateTest = $db->prepare("UPDATE tests SET title = ?, question_count = ?, answer_count = ?, answer_type = ? WHERE access_code = ?");
                $updateTest->execute([
                    $meta['title'], 
                    $meta['question_count'], 
                    $meta['answer_count'], 
                    $meta['answer_type'],
                    $accessCode
                ]);
                writeLog("Test aktualisiert: Code=$accessCode, Title={$meta['title']}");
            }
        }
    }
    
    $stats = [
        'added' => 0,
        'deleted' => count($testsToDelete),
        'total' => 0
    ];

    // Ermittle Gesamtanzahl der Tests
    $stats['total'] = $db->query("SELECT COUNT(*) FROM tests")->fetchColumn();

    echo json_encode([
        'success' => true,
        'added' => $stats['added'],
        'deleted' => $stats['deleted'],
        'total' => $stats['total']
    ]);

} catch (Exception $e) {
    writeLog("Fehler bei der Synchronisation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 