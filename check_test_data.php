<?php
require_once 'includes/database_config.php';

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    $accessCode = '2U2';

    echo "Suche nach Testdaten für Zugangscode: $accessCode\n\n";

    // 1. Hole Testinformationen
    $stmt = $db->prepare("
        SELECT * FROM tests 
        WHERE access_code = :access_code
    ");
    $stmt->execute(['access_code' => $accessCode]);
    $test = $stmt->fetch();

    if (!$test) {
        echo "Kein Test mit diesem Zugangscode gefunden.\n";
        exit;
    }

    echo "=== Testinformationen ===\n";
    echo "Test ID: {$test['test_id']}\n";
    echo "Titel: {$test['title']}\n";
    echo "Fragen: {$test['question_count']}\n";
    echo "Antworten: {$test['answer_count']}\n";
    echo "Antworttyp: {$test['answer_type']}\n";
    echo "Erstellt am: {$test['created_at']}\n\n";

    // 2. Hole Testversuche
    $stmt = $db->prepare("
        SELECT * FROM test_attempts 
        WHERE test_id = :test_id 
        ORDER BY completed_at DESC
    ");
    $stmt->execute(['test_id' => $test['test_id']]);
    $attempts = $stmt->fetchAll();

    echo "=== Testversuche ===\n";
    if (empty($attempts)) {
        echo "Keine Testversuche gefunden.\n";
    } else {
        foreach ($attempts as $attempt) {
            echo "Schüler: {$attempt['student_name']}\n";
            echo "Punkte: {$attempt['points_achieved']}/{$attempt['points_maximum']}\n";
            echo "Prozent: {$attempt['percentage']}%\n";
            echo "Note: {$attempt['grade']}\n";
            echo "Durchgeführt am: {$attempt['completed_at']}\n";
            echo "XML-Datei: {$attempt['xml_file_path']}\n";
            echo "------------------------\n";
        }
    }

    // 3. Hole Teststatistiken
    $stmt = $db->prepare("
        SELECT * FROM test_statistics 
        WHERE test_id = :test_id
    ");
    $stmt->execute(['test_id' => $test['test_id']]);
    $stats = $stmt->fetch();

    echo "\n=== Teststatistiken ===\n";
    if ($stats) {
        echo "Anzahl Versuche: {$stats['attempts_count']}\n";
        echo "Durchschnittliche Prozent: {$stats['average_percentage']}%\n";
        echo "Durchschnittliche Dauer: " . gmdate("H:i:s", $stats['average_duration']) . "\n";
        echo "Letzter Versuch: {$stats['last_attempt_at']}\n";
    } else {
        echo "Keine Statistiken verfügbar.\n";
    }

} catch (Exception $e) {
    echo "Fehler beim Abrufen der Daten: " . $e->getMessage() . "\n";
    exit(1);
} 