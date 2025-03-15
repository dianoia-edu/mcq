<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Überprüfe erforderliche Parameter
    if (!isset($_POST['title']) || !isset($_POST['access_code']) || !isset($_POST['content']) || 
        !isset($_POST['question_count']) || !isset($_POST['answer_count']) || !isset($_POST['answer_type'])) {
        throw new Exception('Fehlende Parameter');
    }
    
    $title = $_POST['title'];
    $access_code = $_POST['access_code'];
    $content = $_POST['content'];
    $question_count = intval($_POST['question_count']);
    $answer_count = intval($_POST['answer_count']);
    $answer_type = $_POST['answer_type'];
    
    // Lade die Konvertierungsfunktion
    require_once(__DIR__ . '/../includes/functions/text_to_xml_converter.php');
    
    // Konvertiere den Text in XML
    $dom = convertTextToXML($content, $access_code, $question_count, $answer_count, $answer_type, $title);
    
    // Konvertiere das DOM-Objekt in einen String
    $xml_content = $dom->saveXML();
    
    // Sende JSON-Antwort
    echo json_encode([
        'success' => true,
        'xml_content' => $xml_content
    ]);
    
} catch (Exception $e) {
    error_log("Error in preview_test.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 