<?php
/**
 * Debug Admin-Login in Instanzen
 */

if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'login_debug_2024') {
    die('Debug-Zugriff verweigert.');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin-Login Debug</title>
    <style>
        body { font-family: monospace; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap; max-height: 300px; overflow-y: auto; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Admin-Login Debug</h1>
    
    <div class="section">
        <h2>1. Admin-Codes in allen Instanzen</h2>
        <?php
        
        $instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
        
        if (is_dir($instancesPath)) {
            $dirs = scandir($instancesPath);
            
            echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
            echo "<tr><th>Instanz</th><th>Config-Datei</th><th>Admin-Code</th><th>Status</th></tr>";
            
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                
                $instancePath = $instancesPath . $dir;
                $mcqPath = $instancePath . '/mcq-test-system';
                $configPath = $mcqPath . '/config/app_config.json';
                
                if (!is_dir($mcqPath)) continue;
                
                echo "<tr>";
                echo "<td><strong>$dir</strong></td>";
                
                if (file_exists($configPath)) {
                    echo "<td>✅ Vorhanden</td>";
                    
                    try {
                        $config = json_decode(file_get_contents($configPath), true);
                        
                        if (isset($config['admin_access_code'])) {
                            $adminCode = $config['admin_access_code'];
                            echo "<td><code>$adminCode</code></td>";
                            echo "<td>✅ OK</td>";
                        } else {
                            echo "<td>❌ Nicht gefunden</td>";
                            echo "<td>❌ Fehlt</td>";
                        }
                    } catch (Exception $e) {
                        echo "<td>❌ JSON-Fehler</td>";
                        echo "<td>❌ " . htmlspecialchars($e->getMessage()) . "</td>";
                    }
                } else {
                    echo "<td>❌ Nicht vorhanden</td>";
                    echo "<td>❌ Config fehlt</td>";
                    echo "<td>❌ Keine Config</td>";
                }
                
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='error'>Instanzen-Verzeichnis nicht gefunden</div>";
        }
        
        ?>
    </div>
    
    <div class="section">
        <h2>2. Login-Validierung testen</h2>
        <p>Testen Sie einen Admin-Code direkt:</p>
        
        <div style="margin: 10px 0;">
            <label>Instanz-Name:</label>
            <input type="text" id="instanceName" placeholder="z.B. test10" style="margin: 0 10px; padding: 5px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label>Admin-Code:</label>
            <input type="text" id="adminCode" placeholder="z.B. admin_test10" style="margin: 0 10px; padding: 5px;">
        </div>
        
        <button class="btn btn-primary" onclick="testLogin()">🧪 Login testen</button>
        
        <div id="loginResult" style="margin-top: 15px;"></div>
    </div>
    
    <div class="section">
        <h2>3. Index.php Login-Code prüfen</h2>
        <?php
        
        // Prüfe eine Test-Instanz
        $testInstance = 'test10';
        $indexPath = "/var/www/dianoia-ai.de/lehrer_instanzen/$testInstance/mcq-test-system/index.php";
        
        echo "<p><strong>Test-Instanz:</strong> $testInstance</p>";
        echo "<p><strong>Index.php Pfad:</strong> <code>$indexPath</code></p>";
        
        if (file_exists($indexPath)) {
            echo "<p>✅ Index.php existiert</p>";
            
            // Suche nach Admin-Code Validierung im index.php
            $content = file_get_contents($indexPath);
            
            if (strpos($content, 'admin_access_code') !== false) {
                echo "<p>✅ Admin-Code Validierung gefunden</p>";
                
                // Zeige relevante Code-Abschnitte
                $lines = explode("\n", $content);
                $relevantLines = [];
                
                foreach ($lines as $i => $line) {
                    if (stripos($line, 'admin') !== false || 
                        stripos($line, 'access_code') !== false ||
                        stripos($line, 'teacher') !== false) {
                        $relevantLines[] = ($i + 1) . ": " . htmlspecialchars($line);
                    }
                }
                
                if (!empty($relevantLines)) {
                    echo "<p><strong>Relevante Code-Zeilen:</strong></p>";
                    echo "<pre>" . implode("\n", array_slice($relevantLines, 0, 20)) . "</pre>";
                }
            } else {
                echo "<p>❌ Keine Admin-Code Validierung gefunden</p>";
            }
        } else {
            echo "<p>❌ Index.php nicht gefunden</p>";
        }
        
        ?>
    </div>
    
    <script>
    function testLogin() {
        const instanceName = document.getElementById('instanceName').value;
        const adminCode = document.getElementById('adminCode').value;
        const resultDiv = document.getElementById('loginResult');
        
        if (!instanceName || !adminCode) {
            resultDiv.innerHTML = '<div class="error">Bitte beide Felder ausfüllen</div>';
            return;
        }
        
        resultDiv.innerHTML = '<p>🔄 Teste Login...</p>';
        
        // Simuliere einen Login-Versuch durch direkten Config-Vergleich
        fetch(`?debug_key=login_debug_2024&test_login=1&instance=${encodeURIComponent(instanceName)}&code=${encodeURIComponent(adminCode)}`)
            .then(response => response.text())
            .then(result => {
                resultDiv.innerHTML = result;
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="error">Fehler: ${error.message}</div>`;
            });
    }
    </script>
    
    <?php
    
    // Login-Test Handler
    if (isset($_GET['test_login']) && $_GET['test_login'] == '1') {
        $instanceName = $_GET['instance'] ?? '';
        $testCode = $_GET['code'] ?? '';
        
        if ($instanceName && $testCode) {
            $configPath = "/var/www/dianoia-ai.de/lehrer_instanzen/$instanceName/mcq-test-system/config/app_config.json";
            
            if (file_exists($configPath)) {
                try {
                    $config = json_decode(file_get_contents($configPath), true);
                    $storedCode = $config['admin_access_code'] ?? '';
                    
                    if ($storedCode === $testCode) {
                        echo "<div class='success'>✅ <strong>Login erfolgreich!</strong><br>";
                        echo "Code '$testCode' stimmt mit gespeichertem Code überein.</div>";
                    } else {
                        echo "<div class='error'>❌ <strong>Login fehlgeschlagen!</strong><br>";
                        echo "Eingegeben: <code>$testCode</code><br>";
                        echo "Gespeichert: <code>$storedCode</code><br>";
                        echo "Codes stimmen nicht überein.</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Config-Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            } else {
                echo "<div class='error'>❌ Config-Datei nicht gefunden: <code>$configPath</code></div>";
            }
        }
        exit;
    }
    
    ?>
    
</body>
</html>
