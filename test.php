<?php
ob_start();

// Gemeinsame Funktionen einbinden
require_once 'includes/functions/common_functions.php';
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
    exit;
}

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
    <!-- Globale CSS-Datei -->
    <link href="css/global.css" rel="stylesheet">
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

        .question-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 20px;
            color: var(--text-primary);
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

        .form-actions {
            margin-top: 40px;
            text-align: center;
        }

        .question-number {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 8px;
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
    </style>
    <link rel="stylesheet" href="./styles.css">
    <script src="attention.js"></script>
</head>
<body data-test-mode="<?php echo isset($config['testMode']) && $config['testMode'] ? 'true' : 'false'; ?>"
      data-disable-attention-button="<?php echo isset($config['disableAttentionButton']) && $config['disableAttentionButton'] ? 'true' : 'false'; ?>">
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
                    <div class="question-number">Frage <?php echo $qIndex + 1; ?> von <?php echo count($shuffledQuestions); ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($question["question"]); ?></div>
                    
                    <div class="answers-container">
                        <?php if (!empty($question["answers"])): ?>
                            <?php foreach ($question["answers"] as $aIndex => $answer): ?>
                                <div class="answer-option">
                                    <?php if ($question['correctAnswerCount'] === 1): ?>
                                        <input type="radio" 
                                               name="answer_<?php echo $question['nr']; ?>" 
                                               value="<?php echo $answer['nr']; ?>" 
                                               required>
                                    <?php else: ?>
                                        <input type="checkbox" 
                                               name="answer_<?php echo $question['nr']; ?>[]" 
                                               value="<?php echo $answer['nr']; ?>">
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
                <button type="submit" class="btn-submit">Test abschließen</button>
            </div>
        </form>
    </div>
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>

    <script>
        // Debug-Informationen
        console.log('Debug Information - Test Anzeige:');
        console.log('Test Name:', '<?php echo htmlspecialchars($testName); ?>');
        console.log('Student Name:', '<?php echo htmlspecialchars($studentName); ?>');
        console.log('Questions:', <?php echo json_encode($shuffledQuestions); ?>);
    </script>
</body>
</html>