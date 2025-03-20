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
    // Stelle IMMER sicher, dass der Zugangscode ein String ist
    $accessCode = (string)$_POST['access_code'];

    // Zusätzliches Typensicherheit und Validierung
    if (strlen($accessCode) !== 3) {
        throw new Exception('Der Zugangscode muss genau 3 Zeichen lang sein');
    }

    $questions = json_decode($_POST['questions'], true);
    
    // Bessere Abfrage für force_overwrite
    $forceOverwrite = false;
    if (isset($_POST['force_overwrite'])) {
        $forceOverwriteValue = $_POST['force_overwrite'];
        $forceOverwrite = ($forceOverwriteValue === 'true' || $forceOverwriteValue === true || 
                          $forceOverwriteValue === '1' || $forceOverwriteValue === 1);
    }

    // Debug-Ausgabe für Troubleshooting
    error_log('SAVE_TEST.PHP DEBUGGING:');
    error_log('POST-Parameter: ' . print_r($_POST, true));
    error_log('Force Overwrite: ' . ($forceOverwrite ? 'TRUE' : 'FALSE'));
    error_log('Zugangscode: ' . $accessCode . ' (Typ: ' . gettype($accessCode) . ')');

    // Durchsuche alle XML-Dateien nach dem gleichen Zugangscode
    $allFiles = glob($testsDir . '/*.xml');
    $matchingFiles = [];

    foreach ($allFiles as $file) {
        // Versuche, die XML-Datei zu laden
        $xmlContent = @file_get_contents($file);
        if ($xmlContent) {
            $xml = @simplexml_load_string($xmlContent);
            if ($xml && isset($xml->access_code)) {
                // Erzwinge String-Konvertierung für beide Seiten und trimme Leerzeichen
                $fileCode = trim((string)$xml->access_code);
                if ($fileCode === $accessCode) {
                    $matchingFiles[] = $file;
                    error_log("Matching file found: " . basename($file) . " with code '" . $fileCode . "' === '" . $accessCode . "'");
                } else {
                    error_log("No match: " . basename($file) . " with code '" . $fileCode . "' !== '" . $accessCode . "'");
                }
            }
        }
    }

    error_log("Found " . count($matchingFiles) . " files with the same access code: " . $accessCode);

    if (!empty($matchingFiles) && !$forceOverwrite) {
        // Wenn es bereits Tests gibt und kein Überschreiben erzwungen wird
        $existingFile = $matchingFiles[0];
        $existingXml = simplexml_load_string(file_get_contents($existingFile));
        
        if ($existingXml) {
            $oldTitle = (string)$existingXml->title;
            error_log("Found existing test: $oldTitle with code " . (string)$existingXml->access_code);
            
            // Sende Info zurück für Überschreiben-Dialog
            echo json_encode([
                'success' => false,
                'need_confirmation' => true,
                'existing_test' => [
                    'filename' => basename($existingFile),
                    'title' => $oldTitle,
                    'access_code' => $accessCode
                ],
                'message' => 'Es existiert bereits ein Test mit diesem Zugangscode.'
            ]);
            exit;
        }
    } else if (!empty($matchingFiles) && $forceOverwrite) {
        // Lösche alle existierenden Dateien mit diesem Zugangscode
        error_log("DELETING EXISTING FILES due to forceOverwrite=true");
        
        $deleted = 0;
        foreach ($matchingFiles as $file) {
            if (file_exists($file)) {
                error_log("Deleting: $file");
                if (unlink($file)) {
                    $deleted++;
                    error_log("Successfully deleted: $file");
                } else {
                    error_log("FAILED to delete: $file (Permissions: " . decoct(fileperms($file)) . ")");
                }
            }
        }
        
        error_log("Deleted $deleted out of " . count($matchingFiles) . " files");
    }

    // Erstelle den Dateinamen für den neuen Test
    $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
    // Stelle sicher, dass der Zugangscode als String behandelt wird
    $filename = (string)$accessCode . "_" . $cleanTitle . ".xml";
    $fullPath = $testsDir . '/' . $filename;
    error_log("Saving new test to: $fullPath");
    
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
    
    if (!empty($questions)) {
        $xml->addChild('answer_count', count($questions[0]['answers'] ?? 0));
    } else {
        $xml->addChild('answer_count', 0);
    }
    
    $xml->addChild('answer_type', 'mixed');
    
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

    // Überprüfe, ob die Datei erfolgreich erstellt wurde
    if (!file_exists($fullPath)) {
        throw new Exception('Datei konnte nicht erstellt werden: ' . $fullPath);
    }

    error_log("Successfully saved test: $fullPath");
    
    // Sende Erfolgsantwort zurück
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