<?php
require_once __DIR__ . '/TestDatabase.php';
require_once __DIR__ . '/../config/database_config.php';

class DataMigration {
    private $db;
    private $testDb;
    private $testsDir;
    private $resultsDir;
    
    public function __construct() {
        $this->db = DatabaseConfig::getInstance()->getConnection();
        $this->testDb = new TestDatabase();
        $this->testsDir = __DIR__ . '/../../tests/';
        $this->resultsDir = __DIR__ . '/../../results/';
    }
    
    public function migrateAllData() {
        try {
            // Migriere zuerst alle Tests
            $this->migrateTests();
            
            // Dann migriere alle Testergebnisse
            $this->migrateTestResults();
            
            echo "Migration erfolgreich abgeschlossen.\n";
            
        } catch (Exception $e) {
            error_log("Fehler bei der Migration: " . $e->getMessage());
            echo "Fehler bei der Migration. Bitte prüfen Sie die Logs.\n";
            throw $e;
        }
    }
    
    private function migrateTests() {
        echo "Migriere Tests...\n";
        
        // Hole alle XML-Dateien im tests-Verzeichnis
        $testFiles = glob($this->testsDir . '*.xml');
        
        foreach ($testFiles as $file) {
            $xml = simplexml_load_file($file);
            if ($xml === false) {
                error_log("Konnte XML-Datei nicht laden: " . $file);
                continue;
            }
            
            try {
                $this->db->beginTransaction();
                
                // Extrahiere die notwendigen Informationen
                $accessCode = (string)$xml->access_code;
                $title = (string)$xml->title;
                $questionCount = count($xml->xpath('//question'));
                $answerCount = count($xml->xpath('//question[1]/answers/answer'));
                $answerType = $this->determineAnswerType($xml);
                
                // Prüfe ob der Test bereits existiert
                $stmt = $this->db->prepare("SELECT test_id FROM tests WHERE access_code = ?");
                $stmt->execute([$accessCode]);
                $existingTest = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingTest) {
                    $this->db->rollBack();
                    continue;
                }
                
                // Generiere eine eindeutige Test-ID
                $testId = 'test_' . uniqid();
                
                // Speichere den Test
                $sql = "INSERT INTO tests (test_id, access_code, title, question_count, answer_count, answer_type) 
                        VALUES (:test_id, :access_code, :title, :question_count, :answer_count, :answer_type)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':test_id' => $testId,
                    ':access_code' => $accessCode,
                    ':title' => $title,
                    ':question_count' => $questionCount,
                    ':answer_count' => $answerCount,
                    ':answer_type' => $answerType
                ]);
                
                $this->db->commit();
                echo "Test migriert: " . $title . "\n";
                
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log("Fehler beim Migrieren des Tests: " . $file . "\n" . $e->getMessage());
            }
        }
    }
    
    private function migrateTestResults() {
        echo "Migriere Testergebnisse...\n";
        
        // Durchsuche alle Unterordner im results-Verzeichnis
        $resultFolders = glob($this->resultsDir . '*_*', GLOB_ONLYDIR);
        
        foreach ($resultFolders as $folder) {
            $folderName = basename($folder);
            $parts = explode('_', $folderName);
            $accessCode = $parts[0];
            
            // Hole den zugehörigen Test
            $stmt = $this->db->prepare("SELECT test_id FROM tests WHERE access_code = ? LIMIT 1");
            $stmt->execute([$accessCode]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) {
                echo "Kein Test gefunden für Zugangscode: " . $accessCode . "\n";
                continue;
            }
            
            // Verarbeite alle XML-Dateien im Ordner
            $xmlFiles = glob($folder . '/*.xml');
            foreach ($xmlFiles as $file) {
                try {
                    $xml = simplexml_load_file($file);
                    if ($xml === false) {
                        error_log("Konnte XML-Datei nicht laden: " . $file);
                        continue;
                    }
                    
                    $this->db->beginTransaction();
                    
                    // Prüfe ob dieser Versuch bereits existiert
                    $stmt = $this->db->prepare("SELECT attempt_id FROM test_attempts WHERE xml_file_path = ?");
                    $relativePath = str_replace(__DIR__ . '/../../', '', $file);
                    $stmt->execute([$relativePath]);
                    if ($stmt->fetch()) {
                        $this->db->rollBack();
                        continue;
                    }
                    
                    // Extrahiere die Informationen aus der XML-Datei
                    $studentName = (string)$xml->schuelername;
                    $points = $this->calculatePoints($xml);
                    
                    // Speichere das Ergebnis in der Datenbank
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
                        ':test_id' => $test['test_id'],
                        ':student_name' => $studentName,
                        ':xml_file_path' => $relativePath,
                        ':points_achieved' => $points['achieved'],
                        ':points_maximum' => $points['max'],
                        ':percentage' => $points['percentage'],
                        ':grade' => $this->calculateGrade($points['percentage']),
                        ':started_at' => date('Y-m-d H:i:s', filemtime($file) - 3600),
                        ':completed_at' => date('Y-m-d H:i:s', filemtime($file))
                    ]);
                    
                    // Aktualisiere die Teststatistik
                    $this->updateTestStatistics($test['test_id']);
                    
                    $this->db->commit();
                    echo "Testergebnis migriert: " . basename($file) . "\n";
                    
                } catch (Exception $e) {
                    $this->db->rollBack();
                    error_log("Fehler beim Migrieren des Testergebnisses: " . $file . "\n" . $e->getMessage());
                    echo "Fehler beim Migrieren des Testergebnisses: " . $file . "\n" . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    private function calculatePoints($xml) {
        $achieved = 0;
        $max = 0;
        
        foreach ($xml->questions->question as $question) {
            $questionMax = 0;
            $questionAchieved = 0;
            
            foreach ($question->answers->answer as $answer) {
                if ((int)$answer->correct === 1) {
                    $questionMax++;
                    if ((int)$answer->schuelerantwort === 1) {
                        $questionAchieved++;
                    }
                } elseif ((int)$answer->schuelerantwort === 1) {
                    $questionAchieved--;
                }
            }
            
            $max += $questionMax;
            $achieved += max(0, $questionAchieved);
        }
        
        return [
            'achieved' => $achieved,
            'max' => $max,
            'percentage' => $max > 0 ? round(($achieved / $max) * 100, 2) : 0
        ];
    }
    
    private function calculateGrade($percentage) {
        if ($percentage >= 92) return '1';
        if ($percentage >= 81) return '2';
        if ($percentage >= 67) return '3';
        if ($percentage >= 50) return '4';
        if ($percentage >= 30) return '5';
        return '6';
    }
    
    private function determineAnswerType($xml) {
        $hasMultiple = false;
        $hasSingle = false;
        
        foreach ($xml->questions->question as $question) {
            $correctCount = 0;
            foreach ($question->answers->answer as $answer) {
                if ((int)$answer->correct === 1) {
                    $correctCount++;
                }
            }
            
            if ($correctCount > 1) {
                $hasMultiple = true;
            } else {
                $hasSingle = true;
            }
        }
        
        if ($hasMultiple && $hasSingle) {
            return 'mixed';
        } elseif ($hasMultiple) {
            return 'multiple';
        } else {
            return 'single';
        }
    }
    
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
        $stmt->execute([':test_id' => $testId]);
    }
}

// Führe die Migration durch
try {
    $migration = new DataMigration();
    $migration->migrateAllData();
} catch (Exception $e) {
    exit(1);
} 