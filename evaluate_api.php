<?php
require_once 'auswertung.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testCode = $_POST['test_code'] ?? '';
    $studentName = $_POST['student_name'] ?? '';
    $xmlFile = $_POST['xml_file'] ?? '';

    if ($testCode && $studentName && $xmlFile) {
        $results = evaluateTest($xmlFile);
        if ($results !== false) {
            updateTestList($testCode, $studentName, $results);
            echo json_encode(['success' => true, 'results' => $results]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Auswerten des Tests']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Ungültige Anfragemethode']);
} 