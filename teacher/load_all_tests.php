<?php
// Aktiviere Error-Reporting für Entwicklung
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Setze Header für JSON
header('Content-Type: application/json');

// Lade Datenbankverbindung
require_once dirname(__DIR__) . '/includes/database_config.php';

try {
    // Hole Datenbankverbindung
    $dbConfig = DatabaseConfig::getInstance();
    $db = $dbConfig->getConnection();
    
    // Initialisiere Arrays für Tests
    $testsFromDb = [];
    $testsFromFiles = [];
    $allTests = [];
    
    // 1. Hole alle Tests aus der Datenbank
    $stmt = $db->query("SELECT test_id, access_code, title, question_count, created_at FROM tests ORDER BY created_at DESC");
    $testsFromDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Erstelle ein Array mit test_id als Schlüssel für schnellen Zugriff
    $dbTestsMap = [];
    foreach ($testsFromDb as $test) {
        $dbTestsMap[$test['test_id']] = $test;
    }
    
    // 2. Hole alle XML-Dateien aus dem tests-Verzeichnis
    $testsDir = dirname(__DIR__) . '/tests/';
    $xmlFiles = glob($testsDir . '*.xml');
    
    foreach ($xmlFiles as $xmlFile) {
        $fileName = basename($xmlFile, '.xml');
        
        // Versuche, die XML-Datei zu parsen
        $xmlContent = file_get_contents($xmlFile);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml) {
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
            
            $testFromFile = [
                'test_id' => $testId,
                'access_code' => $accessCode,
                'title' => (string)$xml->title,
                'question_count' => isset($xml->question_count) ? (string)$xml->question_count : count($xml->questions->question),
                'file_exists' => true,
                'in_database' => $foundInDb,
                'file_name' => $fileName
            ];
            
            $testsFromFiles[] = $testFromFile;
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
    foreach ($allTestsMap as $testId => &$test) {
        // Hole Anzahl der Versuche
        $stmt = $db->prepare("SELECT COUNT(*) as attempt_count FROM test_attempts WHERE test_id = ?");
        $stmt->execute([$testId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $test['attempt_count'] = $result ? (int)$result['attempt_count'] : 0;
    }
    
    // Konvertiere Map zurück zu Array und sortiere nach Erstellungsdatum (neueste zuerst)
    $allTests = array_values($allTestsMap);
    usort($allTests, function($a, $b) {
        $dateA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
        $dateB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
        return $dateB - $dateA;
    });
    
    // Erfolg melden
    echo json_encode([
        'success' => true,
        'tests' => $allTests,
        'db_count' => count($testsFromDb),
        'file_count' => count($testsFromFiles),
        'total_count' => count($allTests)
    ]);
    
} catch (Exception $e) {
    // Fehler melden
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 