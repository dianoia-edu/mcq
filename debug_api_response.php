<?php
/**
 * Debug API Response für Admin-Codes
 */

if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'api_debug_2024') {
    die('Debug-Zugriff verweigert.');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>API Response Debug</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>🔍 API Response Debug</h1>
    
    <div class="section">
        <h2>1. Direkter API-Test</h2>
        <button onclick="testAPI()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">🧪 Teste get_instances.php</button>
        <div id="apiResult" style="margin-top: 15px;"></div>
    </div>
    
    <div class="section">
        <h2>2. Manuelle Pfad-Tests</h2>
        <?php
        
        // Teste manuell eine Instanz
        $testInstance = 'test10';
        $instancePath = "/var/www/dianoia-ai.de/lehrer_instanzen/$testInstance";
        $mcqPath = "$instancePath/mcq-test-system";
        $configPath = "$mcqPath/config/app_config.json";
        
        echo "<p><strong>Test-Instanz:</strong> $testInstance</p>";
        echo "<p><strong>Config-Pfad:</strong> <code>$configPath</code></p>";
        
        if (file_exists($configPath)) {
            echo "<p>✅ Config-Datei existiert</p>";
            
            try {
                $content = file_get_contents($configPath);
                $data = json_decode($content, true);
                
                if ($data) {
                    echo "<p>✅ JSON Parse erfolgreich</p>";
                    echo "<p><strong>Verfügbare Keys:</strong> " . implode(', ', array_keys($data)) . "</p>";
                    
                    if (isset($data['admin_access_code'])) {
                        echo "<div class='success'>";
                        echo "<p>🎯 <strong>Admin-Code GEFUNDEN:</strong> <code>" . htmlspecialchars($data['admin_access_code']) . "</code></p>";
                        echo "</div>";
                    } else {
                        echo "<div class='error'>";
                        echo "<p>❌ Kein 'admin_access_code' Key gefunden</p>";
                        echo "</div>";
                    }
                    
                    echo "<details><summary>Vollständiger JSON-Inhalt</summary>";
                    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                    echo "</details>";
                } else {
                    echo "<div class='error'>";
                    echo "<p>❌ JSON Parse fehlgeschlagen</p>";
                    echo "<p><strong>Roher Inhalt:</strong></p>";
                    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<p>❌ Fehler beim Lesen: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<p>❌ Config-Datei nicht gefunden</p>";
            echo "</div>";
        }
        
        ?>
    </div>
    
    <div class="section">
        <h2>3. API Include Test</h2>
        <?php
        
        // Teste ob die API-Datei die richtigen Pfade verwendet
        echo "<p>Teste ob get_instances.php die korrekten Pfade verwendet...</p>";
        
        // Simuliere den API-Code
        function testGetInstanceInfo($instanceName) {
            $instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
            $instancePath = $instancesBasePath . $instanceName;
            $mcqPath = $instancePath . '/mcq-test-system';
            
            $info = [
                'name' => $instanceName,
                'admin_code' => 'Unbekannt'
            ];
            
            // Test die Pfad-Logik aus der API
            $possibleConfigFiles = [
                $mcqPath . '/config/app_config.json',
                $mcqPath . '/includes/config/api_config.json',
                $mcqPath . '/includes/config/app_config.php', 
                $mcqPath . '/config/api_config.json',
                $instancePath . '/config/api_config.json'
            ];
            
            echo "<p><strong>Instanz:</strong> $instanceName</p>";
            echo "<p><strong>Getestete Pfade:</strong></p>";
            echo "<ul>";
            
            foreach ($possibleConfigFiles as $configFile) {
                $exists = file_exists($configFile);
                echo "<li>";
                echo "<code>" . htmlspecialchars($configFile) . "</code> ";
                echo $exists ? "✅" : "❌";
                
                if ($exists) {
                    try {
                        if (str_ends_with($configFile, '.json')) {
                            $configData = json_decode(file_get_contents($configFile), true);
                            if (isset($configData['admin_access_code'])) {
                                $info['admin_code'] = $configData['admin_access_code'];
                                echo " → <strong style='color: green;'>Admin-Code: " . htmlspecialchars($configData['admin_access_code']) . "</strong>";
                                break;
                            } else {
                                echo " → Kein admin_access_code";
                            }
                        }
                    } catch (Exception $e) {
                        echo " → Fehler: " . htmlspecialchars($e->getMessage());
                    }
                }
                echo "</li>";
            }
            echo "</ul>";
            
            return $info;
        }
        
        $result = testGetInstanceInfo('test10');
        
        echo "<div class='" . ($result['admin_code'] !== 'Unbekannt' ? 'success' : 'error') . "'>";
        echo "<p><strong>Endergebnis:</strong> Admin-Code = <code>" . htmlspecialchars($result['admin_code']) . "</code></p>";
        echo "</div>";
        
        ?>
    </div>
    
    <script>
    function testAPI() {
        const resultDiv = document.getElementById('apiResult');
        resultDiv.innerHTML = '<p>🔄 Teste API...</p>';
        
        fetch('includes/teacher_dashboard/get_instances.php')
            .then(response => response.text())
            .then(text => {
                console.log('Raw API response:', text);
                
                let content = `
                    <div class="success">
                        <h3>✅ API-Aufruf erfolgreich</h3>
                        <p><strong>Response Länge:</strong> ${text.length} Zeichen</p>
                `;
                
                try {
                    const json = JSON.parse(text);
                    content += `
                        <p style="color: green;"><strong>JSON Parse:</strong> ✅ Erfolgreich</p>
                        <p><strong>Success:</strong> ${json.success}</p>
                        <p><strong>Instanzen gefunden:</strong> ${json.instances ? json.instances.length : 'undefined'}</p>
                    `;
                    
                    if (json.instances && json.instances.length > 0) {
                        content += '<h4>Admin-Codes der ersten 3 Instanzen:</h4><ul>';
                        json.instances.slice(0, 3).forEach(instance => {
                            content += `<li><strong>${instance.name}:</strong> <code>${instance.admin_code}</code></li>`;
                        });
                        content += '</ul>';
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
                        <details>
                            <summary>Rohe API-Antwort</summary>
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
                        <h3>❌ API-Fehler</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            });
    }
    </script>
    
</body>
</html>
