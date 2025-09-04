<?php
/**
 * Schnelle Tab-Debug für Live-Server
 */

if (!isset($_GET['test_tabs']) || $_GET['test_tabs'] !== 'debug_now') {
    die('Tab-Debug verweigert.');
}

// Teste verschiedene Pfad-Szenarien
$testPaths = [];

// Szenario 1: Hauptsystem
$testPaths['Hauptsystem (teacher/)'] = [
    'currentDir' => '/var/www/dianoia-ai.de/mcq-test-system/teacher',
    'includesPath' => dirname('/var/www/dianoia-ai.de/mcq-test-system/teacher') . '/includes/teacher_dashboard/test_generator_view.php'
];

// Szenario 2: Instanz mit teacher/ Verzeichnis  
$testPaths['Instanz (teacher/)'] = [
    'currentDir' => '/var/www/dianoia-ai.de/lehrer_instanzen/test10/mcq-test-system/teacher',
    'includesPath' => dirname('/var/www/dianoia-ai.de/lehrer_instanzen/test10/mcq-test-system/teacher') . '/includes/teacher_dashboard/test_generator_view.php'
];

// Szenario 3: Instanz im Root (falls Dashboard kopiert wurde)
$testPaths['Instanz (root)'] = [
    'currentDir' => '/var/www/dianoia-ai.de/lehrer_instanzen/test10/mcq-test-system',
    'includesPath' => '/var/www/dianoia-ai.de/lehrer_instanzen/test10/mcq-test-system/includes/teacher_dashboard/test_generator_view.php'
];

// Live-Test für aktuelle Umgebung
$realCurrentDir = dirname(__FILE__);
$realScriptPath = __FILE__;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Quick Tab Debug</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 50px auto; padding: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>🔍 Quick Tab Debug</h1>
    
    <div class="test-section">
        <h2>Aktuelle Umgebung</h2>
        <p><strong>Script-Pfad:</strong> <?php echo $realScriptPath; ?></p>
        <p><strong>Current Dir:</strong> <?php echo $realCurrentDir; ?></p>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'NICHT GESETZT'; ?></p>
        <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? 'NICHT GESETZT'; ?></p>
    </div>
    
    <div class="test-section">
        <h2>Pfad-Szenarien Test</h2>
        <?php foreach ($testPaths as $scenarioName => $scenario): ?>
            <h3><?php echo $scenarioName; ?></h3>
            <p><strong>Current Dir:</strong> <?php echo $scenario['currentDir']; ?></p>
            <p><strong>Include Path:</strong> <?php echo $scenario['includesPath']; ?></p>
            <p><strong>Existiert:</strong> 
                <?php 
                if (file_exists($scenario['includesPath'])) {
                    echo '<span class="success">✅ JA (' . number_format(filesize($scenario['includesPath'])) . ' Bytes)</span>';
                } else {
                    echo '<span class="error">❌ NEIN</span>';
                }
                ?>
            </p>
            <hr>
        <?php endforeach; ?>
    </div>
    
    <div class="test-section">
        <h2>Live-Instanzen Test</h2>
        <?php
        $instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
        if (is_dir($instancesPath)) {
            $dirs = scandir($instancesPath);
            $testInstance = null;
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($instancesPath . $dir . '/mcq-test-system')) {
                    $testInstance = $dir;
                    break;
                }
            }
            
            if ($testInstance) {
                echo "<h3>Test-Instanz: $testInstance</h3>";
                
                // Teste verschiedene Dashboard-Positionen
                $dashboardPaths = [
                    'teacher/teacher_dashboard.php' => $instancesPath . $testInstance . '/mcq-test-system/teacher/teacher_dashboard.php',
                    'teacher_dashboard.php (root)' => $instancesPath . $testInstance . '/mcq-test-system/teacher_dashboard.php'
                ];
                
                foreach ($dashboardPaths as $name => $path) {
                    echo "<p><strong>$name:</strong> ";
                    if (file_exists($path)) {
                        echo '<span class="success">✅ Existiert</span>';
                        
                        // Teste Include-Pfade für diese Position
                        $testFile = dirname($path) . '/includes/teacher_dashboard/test_generator_view.php';
                        if (basename(dirname($path)) === 'teacher') {
                            $testFile = dirname(dirname($path)) . '/includes/teacher_dashboard/test_generator_view.php';
                        }
                        
                        echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;Include-Test: $testFile ";
                        if (file_exists($testFile)) {
                            echo '<span class="success">✅</span>';
                        } else {
                            echo '<span class="error">❌</span>';
                        }
                    } else {
                        echo '<span class="error">❌ Fehlt</span>';
                    }
                    echo "</p>";
                }
                
                // Teste Include-Verzeichnis
                $includesDir = $instancesPath . $testInstance . '/mcq-test-system/includes/teacher_dashboard/';
                echo "<p><strong>Includes-Verzeichnis:</strong> ";
                if (is_dir($includesDir)) {
                    $files = scandir($includesDir);
                    $viewFiles = array_filter($files, function($f) { return strpos($f, '_view.php') !== false; });
                    echo '<span class="success">✅ ' . count($viewFiles) . ' View-Dateien</span>';
                    echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;Dateien: ' . implode(', ', $viewFiles);
                } else {
                    echo '<span class="error">❌ Fehlt</span>';
                }
                echo "</p>";
                
            } else {
                echo '<p class="warning">Keine Test-Instanz gefunden</p>';
            }
        } else {
            echo '<p class="error">Instanzen-Verzeichnis nicht gefunden</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Empfohlene Aktion</h2>
        <p>Basierend auf den Tests oben:</p>
        <ol>
            <li>Überprüfen Sie, wo das Dashboard in den Instanzen liegt</li>
            <li>Passen Sie die getIncludesPath() Funktion entsprechend an</li>
            <li>Testen Sie das Update-Script</li>
        </ol>
    </div>

</body>
</html>
