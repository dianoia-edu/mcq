<?php
/**
 * Test-Script für YouTube Transcript Services
 */

require_once 'includes/youtube_transcript_fetcher.php';

echo "<h1>🧪 YouTube Transcript Service Test</h1>\n";

// Test-Videos (verschiedene Typen)
$testVideos = [
    'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s', // Dein Video
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',      // Rick Roll (populär)
    'https://www.youtube.com/watch?v=jNQXAC9IVRw',      // Me at the zoo (erstes YouTube-Video)
];

if (isset($_GET['test_video'])) {
    $testVideos = [$_GET['test_video']];
}

$fetcher = new YouTubeTranscriptFetcher();

foreach ($testVideos as $videoUrl) {
    echo "<hr>\n";
    $result = $fetcher->testTranscript($videoUrl);
    
    if ($result['success']) {
        echo "<h4>🎯 Diesen Service verwenden:</h4>\n";
        echo "<code>{$result['source']}</code><br>\n";
        break; // Stoppe nach erstem Erfolg
    }
}

?>

<hr>
<h2>🔧 Eigenes Video testen</h2>
<form method="get">
    <label>YouTube-URL:</label><br>
    <input type="url" name="test_video" placeholder="https://www.youtube.com/watch?v=..." style="width: 400px;">
    <button type="submit">Testen</button>
</form>

<h2>💡 Was passiert hier?</h2>
<p>Das Script versucht <strong>5 verschiedene Methoden</strong> um automatisch an YouTube-Untertitel zu kommen:</p>
<ol>
    <li><strong>YouTube API</strong> - Direkte Caption-API</li>
    <li><strong>DownSub.com</strong> - Service-API</li>
    <li><strong>YouTubeTranscript.com</strong> - Service-API</li>
    <li><strong>SaveSubs.com</strong> - Service-API</li>
    <li><strong>YouTube Direct</strong> - HTML-Scraping</li>
</ol>

<p><strong>Funktioniert mindestens einer?</strong> → Integration in Test-Generator möglich!</p>

<?php
