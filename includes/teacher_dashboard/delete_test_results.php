<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/test_results_manager.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Funktion zum Überprüfen und Löschen leerer Ordner
function removeEmptyDirectory($dirPath) {
    // Prüfe, ob der Pfad ein Verzeichnis ist
    if (!is_dir($dirPath)) {
        return false;
    }
    
    // Überspringe spezielle Verzeichnisse . und ..
    $files = array_diff(scandir($dirPath), ['.', '..']);
    
    // Wenn Verzeichnis leer ist, lösche es
    if (count($files) === 0) {
        return rmdir($dirPath);
    }
    
    return false;
}

// Debug-Informationen protokollieren
$rawInput = file_get_contents('php://input');
writeLog("Empfangene Anfrage - Method: " . $_SERVER['REQUEST_METHOD'] . ", Content-Type: " . $_SERVER['CONTENT_TYPE']);
writeLog("Rohe POST-Daten: " . $rawInput);

// Prüfen, ob POST-Daten gesendet wurden
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Nur POST-Anfragen erlaubt']);
    exit;
}

// POST-Daten empfangen und dekodieren
$input = json_decode($rawInput, true);
writeLog("Decodierte Daten: " . print_r($input, true));

if (!$input) {
    writeLog("JSON-Decodierungsfehler: " . json_last_error_msg());
    echo json_encode(['success' => false, 'error' => 'Ungültige JSON-Daten: ' . json_last_error_msg()]);
    exit;
}

if (!isset($input['entries']) || !is_array($input['entries'])) {
    writeLog("Fehlender oder ungültiger 'entries' Parameter: " . print_r($input, true));
    echo json_encode(['success' => false, 'error' => 'Ungültige Eingabedaten: Parameter "entries" fehlt oder ist kein Array']);
    exit;
}

$entries = $input['entries'];
$deleteFiles = isset($input['delete_files']) ? (bool)$input['delete_files'] : false;
$deleteEmptyDirs = isset($input['delete_empty_dirs']) ? (bool)$input['delete_empty_dirs'] : false;

writeLog("Löschvorgang gestartet. Einträge: " . count($entries) . ", Dateien löschen: " . ($deleteFiles ? 'Ja' : 'Nein') . 
         ", Leere Ordner löschen: " . ($deleteEmptyDirs ? 'Ja' : 'Nein'));

try {
    // Verbindung zur Datenbank herstellen
    $db = DatabaseConfig::getInstance()->getConnection();
    
    // Statistiken für den Löschvorgang
    $stats = [
        'db_deleted' => 0,
        'files_deleted' => 0,
        'files_failed' => 0,
        'db_failed' => 0,
        'dirs_deleted' => 0,
        'dirs_failed' => 0
    ];
    
    // Speichere die Verzeichnisse, um sie später auf Leere zu prüfen
    $directories = [];
    
    // Starte Transaktion für Datenbank-Operationen
    $db->beginTransaction();
    
    foreach ($entries as $entry) {
        writeLog("Verarbeite Eintrag: " . print_r($entry, true));
        // Prüfe, ob es einen gültigen Datenbankeintrag gibt
        if (isset($entry['id']) && $entry['id']) {
            try {
                // Lösche Eintrag aus der Datenbank
                $stmt = $db->prepare("DELETE FROM test_attempts WHERE attempt_id = ?");
                $result = $stmt->execute([$entry['id']]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $stats['db_deleted']++;
                    writeLog("Datenbankeintrag gelöscht: ID " . $entry['id']);
                } else {
                    $stats['db_failed']++;
                    writeLog("Fehler beim Löschen des Datenbankeintrags: ID " . $entry['id']);
                }
            } catch (Exception $e) {
                $stats['db_failed']++;
                writeLog("Ausnahme beim Löschen des Datenbankeintrags: ID " . $entry['id'] . " - " . $e->getMessage());
            }
        }
        
        // Wenn Dateien auch gelöscht werden sollen und ein Dateipfad existiert
        if ($deleteFiles && isset($entry['file_path']) && $entry['file_path']) {
            $filePath = __DIR__ . '/../../' . $entry['file_path'];
            
            // Normalisiere den Pfad für das Dateisystem
            $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            
            // Speichere das Verzeichnis für späteres Prüfen
            if ($deleteEmptyDirs) {
                $dirPath = dirname($filePath);
                if (!in_array($dirPath, $directories)) {
                    $directories[] = $dirPath;
                }
            }
            
            if (file_exists($filePath)) {
                try {
                    if (unlink($filePath)) {
                        $stats['files_deleted']++;
                        writeLog("XML-Datei gelöscht: " . $filePath);
                    } else {
                        $stats['files_failed']++;
                        writeLog("Fehler beim Löschen der XML-Datei: " . $filePath);
                    }
                } catch (Exception $e) {
                    $stats['files_failed']++;
                    writeLog("Ausnahme beim Löschen der XML-Datei: " . $filePath . " - " . $e->getMessage());
                }
            } else {
                writeLog("XML-Datei nicht gefunden: " . $filePath);
                $stats['files_failed']++;
            }
        }
    }
    
    // Commit der Transaktion
    $db->commit();
    
    // Wenn gewünscht, leere Verzeichnisse löschen
    if ($deleteEmptyDirs && !empty($directories)) {
        writeLog("Prüfe " . count($directories) . " Verzeichnisse auf Leere");
        
        // Sortiere Verzeichnisse nach Tiefe (tiefster zuerst)
        usort($directories, function($a, $b) {
            return substr_count($b, DIRECTORY_SEPARATOR) - substr_count($a, DIRECTORY_SEPARATOR);
        });
        
        foreach ($directories as $dirPath) {
            try {
                if (removeEmptyDirectory($dirPath)) {
                    $stats['dirs_deleted']++;
                    writeLog("Leeres Verzeichnis gelöscht: " . $dirPath);
                }
            } catch (Exception $e) {
                $stats['dirs_failed']++;
                writeLog("Ausnahme beim Löschen des Verzeichnisses: " . $dirPath . " - " . $e->getMessage());
            }
        }
    }
    
    // Erfolgreiche Antwort
    echo json_encode([
        'success' => true,
        'message' => 'Löschvorgang abgeschlossen',
        'stats' => $stats
    ]);
    
    writeLog("Löschvorgang abgeschlossen. Statistiken: " . json_encode($stats));
    
} catch (Exception $e) {
    // Bei Fehler Transaktion zurückrollen
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Fehlermeldung
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    writeLog("Fehler beim Löschvorgang: " . $e->getMessage());
}
?> 