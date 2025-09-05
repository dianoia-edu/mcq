<?php
/**
 * Robuster YouTube Transcript Test
 * Testet alle verfügbaren Methoden
 */

require_once 'includes/youtube_transcript_service.php';

$service = new YouTubeTranscriptService(60, true); // 60s Timeout, Debug an

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🎯 Robuster YouTube Transcript Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #e8f5e8; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .system-check { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .check-item { padding: 10px; border-radius: 3px; text-align: center; }
        .check-ok { background: #e8f5e8; }
        .check-fail { background: #ffebee; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
        input[type="url"] { width: 100%; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🎯 Robuster YouTube Transcript Test</h1>

<?php
// System-Check anzeigen
echo "<div class='test-section'>\n";
echo "<h2>🔧 System-Check</h2>\n";

$systemChecks = $service->checkSystem();
echo "<div class='system-check'>\n";

foreach ($systemChecks as $component => $status) {
    $class = $status ? 'check-ok' : 'check-fail';
    $icon = $status ? '✅' : '❌';
    echo "<div class='check-item $class'>$icon $component</div>\n";
}

echo "</div>\n";

// Probleme erkennen und Lösungen vorschlagen
$problems = [];
if (!$systemChecks['python_script']) {
    $problems[] = "Python-Script fehlt";
}
if (!$systemChecks['python3'] && !$systemChecks['python']) {
    $problems[] = "Python nicht installiert";
}
if (!$systemChecks['proc_open'] && !$systemChecks['shell_exec']) {
    $problems[] = "Keine Process-Execution möglich";
}

if ($problems) {
    echo "<div class='warning'>\n";
    echo "<h3>⚠️ Probleme gefunden:</h3>\n";
    echo "<ul>\n";
    foreach ($problems as $problem) {
        echo "<li>$problem</li>\n";
    }
    echo "</ul>\n";
    
    if (!$systemChecks['python3'] && !$systemChecks['python']) {
        echo "<p><strong>Lösung:</strong></p>\n";
        echo "<pre>apt-get update && apt-get install python3 python3-pip\npip3 install requests</pre>\n";
    }
    
    echo "</div>\n";
}

echo "</div>\n";

// Test-Formular
echo "<div class='test-section'>\n";
echo "<h2>🧪 YouTube Video testen</h2>\n";
?>

<form method="post">
    <label><strong>YouTube-URL eingeben:</strong></label>
    <input type="url" name="test_url" value="<?= isset($_POST['test_url']) ? htmlspecialchars($_POST['test_url']) : 'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s' ?>" placeholder="https://www.youtube.com/watch?v=...">
    <button type="submit" name="test_transcript">🚀 Transcript extrahieren</button>
</form>

<?php
echo "</div>\n";

// Wenn Test ausgeführt wird
if (isset($_POST['test_transcript']) && !empty($_POST['test_url'])) {
    echo "<div class='test-section'>\n";
    
    $videoUrl = $_POST['test_url'];
    $result = $service->testTranscript($videoUrl);
    
    echo "</div>\n";
    
    // Bei Erfolg: Integration-Hinweise anzeigen
    if ($result['success']) {
        echo "<div class='test-section success'>\n";
        echo "<h2>🎉 Integration möglich!</h2>\n";
        echo "<p><strong>Diese Methode funktioniert und kann in den Test-Generator integriert werden.</strong></p>\n";
        
        echo "<h3>📋 Nächste Schritte:</h3>\n";
        echo "<ol>\n";
        echo "<li>Bestätige, dass der Transcript korrekt ist</li>\n";
        echo "<li>Falls ja → Integration in <code>teacher/generate_test.php</code></li>\n";
        echo "<li>YouTube-URL eingegeben → Automatischer Transcript → Test generieren</li>\n";
        echo "</ol>\n";
        
        echo "<h3>🔧 Code-Integration:</h3>\n";
        echo "<pre>";
        echo htmlspecialchars('
// In generate_test.php hinzufügen:
if (!empty($youtube_url)) {
    require_once __DIR__ . "/../includes/youtube_transcript_service.php";
    $transcriptService = new YouTubeTranscriptService();
    $result = $transcriptService->getTranscript($youtube_url);
    
    if ($result["success"]) {
        $content = $result["transcript"];
        // Weiter mit Test-Generation...
    } else {
        // Fallback: Manueller Input anfordern
    }
}');
        echo "</pre>\n";
        
        echo "</div>\n";
    }
}

// Standard-Tests durchführen
if (!isset($_POST['test_transcript'])) {
    echo "<div class='test-section'>\n";
    echo "<h2>🎬 Vorher-Test (bekannte Videos)</h2>\n";
    
    $testVideos = [
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => 'Rick Astley - Never Gonna Give You Up',
        'https://www.youtube.com/watch?v=jNQXAC9IVRw' => 'Me at the zoo (erstes YouTube-Video)',
    ];
    
    foreach ($testVideos as $url => $title) {
        echo "<h3>📺 $title</h3>\n";
        echo "<p><code>" . htmlspecialchars($url) . "</code></p>\n";
        
        $result = $service->getTranscript($url);
        
        if ($result['success']) {
            echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0;'>\n";
            echo "✅ <strong>Funktioniert!</strong> Quelle: {$result['source']}, Länge: {$result['length']} Zeichen\n";
            echo "</div>\n";
            
            // Ersten erfolgreichen Test anzeigen
            static $first_success = true;
            if ($first_success) {
                echo "<details><summary>Transcript-Vorschau</summary>\n";
                echo "<pre>" . htmlspecialchars(substr($result['transcript'], 0, 500)) . "...</pre>\n";
                echo "</details>\n";
                $first_success = false;
            }
            
        } else {
            echo "<div style='background: #ffebee; padding: 10px; margin: 10px 0;'>\n";
            echo "❌ <strong>Fehlgeschlagen:</strong> " . htmlspecialchars($result['error']) . "\n";
            echo "</div>\n";
        }
        
        echo "<hr>\n";
    }
    
    echo "</div>\n";
}
?>

<div class="test-section">
    <h2>💡 Über diese Lösung</h2>
    <p><strong>Warum funktioniert das besser?</strong></p>
    <ul>
        <li>🔄 <strong>5 verschiedene Methoden</strong> werden nacheinander versucht</li>
        <li>🎭 <strong>Browser-Simulation</strong> mit realistischen Headers</li>
        <li>📱 <strong>Mobile APIs</strong> haben weniger Restrictions</li>
        <li>🔍 <strong>HTML-Parsing</strong> extrahiert Caption-URLs direkt</li>
        <li>🚀 <strong>InnerTube API</strong> nutzt YouTubes interne Schnittstelle</li>
    </ul>
    
    <p><strong>Das ist derselbe Ansatz, den die erfolgreichen Transcript-Services verwenden!</strong></p>
</div>

</body>
</html>
