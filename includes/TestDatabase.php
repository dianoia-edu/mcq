<?php
require_once __DIR__ . '/database_config.php';

class TestDatabase {
    private $db;
    private $isConnected = false;
    
    public function __construct() {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $this->db = $dbConfig->getConnection();
            $this->isConnected = ($this->db !== null);
            
            if (!$this->isConnected) {
                error_log("TestDatabase: Keine Datenbankverbindung hergestellt");
            } else {
                error_log("TestDatabase: Datenbankverbindung erfolgreich hergestellt");
            }
        } catch (Exception $e) {
            error_log("TestDatabase: Fehler beim Herstellen der Datenbankverbindung: " . $e->getMessage());
            $this->isConnected = false;
        }
    }
    
    /**
     * Prüft, ob eine Datenbankverbindung besteht
     * 
     * @return bool True, wenn eine Verbindung besteht, sonst False
     */
    public function isConnected() {
        return $this->isConnected;
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
        if (!$this->isConnected) {
            error_log("saveTestAttempt: Keine Datenbankverbindung");
            return false;
        }
        
        try {
            // Finde die Test-ID anhand des Zugangscodes
            $stmt = $this->db->prepare("SELECT test_id FROM tests WHERE access_code = :access_code");
            $stmt->execute(['access_code' => $testCode]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) {
                error_log("Test mit Zugangscode $testCode nicht gefunden. Erstelle neuen Test.");
                // Erstelle einen neuen Test, wenn er nicht existiert
                $testId = $this->createTest($testCode, "Automatisch erstellter Test", 0, 0, "multiple_choice");
                
                if (!$testId) {
                    error_log("Konnte keinen neuen Test erstellen für Zugangscode: $testCode");
                    return false;
                }
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
                error_log("Testversuch erfolgreich gespeichert für: $studentName, Test: $testCode");
                return true;
            }
            
            error_log("Fehler beim Speichern des Testversuchs in der Datenbank");
            return false;
        } catch (Exception $e) {
            error_log("Fehler beim Speichern des Testversuchs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aktualisiert die Teststatistiken für einen bestimmten Test
     * 
     * @param int $testId Die ID des Tests
     * @return bool True bei Erfolg, False bei Fehler
     */
    private function updateTestStatistics($testId) {
        if (!$this->isConnected) {
            error_log("updateTestStatistics: Keine Datenbankverbindung");
            return false;
        }
        
        try {
            // Berechne die durchschnittliche Prozentzahl
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as attempts_count,
                    AVG(percentage) as average_percentage
                FROM test_attempts
                WHERE test_id = :test_id
            ");
            $stmt->execute(['test_id' => $testId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Prüfe, ob bereits ein Eintrag in der Statistik-Tabelle existiert
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM test_statistics WHERE test_id = :test_id
            ");
            $checkStmt->execute(['test_id' => $testId]);
            $exists = ($checkStmt->fetchColumn() > 0);
            
            if ($exists) {
                // Aktualisiere die Statistiken
                $updateStmt = $this->db->prepare("
                    UPDATE test_statistics
                    SET 
                        attempts_count = :attempts_count,
                        average_percentage = :average_percentage,
                        updated_at = NOW()
                    WHERE test_id = :test_id
                ");
                
                $updateStmt->execute([
                    'attempts_count' => $stats['attempts_count'],
                    'average_percentage' => $stats['average_percentage'],
                    'test_id' => $testId
                ]);
            } else {
                // Erstelle einen neuen Statistik-Eintrag
                $insertStmt = $this->db->prepare("
                    INSERT INTO test_statistics (
                        test_id, attempts_count, average_percentage, created_at
                    ) VALUES (
                        :test_id, :attempts_count, :average_percentage, NOW()
                    )
                ");
                
                $insertStmt->execute([
                    'test_id' => $testId,
                    'attempts_count' => $stats['attempts_count'],
                    'average_percentage' => $stats['average_percentage']
                ]);
            }
            
            error_log("Teststatistiken aktualisiert für Test-ID: $testId");
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
        if (!$this->isConnected) {
            error_log("getLatestTestResult: Keine Datenbankverbindung");
            return false;
        }
        
        try {
            error_log("Suche Testergebnis für Test: $testCode, Schüler: $studentName");
            
            // Prüfe zuerst, ob der Test existiert
            $testStmt = $this->db->prepare("SELECT test_id FROM tests WHERE access_code = :access_code");
            $testStmt->execute(['access_code' => $testCode]);
            $test = $testStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) {
                error_log("Test mit Zugangscode $testCode nicht gefunden");
                
                // Versuche, das Ergebnis direkt aus der XML-Datei zu laden
                return $this->getResultFromXmlFile($testCode, $studentName);
            }
            
            $testId = $test['test_id'];
            error_log("Test gefunden mit ID: $testId");
            
            // Suche nach dem neuesten Versuch für diesen Test und Schüler
            $stmt = $this->db->prepare("
                SELECT 
                    ta.points_achieved,
                    ta.points_maximum,
                    ta.percentage,
                    ta.grade,
                    ta.completed_at,
                    ta.xml_file_path
                FROM test_attempts ta
                WHERE ta.test_id = :test_id
                AND ta.student_name = :student_name
                ORDER BY ta.completed_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([
                'test_id' => $testId,
                'student_name' => $studentName
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log("Testergebnis gefunden für " . $studentName . " (Test: " . $testCode . ")");
                return $result;
            } else {
                error_log("Kein Testergebnis in der Datenbank gefunden für " . $studentName . " (Test: " . $testCode . ")");
                
                // Versuche, das Ergebnis direkt aus der XML-Datei zu laden
                return $this->getResultFromXmlFile($testCode, $studentName);
            }
        } catch (Exception $e) {
            error_log("Fehler beim Laden des Testergebnisses: " . $e->getMessage());
            
            // Versuche, das Ergebnis direkt aus der XML-Datei zu laden
            return $this->getResultFromXmlFile($testCode, $studentName);
        }
    }
    
    /**
     * Versucht, ein Testergebnis direkt aus einer XML-Datei zu laden
     * 
     * @param string $testCode Der Zugangscode des Tests
     * @param string $studentName Der Name des Schülers
     * @return array|false Die Testergebnisse oder false bei Fehler
     */
    private function getResultFromXmlFile($testCode, $studentName) {
        error_log("Versuche, Testergebnis aus XML-Datei zu laden für Test: $testCode, Schüler: $studentName");
        
        // Suche nach XML-Dateien im results-Verzeichnis
        $date = date('Y-m-d');
        $folderName = $testCode . '_' . $date;
        $resultsDir = dirname(__DIR__) . '/results/' . $folderName;
        
        if (!is_dir($resultsDir)) {
            error_log("Ergebnisverzeichnis nicht gefunden: $resultsDir");
            
            // Versuche, alle Unterverzeichnisse im results-Verzeichnis zu durchsuchen
            $resultsBaseDir = dirname(__DIR__) . '/results';
            $subDirs = glob($resultsBaseDir . '/' . $testCode . '_*', GLOB_ONLYDIR);
            
            if (empty($subDirs)) {
                error_log("Keine Ergebnisverzeichnisse für Test $testCode gefunden");
                return false;
            }
            
            // Verwende das neueste Verzeichnis
            usort($subDirs, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $resultsDir = $subDirs[0];
            error_log("Verwende Ergebnisverzeichnis: $resultsDir");
        }
        
        // Suche nach XML-Dateien für den Schüler
        $files = glob($resultsDir . '/' . $testCode . '_' . $studentName . '_*.xml');
        
        if (empty($files)) {
            error_log("Keine XML-Dateien für Schüler $studentName in $resultsDir gefunden");
            return false;
        }
        
        // Verwende die neueste Datei
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $xmlPath = $files[0];
        error_log("Verwende XML-Datei: $xmlPath");
        
        // Versuche, die XML-Datei zu laden
        $xml = @simplexml_load_file($xmlPath);
        if ($xml) {
            $achievedPoints = isset($xml->achieved_points) ? (int)$xml->achieved_points : 0;
            $maxPoints = isset($xml->max_points) ? (int)$xml->max_points : 0;
            $percentage = isset($xml->percentage) ? (float)$xml->percentage : 0;
            $grade = isset($xml->grade) ? (string)$xml->grade : 'N/A';
            $date = isset($xml->date) ? (string)$xml->date : date('Y-m-d H:i:s');
            
            error_log("Testergebnis aus XML-Datei geladen: $achievedPoints/$maxPoints Punkte, Note: $grade");
            
            return [
                'points_achieved' => $achievedPoints,
                'points_maximum' => $maxPoints,
                'percentage' => $percentage,
                'grade' => $grade,
                'completed_at' => $date,
                'xml_file_path' => $xmlPath
            ];
        }
        
        error_log("Konnte XML-Datei nicht laden: $xmlPath");
        return false;
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
        if (!$this->isConnected) {
            error_log("createTest: Keine Datenbankverbindung");
            return false;
        }
        
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
    
    /**
     * Testet die Datenbankverbindung
     * 
     * @return bool True, wenn die Verbindung funktioniert, sonst False
     */
    public function testConnection() {
        if (!$this->isConnected) {
            error_log("testConnection: Keine Datenbankverbindung");
            return false;
        }
        
        try {
            $stmt = $this->db->query("SELECT 1");
            if ($stmt) {
                error_log("Datenbankverbindung erfolgreich getestet");
                return true;
            }
            
            error_log("Datenbankverbindungstest fehlgeschlagen");
            return false;
        } catch (Exception $e) {
            error_log("Fehler beim Testen der Datenbankverbindung: " . $e->getMessage());
            return false;
        }
    }
} 