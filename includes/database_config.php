<?php
// require_once __DIR__ . '/../config/config.php'; wurde  entfernt

// Datenbankverbindungskonfiguration
class DatabaseConfig {
    private static $instance = null;
    private $connection = null;
    private $config;
    
    private function __construct() {
        // Debugging: Server-Informationen loggen
        error_log("SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'nicht gesetzt'));
        error_log("HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'nicht gesetzt'));
        
        // Prüfen, ob wir uns auf dem Produktionsserver befinden
        $isProduction = ($_SERVER['SERVER_NAME'] ?? '') === 'www.dianoia-ai.de' || 
                       ($_SERVER['HTTP_HOST'] ?? '') === 'dianoia-ai.de' ||
                       file_exists('/var/www/production_flag');
        
        // Konfiguration basierend auf der Umgebung
        if ($isProduction) {
            // Produktionsumgebung
            $this->config = [
                'db_host' => 'localhost',
                'db_user' => 'mcqadmin',
                'db_password' => 'Ib1973g!np', // Hier das richtige Passwort eintragen
                'db_name' => 'mcq_test_system'
            ];
            error_log("Produktionsumgebung erkannt");
        } else {
            // Lokale Entwicklungsumgebung
            $this->config = [
                'db_host' => 'localhost',
                'db_user' => 'root',
                'db_password' => '',
                'db_name' => 'mcq_test_system'
            ];
            error_log("Entwicklungsumgebung erkannt");
        }

        // Definiere Konstanten für globalen Zugriff, falls noch nicht definiert
        if (!defined('DB_HOST')) {
            define('DB_HOST', $this->config['db_host']);
        }
        if (!defined('DB_USER')) {
            define('DB_USER', $this->config['db_user']);
        }
        if (!defined('DB_PASS')) {
            define('DB_PASS', $this->config['db_password']);
        }
        if (!defined('DB_NAME')) {
            define('DB_NAME', $this->config['db_name']);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConfig();
        }
        return self::$instance;
    }
    
    public function createDatabase() {
        try {
            $pdo = new PDO(
                "mysql:host=" . $this->config['db_host'],
                $this->config['db_user'],
                $this->config['db_password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "CREATE DATABASE IF NOT EXISTS " . $this->config['db_name'] . 
                   " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);
            
        } catch (PDOException $e) {
            error_log("Fehler beim Erstellen der Datenbank: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            try {
                $this->writeLog("Versuche Datenbankverbindung herzustellen...");
                $this->writeLog("Server Name: " . $_SERVER['SERVER_NAME']);
                $this->writeLog("Verwende " . ($this->isProduction() ? "Produktions" : "Entwicklungs") . "-Konfiguration");
                
                $this->writeLog("Verbindungsdetails: Host=" . $this->config['db_host'] . 
                               ", DB=" . $this->config['db_name'] . 
                               ", User=" . $this->config['db_user']);
                
                $this->connection = new PDO(
                    "mysql:host=" . $this->config['db_host'] . 
                    ";dbname=" . $this->config['db_name'] . 
                    ";charset=utf8mb4",
                    $this->config['db_user'],
                    $this->config['db_password']
                );
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->writeLog("Datenbankverbindung erfolgreich hergestellt");
            } catch (PDOException $e) {
                $this->writeLog("Fehler bei der Datenbankverbindung: " . $e->getMessage());
                throw new Exception("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
            }
        }
        return $this->connection;
    }
    
    private function writeLog($message) {
        $logFile = __DIR__ . '/../logs/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] [DatabaseConfig] $message\n", FILE_APPEND);
    }

    private function isProduction() {
        return ($_SERVER['SERVER_NAME'] ?? '') === 'www.dianoia-ai.de' || 
               ($_SERVER['HTTP_HOST'] ?? '') === 'dianoia-ai.de' ||
               file_exists('/var/www/production_flag');
    }
} 