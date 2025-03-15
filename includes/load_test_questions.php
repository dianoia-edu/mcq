<?php
session_start();

// Überprüfe, ob der Benutzer als Lehrer eingeloggt ist
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    die(json_encode(['success' => false, 'error' => 'Nicht autorisiert']));
}

// Überprüfe, ob eine Datei angegeben wurde
if (!isset($_POST['file']) || empty($_POST['file'])) {
    die(json_encode(['success' => false, 'error' => 'Keine Datei angegeben']));
}

$file = $_POST['file'];

// Sicherheitscheck: Stelle sicher, dass die Datei im tests-Verzeichnis liegt
$testsDir = realpath(dirname(__DIR__) . '/tests');
$requestedFile = realpath($file);

if ($requestedFile === false || strpos($requestedFile, $testsDir) !== 0) {
    die(json_encode(['success' => false, 'error' => 'Ungültige Datei']));
}

// Versuche die XML-Datei zu laden
try {
    $xml = simplexml_load_file($requestedFile);
    if ($xml === false) {
        throw new Exception('XML konnte nicht geladen werden');
    }

    $questions = [];

    // Extrahiere die Fragen und Antworten
    foreach ($xml->questions->question as $question) {
        $questionData = [
            'text' => (string)$question->text,
            'answers' => []
        ];

        // Verarbeite die Antworten
        if (isset($question->answers)) {
            foreach ($question->answers->answer as $answer) {
                $questionData['answers'][] = [
                    'text' => (string)$answer->text,
                    'correct' => ((string)$answer->correct === '1')
                ];
            }
        }

        $questions[] = $questionData;
    }

    // Sende die Fragen zurück
    echo json_encode([
        'success' => true,
        'questions' => $questions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 