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
$showDebug = isset($_GET['debug']) && $_GET['debug'] === '1';

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
        
        // Normalisiere den Pfad für den Datenbankvergleich (ersetze Backslashes mit Forward Slashes)
        $normalizedFile = str_replace('\\', '/', $file);
        writeLog("Normalisierter Pfad für DB-Suche: " . $normalizedFile);
        
        // Versuche zunächst, die Datei direkt zu finden
        $possiblePaths = [
            $normalizedFile,                               // Direkt wie überliefert
            __DIR__ . '/../../' . ltrim($normalizedFile, '/'),  // Vollständiger Pfad relativ zum Skript
            'results/' . basename(dirname($normalizedFile)) . '/' . basename($normalizedFile) // Relativer Pfad mit Basisverzeichnis
        ];
        
        writeLog("Versuche direkt verschiedene Pfade zur Datei:");
        $fileFound = false;
        $foundFullPath = '';
        
        foreach ($possiblePaths as $testPath) {
            writeLog("Teste Pfad: " . $testPath);
            if (file_exists($testPath)) {
                $fileFound = true;
                $foundFullPath = $testPath;
                writeLog("Datei gefunden unter: " . $foundFullPath);
                break;
            }
        }
        
        // Wenn die Datei direkt gefunden wurde, lade sie
        if ($fileFound) {
            writeLog("Versuche XML-Datei zu laden von: " . $foundFullPath);
            $xml = simplexml_load_file($foundFullPath);
            if ($xml) {
                writeLog("XML-Datei direkt geladen");
                // Versuche Schülernamen aus dem Dateinamen zu extrahieren
                $studentName = 'Unbekannt';
                if (preg_match('/_([^_]+)_\d{4}-\d{2}-\d{2}/', $normalizedFile, $matches)) {
                    $studentName = str_replace('_', ' ', $matches[1]);
                    writeLog("Extrahierter Schülername aus Dateinamen: " . $studentName);
                }
                
                // Hole Note aus DB, falls vorhanden
                $stmt = $db->prepare("SELECT ta.grade, ta.student_name FROM test_attempts ta WHERE ta.xml_file_path LIKE ?");
                $stmt->execute(['%' . basename($normalizedFile) . '%']);
                $dbResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $grade = isset($dbResult['grade']) ? $dbResult['grade'] : '-';
                if (isset($dbResult['student_name']) && !empty($dbResult['student_name'])) {
                    $studentName = $dbResult['student_name'];
                }
                
                displayTestResults($xml, $studentName, $grade);
                return;
            } else {
                writeLog("Fehler beim Parsen der direkt gefundenen XML-Datei");
            }
        }
        
        // Dateiinformationen aus der Datenbank holen
        writeLog("Suche in Datenbank nach Dateinamen: " . basename($normalizedFile));
        $stmt = $db->prepare("SELECT ta.xml_file_path, ta.grade, ta.student_name FROM test_attempts ta WHERE ta.xml_file_path LIKE ?");
        $stmt->execute(['%' . basename($normalizedFile) . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $xmlPath = $result['xml_file_path'];
            $grade = isset($result['grade']) ? $result['grade'] : '-';
            // Debug-Information zur Note hinzufügen
            writeLog("Gefundene Daten: XML-Pfad=" . $xmlPath . ", Note=" . $grade . ", DB-Schülername=" . (isset($result['student_name']) ? $result['student_name'] : 'nicht gesetzt'));
            
            // Stelle sicher, dass der Pfad für Windows korrekt normalisiert ist
            $xmlPath = str_replace('\\', '/', $xmlPath);
            
            // Baue den vollständigen Pfad
            $fullPath = __DIR__ . '/../../' . ltrim($xmlPath, '/');
            writeLog("Vollständiger Pfad aus Datenbank: " . $fullPath);
            
            if (file_exists($fullPath)) {
                writeLog("Datei existiert in Datenbank-Pfad");
                
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
                writeLog("Datei existiert nicht unter DB-Pfad: " . $fullPath);
                
                if ($fileFound) {
                    // Wenn die Datei vorher direkt gefunden wurde, versuche sie nochmal zu laden
                    writeLog("Verwende alternativ direkt gefundenen Pfad: " . $foundFullPath);
                    $xml = simplexml_load_file($foundFullPath);
                    if ($xml) {
                        $studentName = isset($result['student_name']) ? $result['student_name'] : 'Unbekannt';
                        displayTestResults($xml, $studentName, $grade);
                    } else {
                        echo "<div class='alert alert-danger'>Die Ergebnisdatei wurde nicht gefunden</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>Die Ergebnisdatei wurde nicht gefunden</div>";
                }
            }
        } else {
            writeLog("Kein Datensatz in der Datenbank gefunden für: " . basename($normalizedFile));
            
            if ($fileFound) {
                // Wenn die Datei direkt gefunden wurde, verwende sie trotzdem
                writeLog("Kein DB-Eintrag, aber Datei wurde direkt gefunden unter: " . $foundFullPath);
                $xml = simplexml_load_file($foundFullPath);
                if ($xml) {
                    // Versuche Schülernamen aus dem Dateinamen zu extrahieren
                    $studentName = 'Unbekannt';
                    if (preg_match('/_([^_]+)_\d{4}-\d{2}-\d{2}/', $normalizedFile, $matches)) {
                        $studentName = str_replace('_', ' ', $matches[1]);
                    }
                    displayTestResults($xml, $studentName, '-');
                } else {
                    echo "<div class='alert alert-danger'>Keine Informationen zu diesem Test in der Datenbank gefunden<br>Datei: " . htmlspecialchars($normalizedFile) . "</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Keine Informationen zu diesem Test in der Datenbank gefunden<br>Datei: " . htmlspecialchars($normalizedFile) . "</div>";
            }
        }
    } catch (Exception $e) {
        writeLog("Datenbankfehler: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Datenbankfehler: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Funktion zur Anzeige der Testergebnisse
function displayTestResults($xml, $studentName = 'Unbekannt', $grade = '-') {
    // Debug-Info
    writeLog("====== ANZEIGE TESTERGEBNISSE ======");
    
    /*
     * Punkteberechnung und Farbkodierung:
     * - Für jede richtige Antwort: +1 Punkt
     * - Für jede falsche Antwort: -1 Punkt (Minimum 0)
     * - Farbkodierung:
     *   - Grün: Alle möglichen Punkte erreicht (100%)
     *   - Rot: Keine Punkte erreicht (0%)
     *   - Orange: Teilweise Punkte erreicht (1% - 99%)
     */
    
    // Grundlegende Informationen extrahieren
    $testTitle = isset($xml->title) ? (string)$xml->title : 'Unbekannter Test';
    $accessCode = isset($xml->access_code) ? (string)$xml->access_code : '';
    
    writeLog("Test: $testTitle, Code: $accessCode, Schüler: $studentName");
    
    // Score und Bewertung berechnen
    $totalPoints = 0;           // Maximale Punktzahl
    $achievedPoints = 0;        // Erreichte Punktzahl
    $questionPoints = [];       // Punkte pro Frage [erreicht, maximal]
    
    if (isset($xml->questions->question)) {
        foreach ($xml->questions->question as $qIndex => $question) {
            $questionNumber = isset($question['nr']) ? (string)$question['nr'] : ((int)$qIndex + 1);
            writeLog("Verarbeite Frage $questionNumber");
            
            $correctAnswersTotal = 0;   // Gesamtzahl richtiger Antworten in dieser Frage
            $correctChosen = 0;         // Anzahl richtig gewählter Antworten
            $wrongChosen = 0;           // Anzahl falsch gewählter Antworten
            $totalAnswers = 0;          // Gesamtzahl der Antworten
            
            // Zähle die richtigen Antwortmöglichkeiten und Gesamtanzahl
            foreach ($question->answers->answer as $answer) {
                $totalAnswers++;
                if ((int)$answer->correct === 1) {
                    $correctAnswersTotal++;
                }
            }
            
            writeLog("  Gesamtzahl richtiger Antworten: $correctAnswersTotal");
            
            // Zähle richtig/falsch gewählte Antworten
            foreach ($question->answers->answer as $answer) {
                $isCorrect = (int)$answer->correct === 1;
                // Prüfe sowohl 'schuelerantwort' als auch 'selected' Attribute
                $schuelerantwort = (int)$answer->schuelerantwort;
                $selected = (int)$answer->selected;
                $wasChosen = ($schuelerantwort === 1) || ($selected === 1);
                
                writeLog("  Antwort: correct=" . ($isCorrect ? "1" : "0") . ", schuelerantwort=" . $schuelerantwort . ", selected=" . $selected . ", wasChosen=" . ($wasChosen ? "1" : "0"));
                
                if ($isCorrect && $wasChosen) {
                    // Richtige Antwort wurde gewählt
                    $correctChosen++;
                    writeLog("    -> RICHTIG gewählt");
                } elseif (!$isCorrect && $wasChosen) {
                    // Falsche Antwort wurde gewählt
                    $wrongChosen++;
                    writeLog("    -> FALSCH gewählt");
                } else {
                    writeLog("    -> NICHT gewählt");
                }
            }
            
            // ZUSÄTZLICHE DEBUG-AUSGABE: Zeige alle Antworten einer Frage
            writeLog("  === FRAGE $questionNumber DEBUG ===");
            foreach ($question->answers->answer as $answer) {
                $answerText = (string)$answer->text;
                $isCorrect = (int)$answer->correct === 1;
                $schuelerantwort = (int)$answer->schuelerantwort;
                $selected = (int)$answer->selected;
                $wasChosen = ($schuelerantwort === 1) || ($selected === 1);
                
                writeLog("    Antwort: '$answerText' | correct=$isCorrect | schuelerantwort=$schuelerantwort | selected=$selected | wasChosen=" . ($wasChosen ? "1" : "0"));
            }
            
            writeLog("  Gewählte richtige Antworten: $correctChosen");
            writeLog("  Gewählte falsche Antworten: $wrongChosen");
            writeLog("  === ZUSAMMENFASSUNG FRAGE $questionNumber ===");
            writeLog("  - Erreichte Punkte: $questionAchievedPoints/$questionMaxPoints");
            writeLog("  - Richtig gewählt: $correctChosen");
            writeLog("  - Falsch gewählt: $wrongChosen");
            writeLog("  - Single Choice: " . ($isSingleChoice ? 'Ja' : 'Nein'));
            
            // Maximale Punkte für diese Frage ist die Anzahl der richtigen Antworten
            $questionMaxPoints = $correctAnswersTotal;
            
            // Punkte für diese Frage berechnen (für jede richtige +1, für jede falsche -1, min. 0)
            $questionAchievedPoints = max(0, $correctChosen - $wrongChosen);
            
            writeLog("  Erreichte Punkte: $questionAchievedPoints/$questionMaxPoints");
            
            // Speichere die Punkte für diese Frage
            $questionPoints[$qIndex] = [
                'achieved' => $questionAchievedPoints,
                'max' => $questionMaxPoints,
                'correctTotal' => $correctAnswersTotal,
                'correctChosen' => $correctChosen,
                'wrongChosen' => $wrongChosen,
                'isSingleChoice' => ($correctAnswersTotal === 1),
                'xmlNr' => $questionNumber // Speichere die XML Fragennummer
            ];
            
            // Füge zur Gesamtpunktzahl hinzu
            $totalPoints += $questionMaxPoints;
            $achievedPoints += $questionAchievedPoints;
        }
    }
    
    $percentage = $totalPoints > 0 ? round(($achievedPoints / $totalPoints) * 100) : 0;
    writeLog("Gesamtergebnis: $achievedPoints von $totalPoints Punkten ($percentage%)");
    
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
    echo "<p><strong>Punkte:</strong> $achievedPoints/$totalPoints</p>";
    echo "<p><strong>Prozent:</strong> $percentage%</p>";
    echo "<p><strong>Note:</strong> " . htmlspecialchars($grade) . "</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Debug-Schalter hinzufügen
    echo "<div class='card mb-3'>";
    echo "<div class='card-body'>";
    echo "<div class='d-flex justify-content-between align-items-center'>";
    echo "<h5 class='mb-0'>Debug-Ausgaben</h5>";
    echo "<div class='form-check form-switch'>";
    $debugUrl = $showDebug ? str_replace('&debug=1', '', $_SERVER['REQUEST_URI']) : $_SERVER['REQUEST_URI'] . '&debug=1';
    echo "<input class='form-check-input' type='checkbox' id='debugToggle' " . ($showDebug ? 'checked' : '') . " onchange='toggleDebug()'>";
    echo "<label class='form-check-label' for='debugToggle'>Debug-Ausgaben anzeigen</label>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // JavaScript für Debug-Toggle ist jetzt global in teacher_dashboard.php definiert
    
    // Fragen und Antworten anzeigen
    if (isset($xml->questions->question)) {
        // Konvertiere SimpleXML zu Array für Sortierung
        $questionsArray = [];
        foreach ($xml->questions->question as $qIndex => $question) {
            $questionsArray[] = [
                'element' => $question,
                'index' => $qIndex,
                'nr' => isset($question['nr']) ? (int)$question['nr'] : ($qIndex + 1)
            ];
        }
        
        // Sortiere nach Fragennummer (ursprüngliche Testreihenfolge)
        usort($questionsArray, function($a, $b) {
            return $a['nr'] - $b['nr'];
        });
        
        foreach ($questionsArray as $questionData) {
            $question = $questionData['element'];
            $qIndex = $questionData['index'];
            $questionNumber = isset($question['nr']) ? (string)$question['nr'] : ((int)$qIndex + 1);
            $questionText = isset($question->text) ? (string)$question->text : 'Keine Fragentext';
            
            writeLog("Frage in Anzeige-Schleife: XML-Nummer $questionNumber, Schleifenindex $qIndex");
            
            // Suche die richtige Frage im questionPoints-Array basierend auf der XML-Nummer
            $pointsIndex = null;
            foreach ($questionPoints as $idx => $points) {
                if (isset($points['xmlNr']) && $points['xmlNr'] == $questionNumber) {
                    $pointsIndex = $idx;
                    writeLog("  Gefunden: Frage $questionNumber hat Index $pointsIndex im questionPoints-Array");
                    break;
                }
            }
            
            // Fallback: Wenn nicht gefunden, verwende den ursprünglichen Index
            if ($pointsIndex === null) {
                $pointsIndex = $qIndex;
                writeLog("  Fallback: Verwende ursprünglichen Index $qIndex für Frage $questionNumber");
            }
            
            // Direkt auf die Werte aus dem ursprünglichen Array zugreifen, aber mit dem korrekten Index
            $achievedQPoints = isset($questionPoints[$pointsIndex]['achieved']) ? $questionPoints[$pointsIndex]['achieved'] : 0;
            $maxQPoints = isset($questionPoints[$pointsIndex]['max']) ? $questionPoints[$pointsIndex]['max'] : 0;
            $isSingleChoice = isset($questionPoints[$pointsIndex]['isSingleChoice']) ? $questionPoints[$pointsIndex]['isSingleChoice'] : true;
            $correctChosen = isset($questionPoints[$pointsIndex]['correctChosen']) ? $questionPoints[$pointsIndex]['correctChosen'] : 0;
            $correctTotal = isset($questionPoints[$pointsIndex]['correctTotal']) ? $questionPoints[$pointsIndex]['correctTotal'] : 0;
            $wrongChosen = isset($questionPoints[$pointsIndex]['wrongChosen']) ? $questionPoints[$pointsIndex]['wrongChosen'] : 0;
            
            // Debug-Ausgaben entfernt
            
            // Neuberechnung der Punkte im Anzeigemodus, um Konsistenz mit auswertung.php zu gewährleisten
            // Diese Zeilen sicherstellen, dass die Anzeige mit der Auswertung übereinstimmt
            $correctChosen = 0;
            $wrongChosen = 0;
            $correctTotal = 0;
            
            // DEBUG: Zeige Punkteberechnung für diese Frage
            echo "<div class='debug-info' style='background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffc107; font-family: monospace; font-size: 12px; display: " . ($showDebug ? 'block' : 'none') . ";'>";
            echo "<strong>📊 PUNKTEBERECHNUNG für Frage " . $questionNr . ":</strong><br>";
            
            // Berechne die Punkte erneut, genau wie in auswertung.php
            foreach ($question->answers->answer as $answer) {
                $isCorrect = (int)$answer->correct === 1;
                // Prüfe sowohl 'schuelerantwort' als auch 'selected' Attribute
                $rawSchuelerantwort = (string)$answer->schuelerantwort;
                $rawSelected = (string)$answer->selected;
                $schuelerantwort = (int)$rawSchuelerantwort;
                $selected = (int)$rawSelected;
                
                // KORRIGIERTE LOGIK: Nur wenn mindestens einer der Werte 1 ist, ist die Antwort gewählt
                // ABER: Prüfe zuerst, ob die XML-Datei überhaupt gültige Daten enthält
                $wasChosen = false;
                
                // Prüfe, ob die XML-Datei gültige Schülerantworten enthält
                if ($rawSchuelerantwort !== '' || $rawSelected !== '') {
                    // Nur wenn mindestens einer der Werte 1 ist, ist die Antwort gewählt
                    $wasChosen = ($schuelerantwort === 1) || ($selected === 1);
                } else {
                    // Wenn beide Attribute leer sind, wurde keine Antwort gewählt
                    $wasChosen = false;
                    writeLog("  INFO: Beide Attribute sind leer - keine Antwort gewählt");
                }
                
                // ZUSÄTZLICHE VALIDIERUNG: Prüfe, ob die Werte sinnvoll sind
                if ($schuelerantwort > 1 || $selected > 1) {
                    writeLog("  WARNUNG: Ungültige Werte - schuelerantwort=$schuelerantwort, selected=$selected");
                    // Setze auf 0, wenn Werte > 1 sind
                    $schuelerantwort = 0;
                    $selected = 0;
                    $wasChosen = false;
                }
                
                // DEBUG-Ausgabe für jede Antwort in der Punkteberechnung
                echo "Antwort " . $answer['nr'] . ": ";
                echo "Richtig=" . ($isCorrect ? "JA" : "NEIN") . " | ";
                echo "Gewählt=" . ($wasChosen ? "JA" : "NEIN") . " | ";
                echo "schuelerantwort='" . $rawSchuelerantwort . "' | ";
                echo "selected='" . $rawSelected . "'<br>";
                
                if ($isCorrect) {
                    $correctTotal++;
                }
                
                if ($isCorrect && $wasChosen) {
                    $correctChosen++;
                } elseif (!$isCorrect && $wasChosen) {
                    $wrongChosen++;
                }
            }
            
            $maxQPoints = $correctTotal;
            $achievedQPoints = max(0, $correctChosen - $wrongChosen);
            
            // DEBUG: Zeige das finale Ergebnis für diese Frage
            echo "<strong>ERGEBNIS:</strong> ";
            echo "Richtige gewählt: $correctChosen | ";
            echo "Falsche gewählt: $wrongChosen | ";
            echo "Max. Punkte: $maxQPoints | ";
            echo "Erreichte Punkte: $achievedQPoints<br>";
            echo "</div>";
            
            // Debug-Ausgaben entfernt
            
            // Bestimme die Farbcodierung basierend auf dem Ergebnis
            if ($achievedQPoints == $maxQPoints && $maxQPoints > 0) {
                // Alle richtigen Antworten ausgewählt und keine falschen - Grün
                $headerBgColor = '#28a745';
                $borderClass = 'border-success';
                $cardStyle = 'border: 3px solid #28a745 !important;';
            } elseif ($achievedQPoints == 0) {
                // Keine Punkte - Rot
                $headerBgColor = '#dc3545';
                $borderClass = 'border-danger';
                $cardStyle = 'border: 3px solid #dc3545 !important;';
            } else {
                // Teilweise Punkte - Orange
                $headerBgColor = '#fd7e14'; // Orange-Farbe
                $borderClass = 'border-warning';
                $cardStyle = 'border: 3px solid #fd7e14 !important;';
            }
            
            $headerStyle = 'background-color: ' . $headerBgColor . ' !important; opacity: 0.9 !important; color: white !important;';
            
            echo "<div class='card mb-3 $borderClass' style='$cardStyle'>";
            echo "<div class='card-header' style='$headerStyle'>";
            
            // Debug-Ausgaben entfernt
            
            echo "<h5 class='mb-0'>Frage $questionNumber: $achievedQPoints/$maxQPoints Punkte</h5>";
            echo "</div>";
            echo "<div class='card-body'>";
            
            // Debug-Ausgaben entfernt
            
            // Fragentext
            echo "<p class='card-text'>" . nl2br(htmlspecialchars($questionText)) . "</p>";
            
            // Antwortoptionen anzeigen
            if (isset($question->answers->answer)) {
                echo "<div class='list-group mt-3'>";
                echo "<h6>Antwortmöglichkeiten:</h6>";
                
                // Info über Multiple-Choice anzeigen, wenn mehr als eine richtige Antwort möglich ist
                if (!$isSingleChoice) {
                    echo "<div class='alert alert-info mb-3' style='font-size: 0.9em;'>
                            <i class='bi bi-info-circle'></i> 
                            Diese Frage hat mehrere richtige Antworten ($correctTotal).
                          </div>";
                }
                
                // DEBUG: Zeige alle Antworten der Frage mit Details
                echo "<div class='debug-info' style='background: #f0f8ff; padding: 10px; margin: 10px 0; border: 1px solid #007bff; font-family: monospace; font-size: 12px; display: " . ($showDebug ? 'block' : 'none') . ";'>";
                echo "<strong>🔍 DEBUG für Frage " . $questionNr . ":</strong><br>";
                
                foreach ($question->answers->answer as $answer) {
                    $answerText = isset($answer->text) ? (string)$answer->text : 'Keine Antworttext';
                    $isCorrect = (string)$answer->correct === '1';
                    // Prüfe sowohl 'schuelerantwort' als auch 'selected' Attribute - mit Debug
                    $rawSchuelerantwort = (string)$answer->schuelerantwort;
                    $rawSelected = (string)$answer->selected;
                    $schuelerantwort = (int)$rawSchuelerantwort;
                    $selected = (int)$rawSelected;
                    
                    // Gleiche Logik wie in der Berechnung
                    $isSelected = false;
                    if ($rawSchuelerantwort !== '' || $rawSelected !== '') {
                        $isSelected = ($schuelerantwort === 1) || ($selected === 1);
                    }
                    
                    // DEBUG-Ausgabe für jede Antwort
                    echo "Antwort " . $answer['nr'] . ": ";
                    echo "Richtig=" . ($isCorrect ? "JA" : "NEIN") . " | ";
                    echo "Schüler=" . ($isSelected ? "ANGEKREUZT" : "NICHT ANGEKREUZT") . " | ";
                    echo "schuelerantwort='" . $rawSchuelerantwort . "' | ";
                    echo "selected='" . $rawSelected . "'<br>";
                }
                echo "</div>";
                
                foreach ($question->answers->answer as $answer) {
                    $answerText = isset($answer->text) ? (string)$answer->text : 'Keine Antworttext';
                    $isCorrect = (string)$answer->correct === '1';
                    // Prüfe sowohl 'schuelerantwort' als auch 'selected' Attribute - mit Debug
                    $rawSchuelerantwort = (string)$answer->schuelerantwort;
                    $rawSelected = (string)$answer->selected;
                    $schuelerantwort = (int)$rawSchuelerantwort;
                    $selected = (int)$rawSelected;
                    
                    // Gleiche Logik wie in der Berechnung
                    $isSelected = false;
                    if ($rawSchuelerantwort !== '' || $rawSelected !== '') {
                        $isSelected = ($schuelerantwort === 1) || ($selected === 1);
                    }
                    
                    // Debug-Ausgabe für Browser-Konsole (nur bei Bedarf)
                    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                        echo "<script>console.log('Antwort-Debug:', {
                            'Text': '" . addslashes($answerText) . "',
                            'Correct': " . ($isCorrect ? 'true' : 'false') . ",
                            'Schuelerantwort': $schuelerantwort,
                            'Selected': $selected,
                            'IsSelected': " . ($isSelected ? 'true' : 'false') . "
                        });</script>";
                    }
                    
                    // Debug-Ausgaben entfernt
                    
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
                        // Nicht gewählte richtige Antwort - Gelb (bei allen Fragen, nicht nur Multiple Choice)
                        $bgStyle = 'background-color: rgba(255, 193, 7, 0.2);';
                        $badgeClass = 'bg-warning text-dark';
                        $badgeText = 'Korrekt';
                    }
                    // Bei nicht gewählten falschen Antworten wird nichts markiert (normal)
                    
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