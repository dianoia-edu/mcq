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

// Protokolliere Pfad zur Fehlerdiagnose
error_log("Lese Konfigurationsdatei: " . $configFile);

// Standardkonfiguration
$defaultConfig = [
    'schoolName' => '',
    'defaultTimeLimit' => 45,
    'resultStorage' => 'results',
    'disableAttentionButton' => false,
    'disableDailyTestLimit' => false
];

// Lade die aktuelle Konfiguration
$config = $defaultConfig;

if (file_exists($configFile)) {
    $jsonContent = file_get_contents($configFile);
    if ($jsonContent !== false) {
        $loadedConfig = json_decode($jsonContent, true);
        if (is_array($loadedConfig)) {
            // Merge mit Standardkonfiguration, damit keine Felder fehlen
            $config = array_merge($defaultConfig, $loadedConfig);
        } else {
            error_log("Konfigurationsdatei enthält ungültiges JSON: " . $jsonContent);
        }
    } else {
        error_log("Konfigurationsdatei konnte nicht gelesen werden: " . $configFile);
    }
} else {
    error_log("Konfigurationsdatei existiert nicht: " . $configFile);
}

// Sende die Konfiguration als JSON-Antwort
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'config' => $config
]); 