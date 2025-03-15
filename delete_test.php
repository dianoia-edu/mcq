<?php
// Aktiviere Error Reporting für Entwicklung
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Setze Header für JSON
header('Content-Type: application/json');

try {
    // Überprüfe, ob ein Dateiname übergeben wurde
    if (!isset($_POST['filename']) || empty($_POST['filename'])) {
        throw new Exception('Kein Dateiname angegeben');
    }
    
    $filename = $_POST['filename'];
    
    // Sicherheitsüberprüfung: Stelle sicher, dass der Dateiname keine Verzeichnistraversierung enthält
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        throw new Exception('Ungültiger Dateiname');
    }
    
    // Pfad zur Datei
    $file_path = __DIR__ . '/tests/' . $filename;
    
    // Überprüfe, ob die Datei existiert
    if (!file_exists($file_path)) {
        throw new Exception('Die Datei existiert nicht');
    }
    
    // Versuche, die Datei zu löschen
    if (!unlink($file_path)) {
        throw new Exception('Die Datei konnte nicht gelöscht werden');
    }
    
    // Erfolg melden
    echo json_encode([
        'success' => true,
        'message' => 'Test erfolgreich gelöscht'
    ]);
    
} catch (Exception $e) {
    // Fehler melden
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 