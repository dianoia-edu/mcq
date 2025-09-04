<?php
/**
 * Schneller Test für Instanz-Erstellung
 */

session_start();
$_SESSION["teacher"] = true;

// Instanz testdb3 löschen falls sie existiert
$instancePath = '/var/www/dianoia-ai.de/lehrer_instanzen/testdb3';
if (is_dir($instancePath)) {
    echo "🗑️ Lösche existierende Instanz testdb3...<br>\n";
    exec("rm -rf $instancePath");
}

// POST-Parameter setzen
$_POST['instance_name'] = 'testdb3';
$_POST['admin_access_code'] = 'admin123';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h1>🧪 Test: Instanz testdb3 erstellen</h1>\n";

// Error-Reporting aktivieren
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include create_instance.php
ob_start();
try {
    include 'teacher/create_instance.php';
} catch (Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "<h2>📄 Output:</h2>\n";
echo "<pre>$output</pre>\n";

// Prüfe Ergebnis
echo "<h2>🔍 Prüfung:</h2>\n";
$mcqPath = '/var/www/dianoia-ai.de/lehrer_instanzen/testdb3/mcq-test-system';
if (is_dir($mcqPath)) {
    echo "✅ Instanz erstellt: <code>$mcqPath</code><br>\n";
    
    $testsPath = $mcqPath . '/tests';
    $resultsPath = $mcqPath . '/results';
    $configPath = $mcqPath . '/config';
    $dbConfigPath = $mcqPath . '/includes/database_config.php';
    
    // Tests-Ordner prüfen
    if (is_dir($testsPath)) {
        $files = glob($testsPath . '/*');
        echo "📁 tests: " . count($files) . " Dateien " . (count($files) === 0 ? "✅" : "❌") . "<br>\n";
    }
    
    // Results-Ordner prüfen  
    if (is_dir($resultsPath)) {
        $files = glob($resultsPath . '/*');
        echo "📁 results: " . count($files) . " Dateien " . (count($files) === 0 ? "✅" : "❌") . "<br>\n";
    }
    
    // Config prüfen
    if (is_dir($configPath)) {
        echo "📁 config: ✅ existiert<br>\n";
        $appConfig = $configPath . '/app_config.json';
        if (file_exists($appConfig)) {
            $config = json_decode(file_get_contents($appConfig), true);
            echo "🔑 admin_access_code: " . ($config['admin_access_code'] ?? 'FEHLT') . "<br>\n";
        }
    }
    
    // Database config prüfen
    if (file_exists($dbConfigPath)) {
        echo "🗄️ database_config.php: ✅ existiert<br>\n";
    } else {
        echo "🗄️ database_config.php: ❌ fehlt<br>\n";
    }
    
} else {
    echo "❌ Instanz nicht erstellt<br>\n";
}

?>
