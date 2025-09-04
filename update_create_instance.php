<?php
/**
 * Update-Script: Verteile das korrigierte create_instance.php an alle Instanzen
 */

session_start();

// Sicherstellen, dass nur der Super-Admin darauf zugreifen kann
if (!isset($_SESSION["teacher"]) || $_SESSION["teacher"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert.']);
    exit;
}

echo "<h1>🔧 Update: create_instance.php verteilen</h1>\n";

// Pfade
$sourceFile = __DIR__ . '/teacher/create_instance.php';
$instancesPath = '/var/www/dianoia-ai.de/lehrer_instanzen/';

if (!file_exists($sourceFile)) {
    echo "❌ Source-Datei nicht gefunden: $sourceFile<br>\n";
    exit;
}

if (!is_dir($instancesPath)) {
    echo "❌ Instanzen-Verzeichnis nicht gefunden: $instancesPath<br>\n";
    exit;
}

echo "📁 Instanzen-Verzeichnis: <code>$instancesPath</code><br>\n";
echo "📄 Source-Datei: <code>$sourceFile</code><br>\n";

$dirs = scandir($instancesPath);
$updated = 0;
$errors = 0;

foreach ($dirs as $dir) {
    if ($dir === '.' || $dir === '..') continue;
    
    $instanceDir = $instancesPath . $dir . '/mcq-test-system';
    $targetDir = $instanceDir . '/teacher';
    $targetFile = $targetDir . '/create_instance.php';
    
    if (!is_dir($instanceDir)) {
        echo "⚠️ Instanz-Verzeichnis nicht gefunden: $instanceDir<br>\n";
        continue;
    }
    
    if (!is_dir($targetDir)) {
        echo "⚠️ Teacher-Verzeichnis nicht gefunden: $targetDir<br>\n";
        continue;
    }
    
    // Datei kopieren
    if (copy($sourceFile, $targetFile)) {
        echo "✅ Instanz '$dir' aktualisiert<br>\n";
        $updated++;
    } else {
        echo "❌ Fehler bei Instanz '$dir'<br>\n";
        $errors++;
    }
}

echo "<br><strong>📊 Zusammenfassung:</strong><br>\n";
echo "✅ Aktualisiert: $updated Instanzen<br>\n";
echo "❌ Fehler: $errors<br>\n";

if ($updated > 0) {
    echo "<br>🎉 <strong>Das korrigierte create_instance.php wurde verteilt!</strong><br>\n";
    echo "➡️ Neue Instanzen sollten jetzt keine Testergebnisse mehr kopieren.<br>\n";
}

?>
