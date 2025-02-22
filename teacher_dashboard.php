<?php
session_start();
require_once 'config.php';

// Cache-Prevention-Header
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION["teacher"]) || $_SESSION["teacher"] !== true) {
    header("Location: index.php");
    exit;
}

// Verarbeite AJAX-Anfragen für Konfigurationsänderungen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'updateTestMode') {
        $config = [
            'testMode' => $_POST['testMode'] === 'true',
            'disableAttentionButton' => $_POST['testMode'] === 'true',
            'allowTestRepetition' => $_POST['testMode'] === 'true'
        ];
        saveConfig($config);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Lade aktuelle Konfiguration
$config = loadConfig();

function saveTestResult($data) {
    $testName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['testName']);
    $studentName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['studentName']);
    $timestamp = date('Y-m-d_H-i-s');
    
    // Füge Information über Testabbruch hinzu
    $data['testAborted'] = $data['testAborted'] ?? false;
    $data['missedClicks'] = $data['missedClicks'] ?? 0;
    
    $resultFile = "results/{$testName}_{$studentName}_{$timestamp}.txt";
    file_put_contents($resultFile, json_encode($data));
    
    return ['success' => true, 'message' => 'Ergebnis gespeichert'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    header('Content-Type: application/json');
    echo json_encode(saveTestResult($data));
    exit;
}

$testFiles = glob("tests/*.txt");
$tests = [];

foreach ($testFiles as $testFile) {
    $lines = file($testFile, FILE_IGNORE_NEW_LINES);
    $tests[] = [
        "file" => $testFile,
        "name" => $lines[1],
        "accessCode" => $lines[0]
    ];
}

$selectedTest = $_GET['test'] ?? '';
$results = [];

if (!empty($selectedTest)) {
    $safeTestName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $selectedTest);
    error_log("Suche nach Ergebnisdateien mit Muster: results/{$safeTestName}_*.txt");
    
    $resultFiles = glob("results/{$safeTestName}_*.txt");
    error_log("Gefundene Dateien: " . print_r($resultFiles, true));
    
    foreach ($resultFiles as $resultFile) {
        $resultData = json_decode(file_get_contents($resultFile), true);
        if ($resultData) {
            $results[] = $resultData;
        }
    }
    
    usort($results, function($a, $b) {
        return strtotime($b['submissionDate']) - strtotime($a['submissionDate']);
    });
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Lehrer-Dashboard - MCQ Test System</title>
    <style>
        /* Grundlegende Stile inline */
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --border-color: #e5e7eb;
            --danger-color: #dc2626;
            --warning-bg: #fff3cd;
            --success-color: #16a34a;
            --error-color: #dc2626;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .dashboard-section {
            margin-bottom: 2rem;
        }

        .student-result-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            color: white;
            background-color: var(--primary-color);
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
        }

        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            background-color: white;
        }

        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            display: none;
        }

        .lightbox-content {
            position: relative;
            max-width: 900px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: white;
            border-radius: 0.75rem;
            max-height: 90vh;
            overflow-y: auto;
        }

        .compact-info-table {
            width: 100%;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
        }

        .compact-info-table td {
            padding: 0.5rem;
            font-size: 0.95rem;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .results-table th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }

        .results-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .question-number {
            font-weight: bold;
            text-align: center;
            width: 50px;
        }

        .question-text {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .answers-container {
            display: flex;
            gap: 2rem;
        }

        .answer-column {
            flex: 1;
        }

        .answer-list {
            list-style: none;
            padding-left: 0;
            margin: 0.5rem 0;
            font-size: 0.9rem;
        }

        .answer-list li {
            padding: 0.25rem 0;
        }

        .points-cell {
            text-align: center;
            font-weight: bold;
        }

        .correct-row {
            background-color: #f0fdf4;
        }

        .incorrect-row {
            background-color: #fef2f2;
        }

        .no-answer {
            color: #dc2626;
            font-style: italic;
            font-size: 0.9rem;
        }

        .aborted-test {
            border-left: 4px solid var(--danger-color);
            background-color: var(--warning-bg);
        }
        
        .aborted-badge {
            background-color: var(--danger-color);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 8px;
        }

        .test-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .status-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-aborted {
            background-color: var(--danger-color);
        }

        .progress-info {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .answer-correct {
            color: var(--success-color);
            font-weight: 500;
        }

        .answer-incorrect {
            color: var(--error-color);
            text-decoration: line-through;
        }

        .answer-missing {
            color: var(--error-color);
            font-style: italic;
        }

        .test-mode-warning {
            background-color: #ff4444;
            color: white;
            padding: 1rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            font-weight: bold;
        }

        .test-mode-controls {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .test-mode-controls h2 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #dc3545;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .toggle-switch label {
            margin-left: 0.5rem;
            user-select: none;
        }

        .toggle-switch input[type="checkbox"] {
            width: 3rem;
            height: 1.5rem;
        }

        .warning-text {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        /* Neue Styles für Tabs und Schieberegler */
        .tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .tab {
            padding: 1rem 2rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Moderner Schieberegler */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #ff4444;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .test-mode-control {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .test-mode-control label {
            font-weight: 500;
            font-size: 1.1rem;
        }
    </style>
    <link rel="stylesheet" href="./styles.css?v=<?php echo time(); ?>">
    <script>
        function showDetails(resultData) {
            const lightbox = document.createElement('div');
            lightbox.className = 'lightbox';
            
            const content = document.createElement('div');
            content.className = 'lightbox-content';
            
            const data = JSON.parse(decodeURIComponent(resultData));
            
            // Konvertiere Datum in lokales Format
            const testDate = new Date(data.submissionDate).toLocaleString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            content.innerHTML = `
                <button class="close-lightbox" onclick="closeLightbox(this)">&times;</button>
                <div class="lightbox-header">
                    <table class="compact-info-table">
                        <tr>
                            <td><strong>Schüler:</strong> ${data.studentName}</td>
                            <td><strong>Test:</strong> ${data.testName}</td>
                            <td><strong>Datum:</strong> ${testDate}</td>
                        </tr>
                        <tr>
                            <td><strong>Punkte:</strong> ${data.totalPoints}/${data.maxPoints}</td>
                            <td><strong>Prozent:</strong> ${data.percentage.toFixed(1)}%</td>
                            <td><strong>Note:</strong> ${data.grade}</td>
                        </tr>
                        ${data.isAborted ? `
                        <tr>
                            <td colspan="3" style="color: var(--danger-color);">
                                <strong>Test abgebrochen</strong> nach ${data.missedClicks} verpassten Aufmerksamkeitsklicks<br>
                                <small>Bearbeitete Fragen: ${data.answeredQuestions} von ${data.totalQuestions}</small>
                            </td>
                        </tr>
                        ` : ''}
                    </table>
                </div>

                <div class="detailed-results">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Nr.</th>
                                <th>Frage & Antworten</th>
                                <th style="width: 60px">Punkte</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.results.map((result, index) => `
                                <tr class="${result.points > 0 ? 'correct-row' : 'incorrect-row'}">
                                    <td class="question-number">${index + 1}</td>
                                    <td>
                                        <div class="question-text">${result.question}</div>
                                        <div class="answers-container">
                                            <div class="answer-column">
                                                <strong>Gewählt:</strong><br>
                                                <span class="${result.selectedAnswers.length === 0 ? 'no-answer' : ''}">
                                                    ${result.selectedAnswers.length === 0 ? 'Keine Auswahl' : result.selectedAnswers.map(answer => {
                                                        const isCorrect = result.correctAnswers.includes(answer);
                                                        return `<div class="${isCorrect ? 'answer-correct' : 'answer-incorrect'}">${answer}</div>`;
                                                    }).join('')}
                                                </span>
                                            </div>
                                            <div class="answer-column">
                                                <strong>Richtig:</strong><br>
                                                ${result.correctAnswers.map(answer => {
                                                    const wasSelected = result.selectedAnswers.includes(answer);
                                                    return `<div class="${wasSelected ? 'answer-correct' : 'answer-missing'}">${answer}</div>`;
                                                }).join('')}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="points-cell">${result.points}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;
            
            lightbox.appendChild(content);
            document.body.appendChild(lightbox);
            lightbox.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox(button) {
            const lightbox = button.closest('.lightbox');
            lightbox.remove();
            document.body.style.overflow = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Tab-Funktionalität
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    
                    tab.classList.add('active');
                    document.querySelector(tab.dataset.target).classList.add('active');
                });
            });
            
            // Testmodus-Schieberegler
            const testModeToggle = document.getElementById('testModeToggle');
            testModeToggle.checked = <?php echo $config['testMode'] ? 'true' : 'false'; ?>;
            
            testModeToggle.addEventListener('change', function() {
                fetch('teacher_dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=updateTestMode&testMode=' + this.checked
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            });
        });
    </script>
</head>
<body>
    <?php echo getTestModeWarning(); ?>
    
    <div class="container">
        <h1>Lehrer-Dashboard</h1>
        
        <div class="tabs">
            <div class="tab active" data-target="#testResults">Testergebnisse</div>
            <div class="tab" data-target="#configuration">Konfiguration</div>
        </div>
        
        <div id="configuration" class="tab-content">
            <div class="test-mode-control">
                <label for="testModeToggle">Testmodus</label>
                <label class="switch">
                    <input type="checkbox" id="testModeToggle">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        
        <div id="testResults" class="tab-content active">
            <div class="dashboard-section">
                <h2>Test zur Auswertung auswählen</h2>
                <form method="get" action="teacher_dashboard.php" class="form-select-container">
                    <div class="form-group">
                        <select name="test" class="form-control" onchange="this.form.submit()">
                            <option value="">Bitte wählen...</option>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?php echo htmlspecialchars($test["name"]); ?>" 
                                        <?php echo $selectedTest === $test["name"] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($test["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($selectedTest)): ?>
                <div class="dashboard-section">
                    <h2>Testergebnisse: <?php echo htmlspecialchars($selectedTest); ?></h2>
                    <?php if (!empty($results)): ?>
                        <div class="student-list">
                            <?php foreach ($results as $result): ?>
                                <div class="student-result-item <?php echo isset($result['isAborted']) && $result['isAborted'] ? 'aborted-test' : ''; ?>">
                                    <div class="student-result-info">
                                        <strong><?php echo htmlspecialchars($result["studentName"]); ?></strong>
                                        <?php if (isset($result['isAborted']) && $result['isAborted']): ?>
                                            <span class="aborted-badge">Abgebrochen</span>
                                        <?php endif; ?>
                                        <br>
                                        <?php echo $result["totalPoints"]; ?> von <?php echo $result["maxPoints"]; ?> Punkten
                                        (<?php echo number_format($result["percentage"], 1); ?>%): Note <?php echo $result["grade"]; ?>
                                        
                                        <div class="test-status">
                                            <?php if (isset($result['isAborted']) && $result['isAborted']): ?>
                                                <span class="status-icon status-aborted"></span>
                                                <span class="progress-info">
                                                    Test abgebrochen nach <?php echo $result['missedClicks']; ?> verpassten Klicks
                                                    (<?php echo $result['answeredQuestions']; ?> von <?php echo $result['totalQuestions']; ?> Fragen bearbeitet)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="submission-info">
                                            <small>
                                                Abgegeben: <?php echo date("d.m.Y H:i", strtotime($result["submissionDate"])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="student-result-actions">
                                        <button class="btn secondary-btn" 
                                                onclick='showDetails(<?php echo json_encode(json_encode($result)); ?>)'>
                                            Details anzeigen
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-results">Keine Ergebnisse für diesen Test verfügbar.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="navigation">
            <a href="index.php" class="btn secondary-btn">Zurück zur Startseite</a>
        </div>
    </div>
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>
</body>
</html>