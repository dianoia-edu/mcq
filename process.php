<?php
// Starte Output-Buffering
ob_start();

// Temporäre Debug-Ausgaben
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'check_test_attempts.php';
require_once 'auswertung.php';
require_once 'includes/TestDatabase.php';
require_once 'includes/seb_detection.php';

// SEB-Erkennung und Session-Markierung
markSessionAsSEB();

// Zusätzliche Fehlerprotokollierung für problematische Fälle
error_log("=== Start der Testverarbeitung ===");
error_log("Browser-Info: " . $_SERVER['HTTP_USER_AGENT']);
error_log("Session-ID: " . session_id());
error_log("Formular-Daten: " . print_r($_POST, true));

// Diese Funktion wandelt Umlaute korrekt um und behält Leerzeichen und Bindestriche
function sanitizeStudentName($name) {
    // Debug-Ausgabe für Fehlersuche
    error_log("Sanitizing student name: " . $name);
    
    // Umlaute und Sonderzeichen korrekt umwandeln
    $search = array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß');
    $replace = array('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss');
    $name = str_replace($search, $replace, $name);
    
    // Für den Fall, dass UTF-8 und Multibyte-Zeichen verwendet werden
    if (function_exists('mb_convert_encoding')) {
        // Konvertiere in ASCII, um sicherzustellen, dass alle Sonderzeichen korrekt behandelt werden
        $name = mb_convert_encoding($name, 'ASCII', 'UTF-8');
    }
    
    // Nur erlaubte Zeichen behalten: Buchstaben, Zahlen, Leerzeichen, Bindestriche
    $name = preg_replace('/[^a-zA-Z0-9 \-]/', '_', $name);
    
    // Entferne führende und abschließende Unterstriche oder Leerzeichen
    $name = trim($name, "_ \t\n\r\0\x0B");
    
    // Stelle sicher, dass der Name nicht leer ist
    if (empty($name)) {
        $name = 'Schueler_' . substr(md5(microtime()), 0, 8);
        error_log("Leerer Name nach Sanitierung - verwende Standardnamen: " . $name);
    }
    
    error_log("Name nach Sanitierung: " . $name);
    return $name;
}

// Überprüfe, ob alle erforderlichen Session-Variablen vorhanden sind
if (!isset($_SESSION['test_file']) || !isset($_SESSION['test_code']) || !isset($_SESSION['student_name']) || !isset($_SESSION['original_questions'])) {
    error_log("Fehlende Session-Variablen in process.php");
    error_log("Session-Variablen: " . print_r($_SESSION, true));
    $_SESSION['error'] = "Ungültige Sitzung. Bitte starten Sie den Test neu.";
    header("Location: index.php");
    exit();
}

error_log("Session-Variablen sind vorhanden");

// WICHTIG: Sanitiere den Namen VOR der Validierung
$sanitizedName = sanitizeStudentName($_SESSION['student_name']);
error_log("Original Schülername: " . $_SESSION['student_name']);
error_log("Sanitized Schülername: " . $sanitizedName);

// Validiere den sanitierten Schülernamen
if (!preg_match('/^[a-zA-Z0-9\s\-_]{2,50}$/', $sanitizedName)) {
    error_log("Ungültiger Schülername nach Sanitierung: " . $sanitizedName);
    $_SESSION['error'] = "Ungültiger Schülername. Bitte verwenden Sie nur Buchstaben, Zahlen, Leerzeichen und Bindestriche.";
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

// DEBUG: Zeige ursprüngliche XML-Struktur
error_log("=== URSPRÜNGLICHE XML-STRUKTUR ===");
foreach ($answerXml->questions->question as $question) {
    error_log("Frage Nr: " . (string)$question['nr'] . " - " . (string)$question->text);
}

// KORREKTUR: Setze die Fragennummern in der XML-Datei auf die korrekte Reihenfolge
$questionIndex = 1;
foreach ($answerXml->questions->question as $question) {
    $question['nr'] = $questionIndex;
    error_log("Korrigierte Frage Nr: " . $questionIndex . " - " . (string)$question->text);
    $questionIndex++;
}

// Füge den Schülernamen und Zeitstempel hinzu
$answerXml->addChild('schuelername', htmlspecialchars($_SESSION['student_name']));
$answerXml->addChild('abgabezeit', date('Y-m-d H:i:s'));

// Debug-Ausgabe
error_log("Verarbeite Testabgabe von: " . $_SESSION['student_name']);

// Verarbeite die Antworten
error_log("=== VERARBEITUNG DER ANTWORTEN ===");
error_log("POST-Daten: " . print_r($_POST, true));
error_log("shuffled_questions: " . print_r($_SESSION['shuffled_questions'] ?? 'NICHT GESETZT', true));

// DEBUG-AUSGABEN FÜR BROWSER - temporär wieder aktiviert für Fehleranalyse
echo "<div class='debug-info' style='background: #f0f0f0; padding: 20px; margin: 20px; border: 2px solid #333; font-family: monospace; display: none;'>";
echo "<h3>🔍 DEBUG: Testverarbeitung</h3>";
echo "<p><strong>POST-Daten:</strong></p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";
echo "<p><strong>Session shuffled_questions:</strong></p>";
echo "<pre>" . print_r($_SESSION['shuffled_questions'] ?? 'NICHT GESETZT', true) . "</pre>";
echo "<p><strong>Session test_file:</strong> " . ($_SESSION['test_file'] ?? 'NICHT GESETZT') . "</p>";
echo "<p><strong>Session student_name:</strong> " . ($_SESSION['student_name'] ?? 'NICHT GESETZT') . "</p>";

// DEBUG: Zeige die Zuordnung zwischen POST-Daten und Fragen
echo "<h4>🔗 ZUORDNUNG POST-DATEN ZU FRAGEN:</h4>";
foreach ($_POST as $key => $value) {
    if (strpos($key, 'answer_') === 0) {
        $qIndex = substr($key, 7);
        $shuffledQuestions = $_SESSION['shuffled_questions'] ?? null;
        if ($shuffledQuestions && isset($shuffledQuestions[$qIndex])) {
            $originalQuestionNr = $shuffledQuestions[$qIndex]['originalQuestionNr'] ?? $shuffledQuestions[$qIndex]['nr'];
            echo "POST: $key = " . print_r($value, true) . " → Frage Nr: $originalQuestionNr<br>";
            
            // DEBUG: Zeige alle Antworten dieser Frage
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Alle Antworten dieser Frage:<br>";
            foreach ($shuffledQuestions[$qIndex]['answers'] as $answer) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Anzeige-Nr: " . $answer['nr'] . " → Original-Nr: " . $answer['originalAnswerNr'] . " | Text: " . substr($answer['text'], 0, 50) . "...<br>";
            }
        } else {
            echo "POST: $key = " . print_r($value, true) . " → FEHLER: Keine Zuordnung gefunden!<br>";
        }
    }
}
echo "</div>";

// JavaScript für Debug-Toggle in process.php
echo "<script>";
echo "document.addEventListener('DOMContentLoaded', function() {";
echo "  const debugElements = document.querySelectorAll('.debug-info');";
echo "  debugElements.forEach(element => {";
echo "    element.style.display = 'none';";
echo "  });";
echo "});";
echo "</script>";

foreach ($_POST as $key => $value) {
    if (strpos($key, 'answer_') === 0) {
        // Extrahiere den Fragenindex (jetzt qIndex basiert)
        $qIndex = substr($key, 7);
        
        error_log("Verarbeite Antwort: key=$key, qIndex=$qIndex, value=" . print_r($value, true));
        
        // Validiere den qIndex
        if (!is_numeric($qIndex) || $qIndex < 0) {
            error_log("Ungültiger qIndex: " . $qIndex);
            continue;
        }
        
        // Finde die entsprechende originale Fragenummer
        $shuffledQuestions = $_SESSION['shuffled_questions'] ?? null;
        if (!$shuffledQuestions || !isset($shuffledQuestions[$qIndex])) {
            error_log("Keine shuffled_questions in Session oder qIndex nicht gefunden: " . $qIndex);
            continue;
        }
        
        // Verwende die ursprüngliche Fragenummer aus der Session
        $originalQuestionNr = $shuffledQuestions[$qIndex]['originalQuestionNr'] ?? $shuffledQuestions[$qIndex]['nr'];
        error_log("qIndex $qIndex entspricht originaler Frage Nr: $originalQuestionNr");
        
        // Finde die ursprüngliche Antwortnummer für die gewählte Antwort
        $originalAnswerNr = null;
        foreach ($shuffledQuestions[$qIndex]['answers'] as $answer) {
            if ($answer['nr'] == $value) {
                $originalAnswerNr = $answer['originalAnswerNr'];
                break;
            }
        }
        error_log("Gewählte Antwort Nr: $value entspricht originaler Antwort Nr: $originalAnswerNr");
        
        // Wenn es sich um eine Checkbox-Antwort handelt (Array)
        if (is_array($value)) {
            error_log("Multiple-Choice-Frage: " . count($value) . " Antworten gewählt");
            error_log("POST-Daten für Multiple-Choice: " . print_r($value, true));
            foreach ($value as $answerIndex) {
                // Finde die ursprüngliche Antwortnummer für diese gewählte Antwort
                $originalAnswerNr = null;
                foreach ($shuffledQuestions[$qIndex]['answers'] as $answer) {
                    if ($answer['nr'] == $answerIndex) {
                        $originalAnswerNr = $answer['originalAnswerNr'];
                        break;
                    }
                }
                
                if (!$originalAnswerNr) {
                    error_log("Ungültige Checkbox-Antwort: $answerIndex - keine ursprüngliche Nummer gefunden");
                    continue;
                }
                
                // Finde die ursprüngliche Antwort in der XML
                error_log("Suche nach Frage Nr: $originalQuestionNr, ursprüngliche Antwort Nr: $originalAnswerNr");
                foreach ($answerXml->questions->question as $question) {
                    if ((string)$question['nr'] === $originalQuestionNr) {
                        error_log("Frage gefunden: " . (string)$question['nr']);
                        foreach ($question->answers->answer as $answer) {
                            if ((string)$answer['nr'] === $originalAnswerNr) {
                                error_log("Antwort gefunden und schuelerantwort=1 gesetzt: " . (string)$answer['nr']);
                                // Prüfe, ob schuelerantwort bereits existiert
                                if (isset($answer->schuelerantwort)) {
                                    $answer->schuelerantwort = '1';
                                } else {
                                    $answer->addChild('schuelerantwort', '1');
                                }
                                break; // WICHTIG: Nur diese eine Antwort setzen!
                            }
                        }
                    }
                }
            }
        } else {
            // Wenn es sich um eine Radio-Button-Antwort handelt
            if (!$originalAnswerNr) {
                error_log("Ungültige Radio-Antwort: $value - keine ursprüngliche Nummer gefunden");
                continue;
            }
            
            error_log("Suche nach Frage Nr: $originalQuestionNr, ursprüngliche Radio-Antwort: $originalAnswerNr");
            foreach ($answerXml->questions->question as $question) {
                if ((string)$question['nr'] === $originalQuestionNr) {
                    error_log("Frage gefunden: " . (string)$question['nr']);
                    foreach ($question->answers->answer as $answer) {
                        if ((string)$answer['nr'] === $originalAnswerNr) {
                            error_log("Radio-Antwort gefunden und schuelerantwort=1 gesetzt: " . (string)$answer['nr']);
                            // Prüfe, ob schuelerantwort bereits existiert
                            if (isset($answer->schuelerantwort)) {
                                $answer->schuelerantwort = '1';
                            } else {
                                $answer->addChild('schuelerantwort', '1');
                            }
                            break; // WICHTIG: Nur diese eine Antwort setzen!
                        }
                    }
                }
            }
        }
    }
}

// Setze alle nicht beantworteten Antworten auf 0
error_log("=== SETZE NICHT BEANTWORTETE ANTWORTEN AUF 0 ===");
foreach ($answerXml->questions->question as $question) {
    $questionNr = (string)$question['nr'];
    error_log("Prüfe Frage Nr: $questionNr");
    foreach ($question->answers->answer as $answer) {
        $answerNr = (string)$answer['nr'];
        if (!isset($answer->schuelerantwort)) {
            error_log("  Antwort $answerNr hat keine schuelerantwort - setze auf 0");
            $answer->addChild('schuelerantwort', '0');
        } else {
            error_log("  Antwort $answerNr hat schuelerantwort: " . (string)$answer->schuelerantwort);
        }
    }
}

// Erstelle den Ordnernamen basierend auf Zugangscode und Datum
$date = date('Y-m-d');
$folderName = $_SESSION['test_code'] . '_' . $date;

// Verwende absolute Pfade statt relativer Pfade
$rootPath = dirname(__FILE__); // Absoluter Pfad zum aktuellen Verzeichnis
$mainResultsDir = $rootPath . '/results';
$resultsDir = $mainResultsDir . '/' . $folderName;

// Logge aktuelle Pfade und Berechtigungen für besseres Debugging
error_log("Root-Verzeichnis: " . $rootPath);
error_log("Hauptverzeichnis für Ergebnisse: " . $mainResultsDir);
error_log("Spezifisches Results-Verzeichnis: " . $resultsDir);

// Stelle sicher, dass der Hauptordner 'results' existiert
if (!file_exists($mainResultsDir)) {
    error_log("Versuche Hauptordner zu erstellen: " . $mainResultsDir);
    if (!mkdir($mainResultsDir, 0775, true)) {
        error_log("Fehler beim Erstellen des Hauptergebnisordners: " . $mainResultsDir);
        error_log("PHP Fehler: " . error_get_last()['message']);
        error_log("Aktuelles Arbeitsverzeichnis: " . getcwd());
        $_SESSION['error'] = "Fehler beim Speichern des Tests. Bitte kontaktieren Sie den Administrator.";
        header("Location: index.php");
        exit();
    }
    // Setze Berechtigungen explizit - verwende 0775 statt 0777 für bessere Sicherheit
    chmod($mainResultsDir, 0775);
    error_log("Hauptordner erfolgreich erstellt");
}

// Überprüfe, ob der Webserver Schreibrechte für den Hauptordner hat
if (!is_writable($mainResultsDir)) {
    error_log("WARNUNG: Hauptordner ist nicht beschreibbar: " . $mainResultsDir);
    error_log("Versuche Berechtigungen zu setzen...");
    chmod($mainResultsDir, 0775);
    
    // Überprüfe erneut nach Berechtigungsänderung
    if (!is_writable($mainResultsDir)) {
        error_log("FEHLER: Hauptordner ist immer noch nicht beschreibbar nach Berechtigungsänderung");
        $_SESSION['error'] = "Fehler beim Speichern des Tests: Ordner nicht beschreibbar.";
        header("Location: index.php");
        exit();
    }
}

// Erstelle den Unterordner für den spezifischen Test
if (!file_exists($resultsDir)) {
    error_log("Versuche Ordner zu erstellen: " . $resultsDir);
    if (!mkdir($resultsDir, 0775, true)) {
        error_log("Fehler beim Erstellen des Ergebnisordners: " . $resultsDir);
        error_log("PHP Fehler: " . error_get_last()['message']);
        error_log("Aktuelles Arbeitsverzeichnis: " . getcwd());
        error_log("Aktuelle Berechtigungen des übergeordneten Ordners: " . decoct(fileperms($mainResultsDir)));
        $_SESSION['error'] = "Fehler beim Speichern des Tests. Bitte kontaktieren Sie den Administrator.";
        header("Location: index.php");
        exit();
    }
    // Setze Berechtigungen explizit - verwende 0775 statt 0777 für bessere Sicherheit
    chmod($resultsDir, 0775);
    error_log("Ordner erfolgreich erstellt: " . $resultsDir);
}

// Überprüfe, ob der Webserver Schreibrechte für den spezifischen Ordner hat
if (!is_writable($resultsDir)) {
    error_log("WARNUNG: Ergebnisordner ist nicht beschreibbar: " . $resultsDir);
    error_log("Versuche Berechtigungen zu setzen...");
    chmod($resultsDir, 0775);
    
    // Überprüfe erneut nach Berechtigungsänderung
    if (!is_writable($resultsDir)) {
        error_log("FEHLER: Ergebnisordner ist immer noch nicht beschreibbar nach Berechtigungsänderung");
        $_SESSION['error'] = "Fehler beim Speichern des Tests: Ordner nicht beschreibbar.";
        header("Location: index.php");
        exit();
    }
}

// Erstelle den Dateinamen mit sanitiertem Schülernamen
$timestamp = date('Y-m-d_H-i-s');
$safeStudentName = sanitizeStudentName($_SESSION['student_name']);
$filename = $_SESSION['test_code'] . '_' . $safeStudentName . '_' . $timestamp . '.xml';
$filepath = $resultsDir . '/' . $filename;

// Formatiere das XML für bessere Lesbarkeit
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($answerXml->asXML());

// DEBUG: Finale XML-Datei wird in Log-Datei geschrieben
error_log("=== FINALE XML-DATEI ===");
error_log($dom->saveXML());
error_log("=== ENDE XML-DATEI ===");

// DEBUG: Zeige finale XML im Browser
echo "<div class='debug-info' style='background: #fff; padding: 20px; margin: 20px; border: 2px solid #333; font-family: monospace; display: none;'>";
echo "<h3>🔍 DEBUG: Finale XML-Datei</h3>";
echo "<pre style='background: #f9f9f9; padding: 10px; border: 1px solid #ccc; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($dom->saveXML());
echo "</pre>";
echo "</div>";

// Überprüfe, ob die Datei geschrieben werden kann
error_log("Versuche XML-Datei zu speichern: " . $filepath);
error_log("Aktuelles Arbeitsverzeichnis: " . getcwd());
error_log("Dateiberechtigungen des Zielordners: " . decoct(fileperms($resultsDir)));

// Speichere zuerst eine Backup-Kopie der XML-Daten
$xmlContent = $dom->saveXML();
$backupFilepath = $mainResultsDir . '/backup_' . $filename;
error_log("Erstelle Backup-Datei: " . $backupFilepath);
$backupSaved = file_put_contents($backupFilepath, $xmlContent);
if ($backupSaved === false) {
    error_log("Fehler beim Speichern der Backup-XML-Datei: " . $backupFilepath);
} else {
    error_log("Backup-XML-Datei erfolgreich gespeichert: " . $backupFilepath);
    // Setze Berechtigungen für die Datei
    chmod($backupFilepath, 0664);
}

// Prüfe Schreibrechte vor dem Speichern
if (!is_writable(dirname($filepath))) {
    error_log("WARNUNG: Verzeichnis für XML-Datei ist nicht beschreibbar: " . dirname($filepath));
    // Versuche, Berechtigungen zu setzen
    chmod(dirname($filepath), 0775);
    
    if (!is_writable(dirname($filepath))) {
        error_log("FEHLER: Verzeichnis bleibt nicht beschreibbar nach Berechtigungsänderung");
        $_SESSION['error'] = "Fehler beim Speichern des Tests: Verzeichnis nicht beschreibbar.";
        header("Location: index.php");
        exit();
    }
}

// Versuche die Datei zu speichern
$saved = false;
try {
    $saved = $dom->save($filepath);
} catch (Exception $e) {
    error_log("Exception beim Speichern der XML-Datei: " . $e->getMessage());
}

if (!$saved) {
    error_log("Fehler beim Speichern der XML-Datei: " . $filepath);
    error_log("PHP Fehler: " . error_get_last()['message']);
    error_log("Aktuelle Berechtigungen des Ordners: " . decoct(fileperms($resultsDir)));
    
    // Versuche einen alternativen Speicherweg mit file_put_contents
    error_log("Versuche alternativen Speichermechanismus mit file_put_contents");
    $xmlContent = $dom->saveXML();
    if (file_put_contents($filepath, $xmlContent) === false) {
        error_log("Auch file_put_contents ist fehlgeschlagen. Kann Test nicht speichern.");
        $_SESSION['error'] = "Fehler beim Speichern des Tests.";
        header("Location: index.php");
        exit();
    } else {
        error_log("XML-Datei erfolgreich mit file_put_contents gespeichert");
        // Setze Berechtigungen für die Datei
        chmod($filepath, 0664);
    }
} else {
    error_log("XML-Datei erfolgreich gespeichert mit DOM->save");
    // Setze Berechtigungen für die Datei
    chmod($filepath, 0664);
}

// Markiere den Test als absolviert
error_log("Markiere Test als absolviert");
$markResult = markTestAsCompleted($_SESSION['test_code'], $_SESSION['student_name']);
if (!$markResult) {
    error_log("Warnung: Test konnte nicht als absolviert markiert werden, fahre trotzdem fort");
    // Füge einen Hinweis zur Sitzung hinzu, aber leite nicht um
    $_SESSION['warning'] = "Hinweis: Der Teststatus konnte nicht gespeichert werden. Die Testergebnisse wurden jedoch erfasst.";
    // Kein Redirect und kein exit() - wir setzen die Verarbeitung fort
}
error_log("Testmarkierung abgeschlossen, fahre mit Auswertung fort");

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
    
    // Bereite die Testdaten vor - verwende den sanitierten Namen für die Datenbank
    $testData = [
        'access_code' => $baseCode, // Speichere den Basis-Code
        'title' => (string)$originalXml->title,
        'question_count' => count($questions),
        'answer_count' => $totalAnswers,
        'answer_type' => $answerType,
        'student_name' => sanitizeStudentName($_SESSION['student_name']) . (isAdminCode($_SESSION['test_code']) ? ' (Admin)' : ''),
        'xml_file_path' => $filepath,
        'points_achieved' => $results['achieved'],
        'points_maximum' => $results['max'],
        'percentage' => $results['percentage'],
        'grade' => $grade,
        'started_at' => $_SESSION['test_start_time'] ?? date('Y-m-d H:i:s'),
        'session_type' => $_SESSION['session_type'] ?? 'Browser',
        'is_seb_session' => $_SESSION['is_seb_session'] ?? false
    ];
    
    $testDb->saveTestAttempt($testData);
    error_log("Ergebnisse erfolgreich in der Datenbank gespeichert");

    // Keine Synchronisierung mehr durchführen, da dies zu doppelten Einträgen führt
    error_log("Überspringe direkte Datenbanksynchronisation, um doppelte Einträge zu vermeiden");

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

// Biete dem Schüler die XML-Datei zum Download an
error_log("Biete XML-Datei zum Download an");
$_SESSION['download_xml_file'] = $filepath;
$_SESSION['download_xml_filename'] = $filename;

// Weiterleitung zur Ergebnisseite
error_log("Weiterleitung zur Ergebnisseite");
// DEBUG: Schreibe Debug-Informationen in Log-Datei
$debugLogFile = __DIR__ . '/debug_test_processing.log';
$debugContent = "\n=== TESTVERARBEITUNG DEBUG - " . date('Y-m-d H:i:s') . " ===\n";
$debugContent .= "POST-Daten:\n" . print_r($_POST, true) . "\n";
$debugContent .= "Session shuffled_questions:\n" . print_r($_SESSION['shuffled_questions'] ?? 'NICHT GESETZT', true) . "\n";
$debugContent .= "Finale XML-Datei:\n" . $dom->saveXML() . "\n";
$debugContent .= "=== ENDE DEBUG ===\n\n";

file_put_contents($debugLogFile, $debugContent, FILE_APPEND | LOCK_EX);

header("Location: result.php");
exit();

error_log("=== Ende der Testverarbeitung ===");
?>