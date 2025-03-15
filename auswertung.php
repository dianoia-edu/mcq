<?php
require_once 'includes/database_config.php';

// Funktion zum Laden des Notenschemas
function loadGradeSchema() {
    $schemaFile = 'notenschema.txt';
    if (!file_exists($schemaFile)) {
        error_log("Notenschema nicht gefunden: " . $schemaFile);
        return false;
    }

    $schema = [];
    $lines = file($schemaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(':', $line);
        if (count($parts) === 2) {
            $grade = trim($parts[0]);
            $minPercentage = (float)trim($parts[1]);
            $schema[] = ['grade' => $grade, 'minPercentage' => $minPercentage];
        }
    }

    // Sortiere nach Prozentsatz absteigend
    usort($schema, function($a, $b) {
        return $b['minPercentage'] <=> $a['minPercentage'];
    });

    return $schema;
}

// Funktion zur Berechnung der Note basierend auf dem Prozentsatz
function calculateGrade($percentage, $schema) {
    foreach ($schema as $entry) {
        if ($percentage >= $entry['minPercentage']) {
            return $entry['grade'];
        }
    }
    return end($schema)['grade']; // Schlechteste Note als Fallback
}

// Funktion zur Auswertung eines einzelnen Tests
function evaluateTest($xmlFile) {
    error_log("Starte Auswertung für: " . $xmlFile);
    
    // Lade die XML-Datei
    $xml = simplexml_load_file($xmlFile);
    if ($xml === false) {
        error_log("Fehler beim Laden der XML-Datei: " . $xmlFile);
        return false;
    }

    $maxPoints = 0;      // Maximale Punktzahl
    $achievedPoints = 0; // Erreichte Punktzahl

    // Gehe durch alle Fragen
    foreach ($xml->questions->question as $question) {
        $correctAnswers = 0;    // Anzahl richtiger Antworten in dieser Frage
        $correctChosen = 0;     // Anzahl richtig gewählter Antworten
        $wrongChosen = 0;       // Anzahl falsch gewählter Antworten
        
        // Zähle zunächst die möglichen richtigen Antworten
        foreach ($question->answers->answer as $answer) {
            if ((int)$answer->correct === 1) {
                $correctAnswers++;
            }
        }
        
        // Addiere zur maximalen Punktzahl
        $maxPoints += $correctAnswers;
        
        // Prüfe die Schülerantworten
        foreach ($question->answers->answer as $answer) {
            $isCorrect = (int)$answer->correct === 1;
            $wasChosen = (int)$answer->schuelerantwort === 1;
            
            if ($isCorrect && $wasChosen) {
                // Richtige Antwort wurde gewählt
                $correctChosen++;
            } elseif (!$isCorrect && $wasChosen) {
                // Falsche Antwort wurde gewählt
                $wrongChosen++;
            }
        }
        
        // Berechne Punkte für diese Frage
        // Neue Logik für Fragen mit mehreren richtigen Antworten:
        // - Wenn falsche Antworten gewählt wurden, werden die Punkte reduziert
        // - Pro Frage gibt es keine Minuspunkte
        // - Bei einer falschen Antwort wird ein Punkt abgezogen
        $questionPoints = 0;
        if ($wrongChosen === 0) {
            // Keine falschen Antworten gewählt -> alle richtigen Antworten zählen
            $questionPoints = $correctChosen;
        } else {
            // Bei falschen Antworten: Ziehe einen Punkt pro falscher Antwort ab
            $questionPoints = max(0, $correctChosen - $wrongChosen);
        }
        
        $achievedPoints += $questionPoints;
        
        error_log("Frage " . $question['nr'] . ":");
        error_log(" - Mögliche richtige Antworten: " . $correctAnswers);
        error_log(" - Richtig gewählte Antworten: " . $correctChosen);
        error_log(" - Falsch gewählte Antworten: " . $wrongChosen);
        error_log(" - Punkte für diese Frage: " . $questionPoints);
    }
    
    // Berechne Prozentsatz
    $percentage = ($maxPoints > 0) ? round(($achievedPoints / $maxPoints) * 100, 2) : 0;
    
    error_log("Auswertung abgeschlossen:");
    error_log(" - Maximale Punktzahl: " . $maxPoints);
    error_log(" - Erreichte Punktzahl: " . $achievedPoints);
    error_log(" - Prozentsatz: " . $percentage . "%");
    
    return [
        'max' => $maxPoints,
        'achieved' => $achievedPoints,
        'percentage' => $percentage
    ];
}

// AJAX-Endpunkt für Ergebnisse
if (isset($_GET['action']) && $_GET['action'] === 'get_results') {
    header('Content-Type: application/json');
    try {
        $db = DatabaseConfig::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT 
                t.access_code as test_code,
                t.title as test_title,
                ta.student_name,
                ta.completed_at as date,
                ta.points_achieved as achieved_points,
                ta.points_maximum as max_points,
                ta.percentage,
                ta.grade,
                ts.attempts_count,
                ts.average_percentage
            FROM test_attempts ta
            JOIN tests t ON ta.test_id = t.test_id
            LEFT JOIN test_statistics ts ON t.test_id = ts.test_id
            ORDER BY ta.completed_at DESC
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $results]);
    } catch (Exception $e) {
        error_log("Fehler beim Laden der Ergebnisse: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Testauswertung</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .result-item {
            background: #fff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .result-item h3 {
            margin-top: 0;
            color: #2563eb;
        }
        .test-stats {
            background: #f3f4f6;
            padding: 10px;
            margin-top: 10px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .error-message {
            color: #dc2626;
            padding: 10px;
            background: #fee2e2;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Testauswertung</h1>
        <div id="results-list">
            <!-- Hier werden die Ergebnisse dynamisch eingefügt -->
        </div>
    </div>
    <script>
    function loadResults() {
        console.log('Debug: auswertung.php wird geladen - Version vom ' + new Date().toLocaleString('de-DE'));
        
        fetch('auswertung.php?action=get_results')
            .then(response => response.json())
            .then(response => {
                if (!response.success) {
                    throw new Error(response.error || 'Fehler beim Laden der Daten');
                }
                
                const resultsList = document.getElementById('results-list');
                resultsList.innerHTML = '';
                
                if (!response.data || response.data.length === 0) {
                    resultsList.innerHTML = '<div class="result-item">Keine Ergebnisse verfügbar.</div>';
                    return;
                }
                
                // Gruppiere Ergebnisse nach Test
                const testGroups = {};
                response.data.forEach(attempt => {
                    if (!testGroups[attempt.test_code]) {
                        testGroups[attempt.test_code] = {
                            title: attempt.test_title,
                            attempts_count: attempt.attempts_count,
                            average_percentage: attempt.average_percentage,
                            results: []
                        };
                    }
                    testGroups[attempt.test_code].results.push(attempt);
                });
                
                // Zeige Ergebnisse gruppiert nach Test an
                Object.entries(testGroups).forEach(([testCode, group]) => {
                    const testDiv = document.createElement('div');
                    testDiv.className = 'test-group';
                    testDiv.innerHTML = `
                        <h2>${group.title} (${testCode})</h2>
                        <div class="test-stats">
                            <p>Gesamtversuche: ${group.attempts_count || 0}</p>
                            <p>Durchschnittliche Leistung: ${group.average_percentage ? group.average_percentage.toFixed(1) + '%' : 'N/A'}</p>
                        </div>
                    `;
                    
                    group.results.forEach(attempt => {
                        const resultDiv = document.createElement('div');
                        resultDiv.className = 'result-item';
                        resultDiv.innerHTML = `
                            <h3>${attempt.student_name}</h3>
                            <p>Datum: ${new Date(attempt.date).toLocaleString('de-DE')}</p>
                            <p>Punkte: ${attempt.achieved_points} von ${attempt.max_points}</p>
                            <p>Prozent: ${attempt.percentage}%</p>
                            <p>Note: ${attempt.grade}</p>
                        `;
                        testDiv.appendChild(resultDiv);
                    });
                    
                    resultsList.appendChild(testDiv);
                });
            })
            .catch(error => {
                console.error('Fehler beim Laden der Ergebnisse:', error);
                const resultsList = document.getElementById('results-list');
                resultsList.innerHTML = `
                    <div class="error-message">
                        Fehler beim Laden der Ergebnisse. Bitte versuchen Sie es später erneut.
                    </div>
                `;
            });
    }

    // Lade die Ergebnisse beim Start
    document.addEventListener('DOMContentLoaded', loadResults);
    </script>
</body>
</html> 