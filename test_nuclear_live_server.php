<?php
/**
 * 🕵️ LIVE-SERVER Test für NUCLEAR OPTION
 * Teste ob es wirklich auf dem Live-Server funktioniert!
 */

require_once 'includes/youtube_professional_service.php';

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>🕵️ LIVE-SERVER NUCLEAR TEST</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #0a0a0a; color: #fff; }
        .test-box { background: #1a1a1a; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #ff4444; }
        .success { border-color: #44ff44; background: #0f2f0f; }
        .warning { border-color: #ffaa44; background: #2f2f0f; }
        .error { border-color: #ff4444; background: #2f0f0f; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .hero { text-align: center; background: linear-gradient(45deg, #ff4444, #ff8844); padding: 30px; border-radius: 15px; margin-bottom: 30px; }
    </style>
</head>
<body>";

echo "<div class='hero'>
    <h1>🕵️ LIVE-SERVER NUCLEAR TEST</h1>
    <p><strong>Teste ob NUCLEAR OPTION wirklich auf dem Live-Server funktioniert!</strong></p>
</div>";

// Test 1: Server-Environment prüfen
echo "<div class='test-box'>
    <h2>🖥️ Server-Environment Check</h2>";

echo "<h3>📊 Server-Info:</h3>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Operating System: " . PHP_OS . " (" . PHP_OS_FAMILY . ")\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Path: " . __FILE__ . "\n";
echo "Working Directory: " . getcwd() . "\n";
echo "</pre>";

// Test 2: Python verfügbar?
echo "<h3>🐍 Python-Verfügbarkeit:</h3>";
echo "<pre>";

$pythonCommands = ['python3', 'python', 'py'];
$pythonWorking = false;
$workingPythonCmd = null;

foreach ($pythonCommands as $cmd) {
    $output = shell_exec("$cmd --version 2>&1");
    echo "$cmd: " . ($output ? trim($output) : '❌ Nicht verfügbar') . "\n";
    
    if ($output && !$pythonWorking) {
        $pythonWorking = true;
        $workingPythonCmd = $cmd;
    }
}

echo "</pre>";

if (!$pythonWorking) {
    echo "<div class='error'>❌ <strong>PROBLEM:</strong> Kein Python auf dem Server verfügbar!</div>";
} else {
    echo "<div class='success'>✅ <strong>Python verfügbar:</strong> $workingPythonCmd</div>";
}

echo "</div>";

// Test 3: NUCLEAR Script prüfen
echo "<div class='test-box'>
    <h2>☢️ NUCLEAR Script Check</h2>";

$nuclearScript = __DIR__ . '/includes/youtube_nuclear_option.py';
echo "<p><strong>Script-Pfad:</strong> $nuclearScript</p>";

if (file_exists($nuclearScript)) {
    echo "<div class='success'>✅ NUCLEAR Script gefunden!</div>";
    
    $scriptSize = filesize($nuclearScript);
    $lastModified = date('Y-m-d H:i:s', filemtime($nuclearScript));
    echo "<p><strong>Größe:</strong> " . number_format($scriptSize) . " Bytes</p>";
    echo "<p><strong>Letzte Änderung:</strong> $lastModified</p>";
    
    // Erste Zeilen anzeigen
    $firstLines = array_slice(file($nuclearScript), 0, 10);
    echo "<h3>📄 Script-Anfang:</h3>";
    echo "<pre>" . htmlspecialchars(implode('', $firstLines)) . "</pre>";
    
} else {
    echo "<div class='error'>❌ <strong>FEHLER:</strong> NUCLEAR Script nicht gefunden!</div>";
}

echo "</div>";

// Test 4: Dependencies Check
if ($pythonWorking) {
    echo "<div class='test-box'>
        <h2>📦 Python Dependencies Check</h2>";
    
    $dependencies = ['requests', 'yt_dlp', 'selenium', 'undetected_chromedriver'];
    
    foreach ($dependencies as $dep) {
        echo "<h3>🔍 Teste $dep:</h3>";
        echo "<pre>";
        
        $checkCmd = "$workingPythonCmd -c \"import $dep; print('✅ $dep verfügbar:', $dep.__version__ if hasattr($dep, '__version__') else 'Version unbekannt')\" 2>&1";
        $output = shell_exec($checkCmd);
        
        echo htmlspecialchars($output ?: "❌ $dep nicht verfügbar");
        echo "</pre>";
    }
    
    echo "</div>";
}

// Test 5: NUCLEAR OPTION Live-Test
if ($pythonWorking && file_exists($nuclearScript)) {
    echo "<div class='test-box'>
        <h2>🚀 LIVE NUCLEAR TEST</h2>
        <p><strong>Teste NUCLEAR OPTION mit echtem YouTube-Video...</strong></p>";
    
    $testUrl = 'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s';
    echo "<p><strong>Test-Video:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>";
    
    echo "<h3>⏳ Führe NUCLEAR OPTION aus...</h3>";
    echo "<div id='nuclear-output' style='background: #000; padding: 15px; border-radius: 5px; font-family: monospace;'>Starte Test...</div>";
    
    echo "<script>
        var outputDiv = document.getElementById('nuclear-output');
        outputDiv.innerHTML = '🚀 NUCLEAR OPTION startet...\\n';
        
        // AJAX-Request für Live-Output
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'test_nuclear_live_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    outputDiv.innerHTML = xhr.responseText;
                } else {
                    outputDiv.innerHTML = '❌ AJAX-Fehler: ' + xhr.status;
                }
            }
        };
        
        xhr.send('test_url=" . urlencode($testUrl) . "');
    </script>";
    
    echo "</div>";
}

// Test 6: Integration Test
echo "<div class='test-box'>
    <h2>🔧 Integration Test</h2>
    <p>Teste die vollständige YouTube-Service-Integration...</p>";

try {
    $service = new YouTubeProfessionalService(60, true); // Kurzer Timeout für Test
    echo "<div class='success'>✅ YouTubeProfessionalService erstellt</div>";
    
    echo "<h3>📋 Service-Konfiguration:</h3>";
    echo "<pre>";
    
    // Reflection für private Properties
    $reflection = new ReflectionClass($service);
    $props = $reflection->getProperties();
    
    foreach ($props as $prop) {
        $prop->setAccessible(true);
        $value = $prop->getValue($service);
        
        if (is_string($value) && strlen($value) > 100) {
            $value = substr($value, 0, 100) . '...';
        }
        
        echo $prop->getName() . ": " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Integration-Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='test-box warning'>
    <h2>⚠️ WICHTIGER HINWEIS</h2>
    <p>Dieser Test läuft auf dem <strong>Live-Server</strong>! Falls Probleme auftreten:</p>
    <ul>
        <li><strong>Python fehlt:</strong> Server-Admin kontaktieren</li>
        <li><strong>Dependencies fehlen:</strong> <code>pip install yt-dlp requests selenium</code></li>
        <li><strong>Permissions:</strong> Script-Ausführungsrechte prüfen</li>
        <li><strong>Firewall:</strong> Ausgehende Verbindungen zu YouTube erlauben</li>
    </ul>
</div>";

echo "</body></html>";
?>
