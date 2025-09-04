<?php
/**
 * Debug Test-Generator Fehler
 */

if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'generator_debug_2024') {
    die('Debug-Zugriff verweigert.');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Test-Generator Debug</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>🔍 Test-Generator Debug</h1>
    
    <div class="section">
        <h2>1. Test-Generator Direkttest</h2>
        <p>Simuliert einen Test-Generator Aufruf mit Debug-Daten</p>
        <button onclick="testGenerator()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">🧪 Teste generate_test.php</button>
        <div id="generatorResult" style="margin-top: 15px;"></div>
    </div>
    
    <div class="section">
        <h2>2. Pfad-Analyse für Generator</h2>
        <?php
        
        // Teste ob generate_test.php in Instanzen existiert
        $testInstance = 'test10';
        $instancePath = "/var/www/dianoia-ai.de/lehrer_instanzen/$testInstance/mcq-test-system";
        
        $testPaths = [
            'generate_test.php (teacher)' => "$instancePath/teacher/generate_test.php",
            'generate_test.php (root)' => "$instancePath/generate_test.php",
            'config directory' => "$instancePath/config/",
            'includes directory' => "$instancePath/includes/",
            'openai_api.php' => "$instancePath/includes/openai_api.php"
        ];
        
        echo "<p><strong>Test-Instanz:</strong> $testInstance</p>";
        echo "<p><strong>Pfade-Check:</strong></p>";
        echo "<ul>";
        
        foreach ($testPaths as $label => $path) {
            $exists = file_exists($path);
            echo "<li><strong>$label:</strong> ";
            echo "<code>$path</code> ";
            echo $exists ? "✅" : "❌";
            
            if ($exists && is_file($path)) {
                $size = filesize($path);
                echo " ($size bytes)";
            }
            
            echo "</li>";
        }
        echo "</ul>";
        
        ?>
    </div>
    
    <div class="section">
        <h2>3. Konfiguration prüfen</h2>
        <?php
        
        $configPath = "/var/www/dianoia-ai.de/lehrer_instanzen/$testInstance/mcq-test-system/config/app_config.json";
        
        if (file_exists($configPath)) {
            echo "<p>✅ Konfigurationsdatei gefunden</p>";
            
            try {
                $config = json_decode(file_get_contents($configPath), true);
                
                $requiredKeys = ['api_key', 'api_base_url', 'admin_access_code'];
                
                echo "<p><strong>Konfiguration:</strong></p>";
                echo "<ul>";
                foreach ($requiredKeys as $key) {
                    $exists = isset($config[$key]);
                    $value = $exists ? $config[$key] : 'fehlt';
                    
                    echo "<li><strong>$key:</strong> ";
                    if ($exists) {
                        if ($key === 'api_key') {
                            echo "✅ (***" . substr($value, -4) . ")";
                        } else {
                            echo "✅ " . htmlspecialchars($value);
                        }
                    } else {
                        echo "❌ nicht vorhanden";
                    }
                    echo "</li>";
                }
                echo "</ul>";
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<p>❌ Fehler beim Lesen der Konfiguration: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<p>❌ Konfigurationsdatei nicht gefunden: <code>$configPath</code></p>";
            echo "</div>";
        }
        
        ?>
    </div>
    
    <script>
    function testGenerator() {
        const resultDiv = document.getElementById('generatorResult');
        resultDiv.innerHTML = '<p>🔄 Teste Test-Generator...</p>';
        
        // Simuliere einen Test-Generator Aufruf
        const testData = {
            question_count: 5,
            answer_count: 4,
            answer_type: 'single',
            webpage_url: 'https://de.wikipedia.org/wiki/QR-Code',
            ai_model: 'auto'
        };
        
        fetch('teacher/generate_test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(testData)
        })
        .then(response => {
            console.log('Generator Response Status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Generator Raw Response:', text);
            
            let content = `
                <div class="${text.includes('error') || text.includes('Fehler') ? 'error' : 'success'}">
                    <h3>${text.includes('error') || text.includes('Fehler') ? '❌' : '✅'} Generator Response</h3>
                    <p><strong>Response Länge:</strong> ${text.length} Zeichen</p>
            `;
            
            // Versuche JSON zu parsen
            try {
                const json = JSON.parse(text);
                content += `
                    <p style="color: green;"><strong>JSON Parse:</strong> ✅ Erfolgreich</p>
                    <p><strong>Success:</strong> ${json.success}</p>
                    <p><strong>Error:</strong> ${json.error || 'keine'}</p>
                `;
                
                if (json.debug) {
                    content += `<p><strong>Debug Info:</strong> ${json.debug}</p>`;
                }
                
                content += `
                    <details>
                        <summary>Vollständige JSON-Antwort</summary>
                        <pre>${JSON.stringify(json, null, 2)}</pre>
                    </details>
                `;
            } catch (e) {
                content += `
                    <p style="color: red;"><strong>JSON Parse:</strong> ❌ Fehler: ${e.message}</p>
                    <p>Das bedeutet wahrscheinlich einen PHP-Fehler oder HTML-Output vor dem JSON.</p>
                    <details>
                        <summary>Rohe Generator-Antwort</summary>
                        <pre>${text}</pre>
                    </details>
                `;
            }
            
            content += '</div>';
            resultDiv.innerHTML = content;
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="error">
                    <h3>❌ Generator-Fehler</h3>
                    <p>${error.message}</p>
                    <p>Möglicherweise ist das generate_test.php Script nicht vorhanden oder hat einen Fehler.</p>
                </div>
            `;
        });
    }
    </script>
    
</body>
</html>
