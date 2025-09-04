<?php
/**
 * Debug-Script für Testgenerator-Probleme in Lehrerinstanzen
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug: Testgenerator Problem</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <h1>🔍 Debug: Testgenerator Problem</h1>
    <p><strong>Aktueller Pfad:</strong> <?php echo __DIR__; ?></p>
    <p><strong>Server URL:</strong> <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
    
    <?php
    $errors = [];
    $warnings = [];
    $info = [];
    
    // 1. Prüfe ob wir in einer Instanz sind
    echo '<div class="debug-section info">';
    echo '<h2>📍 Instanz-Erkennung</h2>';
    $isInstance = strpos(__DIR__, 'lehrer_instanzen') !== false;
    echo '<p><strong>In Lehrerinstanz:</strong> ' . ($isInstance ? 'JA' : 'NEIN') . '</p>';
    echo '<p><strong>Erkannt durch:</strong> ' . ($isInstance ? 'Pfad enthält "lehrer_instanzen"' : 'Normaler Pfad') . '</p>';
    echo '</div>';
    
    // 2. Prüfe wichtige Dateien
    echo '<div class="debug-section">';
    echo '<h2>📁 Datei-Verfügbarkeit</h2>';
    
    $criticalFiles = [
        'js/main.js' => 'JavaScript Hauptdatei',
        'teacher/generate_test.php' => 'Test-Generator Backend',
        'includes/teacher_dashboard/test_generator_view.php' => 'Test-Generator View',
        'includes/database_config.php' => 'Datenbank-Konfiguration',
        'teacher/teacher_dashboard.php' => 'Teacher Dashboard'
    ];
    
    echo '<table>';
    echo '<tr><th>Datei</th><th>Status</th><th>Beschreibung</th><th>Dateigröße</th></tr>';
    
    foreach ($criticalFiles as $file => $description) {
        $path = __DIR__ . '/' . $file;
        $exists = file_exists($path);
        $size = $exists ? filesize($path) : 0;
        $class = $exists ? 'success' : 'error';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($file) . '</td>';
        echo '<td class="' . $class . '">' . ($exists ? '✅ Vorhanden' : '❌ Fehlt') . '</td>';
        echo '<td>' . htmlspecialchars($description) . '</td>';
        echo '<td>' . ($exists ? number_format($size) . ' Bytes' : '-') . '</td>';
        echo '</tr>';
        
        if (!$exists && in_array($file, ['js/main.js', 'teacher/generate_test.php'])) {
            $errors[] = "Kritische Datei fehlt: $file";
        }
    }
    echo '</table>';
    echo '</div>';
    
    // 3. Prüfe JavaScript-Funktionen
    echo '<div class="debug-section">';
    echo '<h2>🔧 JavaScript-Test</h2>';
    echo '<p>Teste, ob die JavaScript-Funktionen korrekt geladen werden:</p>';
    ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            console.log('✅ jQuery geladen');
            
            // Teste ob main.js geladen wurde
            if (typeof getTeacherUrl === 'function') {
                console.log('✅ main.js geladen - getTeacherUrl verfügbar');
                
                // Teste die Funktion
                const testUrl = getTeacherUrl('generate_test.php');
                console.log('🔍 getTeacherUrl Test:', testUrl);
                
                document.getElementById('js-test-result').innerHTML = 
                    '<div class="success">✅ JavaScript-Funktionen verfügbar<br>' +
                    'getTeacherUrl("generate_test.php") = "' + testUrl + '"</div>';
            } else {
                console.error('❌ main.js nicht geladen oder getTeacherUrl nicht verfügbar');
                document.getElementById('js-test-result').innerHTML = 
                    '<div class="error">❌ JavaScript-Funktionen nicht verfügbar</div>';
            }
            
            // Teste AJAX-Pfade
            const pathTests = [
                { name: 'Teacher URL', func: () => getTeacherUrl('generate_test.php') },
                { name: 'Includes URL', func: () => getIncludesUrl('teacher_dashboard/test_generator_view.php') }
            ];
            
            let pathTestHtml = '<h3>URL-Funktionen Test:</h3><ul>';
            pathTests.forEach(test => {
                try {
                    const result = test.func();
                    pathTestHtml += `<li><strong>${test.name}:</strong> ${result}</li>`;
                } catch (e) {
                    pathTestHtml += `<li><strong>${test.name}:</strong> ❌ Fehler: ${e.message}</li>`;
                }
            });
            pathTestHtml += '</ul>';
            
            document.getElementById('path-test-result').innerHTML = pathTestHtml;
        });
    </script>
    
    <div id="js-test-result">⏳ Teste JavaScript...</div>
    <div id="path-test-result"></div>
    
    <?php if (file_exists(__DIR__ . '/js/main.js')): ?>
        <script src="js/main.js"></script>
    <?php else: ?>
        <div class="error">❌ main.js konnte nicht geladen werden</div>
    <?php endif; ?>
    
    <?php
    echo '</div>';
    
    // 4. Prüfe Netzwerk-Zugriff
    echo '<div class="debug-section">';
    echo '<h2>🌐 Netzwerk-Test</h2>';
    echo '<p>Teste, ob die Backend-Dateien erreichbar sind:</p>';
    
    $testUrls = [
        'teacher/generate_test.php' => 'POST-Request Test'
    ];
    
    foreach ($testUrls as $url => $description) {
        $fullUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $url;
        echo '<p><strong>' . $description . ':</strong> <a href="' . $fullUrl . '" target="_blank">' . $fullUrl . '</a></p>';
    }
    echo '</div>';
    
    // 5. Console-Log Test
    echo '<div class="debug-section">';
    echo '<h2>📱 Browser-Console Test</h2>';
    echo '<p>Öffnen Sie die Browser-Entwicklertools (F12) und prüfen Sie die Console auf Fehler.</p>';
    echo '<button onclick="testAjaxCall()">🧪 AJAX-Call testen</button>';
    echo '<div id="ajax-result" style="margin-top: 10px;"></div>';
    ?>
    
    <script>
        function testAjaxCall() {
            console.log('🧪 Starte AJAX-Test...');
            
            const resultDiv = document.getElementById('ajax-result');
            resultDiv.innerHTML = '⏳ Teste AJAX-Call...';
            
            // Prüfe ob getTeacherUrl verfügbar ist
            if (typeof getTeacherUrl !== 'function') {
                resultDiv.innerHTML = '<div class="error">❌ getTeacherUrl Funktion nicht verfügbar</div>';
                return;
            }
            
            const url = getTeacherUrl('generate_test.php');
            console.log('📡 AJAX URL:', url);
            
            // Einfacher Test-Request
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'test=true'
            })
            .then(response => {
                console.log('📡 Response Status:', response.status);
                if (response.ok) {
                    resultDiv.innerHTML = '<div class="success">✅ AJAX-Call erfolgreich (Status: ' + response.status + ')</div>';
                } else {
                    resultDiv.innerHTML = '<div class="warning">⚠️ AJAX-Call mit Status: ' + response.status + '</div>';
                }
                return response.text();
            })
            .then(data => {
                console.log('📡 Response Data:', data.substring(0, 200) + '...');
            })
            .catch(error => {
                console.error('📡 AJAX Error:', error);
                resultDiv.innerHTML = '<div class="error">❌ AJAX-Fehler: ' + error.message + '</div>';
            });
        }
    </script>
    
    <?php
    echo '</div>';
    
    // 6. Zusammenfassung
    echo '<div class="debug-section">';
    echo '<h2>📋 Zusammenfassung</h2>';
    
    if (count($errors) > 0) {
        echo '<div class="error"><h3>❌ Kritische Fehler:</h3><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    
    if (count($warnings) > 0) {
        echo '<div class="warning"><h3>⚠️ Warnungen:</h3><ul>';
        foreach ($warnings as $warning) {
            echo '<li>' . htmlspecialchars($warning) . '</li>';
        }
        echo '</ul></div>';
    }
    
    if (count($errors) === 0) {
        echo '<div class="success"><h3>✅ Keine kritischen Fehler gefunden</h3>';
        echo '<p>Das Problem könnte in folgenden Bereichen liegen:</p>';
        echo '<ul>';
        echo '<li>Browser-Console-Fehler (F12 öffnen und prüfen)</li>';
        echo '<li>JavaScript wird blockiert oder nicht geladen</li>';
        echo '<li>AJAX-Requests schlagen fehl</li>';
        echo '<li>Server-seitige PHP-Fehler</li>';
        echo '</ul></div>';
    }
    
    echo '</div>';
    ?>
    
    <div class="debug-section info">
        <h2>🔧 Nächste Schritte</h2>
        <ol>
            <li>Öffnen Sie die Browser-Entwicklertools (F12)</li>
            <li>Gehen Sie zum Tab "Console"</li>
            <li>Laden Sie den Testgenerator neu</li>
            <li>Klicken Sie auf "Test generieren"</li>
            <li>Prüfen Sie alle Fehlermeldungen in der Console</li>
            <li>Testen Sie den AJAX-Call oben</li>
        </ol>
        
        <p><strong>Häufige Lösungen:</strong></p>
        <ul>
            <li>Browser-Cache leeren (Strg+F5)</li>
            <li>JavaScript aktivieren</li>
            <li>Popup-Blocker deaktivieren</li>
            <li>Andere Browser testen</li>
        </ul>
    </div>
    
</body>
</html>
