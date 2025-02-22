<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

function getClientIdentifier() {
    // Kombiniere IP-Adresse und User-Agent für eindeutige Identifizierung
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $identifier = hash('sha256', $ip . $userAgent);
    error_log("Client Identifier generiert: " . substr($identifier, 0, 8) . "...");
    return $identifier;
}

function hasCompletedTestToday($testName) {
    // Wenn Testwiederholung erlaubt ist, erlaube mehrfache Versuche
    $config = loadConfig();
    if ($config['testMode'] && $config['allowTestRepetition']) {
        error_log("Testwiederholung ist im Testmodus erlaubt");
        return false;
    }

    if (empty($testName)) {
        error_log("Warnung: Leerer Testname übergeben");
        return false;
    }

    // 1. Prüfe Session-basierte Sperre
    $testKey = date('Y-m-d') . '_' . $testName;
    if (isset($_SESSION['completed_tests']) && in_array($testKey, $_SESSION['completed_tests'])) {
        error_log("Test bereits in Session als absolviert markiert: " . $testKey);
        return true;
    }

    // 2. Prüfe dateibasierte Sperre
    $today = date('Y-m-d');
    $clientId = getClientIdentifier();
    $shortClientId = substr($clientId, 0, 8);
    $safeTestName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $testName);
    
    // Stelle sicher, dass das results-Verzeichnis existiert
    if (!is_dir('results')) {
        if (!mkdir('results', 0755, true)) {
            error_log("Fehler: Konnte results-Verzeichnis nicht erstellen");
            return false;
        }
    }
    
    // Suche nach Ergebnisdateien von heute für diesen Test und Client
    $resultPattern = "results/{$safeTestName}_*_{$shortClientId}*_{$today}*.txt";
    $resultFiles = glob($resultPattern);
    
    error_log("Suche nach Testergebnissen mit Muster: " . $resultPattern);
    error_log("Gefundene Dateien: " . count($resultFiles));
    
    if (!empty($resultFiles)) {
        error_log("Test bereits absolviert gefunden mit Client-ID: " . $shortClientId);
        // Aktualisiere auch die Session-Information
        if (!isset($_SESSION['completed_tests'])) {
            $_SESSION['completed_tests'] = [];
        }
        $_SESSION['completed_tests'][] = $testKey;
        return true;
    }
    
    return false;
}

function markTestAsCompleted($testName) {
    if (empty($testName)) {
        error_log("Warnung: Leerer Testname beim Markieren als abgeschlossen");
        return;
    }

    $testKey = date('Y-m-d') . '_' . $testName;
    
    if (!isset($_SESSION['completed_tests'])) {
        $_SESSION['completed_tests'] = [];
    }
    
    if (!in_array($testKey, $_SESSION['completed_tests'])) {
        $_SESSION['completed_tests'][] = $testKey;
        error_log("Test als abgeschlossen markiert: " . $testKey);
    }
} 