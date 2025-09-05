<?php
/**
 * Debug YouTube Python Script
 * Zeigt genau was schiefgeht
 */

require_once 'includes/youtube_transcript_service.php';

echo "<h1>🔍 YouTube Python Debug</h1>\n";

// Test-URL (sauber)
$testUrl = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'; // Rick Roll (hat garantiert Captions)

echo "<h2>1. 🧪 URL-Test</h2>\n";
echo "<p><strong>Test-URL:</strong> <code>" . htmlspecialchars($testUrl) . "</code></p>\n";

// Python-Script direkt testen
echo "<h2>2. 🐍 Python-Script direkt</h2>\n";

$pythonScript = __DIR__ . '/includes/youtube_transcript_robust.py';
echo "<p><strong>Script-Pfad:</strong> <code>$pythonScript</code></p>\n";
echo "<p><strong>Existiert:</strong> " . (file_exists($pythonScript) ? "✅ Ja" : "❌ Nein") . "</p>\n";

if (file_exists($pythonScript)) {
    // Direkte Python-Ausführung
    $escapedUrl = escapeshellarg($testUrl);
    $escapedScript = escapeshellarg($pythonScript);
    
    $commands = [
        "python3 $escapedScript $escapedUrl",
        "python $escapedScript $escapedUrl",
        "/usr/bin/python3 $escapedScript $escapedUrl"
    ];
    
    foreach ($commands as $i => $command) {
        echo "<h3>🔧 Command " . ($i + 1) . "</h3>\n";
        echo "<pre>" . htmlspecialchars($command) . "</pre>\n";
        
        $startTime = microtime(true);
        // STDERR separat abfangen
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        
        $output = $stdout . "\n--- STDERR ---\n" . $stderr;
        $duration = microtime(true) - $startTime;
        
        echo "<p><strong>Dauer:</strong> " . round($duration, 2) . "s</p>\n";
        echo "<p><strong>Output-Länge:</strong> " . strlen($output) . " Zeichen</p>\n";
        
        if ($output) {
            echo "<details><summary><strong>Vollständiger Output</strong></summary>\n";
            echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: auto;'>";
            echo htmlspecialchars($output);
            echo "</pre></details>\n";
            
            // Versuche JSON zu parsen
            $lines = explode("\n", trim($output));
            for ($j = count($lines) - 1; $j >= 0; $j--) {
                $line = trim($lines[$j]);
                if (!empty($line) && $line[0] === '{') {
                    $result = json_decode($line, true);
                    if ($result !== null) {
                        echo "<div style='background: " . ($result['success'] ? '#e8f5e8' : '#ffebee') . "; padding: 10px; margin: 10px 0;'>\n";
                        echo "<strong>JSON-Result:</strong><br>\n";
                        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>\n";
                        echo "</div>\n";
                        break;
                    }
                }
            }
        } else {
            echo "<p>❌ <strong>Kein Output</strong></p>\n";
        }
        
        echo "<hr>\n";
    }
}

// PHP Service Test
echo "<h2>3. 🔧 PHP Service Test</h2>\n";

$service = new YouTubeTranscriptService(30, true); // 30s Timeout, Debug an

$result = $service->getTranscript($testUrl);

echo "<div style='background: " . ($result['success'] ? '#e8f5e8' : '#ffebee') . "; padding: 15px; border-radius: 5px;'>\n";
echo "<h3>" . ($result['success'] ? '✅ Erfolgreich' : '❌ Fehlgeschlagen') . "</h3>\n";

if ($result['success']) {
    echo "<p><strong>Quelle:</strong> {$result['source']}</p>\n";
    echo "<p><strong>Länge:</strong> {$result['length']} Zeichen</p>\n";
    echo "<details><summary><strong>Transcript-Vorschau</strong></summary>\n";
    echo "<pre>" . htmlspecialchars(substr($result['transcript'], 0, 500)) . "...</pre>\n";
    echo "</details>\n";
} else {
    echo "<p><strong>Fehler:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
    
    if (isset($result['raw_output'])) {
        echo "<details><summary><strong>Raw Output</strong></summary>\n";
        echo "<pre>" . htmlspecialchars($result['raw_output']) . "</pre>\n";
        echo "</details>\n";
    }
}

echo "</div>\n";

// Einfacher Python-Test
echo "<h2>4. 🧪 Einfacher Python-Test</h2>\n";

$simpleTest = '
import sys
import json

try:
    import requests
    print(json.dumps({"success": True, "message": "Python und requests funktionieren", "version": sys.version}))
except ImportError as e:
    print(json.dumps({"success": False, "error": "requests nicht installiert: " + str(e)}))
except Exception as e:
    print(json.dumps({"success": False, "error": "Python-Fehler: " + str(e)}))
';

$tempFile = sys_get_temp_dir() . '/test_python_' . uniqid() . '.py';
file_put_contents($tempFile, $simpleTest);

$pythonCommands = ['python3', 'python', '/usr/bin/python3'];

foreach ($pythonCommands as $pythonCmd) {
    echo "<h3>🐍 $pythonCmd</h3>\n";
    
    $output = shell_exec("$pythonCmd " . escapeshellarg($tempFile) . " 2>&1");
    
    if ($output) {
        $result = json_decode(trim($output), true);
        if ($result) {
            if ($result['success']) {
                echo "<div style='background: #e8f5e8; padding: 10px;'>✅ OK: " . htmlspecialchars($result['message']) . "</div>\n";
            } else {
                echo "<div style='background: #ffebee; padding: 10px;'>❌ Fehler: " . htmlspecialchars($result['error']) . "</div>\n";
            }
        } else {
            echo "<div style='background: #fff3e0; padding: 10px;'>⚠️ Unbekannter Output: " . htmlspecialchars($output) . "</div>\n";
        }
    } else {
        echo "<div style='background: #ffebee; padding: 10px;'>❌ $pythonCmd nicht verfügbar</div>\n";
    }
}

unlink($tempFile);

echo "<h2>5. 💡 Nächste Schritte</h2>\n";
echo "<p>Wenn hier Fehler zu sehen sind, können wir sie gezielt beheben!</p>\n";

?>
