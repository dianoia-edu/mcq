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
        $this->tempDir = __DIR__ . '/../temp';
        $this->outputDir = __DIR__ . '/../output';
        $this->logFile = __DIR__ . '/../logs/youtube-whisper.log';
        $this->cookieFile = __DIR__ . '/../includes/config/www.youtube.com.txt';
        
        if (!is_dir($this->tempDir)) mkdir($this->tempDir, 0755, true);
        if (!is_dir($this->outputDir)) mkdir($this->outputDir, 0755, true);
        if (!is_dir(dirname($this->logFile))) mkdir(dirname($this->logFile), 0755, true);
    }
    
    private function logMessage($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        return $logMessage;
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
            
            // Methode 1: Mit alternativen Cookies
            $altCookieFile = "{$this->tempDir}/youtube_cookies_temp_$processId.txt";
            file_put_contents($altCookieFile, 
                ".youtube.com\tTRUE\t/\tTRUE\t" . (time() + 86400) . "\tCONSENT\tYES+cb\n" .
                ".youtube.com\tTRUE\t/\tTRUE\t" . (time() + 86400) . "\tAECGD\ttest\n" .
                file_get_contents($this->cookieFile)
            );
            chmod($altCookieFile, 0644);
            
            $ytdlpCommand1 = "{$this->ytdlpPath} -x --audio-format mp3 --audio-quality 0 --cookies '$altCookieFile' -o '$audioFile' '$videoUrl' 2>&1";
            $ytdlpOutput1 = shell_exec($ytdlpCommand1);
            $logs[] = $this->logMessage("Methode 1 Ausgabe: " . $ytdlpOutput1);
            
            if (file_exists($audioFile) && filesize($audioFile) > 1000) {
                $downloadSuccessful = true;
                $logs[] = $this->logMessage("Download mit Methode 1 erfolgreich.");
            } else {
                // Methode 2: Mit User-Agent
                $ytdlpCommand2 = "{$this->ytdlpPath} -x --audio-format mp3 -f 'bestaudio' --user-agent 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36' -o '$audioFile' '$videoUrl' 2>&1";
                $ytdlpOutput2 = shell_exec($ytdlpCommand2);
                $logs[] = $this->logMessage("Methode 2 Ausgabe: " . $ytdlpOutput2);
                
                if (file_exists($audioFile) && filesize($audioFile) > 1000) {
                    $downloadSuccessful = true;
                    $logs[] = $this->logMessage("Download mit Methode 2 erfolgreich.");
                } else {
                    // Methode 3: Mit invidious
                    $invidousUrl = str_replace('youtube.com', 'invidious.snopyta.org', $videoUrl);
                    $invidousUrl = str_replace('youtu.be', 'invidious.snopyta.org/watch?v=', $invidousUrl);
                    $ytdlpCommand3 = "{$this->ytdlpPath} -x --audio-format mp3 -o '$audioFile' '$invidousUrl' 2>&1";
                    $ytdlpOutput3 = shell_exec($ytdlpCommand3);
                    $logs[] = $this->logMessage("Methode 3 Ausgabe: " . $ytdlpOutput3);
                    
                    if (file_exists($audioFile) && filesize($audioFile) > 1000) {
                        $downloadSuccessful = true;
                        $logs[] = $this->logMessage("Download mit Methode 3 erfolgreich.");
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
            if (file_exists($altCookieFile)) unlink($altCookieFile);
            
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
            if (file_exists($altCookieFile)) unlink($altCookieFile);
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'logs' => $logs
            ];
        }
    }
}
