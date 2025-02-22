<?php
session_start();

// Überprüfen, ob der Benutzer ein Lehrer ist
if (!isset($_SESSION["teacher"]) || $_SESSION["teacher"] !== true) {
    header("Location: index.php");
    exit;
}

// Überprüfen, ob eine Datei ausgewählt wurde
$testFile = $_GET["file"] ?? null;
if (!$testFile || !file_exists($testFile)) {
    header("Location: teacher_dashboard.php");
    exit;
}

// Testdaten laden
$lines = file($testFile, FILE_IGNORE_NEW_LINES);
$accessCode = $lines[0];
$testName = $lines[1];

// Zugangscode und Testname entfernen
array_shift($lines);
array_shift($lines);

// Fragen und Antworten parsen
$questions = [];
$currentQuestion = null;
$answers = [];

foreach ($lines as $line) {
    if (trim($line) === "") continue;
    
    if (substr($line, 0, 1) !== " " && substr($line, 0, 1) !== "*") {
        // Neue Frage gefunden
        if ($currentQuestion !== null) {
            $questions[] = [
                "question" => $currentQuestion,
                "answers" => $answers
            ];
        }
        $currentQuestion = $line;
        $answers = [];
    } else {
        // Antwort gefunden
        $isCorrect = false;
        if (strpos($line, "[richtig]") !== false) {
            $isCorrect = true;
            $line = str_replace("[richtig]", "", $line);
        }
        $line = ltrim($line, "* ");
        $answers[] = [
            "text" => $line,
            "isCorrect" => $isCorrect
        ];
    }
}

// Letzte Frage hinzufügen
if ($currentQuestion !== null) {
    $questions[] = [
        "question" => $currentQuestion,
        "answers" => $answers
    ];
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testvorschau: <?php echo $testName; ?> - MCQ Test System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Testvorschau: <?php echo $testName; ?></h1>
        <div class="test-info">
            <p><strong>Zugangscode:</strong> <?php echo $accessCode; ?></p>
            <p><strong>Datei:</strong> <?php echo basename($testFile); ?></p>
        </div>
        
        <div class="preview-container">
            <h2>Fragen und Antworten</h2>
            <?php foreach ($questions as $qIndex => $question): ?>
                <div class="question-preview">
                    <h3>Frage <?php echo $qIndex + 1; ?>: <?php echo $question["question"]; ?></h3>
                    <div class="answers-preview">
                        <?php foreach ($question["answers"] as $aIndex => $answer): ?>
                            <div class="answer-option <?php echo $answer["isCorrect"] ? 'correct-answer' : ''; ?>">
                                <?php echo $answer["text"]; ?>
                                <?php if ($answer["isCorrect"]): ?>
                                    <span class="correct-indicator">[richtig]</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="navigation">
            <a href="teacher_dashboard.php" class="btn secondary-btn">Zurück zum Dashboard</a>
        </div>
    </div>
</body>
</html>
