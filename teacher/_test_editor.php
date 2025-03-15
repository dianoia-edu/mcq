<?php
// Aktiviere Error-Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Starte die Session
session_start();

// Debug-Ausgaben
error_log("\n\n=== Neue Anfrage an test_editor.php ===");
error_log('Script Path: ' . __FILE__);
error_log('GET-Parameter: ' . print_r($_GET, true));
error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
error_log('SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME']);
error_log('SESSION: ' . print_r($_SESSION, true));

// Überprüfe Lehrer-Berechtigung
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    error_log('Keine Lehrer-Berechtigung gefunden');
    error_log('Session-Status: ' . print_r($_SESSION, true));
    header('Location: ../index.php');
    exit();
}

error_log('Lehrer-Berechtigung bestätigt - Fahre fort...');

// Funktion zum Generieren eines zufälligen Zugangscodes
function generateAccessCode() {
    return substr(str_shuffle('123456789ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 3);
}

// Lade Test zum Bearbeiten
$selectedTest = null;
if (isset($_GET['test']) && !empty($_GET['test'])) {
    $testsDir = __DIR__ . '/../tests/';
    error_log('Suche nach Tests in: ' . $testsDir);
    
    // Hole alle XML-Dateien im tests-Verzeichnis
    $xmlFiles = glob($testsDir . '*.xml');
    
    if (!empty($xmlFiles)) {
        // Sortiere nach Änderungsdatum (neueste zuerst)
        usort($xmlFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Nimm die neueste Datei
        $latestFile = $xmlFiles[0];
        error_log('Neueste Test-Datei gefunden: ' . $latestFile);
        
        // Lade den XML-Inhalt
        $xmlContent = file_get_contents($latestFile);
        
        // Parse XML
        $xml = simplexml_load_string($xmlContent);
        if ($xml) {
            $selectedTest = [
                'content' => $xmlContent,
                'name' => basename($latestFile, '.xml'),
                'title' => (string)$xml->title,
                'access_code' => (string)$xml->access_code,
                'questions' => []
            ];
            
            // Lade Fragen und Antworten
            foreach ($xml->questions->question as $question) {
                $q = [
                    'text' => (string)$question->text,
                    'answers' => []
                ];
                
                foreach ($question->answers->answer as $answer) {
                    $q['answers'][] = [
                        'text' => (string)$answer->text,
                        'correct' => (string)$answer->correct == '1'
                    ];
                }
                
                $selectedTest['questions'][] = $q;
            }
            
            error_log('Test erfolgreich geladen: ' . $selectedTest['name']);
        } else {
            error_log('Fehler beim Parsen der XML-Datei');
        }
    } else {
        error_log('Keine Test-Dateien im Verzeichnis gefunden');
    }
}

// Weiterleitung zum Dashboard nur wenn kein Test gefunden wurde
if (isset($_GET['test']) && !$selectedTest) {
    error_log('Test nicht gefunden - Weiterleitung zum Dashboard mit Fehlermeldung');
    header('Location: teacher_dashboard.php?tab=editor&error=test_not_found');
    exit();
}

// Weiterleitung zum Test-Editor-Tab im Dashboard
error_log('Weiterleitung zum Dashboard mit Test-Parameter');
error_log('Weiterleitungs-URL: teacher_dashboard.php?tab=editor&test=' . urlencode($_GET['test']));
header('Location: teacher_dashboard.php?tab=editor&test=' . urlencode($_GET['test']));
error_log('Header für Weiterleitung gesetzt - Script wird beendet');
exit();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Test-Editor - Lehrer-Dashboard</title>
    <style>
        .validation-error {
            border: 2px solid #ff4444;
            padding: 10px;
            margin: 10px 0;
            background-color: #fff0f0;
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .alert.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }
        .alert.success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .question-block, .answer-block {
            margin: 1rem 0;
            padding: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .error { color: red; }
        .success { color: green; }
        .editor-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .code-group {
            display: flex;
            gap: 10px;
        }
        .answers-container {
            margin: 10px 0;
            padding-left: 20px;
        }
        .answer-block {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>Test-Editor</h1>
    
    <?php if (isset($message)): ?>
        <div class="<?php echo $message['type']; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>

    <div class="editor-container">
        <h2><?php echo $selectedTest ? 'Test bearbeiten' : 'Neuen Test erstellen'; ?></h2>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <form id="testForm" method="post" action="save_test.php">
            <div class="form-group">
                <label for="testTitle">Titel:</label>
                <input type="text" id="testTitle" name="title" required>
            </div>

            <div class="form-group">
                <label for="accessCode">Zugangscode:</label>
                <div class="code-group">
                    <input type="text" id="accessCode" name="accessCode" value="<?php echo generateAccessCode(); ?>" required>
                    <button type="button" onclick="generateNewCode()">Neu generieren</button>
                </div>
            </div>

            <div id="questionsContainer">
                <!-- Fragen werden hier dynamisch eingefügt -->
            </div>

            <button type="button" onclick="addQuestion()" class="btn">Frage hinzufügen</button>
            <button type="submit" class="btn primary-btn">Test speichern</button>
        </form>
    </div>

    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>

    <script>
        let questionCounter = 0;

        function generateNewCode() {
            // Einfache Frontend-Implementierung
            const chars = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
            let code = '';
            for (let i = 0; i < 6; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('accessCode').value = code;
        }

        function addQuestion() {
            const container = document.getElementById('questionsContainer');
            const questionBlock = document.createElement('div');
            questionBlock.className = 'question-block';
            questionBlock.innerHTML = `
                <h3>Frage ${questionCounter + 1}</h3>
                <input type="text" name="questions[${questionCounter}][text]" placeholder="Frage eingeben" required>
                <div class="answers-container">
                    <!-- Antworten werden hier eingefügt -->
                </div>
                <button type="button" onclick="addAnswer(${questionCounter})">Antwort hinzufügen</button>
                <button type="button" onclick="removeQuestion(this)">Frage entfernen</button>
            `;
            container.appendChild(questionBlock);
            questionCounter++;
        }

        function addAnswer(questionIndex) {
            const container = document.querySelector(`[name="questions[${questionIndex}][text]"]`).parentNode.querySelector('.answers-container');
            const answerBlock = document.createElement('div');
            answerBlock.className = 'answer-block';
            answerBlock.innerHTML = `
                <input type="text" name="questions[${questionIndex}][answers][][text]" placeholder="Antwort eingeben" required>
                <label>
                    <input type="checkbox" name="questions[${questionIndex}][answers][][correct]" value="1">
                    Richtige Antwort
                </label>
                <button type="button" onclick="removeAnswer(this)">Entfernen</button>
            `;
            container.appendChild(answerBlock);
        }

        function removeQuestion(button) {
            button.parentNode.remove();
        }

        function removeAnswer(button) {
            button.parentNode.remove();
        }

        // Lade vorhandenen Test, falls vorhanden
        <?php if ($selectedTest): ?>
        loadExistingTest(<?php echo json_encode($selectedTest); ?>);
        <?php endif; ?>

        function loadExistingTest(test) {
            // Setze Titel und Zugangscode
            document.getElementById('testTitle').value = test.title;
            document.getElementById('accessCode').value = test.access_code;
            
            // Leere den Container für Fragen
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';
            
            // Füge jede Frage hinzu
            test.questions.forEach((question, qIndex) => {
                const questionBlock = document.createElement('div');
                questionBlock.className = 'question-block';
                questionBlock.innerHTML = `
                    <h3>Frage ${qIndex + 1}</h3>
                    <input type="text" name="questions[${qIndex}][text]" placeholder="Frage eingeben" required value="${escapeHtml(question.text)}">
                    <div class="answers-container">
                        ${question.answers.map((answer, aIndex) => `
                            <div class="answer-block">
                                <input type="text" name="questions[${qIndex}][answers][][text]" placeholder="Antwort eingeben" required value="${escapeHtml(answer.text)}">
                                <label>
                                    <input type="checkbox" name="questions[${qIndex}][answers][][correct]" value="1" ${answer.correct ? 'checked' : ''}>
                                    Richtige Antwort
                                </label>
                                <button type="button" onclick="removeAnswer(this)">Entfernen</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" onclick="addAnswer(${qIndex})">Antwort hinzufügen</button>
                    <button type="button" onclick="removeQuestion(this)">Frage entfernen</button>
                `;
                container.appendChild(questionBlock);
                questionCounter = qIndex + 1;
            });
        }
        
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>