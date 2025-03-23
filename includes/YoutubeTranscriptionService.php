<?php
class YoutubeTranscriptionService {
    private $ytdlpPath;
    private $openai_api_key;
    private $tempDir;
    private $outputDir;
    private $logFile;
    private $cookieFile;
    
    public function __construct($openai_api_key) {
        $this->ytdlpPath = '/home/mcqadmin/.local/bin/yt-dlp';
        $this->openai_api_key = $openai_api_key;
        
        // Absolute Pfade verwenden
        $rootDir = realpath(__DIR__ . '/..');
        $this->tempDir = $rootDir . '/temp';
        $this->outputDir = $rootDir . '/output';
        $this->logFile = $rootDir . '/logs/youtube-whisper.log';
        $this->cookieFile = $rootDir . '/includes/config/www.youtube.com.txt';
    }
    
    private function logMessage($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        return $logMessage;
    }
    
    private function extractVideoId($url) {
        // Regulärer Ausdruck zum Extrahieren der Video-ID
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    public function transcribeVideo($videoUrl) {
        $processId = uniqid();
        $audioFile = "{$this->tempDir}/audio_$processId.mp3";
        $transcriptionFile = "{$this->outputDir}/transcript_$processId.txt";
        $logs = [];
        
        try {
            // 1. Download-Phase: Versuche verschiedene Methoden
            $logs[] = $this->logMessage("Starte Download von YouTube-Video: $videoUrl");
            
            $downloadSuccessful = false;
            
            // Extrahiere die Video-ID für spätere Verwendung
            $videoId = $this->extractVideoId($videoUrl);
            if (!$videoId) {
                $logs[] = $this->logMessage("Konnte Video-ID nicht aus URL extrahieren: $videoUrl");
                throw new Exception("Ungültige YouTube-URL: Konnte Video-ID nicht extrahieren");
            }
            
            $logs[] = $this->logMessage("Extrahierte Video-ID: $videoId");
            
            // Methode 1: Direkt mit Format-Auswahl und User-Agent
            $logs[] = $this->logMessage("Methode 1: Mit Format-Auswahl und User-Agent");
            $ytdlpCommand1 = "{$this->ytdlpPath} -x --audio-format mp3 --audio-quality 0 -f 'bestaudio[ext=m4a]' --user-agent 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36' -o '$audioFile' 'https://www.youtube.com/watch?v=$videoId' 2>&1";
            $ytdlpOutput1 = shell_exec($ytdlpCommand1);
            $logs[] = $this->logMessage("Methode 1 Ausgabe: " . $ytdlpOutput1);
            
            if (file_exists($audioFile) && filesize($audioFile) > 1000) {
                $downloadSuccessful = true;
                $logs[] = $this->logMessage("Download mit Methode 1 erfolgreich.");
            } else {
                // Methode 2: Mit anderen Optionen
                $logs[] = $this->logMessage("Methode 2: Mit erweiterten Optionen");
                $ytdlpCommand2 = "{$this->ytdlpPath} -x --audio-format mp3 --force-ipv4 --no-check-certificate --geo-bypass -f 'bestaudio' -o '$audioFile' 'https://www.youtube.com/watch?v=$videoId' 2>&1";
                $ytdlpOutput2 = shell_exec($ytdlpCommand2);
                $logs[] = $this->logMessage("Methode 2 Ausgabe: " . $ytdlpOutput2);
                
                if (file_exists($audioFile) && filesize($audioFile) > 1000) {
                    $downloadSuccessful = true;
                    $logs[] = $this->logMessage("Download mit Methode 2 erfolgreich.");
                } else {
                    // Methode 3: Mit Invidious-Instance (korrigiert)
                    $logs[] = $this->logMessage("Methode 3: Mit Invidious-Instance");
                    // Verwende die korrekte Invidious-URL-Struktur mit der Video-ID
                    $invidousUrl = "https://invidious.snopyta.org/watch?v=$videoId";
                    $ytdlpCommand3 = "{$this->ytdlpPath} -x --audio-format mp3 -o '$audioFile' '$invidousUrl' 2>&1";
                    $ytdlpOutput3 = shell_exec($ytdlpCommand3);
                    $logs[] = $this->logMessage("Methode 3 Ausgabe: " . $ytdlpOutput3);
                    
                    if (file_exists($audioFile) && filesize($audioFile) > 1000) {
                        $downloadSuccessful = true;
                        $logs[] = $this->logMessage("Download mit Methode 3 erfolgreich.");
                    } else {
                        // Methode 4: Mit youtube-dl als Fallback
                        $youtubeDlPath = '/usr/bin/youtube-dl';
                        if (file_exists($youtubeDlPath)) {
                            $logs[] = $this->logMessage("Methode 4: Verwende youtube-dl als Fallback");
                            $ytdlCommand4 = "$youtubeDlPath -x --audio-format mp3 --audio-quality 0 -o '$audioFile' 'https://www.youtube.com/watch?v=$videoId' 2>&1";
                            $ytdlOutput4 = shell_exec($ytdlCommand4);
                            $logs[] = $this->logMessage("Methode 4 Ausgabe: " . $ytdlOutput4);
                            
                            if (file_exists($audioFile) && filesize($audioFile) > 1000) {
                                $downloadSuccessful = true;
                                $logs[] = $this->logMessage("Download mit Methode 4 erfolgreich.");
                            }
                        }
                    }
                }
            }
            
            if (!$downloadSuccessful) {
                throw new Exception("Keine der Download-Methoden war erfolgreich.");
            }
            
            $fileSize = filesize($audioFile);
            $logs[] = $this->logMessage("Audio erfolgreich gespeichert. Größe: " . round($fileSize / 1024 / 1024, 2) . " MB");
            
            // 2. Transkription mit OpenAI Whisper API
            $logs[] = $this->logMessage("Starte Transkription mit OpenAI Whisper API...");
            
            if (!function_exists('curl_file_create')) {
                function curl_file_create($filename, $mimetype = '', $postname = '') {
                    return "@$filename;filename="
                        . ($postname ?: basename($filename))
                        . ($mimetype ? ";type=$mimetype" : '');
                }
            }
            
            $curl = curl_init();
            $cFile = curl_file_create($audioFile, 'audio/mpeg', basename($audioFile));
            
            $postFields = [
                'file' => $cFile,
                'model' => 'whisper-1',
                'language' => 'de',
                'response_format' => 'text'
            ];
            
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->openai_api_key
                ],
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            
            curl_close($curl);
            
            if ($error) {
                throw new Exception("Fehler bei OpenAI API-Anfrage: $error");
            }
            
            if ($httpCode != 200) {
                throw new Exception("OpenAI API-Fehler mit Code $httpCode: $response");
            }
            
            // 3. Transkription speichern
            file_put_contents($transcriptionFile, $response);
            $logs[] = $this->logMessage("Transkription erfolgreich gespeichert in $transcriptionFile");
            
            // 4. Temporäre Dateien bereinigen
            unlink($audioFile);
            
            return [
                'success' => true,
                'transcription' => $response,
                'transcription_file' => $transcriptionFile,
                'logs' => $logs
            ];
            
        } catch (Exception $e) {
            $errorMsg = "Fehler bei der Verarbeitung: " . $e->getMessage();
            $logs[] = $this->logMessage($errorMsg);
            
            // Temporäre Dateien bereinigen bei Fehler
            if (file_exists($audioFile)) unlink($audioFile);
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'logs' => $logs
            ];
        }
    }
}
?>
