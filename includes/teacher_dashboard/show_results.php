<?php
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== Start show_results.php ===");

$file = isset($_GET['file']) ? $_GET['file'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : '';
$isAjax = ($format === 'ajax');

writeLog("Angefragter Dateiname: " . $file . ", Format: " . $format);

if (empty($file)) {
    writeLog("Kein Dateiname angegeben, leite um");
    if ($isAjax) {
        echo "<div class='alert alert-danger'>Fehler: Kein Dateiname angegeben</div>";
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

// Wenn es eine AJAX-Anfrage ist, direkt verarbeiten ohne Header/Footer
if ($isAjax) {
    processFile($file);
} else {
    // Vollständige HTML-Seite ausgeben
    include_once __DIR__ . '/../../teacher/header.php';
    echo '<div class="container mt-4">';
    processFile($file);
    echo '</div>';
    include_once __DIR__ . '/../../teacher/footer.php';
}

// Funktion zur Verarbeitung der Datei
function processFile($file) {
    writeLog("Verarbeite Datei: " . $file);
    
    try {
        $db = DatabaseConfig::getInstance()->getConnection();
        writeLog("Datenbankverbindung hergestellt");
        
        // Dateiinformationen aus der Datenbank holen
        $stmt = $db->prepare("SELECT ta.xml_file_path, ta.grade, ta.student_name FROM test_attempts ta WHERE ta.xml_file_path LIKE ?");
        $stmt->execute(['%' . $file . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $xmlPath = $result['xml_file_path'];
            $grade = isset($result['grade']) ? $result['grade'] : '-';
            // Debug-Information zur Note hinzufügen
            writeLog("Gefundene Daten: XML-Pfad=" . $xmlPath . ", Note=" . $grade . ", DB-Schülername=" . (isset($result['student_name']) ? $result['student_name'] : 'nicht gesetzt'));
            
            // Baue den vollständigen Pfad
            $fullPath = __DIR__ . '/../../' . $xmlPath;
            writeLog("Vollständiger Pfad: " . $fullPath);
            
            if (file_exists($fullPath)) {
                writeLog("Datei existiert");
                
                // XML-Datei laden und verarbeiten
                $xml = simplexml_load_file($fullPath);
                if ($xml) {
                    // Extrahiere Schülername aus dem Dateinamen
                    $studentName = '';
                    if (isset($result['student_name']) && !empty($result['student_name'])) {
                        // Wenn in der Datenbank ein Schülername vorhanden ist, verwende diesen
                        $studentName = $result['student_name'];
                        writeLog("Verwende Schülernamen aus der Datenbank: " . $studentName);
                    } else if (preg_match('/_([^_]+)_\d{4}-\d{2}-\d{2}/', $xmlPath, $matches)) {
                        // Sonst versuche den Namen aus dem Dateinamen zu extrahieren
                        $studentName = str_replace('_', ' ', $matches[1]);
                        writeLog("Extrahierter Schülername aus Dateinamen: " . $studentName);
                    } else {
                        $studentName = 'Unbekannt';
                        writeLog("Kein Schülername gefunden");
                    }
                    
                    displayTestResults($xml, $studentName, $grade);
                } else {
                    writeLog("Fehler beim Parsen der XML-Datei");
                    echo "<div class='alert alert-danger'>Fehler beim Parsen der XML-Datei</div>";
                }
            } else {
                writeLog("Datei existiert nicht: " . $fullPath);
                echo "<div class='alert alert-danger'>Die Ergebnisdatei wurde nicht gefunden</div>";
            }
        } else {
            writeLog("Kein Datensatz in der Datenbank gefunden");
            echo "<div class='alert alert-danger'>Keine Informationen zu diesem Test in der Datenbank gefunden</div>";
        }
    } catch (Exception $e) {
        writeLog("Datenbankfehler: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Datenbankfehler: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Funktion zur Anzeige der Testergebnisse
function displayTestResults($xml, $studentName = 'Unbekannt', $grade = '-') {
    // Grundlegende Informationen extrahieren
    $testTitle = isset($xml->title) ? (string)$xml->title : 'Unbekannter Test';
    $accessCode = isset($xml->access_code) ? (string)$xml->access_code : '';
    
    // Score und Bewertung berechnen
    $correctAnswers = 0;
    $totalQuestions = 0;
    
    if (isset($xml->questions->question)) {
        $totalQuestions = count($xml->questions->question);
        
        foreach ($xml->questions->question as $question) {
            $isCorrect = true;
            foreach ($question->answers->answer as $answer) {
                $correct = (string)$answer->correct === '1';
                $schuelerantwort = (string)$answer->schuelerantwort === '1';
                
                if ($correct !== $schuelerantwort) {
                    $isCorrect = false;
                    break;
                }
            }
            
            if ($isCorrect) {
                $correctAnswers++;
            }
        }
    }
    
    $percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
    
    // Wenn keine Note gesetzt ist oder sie leer ist, berechne sie basierend auf dem Prozentsatz
    if (empty($grade) || $grade === '-') {
        if ($percentage >= 92) {
            $grade = '1';
        } elseif ($percentage >= 81) {
            $grade = '2';
        } elseif ($percentage >= 67) {
            $grade = '3';
        } elseif ($percentage >= 50) {
            $grade = '4';
        } elseif ($percentage >= 30) {
            $grade = '5';
        } else {
            $grade = '6';
        }
    }
    
    // Testinformationen anzeigen
    echo "<div class='card mb-4'>";
    echo "<div class='card-header' style='background-color: #007bff; opacity: 0.8; color: white;'>";
    echo "<h4 class='mb-0'>" . htmlspecialchars($testTitle) . "</h4>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Test-Code:</strong> " . htmlspecialchars($accessCode) . "</p>";
    echo "<p><strong>Schüler:</strong> " . htmlspecialchars($studentName) . "</p>";
    echo "</div>";
    echo "<div class='col-md-6 text-md-end'>";
    echo "<p><strong>Punkte:</strong> $correctAnswers/$totalQuestions</p>";
    echo "<p><strong>Prozent:</strong> $percentage%</p>";
    echo "<p><strong>Note:</strong> " . htmlspecialchars($grade) . "</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Fragen und Antworten anzeigen
    if (isset($xml->questions->question)) {
        foreach ($xml->questions->question as $i => $question) {
            // Extrahiere die tatsächliche Fragennummer aus dem XML
            $questionNumber = isset($question['nr']) ? (string)$question['nr'] : ((int)$i + 1);
            $questionText = isset($question->text) ? (string)$question->text : 'Keine Fragentext';
            
            // Prüfe, ob alle Antworten korrekt sind
            $isQuestionCorrect = true;
            foreach ($question->answers->answer as $answer) {
                $correct = (string)$answer->correct === '1';
                $schuelerantwort = (string)$answer->schuelerantwort === '1';
                
                if ($correct !== $schuelerantwort) {
                    $isQuestionCorrect = false;
                    break;
                }
            }
            
            // CSS-Klasse je nach Korrektheit - dezentere Farben
            $headerBgColor = $isQuestionCorrect ? '#28a745' : '#dc3545';
            $headerStyle = 'background-color: ' . $headerBgColor . '; opacity: 0.7; color: white;';
            
            echo "<div class='card mb-3 " . ($isQuestionCorrect ? 'border-success' : 'border-danger') . "'>";
            echo "<div class='card-header' style='$headerStyle'>";
            echo "<h5 class='mb-0'>Frage $questionNumber: " . ($isQuestionCorrect ? '1' : '0') . "/1 Punkte</h5>";
            echo "</div>";
            echo "<div class='card-body'>";
            
            // Fragentext
            echo "<p class='card-text'>" . nl2br(htmlspecialchars($questionText)) . "</p>";
            
            // Antwortoptionen anzeigen
            if (isset($question->answers->answer)) {
                echo "<div class='list-group mt-3'>";
                echo "<h6>Antwortmöglichkeiten:</h6>";
                
                foreach ($question->answers->answer as $answer) {
                    $answerText = isset($answer->text) ? (string)$answer->text : 'Keine Antworttext';
                    $isCorrect = (string)$answer->correct === '1';
                    $isSelected = (string)$answer->schuelerantwort === '1';
                    
                    // Bestimme CSS-Klasse und Stil für die Antwort - dezentere Farben
                    $bgStyle = '';
                    $badgeClass = '';
                    $badgeText = '';
                    
                    if ($isSelected && $isCorrect) {
                        // Richtig gewählte Antwort - Grün
                        $bgStyle = 'background-color: rgba(40, 167, 69, 0.2);';
                        $badgeClass = 'bg-success';
                        $badgeText = 'Richtig';
                    } else if ($isSelected && !$isCorrect) {
                        // Falsch gewählte Antwort - Rot
                        $bgStyle = 'background-color: rgba(220, 53, 69, 0.2);';
                        $badgeClass = 'bg-danger';
                        $badgeText = 'Falsch';
                    } else if (!$isSelected && $isCorrect) {
                        // Nicht gewählte richtige Antwort - Gelb
                        $bgStyle = 'background-color: rgba(255, 193, 7, 0.2);';
                        $badgeClass = 'bg-warning text-dark';
                        $badgeText = 'Korrekt';
                    }
                    
                    // Ermittle den Antwortbuchstaben
                    $answerLetter = isset($answer['nr']) ? (string)$answer['nr'] : '';
                    
                    echo "<div class='list-group-item' style='$bgStyle'>";
                    echo "<div class='d-flex justify-content-between align-items-center'>";
                    echo "<div class='d-flex align-items-center'>";
                    echo "<div class='me-3 fw-bold'>" . htmlspecialchars($answerLetter) . ".</div>";
                    echo "<div>" . nl2br(htmlspecialchars($answerText)) . "</div>";
                    echo "</div>";
                    if (!empty($badgeText)) {
                        echo "<span class='badge $badgeClass ms-2'>$badgeText</span>";
                    }
                    echo "</div>";
                    echo "</div>";
                }
                
                echo "</div>";
            }
            
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Keine Fragen gefunden</div>";
    }
} 