<?php
/**
 * YouTube Transcript Fetcher
 * Automatisierte Abfrage von Transcript-Services
 */

class YouTubeTranscriptFetcher {
    
    public function getTranscript($videoUrl) {
        $videoId = $this->extractVideoId($videoUrl);
        if (!$videoId) {
            throw new Exception("Ungültige YouTube-URL");
        }
        
        $services = [
            'youtube_api' => [$this, 'fetchFromYouTubeAPI'],
            'downsub' => [$this, 'fetchFromDownsub'],
            'youtubetranscript' => [$this, 'fetchFromYouTubeTranscript'],
            'savesubs' => [$this, 'fetchFromSaveSubs'],
            'youtube_direct' => [$this, 'fetchFromYouTubeDirect']
        ];
        
        $results = [];
        
        foreach ($services as $serviceName => $method) {
            try {
                error_log("Versuche Service: $serviceName für Video: $videoId");
                $transcript = call_user_func($method, $videoId, $videoUrl);
                
                if ($transcript && strlen(trim($transcript)) > 100) {
                    $cleanTranscript = $this->cleanTranscript($transcript);
                    error_log("Service $serviceName erfolgreich: " . strlen($cleanTranscript) . " Zeichen");
                    return [
                        'success' => true,
                        'transcript' => $cleanTranscript,
                        'source' => $serviceName,
                        'length' => strlen($cleanTranscript)
                    ];
                }
            } catch (Exception $e) {
                error_log("Service $serviceName fehlgeschlagen: " . $e->getMessage());
                $results[$serviceName] = $e->getMessage();
            }
        }
        
        return [
            'success' => false,
            'transcript' => null,
            'errors' => $results,
            'message' => 'Kein Service konnte Transcript extrahieren'
        ];
    }
    
    private function fetchFromYouTubeAPI($videoId, $videoUrl) {
        // Direkte YouTube API für Captions
        $urls = [
            "https://www.youtube.com/api/timedtext?lang=de&v={$videoId}&fmt=json3",
            "https://www.youtube.com/api/timedtext?lang=en&v={$videoId}&fmt=json3",
            "https://www.youtube.com/api/timedtext?lang=de&v={$videoId}",
            "https://www.youtube.com/api/timedtext?lang=en&v={$videoId}"
        ];
        
        foreach ($urls as $url) {
            $response = $this->makeRequest($url);
            if ($response) {
                if (strpos($response, '{"events":') !== false) {
                    // JSON3 Format
                    return $this->parseJson3Transcript($response);
                } else {
                    // XML Format
                    return $this->parseXmlTranscript($response);
                }
            }
        }
        
        throw new Exception("YouTube API liefert kein Transcript");
    }
    
    private function fetchFromDownsub($videoId, $videoUrl) {
        // downsub.com automatisiert aufrufen
        $apiUrl = "https://downsub.com/api/download";
        
        $postData = [
            'url' => $videoUrl,
            'format' => 'txt'
        ];
        
        $response = $this->makeRequest($apiUrl, $postData);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['download_url'])) {
                return $this->makeRequest($data['download_url']);
            }
        }
        
        throw new Exception("Downsub API nicht verfügbar");
    }
    
    private function fetchFromYouTubeTranscript($videoId, $videoUrl) {
        // youtubetranscript.com API
        $apiUrl = "https://youtubetranscript.com/api/transcript";
        
        $postData = [
            'video_id' => $videoId,
            'lang' => 'de'
        ];
        
        $response = $this->makeRequest($apiUrl, $postData);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['transcript'])) {
                return $data['transcript'];
            }
        }
        
        // Fallback: Direct HTML scraping
        $webUrl = "https://youtubetranscript.com/?v={$videoId}";
        $html = $this->makeRequest($webUrl);
        
        if ($html && preg_match('/<div[^>]*class="transcript"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            return strip_tags($matches[1]);
        }
        
        throw new Exception("YouTubeTranscript.com nicht verfügbar");
    }
    
    private function fetchFromSaveSubs($videoId, $videoUrl) {
        // savesubs.com API
        $apiUrl = "https://savesubs.com/api/download";
        
        $postData = [
            'url' => $videoUrl,
            'format' => 'txt',
            'lang' => 'de'
        ];
        
        $response = $this->makeRequest($apiUrl, $postData);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['content'])) {
                return $data['content'];
            }
        }
        
        throw new Exception("SaveSubs API nicht verfügbar");
    }
    
    private function fetchFromYouTubeDirect($videoId, $videoUrl) {
        // Versuche direkt von YouTube HTML zu scrapen
        $html = $this->makeRequest($videoUrl);
        
        if (!$html) {
            throw new Exception("YouTube-Seite nicht erreichbar");
        }
        
        // Suche nach Caption-Tracks in den Seiten-Daten
        if (preg_match('/"captions":\{"playerCaptionsTracklistRenderer":\{"captionTracks":\[(.*?)\]/', $html, $matches)) {
            $captionsData = '[' . $matches[1] . ']';
            $captions = json_decode($captionsData, true);
            
            if ($captions) {
                foreach ($captions as $caption) {
                    if (isset($caption['baseUrl'])) {
                        $transcriptXml = $this->makeRequest($caption['baseUrl']);
                        if ($transcriptXml) {
                            return $this->parseXmlTranscript($transcriptXml);
                        }
                    }
                }
            }
        }
        
        throw new Exception("Keine Captions in YouTube-HTML gefunden");
    }
    
    private function makeRequest($url, $postData = null) {
        $ch = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: de,en-US;q=0.7,en;q=0.3',
                'Accept-Encoding: gzip, deflate, br',
                'DNT: 1',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ]
        ];
        
        if ($postData) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = is_array($postData) ? http_build_query($postData) : $postData;
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("CURL Error: $error");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error: $httpCode");
        }
        
        return $response;
    }
    
    private function parseJson3Transcript($jsonData) {
        $data = json_decode($jsonData, true);
        
        if (!isset($data['events'])) {
            throw new Exception("Ungültiges JSON3-Format");
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
        
        return trim($transcript);
    }
    
    private function parseXmlTranscript($xmlData) {
        // XML-Untertitel parsen
        $xml = simplexml_load_string($xmlData);
        
        if (!$xml) {
            throw new Exception("Ungültiges XML-Format");
        }
        
        $transcript = '';
        foreach ($xml->text as $text) {
            $transcript .= html_entity_decode((string)$text) . ' ';
        }
        
        return trim($transcript);
    }
    
    private function cleanTranscript($transcript) {
        // Bereinige den Transcript
        $transcript = html_entity_decode($transcript);
        $transcript = strip_tags($transcript);
        
        // Entferne Timestamps wie [00:12] oder (12:34)
        $transcript = preg_replace('/\[?\d{1,2}:\d{2}(?::\d{2})?\]?/', '', $transcript);
        $transcript = preg_replace('/\(\d{1,2}:\d{2}(?::\d{2})?\)/', '', $transcript);
        
        // Entferne Speaker-Labels wie "Speaker:" oder "Sprecher 1:"
        $transcript = preg_replace('/^[A-Za-z\s]+\d*:\s*/m', '', $transcript);
        
        // Mehrfache Leerzeichen/Zeilenumbrüche reduzieren
        $transcript = preg_replace('/\s+/', ' ', $transcript);
        $transcript = preg_replace('/\n+/', '\n', $transcript);
        
        return trim($transcript);
    }
    
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
     * Test-Funktion für Debugging
     */
    public function testTranscript($videoUrl) {
        echo "<h2>🧪 Transcript-Test für: $videoUrl</h2>\n";
        
        $result = $this->getTranscript($videoUrl);
        
        if ($result['success']) {
            echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50;'>\n";
            echo "<h3>✅ Erfolgreich mit Service: {$result['source']}</h3>\n";
            echo "<p><strong>Länge:</strong> {$result['length']} Zeichen</p>\n";
            echo "<details><summary>Transcript anzeigen</summary>\n";
            echo "<pre style='max-height: 300px; overflow-y: auto;'>" . htmlspecialchars(substr($result['transcript'], 0, 2000)) . "</pre>\n";
            echo "</details></div>\n";
        } else {
            echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336;'>\n";
            echo "<h3>❌ Alle Services fehlgeschlagen</h3>\n";
            echo "<ul>\n";
            foreach ($result['errors'] as $service => $error) {
                echo "<li><strong>$service:</strong> $error</li>\n";
            }
            echo "</ul></div>\n";
        }
        
        return $result;
    }
}
?>
