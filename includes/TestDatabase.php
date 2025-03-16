<?php
require_once __DIR__ . '/database_config.php';

class TestDatabase {
    private $db;
    
    public function __construct() {
        $dbConfig = DatabaseConfig::getInstance();
        $this->db = $dbConfig->getConnection();
    }
    
    /**
     * Speichert einen Testversuch in der Datenbank
     * 
     * @param string $testCode Der Zugangscode des Tests
     * @param string $studentName Der Name des Schülers
     * @param string $xmlFilePath Der Pfad zur XML-Datei mit den Ergebnissen
     * @param int $pointsAchieved Die erreichten Punkte
     * @param int $pointsMaximum Die maximale Punktzahl
     * @param float $percentage Der Prozentsatz der erreichten Punkte
     * @param string $grade Die Note
     * @param string $startedAt Der Zeitpunkt, zu dem der Test begonnen wurde
     * @return bool True bei Erfolg, False bei Fehler
     */
    public function saveTestAttempt($testCode, $studentName, $xmlFilePath, $pointsAchieved, $pointsMaximum, $percentage, $grade, $startedAt) {
        try {
            // Finde die Test-ID anhand des Zugangscodes
            $stmt = $this->db->prepare("SELECT test_id FROM tests WHERE access_code = :access_code");
            $stmt->execute(['access_code' => $testCode]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) {
                error_log("Test mit Zugangscode $testCode nicht gefunden. Erstelle neuen Test.");
                // Erstelle einen neuen Test, wenn er nicht existiert
                $testId = $this->createTest($testCode, "Automatisch erstellter Test", 0, 0, "multiple_choice");
            } else {
                $testId = $test['test_id'];
            }
            
            // Speichere den Testversuch
            $stmt = $this->db->prepare("
                INSERT INTO test_attempts (
                    test_id, student_name, xml_file_path, 
                    points_achieved, points_maximum, percentage, grade,
                    started_at, completed_at
                ) VALUES (
                    :test_id, :student_name, :xml_file_path,
                    :points_achieved, :points_maximum, :percentage, :grade,
                    :started_at, NOW()
                )
            ");
            
            $result = $stmt->execute([
                'test_id' => $testId,
                'student_name' => $studentName,
                'xml_file_path' => $xmlFilePath,
                'points_achieved' => $pointsAchieved,
                'points_maximum' => $pointsMaximum,
                'percentage' => $percentage,
                'grade' => $grade,
                'started_at' => $startedAt
            ]);
            
            if ($result) {
                // Aktualisiere die Teststatistiken
                $this->updateTestStatistics($testId);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Fehler beim Speichern des Testversuchs: " . $e->getMessage());
            return false;
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
            // Berechne die durchschnittliche Prozentzahl und Dauer
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as attempts_count,
                    AVG(percentage) as average_percentage,
                    MAX(completed_at) as last_attempt_at
                FROM test_attempts
                WHERE test_id = :test_id
            ");
            $stmt->execute(['test_id' => $testId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Aktualisiere die Statistiken
            $updateStmt = $this->db->prepare("
                UPDATE test_statistics
                SET 
                    attempts_count = :attempts_count,
                    average_percentage = :average_percentage,
                    last_attempt_at = :last_attempt_at
                WHERE test_id = :test_id
            ");
            
            $updateStmt->execute([
                'attempts_count' => $stats['attempts_count'],
                'average_percentage' => $stats['average_percentage'],
                'last_attempt_at' => $stats['last_attempt_at'],
                'test_id' => $testId
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Fehler beim Aktualisieren der Teststatistiken: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lädt das neueste Testergebnis für einen bestimmten Test und Schüler
     * 
     * @param string $testCode Der Zugangscode des Tests
     * @param string $studentName Der Name des Schülers
     * @return array|false Die Testergebnisse oder false bei Fehler
     */
    public function getLatestTestResult($testCode, $studentName) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ta.points_achieved,
                    ta.points_maximum,
                    ta.percentage,
                    ta.grade,
                    ta.completed_at
                FROM test_attempts ta
                JOIN tests t ON ta.test_id = t.test_id
                WHERE t.access_code = :access_code
                AND ta.student_name = :student_name
                ORDER BY ta.completed_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([
                'access_code' => $testCode,
                'student_name' => $studentName
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log("Testergebnis gefunden für " . $studentName . " (Test: " . $testCode . ")");
                return $result;
            } else {
                error_log("Kein Testergebnis gefunden für " . $studentName . " (Test: " . $testCode . ")");
                return false;
            }
        } catch (Exception $e) {
            error_log("Fehler beim Laden des Testergebnisses: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Erstellt einen neuen Test in der Datenbank
     * 
     * @param string $accessCode Der Zugangscode des Tests
     * @param string $title Der Titel des Tests
     * @param int $questionCount Die Anzahl der Fragen
     * @param int $answerCount Die Anzahl der Antwortmöglichkeiten
     * @param string $answerType Der Typ der Antworten (multiple_choice, text, etc.)
     * @return int|false Die ID des erstellten Tests oder false bei Fehler
     */
    public function createTest($accessCode, $title, $questionCount, $answerCount, $answerType) {
        try {
            // Erstelle den Test
            $stmt = $this->db->prepare("
                INSERT INTO tests (
                    access_code, title, question_count, answer_count, answer_type, created_at
                ) VALUES (
                    :access_code, :title, :question_count, :answer_count, :answer_type, NOW()
                )
            ");
            
            $stmt->execute([
                'access_code' => $accessCode,
                'title' => $title,
                'question_count' => $questionCount,
                'answer_count' => $answerCount,
                'answer_type' => $answerType
            ]);
            
            $testId = $this->db->lastInsertId();
            
            // Erstelle die Teststatistiken
            $statsStmt = $this->db->prepare("
                INSERT INTO test_statistics (
                    test_id, attempts_count, average_percentage, created_at
                ) VALUES (
                    :test_id, 0, 0, NOW()
                )
            ");
            
            $statsStmt->execute(['test_id' => $testId]);
            
            error_log("Neuer Test erstellt mit ID: $testId und Zugangscode: $accessCode");
            return $testId;
        } catch (Exception $e) {
            error_log("Fehler beim Erstellen des Tests: " . $e->getMessage());
            return false;
        }
    }
} 