<?php
// Datenbankverbindungskonfiguration
class DatabaseConfig {
    private static $instance = null;
    private $connection = null;
    
    // Standardwerte für lokale Entwicklung
    private $config = [
        'host' => 'localhost',
        'dbname' => 'mcq_test_system',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
    
    private function __construct() {
        // Überschreibe Standardwerte mit Umgebungsvariablen, falls vorhanden
        $this->config['host'] = getenv('DB_HOST') ?: $this->config['host'];
        $this->config['dbname'] = getenv('DB_NAME') ?: $this->config['dbname'];
        $this->config['username'] = getenv('DB_USER') ?: $this->config['username'];
        $this->config['password'] = getenv('DB_PASS') ?: $this->config['password'];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConfig();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
                $this->connection = new PDO($dsn, 
                    $this->config['username'], 
                    $this->config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("Datenbankverbindungsfehler: " . $e->getMessage());
                throw new Exception("Datenbankverbindung konnte nicht hergestellt werden.");
            }
        }
        return $this->connection;
    }
    
    public function createDatabase() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->config['host']};charset={$this->config['charset']}",
                $this->config['username'],
                $this->config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Erstelle Datenbank mit korrekter Zeichenkodierung
            $sql = "CREATE DATABASE IF NOT EXISTS `{$this->config['dbname']}` 
                   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);
            
            return true;
        } catch (PDOException $e) {
            error_log("Fehler beim Erstellen der Datenbank: " . $e->getMessage());
            throw new Exception("Datenbank konnte nicht erstellt werden.");
        }
    }
} 