<?php
/**
 * Professioneller YouTube Transcript Service
 * Nutzt erweiterte Python-Libraries wie downsub & Co.
 */

class YouTubeProfessionalService {
    
    private $pythonScript;
    private $nuclearScript;
    private $timeout;
    private $debug;
    private $fallbackMethods;
    
    public function __construct($timeout = 120, $debug = true) {
        $this->pythonScript = __DIR__ . '/youtube_advanced_extractor.py';
        $this->nuclearScript = __DIR__ . '/youtube_nuclear_option.py';  // ☢️ NUCLEAR OPTION!
        $this->timeout = $timeout;
        $this->debug = $debug;
        
        // Fallback-Methoden falls Python fehlschlägt
        $this->fallbackMethods = [
            'nuclear_option',           // ☢️ NUCLEAR FIRST!
            'direct_php_extraction',
            'web_scraping_method',
            'api_proxy_method'
        ];
    }
    
    /**
     * Hauptmethode: Transcript extrahieren
     */
    public function getTranscript($videoUrl) {
        if ($this->debug) {
            error_log("🎯 YouTube Professional Service: $videoUrl");
        }
        
        // 1. Versuche erweiterte Python-Methoden
        $result = $this->tryPythonMethods($videoUrl);
        if ($result['success']) {
            if ($this->debug) {
                error_log("✅ Python-Methode erfolgreich: " . $result['source']);
            }
            return $result;
        }
        
        if ($this->debug) {
            error_log("❌ Python-Methoden fehlgeschlagen, versuche PHP-Fallbacks");
        }
        
        // 2. Fallback: PHP-Methoden
        foreach ($this->fallbackMethods as $method) {
            try {
                if ($this->debug) {
                    error_log("🔄 Versuche Fallback-Methode: $method");
                }
                
                // NUCLEAR OPTION ist speziell
                if ($method === 'nuclear_option') {
                    $result = $this->$method($videoUrl);
                    if ($result['success']) {
                        return $result;
                    }
                    continue;
                }
                
                $transcript = $this->$method($videoUrl);
                if ($transcript && strlen($transcript) > 100) {
                    if ($this->debug) {
                        error_log("✅ PHP-Methode erfolgreich: $method");
                    }
                    
                    return [
                        'success' => true,
                        'transcript' => $this->cleanTranscript($transcript),
                        'source' => $method,
                        'length' => strlen($transcript)
                    ];
                }
            } catch (Exception $e) {
                if ($this->debug) {
                    error_log("❌ PHP-Methode $method: " . $e->getMessage());
                }
                continue;
            }
        }
        
        // 🚀 ULTIMATE FALLBACK: Wenn YouTube komplett blockiert
        if ($this->debug) {
            error_log("🚀 Aktiviere ULTIMATE FALLBACK...");
        }
        
        require_once __DIR__ . '/youtube_ultimate_fallback.php';
        $fallback = new YouTubeUltimateFallback($this->debug);
        
        try {
            $fallbackResult = $fallback->getTranscript($videoUrl);
            if ($fallbackResult['success']) {
                if ($this->debug) {
                    error_log("🚀 ULTIMATE FALLBACK SUCCESS: " . $fallbackResult['source']);
                }
                return $fallbackResult;
            }
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("❌ ULTIMATE FALLBACK Error: " . $e->getMessage());
            }
        }
        
        return [
            'success' => false, 
            'error' => 'Alle Methoden fehlgeschlagen - YouTube komplett blockiert (auch Fallback-Strategien)',
            'details' => $result['details'] ?? [],
            'fallback_attempted' => true
        ];
    }
    
    /**
     * ☢️ NUCLEAR OPTION - Die ultimative Waffe!
     */
    private function nuclear_option($videoUrl) {
        if (!file_exists($this->nuclearScript)) {
            throw new Exception("☢️ NUCLEAR SCRIPT nicht gefunden: " . $this->nuclearScript);
        }
        
        if ($this->debug) {
            error_log("☢️ NUCLEAR OPTION wird aktiviert...");
        }
        
        $videoUrl = escapeshellarg($videoUrl);
        $scriptPath = escapeshellarg($this->nuclearScript);
        
        // Windows/Linux kompatibel
        $pythonCmd = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
        $command = "$pythonCmd $scriptPath $videoUrl 2>&1";
        
        if ($this->debug) {
            error_log("☢️ NUCLEAR Command: $command");
        }
        
        $startTime = microtime(true);
        $output = shell_exec($command);
        $duration = microtime(true) - $startTime;
        
        if ($this->debug) {
            error_log("☢️ NUCLEAR Output (nach {$duration}s): " . substr($output, 0, 200) . "...");
        }
        
        if (empty($output)) {
            throw new Exception("☢️ NUCLEAR OPTION: Leerer Output");
        }
        
        // Parse JSON-Output (letzte Zeile)
        $lines = array_filter(explode("\n", trim($output)));
        $jsonLine = end($lines);
        
        $result = json_decode($jsonLine, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("☢️ NUCLEAR OPTION: Invalid JSON - " . json_last_error_msg());
        }
        
        if (!$result['success']) {
            throw new Exception("☢️ NUCLEAR OPTION FAILED: " . $result['error']);
        }
        
        if ($this->debug) {
            error_log("☢️ NUCLEAR SUCCESS: " . $result['source'] . " (" . $result['length'] . " Zeichen)");
        }
        
        return [
            'success' => true,
            'transcript' => $result['transcript'],
            'source' => '☢️ NUCLEAR_' . $result['source'],
            'length' => $result['length'],
            'method' => 'nuclear_option',
            'duration' => $duration
        ];
    }
    
    /**
     * Erweiterte Python-Methoden
     */
    private function tryPythonMethods($videoUrl) {
        if (!file_exists($this->pythonScript)) {
            return ['success' => false, 'error' => 'Python-Script nicht gefunden'];
        }
        
        // Python-Command mit erweiterten Optionen
        $command = $this->buildAdvancedPythonCommand($videoUrl);
        
        if ($this->debug) {
            error_log("🐍 Python-Command: $command");
        }
        
        // Script mit erweitertem Timeout ausführen
        $output = $this->executeWithTimeout($command, $this->timeout);
        
        if ($this->debug) {
            error_log("🐍 Python-Output: " . substr($output, 0, 500) . "...");
        }
        
        return $this->parseAdvancedOutput($output);
    }
    
    /**
     * Erweiterten Python-Command bauen
     */
    private function buildAdvancedPythonCommand($videoUrl) {
        $escapedUrl = escapeshellarg($videoUrl);
        $escapedScript = escapeshellarg($this->pythonScript);
        
        // Python mit erweiterten Optionen
        $pythonCmds = [
            'python3 -u',  // Unbuffered output
            '/usr/bin/python3 -u',
            'python -u'
        ];
        
        foreach ($pythonCmds as $pythonCmd) {
            $testCmd = explode(' ', $pythonCmd)[0] . " --version 2>/dev/null";
            $testOutput = shell_exec($testCmd);
            
            if ($testOutput && strpos($testOutput, 'Python') !== false) {
                return "$pythonCmd $escapedScript $escapedUrl 2>&1";
            }
        }
        
        return "python3 $escapedScript $escapedUrl 2>&1";
    }
    
    /**
     * Command mit Timeout ausführen
     */
    private function executeWithTimeout($command, $timeout) {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            throw new Exception("Konnte Python-Process nicht starten");
        }
        
        // Non-blocking pipes
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
            
            if (time() - $start > $timeout) {
                proc_terminate($process);
                proc_close($process);
                throw new Exception("Python-Script Timeout nach {$timeout} Sekunden");
            }
            
            $output .= stream_get_contents($pipes[1]);
            $errorOutput .= stream_get_contents($pipes[2]);
            
            usleep(100000); // 100ms
        }
        
        $output .= stream_get_contents($pipes[1]);
        $errorOutput .= stream_get_contents($pipes[2]);
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        
        if ($this->debug && $errorOutput) {
            error_log("🐍 Python STDERR: " . substr($errorOutput, 0, 1000));
        }
        
        return $output . "\n--- STDERR ---\n" . $errorOutput;
    }
    
    /**
     * Erweiterte Output-Parsing
     */
    private function parseAdvancedOutput($output) {
        $lines = explode("\n", trim($output));
        
        // Suche nach JSON in der Ausgabe
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if (empty($line) || $line === '--- STDERR ---') continue;
            
            if ($line[0] === '{') {
                $result = json_decode($line, true);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        
        return [
            'success' => false,
            'error' => 'Ungültige Python-Ausgabe',
            'raw_output' => substr($output, 0, 2000)
        ];
    }
    
    /**
     * PHP-Fallback: Direkte Extraktion
     */
    private function direct_php_extraction($videoUrl) {
        $videoId = $this->extractVideoId($videoUrl);
        if (!$videoId) {
            throw new Exception('Ungültige YouTube-URL');
        }
        
        // Erweiterte Caption-API-Versuche
        $apiUrls = [
            "https://www.youtube.com/api/timedtext?v={$videoId}&lang=de&fmt=json3",
            "https://www.youtube.com/api/timedtext?v={$videoId}&lang=en&fmt=json3",
            "https://www.youtube.com/api/timedtext?v={$videoId}&lang=de&fmt=srv3",
            "https://www.youtube.com/api/timedtext?v={$videoId}&lang=en&fmt=srv3",
            "https://video.google.com/timedtext?v={$videoId}&lang=de",
            "https://video.google.com/timedtext?v={$videoId}&lang=en"
        ];
        
        foreach ($apiUrls as $url) {
            $content = $this->makeAdvancedRequest($url);
            if ($content && strlen($content) > 100) {
                if (strpos($url, 'json3') !== false) {
                    return $this->parseJSON3($content);
                } else {
                    return $this->parseXML($content);
                }
            }
        }
        
        throw new Exception('Direkte API-Extraktion fehlgeschlagen');
    }
    
    /**
     * PHP-Fallback: Web-Scraping
     */
    private function web_scraping_method($videoUrl) {
        $html = $this->makeAdvancedRequest($videoUrl);
        if (!$html) {
            throw new Exception('YouTube-Seite nicht erreichbar');
        }
        
        // Erweiterte Pattern
        $patterns = [
            '/"captions":\{"playerCaptionsTracklistRenderer":\{"captionTracks":\[(.*?)\]/',
            '/"captionTracks":\[(.*?)\]/',
            '/ytInitialPlayerResponse.*?"captionTracks":\[(.*?)\]/',
            '/"playerCaptionsRenderer".*?"captionTracks":\[(.*?)\]/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $captionData = '[' . $matches[1] . ']';
                $captions = json_decode($captionData, true);
                
                if ($captions) {
                    foreach ($captions as $caption) {
                        if (isset($caption['baseUrl'])) {
                            $transcript = $this->makeAdvancedRequest($caption['baseUrl']);
                            if ($transcript) {
                                return $this->parseXML($transcript);
                            }
                        }
                    }
                }
            }
        }
        
        throw new Exception('Web-Scraping fehlgeschlagen');
    }
    
    /**
     * PHP-Fallback: API-Proxy-Methode
     */
    private function api_proxy_method($videoUrl) {
        $videoId = $this->extractVideoId($videoUrl);
        
        // Verschiedene Proxy-APIs versuchen
        $proxyApis = [
            "https://youtube-transcript3.p.rapidapi.com/youtube/transcript?video_id={$videoId}",
            "https://youtube-v31.p.rapidapi.com/captions?part=snippet&videoId={$videoId}",
        ];
        
        foreach ($proxyApis as $apiUrl) {
            try {
                $headers = [
                    'X-RapidAPI-Host: ' . parse_url($apiUrl, PHP_URL_HOST),
                    'X-RapidAPI-Key: YOUR_RAPIDAPI_KEY_HERE' // Würde echten Key benötigen
                ];
                
                $content = $this->makeAdvancedRequest($apiUrl, $headers);
                if ($content) {
                    $data = json_decode($content, true);
                    if ($data && isset($data['transcript'])) {
                        return $data['transcript'];
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        throw new Exception('API-Proxy-Methode fehlgeschlagen');
    }
    
    /**
     * Erweiterte HTTP-Requests
     */
    private function makeAdvancedRequest($url, $additionalHeaders = []) {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];
        
        $headers = [
            'User-Agent: ' . $userAgents[array_rand($userAgents)],
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: de,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding: gzip, deflate, br',
            'DNT: 1',
            'Connection: close',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ];
        
        $headers = array_merge($headers, $additionalHeaders);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 15,
                'ignore_errors' => true
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        
        if ($this->debug && isset($http_response_header)) {
            error_log("🌐 HTTP Request: " . $http_response_header[0] . " für " . parse_url($url, PHP_URL_HOST));
        }
        
        return $result;
    }
    
    /**
     * Hilfsfunktionen
     */
    private function extractVideoId($url) {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/',
            '/youtube\.com\/v\/([^&\n?#]+)/',
            '/youtube\.com\/watch\?.*v=([^&\n?#]+)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    private function parseJSON3($content) {
        $data = json_decode($content, true);
        if (!$data || !isset($data['events'])) {
            return null;
        }
        
        $transcript = '';
        foreach ($data['events'] as $event) {
            if (isset($event['segs'])) {
                foreach ($event['segs'] as $seg) {
                    if (isset($seg['utf8'])) {
                        $transcript .= $seg['utf8'] . ' ';
                    }
                }
            }
        }
        
        return $transcript;
    }
    
    private function parseXML($content) {
        $xml = @simplexml_load_string($content);
        if ($xml) {
            $transcript = '';
            foreach ($xml->text as $text) {
                $transcript .= (string)$text . ' ';
            }
            return $transcript;
        }
        
        // RegEx-Fallback
        if (preg_match_all('/<text[^>]*>(.*?)<\/text>/s', $content, $matches)) {
            return implode(' ', $matches[1]);
        }
        
        return null;
    }
    
    private function cleanTranscript($transcript) {
        $transcript = html_entity_decode($transcript, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $transcript = preg_replace('/\[?\d{1,2}:\d{2}(?::\d{2})?\]?/', '', $transcript);
        $transcript = preg_replace('/\s+/', ' ', $transcript);
        
        return trim($transcript);
    }
    
    /**
     * Test-Funktion
     */
    public function testTranscript($videoUrl) {
        echo "<h2>🚀 Professioneller YouTube Test</h2>\n";
        echo "<p><strong>Video:</strong> " . htmlspecialchars($videoUrl) . "</p>\n";
        
        $startTime = microtime(true);
        $result = $this->getTranscript($videoUrl);
        $duration = microtime(true) - $startTime;
        
        echo "<p><strong>Dauer:</strong> " . round($duration, 2) . " Sekunden</p>\n";
        
        if ($result['success']) {
            echo "<div style='background: #e8f5e8; padding: 15px; border: 2px solid #4caf50; border-radius: 5px;'>\n";
            echo "<h3>🎉 ERFOLG! Professionelle Methode funktioniert!</h3>\n";
            echo "<p><strong>Quelle:</strong> {$result['source']}</p>\n";
            echo "<p><strong>Länge:</strong> {$result['length']} Zeichen</p>\n";
            echo "<details><summary><strong>Transcript anzeigen</strong></summary>\n";
            echo "<pre style='max-height: 400px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
            echo htmlspecialchars(substr($result['transcript'], 0, 2000));
            if (strlen($result['transcript']) > 2000) {
                echo "\n\n... (verkürzt)";
            }
            echo "</pre></details>\n";
            echo "</div>\n";
            
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border: 2px solid #f44336; border-radius: 5px;'>\n";
            echo "<h3>❌ Alle professionellen Methoden fehlgeschlagen</h3>\n";
            echo "<p><strong>Fehler:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
            
            if (isset($result['details'])) {
                echo "<details><summary><strong>Details anzeigen</strong></summary>\n";
                echo "<pre>" . htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT)) . "</pre>\n";
                echo "</details>\n";
            }
            echo "</div>\n";
        }
        
        return $result;
    }
}
?>
