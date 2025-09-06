<?php
/**
 * 🚀 ULTIMATE FALLBACK STRATEGY
 * Wenn YouTube komplett blockiert - Alternative Ansätze
 */

class YouTubeUltimateFallback {
    
    private $debug;
    
    public function __construct($debug = true) {
        $this->debug = $debug;
    }
    
    /**
     * 🎯 ULTIMATE FALLBACK: Wenn alle YouTube-Methoden versagen
     */
    public function getTranscript($videoUrl) {
        if ($this->debug) {
            error_log("🚀 ULTIMATE FALLBACK aktiviert für: $videoUrl");
        }
        
        // Strategie 1: Whisper-API (OpenAI)
        $result = $this->tryWhisperAPI($videoUrl);
        if ($result['success']) {
            return $result;
        }
        
        // Strategie 2: Audio-Download + lokale Verarbeitung
        $result = $this->tryLocalAudioProcessing($videoUrl);
        if ($result['success']) {
            return $result;
        }
        
        // Strategie 3: Video-Content-Generierung ohne Transcript
        $result = $this->generateContentFromMetadata($videoUrl);
        if ($result['success']) {
            return $result;
        }
        
        return [
            'success' => false, 
            'error' => 'Alle Transcript-Methoden fehlgeschlagen - YouTube komplett blockiert'
        ];
    }
    
    /**
     * 🎤 STRATEGIE 1: OpenAI Whisper-API
     */
    private function tryWhisperAPI($videoUrl) {
        if ($this->debug) {
            error_log("🎤 Versuche Whisper-API...");
        }
        
        try {
            // 1. Audio extrahieren mit yt-dlp (audio-only)
            $audioFile = $this->extractAudioOnly($videoUrl);
            
            if (!$audioFile) {
                throw new Exception("Audio-Extraktion fehlgeschlagen");
            }
            
            // 2. Whisper-API aufrufen
            $transcript = $this->callWhisperAPI($audioFile);
            
            // 3. Temporäre Datei löschen
            if (file_exists($audioFile)) {
                unlink($audioFile);
            }
            
            if ($transcript && strlen($transcript) > 100) {
                return [
                    'success' => true,
                    'transcript' => $transcript,
                    'source' => 'WHISPER_API',
                    'length' => strlen($transcript)
                ];
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("❌ Whisper-API Error: " . $e->getMessage());
            }
        }
        
        return ['success' => false, 'error' => 'Whisper-API fehlgeschlagen'];
    }
    
    /**
     * 🔊 STRATEGIE 2: Lokale Audio-Verarbeitung
     */
    private function tryLocalAudioProcessing($videoUrl) {
        if ($this->debug) {
            error_log("🔊 Versuche lokale Audio-Verarbeitung...");
        }
        
        try {
            // 1. Audio extrahieren (sehr kurz, nur erste 30 Sekunden)
            $audioFile = $this->extractAudioSample($videoUrl, 30);
            
            if (!$audioFile) {
                throw new Exception("Audio-Sample-Extraktion fehlgeschlagen");
            }
            
            // 2. Speech-to-Text mit verfügbaren Tools
            $transcript = $this->processAudioLocally($audioFile);
            
            // 3. Cleanup
            if (file_exists($audioFile)) {
                unlink($audioFile);
            }
            
            if ($transcript && strlen($transcript) > 50) {
                return [
                    'success' => true,
                    'transcript' => $transcript . "\n\n[Hinweis: Nur erste 30 Sekunden des Videos transkribiert]",
                    'source' => 'LOCAL_AUDIO_PROCESSING',
                    'length' => strlen($transcript),
                    'partial' => true
                ];
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("❌ Lokale Audio-Verarbeitung Error: " . $e->getMessage());
            }
        }
        
        return ['success' => false, 'error' => 'Lokale Audio-Verarbeitung fehlgeschlagen'];
    }
    
    /**
     * 📊 STRATEGIE 3: Content-Generierung aus Video-Metadaten
     */
    private function generateContentFromMetadata($videoUrl) {
        if ($this->debug) {
            error_log("📊 Generiere Content aus Video-Metadaten...");
        }
        
        try {
            $videoId = $this->extractVideoId($videoUrl);
            
            // 1. Video-Titel und Beschreibung extrahieren
            $metadata = $this->getVideoMetadata($videoUrl);
            
            if (!$metadata || empty($metadata['title'])) {
                throw new Exception("Keine Metadaten verfügbar");
            }
            
            // 2. Content aus Titel/Beschreibung generieren
            $generatedContent = $this->generateContentFromTitle($metadata);
            
            if ($generatedContent && strlen($generatedContent) > 200) {
                return [
                    'success' => true,
                    'transcript' => $generatedContent,
                    'source' => 'GENERATED_FROM_METADATA',
                    'length' => strlen($generatedContent),
                    'generated' => true,
                    'metadata' => $metadata
                ];
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("❌ Metadata-Generation Error: " . $e->getMessage());
            }
        }
        
        return ['success' => false, 'error' => 'Content-Generierung fehlgeschlagen'];
    }
    
    /**
     * 🎵 Audio-Only Extraktion (für Whisper)
     */
    private function extractAudioOnly($videoUrl) {
        $tempDir = sys_get_temp_dir();
        $audioFile = $tempDir . '/audio_' . uniqid() . '.mp3';
        
        // yt-dlp für Audio-Only (umgeht Video-Blocks teilweise)
        $command = sprintf(
            'yt-dlp -f "bestaudio/best" -x --audio-format mp3 --audio-quality 0 -o %s %s 2>/dev/null',
            escapeshellarg($audioFile),
            escapeshellarg($videoUrl)
        );
        
        $output = shell_exec($command);
        
        return file_exists($audioFile) ? $audioFile : null;
    }
    
    /**
     * 🎵 Audio-Sample (erste X Sekunden)
     */
    private function extractAudioSample($videoUrl, $duration = 30) {
        $tempDir = sys_get_temp_dir();
        $audioFile = $tempDir . '/sample_' . uniqid() . '.wav';
        
        // Nur erste 30 Sekunden extrahieren
        $command = sprintf(
            'yt-dlp -f "bestaudio/best" --postprocessor-args "-t %d" -x --audio-format wav -o %s %s 2>/dev/null',
            $duration,
            escapeshellarg($audioFile),
            escapeshellarg($videoUrl)
        );
        
        $output = shell_exec($command);
        
        return file_exists($audioFile) ? $audioFile : null;
    }
    
    /**
     * 🤖 OpenAI Whisper-API aufrufen
     */
    private function callWhisperAPI($audioFile) {
        // OpenAI API-Key aus Config
        require_once __DIR__ . '/config/openai_config.php';
        
        if (!isset($apiKey) || empty($apiKey)) {
            throw new Exception("OpenAI API-Key nicht konfiguriert");
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.openai.com/v1/audio/transcriptions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $apiKey,
                "Content-Type: multipart/form-data"
            ],
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile($audioFile),
                'model' => 'whisper-1',
                'language' => 'de',
                'response_format' => 'text'
            ],
            CURLOPT_TIMEOUT => 300 // 5 Minuten für große Dateien
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($httpCode === 200) {
            return trim($response);
        } else {
            throw new Exception("Whisper-API Error: HTTP $httpCode - $response");
        }
    }
    
    /**
     * 🔊 Lokale Audio-Verarbeitung (falls Tools verfügbar)
     */
    private function processAudioLocally($audioFile) {
        // Prüfe verfügbare Speech-to-Text Tools
        $tools = [
            'whisper',  // Lokales Whisper
            'speech_recognition',  // Python speech_recognition
            'ffmpeg'    // FFmpeg mit Speech-Recognition
        ];
        
        foreach ($tools as $tool) {
            try {
                $result = $this->tryTool($tool, $audioFile);
                if ($result) {
                    return $result;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * 🛠️ Tool-spezifische Verarbeitung
     */
    private function tryTool($tool, $audioFile) {
        switch ($tool) {
            case 'whisper':
                // Lokales Whisper (falls installiert)
                $command = "whisper --language de --output_format txt " . escapeshellarg($audioFile) . " 2>/dev/null";
                $output = shell_exec($command);
                return $this->extractTextFromWhisperOutput($output);
                
            case 'speech_recognition':
                // Python speech_recognition
                $pythonScript = $this->createSpeechRecognitionScript($audioFile);
                $output = shell_exec("python3 $pythonScript 2>/dev/null");
                return trim($output);
                
            default:
                return null;
        }
    }
    
    /**
     * 📊 Video-Metadaten extrahieren
     */
    private function getVideoMetadata($videoUrl) {
        // Versuche verschiedene Methoden für Metadaten
        
        // 1. yt-dlp für Metadaten (oft weniger blockiert)
        $command = sprintf(
            'yt-dlp --dump-json --no-download %s 2>/dev/null',
            escapeshellarg($videoUrl)
        );
        
        $output = shell_exec($command);
        
        if ($output) {
            $data = json_decode($output, true);
            if ($data && isset($data['title'])) {
                return [
                    'title' => $data['title'] ?? '',
                    'description' => $data['description'] ?? '',
                    'duration' => $data['duration'] ?? 0,
                    'uploader' => $data['uploader'] ?? '',
                    'upload_date' => $data['upload_date'] ?? ''
                ];
            }
        }
        
        // 2. Fallback: HTML-Scraping für Open Graph Tags
        return $this->scrapeVideoMetadata($videoUrl);
    }
    
    /**
     * 🌐 HTML-Scraping für Metadaten
     */
    private function scrapeVideoMetadata($videoUrl) {
        try {
            $html = file_get_contents($videoUrl, false, stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'timeout' => 10
                ]
            ]));
            
            if ($html) {
                preg_match('/<title>([^<]+)<\/title>/', $html, $titleMatch);
                preg_match('/<meta property="og:description" content="([^"]+)"/', $html, $descMatch);
                
                return [
                    'title' => $titleMatch[1] ?? '',
                    'description' => $descMatch[1] ?? '',
                    'duration' => 0,
                    'uploader' => '',
                    'upload_date' => ''
                ];
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("❌ Metadata-Scraping Error: " . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * 📝 Content aus Titel/Beschreibung generieren
     */
    private function generateContentFromTitle($metadata) {
        $title = $metadata['title'] ?? '';
        $description = $metadata['description'] ?? '';
        
        if (empty($title)) {
            return null;
        }
        
        // Basis-Content aus Titel und Beschreibung
        $content = "Video-Titel: " . $title . "\n\n";
        
        if (!empty($description)) {
            $content .= "Beschreibung: " . substr($description, 0, 500) . "\n\n";
        }
        
        // Versuche, thematischen Content zu generieren
        $content .= $this->generateThematicContent($title, $description);
        
        return $content;
    }
    
    /**
     * 🎯 Thematischen Content generieren
     */
    private function generateThematicContent($title, $description) {
        // Einfache Keyword-basierte Content-Generierung
        $keywords = $this->extractKeywords($title . ' ' . $description);
        
        if (empty($keywords)) {
            return "Dieses Video behandelt verschiedene Themen, die im Titel erwähnt werden.";
        }
        
        $content = "Hauptthemen des Videos:\n";
        foreach ($keywords as $keyword) {
            $content .= "- " . ucfirst($keyword) . "\n";
        }
        
        $content .= "\nBasierend auf dem Titel und der Beschreibung behandelt dieses Video wahrscheinlich Aspekte zu den genannten Themen. ";
        $content .= "Eine detaillierte Analyse war aufgrund von YouTube-Zugriffsbeschränkungen nicht möglich.";
        
        return $content;
    }
    
    /**
     * 🔍 Keywords extrahieren
     */
    private function extractKeywords($text) {
        // Einfache Keyword-Extraktion
        $text = strtolower($text);
        $words = preg_split('/\W+/', $text);
        
        // Filtere zu kurze und Stopwords
        $stopwords = ['der', 'die', 'das', 'und', 'oder', 'aber', 'ist', 'sind', 'ein', 'eine', 'in', 'auf', 'mit', 'für', 'von', 'zu', 'im', 'am', 'an', 'bei', 'the', 'and', 'or', 'but', 'is', 'are', 'a', 'an', 'in', 'on', 'with', 'for', 'of', 'to'];
        
        $keywords = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 3 && !in_array($word, $stopwords);
        });
        
        return array_unique(array_slice($keywords, 0, 10));
    }
    
    /**
     * 🎬 Video-ID extrahieren
     */
    private function extractVideoId($url) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $url, $matches);
        return $matches[1] ?? null;
    }
}
?>
