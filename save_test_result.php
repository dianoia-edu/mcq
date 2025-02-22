<?php
session_start();
header('Content-Type: application/json');

// Aktiviere Fehlerprotokollierung
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Lese die JSON-Daten aus dem Request-Body
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if ($data === null) {
        throw new Exception('Ungültige JSON-Daten empfangen');
    }

    // Überprüfe erforderliche Felder
    $requiredFields = ['testName', 'studentName'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Erforderliches Feld fehlt: {$field}");
        }
    }

    // Erstelle den Dateinamen
    $testName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['testName']);
    $studentName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['studentName']);
    $timestamp = date('Y-m-d_H-i-s');
    
    // Stelle sicher, dass das results-Verzeichnis existiert
    if (!is_dir('results')) {
        if (!mkdir('results', 0777, true)) {
            throw new Exception('Konnte results-Verzeichnis nicht erstellen');
        }
    }

    // Füge Standardwerte für Testabbruch hinzu
    $data['testAborted'] = $data['testAborted'] ?? false;
    $data['missedClicks'] = $data['missedClicks'] ?? 0;
    
    // Erstelle den vollständigen Dateipfad
    $resultFile = "results/{$testName}_{$studentName}_{$timestamp}.txt";
    
    // Speichere die Daten
    if (file_put_contents($resultFile, json_encode($data)) === false) {
        throw new Exception('Fehler beim Speichern der Datei');
    }

    // Markiere den Test als absolviert in der Session
    if (!isset($_SESSION['completed_tests'])) {
        $_SESSION['completed_tests'] = [];
    }
    $testKey = date('Y-m-d') . '_' . $data['testName'];
    $_SESSION['completed_tests'][] = $testKey;

    // Erfolgreiche Antwort
    echo json_encode([
        'success' => true,
        'message' => 'Ergebnis erfolgreich gespeichert',
        'file' => $resultFile
    ]);

} catch (Exception $e) {
    // Fehlerantwort
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getTraceAsString()
    ]);
}
?> 