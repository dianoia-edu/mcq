<?php
/**
 * Einfache Verteilung der API-Konfiguration an alle Instanzen
 * OHNE Überschreibung oder Änderung der existierenden Config
 */

session_start();
$_SESSION["teacher"] = true;

echo "<h1>📦 API-Config Verteilung</h1>\n";

// Finde die Haupt-API-Konfiguration
$mainConfigPaths = [
    __DIR__ . '/config/api_config.json',
    __DIR__ . '/includes/config/api_config.json',
    __DIR__ . '/includes/config/openai_config.php'
];

$sourceConfig = null;
$sourceConfigPath = null;

foreach ($mainConfigPaths as $path) {
    if (file_exists($path) && filesize($path) > 50) {
        echo "🔍 Prüfe: <code>$path</code><br>\n";
        
        if (str_ends_with($path, '.json')) {
            $content = file_get_contents($path);
            $data = json_decode($content, true);
            if ($data && isset($data['api_key']) && $data['api_key'] !== 'YOUR_OPENAI_API_KEY_HERE') {
                $sourceConfig = $content;
                $sourceConfigPath = $path;
                echo "✅ Gefunden: JSON-Config mit API-Key<br>\n";
                break;
            }
        } elseif (str_ends_with($path, '.php')) {
            $content = file_get_contents($path);
            if (strpos($content, 'sk-') !== false) {
                // Konvertiere PHP zu JSON
                if (preg_match('/\$apiKey\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    $apiKey = $matches[1];
                    $sourceConfig = json_encode([
                        'api_key' => $apiKey,
                        'api_base_url' => 'https://api.openai.com/v1',
                        'default_model' => 'gpt-4o-mini',
                        'max_tokens' => 4000,
                        'timeout' => 30,
                        'temperature' => 0.7,
                        'converted_from' => 'openai_config.php',
                        'converted_at' => date('Y-m-d H:i:s')
                    ], JSON_PRETTY_PRINT);
                    $sourceConfigPath = $path;
                    echo "✅ Gefunden: PHP-Config mit API-Key (wird zu JSON konvertiert)<br>\n";
                    break;
                }
            }
        }
    }
}

if (!$sourceConfig) {
    echo "❌ <strong>Keine gültige API-Konfiguration gefunden!</strong><br>\n";
    echo "Überprüfte Pfade:<br>\n";
    foreach ($mainConfigPaths as $path) {
        $exists = file_exists($path) ? '✅' : '❌';
        $size = file_exists($path) ? filesize($path) . ' bytes' : 'nicht vorhanden';
        echo "  - <code>$path</code> $exists ($size)<br>\n";
    }
    exit;
}

echo "<h2>📋 Quelle</h2>\n";
echo "Datei: <code>$sourceConfigPath</code><br>\n";

// Zeige API-Key Vorschau
if (str_ends_with($sourceConfigPath, '.json')) {
    $configData = json_decode($sourceConfig, true);
    $apiKey = $configData['api_key'] ?? '';
} else {
    preg_match('/\$apiKey\s*=\s*[\'"]([^\'"]+)[\'"]/', file_get_contents($sourceConfigPath), $matches);
    $apiKey = $matches[1] ?? '';
}

if ($apiKey) {
    echo "🔑 API-Key: " . substr($apiKey, 0, 15) . "... (" . strlen($apiKey) . " Zeichen)<br>\n";
}

echo "<h2>📦 Verteilung an Instanzen</h2>\n";

$instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
if (!is_dir($instancesPath)) {
    echo "❌ Instanzen-Verzeichnis nicht gefunden: $instancesPath<br>\n";
    exit;
}

$instances = array_filter(scandir($instancesPath), function($item) use ($instancesPath) {
    return $item !== '.' && $item !== '..' && is_dir($instancesPath . $item);
});

echo "Gefunde Instanzen: " . count($instances) . "<br><br>\n";

$updated = 0;
$errors = 0;

foreach ($instances as $instance) {
    echo "<h3>📦 $instance</h3>\n";
    
    $instanceMcqPath = $instancesPath . $instance . '/mcq-test-system';
    
    if (!is_dir($instanceMcqPath)) {
        echo "  ⚠️ MCQ-System nicht gefunden<br>\n";
        $errors++;
        continue;
    }
    
    // Erstelle Zielverzeichnisse
    $targetDirs = [
        $instanceMcqPath . '/config',
        $instanceMcqPath . '/includes/config'
    ];
    
    foreach ($targetDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "  📁 Erstellt: " . str_replace($instanceMcqPath, '', $dir) . "<br>\n";
        }
    }
    
    // Schreibe JSON-Config in beide Ziele
    $targets = [
        $instanceMcqPath . '/config/api_config.json',
        $instanceMcqPath . '/includes/config/api_config.json'
    ];
    
    $instanceSuccess = true;
    foreach ($targets as $target) {
        if (file_put_contents($target, $sourceConfig)) {
            echo "  ✅ " . str_replace($instanceMcqPath, '', $target) . "<br>\n";
        } else {
            echo "  ❌ Fehler: " . str_replace($instanceMcqPath, '', $target) . "<br>\n";
            $instanceSuccess = false;
        }
    }
    
    if ($instanceSuccess) {
        $updated++;
    } else {
        $errors++;
    }
}

echo "<h2>📊 Zusammenfassung</h2>\n";
echo "✅ Erfolgreich aktualisiert: <strong>$updated</strong> Instanzen<br>\n";
echo "❌ Fehler: <strong>$errors</strong><br>\n";

if ($updated > 0) {
    echo "<br><h2>🎉 Verteilung abgeschlossen!</h2>\n";
    echo "Die API-Konfiguration wurde an alle Instanzen verteilt.<br>\n";
    
    echo "<br><h3>🧪 Test-Links</h3>\n";
    foreach (array_slice($instances, 0, 5) as $instance) {
        echo "🔗 <a href='/lehrer_instanzen/$instance/mcq-test-system/teacher/teacher_dashboard.php' target='_blank'>$instance - Test-Generator</a><br>\n";
    }
    
    echo "<br>💡 <strong>Die OpenAI-Modelle sollten jetzt in allen Instanzen funktionieren!</strong><br>\n";
    echo "Gehe zu einer Instanz und prüfe den Test-Generator - die Modell-Auswahl sollte ohne Fehler laden.<br>\n";
}

// Erstelle auch Haupt-JSON falls es noch nicht existiert
$mainJsonPath = __DIR__ . '/config/api_config.json';
if (!file_exists($mainJsonPath) && $sourceConfig) {
    if (!is_dir(dirname($mainJsonPath))) {
        mkdir(dirname($mainJsonPath), 0777, true);
    }
    file_put_contents($mainJsonPath, $sourceConfig);
    echo "<br>✅ Haupt-JSON-Config erstellt: <code>config/api_config.json</code><br>\n";
}

$includesJsonPath = __DIR__ . '/includes/config/api_config.json';
if (!file_exists($includesJsonPath) && $sourceConfig) {
    if (!is_dir(dirname($includesJsonPath))) {
        mkdir(dirname($includesJsonPath), 0777, true);
    }
    file_put_contents($includesJsonPath, $sourceConfig);
    echo "✅ Includes-JSON-Config erstellt: <code>includes/config/api_config.json</code><br>\n";
}

?>
