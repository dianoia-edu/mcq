<?php
/**
 * Debug für Tab-Sichtbarkeit in Instanzen
 */

if (!isset($_GET['debug_tab']) || $_GET['debug_tab'] !== 'visibility_test') {
    die('Tab-Visibility-Debug verweigert.');
}

// Simuliere die gleiche Logik wie im teacher_dashboard.php
$currentDir = dirname(__FILE__);
$isInTeacherDir = basename($currentDir) === 'teacher';

// Test-Instanz
$testInstance = 'neutest';
$instanceDashboardPath = "/var/www/dianoia-ai.de/lehrer_instanzen/$testInstance/mcq-test-system/teacher/teacher_dashboard.php";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tab Visibility Debug</title>
    <style>
        body { font-family: monospace; max-width: 800px; margin: 50px auto; padding: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>🔍 Tab Visibility Debug</h1>
    
    <div class="test-section">
        <h2>Hauptsystem-Kontext</h2>
        <p><strong>Current Dir:</strong> <?php echo $currentDir; ?></p>
        <p><strong>Basename:</strong> <?php echo basename($currentDir); ?></p>
        <p><strong>$isInTeacherDir:</strong> <?php echo $isInTeacherDir ? 'true' : 'false'; ?></p>
        <p><strong>Tab sollte sein:</strong> <?php echo $isInTeacherDir ? '✅ Sichtbar' : '❌ Versteckt'; ?></p>
    </div>
    
    <div class="test-section">
        <h2>Instanz-Kontext Simulation</h2>
        <?php
        // Simuliere Instanz-Kontext
        $instanceTeacherDir = "/var/www/dianoia-ai.de/lehrer_instanzen/$testInstance/mcq-test-system/teacher";
        $instanceIsInTeacherDir = basename($instanceTeacherDir) === 'teacher';
        ?>
        <p><strong>Instanz-Teacher-Dir:</strong> <?php echo $instanceTeacherDir; ?></p>
        <p><strong>Basename:</strong> <?php echo basename($instanceTeacherDir); ?></p>
        <p><strong>$isInTeacherDir (Instanz):</strong> <?php echo $instanceIsInTeacherDir ? 'true' : 'false'; ?></p>
        <p><strong>Tab sollte in Instanz sein:</strong> <?php echo $instanceIsInTeacherDir ? '⚠️ Sichtbar (PROBLEM!)' : '✅ Versteckt'; ?></p>
    </div>
    
    <div class="test-section">
        <h2>Dashboard-Dateien Check</h2>
        <?php
        $files = [
            'Hauptsystem' => '/var/www/dianoia-ai.de/mcq-test-system/teacher/teacher_dashboard.php',
            'Test-Instanz' => $instanceDashboardPath
        ];
        
        foreach ($files as $name => $path) {
            echo "<h3>$name</h3>";
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $hasTabHiding = strpos($content, 'if ($isInTeacherDir):') !== false;
                $hasEndif = strpos($content, '<?php endif; ?>') !== false;
                
                echo '<p class="success">✅ Datei existiert (' . number_format(filesize($path)) . ' Bytes)</p>';
                echo '<p><strong>Tab-Hiding-Logic:</strong> ' . ($hasTabHiding ? '✅ Vorhanden' : '❌ Fehlt') . '</p>';
                echo '<p><strong>Endif-Tag:</strong> ' . ($hasEndif ? '✅ Vorhanden' : '❌ Fehlt') . '</p>';
                
                // Zeige relevante Zeilen
                $lines = explode("\n", $content);
                $relevantLines = [];
                foreach ($lines as $i => $line) {
                    if (strpos($line, 'isInTeacherDir') !== false || strpos($line, 'tab-instance-management') !== false) {
                        $relevantLines[] = ($i + 1) . ': ' . htmlspecialchars(trim($line));
                    }
                }
                
                if (!empty($relevantLines)) {
                    echo '<p><strong>Relevante Zeilen:</strong></p>';
                    echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ddd;">';
                    echo implode("\n", array_slice($relevantLines, 0, 10));
                    echo '</pre>';
                }
            } else {
                echo '<p class="error">❌ Datei nicht gefunden</p>';
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Mögliche Lösungen</h2>
        <ol>
            <li><strong>Fall 1:</strong> Die Instanz hat noch die alte dashboard.php → Update war nicht vollständig</li>
            <li><strong>Fall 2:</strong> Die Logik erkennt Instanzen als "teacher-dir" → Logik muss angepasst werden</li>
            <li><strong>Fall 3:</strong> Cache-Problem → Browser-Cache leeren</li>
        </ol>
        
        <?php if (file_exists($instanceDashboardPath)): ?>
            <p><strong>Empfehlung:</strong> 
            <?php
            $content = file_get_contents($instanceDashboardPath);
            if (strpos($content, 'if ($isInTeacherDir):') !== false) {
                echo '<span class="success">✅ Update scheint erfolgreich. Prüfen Sie die Browser-Cache oder Logik.</span>';
            } else {
                echo '<span class="error">❌ Tab-Hiding-Code fehlt. Update war nicht erfolgreich.</span>';
            }
            ?>
            </p>
        <?php endif; ?>
    </div>
    
</body>
</html>
