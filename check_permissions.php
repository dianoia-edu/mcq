<?php
// Alternative zu yt-dlp: Verwende youtube-dl, falls vorhanden
if (!$downloadSuccessful) {
    $youtubeDlPath = '/usr/bin/youtube-dl'; // Oder alternativer Pfad
    if (file_exists($youtubeDlPath)) {
        $ytdlCommand = "$youtubeDlPath -x --audio-format mp3 --audio-quality 0 -o '$audioFile' '$videoUrl' 2>&1";
        $ytdlOutput = shell_exec($ytdlCommand);
        $logs[] = $this->logMessage("Alternative youtube-dl Ausgabe: " . $ytdlOutput);
        
        if (file_exists($audioFile) && filesize($audioFile) > 1000) {
            $downloadSuccessful = true;
            $logs[] = $this->logMessage("Download mit youtube-dl erfolgreich.");
        }
    }
}
