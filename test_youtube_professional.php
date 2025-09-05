<?php
/**
 * Test für professionelle YouTube-Transcript-Lösung
 * Nutzt dieselben Methoden wie downsub & Co.
 */

require_once 'includes/youtube_professional_service.php';

$service = new YouTubeProfessionalService(120, true); // 2 Min Timeout, Debug an

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🚀 Professionelle YouTube-Lösung</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .method-box { margin: 20px 0; padding: 20px; border-radius: 8px; border: 2px solid #ddd; }
        .success { background: #e8f5e8; border-color: #4caf50; }
        .info { background: #e3f2fd; border-color: #2196f3; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        .error { background: #ffebee; border-color: #f44336; }
        button { padding: 12px 24px; font-size: 16px; cursor: pointer; margin: 5px; border: none; border-radius: 5px; }
        .btn-primary { background: #2196f3; color: white; }
        .btn-success { background: #4caf50; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        input[type="url"] { width: 100%; padding: 12px; margin: 10px 0; border: 2px solid #ddd; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; border-radius: 5px; }
        .tech-info { font-size: 14px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>

<div class="hero">
    <h1>🚀 Professionelle YouTube-Transcript-Lösung</h1>
    <p><strong>Nutzt dieselben Methoden wie downsub, pytube und youtube-transcript-api!</strong></p>
    <p>5 verschiedene professionelle Libraries + 3 PHP-Fallback-Methoden</p>
</div>

<div class="method-box info">
    <h2>🔧 Verwendete Profi-Methoden</h2>
    <ol>
        <li><strong>youtube-transcript-api</strong> - Bewährte Python-Library (wie downsub)</li>
        <li><strong>pytube</strong> - Direkte Caption-Extraktion</li>
        <li><strong>yt-dlp</strong> - Subtitle-Download ohne Video</li>
        <li><strong>Erweiterte YouTube-API</strong> - Verschiedene Endpoints</li>
        <li><strong>Osiris-SDK-Methode</strong> - HTML-Pattern-Matching</li>
    </ol>
    
    <h3>🛡️ Fallback-Methoden (PHP):</h3>
    <ul>
        <li>Direkte API-Extraktion mit verschiedenen Endpoints</li>
        <li>Erweiterte Web-Scraping-Patterns</li>
        <li>API-Proxy-Methoden</li>
    </ul>
</div>

<div class="method-box warning">
    <h2>⚡ Automatic Installation</h2>
    <p>Das System installiert automatisch alle benötigten Python-Libraries:</p>
    <pre>pip install youtube-transcript-api pytube yt-dlp requests</pre>
    <p class="tech-info">Falls Libraries fehlen, werden sie beim ersten Aufruf automatisch installiert.</p>
</div>

<div class="method-box">
    <h2>🧪 Test mit deinem Video</h2>
    <form method="post">
        <label><strong>YouTube-URL:</strong></label>
        <input type="url" name="test_url" value="<?= htmlspecialchars($_POST['test_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
        <button type="submit" name="test_professional" class="btn-primary">🚀 Professionelle Extraktion starten</button>
    </form>
</div>

<?php
if (isset($_POST['test_professional']) && !empty($_POST['test_url'])) {
    echo "<div class='method-box'>\n";
    $result = $service->testTranscript($_POST['test_url']);
    echo "</div>\n";
    
    if ($result['success']) {
        echo "<div class='method-box success'>\n";
        echo "<h2>🎉 Integration bereit!</h2>\n";
        echo "<p>Die professionelle Lösung funktioniert! <strong>Kann sofort in den Test-Generator integriert werden!</strong></p>\n";
        
        echo "<h3>📋 Integration in generate_test.php:</h3>\n";
        echo "<pre>";
        echo htmlspecialchars('
// Am Anfang der Datei:
require_once __DIR__ . "/../includes/youtube_professional_service.php";

// YouTube-URL Handler ersetzen:
if (!empty($_POST["youtube_url"])) {
    $professionalService = new YouTubeProfessionalService();
    $result = $professionalService->getTranscript($_POST["youtube_url"]);
    
    if ($result["success"]) {
        $combinedContent .= $result["transcript"] . "\n\n";
        error_log("YouTube-Transcript erfolgreich: " . $result["source"] . " (" . $result["length"] . " Zeichen)");
    } else {
        error_log("YouTube-Transcript fehlgeschlagen: " . $result["error"]);
        throw new Exception("YouTube-Video konnte nicht verarbeitet werden: " . $result["error"]);
    }
}');
        echo "</pre>\n";
        echo "</div>\n";
    }
}

// Automatische Tests mit verschiedenen Video-Typen
if (!isset($_POST['test_professional'])) {
    echo "<div class='method-box'>\n";
    echo "<h2>🎬 Automatische Tests mit verschiedenen Video-Typen</h2>\n";
    
    $testVideos = [
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => '🎵 Musik-Video (Rick Roll)',
        'https://www.youtube.com/watch?v=jNQXAC9IVRw' => '📹 Kurzes Video (Me at the zoo)',
        'https://www.youtube.com/watch?v=9bZkp7q19f0' => '🎤 TED Talk (PSY Gangnam Style)'
    ];
    
    foreach ($testVideos as $url => $description) {
        echo "<h3>$description</h3>\n";
        echo "<p><code>" . htmlspecialchars($url) . "</code></p>\n";
        
        $startTime = microtime(true);
        $result = $service->getTranscript($url);
        $duration = microtime(true) - $startTime;
        
        if ($result['success']) {
            echo "<div class='success' style='padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "✅ <strong>ERFOLG!</strong> Quelle: {$result['source']}, Dauer: " . round($duration, 1) . "s, Länge: {$result['length']} Zeichen\n";
            
            if ($result['length'] > 200) {
                echo "<details style='margin-top: 10px;'><summary>Transcript-Vorschau</summary>\n";
                echo "<pre style='max-height: 150px; overflow-y: auto; background: #f9f9f9; padding: 10px;'>";
                echo htmlspecialchars(substr($result['transcript'], 0, 500)) . "...";
                echo "</pre></details>\n";
            }
            echo "</div>\n";
            
            // Bei erstem Erfolg: Zeige welche Methode funktioniert
            static $first_success = true;
            if ($first_success) {
                echo "<div class='info' style='padding: 15px; margin: 15px 0; border-radius: 5px;'>\n";
                echo "<h4>🎯 Funktionierende Methode identifiziert!</h4>\n";
                echo "<p><strong>{$result['source']}</strong> funktioniert auf diesem Server!</p>\n";
                echo "<p>Diese Methode wird für alle weiteren Videos priorisiert.</p>\n";
                echo "</div>\n";
                $first_success = false;
            }
            
        } else {
            echo "<div class='error' style='padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Fehlgeschlagen nach " . round($duration, 1) . "s:</strong> " . htmlspecialchars($result['error']) . "\n";
            echo "</div>\n";
        }
        
        echo "<hr>\n";
    }
    
    echo "</div>\n";
}
?>

<div class="method-box info">
    <h2>🔬 Technische Details</h2>
    <h3>Python-Libraries:</h3>
    <ul>
        <li><strong>youtube-transcript-api:</strong> Direkte Transcript-Extraktion ohne API-Keys</li>
        <li><strong>pytube:</strong> YouTube-Video-Metadaten und Caption-Zugriff</li>
        <li><strong>yt-dlp:</strong> Erweiterte YouTube-Download-Funktionen</li>
    </ul>
    
    <h3>Warum funktioniert das besser?</h3>
    <ul>
        <li>🔄 <strong>8 verschiedene Methoden</strong> parallel</li>
        <li>🎭 <strong>Browser-Simulation</strong> mit rotierenden User-Agents</li>
        <li>📚 <strong>Bewährte Libraries</strong> die downsub & Co. auch nutzen</li>
        <li>⚡ <strong>Automatische Installation</strong> fehlender Dependencies</li>
        <li>🛡️ <strong>Robuste Fallbacks</strong> bei Ausfällen</li>
    </ul>
</div>

<div class="method-box">
    <h2>🔧 Weitere Debug-Tools</h2>
    <p><a href="debug_youtube_python.php" target="_blank" class="btn-primary">🐍 Python-Debug</a></p>
    <p><a href="test_youtube_php.php" target="_blank" class="btn-primary">🔧 PHP-Alternative</a></p>
    <p><a href="create_youtube_solution.php" target="_blank" class="btn-warning">💡 Manuelle Lösung</a></p>
</div>

</body>
</html>
