<?php
/**
 * Debug API-Konfiguration in Instanzen
 */

echo "<h1>🔍 API-Konfiguration Debug</h1>\n";

// Prüfe alle möglichen Pfade für API-Config
$possiblePaths = [
    'includes/config/api_config.json',
    'config/api_config.json',
    '../includes/config/api_config.json',
    '../config/api_config.json'
];

echo "<h2>📁 Haupt-System</h2>\n";
foreach ($possiblePaths as $path) {
    $fullPath = __DIR__ . '/' . $path;
    echo "Pfad: <code>$fullPath</code><br>\n";
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "✅ Existiert ($size bytes)<br>\n";
        
        if ($size > 0) {
            $content = file_get_contents($fullPath);
            $data = json_decode($content, true);
            if ($data) {
                echo "📄 JSON gültig<br>\n";
                echo "🔑 Enthält API-Key: " . (isset($data['api_key']) ? (strlen($data['api_key']) > 0 ? '✅ JA' : '❌ LEER') : '❌ NEIN') . "<br>\n";
                if (isset($data['api_key'])) {
                    echo "🔐 API-Key Länge: " . strlen($data['api_key']) . " Zeichen<br>\n";
                    echo "🔐 API-Key Start: " . substr($data['api_key'], 0, 8) . "...<br>\n";
                }
            } else {
                echo "❌ JSON ungültig<br>\n";
            }
        }
    } else {
        echo "❌ Existiert nicht<br>\n";
    }
    echo "<br>\n";
}

echo "<h2>🔍 Test-Instanzen</h2>\n";
$instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
if (is_dir($instancesPath)) {
    $instances = array_filter(scandir($instancesPath), function($item) use ($instancesPath) {
        return $item !== '.' && $item !== '..' && is_dir($instancesPath . $item);
    });
    
    foreach (array_slice($instances, 0, 3) as $instance) { // Nur erste 3 Instanzen testen
        echo "<h3>📦 Instanz: $instance</h3>\n";
        $instanceMcqPath = $instancesPath . $instance . '/mcq-test-system';
        
        if (is_dir($instanceMcqPath)) {
            echo "MCQ-Pfad: <code>$instanceMcqPath</code> ✅<br>\n";
            
            $instancePaths = [
                'includes/config/api_config.json',
                'config/api_config.json'
            ];
            
            foreach ($instancePaths as $relPath) {
                $fullPath = $instanceMcqPath . '/' . $relPath;
                echo "  Prüfe: <code>$relPath</code><br>\n";
                
                if (file_exists($fullPath)) {
                    $size = filesize($fullPath);
                    echo "  ✅ Existiert ($size bytes)<br>\n";
                    
                    if ($size > 0) {
                        $content = file_get_contents($fullPath);
                        $data = json_decode($content, true);
                        if ($data && isset($data['api_key'])) {
                            echo "  🔑 API-Key vorhanden (" . strlen($data['api_key']) . " Zeichen)<br>\n";
                        } else {
                            echo "  ❌ Kein API-Key gefunden<br>\n";
                        }
                    }
                } else {
                    echo "  ❌ Existiert nicht<br>\n";
                }
            }
        } else {
            echo "MCQ-Pfad nicht gefunden<br>\n";
        }
        echo "<br>\n";
    }
} else {
    echo "Instanzen-Verzeichnis nicht gefunden: $instancesPath<br>\n";
}

echo "<h2>🧪 config_loader.php Test</h2>\n";
try {
    require_once 'includes/config_loader.php';
    $config = loadConfig();
    
    echo "✅ config_loader.php funktioniert<br>\n";
    echo "🔑 API-Key im Config: " . (isset($config['api_key']) ? (strlen($config['api_key']) > 0 ? '✅ JA' : '❌ LEER') : '❌ NEIN') . "<br>\n";
    
    if (isset($config['api_key'])) {
        echo "🔐 API-Key Länge: " . strlen($config['api_key']) . " Zeichen<br>\n";
    }
    
    echo "📋 Config-Keys: " . implode(', ', array_keys($config)) . "<br>\n";
    
} catch (Exception $e) {
    echo "❌ config_loader.php Fehler: " . $e->getMessage() . "<br>\n";
}

echo "<h2>🔧 OpenAI Models Test</h2>\n";
try {
    require_once 'includes/openai_models.php';
    
    // Teste mit leerem API-Key
    $models1 = new OpenAIModels('');
    $fallback = $models1->getAvailableModels();
    echo "📋 Fallback-Modelle: " . count($fallback) . " verfügbar<br>\n";
    
    // Teste mit echtem API-Key falls vorhanden
    if (isset($config['api_key']) && !empty($config['api_key'])) {
        echo "🧪 Teste mit echtem API-Key...<br>\n";
        $models2 = new OpenAIModels($config['api_key']);
        
        ob_start();
        $realModels = $models2->getAvailableModels();
        $output = ob_get_clean();
        
        echo "📋 Echte API-Modelle: " . count($realModels) . " verfügbar<br>\n";
        if (!empty($output)) {
            echo "⚠️ Debug-Output: " . htmlspecialchars($output) . "<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ OpenAI Models Test Fehler: " . $e->getMessage() . "<br>\n";
}

?>
