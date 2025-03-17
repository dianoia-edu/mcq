<?php
// Aktiviere Fehlerberichterstattung
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Korrekter Pfad zur Datenbankkonfiguration
require_once 'includes/database_config.php';

try {
    // Versuche eine Datenbankverbindung herzustellen
    $db = DatabaseConfig::getInstance();
    $connection = $db->getConnection();
    
    // Wenn wir bis hier kommen, war die Verbindung erfolgreich
    echo "Datenbankverbindung erfolgreich hergestellt!<br>";
    echo "Server-Info: " . $connection->getAttribute(PDO::ATTR_SERVER_INFO) . "<br>";
    echo "Server-Version: " . $connection->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
    
} catch (PDOException $e) {
    // Bei einem Fehler geben wir die Fehlermeldung aus
    echo "Fehler bei der Datenbankverbindung:<br>";
    echo "Fehlermeldung: " . $e->getMessage() . "<br>";
    
    // Zusätzliche Debugging-Informationen
    echo "<br>Server-Informationen:<br>";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'nicht gesetzt') . "<br>";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'nicht gesetzt') . "<br>";
    echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'nicht gesetzt') . "<br>";
    echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'nicht gesetzt') . "<br>";
}
?> 