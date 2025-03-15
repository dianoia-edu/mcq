<?php
header('Content-Type: application/json');

try {
    $testId = $_POST['testId'] ?? '';
    if (empty($testId)) {
        throw new Exception('Keine Test-ID angegeben');
    }
    
    $tempFile = "tests/temp_" . $testId . ".txt";
    $finalFile = "tests/" . $testId . ".txt";
    
    if (!file_exists($tempFile)) {
        throw new Exception('Temporäre Testdatei nicht gefunden');
    }
    
    // Test von temp in final verschieben
    if (!rename($tempFile, $finalFile)) {
        throw new Exception('Fehler beim Speichern des Tests');
    }
    
    echo json_encode([
        'success' => true,
        'testId' => $testId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 