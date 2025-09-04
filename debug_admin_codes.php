<?php
/**
 * Debug-Tool zum Finden der Admin-Codes in Instanzen
 */

// Sicherheitscheck
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'admin_debug_2024') {
    die('Debug-Zugriff verweigert.');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin-Code Debug</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 10px; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>🔍 Admin-Code Debug</h1>
    
    <?php
    
    $instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
    
    if (is_dir($instancesPath)) {
        $dirs = scandir($instancesPath);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $instancePath = $instancesPath . $dir;
            $mcqPath = $instancePath . '/mcq-test-system';
            
            if (!is_dir($mcqPath)) continue;
            
            echo '<div class="section">';
            echo '<h2>📁 Instanz: ' . $dir . '</h2>';
            
            // Alle möglichen Config-Pfade testen
            $testPaths = [
                'api_config.json (includes/config)' => $mcqPath . '/includes/config/api_config.json',
                'app_config.php (includes/config)' => $mcqPath . '/includes/config/app_config.php',
                'api_config.json (config)' => $mcqPath . '/config/api_config.json',
                'api_config.json (instance root)' => $instancePath . '/config/api_config.json',
                'create_instance.log' => $instancePath . '/create_instance.log'
            ];
            
            $foundAdminCode = false;
            
            foreach ($testPaths as $label => $path) {
                $exists = file_exists($path);
                echo '<div>';
                echo '<strong>' . $label . ':</strong> ';
                echo $exists ? '✅ Existiert' : '❌ Nicht gefunden';
                echo '<br><code>' . $path . '</code>';
                
                if ($exists) {
                    try {
                        $content = file_get_contents($path);
                        
                        if (str_ends_with($path, '.json')) {
                            $data = json_decode($content, true);
                            if (isset($data['admin_access_code'])) {
                                echo '<br><span style="color: green;">🎯 Admin-Code gefunden: <strong>' . $data['admin_access_code'] . '</strong></span>';
                                $foundAdminCode = true;
                            } else {
                                echo '<br><span style="color: orange;">JSON enthält keinen admin_access_code</span>';
                                echo '<br>Verfügbare Keys: ' . implode(', ', array_keys($data ?: []));
                            }
                        } elseif (str_ends_with($path, '.php')) {
                            if (preg_match('/[\'"]admin_access_code[\'"].*?[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                                echo '<br><span style="color: green;">🎯 Admin-Code gefunden: <strong>' . $matches[1] . '</strong></span>';
                                $foundAdminCode = true;
                            } else {
                                echo '<br><span style="color: orange;">Kein admin_access_code Pattern gefunden</span>';
                            }
                        } else {
                            // Log-Datei oder andere
                            if (strpos($content, 'admin_access_code') !== false) {
                                if (preg_match('/admin_access_code["\']?\s*[=:]\s*["\']?([^"\'\s\n]+)/', $content, $matches)) {
                                    echo '<br><span style="color: green;">🎯 Admin-Code gefunden: <strong>' . $matches[1] . '</strong></span>';
                                    $foundAdminCode = true;
                                }
                            }
                        }
                        
                        // Zeige ersten Teil des Inhalts
                        echo '<br><details><summary>Datei-Inhalt (erste 500 Zeichen)</summary>';
                        echo '<pre>' . htmlspecialchars(substr($content, 0, 500)) . '</pre>';
                        echo '</details>';
                        
                    } catch (Exception $e) {
                        echo '<br><span style="color: red;">Fehler beim Lesen: ' . $e->getMessage() . '</span>';
                    }
                }
                echo '</div><br>';
            }
            
            if (!$foundAdminCode) {
                echo '<div style="background: #fff3cd; padding: 10px; border: 1px solid #ffc107;">';
                echo '⚠️ <strong>Kein Admin-Code gefunden!</strong> Schauen wir in alle Dateien der Instanz...';
                
                // Rekursive Suche nach allen Dateien mit "admin" im Namen oder Inhalt
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mcqPath));
                $foundFiles = [];
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getSize() < 100000) { // Nur kleinere Dateien
                        $filename = $file->getFilename();
                        $filepath = $file->getPathname();
                        
                        // Dateien mit "admin", "config", "access" im Namen
                        if (stripos($filename, 'admin') !== false || 
                            stripos($filename, 'config') !== false || 
                            stripos($filename, 'access') !== false) {
                            $foundFiles[] = $filepath;
                        }
                    }
                }
                
                if (!empty($foundFiles)) {
                    echo '<br><strong>Interessante Dateien gefunden:</strong><ul>';
                    foreach (array_slice($foundFiles, 0, 10) as $file) { // Nur erste 10
                        echo '<li><code>' . str_replace($mcqPath, '', $file) . '</code></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<br>Keine verdächtigen Dateien gefunden.';
                }
                
                echo '</div>';
            }
            
            echo '</div>';
            
            // Nur erste 3 Instanzen zur Übersicht
            static $count = 0;
            if (++$count >= 3) break;
        }
    } else {
        echo '<div class="error">Instanzen-Verzeichnis nicht gefunden: ' . $instancesPath . '</div>';
    }
    
    ?>
    
</body>
</html>
