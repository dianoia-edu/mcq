<?php
session_start();

// Prüfe Session (optional für Debug)
$sessionValid = isset($_SESSION['teacher']) && $_SESSION['teacher'] === true;

// Ermittle den korrekten Includes-Pfad
function getIncludesPath($relativePath) {
    if (basename(dirname(__FILE__)) === 'teacher') {
        return dirname(__DIR__) . '/includes/' . $relativePath;
    } else {
        return 'includes/' . $relativePath;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Generator - Direkter Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-panel { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 Test Generator - Direkter Debug</h1>
        
        <div class="debug-panel">
            <h3>📋 System-Status</h3>
            <p><strong>Session gültig:</strong> <span class="<?php echo $sessionValid ? 'status-ok' : 'status-error'; ?>"><?php echo $sessionValid ? 'JA' : 'NEIN'; ?></span></p>
            <p><strong>Aktueller Pfad:</strong> <?php echo __DIR__; ?></p>
            <p><strong>Script-Name:</strong> <?php echo basename(__FILE__); ?></p>
            <p><strong>Server-URL:</strong> <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
            <p><strong>In Teacher-Verzeichnis:</strong> <?php echo basename(dirname(__FILE__)) === 'teacher' ? 'JA' : 'NEIN'; ?></p>
        </div>
        
        <div class="debug-panel">
            <h3>📁 Include-Pfad Test</h3>
            <?php
            $testIncludes = [
                'teacher_dashboard/test_generator_view.php',
                'database_config.php',
                'config/openai_config.php'
            ];
            
            foreach ($testIncludes as $include) {
                $path = getIncludesPath($include);
                $exists = file_exists($path);
                echo '<p><strong>' . $include . ':</strong> ';
                echo '<span class="' . ($exists ? 'status-ok' : 'status-error') . '">';
                echo $exists ? '✅ Gefunden' : '❌ Nicht gefunden';
                echo '</span>';
                echo ' <small>(' . $path . ')</small></p>';
            }
            ?>
        </div>
        
        <div class="debug-panel">
            <h3>🔧 Test Generator Include</h3>
            <?php
            $generatorViewPath = getIncludesPath('teacher_dashboard/test_generator_view.php');
            if (file_exists($generatorViewPath)) {
                echo '<p class="status-ok">✅ test_generator_view.php gefunden, lade Inhalt...</p>';
                echo '<div id="generator-content">';
                try {
                    include($generatorViewPath);
                    echo '</div>';
                    echo '<p class="status-ok">✅ test_generator_view.php erfolgreich geladen!</p>';
                } catch (Exception $e) {
                    echo '</div>';
                    echo '<p class="status-error">❌ Fehler beim Laden: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p class="status-error">❌ test_generator_view.php nicht gefunden: ' . $generatorViewPath . '</p>';
            }
            ?>
        </div>
        
        <div class="debug-panel">
            <h3>📱 JavaScript Test</h3>
            <button class="btn btn-primary" onclick="testJavaScript()">🧪 JavaScript testen</button>
            <div id="js-test-result" class="mt-3"></div>
        </div>
        
        <div class="debug-panel">
            <h3>📋 PHP-Konfiguration</h3>
            <details>
                <summary>PHP-Info anzeigen</summary>
                <pre><?php
                echo "PHP Version: " . PHP_VERSION . "\n";
                echo "Memory Limit: " . ini_get('memory_limit') . "\n";
                echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
                echo "Post Max Size: " . ini_get('post_max_size') . "\n";
                echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
                echo "Error Reporting: " . ini_get('error_reporting') . "\n";
                echo "Display Errors: " . ini_get('display_errors') . "\n";
                ?></pre>
            </details>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Lade main.js falls vorhanden -->
    <?php if (file_exists('js/main.js')): ?>
        <script src="js/main.js"></script>
    <?php elseif (file_exists('../js/main.js')): ?>
        <script src="../js/main.js"></script>
    <?php endif; ?>
    
    <script>
        function testJavaScript() {
            const resultDiv = document.getElementById('js-test-result');
            let results = [];
            
            // Test jQuery
            results.push('<p><strong>jQuery:</strong> ' + (typeof $ !== 'undefined' ? '✅ Geladen (v' + $.fn.jquery + ')' : '❌ Nicht geladen') + '</p>');
            
            // Test main.js Funktionen
            results.push('<p><strong>getTeacherUrl:</strong> ' + (typeof getTeacherUrl === 'function' ? '✅ Verfügbar' : '❌ Nicht verfügbar') + '</p>');
            results.push('<p><strong>getIncludesUrl:</strong> ' + (typeof getIncludesUrl === 'function' ? '✅ Verfügbar' : '❌ Nicht verfügbar') + '</p>');
            
            // Test window.mcqPaths
            results.push('<p><strong>window.mcqPaths:</strong> ' + (window.mcqPaths ? '✅ Verfügbar' : '❌ Nicht verfügbar') + '</p>');
            
            // Test Form
            const form = document.getElementById('uploadForm');
            results.push('<p><strong>uploadForm:</strong> ' + (form ? '✅ Gefunden' : '❌ Nicht gefunden') + '</p>');
            
            if (form) {
                // Test Event-Handler
                const jqEvents = $ && $._data ? $._data(form, 'events') : null;
                results.push('<p><strong>Form Events:</strong> ' + (jqEvents && jqEvents.submit ? '✅ Submit-Handler gefunden' : '❌ Kein Submit-Handler') + '</p>');
                
                // Test URL-Generation
                if (typeof getTeacherUrl === 'function') {
                    const testUrl = getTeacherUrl('generate_test.php');
                    results.push('<p><strong>Test URL:</strong> ' + testUrl + '</p>');
                }
            }
            
            resultDiv.innerHTML = results.join('');
        }
        
        // Auto-Test nach dem Laden
        $(document).ready(function() {
            setTimeout(testJavaScript, 1000);
        });
    </script>
</body>
</html>
