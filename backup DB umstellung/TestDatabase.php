<?php
require_once __DIR__ . '/../config/database_config.php';

class TestDatabase {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    /**
     * Speichert einen neuen Test in der Datenbank
     */
    public function saveTest($testId, $accessCode, $title, $questionCount, $answerCount, $answerType) {
        $sql = "INSERT INTO tests (test_id, access_code, title, question_count, answer_count, answer_type) 
                VALUES (:test_id, :access_code, :title, :question_count, :answer_count, :answer_type)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':test_id' => $testId,
            ':access_code' => $accessCode,
            ':title' => $title,
            ':question_count' => $questionCount,
            ':answer_count' => $answerCount,
            ':answer_type' => $answerType
        ]);
    }
    
    /**
     * Speichert einen Testversuch in der Datenbank
     */
    public function saveTestAttempt($testId, $studentName, $xmlFilePath, $results) {
        try {
            $this->db->beginTransaction();
            
            // Speichere den Testversuch
            $sql = "INSERT INTO test_attempts (
                        test_id, student_name, xml_file_path, 
                        points_achieved, points_maximum, percentage, grade,
                        started_at, completed_at
                    ) VALUES (
                        :test_id, :student_name, :xml_file_path,
                        :points_achieved, :points_maximum, :percentage, :grade,
                        :started_at, :completed_at
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':test_id' => $testId,
                ':student_name' => $studentName,
                ':xml_file_path' => $xmlFilePath,
                ':points_achieved' => $results['achieved'],
                ':points_maximum' => $results['max'],
                ':percentage' => $results['percentage'],
                ':grade' => $results['grade'],
                ':started_at' => $results['started_at'],
                ':completed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Aktualisiere die Teststatistik
            $this->updateTestStatistics($testId);
            
            // Speichere den täglichen Versuch
            $this->saveDailyAttempt($testId, $studentName);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Fehler beim Speichern des Testversuchs: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Aktualisiert die Teststatistik
     */
    private function updateTestStatistics($testId) {
        $sql = "INSERT INTO test_statistics (
                    test_id, attempts_count, average_percentage, average_duration, last_attempt_at
                ) 
                SELECT 
                    test_id,
                    COUNT(*) as attempts_count,
                    AVG(percentage) as average_percentage,
                    AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as average_duration,
                    MAX(completed_at) as last_attempt_at
                FROM test_attempts 
                WHERE test_id = :test_id
                GROUP BY test_id
                ON DUPLICATE KEY UPDATE
                    attempts_count = VALUES(attempts_count),
                    average_percentage = VALUES(average_percentage),
                    average_duration = VALUES(average_duration),
                    last_attempt_at = VALUES(last_attempt_at)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':test_id' => $testId]);
    }
    
    /**
     * Speichert einen täglichen Testversuch
     */
    private function saveDailyAttempt($testId, $studentName) {
        $sql = "INSERT INTO daily_attempts (test_id, student_identifier, attempt_date)
                VALUES (:test_id, :student_identifier, CURDATE())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':test_id' => $testId,
            ':student_identifier' => hash('sha256', $studentName . $_SERVER['REMOTE_ADDR'])
        ]);
    }
    
    /**
     * Prüft, ob ein Test heute bereits absolviert wurde
     */
    public function hasCompletedTestToday($testId, $studentIdentifier) {
        $sql = "SELECT COUNT(*) as count FROM daily_attempts 
                WHERE test_id = :test_id 
                AND student_identifier = :student_identifier 
                AND attempt_date = CURDATE()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':test_id' => $testId,
            ':student_identifier' => $studentIdentifier
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Holt einen Test anhand des Zugangscodes
     */
    public function getTestByAccessCode($accessCode) {
        $sql = "SELECT * FROM tests WHERE access_code = :access_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':access_code' => $accessCode]);
        return $stmt->fetch();
    }
    
    /**
     * Holt alle Testergebnisse für einen Test
     */
    public function getTestResults($testId) {
        $sql = "SELECT 
                    ta.*,
                    t.title as test_title,
                    t.access_code
                FROM test_attempts ta
                JOIN tests t ON ta.test_id = t.test_id
                WHERE ta.test_id = :test_id
                ORDER BY ta.completed_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':test_id' => $testId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Holt die Statistik für einen Test
     */
    public function getTestStatistics($testId) {
        $sql = "SELECT * FROM test_statistics WHERE test_id = :test_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':test_id' => $testId]);
        return $stmt->fetch();
    }
} 