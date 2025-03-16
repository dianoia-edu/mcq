<?php
session_start();

// Überprüfe, ob der Benutzer als Lehrer angemeldet ist
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Zugriff verweigert. Bitte melden Sie sich als Lehrer an.</div>';
    exit;
}

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = dirname(__DIR__) . '/logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Überprüfe, ob eine Datei angegeben wurde
if (!isset($_GET['file']) || empty($_GET['file'])) {
    echo '<div class="alert alert-danger">Keine Datei angegeben.</div>';
    exit;
}

$filePath = $_GET['file'];
writeLog("view_test_result.php aufgerufen für Datei: " . $filePath);

// Sicherheitsüberprüfung: Stelle sicher, dass die Datei im results-Verzeichnis liegt
if (strpos($filePath, '../') !== false || !file_exists($filePath)) {
    // Versuche, die Datei im results-Verzeichnis zu finden
    $alternativePath = dirname(__DIR__) . '/results/' . basename($filePath);
    if (file_exists($alternativePath)) {
        $filePath = $alternativePath;
    } else {
        echo '<div class="alert alert-danger">Ungültiger Dateipfad oder Datei nicht gefunden.</div>';
        exit;
    }
}

// Versuche, die XML-Datei zu laden
try {
    $xml = simplexml_load_file($filePath);
    if (!$xml) {
        throw new Exception("Die Datei konnte nicht als XML geladen werden.");
    }
    
    // Extrahiere die Informationen aus der XML-Datei
    $testTitle = isset($xml->title) ? (string)$xml->title : 'Unbekannter Test';
    $studentName = isset($xml->student_name) ? (string)$xml->student_name : 'Unbekannter Schüler';
    $date = isset($xml->date) ? (string)$xml->date : 'Unbekanntes Datum';
    $achievedPoints = isset($xml->achieved_points) ? (int)$xml->achieved_points : 0;
    $maxPoints = isset($xml->max_points) ? (int)$xml->max_points : 0;
    $percentage = isset($xml->percentage) ? (float)$xml->percentage : 0;
    $grade = isset($xml->grade) ? (string)$xml->grade : 'Keine Note';
    
    // Formatiere das Datum
    $formattedDate = date('d.m.Y H:i', strtotime($date));
    
    // Extrahiere die Fragen und Antworten
    $questions = [];
    if (isset($xml->questions->question)) {
        foreach ($xml->questions->question as $question) {
            $questionData = [
                'text' => (string)$question->text,
                'points' => isset($question->points) ? (int)$question->points : 0,
                'achieved' => isset($question->achieved) ? (int)$question->achieved : 0,
                'answers' => []
            ];
            
            if (isset($question->answers->answer)) {
                foreach ($question->answers->answer as $answer) {
                    $questionData['answers'][] = [
                        'text' => (string)$answer->text,
                        'correct' => isset($answer->correct) && (int)$answer->correct === 1,
                        'selected' => isset($answer->selected) && (int)$answer->selected === 1
                    ];
                }
            }
            
            $questions[] = $questionData;
        }
    }
    
    // Ausgabe der Detailansicht
    ?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h4><?php echo htmlspecialchars($testTitle); ?></h4>
                <p><strong>Schüler:</strong> <?php echo htmlspecialchars($studentName); ?></p>
                <p><strong>Datum:</strong> <?php echo htmlspecialchars($formattedDate); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>Ergebnis</h5>
                        <p class="mb-1"><strong>Punkte:</strong> <?php echo $achievedPoints; ?> / <?php echo $maxPoints; ?></p>
                        <p class="mb-1"><strong>Prozent:</strong> <?php echo number_format($percentage, 1); ?>%</p>
                        <p class="mb-0"><strong>Note:</strong> <?php echo htmlspecialchars($grade); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="questions-container">
            <?php foreach ($questions as $index => $question): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Frage <?php echo $index + 1; ?></h5>
                        <span class="badge <?php echo $question['achieved'] == $question['points'] ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $question['achieved']; ?> / <?php echo $question['points']; ?> Punkte
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="question-text"><?php echo htmlspecialchars($question['text']); ?></p>
                        
                        <div class="answers-list">
                            <?php foreach ($question['answers'] as $answerIndex => $answer): ?>
                                <div class="answer-item d-flex align-items-start mb-2">
                                    <div class="answer-checkbox me-2">
                                        <?php if ($answer['selected']): ?>
                                            <i class="bi bi-check-square-fill <?php echo $answer['correct'] ? 'text-success' : 'text-danger'; ?>"></i>
                                        <?php else: ?>
                                            <i class="bi bi-square <?php echo $answer['correct'] ? 'text-success' : ''; ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="answer-text <?php echo ($answer['selected'] && !$answer['correct']) ? 'text-danger' : ''; ?> <?php echo (!$answer['selected'] && $answer['correct']) ? 'text-success' : ''; ?>">
                                        <?php echo htmlspecialchars($answer['text']); ?>
                                        <?php if ($answer['correct']): ?>
                                            <span class="badge bg-success ms-2">Richtig</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Fehler beim Laden der Datei: ' . htmlspecialchars($e->getMessage()) . '</div>';
    writeLog("Fehler beim Laden der Datei " . $filePath . ": " . $e->getMessage());
}
?> 