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