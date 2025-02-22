<?php
ob_start();
session_start();

require_once 'check_test_attempts.php';
require_once 'config.php';

// Lade Konfiguration
$config = loadConfig();

// Überprüfe alle erforderlichen Session-Variablen
$requiredSessionVars = ["testFile", "testName", "studentName", "loginTime"];
foreach ($requiredSessionVars as $var) {
    if (!isset($_SESSION[$var])) {
        header("Location: index.php");
        exit();
    }
}

// Überprüfe, ob die Testdatei existiert
if (!file_exists($_SESSION["testFile"])) {
    $_SESSION["error"] = "Der ausgewählte Test ist nicht mehr verfügbar.";
    header("Location: index.php");
    exit();
}

// Überprüfe, ob der Test bereits absolviert wurde
if (hasCompletedTestToday($_SESSION["testName"])) {
    $_SESSION["error"] = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
    header("Location: index.php");
    exit;
}

// Setze Header für keine Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$testFile = $_SESSION["testFile"];
$testName = $_SESSION["testName"];
$studentName = $_SESSION["studentName"];

// Testdatei einlesen
$content = file_get_contents($testFile);
$lines = explode("\n", $content);

// Entferne Zugangscode und Titel
array_shift($lines); // Entferne Zugangscode
array_shift($lines); // Entferne Titel

// Fragen und Antworten parsen
$questions = [];
$currentQuestion = null;
$currentAnswers = [];

// Füge eine leere Zeile am Ende hinzu, um das Parsing zu vereinfachen
$lines[] = "";

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    
    // Wenn die Zeile leer ist und wir eine aktuelle Frage haben
    if ($line === "") {
        if ($currentQuestion !== null && !empty($currentAnswers)) {
            $currentQuestion['answers'] = $currentAnswers;
            $questions[] = $currentQuestion;
            $currentQuestion = null;
            $currentAnswers = [];
        }
        continue;
    }
    
    // Neue Frage beginnt mit einem Fragezeichen
    if (strpos($line, '?') !== false) {
        if ($currentQuestion !== null && !empty($currentAnswers)) {
            $currentQuestion['answers'] = $currentAnswers;
            $questions[] = $currentQuestion;
            $currentAnswers = [];
        }
        
        $currentQuestion = [
            'question' => $line,
            'answers' => []
        ];
    } 
    // Antwortzeile
    elseif ($currentQuestion !== null) {
        $isCorrect = (strpos($line, '*[richtig]') !== false);
        $answerText = trim(str_replace('*[richtig]', '', $line));
        
        // Behandle alle Antworten, auch "0"
        if ($answerText !== "") {
            $currentAnswers[] = [
                'text' => $answerText,
                'isCorrect' => $isCorrect
            ];
        }
    }
    
    // Wenn wir am Ende der Datei sind und noch eine Frage haben
    if ($lineNum === count($lines) - 1 && $currentQuestion !== null && !empty($currentAnswers)) {
        $currentQuestion['answers'] = $currentAnswers;
        $questions[] = $currentQuestion;
    }
}

// Originale Fragen-IDs zuweisen
foreach ($questions as $qIndex => &$question) {
    $question['originalIndex'] = $qIndex;
    foreach ($question['answers'] as $aIndex => &$answer) {
        $answer['originalIndex'] = $aIndex;
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
    <style>
        /* Grundlegende Stile inline */
        :root {
            --primary-color: #2563eb;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-primary: #1f2937;
            --border-color: #e5e7eb;
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
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .student-info {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #ecf0f1;
            border-radius: 6px;
        }

        .question-container {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: white;
        }

        .answer-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
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
        }
    </style>
    <link rel="stylesheet" href="./styles.css">
    <script src="attention.js"></script>
</head>
<body data-test-mode="<?php echo isset($config['testMode']) && $config['testMode'] ? 'true' : 'false'; ?>"
      data-disable-attention-button="<?php echo isset($config['disableAttentionButton']) && $config['disableAttentionButton'] ? 'true' : 'false'; ?>">
    <?php echo getTestModeWarning(); ?>
    
    <div class="container test-container">
        <h1><?php echo $testName; ?></h1>
        <div class="student-info">
            <h2>Schüler: <?php echo htmlspecialchars($studentName); ?></h2>
        </div>
        
        <form method="post" action="process.php" id="testForm">
            <?php foreach ($shuffledQuestions as $qIndex => $question): ?>
                <div class="question-container">
                    <h3>Frage <?php echo $qIndex + 1; ?>: <?php echo htmlspecialchars($question["question"]); ?></h3>
                    
                    <?php
                    $correctCount = 0;
                    foreach ($question["answers"] as $answer) {
                        if ($answer["isCorrect"]) $correctCount++;
                    }
                    $inputType = $correctCount > 1 ? "checkbox" : "radio";
                    ?>
                    
                    <div class="answers-container">
                        <?php if (!empty($question["answers"])): ?>
                            <?php foreach ($question["answers"] as $aIndex => $answer): ?>
                                <div class="answer-option">
                                    <input type="<?php echo $inputType; ?>" 
                                           id="q<?php echo $qIndex; ?>_a<?php echo $aIndex; ?>" 
                                           name="answers[<?php echo $question['originalIndex']; ?>]<?php echo $inputType === 'checkbox' ? '[]' : ''; ?>" 
                                           value="<?php echo $answer['originalIndex']; ?>"
                                           <?php echo $inputType === 'radio' ? 'required' : ''; ?>>
                                    <label for="q<?php echo $qIndex; ?>_a<?php echo $aIndex; ?>">
                                        <?php echo htmlspecialchars($answer["text"]); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Keine Antwortoptionen verfügbar.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="submit-container">
                <button type="submit" class="btn primary-btn">Test abgeben</button>
            </div>
        </form>
    </div>
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>
</body>
</html>