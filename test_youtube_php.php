<?php
/**
 * Test für Pure PHP YouTube Transcript Service
 */

require_once 'includes/youtube_transcript_php.php';

$service = new YouTubeTranscriptPHP();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🎯 Pure PHP YouTube Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
        input[type="url"] { width: 100%; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🎯 Pure PHP YouTube Transcript Test</h1>

<div class="test-section">
    <h2>💡 Vorteile dieser Lösung</h2>
    <ul>
        <li>✅ <strong>Kein Python nötig</strong> - reines PHP</li>
        <li>✅ <strong>Keine Dependencies</strong> - läuft überall</li>
        <li>✅ <strong>Gleiche Methoden</strong> wie das Python-Script</li>
        <li>✅ <strong>Bessere Integration</strong> in bestehenden Code</li>
    </ul>
</div>

<div class="test-section">
    <h2>🧪 Test mit eigener URL</h2>
    <form method="post">
        <label><strong>YouTube-URL:</strong></label>
        <input type="url" name="test_url" value="<?= htmlspecialchars($_POST['test_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
        <button type="submit" name="test_php">🚀 PHP-Transcript testen</button>
    </form>
</div>

<?php
if (isset($_POST['test_php']) && !empty($_POST['test_url'])) {
    echo "<div class='test-section'>\n";
    $result = $service->testTranscript($_POST['test_url']);
    echo "</div>\n";
    
    if ($result['success']) {
        echo "<div class='test-section' style='background: #e8f5e8;'>\n";
        echo "<h2>🎉 Integration bereit!</h2>\n";
        echo "<p>Diese PHP-Lösung kann <strong>sofort in den Test-Generator</strong> integriert werden!</p>\n";
        echo "<h3>📋 Code für generate_test.php:</h3>\n";
        echo "<pre style='background: #f5f5f5; padding: 15px; overflow-x: auto;'>";
        echo htmlspecialchars('
require_once __DIR__ . "/../includes/youtube_transcript_php.php";

if (!empty($youtube_url)) {
    $transcriptService = new YouTubeTranscriptPHP();
    $result = $transcriptService->getTranscript($youtube_url);
    
    if ($result["success"]) {
        $content = $result["transcript"];
        echo "Transcript erfolgreich extrahiert (" . $result["length"] . " Zeichen)";
        // Weiter mit Test-Generation...
    } else {
        echo "Transcript-Fehler: " . $result["error"];
        // Fallback oder Fehlermeldung
    }
}');
        echo "</pre>\n";
        echo "</div>\n";
    }
}

// Automatische Tests mit bekannten Videos
if (!isset($_POST['test_php'])) {
    echo "<div class='test-section'>\n";
    echo "<h2>🎬 Automatische Tests</h2>\n";
    
    $testVideos = [
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => 'Rick Astley - Never Gonna Give You Up (Englisch)',
        'https://www.youtube.com/watch?v=jNQXAC9IVRw' => 'Me at the zoo (Erstes YouTube-Video)',
    ];
    
    foreach ($testVideos as $url => $title) {
        echo "<h3>📺 $title</h3>\n";
        echo "<p><code>" . htmlspecialchars($url) . "</code></p>\n";
        
        $result = $service->getTranscript($url);
        
        if ($result['success']) {
            echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 3px;'>\n";
            echo "✅ <strong>Erfolg!</strong> Quelle: {$result['source']}, Länge: {$result['length']} Zeichen\n";
            if ($result['length'] > 100) {
                echo "<details style='margin-top: 10px;'><summary>Transcript-Vorschau</summary>\n";
                echo "<pre style='max-height: 150px; overflow-y: auto; background: #f9f9f9; padding: 8px;'>";
                echo htmlspecialchars(substr($result['transcript'], 0, 400)) . "...";
                echo "</pre></details>\n";
            }
            echo "</div>\n";
        } else {
            echo "<div style='background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 3px;'>\n";
            echo "❌ <strong>Fehlgeschlagen:</strong> " . htmlspecialchars($result['error']) . "\n";
            echo "</div>\n";
        }
        
        echo "<hr>\n";
    }
    
    echo "</div>\n";
}
?>

<div class="test-section">
    <h2>🔧 Debugging-Tests</h2>
    <p><a href="debug_youtube_python.php" target="_blank">🐍 Python-Script Debug</a></p>
    <p><a href="test_baseurl_fix.php" target="_blank">🔧 BaseURL Header-Test</a></p>
    <p><a href="test_caption_extraction.php" target="_blank">🎯 Caption-Extraktion Test</a></p>
</div>

</body>
</html>
