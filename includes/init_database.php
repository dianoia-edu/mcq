<?php
require_once __DIR__ . '/database_config.php';

class DatabaseInitializer {
    private $db;
    
    public function __construct() {
        $dbConfig = DatabaseConfig::getInstance();
        // Erstelle zuerst die Datenbank
        $dbConfig->createDatabase();
        // Hole dann die Verbindung
        $this->db = $dbConfig->getConnection();
    }
    
    public function initializeTables() {
        $this->createTestsTable();
        $this->createTestAttemptsTable();
        $this->createTestStatisticsTable();
        $this->createDailyAttemptsTable();
    }
    
    private function createTestsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS tests (
            test_id VARCHAR(50) PRIMARY KEY,
            access_code VARCHAR(10) NOT NULL,
            title VARCHAR(255) NOT NULL,
            question_count INT NOT NULL,
            answer_count INT NOT NULL,
            answer_type ENUM('single', 'multiple', 'mixed') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_access_code (access_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sql);
    }
    
    private function createTestAttemptsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS test_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            test_id VARCHAR(50),
            student_name VARCHAR(100) NOT NULL,
            xml_file_path VARCHAR(255) NOT NULL,
            points_achieved INT NOT NULL,
            points_maximum INT NOT NULL,
            percentage DECIMAL(5,2) NOT NULL,
            grade VARCHAR(10) NOT NULL,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (test_id) REFERENCES tests(test_id),
            INDEX idx_test_student (test_id, student_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sql);
    }
    
    private function createTestStatisticsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS test_statistics (
            test_id VARCHAR(50) PRIMARY KEY,
            attempts_count INT DEFAULT 0,
            average_percentage DECIMAL(5,2),
            average_duration INT,
            last_attempt_at TIMESTAMP NULL,
            FOREIGN KEY (test_id) REFERENCES tests(test_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sql);
    }
    
    private function createDailyAttemptsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS daily_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_id VARCHAR(50),
            student_identifier VARCHAR(64),
            attempt_date DATE,
            FOREIGN KEY (test_id) REFERENCES tests(test_id),
            UNIQUE KEY unique_attempt (test_id, student_identifier, attempt_date),
            INDEX idx_attempt_date (attempt_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sql);
    }
}

// Führe die Initialisierung durch, wenn die Datei direkt aufgerufen wird
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    try {
        $initializer = new DatabaseInitializer();
        $initializer->initializeTables();
        echo "Datenbank und Tabellen wurden erfolgreich erstellt.\n";
    } catch (Exception $e) {
        error_log("Fehler bei der Datenbankinitialisierung: " . $e->getMessage());
        echo "Fehler bei der Datenbankinitialisierung. Bitte prüfen Sie die Logs.\n";
        exit(1);
    }
} 