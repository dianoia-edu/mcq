<?php
/**
 * Instanz-Lösch-Script
 * Löscht eine Lehrerinstanz vollständig (Dateien + Datenbank)
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

// Sicherheitscheck
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

try {
    // Parameter prüfen
    if (!isset($_POST['instance_name']) || empty($_POST['instance_name'])) {
        throw new Exception('Instanz-Name fehlt');
    }
    
    if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'LÖSCHEN') {
        throw new Exception('Bestätigung fehlt oder ungültig');
    }
    
    $instanceName = trim($_POST['instance_name']);
    
    // Validiere Instanz-Name (Sicherheit)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $instanceName)) {
        throw new Exception('Ungültiger Instanz-Name');
    }
    
    // Pfade definieren
    $instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
    $instancePath = $instancesBasePath . $instanceName;
    $mcqPath = $instancePath . '/mcq-test-system';
    
    // Prüfe ob Instanz existiert
    if (!is_dir($instancePath)) {
        throw new Exception('Instanz "' . $instanceName . '" nicht gefunden');
    }
    
    // Prüfe ob es eine MCQ-Instanz ist
    if (!is_dir($mcqPath)) {
        throw new Exception('Instanz "' . $instanceName . '" ist keine gültige MCQ-Instanz');
    }
    
    $deletedItems = [];
    $errors = [];
    
    // 1. Datenbank löschen
    try {
        require_once '../includes/database_config.php';
        $dbConfig = DatabaseConfig::getInstance();
        
        $databaseName = 'mcq_inst_' . $instanceName;
        
        // Prüfe ob Datenbank existiert
        $pdo = $dbConfig->getConnection();
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$databaseName]);
        
        if ($stmt->fetch()) {
            // Datenbank löschen
            $pdo->exec("DROP DATABASE IF EXISTS `$databaseName`");
            $deletedItems[] = "Datenbank: $databaseName";
        } else {
            $deletedItems[] = "Datenbank: $databaseName (war nicht vorhanden)";
        }
        
    } catch (Exception $e) {
        $errors[] = "Datenbank-Fehler: " . $e->getMessage();
    }
    
    // 2. Dateien löschen (rekursiv)
    try {
        $deletedFiles = deleteDirectory($instancePath);
        $deletedItems[] = "Dateien: $deletedFiles Dateien/Ordner gelöscht";
        
    } catch (Exception $e) {
        $errors[] = "Datei-Fehler: " . $e->getMessage();
    }
    
    // Logging
    error_log("INSTANZ GELÖSCHT: $instanceName - Items: " . implode(', ', $deletedItems) . 
              ($errors ? " - Fehler: " . implode(', ', $errors) : ""));
    
    echo json_encode([
        'success' => true,
        'message' => "Instanz '$instanceName' wurde erfolgreich gelöscht",
        'deleted_items' => $deletedItems,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    error_log("FEHLER beim Löschen der Instanz: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Rekursive Verzeichnis-Löschung
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return 0;
    }
    
    $deletedCount = 0;
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $dir . '/' . $file;
        
        if (is_dir($filePath)) {
            $deletedCount += deleteDirectory($filePath);
        } else {
            if (unlink($filePath)) {
                $deletedCount++;
            }
        }
    }
    
    if (rmdir($dir)) {
        $deletedCount++;
    }
    
    return $deletedCount;
}
?>
