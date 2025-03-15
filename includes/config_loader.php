<?php
// require_once __DIR__ . '/../config/config.php'; wurde entfernt

function loadConfig() {
    static $config = null;
    
    if ($config === null) {
        try {
            // Da config.php nicht mehr existiert, erstellen wir eine einfache Standardkonfiguration
            $config = [
                // Hier können Standardwerte definiert werden
                'environment' => 'production'
            ];
            
            // Füge zusätzliche Konfigurationen hinzu
            $configFile = __DIR__ . '/../config/api_config.json';
            if (file_exists($configFile)) {
                $apiConfig = json_decode(file_get_contents($configFile), true);
                if (is_array($apiConfig)) {
                    $config = array_merge($config, $apiConfig);
                }
            }
        } catch (Exception $e) {
            error_log("Fehler beim Laden der Konfiguration: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $config;
} 