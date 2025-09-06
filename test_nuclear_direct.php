<?php
/**
 * 🚀 DIREKTER NUCLEAR TEST (ohne AJAX)
 * Teste NUCLEAR OPTION direkt auf dem Live-Server
 */

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>🚀 DIREKTER NUCLEAR TEST</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #0a0a0a; color: #fff; }
        .hero { text-align: center; background: linear-gradient(45deg, #ff4444, #ff8844); padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .output { background: #000; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; margin: 20px 0; }
        .success { border-left: 4px solid #44ff44; background: #0f2f0f; }
        .error { border-left: 4px solid #ff4444; background: #2f0f0f; }
    </style>
</head>
<body>";

echo "<div class='hero'>
    <h1>🚀 DIREKTER NUCLEAR TEST</h1>
    <p><strong>Live-Server Test ohne AJAX-Timeout!</strong></p>
</div>";

if (isset($_GET['test'])) {
    echo "<div class='output'>";
    
    $testUrl = 'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s';
    $nuclearScript = __DIR__ . '/includes/youtube_nuclear_option.py';
    
    echo "🎯 Test-URL: $testUrl\n";
    echo "☢️ NUCLEAR Script: $nuclearScript\n\n";
    
    if (!file_exists($nuclearScript)) {
        echo "❌ NUCLEAR Script nicht gefunden!\n";
        exit;
    }
    
    // Maximale Ausführungszeit setzen
    set_time_limit(300); // 5 Minuten
    ini_set('max_execution_time', 300);
    
    echo "⏰ Timeout gesetzt: 5 Minuten\n";
    echo "🚀 Starte NUCLEAR OPTION...\n\n";
    flush();
    
    $escapedUrl = escapeshellarg($testUrl);
    $escapedScript = escapeshellarg($nuclearScript);
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
            
            // Heartbeat alle 5 Sekunden
            if (time() - $lastOutput >= 5) {
                echo ".";
                flush();
                $lastOutput = time();
            }
            
            usleep(500000); // 500ms
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
    
    echo "\n\n" . str_repeat("=", 50) . "\n";
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
                echo "🎯 METHODE: " . $result['source'] . "\n";
                echo "📝 LÄNGE: " . number_format($result['length']) . " Zeichen\n";
                echo "📄 TRANSCRIPT (erste 300 Zeichen):\n";
                echo substr($result['transcript'], 0, 300) . "...\n";
                echo "</div>";
                
                echo "\n✅ LIVE-SERVER TEST ERFOLGREICH!\n";
                echo "☢️ NUCLEAR OPTION funktioniert perfekt auf dem Live-Server!\n";
                
            } else {
                echo "<div class='error'>";
                echo "❌ STATUS: FAILED\n";
                echo "🔴 FEHLER: " . $result['error'] . "\n";
                echo "</div>";
            }
        } else {
            echo "❌ JSON-Parse-Fehler in: $lastLine\n";
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
        <h2>🚀 Bereit für direkten Test?</h2>
        <p>Dieser Test läuft direkt ohne AJAX-Timeouts!</p>
        <a href='?test=1' style='background: #ff4444; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px;'>
            ☢️ NUCLEAR OPTION STARTEN
        </a>
        <p style='margin-top: 20px; color: #888;'>
            ⚠️ Test kann bis zu 5 Minuten dauern!
        </p>
    </div>";
}

echo "</body></html>";
?>
