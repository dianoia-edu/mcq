<?php
// require_once __DIR__ . '/../config/config.php'; wurde entfernt

function loadConfig() {
    static $config = null;
    
    if ($config === null) {
        try {
            // Basiseinstellungen
            $config = [
                'environment' => 'development'
            ];
            
            // Prüfen, ob wir uns auf dem Produktionsserver befinden
            if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'mcq.medizin.uni-tuebingen.de') {
                $config['environment'] = 'production';
            }
            
            // App-Konfiguration laden
            $appConfigFile = __DIR__ . '/config/app_config.php';
            if (file_exists($appConfigFile)) {
                $appConfig = require $appConfigFile;
                if (is_array($appConfig)) {
                    $config = array_merge($config, $appConfig);
                }
            }
            
            // API-Konfiguration laden
            $apiConfigFile = __DIR__ . '/config/api_config.json';
            if (file_exists($apiConfigFile)) {
                $apiConfig = json_decode(file_get_contents($apiConfigFile), true);
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