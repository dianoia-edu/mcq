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

header('Content-Type: text/plain');

$dirs = [
    'temp',
    'output',
    'logs',
    'includes/config'
];

$rootDir = __DIR__;
echo "Überprüfe Verzeichnisse im Root-Pfad: $rootDir\n\n";

foreach ($dirs as $dir) {
    $fullPath = "$rootDir/$dir";
    echo "Verzeichnis: $fullPath\n";
    
    if (!file_exists($fullPath)) {
        echo "  - Existiert nicht, erstelle...\n";
        $created = @mkdir($fullPath, 0755, true);
        echo "  - Erstellung " . ($created ? "erfolgreich" : "fehlgeschlagen") . "\n";
    } else {
        echo "  - Existiert\n";
    }
    
    if (is_dir($fullPath)) {
        $isWritable = is_writable($fullPath);
        echo "  - Schreibberechtigung: " . ($isWritable ? "JA" : "NEIN") . "\n";
        
        if (!$isWritable) {
            echo "  - Setze Berechtigungen...\n";
            $chmodResult = @chmod($fullPath, 0755);
            echo "  - chmod 0755 Ergebnis: " . ($chmodResult ? "Erfolgreich" : "Fehlgeschlagen") . "\n";
        }
    }
    
    echo "\n";
}

// Überprüfe auch den yt-dlp-Pfad
$ytdlpPath = '/home/mcqadmin/.local/bin/yt-dlp';
echo "Überprüfe yt-dlp: $ytdlpPath\n";
if (file_exists($ytdlpPath)) {
    echo "  - yt-dlp existiert\n";
    echo "  - Version: " . trim(shell_exec("$ytdlpPath --version 2>&1")) . "\n";
    echo "  - Ausführberechtigung: " . (is_executable($ytdlpPath) ? "JA" : "NEIN") . "\n";
    
    if (!is_executable($ytdlpPath)) {
        echo "  - Setze Ausführberechtigung...\n";
        $chmodResult = @chmod($ytdlpPath, 0755);
        echo "  - chmod 0755 Ergebnis: " . ($chmodResult ? "Erfolgreich" : "Fehlgeschlagen") . "\n";
    }
} else {
    echo "  - yt-dlp NICHT GEFUNDEN\n";
    echo "  - Überprüfe PATH: " . getenv('PATH') . "\n";
}

echo "\nAusgeführt als Benutzer: " . trim(shell_exec("whoami 2>&1")) . "\n";
echo "Server-Umgebung:\n";
echo "PHP-Version: " . PHP_VERSION . "\n";
echo "Betriebssystem: " . PHP_OS . "\n";
echo "Server-Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt') . "\n";
