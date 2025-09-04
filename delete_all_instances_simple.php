<?php
/**
 * Vereinfachtes Script zum Löschen aller Instanzen
 * Verwendet die gleiche Konfiguration wie das Hauptsystem
 */

session_start();

// Sicherstellen, dass nur der Super-Admin darauf zugreifen kann
if (!isset($_SESSION["teacher"]) || $_SESSION["teacher"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert.']);
    exit;
}

header('Content-Type: application/json');

// Lade die DatabaseConfig-Klasse
require_once __DIR__ . '/includes/database_config.php';

try {
    $dbConfig = DatabaseConfig::getInstance();
    $pdo = $dbConfig->getConnection();
    
    if (!$pdo) {
        throw new Exception('Datenbankverbindung fehlgeschlagen');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
    exit;
}

// Überprüfe auf Bestätigungsparameter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmation = $_POST['confirmation'] ?? '';
    
    if ($confirmation !== 'DELETE_ALL_INSTANCES') {
        echo json_encode(['success' => false, 'message' => 'Bestätigung fehlgeschlagen. Geben Sie "DELETE_ALL_INSTANCES" ein.']);
        exit;
    }

    // Finde Instanzen-Verzeichnis
    $possibleInstancePaths = [
        dirname(__DIR__) . '/lehrer_instanzen/',
        '/var/www/dianoia-ai.de/lehrer_instanzen/',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/lehrer_instanzen/'
    ];
    
    $instancesBasePath = null;
    foreach ($possibleInstancePaths as $path) {
        if (is_dir($path)) {
            $instancesBasePath = $path;
            break;
        }
    }
    
    if (!$instancesBasePath) {
        echo json_encode(['success' => false, 'message' => 'Instanzen-Verzeichnis nicht gefunden. Suchpfade: ' . implode(', ', $possibleInstancePaths)]);
        exit;
    }

    $deletedInstances = [];
    $errors = [];
    $totalDeleted = 0;

    try {
        // Finde alle Instanzen
        $dirs = scandir($instancesBasePath);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $instancePath = $instancesBasePath . $dir;
            $mcqPath = $instancePath . '/mcq-test-system';
            
            if (!is_dir($mcqPath)) continue;
            
            try {
                // Lösche Instanz-Datenbank
                $db_name = 'mcq_inst_' . $dir;
                $db_user = 'mcq_user_' . $dir;
                
                // Datenbank löschen
                $stmt = $pdo->prepare("DROP DATABASE IF EXISTS `" . $db_name . "`");
                $stmt->execute();
                
                // Benutzer löschen
                try {
                    $stmt = $pdo->prepare("DROP USER IF EXISTS ?@'%'");
                    $stmt->execute([$db_user]);
                    $stmt = $pdo->prepare("DROP USER IF EXISTS ?@'localhost'");
                    $stmt->execute([$db_user]);
                    $pdo->exec("FLUSH PRIVILEGES");
                } catch (PDOException $e) {
                    // Benutzer existiert möglicherweise nicht, ignorieren
                    $errors[] = "Warnung: Benutzer $db_user konnte nicht gelöscht werden: " . $e->getMessage();
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
        
        $message = "Erfolgreich $totalDeleted Instanzen gelöscht.";
        if (!empty($errors)) {
            $message .= " Warnungen: " . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= " (und " . (count($errors) - 3) . " weitere)";
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'deleted_count' => $totalDeleted,
            'deleted_instances' => $deletedInstances,
            'errors' => $errors,
            'instances_path' => $instancesBasePath
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
