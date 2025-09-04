<?php
/**
 * Fix: Kopiere API-Konfiguration in alle Instanzen
 */

session_start();
$_SESSION["teacher"] = true; // Für Sicherheit

echo "<h1>🔧 Fix: API-Konfiguration in Instanzen</h1>\n";

// Finde die Haupt-API-Konfiguration
$mainApiConfigPaths = [
    __DIR__ . '/includes/config/api_config.json',
    __DIR__ . '/config/api_config.json'
];

$mainApiConfig = null;
$mainApiConfigPath = null;

foreach ($mainApiConfigPaths as $path) {
    if (file_exists($path) && filesize($path) > 0) {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        if ($data && isset($data['api_key']) && !empty($data['api_key'])) {
            $mainApiConfig = $data;
            $mainApiConfigPath = $path;
            break;
        }
    }
}

echo "<h2>📋 Haupt-API-Konfiguration</h2>\n";
if ($mainApiConfig) {
    echo "✅ Gefunden: <code>$mainApiConfigPath</code><br>\n";
    echo "🔑 API-Key: " . substr($mainApiConfig['api_key'], 0, 8) . "... (" . strlen($mainApiConfig['api_key']) . " Zeichen)<br>\n";
    echo "📄 Weitere Keys: " . implode(', ', array_filter(array_keys($mainApiConfig), function($k) { return $k !== 'api_key'; })) . "<br>\n";
} else {
    echo "❌ Keine gültige API-Konfiguration gefunden!<br>\n";
    echo "Überprüfte Pfade:<br>\n";
    foreach ($mainApiConfigPaths as $path) {
        echo "  - <code>$path</code> " . (file_exists($path) ? "✅" : "❌") . "<br>\n";
    }
    exit;
}

echo "<h2>🔄 Instanzen-Update</h2>\n";

$instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
if (!is_dir($instancesPath)) {
    echo "❌ Instanzen-Verzeichnis nicht gefunden: $instancesPath<br>\n";
    exit;
}

$instances = array_filter(scandir($instancesPath), function($item) use ($instancesPath) {
    return $item !== '.' && $item !== '..' && is_dir($instancesPath . $item);
});

$updated = 0;
$errors = 0;

foreach ($instances as $instance) {
    echo "<h3>📦 Instanz: $instance</h3>\n";
    
    $instanceMcqPath = $instancesPath . $instance . '/mcq-test-system';
    
    if (!is_dir($instanceMcqPath)) {
        echo "  ⚠️ MCQ-System-Verzeichnis nicht gefunden<br>\n";
        $errors++;
        continue;
    }
    
    // Erstelle config-Verzeichnisse falls nötig
    $configDirs = [
        $instanceMcqPath . '/config',
        $instanceMcqPath . '/includes/config'
    ];
    
    foreach ($configDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "  📁 Erstellt: " . basename(dirname($dir)) . "/" . basename($dir) . "<br>\n";
        }
    }
    
    // Kopiere API-Config in beide mögliche Orte
    $targetPaths = [
        $instanceMcqPath . '/config/api_config.json',
        $instanceMcqPath . '/includes/config/api_config.json'
    ];
    
    $instanceUpdated = false;
    foreach ($targetPaths as $targetPath) {
        if (copy($mainApiConfigPath, $targetPath)) {
            echo "  ✅ Kopiert: " . str_replace($instanceMcqPath, '', $targetPath) . "<br>\n";
            $instanceUpdated = true;
        } else {
            echo "  ❌ Fehler beim Kopieren: " . str_replace($instanceMcqPath, '', $targetPath) . "<br>\n";
        }
    }
    
    if ($instanceUpdated) {
        $updated++;
        
        // Teste die neue Konfiguration
        $testConfigPath = $targetPaths[0]; // Verwende ersten Pfad zum Testen
        if (file_exists($testConfigPath)) {
            $testContent = file_get_contents($testConfigPath);
            $testData = json_decode($testContent, true);
            if ($testData && isset($testData['api_key'])) {
                echo "  ✅ Test erfolgreich: API-Key korrekt kopiert<br>\n";
            } else {
                echo "  ⚠️ Test fehlgeschlagen: API-Key nicht lesbar<br>\n";
            }
        }
    } else {
        $errors++;
    }
}

echo "<h2>📊 Zusammenfassung</h2>\n";
echo "✅ Erfolgreich aktualisiert: $updated Instanzen<br>\n";
echo "❌ Fehler: $errors<br>\n";

if ($updated > 0) {
    echo "<br><h2>🧪 Test-Links</h2>\n";
    echo "Teste die OpenAI-Modelle in den aktualisierten Instanzen:<br>\n";
    
    foreach (array_slice($instances, 0, 3) as $instance) {
        $instanceMcqPath = $instancesPath . $instance . '/mcq-test-system';
        if (is_dir($instanceMcqPath)) {
            echo "🔗 <a href='/lehrer_instanzen/$instance/mcq-test-system/teacher/teacher_dashboard.php' target='_blank'>$instance - Test-Generator</a><br>\n";
        }
    }
    
    echo "<br>💡 <strong>Tipp:</strong> Gehe zum Test-Generator und überprüfe, ob die Modelle jetzt korrekt geladen werden.<br>\n";
}

?>
