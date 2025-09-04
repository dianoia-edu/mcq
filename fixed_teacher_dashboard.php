<?php
session_start();

if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: ../index.php');
    exit();
}

// VERBESSERTE Pfad-Erkennung für Includes
function getIncludesPath($relativePath) {
    $currentDir = dirname(__FILE__);
    $currentBasename = basename($currentDir);
    
    // Debug-Logging
    error_log("getIncludesPath Debug: currentDir=$currentDir, basename=$currentBasename, relativePath=$relativePath");
    
    // Versuche verschiedene Pfad-Varianten und teste welche existiert
    $possiblePaths = [];
    
    if ($currentBasename === 'teacher') {
        // Hauptinstanz oder Instanz mit teacher-Verzeichnis
        $possiblePaths[] = dirname($currentDir) . '/includes/' . $relativePath;
    } else {
        // Dashboard liegt im Root-Verzeichnis einer Instanz
        $possiblePaths[] = $currentDir . '/includes/' . $relativePath;
    }
    
    // Zusätzliche Fallback-Pfade
    $possiblePaths[] = $currentDir . '/../includes/' . $relativePath;
    $possiblePaths[] = dirname($currentDir) . '/includes/' . $relativePath;
    
    // Teste alle Pfade und verwende den ersten, der existiert
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            error_log("getIncludesPath: Gefundener Pfad: $path");
            return $path;
        }
    }
    
    // Fallback: Verwende den ersten Pfad und logge den Fehler
    $fallbackPath = $possiblePaths[0] ?? '';
    error_log("getIncludesPath: FEHLER - Keine gültige Datei gefunden. Verwende Fallback: $fallbackPath");
    error_log("getIncludesPath: Getestete Pfade: " . implode(', ', $possiblePaths));
    
    return $fallbackPath;
}

// Ermittle Base-Pfad basierend auf aktueller Position mit verbesserter Logik
$currentDir = dirname(__FILE__);
$isInTeacherDir = basename($currentDir) === 'teacher';

if ($isInTeacherDir) {
    // Hauptinstanz: Verzeichnisse eine Ebene höher erstellen
    $baseDir = dirname($currentDir);
} else {
    // Instanz: Verzeichnisse im aktuellen Verzeichnis erstellen  
    $baseDir = $currentDir;
}

error_log("Directory creation: currentDir=$currentDir, isInTeacherDir=" . ($isInTeacherDir ? 'true' : 'false') . ", baseDir=$baseDir");

// Erstelle erforderliche Verzeichnisse (nur falls sie nicht existieren)
$directories = [
    $baseDir . '/includes',
    $baseDir . '/teacher', 
    $baseDir . '/tests',
    $baseDir . '/results',
    $baseDir . '/qrcodes'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Rest der teacher_dashboard.php Datei bleibt unverändert...
// (hier würde der komplette Rest des Dashboards stehen)
?>
