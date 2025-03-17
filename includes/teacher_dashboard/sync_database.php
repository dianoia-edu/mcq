<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/sync.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    
    // Sammle alle vorhandenen Test-Codes aus den XML-Dateien
    $resultsDir = __DIR__ . '/../../results';
    $existingTestCodes = [];
    
    // Durchsuche alle Unterordner nach XML-Dateien
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resultsDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'xml') {
            $filename = $file->getBasename();
            // Extrahiere den Test-Code aus dem Dateinamen (erstes Segment vor dem Unterstrich)
            if (preg_match('/^([A-Z0-9]+)_/', $filename, $matches)) {
                $existingTestCodes[$matches[1]] = true;
            }
        }
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
    
    // Füge KKW und KK3 Tests hinzu, wenn sie nicht existieren
    $testCheck = $db->prepare("SELECT test_id FROM tests WHERE access_code = ?");
    
    $testsToAdd = [
        'KKW' => 'Test KKW',
        'KK3' => 'Test KK3'
    ];
    
    foreach ($testsToAdd as $code => $title) {
        $testCheck->execute([$code]);
        if (!$testCheck->fetch()) {
            $testId = 'test_' . uniqid();
            $createTest = $db->prepare("
                INSERT INTO tests (
                    test_id, 
                    access_code, 
                    title, 
                    question_count, 
                    answer_count, 
                    answer_type
                ) VALUES (
                    ?, ?, ?, 0, 0, 'single'
                )
            ");
            $createTest->execute([$testId, $code, $title]);
            writeLog("Test hinzugefügt: ID=$testId, Code=$code, Title=$title");
        }
    }
    
    $stats = [
        'added' => 0,
        'deleted' => 0,
        'total' => 0
    ];

    // 1. Sammle alle XML-Dateien aus dem results-Ordner
    $xmlFiles = [];
    
    // Durchsuche alle Unterordner nach XML-Dateien
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resultsDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'xml') {
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($resultsDir) + 1));
            $xmlFiles[$relativePath] = $file->getPathname();
        }
    }
    
    writeLog("Gefundene XML-Dateien: " . count($xmlFiles));

    // 2. Hole alle Einträge aus der Datenbank
    $stmt = $db->query("SELECT attempt_id, xml_file_path FROM test_attempts");
    $dbEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Datenbankeinträge: " . count($dbEntries));

    // 3. Finde und lösche verwaiste Datenbankeinträge
    foreach ($dbEntries as $entry) {
        $xmlPath = $entry['xml_file_path'];
        $relativePath = str_replace('\\', '/', substr($xmlPath, strpos($xmlPath, 'results/') + 8));
        
        if (!isset($xmlFiles[$relativePath])) {
            // XML-Datei existiert nicht mehr, lösche Datenbankeintrag
            $deleteStmt = $db->prepare("DELETE FROM test_attempts WHERE attempt_id = ?");
            $deleteStmt->execute([$entry['attempt_id']]);
            $stats['deleted']++;
            writeLog("Gelöschter Eintrag: " . $xmlPath);
        }
    }

    // 4. Füge neue XML-Dateien zur Datenbank hinzu
    $insertStmt = $db->prepare("
        INSERT INTO test_attempts (test_id, student_name, completed_at, xml_file_path) 
        VALUES (
            (SELECT test_id FROM tests WHERE access_code = ?),
            ?,
            ?,
            ?
        )
    ");

    foreach ($xmlFiles as $relativePath => $fullPath) {
        // Prüfe ob die Datei bereits in der Datenbank ist
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM test_attempts WHERE xml_file_path LIKE ?");
        $checkStmt->execute(['%' . $relativePath]);
        
        if ($checkStmt->fetchColumn() == 0) {
            // Parse XML-Datei für Metadaten
            $xml = simplexml_load_file($fullPath);
            if ($xml === false) {
                writeLog("Fehler beim Lesen der XML-Datei: " . $fullPath);
                continue;
            }
            
            // Extrahiere Informationen aus dem Dateinamen
            if (preg_match('/^([A-Z0-9]+)_(.+?)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.xml$/', basename($relativePath), $matches)) {
                $accessCode = $matches[1];
                $studentName = $matches[2];
                $timestamp = str_replace('_', ' ', $matches[3]);
                
                writeLog("Extrahierte Daten: Code=$accessCode, Name=$studentName, Zeit=$timestamp");
                
                try {
                    // Prüfe ob der Test existiert
                    $testCheck = $db->prepare("SELECT test_id FROM tests WHERE access_code = ?");
                    $testCheck->execute([$accessCode]);
                    $testId = $testCheck->fetchColumn();
                    
                    if (!$testId) {
                        writeLog("Test mit Code $accessCode nicht gefunden - erstelle neuen Test");
                        // Erstelle einen neuen Test wenn nicht vorhanden
                        $testId = 'test_' . uniqid();
                        $createTest = $db->prepare("
                            INSERT INTO tests (
                                test_id, 
                                access_code, 
                                title, 
                                question_count, 
                                answer_count, 
                                answer_type
                            ) VALUES (
                                ?, ?, ?, 0, 0, 'single'
                            )
                        ");
                        $createTest->execute([$testId, $accessCode, "Test " . $accessCode]);
                        writeLog("Neuer Test erstellt: ID=$testId, Code=$accessCode");
                    } else {
                        writeLog("Bestehender Test gefunden: ID=$testId, Code=$accessCode");
                    }
                    
                    $insertStmt->execute([
                        $accessCode,
                        $studentName,
                        $timestamp,
                        'results/' . $relativePath
                    ]);
                    $stats['added']++;
                    writeLog("Neuer Eintrag: " . $relativePath);
                } catch (Exception $e) {
                    writeLog("Fehler beim Einfügen: " . $relativePath . " - " . $e->getMessage());
                }
            } else {
                writeLog("Dateiname entspricht nicht dem erwarteten Format: " . basename($relativePath));
            }
        }
    }

    // 5. Ermittle Gesamtanzahl der Datensätze
    $stats['total'] = $db->query("SELECT COUNT(*) FROM test_attempts")->fetchColumn();

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