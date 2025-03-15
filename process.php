<?php
// Starte Output-Buffering
ob_start();

// Temporäre Debug-Ausgaben
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';
require_once 'check_test_attempts.php';
require_once 'auswertung.php';
require_once 'includes/TestDatabase.php';

error_log("=== Start der Testverarbeitung ===");

// Überprüfe, ob alle erforderlichen Session-Variablen vorhanden sind
if (!isset($_SESSION['test_file']) || !isset($_SESSION['test_code']) || !isset($_SESSION['student_name']) || !isset($_SESSION['original_questions'])) {
    error_log("Fehlende Session-Variablen in process.php");
    error_log("Session-Variablen: " . print_r($_SESSION, true));
    $_SESSION['error'] = "Ungültige Sitzung. Bitte starten Sie den Test neu.";
    header("Location: index.php");
    exit();
}

error_log("Session-Variablen sind vorhanden");

// Validiere den Schülernamen
if (!preg_match('/^[a-zA-Z0-9\s\-_]{2,50}$/', $_SESSION['student_name'])) {
    error_log("Ungültiger Schülername: " . $_SESSION['student_name']);
    $_SESSION['error'] = "Ungültiger Schülername. Bitte verwenden Sie nur Buchstaben, Zahlen und Leerzeichen.";
    header("Location: index.php");
    exit();
}

// Überprüfe, ob der Test bereits heute absolviert wurde
if (hasCompletedTestToday($_SESSION['test_code'], $_SESSION['student_name'])) {
    error_log("Doppelter Testversuch von: " . $_SESSION['student_name']);
    $_SESSION['error'] = "Sie haben diesen Test heute bereits absolviert.";
    header("Location: index.php");
    exit();
}

// Überprüfe, ob die Testdatei existiert und lesbar ist
if (!file_exists($_SESSION['test_file']) || !is_readable($_SESSION['test_file'])) {
    error_log("Testdatei nicht gefunden oder nicht lesbar: " . $_SESSION['test_file']);
    $_SESSION['error'] = "Der Test konnte nicht geladen werden. Bitte kontaktieren Sie den Administrator.";
    header("Location: index.php");
    exit();
}

// Lade den Original-Test
$originalXml = simplexml_load_file($_SESSION['test_file']);
if ($originalXml === false) {
    error_log("XML-Parsing-Fehler in: " . $_SESSION['test_file']);
    $_SESSION['error'] = "Fehler beim Laden des Tests.";
    header("Location: index.php");
    exit();
}

// Erstelle eine Kopie des XML für die Antworten
$answerXml = clone $originalXml;

// Füge den Schülernamen und Zeitstempel hinzu
$answerXml->addChild('schuelername', htmlspecialchars($_SESSION['student_name']));
$answerXml->addChild('abgabezeit', date('Y-m-d H:i:s'));

// Debug-Ausgabe
error_log("Verarbeite Testabgabe von: " . $_SESSION['student_name']);

// Verarbeite die Antworten
foreach ($_POST as $key => $value) {
    if (strpos($key, 'answer_') === 0) {
        // Extrahiere den Fragenindex
        $questionIndex = substr($key, 7);
        
        // Validiere den Fragenindex
        if (!is_numeric($questionIndex) || $questionIndex < 0) {
            error_log("Ungültiger Fragenindex: " . $questionIndex);
            continue;
        }
        
        // Wenn es sich um eine Checkbox-Antwort handelt (Array)
        if (is_array($value)) {
            foreach ($value as $answerIndex) {
                // Validiere die Antwortnummer
                if (!is_numeric($answerIndex) || $answerIndex < 0) {
                    error_log("Ungültige Checkbox-Antwort: " . $answerIndex);
                    continue;
                }
                
                // Finde die ursprüngliche Antwort in der XML
                foreach ($answerXml->questions->question as $question) {
                    if ((string)$question['nr'] === $questionIndex) {
                        foreach ($question->answers->answer as $answer) {
                            if ((string)$answer['nr'] === $answerIndex) {
                                $answer->addChild('schuelerantwort', '1');
                            }
                        }
                    }
                }
            }
        } else {
            // Wenn es sich um eine Radio-Button-Antwort handelt
            if (!is_numeric($value) && !preg_match('/^[A-D]$/', $value)) {
                error_log("Ungültige Radio-Antwort: " . $value);
                continue;
            }
            
            foreach ($answerXml->questions->question as $question) {
                if ((string)$question['nr'] === $questionIndex) {
                    foreach ($question->answers->answer as $answer) {
                        if ((string)$answer['nr'] === $value) {
                            $answer->addChild('schuelerantwort', '1');
                        }
                    }
                }
            }
        }
    }
}

// Setze alle nicht beantworteten Antworten auf 0
foreach ($answerXml->questions->question as $question) {
    foreach ($question->answers->answer as $answer) {
        if (!isset($answer->schuelerantwort)) {
            $answer->addChild('schuelerantwort', '0');
        }
    }
}

// Erstelle den Ordnernamen basierend auf Zugangscode und Datum
$date = date('Y-m-d');
$folderName = $_SESSION['test_code'] . '_' . $date;
$resultsDir = 'results/' . $folderName;

// Erstelle den Ordner, falls er nicht existiert
if (!file_exists($resultsDir)) {
    if (!mkdir($resultsDir, 0777, true)) {
        error_log("Fehler beim Erstellen des Ergebnisordners: " . $resultsDir);
        $_SESSION['error'] = "Fehler beim Speichern des Tests. Bitte kontaktieren Sie den Administrator.";
        header("Location: index.php");
        exit();
    }
}

// Erstelle den Dateinamen
$timestamp = date('Y-m-d_H-i-s');
$filename = $_SESSION['test_code'] . '_' . $_SESSION['student_name'] . '_' . $timestamp . '.xml';
$filepath = $resultsDir . '/' . $filename;

// Formatiere das XML für bessere Lesbarkeit
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($answerXml->asXML());

// Speichere die XML-Datei
error_log("Versuche XML-Datei zu speichern: " . $filepath);
if (!$dom->save($filepath)) {
    error_log("Fehler beim Speichern der XML-Datei: " . $filepath);
    $_SESSION['error'] = "Fehler beim Speichern des Tests.";
    header("Location: index.php");
    exit();
}
error_log("XML-Datei erfolgreich gespeichert");

// Markiere den Test als absolviert
error_log("Markiere Test als absolviert");
if (!markTestAsCompleted($_SESSION['test_code'], $_SESSION['student_name'])) {
    error_log("Fehler beim Markieren des Tests als abgeschlossen");
    $_SESSION['error'] = "Fehler beim Speichern des Teststatus.";
    header("Location: index.php");
    exit();
}
error_log("Test erfolgreich als absolviert markiert");

// Führe die Auswertung anhand der gespeicherten XML-Datei durch
error_log("Starte Testauswertung anhand der gespeicherten XML-Datei");
$results = evaluateTest($filepath);
if ($results === false) {
    error_log("Fehler bei der Auswertung des Tests: " . $filepath);
    $_SESSION['error'] = "Fehler bei der Auswertung des Tests.";
    header("Location: index.php");
    exit();
}
error_log("Testauswertung erfolgreich: " . print_r($results, true));

// Lade das Notenschema für die Notenberechnung
error_log("Lade Notenschema");
$schema = loadGradeSchema();
error_log("Notenschema geladen: " . print_r($schema, true));
$grade = calculateGrade($results['percentage'], $schema);
error_log("Note berechnet: " . $grade);

// Speichere die Ergebnisse in der Datenbank
try {
    error_log("Speichere Ergebnisse in der Datenbank");
    $testDb = new TestDatabase();
    
    // Bestimme den Antworttyp basierend auf den Fragen
    $answerType = 'single';
    $multipleChoiceFound = false;
    $singleChoiceFound = false;
    
    foreach ($answerXml->questions->question as $question) {
        $correctCount = 0;
        foreach ($question->answers->answer as $answer) {
            if ((int)$answer->correct === 1) {
                $correctCount++;
            }
        }
        if ($correctCount > 1) {
            $multipleChoiceFound = true;
        } else {
            $singleChoiceFound = true;
        }
    }
    
    if ($multipleChoiceFound && $singleChoiceFound) {
        $answerType = 'mixed';
    } elseif ($multipleChoiceFound) {
        $answerType = 'multiple';
    }
    
    // Konvertiere SimpleXMLElement zu Array für die Zählung
    $questions = iterator_to_array($originalXml->questions->question);
    $totalAnswers = 0;
    foreach ($questions as $question) {
        $totalAnswers += count($question->answers->answer);
    }
    
    // Verwende den Basis-Code für die Datenbank
    $baseCode = getBaseCode($_SESSION['test_code']);
    
    // Bereite die Testdaten vor
    $testData = [
        'access_code' => $baseCode, // Speichere den Basis-Code
        'title' => (string)$originalXml->title,
        'question_count' => count($questions),
        'answer_count' => $totalAnswers,
        'answer_type' => $answerType,
        'student_name' => $_SESSION['student_name'] . (isAdminCode($_SESSION['test_code']) ? ' (Admin)' : ''),
        'xml_file_path' => $filepath,
        'points_achieved' => $results['achieved'],
        'points_maximum' => $results['max'],
        'percentage' => $results['percentage'],
        'grade' => $grade,
        'started_at' => $_SESSION['test_start_time'] ?? date('Y-m-d H:i:s')
    ];
    
    $testDb->saveTestAttempt($testData);
    error_log("Ergebnisse erfolgreich in der Datenbank gespeichert");
} catch (Exception $e) {
    error_log("Fehler beim Speichern in der Datenbank: " . $e->getMessage());
    // Fahre trotz Datenbankfehler fort, da die XML-Datei bereits gespeichert wurde
}

// Speichere die Ergebnisse in der Session
error_log("Speichere Ergebnisse in der Session");
$_SESSION['test_results'] = [
    'achieved' => $results['achieved'],
    'max' => $results['max'],
    'percentage' => $results['percentage'],
    'grade' => $grade
];
error_log("Session nach Speichern der Ergebnisse: " . print_r($_SESSION, true));

// Weiterleitung zur Ergebnisseite
error_log("Weiterleitung zur Ergebnisseite");
header("Location: result.php");
exit();

error_log("=== Ende der Testverarbeitung ===");
?>