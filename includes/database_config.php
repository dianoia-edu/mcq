<?php
require_once __DIR__ . '/../config/config.php';

// Datenbankverbindungskonfiguration
class DatabaseConfig {
    private static $instance = null;
    private $connection = null;
    private $config;
    
    private function __construct() {
        $this->config = Config::get(Config::getEnvironment());
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
                $this->connection = new PDO(
                    "mysql:host=" . $this->config['db_host'] . 
                    ";dbname=" . $this->config['db_name'] . 
                    ";charset=utf8mb4",
                    $this->config['db_user'],
                    $this->config['db_password']
                );
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Verbindungsfehler: " . $e->getMessage());
                throw $e;
            }
        }
        return $this->connection;
    }
} 