<?php
/**
 * Test-Script für Instanz-Erstellung mit Debug
 */

session_start();

// Temporär als Teacher einloggen für Test
$_SESSION["teacher"] = true;

echo "<h1>🧪 Test: Instanz-Erstellung</h1>\n";

echo "<h2>1. Fehler-Log aktivieren</h2>\n";
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/create_instance_debug.log');

echo "Debug-Log: <code>" . __DIR__ . '/create_instance_debug.log</code><br>\n";

echo "<h2>2. Test-Aufruf</h2>\n";

// Simuliere POST-Anfrage
//$_POST['instance_name'] = 'debugtest';
$_POST['admin_access_code'] = 'admin123';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Erstelle Test-Instanz 'debugtest'...<br>\n";
echo "<pre>\n";

// Include create_instance.php
ob_start();
try {
    include 'teacher/create_instance.php';
} catch (Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo $output;
echo "</pre>\n";

echo "<h2>3. Debug-Log anzeigen</h2>\n";
$logFile = __DIR__ . '/create_instance_debug.log';
if (file_exists($logFile)) {
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>\n";
    echo htmlspecialchars(file_get_contents($logFile));
    echo "</pre>\n";
} else {
    echo "❌ Kein Debug-Log gefunden<br>\n";
}

echo "<h2>4. Prüfe Ergebnis</h2>\n";
$instancePath = dirname(__DIR__) . '/lehrer_instanzen/debugtest/mcq-test-system';
if (is_dir($instancePath)) {
    echo "✅ Instanz wurde erstellt: <code>$instancePath</code><br>\n";
    
    $testsPath = $instancePath . '/tests';
    $resultsPath = $instancePath . '/results';
    
    if (is_dir($testsPath)) {
        $testFiles = glob($testsPath . '/*');
        echo "📁 tests: " . count($testFiles) . " Dateien<br>\n";
        if (count($testFiles) > 0) {
            echo "  ⚠️ PROBLEM: Tests-Ordner ist nicht leer!<br>\n";
            foreach (array_slice($testFiles, 0, 5) as $file) {
                echo "    - " . basename($file) . "<br>\n";
            }
        } else {
            echo "  ✅ Tests-Ordner ist leer<br>\n";
        }
    } else {
        echo "❌ Tests-Ordner fehlt<br>\n";
    }
    
    if (is_dir($resultsPath)) {
        $resultFiles = glob($resultsPath . '/*');
        echo "📁 results: " . count($resultFiles) . " Dateien<br>\n";
        if (count($resultFiles) > 0) {
            echo "  ⚠️ PROBLEM: Results-Ordner ist nicht leer!<br>\n";
            foreach (array_slice($resultFiles, 0, 5) as $file) {
                echo "    - " . basename($file) . "<br>\n";
            }
        } else {
            echo "  ✅ Results-Ordner ist leer<br>\n";
        }
    } else {
        echo "❌ Results-Ordner fehlt<br>\n";
    }
} else {
    echo "❌ Instanz wurde nicht erstellt<br>\n";
}

?>
