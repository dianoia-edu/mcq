<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Überprüfe erforderliche Parameter
    if (!isset($_POST['title']) || !isset($_POST['access_code']) || !isset($_POST['xml_content']) || 
        !isset($_POST['question_count']) || !isset($_POST['answer_count']) || !isset($_POST['answer_type']) ||
        !isset($_POST['filename'])) {
        throw new Exception('Fehlende Parameter');
    }
    
    $title = $_POST['title'];
    $access_code = $_POST['access_code'];
    $xml_content = $_POST['xml_content'];
    $question_count = intval($_POST['question_count']);
    $answer_count = intval($_POST['answer_count']);
    $answer_type = $_POST['answer_type'];
    $filename = $_POST['filename'];
    
    // Definiere das Tests-Verzeichnis
    $tests_dir = __DIR__ . '/../tests';
    
    // Erstelle das Verzeichnis, falls es nicht existiert
    if (!file_exists($tests_dir)) {
        mkdir($tests_dir, 0777, true);
    }
    
    // Prüfe ob das Verzeichnis schreibbar ist
    if (!is_writable($tests_dir)) {
        throw new Exception('Verzeichnis nicht schreibbar: ' . $tests_dir);
    }
    
    // Validiere das XML
    $dom = new DOMDocument();
    if (!$dom->loadXML($xml_content)) {
        throw new Exception('Ungültiges XML-Format');
    }
    
    // Formatiere das XML
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    
    // Speichere das XML
    $xml_file_path = $tests_dir . '/' . $filename . '.xml';
    
    // Stelle sicher, dass die XML-Deklaration vorhanden ist
    $xml_content = $dom->saveXML();
    if (strpos($xml_content, '<?xml') !== 0) {
        $xml_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml_content;
    }
    
    if (file_put_contents($xml_file_path, $xml_content) === false) {
        throw new Exception('Fehler beim Speichern der XML-Datei');
    }
    
    if (!file_exists($xml_file_path)) {
        throw new Exception('XML-Datei konnte nicht erstellt werden: ' . $xml_file_path);
    }
    
    // Sende JSON-Antwort
    echo json_encode([
        'success' => true,
        'message' => 'Test erfolgreich gespeichert',
        'filename' => basename($xml_file_path)
    ]);
    
} catch (Exception $e) {
    error_log("Error in save_test_xml.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 