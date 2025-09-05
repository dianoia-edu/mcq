<?php
/**
 * Setup: Erstelle und verteile API-Konfiguration
 */

session_start();
$_SESSION["teacher"] = true; // Für Admin-Zugriff

echo "<h1>🔧 Setup: OpenAI API-Konfiguration</h1>\n";

// Schritt 1: Erstelle API-Konfiguration im Hauptsystem
echo "<h2>📝 Schritt 1: API-Konfiguration erstellen</h2>\n";

$configDir = __DIR__ . '/config';
$apiConfigPath = $configDir . '/api_config.json';

// Erstelle config-Verzeichnis falls nötig
if (!is_dir($configDir)) {
    mkdir($configDir, 0777, true);
    echo "📁 Config-Verzeichnis erstellt<br>\n";
}

// Standard API-Konfiguration
$apiConfig = [
    "api_key" => "YOUR_OPENAI_API_KEY_HERE",
    "api_base_url" => "https://api.openai.com/v1",
    "default_model" => "gpt-4o-mini",
    "max_tokens" => 4000,
    "timeout" => 30,
    "temperature" => 0.7,
    "created_at" => date('Y-m-d H:i:s'),
    "created_by" => "setup_script"
];

if (!file_exists($apiConfigPath)) {
    if (file_put_contents($apiConfigPath, json_encode($apiConfig, JSON_PRETTY_PRINT))) {
        echo "✅ config/api_config.json erstellt<br>\n";
    } else {
        echo "❌ Fehler beim Erstellen der API-Config<br>\n";
        exit;
    }
} else {
    echo "⚠️ config/api_config.json existiert bereits<br>\n";
    
    // Lade existierende Config
    $existingContent = file_get_contents($apiConfigPath);
    $existingConfig = json_decode($existingContent, true);
    
    if ($existingConfig && isset($existingConfig['api_key'])) {
        // WICHTIG: Verwende IMMER die existierende Konfiguration wenn sie einen API-Key hat
        $apiConfig = $existingConfig;
        echo "📄 Verwende existierende Konfiguration<br>\n";
        echo "🔑 API-Key: " . substr($existingConfig['api_key'], 0, 12) . "... (" . strlen($existingConfig['api_key']) . " Zeichen)<br>\n";
        
        // Aktualisiere nur fehlende Felder, aber NIEMALS den API-Key
        $defaultFields = [
            'api_base_url' => 'https://api.openai.com/v1',
            'default_model' => 'gpt-4o-mini', 
            'max_tokens' => 4000,
            'timeout' => 30,
            'temperature' => 0.7
        ];
        
        $updated = false;
        foreach ($defaultFields as $field => $defaultValue) {
            if (!isset($apiConfig[$field])) {
                $apiConfig[$field] = $defaultValue;
                $updated = true;
            }
        }
        
        if ($updated) {
            // Speichere nur wenn neue Felder hinzugefügt wurden
            file_put_contents($apiConfigPath, json_encode($apiConfig, JSON_PRETTY_PRINT));
            echo "✅ Konfiguration erweitert (API-Key beibehalten)<br>\n";
        }
    } else {
        echo "⚠️ Existierende Datei hat keinen API-Key - wird überschrieben<br>\n";
        // Nur überschreiben wenn kein API-Key vorhanden
        file_put_contents($apiConfigPath, json_encode($apiConfig, JSON_PRETTY_PRINT));
    }
}

// Auch im includes/config erstellen (für Kompatibilität)
$includesConfigDir = __DIR__ . '/includes/config';
$includesApiConfigPath = $includesConfigDir . '/api_config.json';

if (!is_dir($includesConfigDir)) {
    mkdir($includesConfigDir, 0777, true);
    echo "📁 includes/config-Verzeichnis erstellt<br>\n";
}

// Kopiere nur wenn die Zieldatei nicht existiert oder älter ist
$shouldCopy = false;
if (!file_exists($includesApiConfigPath)) {
    $shouldCopy = true;
} else {
    $mainModified = filemtime($apiConfigPath);
    $includesModified = filemtime($includesApiConfigPath);
    if ($mainModified > $includesModified) {
        $shouldCopy = true;
    }
}

if ($shouldCopy) {
    copy($apiConfigPath, $includesApiConfigPath);
    echo "✅ includes/config/api_config.json aktualisiert<br>\n";
} else {
    echo "📄 includes/config/api_config.json ist aktuell<br>\n";
}

echo "<h2>🔍 Schritt 2: API-Key Status prüfen</h2>\n";

$needsApiKey = false;
if (isset($apiConfig['api_key']) && $apiConfig['api_key'] === 'YOUR_OPENAI_API_KEY_HERE') {
    echo "⚠️ <strong>API-Key muss noch gesetzt werden!</strong><br>\n";
    echo "📝 Editiere <code>config/api_config.json</code> und setze deinen OpenAI API-Key<br>\n";
    $needsApiKey = true;
} else if (isset($apiConfig['api_key']) && strlen($apiConfig['api_key']) > 20) {
    echo "✅ API-Key ist gesetzt<br>\n";
    
    // Teste API-Key
    echo "🧪 Teste API-Verbindung...<br>\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.openai.com/v1/models',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiConfig['api_key'],
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ CURL Fehler: $error<br>\n";
    } elseif ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['data'])) {
            echo "✅ <strong>API-Key funktioniert!</strong> (" . count($data['data']) . " Modelle verfügbar)<br>\n";
        } else {
            echo "⚠️ API antwortet, aber unerwartetes Format<br>\n";
        }
    } elseif ($httpCode === 401) {
        echo "❌ <strong>API-Key ungültig!</strong> (HTTP 401)<br>\n";
        $needsApiKey = true;
    } else {
        echo "⚠️ API-Fehler: HTTP $httpCode<br>\n";
    }
} else {
    echo "❌ API-Key fehlt oder zu kurz<br>\n";
    $needsApiKey = true;
}

echo "<h2>📦 Schritt 3: Verteilung an Instanzen</h2>\n";

if ($needsApiKey) {
    echo "⏭️ Übersprungen - API-Key muss zuerst gesetzt werden<br>\n";
    echo "<br><h2>🔧 Nächste Schritte:</h2>\n";
    echo "1. <strong>Editiere</strong> <code>config/api_config.json</code><br>\n";
    echo "2. <strong>Setze</strong> deinen OpenAI API-Key ein<br>\n";
    echo "3. <strong>Führe dieses Script nochmal aus</strong> zur Verteilung<br>\n";
    echo "<br>💡 <strong>Hinweis:</strong> Du bekommst deinen API-Key von <a href='https://platform.openai.com/api-keys' target='_blank'>OpenAI Platform</a><br>\n";
} else {
    // Verteile an alle Instanzen
    $instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
    
    if (is_dir($instancesPath)) {
        $instances = array_filter(scandir($instancesPath), function($item) use ($instancesPath) {
            return $item !== '.' && $item !== '..' && is_dir($instancesPath . $item);
        });
        
        $updated = 0;
        $errors = 0;
        
        foreach ($instances as $instance) {
            $instanceMcqPath = $instancesPath . $instance . '/mcq-test-system';
            
            if (is_dir($instanceMcqPath)) {
                // Erstelle config-Verzeichnisse
                $targetDirs = [
                    $instanceMcqPath . '/config',
                    $instanceMcqPath . '/includes/config'
                ];
                
                foreach ($targetDirs as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                }
                
                // Kopiere API-Config
                $targets = [
                    $instanceMcqPath . '/config/api_config.json',
                    $instanceMcqPath . '/includes/config/api_config.json'
                ];
                
                $instanceSuccess = true;
                foreach ($targets as $target) {
                    if (!copy($apiConfigPath, $target)) {
                        $instanceSuccess = false;
                    }
                }
                
                if ($instanceSuccess) {
                    echo "✅ $instance<br>\n";
                    $updated++;
                } else {
                    echo "❌ $instance (Kopierfehler)<br>\n";
                    $errors++;
                }
            } else {
                echo "⚠️ $instance (MCQ-System nicht gefunden)<br>\n";
                $errors++;
            }
        }
        
        echo "<br><strong>📊 Zusammenfassung:</strong><br>\n";
        echo "✅ Erfolgreich: $updated Instanzen<br>\n";
        echo "❌ Fehler: $errors<br>\n";
        
        if ($updated > 0) {
            echo "<br><h2>🎉 Setup abgeschlossen!</h2>\n";
            echo "Die OpenAI API-Konfiguration wurde erfolgreich verteilt.<br>\n";
            echo "<br><strong>🧪 Teste jetzt:</strong><br>\n";
            
            foreach (array_slice($instances, 0, 3) as $instance) {
                echo "🔗 <a href='/lehrer_instanzen/$instance/mcq-test-system/teacher/teacher_dashboard.php' target='_blank'>$instance - Test-Generator</a><br>\n";
            }
            
            echo "<br>💡 Die Modell-Auswahl sollte jetzt funktionieren und aktuelle OpenAI-Modelle anzeigen.<br>\n";
        }
        
    } else {
        echo "❌ Instanzen-Verzeichnis nicht gefunden: $instancesPath<br>\n";
    }
}

echo "<h2>📄 Aktuelle Konfiguration</h2>\n";
echo "<details><summary>Zeige config/api_config.json</summary>\n";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd; font-size: 12px;'>";
echo htmlspecialchars(file_get_contents($apiConfigPath));
echo "</pre></details>\n";

?>
