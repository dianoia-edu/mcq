<?php
require_once 'includes/database/TestDatabase.php';
require_once 'includes/config/database_config.php';

$db = new TestDatabase();
$dbConfig = DatabaseConfig::getInstance();
$conn = $dbConfig->getConnection();

// Alle Tests anzeigen
echo "Alle Tests in der Datenbank:\n";
$sql = "SELECT t.*, ts.attempts_count, ts.average_percentage, ts.average_duration 
        FROM tests t 
        LEFT JOIN test_statistics ts ON t.test_id = ts.test_id";
$stmt = $conn->query($sql);
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($tests)) {
    echo "Keine Tests in der Datenbank gefunden!\n";
} else {
    foreach ($tests as $test) {
        echo "\nZugangscode: {$test['access_code']}\n";
        echo "Titel: {$test['title']}\n";
        echo "Test-ID: {$test['test_id']}\n";
        echo "Anzahl Versuche: " . ($test['attempts_count'] ?? '0') . "\n";
        echo "Durchschnittliche Punktzahl: " . ($test['average_percentage'] ?? '0') . "%\n";
        echo "Durchschnittliche Dauer: " . ($test['average_duration'] ? round($test['average_duration']/60, 2) : '0') . " Minuten\n";
        echo "----------------------------------------\n";
    }
}

$testCode = '2U2';
echo "Details für Test mit Code '$testCode':\n";
$test = $db->getTestByAccessCode($testCode);

if ($test) {
    echo "\nAllgemeine Informationen:\n";
    echo "Test-ID: {$test['test_id']}\n";
    echo "Titel: {$test['title']}\n";
    echo "Fragen: {$test['question_count']}\n";
    echo "Antworten pro Frage: {$test['answer_count']}\n";
    echo "Antworttyp: {$test['answer_type']}\n";
    echo "Erstellt am: {$test['created_at']}\n";
    
    echo "\nTeststatistiken:\n";
    $sql = "SELECT * FROM test_statistics WHERE test_id = :test_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':test_id' => $test['test_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo "Anzahl Versuche: {$stats['attempts_count']}\n";
        echo "Durchschnittliche Punktzahl: {$stats['average_percentage']}%\n";
        echo "Durchschnittliche Dauer: " . ($stats['average_duration'] ? round($stats['average_duration']/60, 2) : '0') . " Minuten\n";
        echo "Letzter Versuch: {$stats['last_attempt_at']}\n";
    }
    
    echo "\nAlle Testversuche:\n";
    $sql = "SELECT * FROM test_attempts WHERE test_id = :test_id ORDER BY completed_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':test_id' => $test['test_id']]);
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($attempts)) {
        echo "Keine Testversuche gefunden.\n";
    } else {
        foreach ($attempts as $attempt) {
            echo "\nSchüler: {$attempt['student_name']}\n";
            echo "Punkte: {$attempt['points_achieved']}/{$attempt['points_maximum']} ({$attempt['percentage']}%)\n";
            echo "Note: {$attempt['grade']}\n";
            echo "Begonnen: {$attempt['started_at']}\n";
            echo "Beendet: {$attempt['completed_at']}\n";
            echo "XML-Datei: {$attempt['xml_file_path']}\n";
            echo "----------------------------------------\n";
        }
    }
} else {
    echo "Test nicht gefunden!\n";
} 