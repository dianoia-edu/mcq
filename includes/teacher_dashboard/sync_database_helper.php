<?php
require_once __DIR__ . '/../../includes/database_config.php';

function syncDatabase() {
    $db = DatabaseConfig::getInstance()->getConnection();
    
    // Sammle alle vorhandenen Test-Codes aus den XML-Dateien
    $rootPath = dirname(dirname(__DIR__)); // Navigiere zur Projektroot (2 Ebenen nach oben)
    $resultsDir = $rootPath . '/results';
    
    error_log("Synchronisiere Datenbank - Verwende Results-Verzeichnis: " . $resultsDir);
    
    if (!is_dir($resultsDir)) {
        $errorMsg = "Results-Verzeichnis nicht gefunden: " . $resultsDir;
        error_log($errorMsg);
        
        // Versuche, das Verzeichnis zu erstellen
        if (!mkdir($resultsDir, 0775, true)) {
            throw new Exception($errorMsg . " und konnte nicht erstellt werden");
        }
        error_log("Results-Verzeichnis wurde erstellt: " . $resultsDir);
    }
    
    // Prüfe Berechtigungen
    if (!is_readable($resultsDir)) {
        $errorMsg = "Results-Verzeichnis ist nicht lesbar: " . $resultsDir;
        error_log($errorMsg);
        // Versuche Berechtigungen zu setzen
        chmod($resultsDir, 0775);
        if (!is_readable($resultsDir)) {
            throw new Exception($errorMsg . " und Berechtigungen konnten nicht gesetzt werden");
        }
    }
    
    // Hole alle existierenden Einträge aus der Datenbank
    $stmt = $db->query("SELECT attempt_id, xml_file_path FROM test_attempts");
    $dbEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sammle alle XML-Dateien
    $xmlFiles = [];
    
    // Erstelle Iterator nur einmal
    $dirIterator = new RecursiveDirectoryIterator($resultsDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator);
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'xml') {
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($resultsDir) + 1));
            $xmlFiles[$relativePath] = $file->getPathname();
        }
    }
    
    // Logge Anzahl der gefundenen XML-Dateien
    error_log("Gefundene XML-Dateien: " . count($xmlFiles));
    
    // Füge neue XML-Dateien zur Datenbank hinzu
    $insertStmt = $db->prepare("
        INSERT INTO test_attempts (
            test_id, 
            student_name, 
            completed_at, 
            xml_file_path,
            points_achieved,
            points_maximum,
            percentage,
            grade
        ) VALUES (
            (SELECT test_id FROM tests WHERE access_code = ?),
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    foreach ($xmlFiles as $relativePath => $fullPath) {
        // Prüfe ob der Eintrag bereits existiert
        $exists = false;
        foreach ($dbEntries as $entry) {
            if (strpos($entry['xml_file_path'], $relativePath) !== false) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            error_log("Verarbeite neue XML-Datei: " . $relativePath);
            
            // Lade XML und extrahiere Informationen
            $xml = simplexml_load_file($fullPath);
            if ($xml === false) {
                error_log("Konnte XML nicht laden: " . $fullPath);
                continue;
            }
            
            // Extrahiere den Zugangscode aus dem Dateinamen
            if (!preg_match('/^([A-Z0-9]+)_/', basename($relativePath), $matches)) {
                error_log("Kein gültiger Zugangscode in Dateiname gefunden: " . basename($relativePath));
                continue;
            }
            $accessCode = $matches[1];
            
            // Extrahiere den Schülernamen
            $studentName = isset($xml->schuelername) ? (string)$xml->schuelername : '';
            
            // Extrahiere das Datum aus dem Dateinamen oder XML
            $completedAt = isset($xml->abgabezeit) ? (string)$xml->abgabezeit : date('Y-m-d H:i:s', filemtime($fullPath));
            
            // Berechne Punkte und Note
            // Definiere Konstante, um das HTML und JavaScript in auswertung.php zu überspringen
            if (!defined('FUNCTIONS_ONLY')) {
                define('FUNCTIONS_ONLY', true);
            }
            require_once __DIR__ . '/../../auswertung.php';
            $results = evaluateTest($fullPath);
            if ($results === false) {
                error_log("Konnte Ergebnisse nicht berechnen: " . $fullPath);
                continue;
            }
            
            try {
                error_log("Lade Notenschema für Datei: " . $fullPath);
                $schema = loadGradeSchema();
                error_log("Notenschema geladen: " . (is_array($schema) ? count($schema) . " Einträge" : "Fehler - kein Array"));
                
                if (is_array($schema) && !empty($schema)) {
                    $grade = calculateGrade($results['percentage'], $schema);
                    error_log("Note berechnet: " . $grade . " für " . $results['percentage'] . "%");
                } else {
                    // Fallback für den Fall, dass das Schema nicht geladen werden kann
                    $grade = "?";
                    error_log("Konnte Note nicht berechnen, verwende Platzhalter");
                }
            } catch (Exception $e) {
                error_log("Fehler bei der Notenberechnung: " . $e->getMessage());
                $grade = "?";
            }
            
            error_log("Füge neuen Eintrag hinzu: " . $accessCode . ", " . $studentName . ", " . $results['achieved'] . "/" . $results['max'] . ", Note: " . $grade);
            
            // Füge den Eintrag zur Datenbank hinzu
            try {
                $insertStmt->execute([
                    $accessCode,
                    $studentName,
                    $completedAt,
                    'results/' . $relativePath,
                    $results['achieved'],
                    $results['max'],
                    $results['percentage'],
                    $grade
                ]);
                error_log("Eintrag erfolgreich hinzugefügt");
            } catch (Exception $e) {
                error_log("Fehler beim Einfügen von " . $relativePath . ": " . $e->getMessage());
            }
        }
    }
} 