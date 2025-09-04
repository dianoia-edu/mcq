<?php
/**
 * Debug-Script für Live-Server
 */

// Sicherheitscheck
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'debug_live_2024') {
    die('Debug-Zugriff verweigert.');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Live-Server Debug</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 10px; }
        .error { background: #ffebee; border-color: #f44336; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .info { background: #e3f2fd; border-color: #2196f3; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Live-Server Debug für update_instances.php</h1>
    
    <?php
    
    echo '<div class="section info">';
    echo '<h2>1. Umgebungsinformationen</h2>';
    echo '<p><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] . '</p>';
    echo '<p><strong>Script-Pfad:</strong> ' . __FILE__ . '</p>';
    echo '<p><strong>Working Directory:</strong> ' . getcwd() . '</p>';
    echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
    echo '</div>';
    
    // Teste AJAX-Aufruf
    echo '<div class="section">';
    echo '<h2>2. AJAX-Test</h2>';
    echo '<button onclick="testAjaxUpdate()">🧪 AJAX Update testen</button>';
    echo '<div id="ajaxResult" style="margin-top: 10px;"></div>';
    echo '</div>';
    
    // Teste Pfade
    echo '<div class="section">';
    echo '<h2>3. Pfad-Analyse</h2>';
    
    $instancesBasePath = dirname(__DIR__) . '/lehrer_instanzen/';
    $sourceBasePath = __DIR__;
    
    echo '<p><strong>Instanzen-Basispfad:</strong> ' . $instancesBasePath . '</p>';
    echo '<p><strong>Existiert:</strong> ' . (is_dir($instancesBasePath) ? '✅ JA' : '❌ NEIN') . '</p>';
    
    if (is_dir($instancesBasePath)) {
        echo '<p><strong>Verzeichnis-Inhalt:</strong></p>';
        echo '<pre>';
        $dirs = scandir($instancesBasePath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $instancePath = $instancesBasePath . $dir;
            $isMcqInstance = is_dir($instancePath . '/mcq-test-system');
            echo "📁 $dir " . ($isMcqInstance ? '✅ (MCQ System)' : '❌ (Kein MCQ)') . "\n";
        }
        echo '</pre>';
    }
    echo '</div>';
    
    // Teste update_instances.php direkt
    echo '<div class="section">';
    echo '<h2>4. Direct Script Test</h2>';
    echo '<p><a href="update_instances.php?admin_key=update_instances_2024" target="_blank">🔗 Normale HTML-Ansicht</a></p>';
    echo '<p><a href="update_instances.php?admin_key=update_instances_2024&ajax=true" target="_blank">🔗 AJAX-Ansicht (JSON)</a></p>';
    echo '</div>';
    
    // Teste Error Logs
    echo '<div class="section">';
    echo '<h2>5. Error Logs</h2>';
    $errorLog = ini_get('error_log');
    echo '<p><strong>Error Log Datei:</strong> ' . ($errorLog ?: 'Standard') . '</p>';
    
    // Versuche letzten Error aus PHP Error Log zu lesen
    if (function_exists('error_get_last')) {
        $lastError = error_get_last();
        if ($lastError) {
            echo '<p><strong>Letzter PHP-Fehler:</strong></p>';
            echo '<pre>' . print_r($lastError, true) . '</pre>';
        }
    }
    echo '</div>';
    
    ?>
    
    <script>
    function testAjaxUpdate() {
        const resultDiv = document.getElementById('ajaxResult');
        resultDiv.innerHTML = '<p>🔄 Teste AJAX-Aufruf...</p>';
        
        fetch('update_instances.php?admin_key=update_instances_2024&ajax=true')
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                
                resultDiv.innerHTML = `
                    <div class="success">
                        <h3>✅ AJAX-Aufruf erfolgreich</h3>
                        <p><strong>Response Länge:</strong> ${text.length} Zeichen</p>
                        <p><strong>Erste 500 Zeichen:</strong></p>
                        <pre>${text.substring(0, 500)}</pre>
                        
                        <h4>JSON Parse Test:</h4>
                `;
                
                try {
                    const json = JSON.parse(text);
                    resultDiv.innerHTML += `
                        <p style="color: green;">✅ JSON Parse erfolgreich!</p>
                        <pre>${JSON.stringify(json, null, 2)}</pre>
                    `;
                } catch (e) {
                    resultDiv.innerHTML += `
                        <p style="color: red;">❌ JSON Parse Fehler: ${e.message}</p>
                        <p><strong>Vollständiger Response:</strong></p>
                        <pre style="max-height: 300px; overflow-y: auto;">${text}</pre>
                    `;
                }
                
                resultDiv.innerHTML += '</div>';
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h3>❌ AJAX-Fehler</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            });
    }
    </script>
    
</body>
</html>
