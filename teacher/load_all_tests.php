<?php
// Aktiviere Error-Reporting für Entwicklung
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Setze Header für JSON
header('Content-Type: application/json');

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = dirname(__DIR__) . '/logs/debug.log';
    $logDir = dirname($logFile);
    
    // Erstelle das Verzeichnis, falls es nicht existiert
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Protokolliere den Start
    writeLog("load_all_tests.php: Starte Laden aller Tests");
    
    // Lade Datenbankverbindung
    $dbConfigPath = dirname(__DIR__) . '/includes/database_config.php';
    writeLog("Versuche Datenbankverbindung zu laden: $dbConfigPath");
    
    if (!file_exists($dbConfigPath)) {
        throw new Exception("Datenbank-Konfigurationsdatei nicht gefunden: $dbConfigPath");
    }
    
    require_once $dbConfigPath;
    
    // Hole Datenbankverbindung
    $dbConfig = DatabaseConfig::getInstance();
    $db = $dbConfig->getConnection();
    writeLog("Datenbankverbindung erfolgreich hergestellt");
    
    // Initialisiere Arrays für Tests
    $testsFromDb = [];
    $testsFromFiles = [];
    $allTests = [];
    
    // 1. Hole alle Tests aus der Datenbank
    writeLog("Hole Tests aus der Datenbank");
    $stmt = $db->query("SELECT test_id, access_code, title, question_count, created_at FROM tests ORDER BY created_at DESC");
    $testsFromDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Anzahl Tests aus Datenbank: " . count($testsFromDb));
    
    // Erstelle ein Array mit test_id als Schlüssel für schnellen Zugriff
    $dbTestsMap = [];
    foreach ($testsFromDb as $test) {
        $dbTestsMap[$test['test_id']] = $test;
    }
    
    // 2. Hole alle XML-Dateien aus dem tests-Verzeichnis
    $testsDir = dirname(__DIR__) . '/tests/';
    writeLog("Suche nach XML-Dateien in: $testsDir");
    
    if (!is_dir($testsDir)) {
        writeLog("WARNUNG: Verzeichnis existiert nicht: $testsDir");
        $xmlFiles = [];
    } else {
        $xmlFiles = glob($testsDir . '*.xml');
        writeLog("Anzahl gefundener XML-Dateien: " . count($xmlFiles));
    }
    
    foreach ($xmlFiles as $xmlFile) {
        $fileName = basename($xmlFile, '.xml');
        writeLog("Verarbeite XML-Datei: $fileName.xml");
        
        try {
            // Versuche, die XML-Datei zu parsen
            $xmlContent = file_get_contents($xmlFile);
            if ($xmlContent === false) {
                writeLog("FEHLER: Konnte Datei nicht lesen: $xmlFile");
                continue;
            }
            
            $xml = @simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                writeLog("FEHLER: Konnte XML nicht parsen: $xmlFile");
                continue;
            }
            
            // Versuche, die test_id zu bestimmen
            $testId = $fileName;
            $accessCode = (string)$xml->access_code;
            
            // Prüfe, ob ein Test mit diesem Zugangscode in der Datenbank existiert
            $foundInDb = false;
            foreach ($dbTestsMap as $dbTestId => $dbTest) {
                if ($dbTest['access_code'] === $accessCode) {
                    $testId = $dbTestId; // Verwende die test_id aus der Datenbank
                    $foundInDb = true;
                    break;
                }
            }
            
            // Bestimme die Anzahl der Fragen
            $questionCount = 0;
            if (isset($xml->question_count) && (int)$xml->question_count > 0) {
                $questionCount = (int)$xml->question_count;
            } elseif (isset($xml->questions) && isset($xml->questions->question)) {
                $questionCount = count($xml->questions->question);
            }
            
            $testFromFile = [
                'test_id' => $testId,
                'access_code' => $accessCode,
                'title' => (string)$xml->title,
                'question_count' => $questionCount,
                'file_exists' => true,
                'in_database' => $foundInDb,
                'file_name' => $fileName
            ];
            
            $testsFromFiles[] = $testFromFile;
            writeLog("Test aus Datei verarbeitet: $accessCode - " . (string)$xml->title);
        } catch (Exception $e) {
            writeLog("FEHLER bei Verarbeitung von $xmlFile: " . $e->getMessage());
            continue;
        }
    }
    
    // 3. Erstelle ein Array mit allen Tests (aus Dateien und Datenbank)
    $allTestsMap = [];
    
    // Füge Tests aus Dateien hinzu
    foreach ($testsFromFiles as $test) {
        $allTestsMap[$test['test_id']] = $test;
    }
    
    // Füge Tests aus der Datenbank hinzu, die nicht in Dateien existieren
    foreach ($testsFromDb as $test) {
        if (!isset($allTestsMap[$test['test_id']])) {
            $test['file_exists'] = false;
            $test['in_database'] = true;
            $allTestsMap[$test['test_id']] = $test;
        } else {
            // Ergänze Informationen für Tests, die sowohl in Dateien als auch in der Datenbank existieren
            $allTestsMap[$test['test_id']]['created_at'] = $test['created_at'];
            $allTestsMap[$test['test_id']]['in_database'] = true;
        }
    }
    
    // 4. Hole Statistiken für jeden Test
    writeLog("Hole Statistiken für Tests");
    foreach ($allTestsMap as $testId => &$test) {
        try {
            // Hole Anzahl der Versuche
            $stmt = $db->prepare("SELECT COUNT(*) as attempt_count FROM test_attempts WHERE test_id = ?");
            $stmt->execute([$testId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $test['attempt_count'] = $result ? (int)$result['attempt_count'] : 0;
        } catch (Exception $e) {
            writeLog("FEHLER beim Holen der Statistiken für Test $testId: " . $e->getMessage());
            $test['attempt_count'] = 0;
        }
    }
    
    // Konvertiere Map zurück zu Array und sortiere nach Erstellungsdatum (neueste zuerst)
    $allTests = array_values($allTestsMap);
    usort($allTests, function($a, $b) {
        $dateA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
        $dateB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
        return $dateB - $dateA;
    });
    
    writeLog("Erfolgreich abgeschlossen. Anzahl Tests gesamt: " . count($allTests));
    
    // Erfolg melden
    echo json_encode([
        'success' => true,
        'tests' => $allTests,
        'db_count' => count($testsFromDb),
        'file_count' => count($testsFromFiles),
        'total_count' => count($allTests)
    ]);
    
} catch (Exception $e) {
    // Protokolliere den Fehler
    $errorMessage = "KRITISCHER FEHLER: " . $e->getMessage();
    writeLog($errorMessage);
    
    // Fehler melden
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
}
?> 