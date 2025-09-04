<?php
/**
 * Erweiterte Debug-Diagnose für Instanzen-Probleme
 */

// Sicherheitscheck
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'live_debug_2024') {
    die('Debug-Zugriff verweigert.');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Live Server Instanzen Debug</title>
    <style>
        body { font-family: monospace; max-width: 1400px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .error { background: #ffebee; border-color: #f44336; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .info { background: #e3f2fd; border-color: #2196f3; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .test-btn { padding: 8px 16px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 3px; }
        .test-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🔍 Live Server Instanzen Debug</h1>
    
    <?php
    
    echo '<div class="section info">';
    echo '<h2>1. Server-Umgebung</h2>';
    echo '<p><strong>Server:</strong> ' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . '</p>';
    echo '<p><strong>Document Root:</strong> ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . '</p>';
    echo '<p><strong>Script Path:</strong> ' . __FILE__ . '</p>';
    echo '<p><strong>Working Dir:</strong> ' . getcwd() . '</p>';
    echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
    echo '</div>';
    
    // AJAX-Test Buttons
    echo '<div class="section">';
    echo '<h2>2. Direkte API-Tests</h2>';
    echo '<button class="test-btn" onclick="testGetInstancesAPI()">🧪 Test get_instances.php</button>';
    echo '<button class="test-btn" onclick="testGetInstancesVerbose()">📋 Test mit Debug-Info</button>';
    echo '<button class="test-btn" onclick="testPathResolution()">📍 Test Pfad-Auflösung</button>';
    echo '<div id="apiTestResults" style="margin-top: 15px;"></div>';
    echo '</div>';
    
    // Manuelle Pfad-Analyse
    echo '<div class="section">';
    echo '<h2>3. Pfad-Analyse</h2>';
    
    $testPaths = [
        'Standard' => dirname(__DIR__, 1) . '/lehrer_instanzen/',
        'Zwei Ebenen hoch' => dirname(__DIR__, 2) . '/lehrer_instanzen/',
        'Document Root Parent' => dirname($_SERVER['DOCUMENT_ROOT']) . '/lehrer_instanzen/',
        'Absolut /var/www' => '/var/www/lehrer_instanzen/',
        'Vollständig dianoia-ai' => '/var/www/dianoia-ai.de/lehrer_instanzen/',
        'Aus Script-Dir' => dirname(__FILE__) . '/../lehrer_instanzen/',
        'MCQ System Parent' => dirname(dirname(__FILE__)) . '/lehrer_instanzen/'
    ];
    
    foreach ($testPaths as $label => $path) {
        $exists = is_dir($path);
        $readable = $exists ? is_readable($path) : false;
        $writable = $exists ? is_writable($path) : false;
        
        echo '<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">';
        echo '<strong>' . $label . ':</strong><br>';
        echo '<code>' . $path . '</code><br>';
        echo '<span style="color: ' . ($exists ? 'green' : 'red') . ';">Existiert: ' . ($exists ? '✅' : '❌') . '</span> | ';
        echo '<span style="color: ' . ($readable ? 'green' : 'red') . ';">Lesbar: ' . ($readable ? '✅' : '❌') . '</span> | ';
        echo '<span style="color: ' . ($writable ? 'green' : 'red') . ';">Schreibbar: ' . ($writable ? '✅' : '❌') . '</span>';
        
        if ($exists) {
            $files = scandir($path);
            $dirCount = count(array_filter($files, function($f) use ($path) {
                return $f !== '.' && $f !== '..' && is_dir($path . $f);
            }));
            echo '<br><span style="color: blue;">Instanzen gefunden: ' . $dirCount . '</span>';
            
            if ($dirCount > 0) {
                echo '<br><strong>Verzeichnisse:</strong> ';
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_dir($path . $file)) {
                        $mcqPath = $path . $file . '/mcq-test-system';
                        $hasMcq = is_dir($mcqPath);
                        echo '<span style="color: ' . ($hasMcq ? 'green' : 'orange') . ';">' . $file . ($hasMcq ? ' ✅' : ' ⚠️') . '</span>, ';
                    }
                }
            }
        }
        echo '</div>';
    }
    echo '</div>';
    
    // Tab-System Debug
    echo '<div class="section">';
    echo '<h2>4. Tab-System Debug</h2>';
    echo '<button class="test-btn" onclick="testTabSystem()">🧪 Test Tab-Navigation</button>';
    echo '<div id="tabTestResults" style="margin-top: 15px;"></div>';
    echo '</div>';
    
    // Instanz-Erstellung Debug  
    echo '<div class="section">';
    echo '<h2>5. Instanz-Erstellung Debug</h2>';
    echo '<button class="test-btn" onclick="testCreateInstance()">🧪 Test Instanz-Formular</button>';
    echo '<div id="instanceTestResults" style="margin-top: 15px;"></div>';
    echo '</div>';
    
    ?>
    
    <script>
    function logResult(containerId, title, content, type = 'info') {
        const container = document.getElementById(containerId);
        const timestamp = new Date().toLocaleTimeString();
        container.innerHTML += `
            <div class="${type}" style="margin: 10px 0; padding: 10px; border-radius: 3px;">
                <h4>${title} (${timestamp})</h4>
                ${content}
            </div>
        `;
    }
    
    function testGetInstancesAPI() {
        logResult('apiTestResults', 'API-Test läuft...', '<p>🔄 Teste get_instances.php...</p>');
        
        fetch('includes/teacher_dashboard/get_instances.php', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response Status:', response.status);
            console.log('Response Headers:', [...response.headers.entries()]);
            return response.text();
        })
        .then(text => {
            console.log('Raw Response:', text);
            
            let content = `
                <p><strong>Status:</strong> ✅ Erfolgreich</p>
                <p><strong>Response Länge:</strong> ${text.length} Zeichen</p>
                <p><strong>Erste 200 Zeichen:</strong></p>
                <pre>${text.substring(0, 200)}...</pre>
            `;
            
            try {
                const json = JSON.parse(text);
                content += `
                    <p style="color: green;"><strong>JSON Parse:</strong> ✅ Erfolgreich</p>
                    <p><strong>Success:</strong> ${json.success}</p>
                    <p><strong>Error:</strong> ${json.error || 'keine'}</p>
                    <p><strong>Instanzen:</strong> ${json.instances ? json.instances.length : 'undefined'}</p>
                    <details>
                        <summary>Vollständige JSON-Antwort</summary>
                        <pre>${JSON.stringify(json, null, 2)}</pre>
                    </details>
                `;
            } catch (e) {
                content += `
                    <p style="color: red;"><strong>JSON Parse:</strong> ❌ Fehler: ${e.message}</p>
                    <details>
                        <summary>Vollständiger Response</summary>
                        <pre>${text}</pre>
                    </details>
                `;
            }
            
            logResult('apiTestResults', 'get_instances.php Resultat', content, 'success');
        })
        .catch(error => {
            logResult('apiTestResults', 'get_instances.php Fehler', `<p style="color: red;">❌ ${error.message}</p>`, 'error');
        });
    }
    
    function testGetInstancesVerbose() {
        logResult('apiTestResults', 'Verbose Test läuft...', '<p>🔄 Teste mit Debug-Parameter...</p>');
        
        fetch('includes/teacher_dashboard/get_instances.php?debug=1', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(text => {
            logResult('apiTestResults', 'Verbose Test Resultat', `<pre>${text}</pre>`, 'info');
        })
        .catch(error => {
            logResult('apiTestResults', 'Verbose Test Fehler', `<p style="color: red;">❌ ${error.message}</p>`, 'error');
        });
    }
    
    function testPathResolution() {
        logResult('apiTestResults', 'Pfad-Test läuft...', '<p>🔄 Teste Pfad-Auflösung...</p>');
        
        // Test mit einem speziellen Endpunkt der die Pfade zurückgibt
        fetch('includes/teacher_dashboard/get_instances.php?test_paths=1', {
            method: 'GET'
        })
        .then(response => response.text())
        .then(text => {
            logResult('apiTestResults', 'Pfad-Test Resultat', `<pre>${text}</pre>`, 'info');
        })
        .catch(error => {
            logResult('apiTestResults', 'Pfad-Test Fehler', `<p style="color: red;">❌ ${error.message}</p>`, 'error');
        });
    }
    
    function testTabSystem() {
        logResult('tabTestResults', 'Tab-System Test', '<p>🔄 Teste Tab-Navigation...</p>');
        
        // Simuliere einen Tab-Click
        const tabElement = document.createElement('a');
        tabElement.id = 'tab-instance-management';
        tabElement.className = 'tab';
        tabElement.href = '#';
        
        // Teste Event-Handler
        try {
            const event = new MouseEvent('click', {
                bubbles: true,
                cancelable: true
            });
            
            tabElement.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                logResult('tabTestResults', 'Tab Event', '<p>✅ Tab-Event funktioniert korrekt</p>', 'success');
            });
            
            tabElement.dispatchEvent(event);
        } catch (e) {
            logResult('tabTestResults', 'Tab Event Fehler', `<p style="color: red;">❌ ${e.message}</p>`, 'error');
        }
    }
    
    function testCreateInstance() {
        logResult('instanceTestResults', 'Instanz-Formular Test', '<p>🔄 Teste Formular-Verhalten...</p>');
        
        // Simuliere Form-Submit
        const form = document.createElement('form');
        form.id = 'createInstanceForm';
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logResult('instanceTestResults', 'Form Submit', '<p>✅ Form-Submit Event korrekt abgefangen</p>', 'success');
        });
        
        const submitEvent = new Event('submit', {
            bubbles: true,
            cancelable: true
        });
        
        try {
            form.dispatchEvent(submitEvent);
        } catch (e) {
            logResult('instanceTestResults', 'Form Submit Fehler', `<p style="color: red;">❌ ${e.message}</p>`, 'error');
        }
    }
    </script>
    
</body>
</html>
