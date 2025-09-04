<?php
// teacher/delete_all_instances.php
session_start();

// Sicherstellen, dass nur der Super-Admin darauf zugreifen kann
if (!isset($_SESSION["teacher"]) || $_SESSION["teacher"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert.']);
    exit;
}

header('Content-Type: application/json');

// Lade die Datenbankkonfiguration der Hauptinstanz
$possibleConfigPaths = [
    dirname(__DIR__) . '/includes/database_config.php',
    __DIR__ . '/../includes/database_config.php',
    dirname(dirname(__DIR__)) . '/mcq-test-system/includes/database_config.php'
];

$configLoaded = false;
foreach ($possibleConfigPaths as $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    echo json_encode(['success' => false, 'message' => 'Fehler: Haupt-Datenbankkonfigurationsdatei nicht gefunden in: ' . implode(', ', $possibleConfigPaths)]);
    exit;
}

// Überprüfe, ob die notwendigen DB-Konstanten geladen wurden
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
    // Versuche DatabaseConfig-Klasse zu verwenden
    if (class_exists('DatabaseConfig')) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            // Definiere Fallback-Konstanten
            if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
            if (!defined('DB_USER')) define('DB_USER', 'root');
            if (!defined('DB_PASS')) define('DB_PASS', '');
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Fehler bei DatabaseConfig: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler: DB_HOST, DB_USER oder DB_PASS nicht definiert und DatabaseConfig nicht verfügbar.']);
        exit;
    }
}

// Pfad-Konfiguration
$base_lehrer_instances_path = dirname(dirname(__DIR__)) . '/lehrer_instanzen/';

// Überprüfe auf Bestätigungsparameter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmation = $_POST['confirmation'] ?? '';
    
    if ($confirmation !== 'DELETE_ALL_INSTANCES') {
        echo json_encode(['success' => false, 'message' => 'Bestätigung fehlgeschlagen. Geben Sie "DELETE_ALL_INSTANCES" ein.']);
        exit;
    }

    $deletedInstances = [];
    $errors = [];
    $totalDeleted = 0;

    try {
        // PDO-Verbindung mit Superuser-Rechten herstellen
        $pdo_super = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo_super->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Finde alle Instanzen
        if (is_dir($base_lehrer_instances_path)) {
            $dirs = scandir($base_lehrer_instances_path);
            
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                
                $instancePath = $base_lehrer_instances_path . $dir;
                $mcqPath = $instancePath . '/mcq-test-system';
                
                if (!is_dir($mcqPath)) continue;
                
                try {
                    // Lösche Instanz-Datenbank
                    $db_name = 'mcq_inst_' . $dir;
                    $db_user = 'mcq_user_' . $dir;
                    
                    // Datenbank löschen
                    $stmt = $pdo_super->prepare("DROP DATABASE IF EXISTS `" . $db_name . "`");
                    $stmt->execute();
                    
                    // Benutzer löschen
                    try {
                        $stmt = $pdo_super->prepare("DROP USER IF EXISTS ?@?");
                        $stmt->execute([$db_user, DB_HOST]);
                        $pdo_super->exec("FLUSH PRIVILEGES");
                    } catch (PDOException $e) {
                        // Benutzer existiert möglicherweise nicht, ignorieren
                    }
                    
                    // Lösche Instanz-Verzeichnis
                    if (deleteDirectory($instancePath)) {
                        $deletedInstances[] = $dir;
                        $totalDeleted++;
                    } else {
                        $errors[] = "Verzeichnis für Instanz '$dir' konnte nicht gelöscht werden";
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Fehler beim Löschen der Instanz '$dir': " . $e->getMessage();
                }
            }
        }
        
        $pdo_super = null;
        
        $message = "Erfolgreich $totalDeleted Instanzen gelöscht.";
        if (!empty($errors)) {
            $message .= " Fehler: " . implode(', ', $errors);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'deleted_count' => $totalDeleted,
            'deleted_instances' => $deletedInstances,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Löschen der Instanzen: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage.']);
}

// Hilfsfunktion zum rekursiven Löschen von Verzeichnissen
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}
?>
