<?php
/**
 * Datenbankmigrationsscript für Webserver-Deployment
 * Dieses Script migriert die bestehenden Daten zur neuen Datenbankstruktur
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sicherheitscheck - nur für Admin-Benutzer
session_start();
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'migrate_db_2024') {
    die('Zugriff verweigert. Admin-Schlüssel erforderlich.');
}

require_once __DIR__ . '/includes/database_config.php';

class DatabaseMigrator {
    private $db;
    private $testsDir;
    private $resultsDir;
    
    public function __construct() {
        $dbConfig = DatabaseConfig::getInstance();
        
        // Erstelle zuerst die Datenbank
        $dbConfig->createDatabase();
        
        // Initialisiere die Tabellen
        $dbConfig->initializeTables();
        
        // Hole die Verbindung
        $this->db = $dbConfig->getConnection();
        $this->testsDir = __DIR__ . '/tests/';
        $this->resultsDir = __DIR__ . '/results/';
        
        echo "<h2>Datenbankinitialisierung abgeschlossen</h2>\n";
    }
    
    public function migrateAllData() {
        echo "<h2>Starte Datenmigration...</h2>\n";
        
        try {
            // Migriere zuerst alle Tests
            $this->migrateTests();
            
            // Dann migriere alle Testergebnisse
            $this->migrateTestResults();
            
            echo "<p style='color: green;'><strong>Migration erfolgreich abgeschlossen!</strong></p>\n";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'><strong>Fehler bei der Migration:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
            error_log("Fehler bei der Migration: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function migrateTests() {
        echo "<h3>Migriere Tests...</h3>\n";
        
        // Hole alle XML-Dateien im tests-Verzeichnis
        $testFiles = glob($this->testsDir . '*.xml');
        $migratedCount = 0;
        
        foreach ($testFiles as $file) {
            $xml = simplexml_load_file($file);
            if ($xml === false) {
                echo "<p style='color: orange;'>Warnung: Konnte XML-Datei nicht laden: " . basename($file) . "</p>\n";
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
                    echo "<p>Test bereits vorhanden: $title (Code: $accessCode)</p>\n";
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
                $migratedCount++;
                echo "<p style='color: green;'>✓ Test migriert: $title (Code: $accessCode)</p>\n";
                
            } catch (Exception $e) {
                $this->db->rollBack();
                echo "<p style='color: red;'>✗ Fehler beim Migrieren des Tests: " . basename($file) . " - " . htmlspecialchars($e->getMessage()) . "</p>\n";
                error_log("Fehler beim Migrieren des Tests: " . $file . "\n" . $e->getMessage());
            }
        }
        
        echo "<p><strong>$migratedCount Tests erfolgreich migriert.</strong></p>\n";
    }
    
    private function migrateTestResults() {
        echo "<h3>Migriere Testergebnisse...</h3>\n";
        
        // Durchsuche alle Unterordner im results-Verzeichnis
        $resultFolders = glob($this->resultsDir . '*_*', GLOB_ONLYDIR);
        $migratedCount = 0;
        
        foreach ($resultFolders as $folder) {
            $folderName = basename($folder);
            $parts = explode('_', $folderName);
            $accessCode = $parts[0];
            
            // Hole den zugehörigen Test
            $stmt = $this->db->prepare("SELECT test_id FROM tests WHERE access_code = ? LIMIT 1");
            $stmt->execute([$accessCode]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) {
                echo "<p style='color: orange;'>Warnung: Kein Test gefunden für Zugangscode: $accessCode</p>\n";
                continue;
            }
            
            // Verarbeite alle XML-Dateien im Ordner
            $xmlFiles = glob($folder . '/*.xml');
            foreach ($xmlFiles as $file) {
                try {
                    $xml = simplexml_load_file($file);
                    if ($xml === false) {
                        echo "<p style='color: orange;'>Warnung: Konnte XML-Datei nicht laden: " . basename($file) . "</p>\n";
                        continue;
                    }
                    
                    $this->db->beginTransaction();
                    
                    // Prüfe ob dieser Versuch bereits existiert
                    $stmt = $this->db->prepare("SELECT attempt_id FROM test_attempts WHERE xml_file_path = ?");
                    $relativePath = str_replace(__DIR__ . '/', '', $file);
                    $stmt->execute([$relativePath]);
                    if ($stmt->fetch()) {
                        $this->db->rollBack();
                        continue; // Bereits migriert
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
                    $migratedCount++;
                    
                } catch (Exception $e) {
                    $this->db->rollBack();
                    echo "<p style='color: red;'>✗ Fehler beim Migrieren des Testergebnisses: " . basename($file) . " - " . htmlspecialchars($e->getMessage()) . "</p>\n";
                    error_log("Fehler beim Migrieren des Testergebnisses: " . $file . "\n" . $e->getMessage());
                }
            }
        }
        
        echo "<p><strong>$migratedCount Testergebnisse erfolgreich migriert.</strong></p>\n";
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

// HTML-Ausgabe für Webserver
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ Test System - Datenbankzerteilung</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #333; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
    </style>
</head>
<body>
    <h1>MCQ Test System - Datenbankzerteilung</h1>
    
    <?php
    try {
        $migrator = new DatabaseMigrator();
        $migrator->migrateAllData();
        
        echo '<div class="status success">';
        echo '<h3>Migration erfolgreich abgeschlossen!</h3>';
        echo '<p>Das System ist nun bereit für den Mehrbenutzerbetrieb.</p>';
        echo '<p><a href="teacher/teacher_dashboard.php">Zum Lehrerbereich</a></p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="status error">';
        echo '<h3>Fehler bei der Migration:</h3>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
    
    <h3>Nächste Schritte:</h3>
    <ol>
        <li>Überprüfen Sie die Datenbankverbindung</li>
        <li>Testen Sie die Mehrbenutzerfunktionen</li>
        <li>Löschen Sie diese Migrationsdatei nach erfolgreichem Test</li>
    </ol>
    
</body>
</html>
