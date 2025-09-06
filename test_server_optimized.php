<?php
/**
 * 🖥️ SERVER-OPTIMIZED NUCLEAR TEST
 * Teste die neue server-optimierte Version ohne Chrome/Selenium
 */

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>🖥️ SERVER-OPTIMIZED TEST</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #0a0a0a; color: #fff; }
        .hero { text-align: center; background: linear-gradient(45deg, #4444ff, #44ff88); padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .output { background: #000; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; margin: 20px 0; }
        .success { border-left: 4px solid #44ff44; background: #0f2f0f; }
        .error { border-left: 4px solid #ff4444; background: #2f0f0f; }
        .warning { border-left: 4px solid #ffaa44; background: #2f2f0f; }
    </style>
</head>
<body>";

echo "<div class='hero'>
    <h1>🖥️ SERVER-OPTIMIZED TEST</h1>
    <p><strong>Neue Strategien gegen YouTube-Bot-Detection!</strong></p>
    <p>6 Methoden ohne Chrome/Selenium - perfekt für Live-Server!</p>
</div>";

if (isset($_GET['test'])) {
    echo "<div class='output'>";
    
    $testUrl = 'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s';
    $serverScript = __DIR__ . '/includes/youtube_server_optimized.py';
    
    echo "🎯 Test-URL: $testUrl\n";
    echo "🖥️ SERVER Script: $serverScript\n\n";
    
    if (!file_exists($serverScript)) {
        echo "❌ SERVER Script nicht gefunden!\n";
        exit;
    }
    
    // Maximale Ausführungszeit setzen
    set_time_limit(300); // 5 Minuten
    ini_set('max_execution_time', 300);
    
    echo "⏰ Timeout gesetzt: 5 Minuten\n";
    echo "🖥️ Starte SERVER-OPTIMIZED NUCLEAR...\n\n";
    flush();
    
    $escapedUrl = escapeshellarg($testUrl);
    $escapedScript = escapeshellarg($serverScript);
    $command = "python3 $escapedScript $escapedUrl 2>&1";
    
    echo "💻 Command: $command\n\n";
    flush();
    
    $startTime = microtime(true);
    
    // Live-Output mit proc_open
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        $output = '';
        $errorOutput = '';
        $lastOutput = time();
        
        echo "📡 Live-Output:\n";
        echo "==============\n";
        flush();
        
        while (true) {
            $status = proc_get_status($process);
            
            if (!$status['running']) {
                echo "\n✅ Process beendet.\n";
                break;
            }
            
            // Timeout nach 4 Minuten
            if (time() - $startTime > 240) {
                proc_terminate($process);
                echo "\n⏰ TIMEOUT nach 4 Minuten!\n";
                break;
            }
            
            $newOutput = stream_get_contents($pipes[1]);
            $newError = stream_get_contents($pipes[2]);
            
            if ($newOutput) {
                $output .= $newOutput;
                echo htmlspecialchars($newOutput);
                flush();
                $lastOutput = time();
            }
            
            if ($newError) {
                $errorOutput .= $newError;
                echo "<span style='color: #ff8888;'>" . htmlspecialchars($newError) . "</span>";
                flush();
                $lastOutput = time();
            }
            
            // Heartbeat alle 3 Sekunden
            if (time() - $lastOutput >= 3) {
                echo "⚡";
                flush();
                $lastOutput = time();
            }
            
            usleep(300000); // 300ms
        }
        
        $output .= stream_get_contents($pipes[1]);
        $errorOutput .= stream_get_contents($pipes[2]);
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        
    } else {
        echo "❌ Konnte Python-Process nicht starten!\n";
    }
    
    $duration = microtime(true) - $startTime;
    
    echo "\n\n" . str_repeat("=", 60) . "\n";
    echo "⏱️ Gesamtdauer: " . round($duration, 2) . " Sekunden\n";
    echo "📊 Output-Länge: " . strlen($output) . " Zeichen\n";
    echo "🔴 Error-Länge: " . strlen($errorOutput) . " Zeichen\n";
    
    // Analysiere Ergebnis
    $lines = array_filter(explode("\n", trim($output)));
    $lastLine = end($lines);
    
    echo "\n📋 RESULT-ANALYSE:\n";
    echo "==================\n";
    
    if (!empty($lastLine) && $lastLine[0] === '{') {
        $result = json_decode($lastLine, true);
        
        if ($result !== null) {
            if ($result['success']) {
                echo "<div class='success'>";
                echo "🎉 STATUS: SUCCESS!\n";
                echo "🖥️ METHODE: " . $result['source'] . "\n";
                echo "📝 LÄNGE: " . number_format($result['length']) . " Zeichen\n";
                echo "\n📄 TRANSCRIPT (erste 500 Zeichen):\n";
                echo "\"" . substr($result['transcript'], 0, 500) . "...\"\n";
                echo "</div>";
                
                echo "\n🎉 SERVER-OPTIMIZED SUCCESS!\n";
                echo "🖥️ Live-Server kann YouTube-Videos verarbeiten!\n";
                echo "✅ Bereit für MCQ-Integration!\n";
                
            } else {
                echo "<div class='error'>";
                echo "❌ STATUS: FAILED\n";
                echo "🔴 FEHLER: " . $result['error'] . "\n";
                echo "</div>";
                
                echo "\n💡 NÄCHSTE SCHRITTE:\n";
                echo "- YouTube-Transcript-API installieren: pip install youtube-transcript-api\n";
                echo "- Invidious-Instanzen prüfen\n";
                echo "- Mobile-Website-Patterns erweitern\n";
            }
        } else {
            echo "❌ JSON-Parse-Fehler in: " . htmlspecialchars($lastLine) . "\n";
        }
    } else {
        echo "⚠️ Kein JSON-Result gefunden. Letzte Zeilen:\n";
        $lastLines = array_slice($lines, -10);
        foreach ($lastLines as $line) {
            echo "  > " . htmlspecialchars($line) . "\n";
        }
    }
    
    if (!empty($errorOutput)) {
        echo "\n🔴 STDERR:\n";
        echo "=========\n";
        echo htmlspecialchars($errorOutput);
    }
    
    echo "</div>";
    
} else {
    echo "<div style='text-align: center; padding: 40px;'>
        <div class='warning' style='margin: 20px 0; padding: 20px;'>
            <h3>🖥️ SERVER-OPTIMIZED METHODEN:</h3>
            <ul style='text-align: left; max-width: 600px; margin: 0 auto;'>
                <li><strong>🏠 YT-DLP Residential-Proxy:</strong> Deutsche/US-IPs simulieren</li>
                <li><strong>📜 YouTube-Transcript-API:</strong> Direkte API-Zugriffe</li>
                <li><strong>📺 Embedded Player:</strong> TV/Mobile-Player-URLs</li>
                <li><strong>📱 Mobile Website:</strong> iPhone/Android-Simulation</li>
                <li><strong>🔄 Invidious Proxy:</strong> Alternative YouTube-Frontends</li>
                <li><strong>🔐 Advanced Session:</strong> Perfekte Browser-Simulation</li>
            </ul>
        </div>
        
        <h2>🖥️ Bereit für SERVER-Test?</h2>
        <p>Diese Version verwendet <strong>KEINE Chrome/Selenium</strong> - perfekt für Live-Server!</p>
        <a href='?test=1' style='background: linear-gradient(45deg, #4444ff, #44ff88); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; font-weight: bold;'>
            🖥️ SERVER-OPTIMIZED STARTEN
        </a>
        <p style='margin-top: 20px; color: #888;'>
            ⚠️ Test kann bis zu 5 Minuten dauern!
        </p>
    </div>";
}

echo "</body></html>";
?>
