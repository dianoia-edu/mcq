<?php
// Funktion zum Speichern der Konfiguration
function saveConfig($config) {
    $configFile = 'config.json';
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
}

// Funktion zum Laden der Konfiguration
function loadConfig() {
    $configFile = 'config.json';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
    } else {
        // Standardkonfiguration
        $config = [
            'testMode' => false,
            'disableAttentionButton' => false,
            'allowTestRepetition' => false
        ];
        saveConfig($config);
    }
    return $config;
}

// Funktion zum Aktualisieren einzelner Konfigurationsoptionen
function updateConfig($key, $value) {
    $config = loadConfig();
    $config[$key] = $value;
    saveConfig($config);
    return $config;
}

// Funktion zum Anzeigen der Testmodus-Warnung
function getTestModeWarning() {
    $config = loadConfig();
    if ($config['testMode']) {
        $warning = 'ACHTUNG: System befindet sich im Testmodus!';
        if ($config['disableAttentionButton']) {
            $warning .= ' | Aufmerksamkeitsbutton deaktiviert';
        }
        if ($config['allowTestRepetition']) {
            $warning .= ' | Testwiederholung erlaubt';
        }
        return '<div class="test-mode-warning" style="background-color: #ff4444; color: white; padding: 1rem; text-align: center; position: fixed; top: 0; left: 0; right: 0; z-index: 9999; font-weight: bold;">' . $warning . '</div>';
    }
    return '';
} 