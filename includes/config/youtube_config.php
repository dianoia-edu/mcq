<?php
// Aktiviere Error Reporting nur für fatale Fehler
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// YouTube API Key - Ersetzen Sie dies mit Ihrem tatsächlichen  API-Key
$youtubeApiKey = 'AIzaSyC7gIh326Qla46--5LI54B_nFSdkORaj1o';

// Funktion zum Laden des YouTube API Keys
function getYoutubeApiKey() {
    global $youtubeApiKey;
    return $youtubeApiKey;
}

// Funktion zum Speichern eines neuen YouTube API Keys
function saveYoutubeApiKey($newKey) {
    $configFile = __FILE__;
    $currentContent = file_get_contents($configFile);
    
    // Ersetze den alten API Key mit dem neuen
    $pattern = "/\\\$youtubeApiKey = '.*?';/";
    $replacement = "\$youtubeApiKey = '$newKey';";
    $newContent = preg_replace($pattern, $replacement, $currentContent);
    
    if ($newContent !== null) {
        return file_put_contents($configFile, $newContent) !== false;
    }
    return false;
} 