<?php
require_once 'includes/database_config.php';

// Funktion zum Laden des Notenschemas
function loadGradeSchema() {
    $activeSchemaFile = __DIR__ . '/config/active_schema.txt';
    $schemaDir = __DIR__ . '/config/grading-schemas';
    
    // Prüfe, ob es ein aktives Schema gibt
    if (file_exists($activeSchemaFile)) {
        $activeSchemaId = trim(file_get_contents($activeSchemaFile));
        $schemaFile = $schemaDir . '/' . $activeSchemaId . '.txt';
        
        if (file_exists($schemaFile)) {
            error_log("Verwende aktives Notenschema: " . $activeSchemaId);
            
            $schema = [];
            $lines = file($schemaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $parts = explode(':', $line);
                    if (count($parts) === 2) {
                        $grade = trim($parts[0]);
                        $minPercentage = (float)trim($parts[1]);
                        $schema[] = ['grade' => $grade, 'minPercentage' => $minPercentage];
                    }
                }
                
                // Überprüfe, ob das Schema leer ist
                if (!empty($schema)) {
                    // Sortiere nach Prozentsatz absteigend
                    usort($schema, function($a, $b) {
                        return $b['minPercentage'] <=> $a['minPercentage'];
                    });
                    
                    return $schema;
                }
            }
        }
    }
    
    // Fallback: Versuche, das erste verfügbare Schema aus dem Ordner zu laden
    if (is_dir($schemaDir)) {
        $files = scandir($schemaDir);
        foreach ($files as $file) {
            if ($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
                $schemaFile = $schemaDir . '/' . $file;
                error_log("Verwende Fallback-Notenschema: " . $file);
                
                $schema = [];
                $lines = file($schemaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                if ($lines !== false) {
                    foreach ($lines as $line) {
                        $parts = explode(':', $line);
                        if (count($parts) === 2) {
                            $grade = trim($parts[0]);
                            $minPercentage = (float)trim($parts[1]);
                            $schema[] = ['grade' => $grade, 'minPercentage' => $minPercentage];
                        }
                    }
                    
                    // Überprüfe, ob das Schema leer ist
                    if (!empty($schema)) {
                        // Sortiere nach Prozentsatz absteigend
                        usort($schema, function($a, $b) {
                            return $b['minPercentage'] <=> $a['minPercentage'];
                        });
                        
                        // Speichere dieses Schema als aktives Schema für zukünftige Verwendung
                        $activeSchemaId = pathinfo($file, PATHINFO_FILENAME);
                        file_put_contents($activeSchemaFile, $activeSchemaId);
                        
                        return $schema;
                    }
                }
            }
        }
    }
    
    // Wenn alle Versuche fehlschlagen, verwende das Standard-Notenschema
    error_log("Konnte kein Notenschema laden, verwende Standard-Notenschema");
    return getDefaultGradeSchema();
}

// Hilfsfunktion für das Standard-Notenschema
function getDefaultGradeSchema() {
    return [
        ['grade' => '1', 'minPercentage' => 90],
        ['grade' => '2', 'minPercentage' => 80],
        ['grade' => '3', 'minPercentage' => 70],
        ['grade' => '4', 'minPercentage' => 60], 
        ['grade' => '5', 'minPercentage' => 50],
        ['grade' => '6', 'minPercentage' => 0]
    ];
}

// Funktion zur Berechnung der Note basierend auf dem Prozentsatz
function calculateGrade($percentage, $schema) {
    // Sicherheitsüberprüfung für das Schema
    if (!is_array($schema) || empty($schema)) {
        error_log("Ungültiges Notenschema, verwende Standard-Notenschema");
        // Standard-Notenschema als Fallback
        $schema = getDefaultGradeSchema();
    }
    
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
        
        // Berechne Punkte für diese Frage:
        // - Für jede richtige Antwort gibt es einen Punkt
        // - Für jede falsche Antwort gibt es einen Punkt Abzug
        // - Die Mindestpunktzahl pro Frage ist 0
        $questionPoints = max(0, $correctChosen - $wrongChosen);
        
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

// Prüfe, ob nur die Funktionen benötigt werden
if (!defined('FUNCTIONS_ONLY')) {
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
        
        // Debug-Ausgaben für Tab-Navigation
        console.log('==== AUSWERTUNG.PHP DEBUG ====');
        console.log('Analysiere die DOM-Struktur:');
        console.log('Tab-Container existiert:', !!document.querySelector('.nav-tabs'));
        console.log('Tabs gefunden:', document.querySelectorAll('.tab').length);
        
        if (document.querySelectorAll('.tab').length > 0) {
            console.log('Tab-Elemente:');
            document.querySelectorAll('.tab').forEach((tab, i) => {
                console.log(`Tab ${i+1}:`, tab.textContent, 'target:', tab.getAttribute('data-target'), 'active:', tab.classList.contains('active'));
            });
        }
        
        console.log('Tab-Panes gefunden:', document.querySelectorAll('.tab-pane').length);
        if (document.querySelectorAll('.tab-pane').length > 0) {
            console.log('Tab-Pane Elemente:');
            document.querySelectorAll('.tab-pane').forEach((pane, i) => {
                console.log(`Pane ${i+1}:`, pane.id, 'display:', window.getComputedStyle(pane).display, 'active:', pane.classList.contains('active'));
            });
        }
        
        // Tab-Event-Listener hinzufügen, falls sie fehlen
        document.querySelectorAll('.tab').forEach(tab => {
            // Entferne vorhandene Event-Listener
            const newTab = tab.cloneNode(true);
            tab.parentNode.replaceChild(newTab, tab);
            
            // Füge neuen Event-Listener hinzu
            newTab.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Tab clicked:', this.getAttribute('data-target'));
                
                // Aktiviere den Tab
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Zeige den zugehörigen Tab-Pane
                const target = this.getAttribute('data-target');
                if (target) {
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('active');
                        pane.style.display = 'none';
                    });
                    
                    const targetPane = document.querySelector(target);
                    if (targetPane) {
                        targetPane.classList.add('active');
                        targetPane.style.display = 'block';
                        console.log('Activated pane:', target);
                    } else {
                        console.error('Target pane not found:', target);
                    }
                }
            });
        });
        
        console.log('================================');
        
        fetch('/mcq-test-system/teacher/load_test_results.php')
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
                            <p>Durchschnittliche Leistung: ${group.average_percentage !== null && group.average_percentage !== undefined ? parseFloat(group.average_percentage).toFixed(1) + '%' : 'N/A'}</p>
                        </div>
                    `;
                    
                    group.results.forEach(attempt => {
                        const resultDiv = document.createElement('div');
                        resultDiv.className = 'result-item';
                        resultDiv.innerHTML = `
                            <h3>${attempt.student_name}</h3>
                            <p>Datum: ${new Date(attempt.date).toLocaleString('de-DE')}</p>
                            <p>Punkte: ${attempt.achieved_points}/${attempt.max_points} (${attempt.percentage}%)</p>
                            <p>Note: ${attempt.grade}</p>
                        `;
                        testDiv.appendChild(resultDiv);
                    });
                    
                    resultsList.appendChild(testDiv);
                });
            })
            .catch(error => {
                console.error('Fehler beim Laden der Ergebnisse:', error);
                document.getElementById('results-list').innerHTML = 
                    `<div class="error-message">Fehler beim Laden der Ergebnisse: ${error.message}</div>`;
            });
    }

    // Führe den Code aus, sobald das DOM geladen ist
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded in auswertung.php');
        loadResults();
        
        // Überprüfe, ob jQuery verfügbar ist
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery ist verfügbar, Version:', jQuery.fn.jquery);
            
            // Füge Tab-Event-Handler mit jQuery hinzu
            $('.tab').on('click', function(e) {
                e.preventDefault();
                console.log('jQuery Tab-Click auf:', $(this).data('target'));
                
                // Aktiviere den Tab
                $('.tab').removeClass('active');
                $(this).addClass('active');
                
                // Zeige den zugehörigen Tab-Pane
                const target = $(this).data('target');
                $('.tab-pane').removeClass('active').hide();
                $(target).addClass('active').show();
            });
        } else {
            console.log('jQuery ist nicht verfügbar');
        }
    });
    </script>
</body>
</html>
<?php
} // Ende der FUNCTIONS_ONLY-Überprüfung
?> 