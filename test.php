<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo '<div style="background:yellow;color:black;padding:10px;z-index:9999;">test.php wurde geladen<br>';
echo 'Session: <pre>' . print_r($_SESSION, true) . '</pre>';
echo 'GET: <pre>' . print_r($_GET, true) . '</pre>';
echo 'POST: <pre>' . print_r($_POST, true) . '</pre>';
echo '</div>';

ob_start();

require_once 'check_test_attempts.php';

// Lade Konfiguration - Anpassung, da config.php nicht mehr existiert
// $config = loadConfig();

// Überprüfe alle erforderlichen Session-Variablen
$requiredSessionVars = ["test_file", "test_code", "student_name"];
foreach ($requiredSessionVars as $var) {
    if (!isset($_SESSION[$var])) {
        error_log("Fehlende Session-Variable in test.php: " . $var);
        error_log("Session-Variablen: " . print_r($_SESSION, true));
        $_SESSION['error'] = "Bitte geben Sie zuerst Ihren Namen ein.";
        header("Location: index.php?code=" . urlencode($_SESSION['test_code']));
        exit();
    }
}

// Überprüfe, ob die Testdatei existiert
if (!file_exists($_SESSION["test_file"])) {
    $_SESSION["error"] = "Der ausgewählte Test ist nicht mehr verfügbar.";
    header("Location: index.php");
    exit();
}

// Überprüfe, ob der Test bereits absolviert wurde
if (hasCompletedTestToday($_SESSION["test_code"], $_SESSION["student_name"])) {
    $_SESSION["error"] = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
    header("Location: index.php");
    exit();
}

// Lade die Konfiguration
$config = [];
$configFile = __DIR__ . '/config/app_config.json';
if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    if ($configContent !== false) {
        $config = json_decode($configContent, true) ?: [];
    }
}

// Debug-Ausgabe für die Konfiguration
error_log("Konfiguration in test.php geladen: " . print_r($config, true));

// Setze Header für keine Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$testFile = $_SESSION["test_file"];
$studentName = $_SESSION["student_name"];

// XML-Datei einlesen und parsen
$xml = simplexml_load_file($testFile);
if ($xml === false) {
    $_SESSION["error"] = "Fehler beim Laden des Tests.";
    header("Location: index.php");
    exit();
}

// Testtitel aus XML extrahieren
$testName = (string)$xml->title;

// Fragen und Antworten aus XML parsen
$questions = [];
foreach ($xml->questions->question as $q) {
    $question = [
        'question' => (string)$q->text,
        'nr' => (string)$q['nr'],
        'answers' => []
    ];
    
    $correctAnswerCount = 0;
    foreach ($q->answers->answer as $a) {
        $answerText = (string)$a->text;
        $isCorrect = ((int)$a->correct === 1);
        if ($isCorrect) {
            $correctAnswerCount++;
        }
        
        $question['answers'][] = [
            'text' => $answerText,
            'isCorrect' => $isCorrect,
            'nr' => (string)$a['nr']
        ];
    }
    
    $question['correctAnswerCount'] = $correctAnswerCount;
    $questions[] = $question;
}

// Originale Fragen-IDs zuweisen
foreach ($questions as $qIndex => &$question) {
    $question['originalIndex'] = $question['nr'];
    foreach ($question['answers'] as $aIndex => &$answer) {
        $answer['originalIndex'] = $answer['nr'];
    }
    unset($answer);
}
unset($question);

// Fragen und Antworten mischen
$shuffledQuestions = $questions;
shuffle($shuffledQuestions);

foreach ($shuffledQuestions as &$question) {
    $shuffledAnswers = $question['answers'];
    shuffle($shuffledAnswers);
    $question['answers'] = $shuffledAnswers;
}
unset($question);

// Anti-Betrugs-Funktion
$_SESSION["confirmationTime"] = time() + rand(30, 180);

// Speichere Fragen in der Session
$_SESSION["original_questions"] = $questions;
$_SESSION["questions"] = $shuffledQuestions;

// Funktion für die Testmodus-Warnung
function getTestModeWarning() {
    global $config;
    if (isset($config['testMode']) && $config['testMode']) {
        return '<div class="test-mode-warning">⚠️ Testmodus aktiv</div>';
    }
    return '';
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
    <title><?php echo $testName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-primary: #1f2937;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        /* Verhindere Textauswahl */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
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
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--card-background);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }

        .student-info {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .question-container {
            margin-bottom: 40px;
            padding: 25px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background-color: white;
            transition: all 0.3s ease;
        }

        .question-container:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .question-number {
            font-size: 1rem;
            color: white;
            background-color: var(--primary-color);
            border-radius: 50%; /* Runder Kreis */
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            flex-shrink: 0; /* Verhindert Schrumpfen */
        }

        .question-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
            color: var(--text-primary);
        }
        
        .question-header {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }
        
        .question-info {
            flex: 1;
        }
        
        .question-counter {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .answers-container {
            margin-top: 15px;
        }

        .answer-option {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .answer-option:hover {
            background-color: #f8fafc;
            border-color: var(--primary-color);
        }

        .answer-option input[type="radio"],
        .answer-option input[type="checkbox"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
        }

        .answer-option label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }

        .btn-submit {
            display: inline-block;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 500;
            color: white;
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }
        
        .btn-backup {
            display: inline-block;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 500;
            color: white;
            background-color: var(--success-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-backup:hover {
            background-color: #0d9668;
            transform: translateY(-1px);
        }

        .form-actions {
            margin-top: 40px;
            text-align: center;
        }

        .test-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .test-title {
            color: var(--text-primary);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .student-name {
            color: #4b5563;
            font-size: 1.1rem;
        }

        .test-mode-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #ffeeba;
            border-radius: 0.25rem;
            text-align: center;
            font-weight: bold;
        }
    </style>
    <link rel="stylesheet" href="./styles.css">
    <script src="attention.js"></script>
</head>
<body data-test-mode="<?php echo isset($config['testMode']) && $config['testMode'] ? 'true' : 'false'; ?>"
      data-disable-attention-button="<?php 
        $disableButton = isset($config['disableAttentionButton']) && $config['disableAttentionButton'] === true ? 'true' : 'false';
        echo $disableButton; 
        // Debug-Ausgabe in die Konsole
        echo "<!-- Debug: disableAttentionButton = " . var_export($config['disableAttentionButton'] ?? false, true) . " -->";
      ?>">
    
    <script>
    // Vollbildmodus-Funktionen
    function enterFullscreen() {
        const element = document.documentElement;
        
        // iOS Safari spezifische Implementierung
        if (element.webkitEnterFullscreen) {
            element.webkitEnterFullscreen();
        } else if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) { // Desktop Safari
            element.webkitRequestFullscreen();
        } else if (element.msRequestFullscreen) { // IE11
            element.msRequestFullscreen();
        }
    }

    function exitFullscreen() {
        // iOS Safari spezifische Implementierung
        if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitCancelFullScreen) { // Desktop Safari
            document.webkitCancelFullScreen();
        } else if (document.msExitFullscreen) { // IE11
            document.msExitFullscreen();
        }
    }

    // Beim Laden der Seite Vollbildmodus aktivieren
    document.addEventListener('DOMContentLoaded', function() {
        enterFullscreen();
    });

    // Beim Absenden des Formulars Vollbildmodus verlassen
    document.getElementById('testForm').addEventListener('submit', function() {
        exitFullscreen();
    });
    </script>

    <?php echo getTestModeWarning(); ?>
    
    <div class="container">
        <div class="test-header">
            <h1 class="test-title"><?php echo htmlspecialchars($testName); ?></h1>
            <div class="student-info">
                <h2 class="student-name"><?php echo htmlspecialchars($studentName); ?></h2>
            </div>
        </div>
        
        <form method="post" action="process.php" id="testForm">
            <?php foreach ($shuffledQuestions as $qIndex => $question): ?>
                <div class="question-container">
                    <div class="question-header">
                        <div class="question-number"><?php echo $qIndex + 1; ?></div>
                        <div class="question-info">
                            <div class="question-counter">Frage <?php echo $qIndex + 1; ?> von <?php echo count($shuffledQuestions); ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($question["question"]); ?></div>
                        </div>
                    </div>
                    
                    <div class="answers-container">
                        <?php if (!empty($question["answers"])): ?>
                            <?php foreach ($question["answers"] as $aIndex => $answer): ?>
                                <div class="answer-option">
                                    <?php if ($question['correctAnswerCount'] === 1): ?>
                                        <input type="radio" 
                                               name="answer_<?php echo $question['nr']; ?>" 
                                               value="<?php echo $answer['nr']; ?>" 
                                               id="q<?php echo $qIndex; ?>_a<?php echo $aIndex; ?>"
                                               class="question-input">
                                    <?php else: ?>
                                        <input type="checkbox" 
                                               name="answer_<?php echo $question['nr']; ?>[]" 
                                               value="<?php echo $answer['nr']; ?>"
                                               id="q<?php echo $qIndex; ?>_a<?php echo $aIndex; ?>"
                                               class="question-input">
                                    <?php endif; ?>
                                    <label for="q<?php echo $qIndex; ?>_a<?php echo $aIndex; ?>">
                                        <?php echo htmlspecialchars($answer["text"]); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" id="backupButton" class="btn-backup btn-success">Sicherheitskopie erstellen</button>
                <button type="button" id="submitButton" class="btn-submit">Test abschließen</button>
                </div>
            </div>
            
            <!-- Warnung Modal -->
            <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="warningModalLabel">Warnung - Nicht alle Fragen beantwortet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Sie haben nicht alle Fragen beantwortet. Möchten Sie den Test trotzdem abschicken?</p>
                            <p id="unansweredQuestions"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zurück zum Test</button>
                            <button type="button" class="btn btn-warning" id="confirmSubmit">Ja, Test abschicken</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Debug-Informationen auskommentiert
        /*
        console.log('Debug Information - Test Anzeige:');
        console.log('Test Name:', '<?php echo htmlspecialchars($testName); ?>');
        console.log('Student Name:', '<?php echo htmlspecialchars($studentName); ?>');
        console.log('Questions:', <?php echo json_encode($shuffledQuestions); ?>);
        */
        
        // Formular-Validierung
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('testForm');
            const submitButton = document.getElementById('submitButton');
            const backupButton = document.getElementById('backupButton');
            const confirmSubmitButton = document.getElementById('confirmSubmit');
            let warningModal;
            
            // Bootstrap-Modal initialisieren
            try {
                warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
            } catch (e) {
                console.error('Fehler beim Initialisieren des Modals:', e);
            }
            
            // Funktion zum Erstellen und Herunterladen der XML-Sicherungsdatei
            function createAndDownloadBackup() {
                console.log("Erstelle XML-Sicherungskopie...");
                const studentName = '<?php echo htmlspecialchars($studentName); ?>';
                const testName = '<?php echo htmlspecialchars($testName); ?>';
                const testCode = '<?php echo htmlspecialchars($_SESSION["test_code"]); ?>';
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                const filename = `${testCode}_${studentName}_${timestamp}_backup.xml`;
                
                // XML-Struktur erstellen
                // Verwende Verkettung für die XML-Deklaration, um PHP-Parsing-Probleme zu vermeiden
                let xmlContent = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>\n';
                xmlContent += '<test>\n';
                xmlContent += `  <title>${testName}</title>\n`;
                xmlContent += `  <code>${testCode}</code>\n`;
                xmlContent += `  <schuelername>${studentName}</schuelername>\n`;
                xmlContent += `  <abgabezeit>${new Date().toISOString()}</abgabezeit>\n`;
                xmlContent += '  <questions>\n';
                
                // Alle Fragen und gewählten Antworten durchgehen
                const questionContainers = document.querySelectorAll('.question-container');
                questionContainers.forEach((container, qIndex) => {
                    const questionText = container.querySelector('.question-text').textContent;
                    const questionNr = <?php echo json_encode($shuffledQuestions); ?>[qIndex].nr;
                    
                    xmlContent += `    <question nr="${questionNr}">\n`;
                    xmlContent += `      <text>${questionText}</text>\n`;
                    xmlContent += '      <answers>\n';
                    
                    // Finde alle Antworten für diese Frage
                    const answerOptions = container.querySelectorAll('.answer-option');
                    answerOptions.forEach((option, aIndex) => {
                        const answerText = option.querySelector('label').textContent.trim();
                        const input = option.querySelector('.question-input');
                        const answerNr = input.value;
                        const isChecked = input.checked ? '1' : '0';
                        
                        xmlContent += `        <answer nr="${answerNr}">\n`;
                        xmlContent += `          <text>${answerText}</text>\n`;
                        xmlContent += `          <schuelerantwort>${isChecked}</schuelerantwort>\n`;
                        xmlContent += '        </answer>\n';
                    });
                    
                    xmlContent += '      </answers>\n';
                    xmlContent += '    </question>\n';
                });
                
                xmlContent += '  </questions>\n';
                xmlContent += '</test>';
                
                // XML-Datei erzeugen und herunterladen
                const blob = new Blob([xmlContent], { type: 'application/xml' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                
                // Aufräumen
                setTimeout(() => {
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                }, 100);
                
                console.log("XML-Sicherungskopie erstellt und heruntergeladen");
                
                // Zeige eine Erfolgsmeldung an
                alert("Sicherheitskopie wurde erfolgreich erstellt. Sie können jetzt den Test abschließen.");
                
                return true;
            }
            
            // Prüfen, ob alle Fragen beantwortet wurden - verbesserte Version
            function checkAllQuestionsAnswered() {
                console.log("Prüfe beantwortete Fragen...");
                const questionContainers = document.querySelectorAll('.question-container');
                let unansweredQuestions = [];
                
                questionContainers.forEach((container, index) => {
                    const inputs = container.querySelectorAll('.question-input:checked');
                    console.log(`Frage ${index + 1}: ${inputs.length} Antwort(en) ausgewählt`);
                    
                    // Eine Frage gilt als beantwortet, wenn MINDESTENS eine Antwort gegeben wurde,
                    // unabhängig davon, wie viele richtige Antworten es gibt
                    if (inputs.length === 0) {
                        unansweredQuestions.push(index + 1);
                    }
                });
                
                console.log("Nicht beantwortete Fragen:", unansweredQuestions);
                return {
                    allAnswered: unansweredQuestions.length === 0,
                    unanswered: unansweredQuestions
                };
            }
            
            // Sicherheitskopie-Button Event
            backupButton.addEventListener('click', function() {
                createAndDownloadBackup();
            });
            
            // Submit-Button Klick-Event
            submitButton.addEventListener('click', function() {
                const result = checkAllQuestionsAnswered();
                
                if (result.allAnswered) {
                    // Alle Fragen beantwortet - Formular absenden
                    console.log("Alle Fragen wurden beantwortet, sende Formular ab...");
                    form.submit();
                } else {
                    // Nicht alle Fragen beantwortet - Warnung anzeigen
                    const unansweredElement = document.getElementById('unansweredQuestions');
                    unansweredElement.textContent = `Nicht beantwortete Fragen: ${result.unanswered.join(', ')}`;
                    
                    // Modal anzeigen
                    if (warningModal) {
                        console.log("Zeige Warnung an...");
                        warningModal.show();
                    } else {
                        // Fallback, falls Modal nicht funktioniert
                        console.log("Modal nicht verfügbar, verwende Confirm-Dialog");
                        if (confirm('Sie haben nicht alle Fragen beantwortet. Möchten Sie den Test trotzdem abschicken?')) {
                            form.submit();
                        }
                    }
                }
            });
            
            // Bestätigungs-Button im Modal
            confirmSubmitButton.addEventListener('click', function() {
                if (warningModal) {
                    warningModal.hide();
                }
                // Formular absenden auch wenn nicht alle Fragen beantwortet wurden
                console.log("Bestätigung zum Absenden erhalten, sende Formular ab...");
                form.submit();
            });
        });
    </script>
</body>
</html>