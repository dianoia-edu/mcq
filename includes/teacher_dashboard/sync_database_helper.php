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
    $stmt = $db->query("SELECT attempt_id, test_id, student_name, xml_file_path, completed_at FROM test_attempts");
    $dbEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Erstelle verschiedene Maps für robuste Duplikaterkennung
    $existingEntries = []; // Dateipfad-basiert
    $existingFiles = [];   // Normalisierte Pfade
    $existingTests = [];   // Test+Student+Datum-basiert
    
    foreach ($dbEntries as $entry) {
        // Basis-Dateiname als Schlüssel
        $key = basename($entry['xml_file_path']);
        $existingEntries[$key] = $entry;
        
        // Normalisierter Pfad (für Vergleich von absoluten/relativen Pfaden)
        $normalizedPath = str_replace('\\', '/', $entry['xml_file_path']);
        $relativePath = preg_replace('#^.*?(/results/|results/)#', 'results/', $normalizedPath);
        $existingFiles[$relativePath] = $entry['attempt_id'];
        
        // Teile des Dateinamens extrahieren für Prüfung ohne Pfad
        if (preg_match('/^([A-Z0-9]+)_(.+)_(\d{4}-\d{2}-\d{2})/', basename($entry['xml_file_path']), $matches)) {
            $codeStudentDateKey = $matches[1] . '_' . $matches[2] . '_' . $matches[3];
            $existingTests[$codeStudentDateKey] = $entry['attempt_id'];
        }
    }
    
    error_log("Bestehende Datenbankeinträge: " . count($existingEntries) . ", Normalisierte Pfade: " . count($existingFiles) . ", Code+Student+Datum Kombinationen: " . count($existingTests));
    
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

    $processed = 0;
    $skipped = 0;
    $added = 0;
    
    foreach ($xmlFiles as $relativePath => $fullPath) {
        $processed++;
        $filename = basename($relativePath);
        
        // Prüfe ob der Eintrag bereits existiert (durch Dateinamen)
        if (isset($existingEntries[$filename])) {
            error_log("Datei bereits in Datenbank: " . $filename);
            $skipped++;
            continue;
        }
        
        // Extrahiere den Zugangscode aus dem Dateinamen
        if (!preg_match('/^([A-Z0-9]+)_/', $filename, $matches)) {
            error_log("Kein gültiger Zugangscode in Dateiname gefunden: " . $filename);
            $skipped++;
            continue;
        }
        $accessCode = $matches[1];
        
        // Lade XML und extrahiere Informationen
        $xml = simplexml_load_file($fullPath);
        if ($xml === false) {
            error_log("Konnte XML nicht laden: " . $fullPath);
            $skipped++;
            continue;
        }
        
        // Extrahiere den Schülernamen
        $studentName = isset($xml->schuelername) ? (string)$xml->schuelername : '';
        
        // Extrahiere das Datum aus dem Dateinamen oder XML
        $completedAt = isset($xml->abgabezeit) ? (string)$xml->abgabezeit : date('Y-m-d H:i:s', filemtime($fullPath));
        $completedDate = date('Y-m-d', strtotime($completedAt));
        
        // Berechne Punkte und Note
        // Definiere Konstante, um das HTML und JavaScript in auswertung.php zu überspringen
        if (!defined('FUNCTIONS_ONLY')) {
            define('FUNCTIONS_ONLY', true);
        }
        require_once __DIR__ . '/../../auswertung.php';
        $results = evaluateTest($fullPath);
        if ($results === false) {
            error_log("Konnte Ergebnisse nicht berechnen: " . $fullPath);
            $skipped++;
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
        
        // VERBESSERTE DUPLIKATPRÜFUNG: Überprüfe mehrere Faktoren
        $isDuplicate = false;
        $duplicateReason = "";
        
        // 1. Prüfung anhand von Zugangscode, Schülername und Datum
        $codeStudentDateKey = $accessCode . '_' . $studentName . '_' . $completedDate;
        if (isset($existingTests[$codeStudentDateKey])) {
            $isDuplicate = true;
            $duplicateReason = "Code+Student+Datum Kombination bereits vorhanden";
        }
        
        // 2. Prüfung anhand des Dateinamens
        $fileBaseName = basename($relativePath);
        if (isset($existingEntries[$fileBaseName])) {
            $isDuplicate = true;
            $duplicateReason = "Dateiname bereits in Datenbank";
        }
        
        // 3. Prüfung anhand des normalisierten Pfads
        $normalizedPath = 'results/' . $relativePath;
        if (isset($existingFiles[$normalizedPath])) {
            $isDuplicate = true;
            $duplicateReason = "Normalisierter Pfad bereits in Datenbank";
        }
        
        // Bei Duplikat überspringe die Einfügung
        if ($isDuplicate) {
            error_log("Doppelter Eintrag gefunden: {$duplicateReason} - Überspringe {$relativePath}");
            $skipped++;
            continue;
        }
        
        // Keine Duplikate gefunden, füge den Eintrag hinzu
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
            error_log("Eintrag erfolgreich hinzugefügt für: " . $filename);
            $added++;
        } catch (Exception $e) {
            error_log("Fehler beim Einfügen von " . $relativePath . ": " . $e->getMessage());
            $skipped++;
        }
    }
    
    error_log("Synchronisation abgeschlossen: {$processed} XML-Dateien verarbeitet, {$added} hinzugefügt, {$skipped} übersprungen");
} 