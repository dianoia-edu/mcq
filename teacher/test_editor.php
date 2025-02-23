<?php
// Session wird bereits in teacher_dashboard.php gestartet
// require_once wird bereits in teacher_dashboard.php eingebunden

// Überprüfe Lehrer-Berechtigung
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: index.php');
    exit();
}

// Funktion zum Generieren eines zufälligen Zugangscodes
function generateAccessCode() {
    return substr(str_shuffle('123456789ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 6);
}

// Am Anfang der Datei
$selectedTest = null;
$testContent = null;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $testFile = __DIR__ . '/../tests/' . basename($_GET['edit']) . '.txt';
    if (file_exists($testFile)) {
        $testContent = file_get_contents($testFile);
        $selectedTest = basename($_GET['edit']);
    }
}

// Tests werden bereits im teacher_dashboard.php geladen
// $tests ist bereits verfügbar
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
    </style>
</head>
<body>
    <h1>Test-Editor</h1>
    
    <?php if (isset($message)): ?>
        <div class="<?php echo $message['type']; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>

    <div class="test-editor-container">
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

        <!-- Test-Auswahl -->
        <div class="test-selector">
            <h3>Vorhandenen Test bearbeiten</h3>
            <select name="existingTest" onchange="loadExistingTest(this.value)">
                <option value="">Neuen Test erstellen...</option>
                <?php foreach ($tests as $test): ?>
                    <option value="<?php echo htmlspecialchars($test['name']); ?>"
                        <?php echo ($selectedTest === $test['name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($test['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Bestehender Test-Editor Code -->
        <form id="testForm" method="post" action="teacher_dashboard.php">
            <input type="hidden" name="tab" value="test-editor">
            <!-- Wenn ein Test bearbeitet wird, füge die Test-ID hinzu -->
            <input type="hidden" name="edit_test" id="editTestField" value="">
            <div>
                <label for="title">Testtitel:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div>
                <label for="accessCode">Zugangscode (optional):</label>
                <input type="text" id="accessCode" name="accessCode">
                <button type="button" onclick="generateAccessCode()">Generieren</button>
            </div>

            <div id="questionsContainer">
                <!-- Fragen werden hier dynamisch eingefügt -->
            </div>

            <button type="button" onclick="addQuestion()">Neue Frage hinzufügen</button>
            <button type="submit">Test speichern</button>
        </form>
    </div>

    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>

    <script>
        let questionCounter = 0;
        // Übergebe die Test-Daten aus PHP an JavaScript
        const testsData = <?php echo json_encode($tests); ?>;

        function generateAccessCode() {
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
                <input type="text" name="questions[${questionCounter}][text]" 
                       placeholder="Frage eingeben" required>
                <div class="answers-container">
                    <!-- Antworten werden hier eingefügt -->
                </div>
                <button type="button" onclick="addAnswer(${questionCounter})">
                    Antwort hinzufügen
                </button>
                <button type="button" onclick="removeQuestion(this)">
                    Frage entfernen
                </button>
            `;
            container.appendChild(questionBlock);
            addAnswer(questionCounter); // Füge erste Antwort hinzu
            questionCounter++;
        }

        function addAnswer(questionIndex) {
            const container = document.querySelector(
                `.question-block:nth-child(${questionIndex + 1}) .answers-container`
            );
            const answerBlock = document.createElement('div');
            answerBlock.className = 'answer-block';
            answerBlock.innerHTML = `
                <input type="text" 
                       name="questions[${questionIndex}][answers][][text]" 
                       placeholder="Antwort eingeben" required>
                <label>
                    <input type="checkbox" 
                           name="questions[${questionIndex}][answers][][correct]" 
                           value="1">
                    Richtige Antwort
                </label>
                <button type="button" onclick="removeAnswer(this)">
                    Antwort entfernen
                </button>
            `;
            container.appendChild(answerBlock);
        }

        function removeQuestion(button) {
            const questionBlock = button.closest('.question-block');
            questionBlock.remove();
            questionCounter--;
        }

        function removeAnswer(button) {
            const answerBlock = button.closest('.answer-block');
            answerBlock.remove();
        }

        function loadExistingTest(testName) {
            if (!testName) {
                // Neuer Test - Form zurücksetzen
                document.getElementById('testForm').reset();
                document.getElementById('questionsContainer').innerHTML = '';
                document.getElementById('editTestField').value = '';
                questionCounter = 0;
                addQuestion();
                return;
            }
            
            // Finde Test-Daten
            const test = testsData.find(t => t.name === testName);
            if (!test) return;
            
            // Setze Titel und Zugangscode
            document.getElementById('title').value = test.title;
            document.getElementById('accessCode').value = test.accessCode;
            document.getElementById('editTestField').value = test.name;
            
            // Leere Container und setze Counter zurück
            document.getElementById('questionsContainer').innerHTML = '';
            questionCounter = 0;
            
            // Parse Test-Inhalt
            const lines = test.content.split('\n');
            lines.shift(); // Überspringe Zugangscode
            lines.shift(); // Überspringe Titel
            
            let currentQuestion = null;
            let questions = [];
            
            // Parse Fragen und Antworten
            lines.forEach(line => {
                line = line.trim();
                if (!line) {
                    if (currentQuestion) {
                        questions.push(currentQuestion);
                        currentQuestion = null;
                    }
                    return;
                }
                
                if (line.includes('*[richtig]')) {
                    line = line.replace('*[richtig] ', '');
                    currentQuestion.answers.push({
                        text: line,
                        correct: true
                    });
                } else if (currentQuestion) {
                    currentQuestion.answers.push({
                        text: line,
                        correct: false
                    });
                } else {
                    currentQuestion = {
                        text: line,
                        answers: []
                    };
                }
            });
            
            if (currentQuestion) {
                questions.push(currentQuestion);
            }
            
            // Füge Fragen hinzu
            questions.forEach(question => {
                const questionBlock = document.createElement('div');
                questionBlock.className = 'question-block';
                questionBlock.innerHTML = `
                    <h3>Frage ${questionCounter + 1}</h3>
                    <input type="text" 
                           name="questions[${questionCounter}][text]" 
                           value="${question.text.replace(/"/g, '&quot;')}"
                           placeholder="Frage eingeben" 
                           required>
                    <div class="answers-container">
                        <!-- Antworten werden hier eingefügt -->
                    </div>
                    <button type="button" onclick="addAnswer(${questionCounter})">
                        Antwort hinzufügen
                    </button>
                    <button type="button" onclick="removeQuestion(this)">
                        Frage entfernen
                    </button>
                `;
                document.getElementById('questionsContainer').appendChild(questionBlock);
                
                // Füge Antworten hinzu
                const answersContainer = questionBlock.querySelector('.answers-container');
                question.answers.forEach(answer => {
                    const answerBlock = document.createElement('div');
                    answerBlock.className = 'answer-block';
                    answerBlock.innerHTML = `
                        <input type="text" 
                               name="questions[${questionCounter}][answers][][text]" 
                               value="${answer.text.replace(/"/g, '&quot;')}"
                               placeholder="Antwort eingeben" 
                               required>
                        <label>
                            <input type="checkbox" 
                                   name="questions[${questionCounter}][answers][][correct]" 
                                   value="1"
                                   ${answer.correct ? 'checked' : ''}>
                            Richtige Antwort
                        </label>
                        <button type="button" onclick="removeAnswer(this)">
                            Antwort entfernen
                        </button>
                    `;
                    answersContainer.appendChild(answerBlock);
                });
                
                questionCounter++;
            });
        }
    </script>
</body>
</html>