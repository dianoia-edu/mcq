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
                error_log("Versuche Verbindung herzustellen mit Host: " . $this->config['db_host'] . 
                         ", Benutzer: " . $this->config['db_user'] . 
                         ", Datenbank: " . $this->config['db_name']);
                
                $this->connection = new PDO(
                    "mysql:host=" . $this->config['db_host'] . 
                    ";dbname=" . $this->config['db_name'] . 
                    ";charset=utf8mb4",
                    $this->config['db_user'],
                    $this->config['db_password']
                );
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                error_log("Datenbankverbindung erfolgreich hergestellt");
            } catch (PDOException $e) {
                error_log("Detaillierter Verbindungsfehler: " . $e->getMessage());
                throw $e;
            }
        }
        return $this->connection;
    }
} 