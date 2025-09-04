<?php
/**
 * Detailliertes Debug für update_instances.php
 */

// Sicherheitscheck
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'debug_live_2024') {
    die('Debug-Zugriff verweigert.');
}

// Alle Fehler anzeigen
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DETAILLIERTES UPDATE DEBUG ===\n\n";

// Simuliere die gleichen Parameter wie das echte Script
$_GET['admin_key'] = 'update_instances_2024';
$_GET['ajax'] = 'true';

echo "1. Parameter gesetzt\n";

// Schritt-für-Schritt Nachbau des Scripts
echo "2. Sicherheitscheck...\n";
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'update_instances_2024') {
    echo "❌ Sicherheitscheck fehlgeschlagen\n";
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Zugriff verweigert']);
        exit;
    }
    die('Zugriff verweigert');
}
echo "✅ Sicherheitscheck OK\n";

echo "3. AJAX-Modus prüfen...\n";
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === 'true';
echo "AJAX-Modus: " . ($isAjax ? "JA" : "NEIN") . "\n";

echo "4. Konfiguration...\n";
$instancesBasePath = dirname(__DIR__) . '/lehrer_instanzen/';
$sourceBasePath = __DIR__;

echo "Instanzen-Pfad: $instancesBasePath\n";
echo "Quell-Pfad: $sourceBasePath\n";

echo "5. Dateien-Array erstellen...\n";
$filesToUpdate = [
    'teacher/teacher_dashboard.php' => 'Teacher Dashboard (korrigierte Tab-Funktion)',
    'teacher/generate_test.php' => 'Test Generator (korrigierte Debug-Behandlung)',
    'js/main.js' => 'JavaScript Main (korrigierte AJAX-Pfade)',
    'includes/teacher_dashboard/test_generator_view.php' => 'Test Generator View',
    'includes/teacher_dashboard/test_editor_view.php' => 'Test Editor View',
    'includes/teacher_dashboard/configuration_view.php' => 'Configuration View',
    'includes/teacher_dashboard/test_results_view.php' => 'Test Results View',
    'includes/teacher_dashboard/config_view.php' => 'Config View',
    'includes/database_config.php' => 'Database Config (korrigierte Tabellenerstellung)'
];
echo "Dateien definiert: " . count($filesToUpdate) . " Stück\n";

echo "6. Instanzen suchen...\n";
$instances = [];
if (is_dir($instancesBasePath)) {
    echo "Verzeichnis existiert\n";
    $dirs = scandir($instancesBasePath);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $instancePath = $instancesBasePath . $dir;
        if (is_dir($instancePath) && is_dir($instancePath . '/mcq-test-system')) {
            $instances[] = $dir;
            echo "Gefunden: $dir\n";
        }
    }
} else {
    echo "❌ Verzeichnis existiert nicht!\n";
}

echo "Instanzen gefunden: " . count($instances) . "\n";

echo "7. Update-Prozess starten...\n";
if (!empty($instances)) {
    echo "Instanzen vorhanden, starte Update...\n";
    
    $totalUpdated = 0;
    $totalErrors = 0;
    $updateLog = [];
    
    // Teste nur die erste Instanz
    $testInstance = $instances[0];
    echo "Teste mit Instanz: $testInstance\n";
    
    $instanceBasePath = $instancesBasePath . $testInstance . '/mcq-test-system/';
    echo "Instanz-Pfad: $instanceBasePath\n";
    echo "Instanz-Pfad existiert: " . (is_dir($instanceBasePath) ? "JA" : "NEIN") . "\n";
    
    $instanceErrors = 0;
    $instanceUpdated = 0;
    $instanceLog = [];
    
    // Teste nur eine Datei
    $testFile = 'js/main.js';
    $sourceFile = $sourceBasePath . '/' . $testFile;
    $targetFile = $instanceBasePath . $testFile;
    $targetDir = dirname($targetFile);
    
    echo "Test-Datei: $testFile\n";
    echo "Quelle: $sourceFile (existiert: " . (file_exists($sourceFile) ? "JA" : "NEIN") . ")\n";
    echo "Ziel: $targetFile\n";
    echo "Ziel-Dir: $targetDir (existiert: " . (is_dir($targetDir) ? "JA" : "NEIN") . ")\n";
    
    if (file_exists($sourceFile)) {
        if (is_dir($targetDir)) {
            // Versuche zu kopieren
            if (copy($sourceFile, $targetFile)) {
                echo "✅ Kopieren erfolgreich\n";
                $instanceUpdated++;
            } else {
                echo "❌ Kopieren fehlgeschlagen\n";
                $instanceErrors++;
            }
        } else {
            echo "❌ Zielverzeichnis fehlt\n";
            $instanceErrors++;
        }
    } else {
        echo "❌ Quelldatei fehlt\n";
        $instanceErrors++;
    }
    
    $totalUpdated += $instanceUpdated;
    $totalErrors += $instanceErrors;
    
    $updateLog[] = [
        'instance' => $testInstance,
        'files_updated' => $instanceUpdated,
        'errors' => $instanceErrors,
        'details' => $instanceLog
    ];
    
    echo "8. JSON-Response vorbereiten...\n";
    if ($isAjax) {
        echo "AJAX-Modus aktiv, generiere JSON...\n";
        
        $response = [
            'success' => $totalErrors === 0,
            'statistics' => [
                'instances_processed' => 1, // Nur Test-Instanz
                'files_updated' => $totalUpdated,
                'errors' => $totalErrors
            ],
            'instances' => [$testInstance],
            'detailed_log' => $updateLog,
            'error' => $totalErrors > 0 ? 'Test-Update mit ' . $totalErrors . ' Fehlern' : null,
            'debug' => 'Test-Modus - nur eine Instanz und eine Datei getestet'
        ];
        
        echo "Response-Array erstellt\n";
        
        // JSON generieren
        $json = json_encode($response);
        if ($json === false) {
            echo "❌ JSON Encoding fehlgeschlagen: " . json_last_error_msg() . "\n";
        } else {
            echo "✅ JSON erfolgreich erstellt (Länge: " . strlen($json) . ")\n";
            echo "JSON Preview:\n";
            echo substr($json, 0, 200) . "...\n";
            
            echo "9. Header und Output...\n";
            if (!headers_sent()) {
                header('Content-Type: application/json');
                echo $json;
                echo "\n=== JSON GESENDET ===\n";
            } else {
                echo "❌ Headers bereits gesendet!\n";
            }
        }
        exit;
    }
} else {
    echo "Keine Instanzen gefunden\n";
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'statistics' => ['instances_processed' => 0, 'files_updated' => 0, 'errors' => 0],
            'message' => 'Keine Instanzen gefunden'
        ]);
        exit;
    }
}

echo "\n=== DEBUG ENDE ===\n";
?>
