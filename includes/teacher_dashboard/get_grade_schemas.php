<?php
/**
 * get_grade_schemas.php
 * Lädt alle verfügbaren Notenschema-Dateien und gibt sie als JSON zurück
 */

// Cache verhindern
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');

// Debug-Funktion mit zusätzlicher Datei-Protokollierung
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
    'schemas' => [],
    'activeSchema' => null,
    'error' => null
];

// Pfad zu den Notenschema-Dateien
$schemaFolder = __DIR__ . '/../../config/grading-schemas/';
debug_log("Versuche, Schemas aus Verzeichnis zu laden: $schemaFolder");

// Pfad zur Datei, die das aktive Schema enthält
$activeSchemaFile = __DIR__ . '/../../config/active_schema.txt';
debug_log("Aktives Schema sollte in Datei zu finden sein: $activeSchemaFile");

// Lade das aktive Schema
$activeSchemaId = '';
if (file_exists($activeSchemaFile)) {
    $activeSchemaId = trim(file_get_contents($activeSchemaFile));
    debug_log("Aktives Schema aus Datei: $activeSchemaId");
} else {
    debug_log("Aktive Schema-Datei nicht gefunden");
}

// Prüfe, ob das Verzeichnis existiert
if (!is_dir($schemaFolder)) {
    debug_log("Notenschema-Verzeichnis nicht gefunden");
    $response['error'] = 'Notenschema-Verzeichnis nicht gefunden';
    echo json_encode($response);
    exit;
}

// Lade Notenschema-Dateien - verwende realpaths
$schemaFiles = glob($schemaFolder . '*.txt');
debug_log("Gefundene Schema-Dateien:", $schemaFiles);

if (empty($schemaFiles)) {
    debug_log("Keine Notenschema-Dateien gefunden");
    $response['error'] = 'Keine Notenschema-Dateien gefunden';
    echo json_encode($response);
    exit;
}

// Mehr Debugging beim Laden der Schemas
debug_log("Starte get_grade_schemas.php");
foreach ($schemaFiles as $schemaFile) {
    // Verwende realpath um sicherzustellen, dass wir den korrekten Dateipfad haben
    $schemaFile = realpath($schemaFile);
    
    $filename = basename($schemaFile);
    debug_log("Verarbeite Schema-Datei: $filename (Voller Pfad: $schemaFile)");
    
    $filenameParts = explode('_', $filename);
    
    if (count($filenameParts) >= 2) {
        $schemaId = str_replace('.txt', '', $filename);
        $displayName = str_replace(['_', '.txt'], [' ', ''], substr($filename, 3));
        
        debug_log("Schema-ID: $schemaId, Anzeigename: $displayName");
        
        // Direktes Lesen mit file_get_contents, Cache löschen
        clearstatcache(true, $schemaFile);
        $schemaContent = file_get_contents($schemaFile);
        
        // Schema-Einträge mit fopen lesen
        $entries = [];
        $fileHandle = fopen($schemaFile, 'r');
        
        if ($fileHandle) {
            debug_log("Datei direkt mit fopen geöffnet: $schemaFile");
            $lineNumber = 0;
            
            while (($line = fgets($fileHandle)) !== false) {
                $lineNumber++;
                $line = trim($line);
                debug_log("Zeile $lineNumber: '$line'");
                
                if (empty($line)) {
                    debug_log("Leere Zeile übersprungen");
                    continue;
                }
                
                // Prüfe auf das Trennzeichen
                if (strpos($line, ':') !== false) {
                    list($grade, $threshold) = explode(':', $line);
                    $entries[] = [
                        'grade' => trim($grade),
                        'threshold' => (float) trim($threshold)
                    ];
                    debug_log("Schema '$schemaId' Zeile $lineNumber: Note {$grade} - Schwelle {$threshold}%");
                }
            }
            
            fclose($fileHandle);
        } else {
            debug_log("Konnte Datei nicht öffnen: $schemaFile");
        }
        
        $schema = [
            'id' => $schemaId,
            'name' => $displayName,
            'file' => $schemaFile,
            'active' => $schemaId === $activeSchemaId,
            'entries' => $entries
        ];
        
        $response['schemas'][] = $schema;
        
        // Wenn dies das aktive Schema ist, setze es in der Antwort
        if ($schema['active']) {
            $response['activeSchema'] = $schema;
            debug_log("Aktives Schema gefunden und geladen: '{$schema['name']}' mit ID '{$schema['id']}' und " . count($entries) . " Einträgen", $schema);
        }
    }
}

// Keine Schemas gefunden?
if (empty($response['schemas'])) {
    debug_log("Keine gültigen Schemas gefunden");
    $response['error'] = 'Keine gültigen Notenschema-Dateien gefunden';
    echo json_encode($response);
    exit;
}

// Wenn kein aktives Schema definiert ist, setze das erste als aktiv
if (empty($response['activeSchema']) && !empty($response['schemas'])) {
    debug_log("Kein aktives Schema definiert, setze das erste Schema als aktiv");
    $response['schemas'][0]['active'] = true;
    $response['activeSchema'] = $response['schemas'][0];
    
    // Speichere das Schema als aktiv
    file_put_contents($activeSchemaFile, $response['schemas'][0]['id']);
    debug_log("Schema {$response['schemas'][0]['id']} als aktiv gespeichert");
}

$response['success'] = true;
debug_log("Antwort fertig, " . count($response['schemas']) . " Schemas gefunden", 
          ['activeId' => $activeSchemaId, 'activeSchema' => $response['activeSchema'] ? $response['activeSchema']['id'] : null]);

// Gib die Antwort zurück
echo json_encode($response); 