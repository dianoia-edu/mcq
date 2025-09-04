<?php
// Schneller Debug-Test für Instanzen
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Quick Debug für Instanz</h1>
    
    <h2>📁 Datei-Status</h2>
    <?php
    $files = [
        'teacher/teacher_dashboard.php' => 'Teacher Dashboard',
        'includes/teacher_dashboard/test_generator_view.php' => 'Test Generator View',
        'teacher/generate_test.php' => 'Generate Test Backend',
        'js/main.js' => 'JavaScript Main'
    ];
    
    foreach ($files as $file => $desc) {
        $exists = file_exists($file);
        echo '<p><strong>' . $desc . ':</strong> ';
        echo '<span class="' . ($exists ? 'ok' : 'error') . '">';
        echo $exists ? '✅ Vorhanden' : '❌ Fehlt';
        echo '</span> <small>(' . $file . ')</small></p>';
    }
    ?>
    
    <h2>🌐 Test Links</h2>
    <p><a href="teacher/teacher_dashboard.php" target="_blank">🎯 Teacher Dashboard öffnen</a></p>
    <p><a href="debug_testgenerator.php" target="_blank">🔍 Original Debug (falls vorhanden)</a></p>
    
    <h2>📋 System Info</h2>
    <pre><?php
    echo "Aktueller Pfad: " . __DIR__ . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Server: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    ?></pre>
    
    <h2>🧪 Direkter Test</h2>
    <p>Wenn die oben gezeigten Dateien alle vorhanden sind, sollte der Test-Generator funktionieren.</p>
    <p><strong>Falls nicht:</strong> Kopieren Sie diesen Code und erstellen Sie eine Datei <code>quick_debug.php</code> direkt in Ihrer Instanz.</p>
</body>
</html>
