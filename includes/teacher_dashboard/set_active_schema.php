<?php
/**
 * set_active_schema.php
 * Setzt das aktive Notenschema und gibt es als JSON zurück
 */

header('Content-Type: application/json');

// Debug-Funktion
function debug_log($message, $data = null) {
    $log_entry = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log_entry .= " - " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    // Schreibe in PHP-Fehlerlog
    error_log($log_entry);
    
    // Schreibe auch in eine Datei
    $logFile = __DIR__ . '/../../logs/schema_debug.log';
    file_put_contents($logFile, $log_entry . "\n", FILE_APPEND);
}

// Initialisiere die Antwort
$response = [
    'success' => false,
    'schema' => null,
    'error' => null
];

// Prüfe, ob die schema_id gesendet wurde
if (!isset($_POST['schema_id']) || empty($_POST['schema_id'])) {
    debug_log("Fehler: Keine Schema-ID angegeben");
    $response['error'] = 'Keine Schema-ID angegeben';
    echo json_encode($response);
    exit;
}

$schemaId = $_POST['schema_id'];
debug_log("Versuche Schema zu aktivieren: $schemaId");

// Pfad zum Schemas-Verzeichnis
$schemaFolder = __DIR__ . '/../../config/grading-schemas/';
$activeSchemaFile = __DIR__ . '/../../config/active_schema.txt';

debug_log("Schema-Verzeichnis: $schemaFolder");
debug_log("Aktive Schema-Datei: $activeSchemaFile");

// Prüfe, ob das Verzeichnis existiert
if (!is_dir($schemaFolder)) {
    debug_log("Fehler: Verzeichnis existiert nicht: $schemaFolder");
    $response['error'] = 'Das Verzeichnis für Notenschema-Dateien existiert nicht.';
    echo json_encode($response);
    exit;
}

// Überprüfe die genaue Datei
$schemaFile = $schemaFolder . $schemaId . ".txt";
debug_log("Suche nach exakter Schema-Datei: $schemaFile");

if (!file_exists($schemaFile)) {
    debug_log("Exakte Datei nicht gefunden, suche nach Alternativen...");
    
    // Alle Dateien im Verzeichnis auflisten
    $allFiles = glob($schemaFolder . "*.txt");
    debug_log("Alle gefundenen Dateien im Verzeichnis:", $allFiles);
    
    // Suche nach der richtigen Datei
    $found = false;
    foreach ($allFiles as $file) {
        $basename = basename($file, ".txt");
        debug_log("Prüfe $file (Basename: $basename) gegen gesuchte ID: $schemaId");
        
        if ($basename === $schemaId) {
            $schemaFile = $file;
            $found = true;
            debug_log("Passende Datei gefunden: $schemaFile");
            break;
        }
    }
    
    if (!$found) {
        debug_log("Fehler: Keine Schema-Datei gefunden für ID: $schemaId");
        $response['error'] = 'Keine Schema-Datei gefunden für diese ID';
        echo json_encode($response);
        exit;
    }
}

debug_log("Schema-Datei gefunden: $schemaFile");

// Speichere die ID als aktives Schema
if (file_put_contents($activeSchemaFile, $schemaId) === false) {
    debug_log("Fehler: Konnte Schema nicht als aktiv speichern");
    $response['error'] = 'Konnte Schema nicht als aktiv speichern';
    echo json_encode($response);
    exit;
}

debug_log("Schema wurde als aktiv gespeichert: $schemaId");

// Lade die Schema-Einträge
$schemaContent = file_get_contents($schemaFile);
debug_log("Roher Datei-Inhalt:", $schemaContent);

// Prüfe Datei-Inhalt und Datei-Encoding
debug_log("Datei-Größe: " . filesize($schemaFile) . " Bytes");
debug_log("Datei-Kodierung prüfen...");
$encoding = mb_detect_encoding($schemaContent, ["UTF-8", "ISO-8859-1", "ASCII"], true);
debug_log("Erkannte Kodierung: $encoding");

// Wir lesen die Datei direkt mit fopen, um Probleme mit Zeilenumbrüchen und Encoding zu vermeiden
$entries = [];
$fileHandle = fopen($schemaFile, 'r');

if ($fileHandle) {
    debug_log("Datei direkt mit fopen geöffnet");
    $lineNumber = 0;
    
    while (($line = fgets($fileHandle)) !== false) {
        $lineNumber++;
        $line = trim($line);
        debug_log("Zeile $lineNumber: '$line'");
        
        if (empty($line)) {
            debug_log("Leere Zeile übersprungen");
            continue;
        }
        
        // Prüfe auf verschiedene Trennzeichen
        if (strpos($line, ':') !== false) {
            list($grade, $threshold) = explode(':', $line);
            $entries[] = [
                'grade' => trim($grade),
                'threshold' => (float) trim($threshold)
            ];
            debug_log("Zeile mit ':' verarbeitet - Note: " . trim($grade) . ", Schwelle: " . (float) trim($threshold));
        } elseif (strpos($line, '=') !== false) {
            list($grade, $threshold) = explode('=', $line);
            $entries[] = [
                'grade' => trim($grade),
                'threshold' => (float) trim($threshold)
            ];
            debug_log("Zeile mit '=' verarbeitet - Note: " . trim($grade) . ", Schwelle: " . (float) trim($threshold));
        } else {
            debug_log("Unerwartetes Zeilenformat: $line");
        }
    }
    
    if (!feof($fileHandle)) {
        debug_log("Warnung: Fehler beim Lesen der Datei");
    }
    
    fclose($fileHandle);
} else {
    debug_log("Konnte Datei nicht mit fopen öffnen");
    
    // Fallback zur alten Methode
    $lines = explode("\n", $schemaContent);
    debug_log("Fallback: Verarbeite " . count($lines) . " Zeilen aus explode()");
    
    foreach ($lines as $i => $line) {
        $line = trim($line);
        if (!empty($line)) {
            debug_log("Zeile $i: '$line'");
            
            if (strpos($line, ':') !== false) {
                list($grade, $threshold) = explode(':', $line);
                $entries[] = [
                    'grade' => trim($grade),
                    'threshold' => (float) trim($threshold)
                ];
                debug_log("Verarbeitet: Note=" . trim($grade) . ", Schwelle=" . (float) trim($threshold));
            }
        }
    }
}

debug_log("Insgesamt " . count($entries) . " Einträge extrahiert:", $entries);

// Sortiere Einträge nach Schwellenwert (absteigend)
usort($entries, function($a, $b) {
    return $b['threshold'] <=> $a['threshold'];
});

// Erstelle Schema-Objekt für die Antwort
$filename = basename($schemaFile);
$displayName = str_replace(['_', '.txt'], [' ', ''], substr($filename, 3));

$schema = [
    'id' => $schemaId,
    'name' => $displayName,
    'file' => $schemaFile,
    'active' => true,
    'entries' => $entries
];

$response['success'] = true;
$response['schema'] = $schema;

debug_log("Schema erfolgreich aktiviert mit " . count($entries) . " Einträgen", $schema);

// Gib die Antwort zurück
echo json_encode($response); 