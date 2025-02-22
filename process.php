<?php
ob_start();
session_start();
require_once 'check_test_attempts.php';
require_once 'config.php';

if (!isset($_SESSION["original_questions"]) || !isset($_SESSION["testName"]) || !isset($_SESSION["studentName"])) {
    header("Location: index.php");
    exit;
}

// Überprüfe erneut, ob der Test bereits absolviert wurde
if (hasCompletedTestToday($_SESSION["testName"])) {
    $_SESSION["error"] = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
    header("Location: index.php");
    exit;
}

$questions = $_SESSION["original_questions"]; // Verwende die originalen Fragen für die Auswertung
$testName = $_SESSION["testName"];
$studentName = $_SESSION["studentName"];
$submittedAnswers = $_POST["answers"] ?? [];
$isAborted = isset($_POST['aborted']) && $_POST['aborted'] === 'true';
$missedClicks = isset($_POST['missedClicks']) ? intval($_POST['missedClicks']) : 0;

// Debug-Ausgabe
error_log("Empfangene POST-Daten: " . print_r($_POST, true));
error_log("Test abgebrochen: " . ($isAborted ? 'Ja' : 'Nein'));
error_log("Verpasste Klicks: " . $missedClicks);

// Ergebnis berechnen
$totalPoints = 0;
$maxPoints = 0;
$answeredQuestions = 0;
$results = [];

foreach ($questions as $qIndex => $question) {
    $questionResult = [
        "question" => $question["question"],
        "selectedAnswers" => [],
        "correctAnswers" => [],
        "points" => 0,
        "answered" => false
    ];
    
    // Richtige Antworten ermitteln und maxPoints berechnen
    $correctAnswerIndices = [];
    foreach ($question["answers"] as $aIndex => $answer) {
        if ($answer["isCorrect"]) {
            $correctAnswerIndices[] = $aIndex;
            $questionResult["correctAnswers"][] = $answer["text"];
        }
    }
    $maxPoints += count($correctAnswerIndices);
    
    // Ausgewählte Antworten für diese Frage ermitteln
    if (isset($submittedAnswers[$qIndex])) {
        $questionResult["answered"] = true;
        $answeredQuestions++;
        
        $selectedAnswerIndices = is_array($submittedAnswers[$qIndex]) 
            ? array_map('intval', $submittedAnswers[$qIndex])
            : [intval($submittedAnswers[$qIndex])];
        
        // Ausgewählte Antworten speichern
        foreach ($selectedAnswerIndices as $aIndex) {
            if (isset($question["answers"][$aIndex])) {
                $questionResult["selectedAnswers"][] = $question["answers"][$aIndex]["text"];
            }
        }
        
        // Punkte berechnen
        $points = 0;
        foreach ($selectedAnswerIndices as $aIndex) {
            if (in_array($aIndex, $correctAnswerIndices)) {
                $points++;
            } else {
                $points--;
            }
        }
        foreach ($correctAnswerIndices as $aIndex) {
            if (!in_array($aIndex, $selectedAnswerIndices)) {
                $points--;
            }
        }
        
        $points = max(0, $points);
        $questionResult["points"] = $points;
        $totalPoints += $points;
    }
    
    $results[] = $questionResult;
}

// Prozentsatz und Note berechnen
$percentage = $maxPoints > 0 ? ($totalPoints / $maxPoints) * 100 : 0;

if ($percentage > 90) {
    $grade = 15;
} elseif ($percentage > 80) {
    $grade = 12;
} elseif ($percentage > 70) {
    $grade = 9;
} elseif ($percentage > 60) {
    $grade = 6;
} elseif ($percentage > 50) {
    $grade = 3;
} else {
    $grade = 0;
}

// Ergebnisse in JSON umwandeln und speichern
$resultData = [
    "testName" => $testName,
    "studentName" => $studentName,
    "totalPoints" => $totalPoints,
    "maxPoints" => $maxPoints,
    "percentage" => $percentage,
    "grade" => $grade,
    "submissionDate" => date("Y-m-d H:i:s"),
    "clientId" => getClientIdentifier(),
    "isAborted" => $isAborted,
    "missedClicks" => $missedClicks,
    "answeredQuestions" => $answeredQuestions,
    "totalQuestions" => count($questions),
    "results" => $results
];

$jsonResult = json_encode($resultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Ergebnisse speichern
$resultDir = "results";
if (!is_dir($resultDir)) {
    mkdir($resultDir, 0755, true);
}

$safeTestName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $testName);
$safeStudentName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentName);
$clientId = substr(getClientIdentifier(), 0, 8);
$timestamp = date('Y-m-d_H-i-s');
$abortedFlag = $isAborted ? '_aborted' : '';

// Erstelle einen eindeutigen Dateinamen mit allen relevanten Informationen
$resultFile = "{$resultDir}/{$safeTestName}_{$safeStudentName}_{$clientId}{$abortedFlag}_{$timestamp}.txt";
file_put_contents($resultFile, $jsonResult);

// Markiere den Test als abgeschlossen
markTestAsCompleted($_SESSION["testName"]);

// Nach erfolgreicher Verarbeitung und Speicherung des Tests
if (!isset($_SESSION['completed_tests'])) {
    $_SESSION['completed_tests'] = [];
}

$testKey = date('Y-m-d') . '_' . $_SESSION["testName"];
$_SESSION['completed_tests'][] = $testKey;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Testergebnis - <?php echo $testName; ?></title>
    <style>
        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #ffeeba;
            border-radius: 0.5rem;
            text-align: center;
        }
        
        .result-summary {
            margin-top: 1.5rem;
        }

        .progress-info {
            margin-top: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
    <link rel="stylesheet" href="./styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php echo getTestModeWarning(); ?>
    <div class="container">
        <h1>Testergebnis</h1>
        <?php if ($isAborted): ?>
            <div class="warning-message">
                Der Test wurde aufgrund von <?php echo $missedClicks; ?> verpassten Aufmerksamkeitsklicks vorzeitig beendet. 
                Die bis dahin erreichten Ergebnisse wurden gespeichert.
                <div class="progress-info">
                    Bearbeitete Fragen: <?php echo $answeredQuestions; ?> von <?php echo count($questions); ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="result-summary">
            <div class="result-text">
                Rohpunkte: <?php echo $totalPoints; ?> von <?php echo $maxPoints; ?> Punkten 
                (<?php echo number_format($percentage, 1); ?>%)
            </div>
            <div class="grade">
                <strong>Note: <?php echo $grade; ?> Punkte</strong>
            </div>
        </div>
        <div class="navigation">
            <a href="index.php" class="btn primary-btn">Zurück zur Startseite</a>
        </div>
    </div>
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>
</body>
</html>