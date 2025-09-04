<?php
/**
 * Debug für performInstanceUpdate Funktion
 */

echo "<h1>🔍 Debug: performInstanceUpdate</h1>\n";

// Simuliere die gleichen Parameter wie in create_instance.php
$instanceName = 'testfinal';
$instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
$sourceBasePath = '/var/www/dianoia-ai.de/mcq-test-system/';

echo "<h2>📋 Parameter</h2>\n";
echo "Instance Name: <code>$instanceName</code><br>\n";
echo "Instances Base Path: <code>$instancesBasePath</code><br>\n";
echo "Source Base Path: <code>$sourceBasePath</code><br>\n";

// Prüfe Pfade
$instanceBasePath = $instancesBasePath . $instanceName . '/mcq-test-system/';
echo "<h2>🔍 Berechnete Pfade</h2>\n";
echo "Instance Base Path: <code>$instanceBasePath</code><br>\n";
echo "Existiert: " . (is_dir($instanceBasePath) ? '✅ YES' : '❌ NO') . "<br>\n";

// Teste die Dateien aus performInstanceUpdate
$filesToUpdate = [
    'teacher/teacher_dashboard.php' => 'Teacher Dashboard',
    'teacher/delete_instance.php' => 'Instanz-Lösch-Script',
    'teacher/generate_test.php' => 'Test Generator',
    'teacher/create_instance.php' => 'Instanz-Erstellung',
    'js/main.js' => 'JavaScript Main',
    'includes/teacher_dashboard/test_generator_view.php' => 'Test Generator View (korrigiert)',
    'includes/teacher_dashboard/test_editor_view.php' => 'Test Editor View (korrigiert)',
    'includes/teacher_dashboard/configuration_view.php' => 'Configuration View (korrigiert)',
    'includes/teacher_dashboard/test_results_view.php' => 'Test Results View (korrigiert)',
    'includes/teacher_dashboard/config_view.php' => 'Config View',
    'includes/teacher_dashboard/get_openai_models.php' => 'OpenAI Models API',
    'includes/teacher_dashboard/get_instances.php' => 'Instanzen-Übersicht API',
    'includes/openai_models.php' => 'OpenAI Models Management',
    'includes/database_config.php' => 'Database Config'
];

echo "<h2>📄 Datei-Tests</h2>\n";
$problemFiles = [];

foreach ($filesToUpdate as $file => $description) {
    $sourceFile = $sourceBasePath . $file;
    $targetFile = $instanceBasePath . $file;
    
    echo "<h3>📄 $file</h3>\n";
    echo "Source: <code>$sourceFile</code> ";
    $sourceExists = file_exists($sourceFile);
    echo ($sourceExists ? '✅' : '❌') . "<br>\n";
    
    echo "Target: <code>$targetFile</code> ";
    $targetExists = file_exists($targetFile);
    echo ($targetExists ? '✅' : '❌') . "<br>\n";
    
    if ($sourceExists && $targetExists) {
        $sourceSize = filesize($sourceFile);
        $targetSize = filesize($targetFile);
        echo "Größe: Source=$sourceSize, Target=$targetSize ";
        echo ($sourceSize === $targetSize ? '✅' : '⚠️') . "<br>\n";
    }
    
    if (!$sourceExists || !$targetExists) {
        $problemFiles[] = $file;
    }
    
    echo "<br>\n";
}

if (!empty($problemFiles)) {
    echo "<h2>⚠️ Problem-Dateien</h2>\n";
    foreach ($problemFiles as $file) {
        echo "❌ $file<br>\n";
    }
}

echo "<h2>🔧 Simuliere performInstanceUpdate</h2>\n";

// Include die Funktion
require_once 'teacher/create_instance.php';

// Führe die Funktion aus
echo "Führe performInstanceUpdate aus...<br>\n";

ob_start();
$result = performInstanceUpdate($instanceName, $instancesBasePath, $sourceBasePath);
$updateOutput = ob_get_clean();

echo "<h3>📄 Update-Output:</h3>\n";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>$updateOutput</pre>\n";

echo "<h3>📊 Update-Ergebnis:</h3>\n";
echo "Success: " . ($result['success'] ? '✅ YES' : '❌ NO') . "<br>\n";
echo "Message: " . htmlspecialchars($result['message']) . "<br>\n";
echo "Updated: " . ($result['updated'] ?? 'N/A') . "<br>\n";
echo "Errors: " . ($result['errors'] ?? 'N/A') . "<br>\n";

// Prüfe Error-Log
echo "<h2>📋 Error-Log (letzten 20 Zeilen)</h2>\n";
$errorLogPath = '/var/log/apache2/error.log';
if (file_exists($errorLogPath)) {
    $lines = file($errorLogPath);
    $lastLines = array_slice($lines, -20);
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: scroll;'>";
    foreach ($lastLines as $line) {
        if (strpos($line, 'CREATE_INSTANCE') !== false || strpos($line, 'UPDATE DEBUG') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>\n";
} else {
    echo "Error-Log nicht gefunden: $errorLogPath<br>\n";
}

?>
