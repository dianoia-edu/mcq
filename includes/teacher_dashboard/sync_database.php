<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/sync.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Führe vollständige Auswertung für den Test durch
function performEvaluation($fullPath) {
    writeLog("Führe vollständige Auswertung für " . $fullPath . " durch");
    
    // Definiere Konstante, um das HTML und JavaScript in auswertung.php zu überspringen
    if (!defined('FUNCTIONS_ONLY')) {
        define('FUNCTIONS_ONLY', true);
    }
    
    // Lade Auswertungsfunktionen
    require_once_safe(__DIR__ . '/../../auswertung.php');
    $results = evaluateTest($fullPath);
    
    if ($results !== false) {
        $pointsAchieved = $results['achieved'];
        $pointsMaximum = $results['max'];
        $percentage = $results['percentage'];
        
        // Berechne Note anhand des aktiven Notenschemas
        $schema = loadGradeSchema();
        $grade = calculateGrade($percentage, $schema);
        
        writeLog("Auswertung erfolgreich: Punkte=$pointsAchieved/$pointsMaximum, Prozent=$percentage%, Note=$grade");
        return [
            'achieved' => $pointsAchieved,
            'maximum' => $pointsMaximum,
            'percentage' => $percentage,
            'grade' => $grade
        ];
    }
    
    writeLog("Fehler bei der Auswertung der XML-Datei");
    return false;
}

// Hilfsfunktion, um require_once sicher zu verwenden
function require_once_safe($file) {
    if (!defined('FUNCTIONS_ONLY')) {
        define('FUNCTIONS_ONLY', true);
    }
    
    if (file_exists($file) && is_readable($file)) {
        require_once $file;
        return true;
    }
    return false;
}

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");
    
    // Sammle alle vorhandenen Test-Codes aus den XML-Dateien
    $resultsDir = __DIR__ . '/../../results';
    writeLog("Suche in Verzeichnis: " . $resultsDir);
    
    if (!is_dir($resultsDir)) {
        throw new Exception("Results-Verzeichnis nicht gefunden: " . $resultsDir);
    }
    
    // Scanne das results-Verzeichnis nach Testordnern
    $testFolders = [];
    $emptyFolders = [];
    
    // Sammle alle Test-Ordner
    if ($handle = opendir($resultsDir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && is_dir($resultsDir . '/' . $entry)) {
                $folderPath = $resultsDir . '/' . $entry;
                
                // Prüfe, ob der Ordner leer ist (keine XML-Dateien)
                $isEmpty = true;
                if ($innerHandle = opendir($folderPath)) {
                    while (false !== ($innerEntry = readdir($innerHandle))) {
                        if (pathinfo($innerEntry, PATHINFO_EXTENSION) === 'xml') {
                            $isEmpty = false;
                            break;
                        }
                    }
                    closedir($innerHandle);
                }
                
                if ($isEmpty) {
                    $emptyFolders[] = $entry;
                    writeLog("Leerer Ordner gefunden: " . $entry);
                } else {
                    // Extrahiere den Testcode vom Ordnernamen (z.B. "5S7_2025-03-18" => "5S7")
                    if (preg_match('/^([A-Z0-9]+)_/', $entry, $matches)) {
                        $testCode = $matches[1];
                        $testFolders[$testCode][] = $entry;
                    }
                }
            }
        }
        closedir($handle);
    }
    
    // Lösche leere Ordner
    foreach ($emptyFolders as $emptyFolder) {
        $folderPath = $resultsDir . '/' . $emptyFolder;
        if (rmdir($folderPath)) {
            writeLog("Leerer Ordner gelöscht: " . $emptyFolder);
        } else {
            writeLog("Konnte leeren Ordner nicht löschen: " . $emptyFolder);
        }
    }
    
    writeLog("Gefundene Test-Codes in results-Verzeichnis: " . implode(", ", array_keys($testFolders)));
    
    // Verarbeite alle Testordner und stelle sicher, dass für jeden Code ein Test existiert
    foreach ($testFolders as $code => $folders) {
        // Überprüfe, ob der Test bereits in der Datenbank existiert
        $testCheck = $db->prepare("SELECT test_id FROM tests WHERE access_code = ? ORDER BY created_at DESC LIMIT 1");
        $testCheck->execute([$code]);
        $testId = $testCheck->fetchColumn();
        
        // Debug: Überprüfe die test_id
        writeLog("Abfrage für Test mit Code $code ergab test_id: " . ($testId ? $testId : "keine"));
        
        if (!$testId) {
            // Test existiert nicht - rekonstruiere ihn aus einer XML-Datei
            writeLog("Test $code existiert nicht in der Datenbank - Rekonstruiere...");
            
            // Finde eine XML-Datei für diesen Test
            $xmlFile = null;
            foreach ($folders as $folder) {
                $folderPath = $resultsDir . '/' . $folder;
                if ($innerHandle = opendir($folderPath)) {
                    while (false !== ($innerEntry = readdir($innerHandle)) && !$xmlFile) {
                        if (is_file($folderPath . '/' . $innerEntry) && pathinfo($innerEntry, PATHINFO_EXTENSION) === 'xml') {
                            $xmlFile = $folderPath . '/' . $innerEntry;
                            break;
                        }
                    }
                    closedir($innerHandle);
                    if ($xmlFile) break;
                }
            }
            
            if (!$xmlFile) {
                writeLog("Keine XML-Datei für Code $code gefunden!");
                continue;
            }
            
            writeLog("Verwende XML-Datei zur Rekonstruktion: " . $xmlFile);
            
            try {
                $xml = simplexml_load_file($xmlFile);
                if ($xml === false) {
                    writeLog("Fehler beim Lesen der XML-Datei: " . $xmlFile);
                    continue;
                }
                
                // Extrahiere Testdaten
                $title = isset($xml->title) ? (string)$xml->title : "Test $code";
                $questionCount = isset($xml->questions->question) ? count($xml->questions->question) : 0;
                $answerCount = 0;
                $answerType = 'single';
                
                // Zähle Antworten und bestimme den Antworttyp
                $multipleCorrect = false;
                
                if (isset($xml->questions->question)) {
                    foreach ($xml->questions->question as $question) {
                        if (isset($question->answers->answer)) {
                            $answerCount += count($question->answers->answer);
                            
                            // Prüfe, ob es Multiple-Choice-Fragen gibt
                            $correctAnswers = 0;
                            foreach ($question->answers->answer as $answer) {
                                if (isset($answer->correct) && (int)$answer->correct === 1) {
                                    $correctAnswers++;
                                }
                            }
                            
                            if ($correctAnswers > 1) {
                                $multipleCorrect = true;
                            }
                        }
                    }
                }
                
                if ($multipleCorrect) {
                    $answerType = 'multiple';
                }
                
                // Extrahiere das Datum aus dem Ordnernamen
                $testDate = '';
                foreach ($folders as $folder) {
                    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $folder, $matches)) {
                        $testDate = $matches[1];
                        break;
                    }
                }
                
                // Erstelle Testdatensatz mit eindeutiger ID
                $testId = $code . '_' . uniqid();
            $createTest = $db->prepare("
                INSERT INTO tests (
                    test_id, 
                    access_code, 
                    title, 
                    question_count, 
                    answer_count, 
                        answer_type,
                        created_at
                ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                
                $testCreatedAt = $testDate ? $testDate . ' 00:00:00' : date('Y-m-d H:i:s');
                $createTest->execute([
                    $testId,
                    $code,
                    $title,
                    $questionCount,
                    $answerCount,
                    $answerType,
                    $testCreatedAt
                ]);
                
                writeLog("Test $code erfolgreich rekonstruiert: ID=$testId, Titel=$title, Fragen=$questionCount, Antworten=$answerCount");
                
                // Überprüfe, ob der Test wirklich angelegt wurde
                $verifyTest = $db->prepare("SELECT test_id FROM tests WHERE test_id = ?");
                $verifyTest->execute([$testId]);
                if ($verifyTest->fetchColumn()) {
                    writeLog("Test $code mit ID=$testId wurde erfolgreich in der Datenbank angelegt.");
                } else {
                    writeLog("FEHLER: Test $code mit ID=$testId konnte nicht in der Datenbank gefunden werden!");
                }
            } catch (Exception $e) {
                writeLog("Fehler bei der Rekonstruktion des Tests $code: " . $e->getMessage());
                continue;
            }
        } else {
            writeLog("Test $code existiert bereits: ID=$testId");
        }
        
        // Für jeden Testordner alle XML-Dateien verarbeiten
        foreach ($folders as $folder) {
            $folderPath = $resultsDir . '/' . $folder;
            processTestFolder($db, $folderPath, $code, $testId);
        }
    }
    
    // Nachdem alle Tests und Testversuche verarbeitet wurden, suche nach Testversuchen mit points_maximum = 0
    // und werte diese neu aus
    writeLog("Suche nach Testversuchen mit fehlenden oder ungültigen Punktzahlen...");
    
    $zeroPointsQuery = $db->query("
        SELECT ta.attempt_id, ta.xml_file_path, ta.test_id, t.access_code
        FROM test_attempts ta
        JOIN tests t ON ta.test_id = t.test_id
        WHERE ta.points_maximum = 0 OR ta.points_achieved = 0 OR ta.percentage = 0
    ");
    
    $zeroPointAttempts = $zeroPointsQuery->fetchAll(PDO::FETCH_ASSOC);
    $reevaluatedCount = 0;
    
    writeLog("Gefunden: " . count($zeroPointAttempts) . " Testversuche mit fehlenden oder ungültigen Punktzahlen");
    
    $updateStmt = $db->prepare("
        UPDATE test_attempts 
        SET points_achieved = ?, points_maximum = ?, percentage = ?, grade = ? 
        WHERE attempt_id = ?
    ");
    
    foreach ($zeroPointAttempts as $attempt) {
        $xmlPath = $attempt['xml_file_path'];
        $fullPath = __DIR__ . '/../../' . $xmlPath;
        $attemptId = $attempt['attempt_id'];
        $testId = $attempt['test_id'];
        $accessCode = $attempt['access_code'];
        
        writeLog("Werte Testversuch ID=$attemptId, TestID=$testId, Code=$accessCode neu aus: $xmlPath");
        
        if (file_exists($fullPath)) {
            try {
                $evaluationResults = performEvaluation($fullPath);
                
                if ($evaluationResults !== false) {
                    $pointsAchieved = $evaluationResults['achieved'];
                    $pointsMaximum = $evaluationResults['maximum'];
                    $percentage = $evaluationResults['percentage'];
                    $grade = $evaluationResults['grade'];
                    
                    writeLog("Neue Werte: Punkte=$pointsAchieved/$pointsMaximum, Prozent=$percentage%, Note=$grade");
                    
                    $updateStmt->execute([
                        $pointsAchieved,
                        $pointsMaximum,
                        $percentage,
                        $grade,
                        $attemptId
                    ]);
                    
                    $reevaluatedCount++;
                    writeLog("Testversuch ID=$attemptId erfolgreich neu ausgewertet");
                } else {
                    writeLog("Fehler bei der Auswertung der XML-Datei: $xmlPath");
                }
            } catch (Exception $e) {
                writeLog("Fehler bei der Neuauswertung: " . $e->getMessage());
            }
        } else {
            writeLog("XML-Datei nicht gefunden: $fullPath");
        }
    }
    
    writeLog("Insgesamt $reevaluatedCount Testversuche wurden neu ausgewertet");
    
    // Sammle Statistiken
    $stats = [
        'added' => 0,
        'deleted' => count($emptyFolders),
        'updated' => $reevaluatedCount,
        'total' => 0
    ];

    // JSON-Antwort vorbereiten und zurückgeben
    echo json_encode([
        'success' => true,
        'added' => $stats['added'],
        'deleted' => $stats['deleted'],
        'updated' => $stats['updated'],
        'total' => $stats['total']
    ]);
} catch (Exception $e) {
    // Fehlermeldung senden
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    echo json_encode($response);
    writeLog("Fehler bei der Synchronisation: " . $e->getMessage());
}

/**
 * Verarbeitet einen Testordner und fügt alle XML-Dateien als Testversuche hinzu
 * 
 * @param PDO $db Die Datenbankverbindung
 * @param string $folderPath Der Pfad zum Testordner
 * @param string $code Der Testcode
 * @param string $testId Die Test-ID in der Datenbank
 * @return array Statistiken über hinzugefügte und aktualisierte Einträge
 */
function processTestFolder($db, $folderPath, $code, $testId) {
    $added = 0;
    $updated = 0;
    $stats = [];
    
    // Protokolliere die test_id für die Nachverfolgung
    writeLog("Verarbeite Testordner: $folderPath (Code: $code, TestID: $testId)");
    
    // Double-check: Stelle sicher, dass die test_id existiert und gültig ist
    if (empty($testId)) {
        writeLog("FEHLER: Keine test_id für Code $code gefunden. Testversuche können nicht hinzugefügt werden.");
        return ['added' => 0, 'updated' => 0];
    }
    
    // Verifiziere, dass die test_id in der Datenbank existiert
    $verifyTest = $db->prepare("SELECT COUNT(*) FROM tests WHERE test_id = ?");
    $verifyTest->execute([$testId]);
    if ($verifyTest->fetchColumn() == 0) {
        writeLog("KRITISCHER FEHLER: Die test_id $testId existiert nicht in der Datenbank! Kann Testversuche nicht hinzufügen.");
        return ['added' => 0, 'updated' => 0];
    }
    
    // Relativer Pfad für die Datenbank
    $relativeFolder = basename($folderPath);
    
    // Sammle alle XML-Dateien im Ordner
    $xmlFiles = [];
    if ($handle = opendir($folderPath)) {
        while (false !== ($entry = readdir($handle))) {
            if (is_file($folderPath . '/' . $entry) && pathinfo($entry, PATHINFO_EXTENSION) === 'xml') {
                $xmlFiles[] = $entry;
            }
        }
        closedir($handle);
    }
    
    writeLog("Gefundene XML-Dateien: " . count($xmlFiles));
    
    // Prüfe für jede XML-Datei, ob sie bereits in der Datenbank ist
    $insertStmt = $db->prepare("
        INSERT INTO test_attempts (
            test_id, 
            student_name, 
            completed_at, 
            xml_file_path,
            points_achieved,
            points_maximum,
            percentage,
            grade
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $updateStmt = $db->prepare("
        UPDATE test_attempts 
        SET points_achieved = ?, points_maximum = ?, percentage = ?, grade = ? 
        WHERE attempt_id = ?
    ");
    
    foreach ($xmlFiles as $file) {
        $fullPath = $folderPath . '/' . $file;
        $relativePath = 'results/' . $relativeFolder . '/' . $file;
        
        // Prüfe, ob die Datei bereits in der Datenbank ist
        $checkStmt = $db->prepare("SELECT attempt_id FROM test_attempts WHERE xml_file_path = ?");
        $checkStmt->execute([$relativePath]);
        $attemptId = $checkStmt->fetchColumn();
        
        // Extrahiere studentName und timestamp aus dem Dateinamen
        if (preg_match('/^' . preg_quote($code) . '_(.+?)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.xml$/', $file, $matches)) {
            $studentName = $matches[1];
            $timestamp = str_replace('_', ' ', $matches[2]);
            
            // Evaluiere den Test
            $evaluationResults = performEvaluation($fullPath);
            
            if ($evaluationResults !== false) {
                $pointsAchieved = $evaluationResults['achieved'];
                $pointsMaximum = $evaluationResults['maximum'];
                $percentage = $evaluationResults['percentage'];
                $grade = $evaluationResults['grade'];
                
                if (!$attemptId) {
                    // Füge einen neuen Testversuch hinzu
                    writeLog("Füge neuen Testversuch hinzu: Student=$studentName, Datei=$file, TestID=$testId");
                    
                    try {
                        $insertStmt->execute([
                            $testId,
                            $studentName,
                            $timestamp,
                            $relativePath,
                            $pointsAchieved,
                            $pointsMaximum,
                            $percentage,
                            $grade
                        ]);
                        
                        // Prüfen, ob der Eintrag wirklich hinzugefügt wurde
                        $newAttemptId = $db->lastInsertId();
                        if ($newAttemptId) {
                            $added++;
                            writeLog("Testversuch erfolgreich hinzugefügt: $file, ID=$newAttemptId");
                        } else {
                            writeLog("FEHLER: Testversuch konnte nicht hinzugefügt werden: " . print_r($insertStmt->errorInfo(), true));
                        }
                    } catch (Exception $e) {
                        writeLog("FEHLER beim Hinzufügen des Testversuchs: " . $e->getMessage());
                    }
                } else {
                    // Aktualisiere bestehenden Testversuch, falls Punkte/Note fehlen
                    $checkValuesStmt = $db->prepare("
                        SELECT points_achieved, points_maximum, percentage, grade, test_id 
                        FROM test_attempts 
                        WHERE attempt_id = ?
                    ");
                    $checkValuesStmt->execute([$attemptId]);
                    $values = $checkValuesStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Prüfe auch, ob die test_id korrekt ist
                    if ($values['test_id'] != $testId) {
                        writeLog("WARNUNG: Testversuch ID=$attemptId hat falsche test_id=" . $values['test_id'] . ", sollte sein: $testId");
                        
                        // Korrigiere die test_id
                        $fixTestIdStmt = $db->prepare("UPDATE test_attempts SET test_id = ? WHERE attempt_id = ?");
                        $fixTestIdStmt->execute([$testId, $attemptId]);
                        writeLog("Test-ID wurde korrigiert für Versuch ID=$attemptId");
                    }
                    
                    // Aktualisiere Punkte und Note, falls sie fehlen
                    if ($values['points_achieved'] == 0 || $values['points_maximum'] == 0 || 
                        $values['percentage'] == 0 || empty($values['grade'])) {
                        writeLog("Aktualisiere Testversuch ID=$attemptId mit korrekten Werten");
                        
                        $updateStmt->execute([
                        $pointsAchieved,
                        $pointsMaximum,
                        $percentage,
                            $grade,
                            $attemptId
                        ]);
                        
                        $updated++;
                        writeLog("Testversuch aktualisiert: ID=$attemptId");
                    }
                }
            } else {
                writeLog("FEHLER: Konnte Test nicht auswerten: $file");
            }
        } else {
            writeLog("Ungültiges Dateinamensformat: $file");
        }
    }
    
    $stats['added'] = $added;
    $stats['updated'] = $updated;
    
    writeLog("Ordner $folderPath verarbeitet: $added hinzugefügt, $updated aktualisiert");
    return $stats;
} 