<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Überprüfe, ob der Test-Name übergeben wurde
    if (!isset($_GET['test_name']) || empty($_GET['test_name'])) {
        throw new Exception('Kein Test-Name angegeben');
    }
    
    $test_name = basename($_GET['test_name']);
    
    // Definiere das Tests-Verzeichnis
    $tests_dir = __DIR__ . '/../tests';
    
    // Überprüfe, ob der Dateiname bereits auf .xml endet
    if (!preg_match('/\.xml$/i', $test_name)) {
        $xml_file = $tests_dir . '/' . $test_name . '.xml';
    } else {
        $xml_file = $tests_dir . '/' . $test_name;
    }
    
    if (file_exists($xml_file)) {
        // Lese den Inhalt der XML-Datei
        $xml_content = file_get_contents($xml_file);
        
        // Validiere das XML
        $xml = simplexml_load_string($xml_content);
        if ($xml === false) {
            throw new Exception('Fehler beim Parsen der XML-Datei');
        }
        
        echo json_encode([
            'success' => true,
            'xml_content' => $xml_content,
            'source' => 'xml'
        ]);
    } else {
        throw new Exception('Test-Datei nicht gefunden: ' . $xml_file);
    }
} catch (Exception $e) {
    error_log("Error in load_test.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 