<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Definiere das Tests-Verzeichnis
    $testsDir = __DIR__ . '/../tests';
    
    // Erstelle das Verzeichnis, falls es nicht existiert
    if (!file_exists($testsDir)) {
        mkdir($testsDir, 0777, true);
    }

    // Überprüfe erforderliche Parameter
    if (!isset($_POST['title']) || !isset($_POST['access_code']) || !isset($_POST['questions'])) {
        throw new Exception('Fehlende Parameter');
    }

    $title = $_POST['title'];
    $accessCode = $_POST['access_code'];
    $questions = json_decode($_POST['questions'], true);

    // Erstelle den Dateinamen aus dem Zugangscode
    $filename = $accessCode . '_' . time() . '.xml';
    $fullPath = $testsDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    error_log('Trying to save test to: ' . $fullPath);
    
    // Prüfe ob das Verzeichnis schreibbar ist
    if (!is_writable($testsDir)) {
        throw new Exception('Verzeichnis nicht schreibbar: ' . $testsDir);
    }

    // Erstelle XML-Inhalt
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><test></test>');
    
    // Füge Metadaten hinzu
    $xml->addChild('title', htmlspecialchars($title));
    $xml->addChild('access_code', htmlspecialchars($accessCode));
    $xml->addChild('question_count', count($questions));
    $xml->addChild('answer_count', count($questions[0]['answers']));
    $xml->addChild('answer_type', 'mixed'); // Standardmäßig mixed, da im Editor verschiedene Antworttypen möglich sind
    
    // Erstelle Fragen-Container
    $questionsNode = $xml->addChild('questions');
    
    // Füge Fragen hinzu
    foreach ($questions as $qIndex => $q) {
        $question = $questionsNode->addChild('question');
        $question->addAttribute('nr', $qIndex + 1);
        $question->addChild('text', htmlspecialchars($q['text']));
        
        // Erstelle Antworten-Container
        $answersNode = $question->addChild('answers');
        
        // Füge Antworten hinzu
        foreach ($q['answers'] as $aIndex => $a) {
            $answer = $answersNode->addChild('answer');
            $answer->addAttribute('nr', $aIndex + 1);
            $answer->addChild('text', htmlspecialchars($a['text']));
            $answer->addChild('correct', $a['correct'] ? '1' : '0');
        }
    }

    // Formatiere XML für bessere Lesbarkeit
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    
    // Speichere die formatierte XML-Datei
    if ($dom->save($fullPath) === false) {
        throw new Exception('Fehler beim Speichern der XML-Datei');
    }

    // Überprüfe die Formatierung
    $savedContent = file_get_contents($fullPath);
    if (strpos($savedContent, "\n") === false) {
        // Wenn keine Zeilenumbrüche vorhanden sind, versuche es erneut mit expliziter Formatierung
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($savedContent);
        $dom->save($fullPath);
    }

    if (!file_exists($fullPath)) {
        throw new Exception('Datei konnte nicht erstellt werden: ' . $fullPath);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Test erfolgreich gespeichert',
        'filename' => basename($fullPath),
        'access_code' => $accessCode,
        'title' => $title
    ]);

} catch (Exception $e) {
    error_log("Error in save_test.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 