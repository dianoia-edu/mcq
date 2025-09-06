<?php
/**
 * AJAX-Handler für Live NUCLEAR Test
 */

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_POST['test_url'])) {
    echo "❌ Keine Test-URL angegeben";
    exit;
}

$testUrl = $_POST['test_url'];

// NUCLEAR OPTION ausführen
$nuclearScript = __DIR__ . '/includes/youtube_nuclear_option.py';

if (!file_exists($nuclearScript)) {
    echo "❌ NUCLEAR Script nicht gefunden: $nuclearScript\n";
    exit;
}

// Python-Command ermitteln
$pythonCommands = ['python3', 'python', 'py'];
$workingPython = null;

foreach ($pythonCommands as $cmd) {
    $test = shell_exec("$cmd --version 2>&1");
    if ($test && strpos($test, 'Python') !== false) {
        $workingPython = $cmd;
        break;
    }
}

if (!$workingPython) {
    echo "❌ Kein Python auf dem Server verfügbar\n";
    echo "Getestete Commands: " . implode(', ', $pythonCommands) . "\n";
    exit;
}

echo "✅ Python gefunden: $workingPython\n";
echo "🎯 Teste URL: $testUrl\n";
echo "☢️ Starte NUCLEAR OPTION...\n\n";

// NUCLEAR Command bauen
$escapedUrl = escapeshellarg($testUrl);
$escapedScript = escapeshellarg($nuclearScript);
$command = "$workingPython $escapedScript $escapedUrl 2>&1";

echo "💻 Command: $command\n\n";

// Ausführen mit Live-Output
$startTime = microtime(true);

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
    $start = time();
    
    while (true) {
        $status = proc_get_status($process);
        
        if (!$status['running']) {
            break;
        }
        
        if (time() - $start > 120) { // 2 Min Timeout
            proc_terminate($process);
            echo "\n⏰ TIMEOUT nach 120 Sekunden!\n";
            break;
        }
        
        $newOutput = stream_get_contents($pipes[1]);
        $newError = stream_get_contents($pipes[2]);
        
        if ($newOutput) {
            $output .= $newOutput;
            echo $newOutput;
            flush();
        }
        
        if ($newError) {
            $errorOutput .= $newError;
            echo "STDERR: " . $newError;
            flush();
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
    echo "❌ Konnte Python-Process nicht starten\n";
    exit;
}

$duration = microtime(true) - $startTime;

echo "\n\n⏱️ Gesamtdauer: " . round($duration, 2) . " Sekunden\n";

// Analyse des Outputs
$lines = explode("\n", trim($output));
$lastLine = end($lines);

if (!empty($lastLine) && $lastLine[0] === '{') {
    $result = json_decode($lastLine, true);
    
    if ($result !== null) {
        echo "\n📊 ERGEBNIS-ANALYSE:\n";
        echo "================\n";
        
        if ($result['success']) {
            echo "🎉 STATUS: SUCCESS!\n";
            echo "🎯 METHODE: " . $result['source'] . "\n";
            echo "📝 LÄNGE: " . number_format($result['length']) . " Zeichen\n";
            echo "📄 TRANSCRIPT-PREVIEW:\n";
            echo substr($result['transcript'], 0, 200) . "...\n";
            
            echo "\n✅ LIVE-SERVER TEST ERFOLGREICH!\n";
            echo "☢️ NUCLEAR OPTION funktioniert auf dem Live-Server!\n";
            
        } else {
            echo "❌ STATUS: FAILED\n";
            echo "🔴 FEHLER: " . $result['error'] . "\n";
            
            if (isset($result['details'])) {
                echo "📋 DETAILS:\n";
                print_r($result['details']);
            }
        }
    } else {
        echo "\n❌ JSON-Parse-Fehler in letzter Zeile: $lastLine\n";
    }
} else {
    echo "\n⚠️ Kein gültiges JSON-Result gefunden\n";
    echo "Letzten 5 Zeilen:\n";
    $lastFiveLines = array_slice($lines, -5);
    foreach ($lastFiveLines as $line) {
        echo "  > $line\n";
    }
}

if (!empty($errorOutput)) {
    echo "\n🔴 STDERR OUTPUT:\n";
    echo "================\n";
    echo $errorOutput;
}
?>
