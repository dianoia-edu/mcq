<?php
require_once 'includes/database_config.php';

// Funktion zum Schreiben von Log-Nachrichten
function log_message($message) {
    echo $message . "<br>";
}

try {
    // Verbindung zur Datenbank herstellen
    $db = DatabaseConfig::getInstance()->getConnection();
    log_message("Datenbankverbindung hergestellt");

    // Prüfe, wie viele Einträge für den Test ZGZ von dfds heute existieren
    $today = date('Y-m-d');
    $testCode = "ZGZ";
    $studentName = "dfds";

    $stmt = $db->prepare("
        SELECT 
            ta.attempt_id, 
            t.access_code, 
            ta.student_name, 
            ta.completed_at, 
            ta.xml_file_path,
            ta.points_achieved,
            ta.points_maximum,
            ta.percentage,
            ta.grade
        FROM 
            test_attempts ta 
        JOIN 
            tests t ON ta.test_id = t.test_id 
        WHERE 
            t.access_code = ? 
            AND ta.student_name = ? 
            AND DATE(ta.completed_at) = ?
        ORDER BY 
            ta.completed_at DESC
    ");

    $stmt->execute([$testCode, $studentName, $today]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    log_message("Anzahl der gefundenen Einträge: " . count($results));

    // Zeige die Ergebnisse an
    if (count($results) > 0) {
        echo "<h2>Testversuche für Test $testCode von $studentName am $today</h2>";
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Attempt ID</th>";
        echo "<th>Test Code</th>";
        echo "<th>Schüler</th>";
        echo "<th>Datum/Zeit</th>";
        echo "<th>XML-Datei</th>";
        echo "<th>Punkte</th>";
        echo "<th>Note</th>";
        echo "</tr>";

        foreach ($results as $result) {
            echo "<tr>";
            echo "<td>" . $result['attempt_id'] . "</td>";
            echo "<td>" . $result['access_code'] . "</td>";
            echo "<td>" . $result['student_name'] . "</td>";
            echo "<td>" . $result['completed_at'] . "</td>";
            echo "<td>" . $result['xml_file_path'] . "</td>";
            echo "<td>" . $result['points_achieved'] . "/" . $result['points_maximum'] . " (" . $result['percentage'] . "%)</td>";
            echo "<td>" . $result['grade'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        log_message("Keine Einträge gefunden.");
    }

    // Prüfe auch den Inhalt der XML-Datei
    $xmlPath = "results/ZGZ_2025-03-23/ZGZ_dfds_2025-03-23_15-18-28.xml";
    if (file_exists($xmlPath)) {
        $xml = simplexml_load_file($xmlPath);
        log_message("<h3>Inhalt der XML-Datei: $xmlPath</h3>");
        log_message("Zugangscode: " . $xml->access_code);
        log_message("Schülername: " . $xml->schuelername);
        log_message("Abgabezeit: " . $xml->abgabezeit);
    } else {
        log_message("XML-Datei nicht gefunden: $xmlPath");
    }

    // Prüfe die Anzeige in der Ergebnisliste (SQL-Query aus get_test_results.php)
    $displayStmt = $db->prepare("
        SELECT 
            t.title as testTitle,
            ta.student_name as studentName,
            ta.completed_at as date,
            t.access_code,
            ta.xml_file_path as fileName,
            ta.points_achieved,
            ta.points_maximum,
            ta.percentage,
            ta.grade
        FROM test_attempts ta
        JOIN tests t ON ta.test_id = t.test_id
        WHERE t.access_code = ?
        ORDER BY ta.completed_at DESC
    ");

    $displayStmt->execute([$testCode]);
    $displayResults = $displayStmt->fetchAll(PDO::FETCH_ASSOC);

    log_message("<h3>Anzeige in der Ergebnisliste</h3>");
    log_message("Anzahl der Anzeige-Einträge: " . count($displayResults));

    if (count($displayResults) > 0) {
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Test Titel</th>";
        echo "<th>Schüler</th>";
        echo "<th>Datum/Zeit</th>";
        echo "<th>Test Code</th>";
        echo "<th>XML-Datei</th>";
        echo "<th>Punkte</th>";
        echo "</tr>";

        foreach ($displayResults as $display) {
            echo "<tr>";
            echo "<td>" . $display['testTitle'] . "</td>";
            echo "<td>" . $display['studentName'] . "</td>";
            echo "<td>" . $display['date'] . "</td>";
            echo "<td>" . $display['access_code'] . "</td>";
            echo "<td>" . $display['fileName'] . "</td>";
            echo "<td>" . $display['points_achieved'] . "/" . $display['points_maximum'] . " (" . $display['percentage'] . "%)</td>";
            echo "</tr>";
        }

        echo "</table>";
    }

} catch (Exception $e) {
    log_message("Fehler: " . $e->getMessage());
}
?> 