<?php
/**
 * API-Endpoint für Instanzen-Übersicht
 * Liefert alle erstellten Lehrerinstanzen mit Details
 */

// Debug-Parameter prüfen
$isDebug = isset($_GET['debug']) && $_GET['debug'] == '1';
$testPaths = isset($_GET['test_paths']) && $_GET['test_paths'] == '1';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Test-Modus für Pfade
if ($testPaths) {
    $paths = [
        'dirname(__DIR__, 2)' => dirname(__DIR__, 2) . '/lehrer_instanzen/',
        'dirname($_SERVER[DOCUMENT_ROOT])' => dirname($_SERVER['DOCUMENT_ROOT']) . '/lehrer_instanzen/',
        '/var/www/lehrer_instanzen/' => '/var/www/lehrer_instanzen/',
        '/var/www/dianoia-ai.de/lehrer_instanzen/' => '/var/www/dianoia-ai.de/lehrer_instanzen/'
    ];
    
    $result = [
        'path_test' => true,
        'server_info' => [
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'undefined',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'undefined', 
            'script_path' => __FILE__,
            'working_dir' => getcwd()
        ],
        'paths_tested' => []
    ];
    
    foreach ($paths as $label => $path) {
        $result['paths_tested'][$label] = [
            'path' => $path,
            'exists' => is_dir($path),
            'readable' => is_dir($path) ? is_readable($path) : false,
            'contents' => is_dir($path) ? scandir($path) : null
        ];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

try {
    // Sicherheitscheck für Admin-Zugang
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Konfiguration laden
    require_once __DIR__ . '/../config_loader.php';
    $config = loadConfig();
    
    // Pfad zu den Instanzen - verschiedene Varianten prüfen
    $possiblePaths = [
        dirname(__DIR__, 2) . '/lehrer_instanzen/',  // Standard
        dirname($_SERVER['DOCUMENT_ROOT']) . '/lehrer_instanzen/', // Webserver
        '/var/www/lehrer_instanzen/', // Direkter Server-Pfad
        '/var/www/dianoia-ai.de/lehrer_instanzen/' // Vollständiger Pfad
    ];
    
    $instancesBasePath = null;
    foreach ($possiblePaths as $path) {
        if (is_dir($path)) {
            $instancesBasePath = $path;
            break;
        }
    }
    
    if (!$instancesBasePath) {
        echo json_encode([
            'success' => false,
            'error' => 'Instanzen-Verzeichnis nicht gefunden',
            'debug' => [
                'checked_paths' => $possiblePaths,
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'undefined',
                'script_path' => __FILE__,
                'working_dir' => getcwd()
            ]
        ]);
        exit;
    }
    
    $instances = [];
    
    // Alle Verzeichnisse durchsuchen
    $dirs = scandir($instancesBasePath);
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $instancePath = $instancesBasePath . $dir;
        
        if (!is_dir($instancePath)) continue;
        
        // Prüfe ob es eine MCQ-Instanz ist
        $mcqPath = $instancePath . '/mcq-test-system';
        if (!is_dir($mcqPath)) continue;
        
        // Lade Instanz-Informationen
        $instanceInfo = getInstanceInfo($dir, $instancePath, $mcqPath);
        if ($instanceInfo) {
            $instances[] = $instanceInfo;
        }
    }
    
    // Sortiere nach Erstellungsdatum (neueste zuerst)
    usort($instances, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $response = [
        'success' => true,
        'instances' => $instances,
        'count' => count($instances),
        'instances_path' => $instancesBasePath
    ];
    
    // Debug-Informationen hinzufügen wenn angefordert
    if ($isDebug) {
        $response['debug_info'] = [
            'script_path' => __FILE__,
            'working_dir' => getcwd(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'undefined',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'undefined',
            'instances_found' => count($instances),
            'scan_result' => is_dir($instancesBasePath) ? scandir($instancesBasePath) : 'path_not_found',
            'all_tested_paths' => [
                dirname(__DIR__, 2) . '/lehrer_instanzen/' => is_dir(dirname(__DIR__, 2) . '/lehrer_instanzen/'),
                dirname($_SERVER['DOCUMENT_ROOT']) . '/lehrer_instanzen/' => is_dir(dirname($_SERVER['DOCUMENT_ROOT']) . '/lehrer_instanzen/'),
                '/var/www/lehrer_instanzen/' => is_dir('/var/www/lehrer_instanzen/'),
                '/var/www/dianoia-ai.de/lehrer_instanzen/' => is_dir('/var/www/dianoia-ai.de/lehrer_instanzen/')
            ]
        ];
    }
    
    echo json_encode($response, $isDebug ? JSON_PRETTY_PRINT : 0);
    
} catch (Exception $e) {
    error_log("Instanzen-Übersicht Fehler: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'instances' => []
    ]);
}

/**
 * Sammle Informationen über eine Instanz
 */
function getInstanceInfo($instanceName, $instancePath, $mcqPath) {
    $info = [
        'name' => $instanceName,
        'display_name' => ucwords(str_replace(['_', '-'], ' ', $instanceName)),
        'path' => $instancePath,
        'url' => '/lehrer_instanzen/' . $instanceName . '/mcq-test-system/',
        'admin_url' => '/lehrer_instanzen/' . $instanceName . '/mcq-test-system/teacher/teacher_dashboard.php',
        'status' => 'unknown',
        'admin_code' => 'Unbekannt',
        'database' => 'mcq_inst_' . $instanceName,
        'created_at' => date('Y-m-d H:i:s', filemtime($instancePath)),
        'last_activity' => 'Unbekannt',
        'test_count' => 0,
        'result_count' => 0,
        'files_ok' => false,
        'database_ok' => false
    ];
    
    // 1. Prüfe wichtige Dateien
    $requiredFiles = [
        $mcqPath . '/index.php',
        $mcqPath . '/teacher/teacher_dashboard.php',
        $mcqPath . '/includes/config_loader.php'
    ];
    
    $filesOk = true;
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            $filesOk = false;
            break;
        }
    }
    $info['files_ok'] = $filesOk;
    
    // 2. Admin-Code aus Konfiguration lesen
    try {
        $configFile = $mcqPath . '/includes/config/api_config.json';
        if (file_exists($configFile)) {
            $configData = json_decode(file_get_contents($configFile), true);
            if (isset($configData['admin_access_code'])) {
                $info['admin_code'] = $configData['admin_access_code'];
            }
        }
    } catch (Exception $e) {
        // Admin-Code konnte nicht gelesen werden
    }
    
    // 3. Datenbankverbindung testen
    try {
        $dbConfig = loadDatabaseConfig($mcqPath);
        if ($dbConfig) {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4",
                $dbConfig['username'],
                $dbConfig['password']
            );
            $info['database_ok'] = true;
            
            // Statistiken sammeln
            $info = collectDatabaseStats($pdo, $info);
        }
    } catch (Exception $e) {
        $info['database_ok'] = false;
        $info['database_error'] = $e->getMessage();
    }
    
    // 4. Ergebnisse-Verzeichnis prüfen
    $resultsPath = $mcqPath . '/results';
    if (is_dir($resultsPath)) {
        $resultFiles = glob($resultsPath . '/*/*.xml');
        $info['result_count'] = count($resultFiles);
        
        // Letzte Aktivität aus Ergebnissen
        if (!empty($resultFiles)) {
            $latestFile = '';
            $latestTime = 0;
            foreach ($resultFiles as $file) {
                $mtime = filemtime($file);
                if ($mtime > $latestTime) {
                    $latestTime = $mtime;
                    $latestFile = $file;
                }
            }
            if ($latestTime > 0) {
                $info['last_activity'] = date('Y-m-d H:i:s', $latestTime);
            }
        }
    }
    
    // 5. Status bestimmen
    if ($info['files_ok'] && $info['database_ok']) {
        $info['status'] = 'active';
    } elseif ($info['files_ok']) {
        $info['status'] = 'partial';
    } else {
        $info['status'] = 'error';
    }
    
    return $info;
}

/**
 * Lade Datenbank-Konfiguration einer Instanz
 */
function loadDatabaseConfig($mcqPath) {
    $configFile = $mcqPath . '/includes/config/api_config.json';
    if (!file_exists($configFile)) {
        return null;
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    if (!$config) {
        return null;
    }
    
    return [
        'host' => $config['db_host'] ?? 'localhost',
        'dbname' => $config['db_name'] ?? null,
        'username' => $config['db_user'] ?? null,
        'password' => $config['db_password'] ?? null
    ];
}

/**
 * Sammle Statistiken aus der Datenbank
 */
function collectDatabaseStats($pdo, $info) {
    try {
        // Test-Anzahl
        $stmt = $pdo->query("SHOW TABLES LIKE 'tests'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tests");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $info['test_count'] = $result['count'] ?? 0;
        }
        
        // Letzte Aktivität aus der Datenbank
        $stmt = $pdo->query("SHOW TABLES LIKE 'test_attempts'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT MAX(completed_at) as last_activity FROM test_attempts WHERE completed_at IS NOT NULL");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['last_activity']) {
                $info['last_activity'] = $result['last_activity'];
            }
        }
        
    } catch (Exception $e) {
        // Statistiken konnten nicht gesammelt werden
        $info['stats_error'] = $e->getMessage();
    }
    
    return $info;
}
?>
