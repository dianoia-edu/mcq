<?php
/**
 * Debug für AJAX-Tab-Aufrufe in Instanzen
 */
session_start();

// Simuliere Teacher-Login für Test
$_SESSION['teacher'] = true;

if (!isset($_GET['test_ajax']) || $_GET['test_ajax'] !== 'debug_tabs') {
    die('AJAX Tab Debug verweigert.');
}

// Teste verschiedene Tab-Views direkt
$testInstance = 'neutest'; // Aus dem Debug-Ergebnis
$basePath = "/var/www/dianoia-ai.de/lehrer_instanzen/$testInstance/mcq-test-system";

$tabViews = [
    'test_generator_view.php',
    'configuration_view.php', 
    'test_results_view.php',
    'test_editor_view.php',
    'config_view.php'
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Tab AJAX Debug</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .tab-test { background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .output { background: #fff; border: 1px solid #ddd; padding: 10px; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>🔍 Tab AJAX Debug</h1>
    
    <div class="tab-test">
        <h2>Session Info</h2>
        <p><strong>Teacher:</strong> <?php echo isset($_SESSION['teacher']) ? '✅ Gesetzt' : '❌ Fehlt'; ?></p>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
    </div>
    
    <?php foreach ($tabViews as $view): ?>
        <div class="tab-test">
            <h2><?php echo $view; ?></h2>
            
            <?php
            $viewPath = $basePath . '/includes/teacher_dashboard/' . $view;
            
            echo "<p><strong>Pfad:</strong> $viewPath</p>";
            
            if (file_exists($viewPath)) {
                echo '<p class="success">✅ Datei existiert (' . number_format(filesize($viewPath)) . ' Bytes)</p>';
                
                // Teste Include
                echo '<h3>Include-Test:</h3>';
                echo '<div class="output">';
                
                ob_start();
                $includeError = null;
                
                try {
                    // Setze Arbeitsverzeichnis
                    $oldCwd = getcwd();
                    chdir(dirname($viewPath));
                    
                    include $viewPath;
                    
                    chdir($oldCwd);
                } catch (Exception $e) {
                    $includeError = $e->getMessage();
                } catch (Error $e) {
                    $includeError = $e->getMessage();
                }
                
                $output = ob_get_clean();
                
                if ($includeError) {
                    echo '<span class="error">❌ Include-Fehler: ' . htmlspecialchars($includeError) . '</span>';
                } elseif (empty(trim($output))) {
                    echo '<span class="error">❌ Leere Ausgabe</span>';
                } else {
                    echo '<span class="success">✅ Include erfolgreich (' . strlen($output) . ' Zeichen)</span>';
                    echo '<br><pre>' . htmlspecialchars(substr($output, 0, 500)) . (strlen($output) > 500 ? '...' : '') . '</pre>';
                }
                
                echo '</div>';
                
                // JavaScript-Test
                echo '<h3>JavaScript-Test:</h3>';
                if (strpos($output, 'getIncludesUrl') !== false) {
                    echo '<span class="success">✅ getIncludesUrl gefunden</span><br>';
                } else {
                    echo '<span class="error">❌ getIncludesUrl fehlt</span><br>';
                }
                
                if (strpos($output, 'getTeacherUrl') !== false) {
                    echo '<span class="success">✅ getTeacherUrl gefunden</span><br>';
                } else {
                    echo '<span class="error">❌ getTeacherUrl fehlt</span><br>';
                }
                
            } else {
                echo '<p class="error">❌ Datei fehlt</p>';
            }
            ?>
        </div>
    <?php endforeach; ?>
    
    <div class="tab-test">
        <h2>JavaScript-Helper-Test</h2>
        <?php
        $jsMainPath = $basePath . '/js/main.js';
        if (file_exists($jsMainPath)) {
            $jsContent = file_get_contents($jsMainPath);
            echo '<p class="success">✅ main.js existiert</p>';
            
            if (strpos($jsContent, 'function getTeacherUrl') !== false) {
                echo '<p class="success">✅ getTeacherUrl Funktion gefunden</p>';
            } else {
                echo '<p class="error">❌ getTeacherUrl Funktion fehlt</p>';
            }
            
            if (strpos($jsContent, 'function getIncludesUrl') !== false) {
                echo '<p class="success">✅ getIncludesUrl Funktion gefunden</p>';
            } else {
                echo '<p class="error">❌ getIncludesUrl Funktion fehlt</p>';
            }
        } else {
            echo '<p class="error">❌ main.js fehlt</p>';
        }
        ?>
    </div>
    
</body>
</html>
