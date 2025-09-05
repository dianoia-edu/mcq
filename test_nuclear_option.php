<?php
/**
 * Test für NUCLEAR OPTION Python-Script
 */

require_once 'includes/youtube_professional_service.php';

// Nuclear Service mit dem neuen Python-Script
class YouTubeNuclearService extends YouTubeProfessionalService {
    
    public function __construct($timeout = 300, $debug = true) {
        parent::__construct($timeout, $debug);
        $this->pythonScript = __DIR__ . '/includes/youtube_nuclear_option.py';
    }
    
    /**
     * Test das Nuclear Python-Script direkt
     */
    public function testNuclearPython($videoUrl) {
        echo "<h2>☢️ NUCLEAR OPTION Test</h2>\n";
        echo "<p><strong>Video:</strong> " . htmlspecialchars($videoUrl) . "</p>\n";
        
        if (!file_exists($this->pythonScript)) {
            echo "<div style='background: #ffebee; padding: 15px; border: 2px solid #f44336; border-radius: 5px;'>\n";
            echo "<h3>❌ Python-Script nicht gefunden!</h3>\n";
            echo "<p><strong>Erwartet:</strong> " . $this->pythonScript . "</p>\n";
            echo "</div>\n";
            return false;
        }
        
        echo "<p>✅ Python-Script gefunden: " . $this->pythonScript . "</p>\n";
        
        // Teste Python-Verfügbarkeit
        $pythonTest = shell_exec('python3 --version 2>&1');
        echo "<p><strong>Python3:</strong> " . ($pythonTest ? htmlspecialchars($pythonTest) : '❌ Nicht verfügbar') . "</p>\n";
        
        // Baue Command
        $escapedUrl = escapeshellarg($videoUrl);
        $escapedScript = escapeshellarg($this->pythonScript);
        $command = "python3 $escapedScript $escapedUrl 2>&1";
        
        echo "<p><strong>Command:</strong> <code>$command</code></p>\n";
        
        echo "<h3>🚀 Führe NUCLEAR OPTION aus...</h3>\n";
        echo "<div style='background: #1a1a1a; color: #0f0; padding: 15px; font-family: monospace; border-radius: 5px;'>\n";
        echo "<div id='output'>Warte auf Output...</div>\n";
        echo "</div>\n";
        
        // Führe Command aus und zeige Live-Output
        echo "<script>\n";
        echo "var outputDiv = document.getElementById('output');\n";
        echo "outputDiv.innerHTML = 'Starte NUCLEAR OPTION...';\n";
        echo "</script>\n";
        
        $startTime = microtime(true);
        
        // Verwende proc_open für Live-Output
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
                
                if (time() - $start > $this->timeout) {
                    proc_terminate($process);
                    proc_close($process);
                    echo "<script>outputDiv.innerHTML += '\\n☢️ TIMEOUT nach {$this->timeout} Sekunden!';</script>\n";
                    return false;
                }
                
                $newOutput = stream_get_contents($pipes[1]);
                $newError = stream_get_contents($pipes[2]);
                
                if ($newOutput) {
                    $output .= $newOutput;
                    $escapedOutput = json_encode($newOutput);
                    echo "<script>outputDiv.innerHTML += $escapedOutput;</script>\n";
                    flush();
                }
                
                if ($newError) {
                    $errorOutput .= $newError;
                    $escapedError = json_encode("STDERR: " . $newError);
                    echo "<script>outputDiv.innerHTML += $escapedError;</script>\n";
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
            echo "<script>outputDiv.innerHTML = '❌ Konnte Python-Process nicht starten';</script>\n";
            return false;
        }
        
        $duration = microtime(true) - $startTime;
        
        echo "<script>outputDiv.innerHTML += '\\n\\n⏱️ Dauer: " . round($duration, 2) . " Sekunden';</script>\n";
        
        // Parse das Ergebnis
        $fullOutput = $output . "\n--- STDERR ---\n" . $errorOutput;
        
        echo "<h3>📋 Ergebnis-Analyse</h3>\n";
        
        // Suche nach JSON-Output
        $lines = explode("\n", trim($output));
        $jsonResult = null;
        
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if (!empty($line) && $line[0] === '{') {
                $jsonResult = json_decode($line, true);
                if ($jsonResult !== null) {
                    break;
                }
            }
        }
        
        if ($jsonResult) {
            if ($jsonResult['success']) {
                echo "<div style='background: #e8f5e8; padding: 20px; border: 2px solid #4caf50; border-radius: 8px;'>\n";
                echo "<h3>☢️ NUCLEAR SUCCESS!</h3>\n";
                echo "<p><strong>Erfolgreiche Methode:</strong> {$jsonResult['source']}</p>\n";
                echo "<p><strong>Transcript-Länge:</strong> " . number_format($jsonResult['length']) . " Zeichen</p>\n";
                echo "<details><summary><strong>Vollständiger Transcript</strong></summary>\n";
                echo "<pre style='max-height: 400px; overflow-y: auto;'>" . htmlspecialchars($jsonResult['transcript']) . "</pre>\n";
                echo "</details>\n";
                echo "</div>\n";
                
                return $jsonResult;
            } else {
                echo "<div style='background: #ffebee; padding: 20px; border: 2px solid #f44336; border-radius: 8px;'>\n";
                echo "<h3>☢️ NUCLEAR FAILED</h3>\n";
                echo "<p><strong>Fehler:</strong> " . htmlspecialchars($jsonResult['error']) . "</p>\n";
                if (isset($jsonResult['details'])) {
                    echo "<details><summary><strong>Detaillierte Fehler</strong></summary>\n";
                    echo "<pre>" . htmlspecialchars(json_encode($jsonResult['details'], JSON_PRETTY_PRINT)) . "</pre>\n";
                    echo "</details>\n";
                }
                echo "</div>\n";
            }
        } else {
            echo "<div style='background: #fff3e0; padding: 20px; border: 2px solid #ff9800; border-radius: 8px;'>\n";
            echo "<h3>⚠️ Unklares Ergebnis</h3>\n";
            echo "<p>Kein gültiges JSON gefunden im Output.</p>\n";
            echo "<details><summary><strong>Roher Output</strong></summary>\n";
            echo "<pre style='max-height: 400px; overflow-y: auto;'>" . htmlspecialchars($fullOutput) . "</pre>\n";
            echo "</details>\n";
            echo "</div>\n";
        }
        
        return false;
    }
}

$service = new YouTubeNuclearService(300, true); // 5 Min Timeout

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>☢️ NUCLEAR OPTION Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #0f0f0f; color: #fff; }
        .hero { 
            background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%); 
            padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align: center; 
            box-shadow: 0 10px 30px rgba(255, 8, 68, 0.3);
        }
        .test-box { margin: 20px 0; padding: 25px; border-radius: 10px; border: 2px solid #333; background: #1a1a1a; }
        button { 
            padding: 15px 30px; font-size: 18px; cursor: pointer; margin: 10px; 
            border: none; border-radius: 8px; font-weight: bold; transition: all 0.3s;
            background: linear-gradient(45deg, #ff0844, #ffb199); color: white;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 7px 20px rgba(255, 8, 68, 0.6); }
        input[type="url"] { 
            width: 100%; padding: 15px; margin: 15px 0; border: 2px solid #444; 
            border-radius: 8px; background: #2a2a2a; color: #fff; font-size: 16px;
        }
        pre { background: #111; padding: 15px; overflow-x: auto; border-radius: 8px; border-left: 4px solid #ff0844; }
        .info-box { background: #1a2a1a; border-left: 4px solid #4caf50; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>

<div class="hero">
    <h1>☢️ NUCLEAR OPTION Test</h1>
    <p><strong>6 ultimative Methoden gegen YouTube-Bot-Detection!</strong></p>
</div>

<div class="test-box">
    <h2>🚀 NUCLEAR Python-Script Test</h2>
    <form method="post">
        <label><strong>YouTube-URL:</strong></label>
        <input type="url" name="test_url" value="<?= htmlspecialchars($_POST['test_url'] ?? 'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s') ?>" placeholder="https://www.youtube.com/watch?v=...">
        <button type="submit" name="test_nuclear">☢️ NUCLEAR OPTION STARTEN</button>
    </form>
    
    <div class="info-box">
        <h3>☢️ NUCLEAR Methoden:</h3>
        <ol>
            <li><strong>🎯 Downsub-Clone:</strong> Exakte API-Nachbildung</li>
            <li><strong>🤖 Perfect Browser:</strong> Undetected Chrome</li>
            <li><strong>💥 API-Bruteforce:</strong> 500+ Endpoint-Kombinationen</li>
            <li><strong>📱 Mobile-App-Clone:</strong> YouTube-App-Simulation</li>
            <li><strong>🔐 Session-Stealing:</strong> Cookie-Diebstahl</li>
            <li><strong>🔄 Proxy-Chain:</strong> IP-Spoofing</li>
        </ol>
    </div>
</div>

<?php
if (isset($_POST['test_nuclear']) && !empty($_POST['test_url'])) {
    echo "<div class='test-box'>\n";
    $result = $service->testNuclearPython($_POST['test_url']);
    echo "</div>\n";
    
    if ($result) {
        echo "<div class='test-box' style='background: #0d4f0d; border-color: #4caf50;'>\n";
        echo "<h2>🎉 INTEGRATION BEREIT!</h2>\n";
        echo "<p>Die NUCLEAR OPTION funktioniert! <strong>Kann sofort implementiert werden!</strong></p>\n";
        
        echo "<h3>📝 Integration in generate_test.php:</h3>\n";
        echo "<pre><code>";
        echo htmlspecialchars('
require_once __DIR__ . "/../includes/youtube_nuclear_option.py";

class YouTubeNuclearService extends YouTubeProfessionalService {
    public function __construct() {
        parent::__construct(300, true);
        $this->pythonScript = __DIR__ . "/../includes/youtube_nuclear_option.py";
    }
}

if (!empty($_POST["youtube_url"])) {
    $nuclearService = new YouTubeNuclearService();
    $result = $nuclearService->getTranscript($_POST["youtube_url"]);
    
    if ($result["success"]) {
        $combinedContent .= $result["transcript"] . "\n\n";
        error_log("☢️ NUCLEAR SUCCESS: " . $result["source"]);
    } else {
        throw new Exception("☢️ NUCLEAR FAILED: " . $result["error"]);
    }
}');
        echo "</code></pre>\n";
        echo "</div>\n";
    }
}
?>

<div class="test-box">
    <h2>🔬 Downsub JavaScript-Analyse</h2>
    <p>Da downsub nur HTML zurückgibt, nutzen sie <strong>Frontend-JavaScript</strong> für API-Calls!</p>
    
    <h3>🕵️ Nächste Schritte:</h3>
    <ol>
        <li><strong>Browser DevTools:</strong> Öffne downsub.com in Chrome</li>
        <li><strong>Network Tab:</strong> Schaue welche AJAX-Calls gemacht werden</li>
        <li><strong>JavaScript-Code:</strong> Analysiere app.js oder main.js</li>
        <li><strong>API-Endpoints:</strong> Finde die echten Backend-URLs</li>
    </ol>
    
    <div class="info-box">
        <h3>💡 Erkenntnisse:</h3>
        <ul>
            <li>✅ Downsub antwortet nur mit HTML (kein direkter API-Zugang)</li>
            <li>✅ JavaScript macht die echten API-Calls</li>
            <li>❌ Alle Standard-YouTube-APIs blockiert</li>
            <li>☢️ NUCLEAR OPTION ist der einzige Weg!</li>
        </ul>
    </div>
</div>

<div class="test-box">
    <h2>🛠️ Manuelle Installation</h2>
    <p>Falls Dependencies fehlen:</p>
    <pre><code># Python Dependencies
pip3 install requests selenium undetected-chromedriver

# Chrome/Chromium für Selenium
apt-get update
apt-get install chromium-browser chromium-chromedriver

# Test
python3 includes/youtube_nuclear_option.py "https://www.youtube.com/watch?v=dQw4w9WgXcQ"</code></pre>
</div>

</body>
</html>
