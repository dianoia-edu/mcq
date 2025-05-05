<?php
// Starte Session
session_start();

// Prüfe, ob der Benutzer als Lehrer eingeloggt ist
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit();
}

// Konfigurationsdatei-Pfad
$configFile = dirname(dirname(__DIR__)) . '/config/app_config.json';
$configDir = dirname($configFile);

// Protokolliere Pfade zur Fehlerdiagnose
error_log("Config Verzeichnis: " . $configDir);
error_log("Config Datei: " . $configFile);

// Stelle sicher, dass das Konfigurationsverzeichnis existiert
if (!is_dir($configDir)) {
    if (!mkdir($configDir, 0777, true)) {
        $mkdirError = error_get_last();
        error_log("Fehler beim Erstellen des Config-Verzeichnisses: " . print_r($mkdirError, true));
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'error' => 'Konfigurationsverzeichnis konnte nicht erstellt werden: ' . $mkdirError['message']]);
        exit();
    }
    // Setze Verzeichnisberechtigungen erneut explizit
    chmod($configDir, 0777);
}

// Überprüfe Schreibberechtigung für Verzeichnis
if (!is_writable($configDir)) {
    error_log("Config-Verzeichnis ist nicht beschreibbar: " . $configDir);
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Konfigurationsverzeichnis ist nicht beschreibbar']);
    exit();
}

// Hole und validiere die eingereichten Daten
$requestData = json_decode(file_get_contents('php://input'), true);

if (!$requestData || !is_array($requestData)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'Ungültige oder fehlende Daten']);
    exit();
}

// Standardkonfiguration
$defaultConfig = [
    'schoolName' => '',
    'defaultTimeLimit' => 45,
    'resultStorage' => 'results',
    'disableAttentionButton' => false,
    'disableDailyTestLimit' => false
];

// Validiere und bereinige die Daten
$config = [
    'schoolName' => isset($requestData['schoolName']) ? trim($requestData['schoolName']) : $defaultConfig['schoolName'],
    'defaultTimeLimit' => isset($requestData['defaultTimeLimit']) ? intval($requestData['defaultTimeLimit']) : $defaultConfig['defaultTimeLimit'],
    'resultStorage' => isset($requestData['resultStorage']) ? trim($requestData['resultStorage']) : $defaultConfig['resultStorage'],
    'disableAttentionButton' => isset($requestData['disableAttentionButton']) ? (bool)$requestData['disableAttentionButton'] : $defaultConfig['disableAttentionButton'],
    'disableDailyTestLimit' => isset($requestData['disableDailyTestLimit']) ? (bool)$requestData['disableDailyTestLimit'] : $defaultConfig['disableDailyTestLimit']
];

// Speichere die Konfiguration
$jsonConfig = json_encode($config, JSON_PRETTY_PRINT);
$result = file_put_contents($configFile, $jsonConfig);

if ($result === false) {
    $fileError = error_get_last();
    error_log("Fehler beim Speichern der Konfigurationsdatei: " . print_r($fileError, true));
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Konfiguration konnte nicht gespeichert werden: ' . $fileError['message']]);
    exit();
}

// Setze Dateiberechtigungen
chmod($configFile, 0666);

// Sende Erfolgsantwort
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Konfiguration wurde erfolgreich gespeichert'
]); 