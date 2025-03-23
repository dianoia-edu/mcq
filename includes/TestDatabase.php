<?php
require_once __DIR__ . '/database_config.php';

class TestDatabase {
    private $db;
    
    public function __construct() {
        $dbConfig = DatabaseConfig::getInstance();
        $this->db = $dbConfig->getConnection();
    }
    
    public function saveTestAttempt($testData) {
        try {
            // Prüfe ob der Test bereits in der tests-Tabelle existiert
            $testId = $this->getOrCreateTest($testData);
            
            // Erweiterte Prüfung auf doppelte Einträge:
            // 1. Prüfung anhand von test_id, student_name und Datum (heutigem Tag)
            $today = date('Y-m-d');
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) as attempt_count, attempt_id 
                FROM test_attempts 
                WHERE test_id = :test_id 
                AND student_name = :student_name 
                AND DATE(completed_at) = :today
            ");
            
            $checkStmt->execute([
                'test_id' => $testId,
                'student_name' => $testData['student_name'],
                'today' => $today
            ]);
            
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Wenn bereits ein Eintrag existiert, protokolliere dies und breche ab
            if ($result['attempt_count'] > 0) {
                error_log("Testversuch für heute existiert bereits: Test ID {$testId}, Student {$testData['student_name']}, Datum {$today}, Attempt ID: {$result['attempt_id']}");
                return false; // Kein neuer Eintrag erstellt
            }
            
            // 2. Prüfung anhand des Dateipfads (falls vorhanden)
            if (!empty($testData['xml_file_path'])) {
                // Normalisiere den Pfad, um absolute und relative Pfade korrekt zu vergleichen
                $normalizedPath = str_replace('\\', '/', $testData['xml_file_path']);
                $relativeBasisPfad = basename(dirname($normalizedPath)) . '/' . basename($normalizedPath);
                
                $checkFilePathStmt = $this->db->prepare("
                    SELECT COUNT(*) as path_count 
                    FROM test_attempts 
                    WHERE xml_file_path LIKE ?
                ");
                
                $checkFilePathStmt->execute([
                    '%' . $relativeBasisPfad
                ]);
                
                $filePathResult = $checkFilePathStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($filePathResult['path_count'] > 0) {
                    error_log("Testversuch mit diesem Dateipfad existiert bereits (Basis: {$relativeBasisPfad})");
                    return false; // Kein neuer Eintrag erstellt
                }
            }
            
            // Timestamp für Logging
            $timestamp = date('Y-m-d H:i:s');
            
            // Alles OK, speichere den Testversuch
            error_log("[{$timestamp}] Speichere neuen Testversuch: Test ID {$testId}, Student {$testData['student_name']}, Punkte {$testData['points_achieved']}/{$testData['points_maximum']}");
            
            // Speichere den Testversuch
            $stmt = $this->db->prepare("
                INSERT INTO test_attempts (
                    test_id, 
                    student_name, 
                    xml_file_path, 
                    points_achieved, 
                    points_maximum, 
                    percentage, 
                    grade,
                    started_at,
                    completed_at
                ) VALUES (
                    :test_id,
                    :student_name,
                    :xml_file_path,
                    :points_achieved,
                    :points_maximum,
                    :percentage,
                    :grade,
                    :started_at,
                    NOW()
                )
            ");
            
            $stmt->execute([
                'test_id' => $testId,
                'student_name' => $testData['student_name'],
                'xml_file_path' => $testData['xml_file_path'],
                'points_achieved' => $testData['points_achieved'],
                'points_maximum' => $testData['points_maximum'],
                'percentage' => $testData['percentage'],
                'grade' => $testData['grade'],
                'started_at' => $testData['started_at']
            ]);
            
            $newAttemptId = $this->db->lastInsertId();
            error_log("[{$timestamp}] Testversuch erfolgreich gespeichert mit ID: {$newAttemptId}");
            
            // Aktualisiere die Teststatistiken
            $this->updateTestStatistics($testId);
            
            return true;
        } catch (PDOException $e) {
            error_log("Fehler beim Speichern des Testversuchs: " . $e->getMessage());
            throw new Exception("Der Testversuch konnte nicht gespeichert werden.");
        }
    }
    
    private function getOrCreateTest($testData) {
        try {
            // Prüfe ob der Test bereits existiert
            $stmt = $this->db->prepare("
                SELECT test_id FROM tests 
                WHERE access_code = :access_code
            ");
            $stmt->execute(['access_code' => $testData['access_code']]);
            
            if ($row = $stmt->fetch()) {
                return $row['test_id'];
            }
            
            // Wenn nicht, erstelle einen neuen Test
            $testId = uniqid('test_', true);
            $stmt = $this->db->prepare("
                INSERT INTO tests (
                    test_id,
                    access_code,
                    title,
                    question_count,
                    answer_count,
                    answer_type
                ) VALUES (
                    :test_id,
                    :access_code,
                    :title,
                    :question_count,
                    :answer_count,
                    :answer_type
                )
            ");
            
            $stmt->execute([
                'test_id' => $testId,
                'access_code' => $testData['access_code'],
                'title' => $testData['title'],
                'question_count' => $testData['question_count'],
                'answer_count' => $testData['answer_count'],
                'answer_type' => $testData['answer_type']
            ]);
            
            // Initialisiere die Teststatistiken
            $this->initializeTestStatistics($testId);
            
            return $testId;
        } catch (PDOException $e) {
            error_log("Fehler beim Erstellen/Abrufen des Tests: " . $e->getMessage());
            throw new Exception("Der Test konnte nicht erstellt/abgerufen werden.");
        }
    }
    
    private function initializeTestStatistics($testId) {
        $stmt = $this->db->prepare("
            INSERT INTO test_statistics (
                test_id,
                attempts_count,
                average_percentage,
                average_duration
            ) VALUES (
                :test_id,
                0,
                0,
                0
            )
        ");
        $stmt->execute(['test_id' => $testId]);
    }
    
    private function updateTestStatistics($testId) {
        try {
            // Berechne neue Statistiken
            $stmt = $this->db->prepare("
                UPDATE test_statistics SET
                    attempts_count = (
                        SELECT COUNT(*) 
                        FROM test_attempts 
                        WHERE test_id = :test_id
                    ),
                    average_percentage = (
                        SELECT AVG(percentage) 
                        FROM test_attempts 
                        WHERE test_id = :test_id
                    ),
                    average_duration = (
                        SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at))
                        FROM test_attempts 
                        WHERE test_id = :test_id
                    ),
                    last_attempt_at = NOW()
                WHERE test_id = :test_id
            ");
            $stmt->execute(['test_id' => $testId]);
        } catch (PDOException $e) {
            error_log("Fehler beim Aktualisieren der Teststatistiken: " . $e->getMessage());
            // Kein throw hier, da dies ein nicht-kritischer Fehler ist
        }
    }
} 