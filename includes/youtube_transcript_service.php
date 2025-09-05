<?php
/**
 * YouTube Transcript Service
 * PHP-Wrapper für das robuste Python-Script
 */

class YouTubeTranscriptService {
    
    private $pythonScript;
    private $timeout;
    private $debug;
    
    public function __construct($timeout = 60, $debug = false) {
        $this->pythonScript = __DIR__ . '/youtube_transcript_robust.py';
        $this->timeout = $timeout;
        $this->debug = $debug;
    }
    
    /**
     * Transcript von YouTube-Video extrahieren
     */
    public function getTranscript($videoUrl) {
        if (!file_exists($this->pythonScript)) {
            return [
                'success' => false,
                'error' => 'Python-Script nicht gefunden: ' . $this->pythonScript
            ];
        }
        
        // Python-Command zusammenbauen
        $command = $this->buildPythonCommand($videoUrl);
        
        if ($this->debug) {
            error_log("YouTube Transcript Command: $command");
        }
        
        // Script ausführen
        $startTime = microtime(true);
        $output = $this->executeCommand($command);
        $duration = microtime(true) - $startTime;
        
        if ($this->debug) {
            error_log("YouTube Transcript Duration: {$duration}s");
            error_log("YouTube Transcript Output: " . substr($output, 0, 500));
        }
        
        // JSON-Response parsen
        $result = $this->parseOutput($output);
        
        if ($result['success']) {
            $result['duration'] = round($duration, 2);
            $result['video_url'] = $videoUrl;
        }
        
        return $result;
    }
    
    /**
     * Python-Command für verschiedene Umgebungen bauen
     */
    private function buildPythonCommand($videoUrl) {
        $escapedUrl = escapeshellarg($videoUrl);
        $escapedScript = escapeshellarg($this->pythonScript);
        
        // Verschiedene Python-Interpreter probieren
        $pythonCmds = [
            'python3',
            'python',
            '/usr/bin/python3',
            '/usr/local/bin/python3'
        ];
        
        foreach ($pythonCmds as $pythonCmd) {
            // Teste ob Python verfügbar ist
            $testCmd = "$pythonCmd --version 2>/dev/null";
            $testOutput = shell_exec($testCmd);
            
            if ($testOutput && strpos($testOutput, 'Python') !== false) {
                return "$pythonCmd $escapedScript $escapedUrl 2>&1";
            }
        }
        
        // Fallback: Standard python3
        return "python3 $escapedScript $escapedUrl 2>&1";
    }
    
    /**
     * Command ausführen mit Timeout
     */
    private function executeCommand($command) {
        // Timeout-Handling für verschiedene Systeme
        if (function_exists('proc_open')) {
            return $this->executeWithProcOpen($command);
        } else {
            // Fallback: shell_exec
            return shell_exec($command);
        }
    }
    
    /**
     * Command mit proc_open und Timeout ausführen
     */
    private function executeWithProcOpen($command) {
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            throw new Exception("Konnte Python-Process nicht starten");
        }
        
        // Pipes non-blocking machen
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        $output = '';
        $errorOutput = '';
        $start = time();
        
        while (true) {
            $status = proc_get_status($process);
            
            // Process beendet?
            if (!$status['running']) {
                break;
            }
            
            // Timeout erreicht?
            if (time() - $start > $this->timeout) {
                proc_terminate($process);
                proc_close($process);
                throw new Exception("Python-Script Timeout nach {$this->timeout} Sekunden");
            }
            
            // Output lesen
            $output .= stream_get_contents($pipes[1]);
            $errorOutput .= stream_get_contents($pipes[2]);
            
            usleep(100000); // 100ms warten
        }
        
        // Restlichen Output lesen
        $output .= stream_get_contents($pipes[1]);
        $errorOutput .= stream_get_contents($pipes[2]);
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        
        if ($this->debug && $errorOutput) {
            error_log("Python Script STDERR: $errorOutput");
        }
        
        return $output . $errorOutput;
    }
    
    /**
     * Script-Output parsen
     */
    private function parseOutput($output) {
        // Versuche JSON zu extrahieren
        $lines = explode("\n", trim($output));
        
        // Suche nach der letzten JSON-Zeile
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            // Ist es JSON?
            if ($line[0] === '{') {
                $result = json_decode($line, true);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        
        // Kein gültiges JSON gefunden
        return [
            'success' => false,
            'error' => 'Ungültige Python-Script Ausgabe',
            'raw_output' => substr($output, 0, 1000) // Erste 1000 Zeichen für Debug
        ];
    }
    
    /**
     * Test-Funktion
     */
    public function testTranscript($videoUrl) {
        echo "<h2>🧪 Robuster Transcript-Test</h2>\n";
        echo "<p><strong>Video:</strong> " . htmlspecialchars($videoUrl) . "</p>\n";
        
        $startTime = microtime(true);
        $result = $this->getTranscript($videoUrl);
        $duration = microtime(true) - $startTime;
        
        echo "<p><strong>Dauer:</strong> " . round($duration, 2) . " Sekunden</p>\n";
        
        if ($result['success']) {
            echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; border-radius: 5px;'>\n";
            echo "<h3>✅ Erfolgreich!</h3>\n";
            echo "<p><strong>Quelle:</strong> {$result['source']}</p>\n";
            echo "<p><strong>Länge:</strong> {$result['length']} Zeichen</p>\n";
            
            if (isset($result['duration'])) {
                echo "<p><strong>Python-Dauer:</strong> {$result['duration']}s</p>\n";
            }
            
            echo "<details><summary><strong>Transcript anzeigen</strong></summary>\n";
            echo "<pre style='max-height: 400px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
            echo htmlspecialchars(substr($result['transcript'], 0, 3000));
            if (strlen($result['transcript']) > 3000) {
                echo "\n\n... (verkürzt, insgesamt {$result['length']} Zeichen)";
            }
            echo "</pre></details>\n";
            echo "</div>\n";
            
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; border-radius: 5px;'>\n";
            echo "<h3>❌ Fehlgeschlagen</h3>\n";
            echo "<p><strong>Fehler:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
            
            if (isset($result['raw_output'])) {
                echo "<details><summary><strong>Debug-Output anzeigen</strong></summary>\n";
                echo "<pre style='background: #f5f5f5; padding: 10px;'>";
                echo htmlspecialchars($result['raw_output']);
                echo "</pre></details>\n";
            }
            echo "</div>\n";
        }
        
        return $result;
    }
    
    /**
     * System-Check für Dependencies
     */
    public function checkSystem() {
        $checks = [
            'python_script' => file_exists($this->pythonScript),
            'python3' => $this->checkPython('python3'),
            'python' => $this->checkPython('python'),
            'proc_open' => function_exists('proc_open'),
            'shell_exec' => function_exists('shell_exec'),
            'curl' => function_exists('curl_init'),
        ];
        
        return $checks;
    }
    
    private function checkPython($pythonCmd) {
        $output = shell_exec("$pythonCmd --version 2>&1");
        return $output && strpos($output, 'Python') !== false;
    }
    
    /**
     * Dependencies installieren (für Development)
     */
    public function installDependencies() {
        $commands = [
            'pip3 install requests',
            'pip install requests',
            'python3 -m pip install requests',
            'python -m pip install requests'
        ];
        
        $results = [];
        
        foreach ($commands as $cmd) {
            $output = shell_exec("$cmd 2>&1");
            $results[$cmd] = $output;
            
            // Erfolgreich? Dann stoppen
            if ($output && strpos($output, 'Successfully installed') !== false) {
                break;
            }
        }
        
        return $results;
    }
}
?>
