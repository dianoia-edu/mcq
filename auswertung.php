<?php
session_start();

// Überprüfen, ob der Benutzer ein Lehrer ist
if (!isset($_SESSION["teacher"]) || $_SESSION["teacher"] !== true) {
    header("Location: index.php");
    exit;
}

// Überprüfen, ob ein Test ausgewählt wurde
$selectedTest = $_GET["test"] ?? null;
if (!$selectedTest) {
    header("Location: teacher_dashboard.php");
    exit;
}

// Resultate für den ausgewählten Test abrufen
$resultFiles = glob("results/{$selectedTest}_*.txt");
$results = [];

foreach ($resultFiles as $resultFile) {
    $resultData = json_decode(file_get_contents($resultFile), true);
    if ($resultData) {
        $results[] = $resultData;
    }
}

// Sortieren nach Nachname
usort($results, function($a, $b) {
    $lastNameA = explode(' ', $a["studentName"]);
    $lastNameA = end($lastNameA);
    
    $lastNameB = explode(' ', $b["studentName"]);
    $lastNameB = end($lastNameB);
    
    return strcmp($lastNameA, $lastNameB);
});

// Statistiken berechnen
$statistics = [
    "totalStudents" => count($results),
    "averagePercentage" => 0,
    "averageGrade" => 0,
    "highestGrade" => 0,
    "lowestGrade" => 15,
    "distribution" => [
        "0" => 0,
        "3" => 0,
        "6" => 0,
        "9" => 0,
        "12" => 0,
        "15" => 0
    ]
];

if (!empty($results)) {
    $totalPercentage = 0;
    $totalGrade = 0;
    
    foreach ($results as $result) {
        $totalPercentage += $result["percentage"];
        $totalGrade += $result["grade"];
        
        $statistics["highestGrade"] = max($statistics["highestGrade"], $result["grade"]);
        $statistics["lowestGrade"] = min($statistics["lowestGrade"], $result["grade"]);
        
        // Notenverteilung aktualisieren
        $statistics["distribution"]["{$result["grade"]}"]++;
    }
    
    $statistics["averagePercentage"] = $totalPercentage / count($results);
    $statistics["averageGrade"] = $totalGrade / count($results);
}

// Fragen analysieren, um zu sehen, welche am schwierigsten waren
$questionStats = [];
if (!empty($results) && !empty($results[0]["results"])) {
    foreach ($results[0]["results"] as $qIndex => $questionResult) {
        $questionStats[$qIndex] = [
            "question" => $questionResult["question"],
            "correctAnswers" => $questionResult["correctAnswers"],
            "totalCorrect" => 0,
            "totalIncorrect" => 0
        ];
    }
    
    foreach ($results as $result) {
        foreach ($result["results"] as $qIndex => $questionResult) {
            if ($questionResult["points"] > 0) {
                $questionStats[$qIndex]["totalCorrect"]++;
            } else {
                $questionStats[$qIndex]["totalIncorrect"]++;
            }
        }
    }
}

// Nach Schwierigkeitsgrad sortieren (schwierigste zuerst)
uasort($questionStats, function($a, $b) {
    $correctRateA = $a["totalCorrect"] / ($a["totalCorrect"] + $a["totalIncorrect"]);
    $correctRateB = $b["totalCorrect"] / ($b["totalCorrect"] + $b["totalIncorrect"]);
    return $correctRateA <=> $correctRateB;
});
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auswertung: <?php echo $selectedTest; ?> - MCQ Test System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Auswertung: <?php echo $selectedTest; ?></h1>
        
        <?php if (empty($results)): ?>
            <div class="message error">
                Keine Ergebnisse für diesen Test gefunden.
            </div>
        <?php else: ?>
            <div class="dashboard-section">
                <h2>Zusammenfassung</h2>
                <div class="stats-summary">
                    <div class="stats-item">
                        <strong>Anzahl Schüler:</strong> <?php echo