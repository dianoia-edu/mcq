<?php
// Aktiviere Error Reporting für Entwicklung
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Setze Header für JSON
header('Content-Type: application/json');

try {
    // Pfad zum Tests-Verzeichnis
    $tests_dir = dirname(__DIR__) . '/tests/';
    
    // Prüfe, ob das Verzeichnis existiert
    if (!is_dir($tests_dir)) {
        throw new Exception('Tests-Verzeichnis nicht gefunden');
    }
    
    // Hole alle XML-Dateien im Tests-Verzeichnis
    $files = glob($tests_dir . '*.xml');
    
    // Sortiere die Dateien nach Änderungsdatum (neueste zuerst)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $tests = [];
    
    // Verarbeite jede Datei
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Lade den XML-Inhalt
        $xml_content = file_get_contents($file);
        
        // Parse XML
        $xml = simplexml_load_string($xml_content);
        
        if ($xml) {
            // Extrahiere Metadaten
            $title = (string)$xml->title;
            $accessCode = (string)$xml->access_code;
            
            // Füge Test zur Liste hinzu (ohne .xml-Erweiterung)
            $tests[] = [
                'name' => basename($filename, '.xml'),
                'title' => $title,
                'accessCode' => $accessCode
            ];
        }
    }
    
    // Erfolg melden
    echo json_encode([
        'success' => true,
        'tests' => $tests
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