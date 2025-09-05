<?php
/**
 * Pure PHP YouTube Transcript Service
 * Keine Python-Dependencies nötig
 */

class YouTubeTranscriptPHP {
    
    private $userAgent;
    private $timeout;
    
    public function __construct() {
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $this->timeout = 30;
    }
    
    /**
     * Transcript von YouTube-Video extrahieren
     */
    public function getTranscript($videoUrl) {
        $videoId = $this->extractVideoId($videoUrl);
        if (!$videoId) {
            return ['success' => false, 'error' => 'Ungültige YouTube-URL'];
        }
        
        try {
            // 1. Versuche direkte Caption API
            $transcript = $this->tryDirectAPI($videoId);
            if ($transcript) {
                return [
                    'success' => true,
                    'transcript' => $transcript,
                    'source' => 'direct_api',
                    'length' => strlen($transcript)
                ];
            }
            
            // 2. HTML-Extraktion
            $transcript = $this->tryHTMLExtraction($videoId, $videoUrl);
            if ($transcript) {
                return [
                    'success' => true,
                    'transcript' => $transcript,
                    'source' => 'html_extraction',
                    'length' => strlen($transcript)
                ];
            }
            
            return ['success' => false, 'error' => 'Kein Transcript gefunden'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Direkte YouTube Caption API versuchen
     */
    private function tryDirectAPI($videoId) {
        $languages = ['de', 'en', 'auto'];
        $formats = ['json3', 'srv3', 'srv1'];
        
        foreach ($languages as $lang) {
            foreach ($formats as $fmt) {
                $url = "https://www.youtube.com/api/timedtext?lang={$lang}&v={$videoId}&fmt={$fmt}";
                
                $content = $this->makeRequest($url);
                if ($content && strlen($content) > 100) {
                    if ($fmt === 'json3') {
                        return $this->parseJSON3($content);
                    } else {
                        return $this->parseXML($content);
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * HTML-Extraktion von YouTube-Seite
     */
    private function tryHTMLExtraction($videoId, $videoUrl) {
        // YouTube-HTML laden
        $html = $this->makeRequest($videoUrl);
        if (!$html) {
            throw new Exception('Konnte YouTube-Seite nicht laden');
        }
        
        // Caption-Tracks suchen
        $pattern = '/"captions":\{"playerCaptionsTracklistRenderer":\{"captionTracks":\[(.*?)\]/';
        
        if (!preg_match($pattern, $html, $matches)) {
            throw new Exception('Keine Caption-Tracks im HTML gefunden');
        }
        
        $captionData = '[' . $matches[1] . ']';
        $captions = json_decode($captionData, true);
        
        if (!$captions) {
            throw new Exception('Caption-JSON konnte nicht geparst werden');
        }
        
        // Beste Sprache finden (Deutsch > Englisch > Andere)
        $preferredLanguages = ['de', 'de-DE', 'en', 'en-US'];
        $selectedCaption = null;
        
        foreach ($preferredLanguages as $prefLang) {
            foreach ($captions as $caption) {
                if (isset($caption['languageCode']) && 
                    strpos($caption['languageCode'], $prefLang) === 0) {
                    $selectedCaption = $caption;
                    break 2;
                }
            }
        }
        
        // Fallback: Erster verfügbarer Caption
        if (!$selectedCaption && !empty($captions)) {
            $selectedCaption = $captions[0];
        }
        
        if (!$selectedCaption || !isset($selectedCaption['baseUrl'])) {
            throw new Exception('Keine verwendbare Caption-BaseURL gefunden');
        }
        
        // Transcript von BaseURL laden
        $baseUrl = $selectedCaption['baseUrl'];
        $transcriptXML = $this->makeRequest($baseUrl, [
            'Referer: https://www.youtube.com/',
            'Origin: https://www.youtube.com',
            'Accept: application/xml,text/xml,*/*'
        ]);
        
        if (!$transcriptXML) {
            throw new Exception('Konnte Transcript von BaseURL nicht laden');
        }
        
        return $this->parseXML($transcriptXML);
    }
    
    /**
     * HTTP-Request mit Headers
     */
    private function makeRequest($url, $additionalHeaders = []) {
        $headers = [
            'User-Agent: ' . $this->userAgent,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: de,en-US;q=0.7,en;q=0.3',
            'DNT: 1',
            'Connection: close'
        ];
        
        $headers = array_merge($headers, $additionalHeaders);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => $this->timeout,
                'ignore_errors' => true
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        
        // Debug: HTTP-Response-Code prüfen
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0];
            if (strpos($statusLine, '200') === false) {
                error_log("YouTube Request failed: $statusLine für URL: $url");
                return false;
            }
        }
        
        return $result;
    }
    
    /**
     * JSON3-Format parsen
     */
    private function parseJSON3($jsonContent) {
        $data = json_decode($jsonContent, true);
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
        
        return $this->cleanTranscript($transcript);
    }
    
    /**
     * XML-Format parsen
     */
    private function parseXML($xmlContent) {
        // Versuche SimpleXML
        $xml = @simplexml_load_string($xmlContent);
        if ($xml) {
            $transcript = '';
            foreach ($xml->text as $text) {
                $transcript .= (string)$text . ' ';
            }
            return $this->cleanTranscript($transcript);
        }
        
        // Fallback: RegEx-basiertes Parsing
        if (preg_match_all('/<text[^>]*>(.*?)<\/text>/s', $xmlContent, $matches)) {
            $transcript = implode(' ', $matches[1]);
            return $this->cleanTranscript($transcript);
        }
        
        return null;
    }
    
    /**
     * Transcript bereinigen
     */
    private function cleanTranscript($transcript) {
        if (!$transcript) return '';
        
        // HTML-Entities dekodieren
        $transcript = html_entity_decode($transcript, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Timestamps entfernen
        $transcript = preg_replace('/\[?\d{1,2}:\d{2}(?::\d{2})?\]?/', '', $transcript);
        $transcript = preg_replace('/\(\d{1,2}:\d{2}(?::\d{2})?\)/', '', $transcript);
        
        // Speaker-Labels entfernen
        $transcript = preg_replace('/^[A-Za-z\s]+\d*:\s*/m', '', $transcript);
        
        // Mehrfache Leerzeichen reduzieren
        $transcript = preg_replace('/\s+/', ' ', $transcript);
        $transcript = preg_replace('/\n+/', '\n', $transcript);
        
        return trim($transcript);
    }
    
    /**
     * Video-ID aus URL extrahieren
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
    
    /**
     * Test-Funktion
     */
    public function testTranscript($videoUrl) {
        echo "<h2>🧪 Pure PHP YouTube Transcript Test</h2>\n";
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
            echo "<details><summary><strong>Transcript anzeigen</strong></summary>\n";
            echo "<pre style='max-height: 400px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
            echo htmlspecialchars(substr($result['transcript'], 0, 1500));
            if (strlen($result['transcript']) > 1500) {
                echo "\n\n... (verkürzt, insgesamt {$result['length']} Zeichen)";
            }
            echo "</pre></details>\n";
            echo "</div>\n";
            
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; border-radius: 5px;'>\n";
            echo "<h3>❌ Fehlgeschlagen</h3>\n";
            echo "<p><strong>Fehler:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
            echo "</div>\n";
        }
        
        return $result;
    }
}
?>
