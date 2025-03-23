<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/test_results_manager.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Verbindung zur Datenbank herstellen
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");
    
    // 1. Sammle alle Daten aus der Datenbank
    $dbResults = [];
    $query = "
        SELECT 
            ta.attempt_id,
            ta.test_id,
            t.access_code,
            t.title,
            ta.student_name,
            ta.completed_at,
            ta.xml_file_path,
            ta.points_achieved,
            ta.points_maximum,
            ta.percentage,
            ta.grade
        FROM 
            test_attempts ta
        JOIN 
            tests t ON ta.test_id = t.test_id
        ORDER BY 
            t.access_code, ta.completed_at DESC
    ";
    
    $stmt = $db->query($query);
    $dbEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Datenbankeinträge geladen: " . count($dbEntries));
    
    // Speichere DB-Einträge in einer Map nach XML-Pfad
    $dbEntriesByPath = [];
    foreach ($dbEntries as $entry) {
        // Normalisierte Pfaddarstellung für Vergleiche
        $normalizedPath = str_replace('\\', '/', $entry['xml_file_path']);
        
        // Entferne absolute Pfadkomponenten, falls vorhanden
        $normalizedPath = preg_replace('/^.*?\/results\//', 'results/', $normalizedPath);
        
        // Füge zur Ergebnisliste hinzu
        $dbResults[] = [
            'id' => $entry['attempt_id'],
            'test_id' => $entry['test_id'],
            'access_code' => $entry['access_code'],
            'title' => $entry['title'],
            'student_name' => $entry['student_name'],
            'completed_at' => $entry['completed_at'],
            'xml_file_path' => $normalizedPath, // Verwende den normalisierten Pfad
            'points_achieved' => $entry['points_achieved'],
            'points_maximum' => $entry['points_maximum'],
            'percentage' => $entry['percentage'],
            'grade' => $entry['grade'],
            'status' => 'db' // Vorerst auf 'db' setzen, später prüfen wir, ob die Datei existiert
        ];
        
        $dbEntriesByPath[$normalizedPath] = $entry;
    }
    
    // 2. Sammle alle XML-Dateien aus dem results-Ordner
    $resultsDir = __DIR__ . '/../../results';
    writeLog("Suche nach XML-Dateien in: " . $resultsDir);
    
    $xmlResults = [];
    $xmlFilesByPath = [];
    
    if (is_dir($resultsDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resultsDir));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'xml') {
                // Normalisierter Dateipfad relativ zum Basisverzeichnis
                $relativePath = 'results/' . str_replace('\\', '/', substr($file->getPathname(), strlen($resultsDir) + 1));
                $xmlFilesByPath[$relativePath] = $file->getPathname();
                
                // Extrahiere Informationen aus der XML-Datei und dem Dateinamen
                $filename = $file->getBasename();
                
                // Versuche, Metadaten aus dem Dateinamen zu extrahieren
                if (preg_match('/^([A-Z0-9]+)_(.+?)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.xml$/', $filename, $matches)) {
                    $accessCode = $matches[1];
                    $studentName = $matches[2];
                    $timestamp = str_replace('_', ' ', $matches[3]);
                    
                    // Prüfe, ob Eintrag in der Datenbank existiert
                    $existsInDb = isset($dbEntriesByPath[$relativePath]);
                    
                    // Hole Test-Informationen, wenn der Test in der Datenbank existiert
                    $testId = null;
                    $testTitle = "Test " . $accessCode;
                    
                    // Suche nach dem Test in der Datenbank
                    $testQuery = $db->prepare("SELECT test_id, title FROM tests WHERE access_code = ?");
                    $testQuery->execute([$accessCode]);
                    $testInfo = $testQuery->fetch(PDO::FETCH_ASSOC);
                    
                    if ($testInfo) {
                        $testId = $testInfo['test_id'];
                        $testTitle = $testInfo['title'];
                    }
                    
                    // Lade XML-Datei, um weitere Daten zu extrahieren
                    $points = [0, 0, 0, ''];
                    try {
                        $xml = simplexml_load_file($file->getPathname());
                        if ($xml) {
                            // Extrahiere Punktzahlen und Note
                            $pointsAchieved = isset($xml->points_achieved) ? (int)$xml->points_achieved : 0;
                            $pointsMaximum = isset($xml->points_maximum) ? (int)$xml->points_maximum : 0;
                            $percentage = $pointsMaximum > 0 ? round(($pointsAchieved / $pointsMaximum) * 100, 2) : 0;
                            $grade = isset($xml->grade) ? (string)$xml->grade : '';
                            
                            $points = [$pointsAchieved, $pointsMaximum, $percentage, $grade];
                        }
                    } catch (Exception $e) {
                        writeLog("Fehler beim Lesen der XML-Datei: " . $file->getPathname() . " - " . $e->getMessage());
                    }
                    
                    // Nur hinzufügen, wenn nicht in der Datenbank vorhanden
                    if (!$existsInDb) {
                        $xmlResults[] = [
                            'id' => null,
                            'test_id' => $testId,
                            'access_code' => $accessCode,
                            'title' => $testTitle,
                            'student_name' => $studentName,
                            'completed_at' => $timestamp,
                            'xml_file_path' => $relativePath,
                            'points_achieved' => $points[0],
                            'points_maximum' => $points[1],
                            'percentage' => $points[2],
                            'grade' => $points[3],
                            'status' => 'xml' // Nur in XML vorhanden
                        ];
                    }
                } else {
                    writeLog("Ungültiges Dateinamensformat: " . $filename);
                }
            }
        }
    } else {
        writeLog("Results-Verzeichnis nicht gefunden: " . $resultsDir);
    }
    
    // 3. Aktualisiere Status für DB-Einträge, die auch als XML existieren
    foreach ($dbResults as &$entry) {
        if (isset($xmlFilesByPath[$entry['xml_file_path']])) {
            $entry['status'] = 'both'; // Sowohl in DB als auch als XML-Datei vorhanden
        }
    }
    
    // 4. Kombiniere DB- und XML-Ergebnisse
    $allResults = array_merge($dbResults, $xmlResults);
    
    // Sortiere nach Test-Code und Datum
    usort($allResults, function($a, $b) {
        // Zuerst nach Test-Code
        $codeCompare = strcmp($a['access_code'], $b['access_code']);
        if ($codeCompare !== 0) {
            return $codeCompare;
        }
        
        // Bei gleichem Code nach Datum absteigend (neueste zuerst)
        return strcmp($b['completed_at'], $a['completed_at']);
    });
    
    // Erfolgreiche Antwort
    echo json_encode([
        'success' => true,
        'data' => $allResults,
        'count' => count($allResults),
        'db_count' => count($dbResults),
        'xml_count' => count($xmlResults)
    ]);
    
    writeLog("Datenabfrage erfolgreich. Gesamt: " . count($allResults) . ", DB: " . count($dbResults) . ", Nur XML: " . count($xmlResults));
    
} catch (Exception $e) {
    // Fehlermeldung
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    writeLog("Fehler bei der Datenabfrage: " . $e->getMessage());
}
?> 