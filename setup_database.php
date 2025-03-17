<?php
// Aktiviere Fehlerberichterstattung für die Entwicklung
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/logs/debug.log';
    $logDir = dirname($logFile);
    
    // Erstelle das Log-Verzeichnis, falls es nicht existiert
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== Start der Datenbankeinrichtung ===");

// Lade die Datenbankkonfiguration
require_once __DIR__ . '/includes/database_config.php';

try {
    // Erstelle eine Instanz der Datenbankkonfiguration
    $dbConfig = DatabaseConfig::getInstance();
    
    // Erstelle die Datenbank, falls sie nicht existiert
    $dbConfig->createDatabase();
    
    // Stelle eine Verbindung zur Datenbank her
    $db = $dbConfig->getConnection();
    
    // Prüfe, ob die Datenbankverbindung erfolgreich hergestellt wurde
    if ($db === null) {
        throw new Exception("Konnte keine Verbindung zur Datenbank herstellen. Bitte überprüfen Sie die Datenbankeinstellungen.");
    }
    
    // Erstelle die Tabellen
    
    // 1. Tests-Tabelle
    $db->exec("
        CREATE TABLE IF NOT EXISTS tests (
            test_id INT AUTO_INCREMENT PRIMARY KEY,
            access_code VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            question_count INT NOT NULL DEFAULT 0,
            answer_count INT NOT NULL DEFAULT 0,
            answer_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY (access_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    writeLog("Tests-Tabelle erstellt oder bereits vorhanden");
    
    // 2. Test-Versuche-Tabelle
    $db->exec("
        CREATE TABLE IF NOT EXISTS test_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            test_id INT NOT NULL,
            student_name VARCHAR(100) NOT NULL,
            xml_file_path VARCHAR(255) NOT NULL,
            points_achieved INT NOT NULL,
            points_maximum INT NOT NULL,
            percentage FLOAT NOT NULL,
            grade VARCHAR(10) NOT NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NOT NULL,
            FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    writeLog("Test-Versuche-Tabelle erstellt oder bereits vorhanden");
    
    // 3. Test-Statistiken-Tabelle
    $db->exec("
        CREATE TABLE IF NOT EXISTS test_statistics (
            statistic_id INT AUTO_INCREMENT PRIMARY KEY,
            test_id INT NOT NULL,
            attempts_count INT NOT NULL DEFAULT 0,
            average_percentage FLOAT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            FOREIGN KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
            UNIQUE KEY (test_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    writeLog("Test-Statistiken-Tabelle erstellt oder bereits vorhanden");
    
    // Überprüfe, ob die Tabellen erstellt wurden
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    writeLog("Vorhandene Tabellen: " . implode(", ", $tables));
    
    // Überprüfe, ob die Tests-Tabelle leer ist
    $testCount = $db->query("SELECT COUNT(*) FROM tests")->fetchColumn();
    writeLog("Anzahl der Tests in der Datenbank: $testCount");
    
    // Importiere vorhandene Tests aus dem tests-Verzeichnis, wenn die Tabelle leer ist
    if ($testCount == 0) {
        writeLog("Importiere vorhandene Tests aus dem tests-Verzeichnis");
        
        // Suche nach XML-Dateien im tests-Verzeichnis
        $testFiles = glob(__DIR__ . '/tests/*.xml');
        writeLog("Gefundene XML-Dateien: " . count($testFiles));
        
        foreach ($testFiles as $file) {
            try {
                $xml = simplexml_load_file($file);
                if ($xml) {
                    $accessCode = (string)$xml->access_code;
                    $title = (string)$xml->title;
                    $questionCount = count($xml->questions->question);
                    $answerCount = count($xml->questions->question[0]->answers->answer);
                    
                    // Bestimme den Antworttyp
                    $answerType = "multiple_choice";
                    if (isset($xml->questions->question[0]->type)) {
                        $firstQuestionType = (string)$xml->questions->question[0]->type;
                        if ($firstQuestionType === "text") {
                            $answerType = "text";
                        }
                    }
                    
                    // Füge den Test zur Datenbank hinzu
                    $stmt = $db->prepare("
                        INSERT INTO tests (
                            access_code, title, question_count, answer_count, answer_type, created_at
                        ) VALUES (
                            :access_code, :title, :question_count, :answer_count, :answer_type, NOW()
                        ) ON DUPLICATE KEY UPDATE
                            title = VALUES(title),
                            question_count = VALUES(question_count),
                            answer_count = VALUES(answer_count),
                            answer_type = VALUES(answer_type),
                            updated_at = NOW()
                    ");
                    
                    $stmt->execute([
                        'access_code' => $accessCode,
                        'title' => $title,
                        'question_count' => $questionCount,
                        'answer_count' => $answerCount,
                        'answer_type' => $answerType
                    ]);
                    
                    // Erstelle einen Eintrag in der Statistik-Tabelle
                    $testId = $db->lastInsertId();
                    if ($testId) {
                        $db->exec("
                            INSERT INTO test_statistics (test_id, attempts_count, average_percentage, created_at)
                            VALUES ($testId, 0, 0, NOW())
                            ON DUPLICATE KEY UPDATE updated_at = NOW()
                        ");
                    }
                    
                    writeLog("Test importiert: $accessCode - $title");
                }
            } catch (Exception $e) {
                writeLog("Fehler beim Importieren von " . basename($file) . ": " . $e->getMessage());
            }
        }
        
        // Überprüfe die Anzahl der importierten Tests
        $testCount = $db->query("SELECT COUNT(*) FROM tests")->fetchColumn();
        writeLog("Anzahl der Tests nach dem Import: $testCount");
    }
    
    // Importiere vorhandene Testergebnisse aus dem results-Verzeichnis
    $resultFiles = glob(__DIR__ . '/results/*.xml');
    writeLog("Gefundene Ergebnis-Dateien: " . count($resultFiles));
    
    $importedResults = 0;
    foreach ($resultFiles as $file) {
        try {
            $xml = simplexml_load_file($file);
            if ($xml && isset($xml->access_code) && isset($xml->student_name)) {
                $accessCode = (string)$xml->access_code;
                $studentName = (string)$xml->student_name;
                $achievedPoints = isset($xml->achieved_points) ? (int)$xml->achieved_points : 0;
                $maxPoints = isset($xml->max_points) ? (int)$xml->max_points : 0;
                $percentage = isset($xml->percentage) ? (float)$xml->percentage : 0;
                $grade = isset($xml->grade) ? (string)$xml->grade : 'N/A';
                $date = isset($xml->date) ? (string)$xml->date : date('Y-m-d H:i:s');
                
                // Prüfe, ob der Test in der Datenbank existiert
                $stmt = $db->prepare("SELECT test_id FROM tests WHERE access_code = :access_code");
                $stmt->execute(['access_code' => $accessCode]);
                $test = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($test) {
                    $testId = $test['test_id'];
                    
                    // Prüfe, ob der Versuch bereits in der Datenbank existiert
                    $stmt = $db->prepare("
                        SELECT COUNT(*) FROM test_attempts 
                        WHERE test_id = :test_id AND student_name = :student_name AND completed_at = :completed_at
                    ");
                    $stmt->execute([
                        'test_id' => $testId,
                        'student_name' => $studentName,
                        'completed_at' => $date
                    ]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        // Füge den Testversuch zur Datenbank hinzu
                        $stmt = $db->prepare("
                            INSERT INTO test_attempts (
                                test_id, student_name, xml_file_path, 
                                points_achieved, points_maximum, percentage, grade,
                                started_at, completed_at
                            ) VALUES (
                                :test_id, :student_name, :xml_file_path,
                                :points_achieved, :points_maximum, :percentage, :grade,
                                :started_at, :completed_at
                            )
                        ");
                        
                        $stmt->execute([
                            'test_id' => $testId,
                            'student_name' => $studentName,
                            'xml_file_path' => $file,
                            'points_achieved' => $achievedPoints,
                            'points_maximum' => $maxPoints,
                            'percentage' => $percentage,
                            'grade' => $grade,
                            'started_at' => $date, // Verwende das gleiche Datum für Start und Ende
                            'completed_at' => $date
                        ]);
                        
                        $importedResults++;
                        writeLog("Testergebnis importiert: $accessCode - $studentName");
                        
                        // Aktualisiere die Teststatistiken
                        $db->exec("
                            UPDATE test_statistics 
                            SET attempts_count = (
                                SELECT COUNT(*) FROM test_attempts WHERE test_id = $testId
                            ),
                            average_percentage = (
                                SELECT AVG(percentage) FROM test_attempts WHERE test_id = $testId
                            ),
                            updated_at = NOW()
                            WHERE test_id = $testId
                        ");
                    }
                } else {
                    writeLog("Test mit Zugangscode $accessCode nicht gefunden für Ergebnis von $studentName");
                }
            }
        } catch (Exception $e) {
            writeLog("Fehler beim Importieren des Ergebnisses " . basename($file) . ": " . $e->getMessage());
        }
    }
    
    writeLog("Anzahl der importierten Testergebnisse: $importedResults");
    
    // Überprüfe die Anzahl der Testversuche
    $attemptCount = $db->query("SELECT COUNT(*) FROM test_attempts")->fetchColumn();
    writeLog("Anzahl der Testversuche in der Datenbank: $attemptCount");
    
    writeLog("=== Datenbankeinrichtung erfolgreich abgeschlossen ===");
    
    // Ausgabe für den Benutzer
    echo "<h1>Datenbankeinrichtung</h1>";
    echo "<p>Die Datenbank wurde erfolgreich eingerichtet.</p>";
    echo "<ul>";
    echo "<li>Tabellen erstellt: " . implode(", ", $tables) . "</li>";
    echo "<li>Anzahl der Tests: $testCount</li>";
    echo "<li>Anzahl der Testversuche: $attemptCount</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Zurück zur Startseite</a></p>";
    
} catch (Exception $e) {
    writeLog("Fehler bei der Datenbankeinrichtung: " . $e->getMessage());
    
    // Ausgabe für den Benutzer
    echo "<h1>Fehler bei der Datenbankeinrichtung</h1>";
    echo "<p>Es ist ein Fehler aufgetreten: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='index.php'>Zurück zur Startseite</a></p>";
}
?> 