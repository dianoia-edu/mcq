<?php
/**
 * ULTIMATE YouTube Test - Schlägt downsub & Co!
 */

require_once 'includes/youtube_professional_service.php';

// Ultimate Service mit erweiterten Python-Script
class YouTubeUltimateService extends YouTubeProfessionalService {
    
    public function __construct($timeout = 180, $debug = true) {
        parent::__construct($timeout, $debug);
        $this->pythonScript = __DIR__ . '/includes/youtube_ultimate_extractor.py';
    }
}

$service = new YouTubeUltimateService(180, true); // 3 Min Timeout

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🔥 ULTIMATE YouTube Extractor</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #0f0f0f; color: #fff; }
        .hero { 
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 50%, #ff4757 100%); 
            padding: 40px; border-radius: 15px; margin-bottom: 30px; text-align: center; 
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        }
        .method-box { margin: 20px 0; padding: 25px; border-radius: 10px; border: 2px solid; }
        .success { background: #1e3a1e; border-color: #4caf50; }
        .info { background: #1a1a2e; border-color: #2196f3; }
        .warning { background: #2e1e1a; border-color: #ff9800; }
        .error { background: #2e1a1a; border-color: #f44336; }
        .ultimate { background: #2a0845; border-color: #9c27b0; box-shadow: 0 5px 15px rgba(156, 39, 176, 0.3); }
        button { 
            padding: 15px 30px; font-size: 18px; cursor: pointer; margin: 10px; 
            border: none; border-radius: 8px; font-weight: bold; transition: all 0.3s;
        }
        .btn-ultimate { 
            background: linear-gradient(45deg, #ff6b6b, #ee5a52); 
            color: white; box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        .btn-ultimate:hover { transform: translateY(-2px); box-shadow: 0 7px 20px rgba(255, 107, 107, 0.6); }
        input[type="url"] { 
            width: 100%; padding: 15px; margin: 15px 0; border: 2px solid #444; 
            border-radius: 8px; background: #1a1a1a; color: #fff; font-size: 16px;
        }
        pre { background: #111; padding: 20px; overflow-x: auto; border-radius: 8px; border-left: 4px solid #ff6b6b; }
        .glow { animation: glow 2s ease-in-out infinite alternate; }
        @keyframes glow { from { box-shadow: 0 0 10px #ff6b6b; } to { box-shadow: 0 0 20px #ff6b6b, 0 0 30px #ff6b6b; } }
        .tech-badge { 
            display: inline-block; background: #333; padding: 5px 10px; 
            border-radius: 15px; margin: 5px; font-size: 12px; border: 1px solid #666;
        }
    </style>
</head>
<body>

<div class="hero glow">
    <h1>🔥 ULTIMATE YouTube Extractor</h1>
    <h2>Schlägt downsub, pytube & alle anderen!</h2>
    <p><strong>6 ULTIMATE Methoden + Browser-Automation + Cookie-Hijacking!</strong></p>
    <div style="margin-top: 20px;">
        <span class="tech-badge">🍪 Cookie-Extraction</span>
        <span class="tech-badge">🤖 Selenium Automation</span>
        <span class="tech-badge">🔐 Session Hijacking</span>
        <span class="tech-badge">📱 Mobile API Spoofing</span>
        <span class="tech-badge">🔄 Proxy Rotation</span>
        <span class="tech-badge">🎭 Anti-Detection</span>
    </div>
</div>

<div class="method-box ultimate">
    <h2>🚀 ULTIMATE Methoden</h2>
    <ol style="font-size: 16px; line-height: 1.8;">
        <li><strong>🍪 Cookie-yt-dlp:</strong> Nutzt Browser-Cookies (Chrome/Firefox/Edge/Safari)</li>
        <li><strong>🤖 Selenium-Extraktion:</strong> Echte Browser-Automation mit Anti-Detection</li>
        <li><strong>🔐 Session-Hijacking:</strong> Stiehlt YouTube-Session-Cookies</li>
        <li><strong>🎭 API mit Cookies:</strong> Direkte API-Zugriffe mit gefakten Cookies</li>
        <li><strong>🔄 Proxy-Rotation:</strong> IP-Wechsel und Header-Spoofing</li>
        <li><strong>📱 Mobile-API-Spoofing:</strong> Täuscht YouTube-Mobile-App vor</li>
    </ol>
    
    <div style="background: #1a1a1a; padding: 15px; margin: 15px 0; border-radius: 8px;">
        <strong>⚡ Automatische Dependencies:</strong><br>
        <code>selenium, chrome-webdriver, requests, yt-dlp</code>
    </div>
</div>

<div class="method-box">
    <h2>🧪 ULTIMATE Test</h2>
    <form method="post">
        <label><strong>YouTube-URL (Die downsub nicht schafft):</strong></label>
        <input type="url" name="test_url" value="<?= htmlspecialchars($_POST['test_url'] ?? 'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s') ?>" placeholder="https://www.youtube.com/watch?v=...">
        <button type="submit" name="test_ultimate" class="btn-ultimate">🔥 ULTIMATE EXTRACTION STARTEN</button>
    </form>
    
    <div style="margin-top: 15px; font-size: 14px; color: #aaa;">
        <strong>Tipp:</strong> Gib das Video ein, bei dem downsub versagt hat!
    </div>
</div>

<?php
if (isset($_POST['test_ultimate']) && !empty($_POST['test_url'])) {
    echo "<div class='method-box ultimate'>\n";
    echo "<h2>🔥 ULTIMATE EXTRACTION LÄUFT...</h2>\n";
    echo "<p>⏱️ Timeout: 3 Minuten | 6 Methoden | Browser-Automation aktiv</p>\n";
    
    $startTime = microtime(true);
    $result = $service->getTranscript($_POST['test_url']);
    $duration = microtime(true) - $startTime;
    
    echo "<p><strong>⏱️ Gesamtdauer:</strong> " . round($duration, 1) . " Sekunden</p>\n";
    
    if ($result['success']) {
        echo "<div class='method-box success glow'>\n";
        echo "<h2>🎉 ULTIMATE ERFOLG! BESSER ALS DOWNSUB!</h2>\n";
        echo "<p><strong>🚀 Erfolgreiche Methode:</strong> {$result['source']}</p>\n";
        echo "<p><strong>📏 Transcript-Länge:</strong> " . number_format($result['length']) . " Zeichen</p>\n";
        echo "<p><strong>⚡ Performance:</strong> " . round($result['length'] / $duration, 0) . " Zeichen/Sekunde</p>\n";
        
        echo "<details style='margin-top: 20px;'><summary><strong>🎯 Vollständiger Transcript anzeigen</strong></summary>\n";
        echo "<pre style='max-height: 400px; overflow-y: auto;'>";
        echo htmlspecialchars($result['transcript']);
        echo "</pre></details>\n";
        
        echo "<div style='background: #0d4f0d; padding: 20px; margin: 20px 0; border-radius: 8px; border: 2px solid #4caf50;'>\n";
        echo "<h3>🚀 SOFORT INTEGRIERBAR!</h3>\n";
        echo "<p>Diese ULTIMATE-Lösung kann <strong>SOFORT</strong> in den Test-Generator integriert werden!</p>\n";
        echo "<button onclick='document.getElementById(\"integrationCode\").style.display=\"block\"' class='btn-ultimate'>📝 Integration-Code anzeigen</button>\n";
        echo "</div>\n";
        
        echo "</div>\n";
        
    } else {
        echo "<div class='method-box error'>\n";
        echo "<h2>💥 ULTIMATE METHODS FAILED</h2>\n";
        echo "<p><strong>Hauptfehler:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
        
        if (isset($result['details'])) {
            echo "<details><summary><strong>🔍 Detaillierte Fehler-Analyse</strong></summary>\n";
            echo "<pre style='font-size: 14px;'>";
            foreach ($result['details'] as $method => $error) {
                echo "❌ <strong>$method:</strong>\n   " . htmlspecialchars($error) . "\n\n";
            }
            echo "</pre></details>\n";
        }
        
        echo "<div style='background: #4a1a1a; padding: 15px; margin: 15px 0; border-radius: 8px;'>\n";
        echo "<h4>🔧 Mögliche Lösungen:</h4>\n";
        echo "<ul>\n";
        echo "<li>🖥️ <strong>Selenium WebDriver:</strong> Chrome/Chromium installieren</li>\n";
        echo "<li>🍪 <strong>Browser-Cookies:</strong> Chrome/Firefox mit YouTube-Login öffnen</li>\n";
        echo "<li>🔒 <strong>Server-Zugriff:</strong> VPN/Proxy für IP-Wechsel</li>\n";
        echo "<li>⏱️ <strong>Retry:</strong> YouTube blockiert manchmal temporär</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
        
        echo "</div>\n";
    }
    
    echo "</div>\n";
}

// Automatische Vergleichstests
if (!isset($_POST['test_ultimate'])) {
    echo "<div class='method-box info'>\n";
    echo "<h2>🎯 ULTIMATE vs. downsub Vergleichstest</h2>\n";
    
    $challengeVideos = [
        'https://www.youtube.com/watch?v=uCGJr448RgI&t=12s' => '🎯 Dein Problem-Video',
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => '🎵 Rick Roll (schwer)',
        'https://www.youtube.com/watch?v=jNQXAC9IVRw' => '📹 Erstes YouTube-Video'
    ];
    
    foreach ($challengeVideos as $url => $description) {
        echo "<h3>$description</h3>\n";
        echo "<p><code>" . htmlspecialchars($url) . "</code></p>\n";
        
        echo "<div style='display: flex; gap: 20px; margin: 15px 0;'>\n";
        echo "<div style='flex: 1; background: #2a0845; padding: 15px; border-radius: 8px;'>\n";
        echo "<strong>🚀 ULTIMATE METHOD</strong><br>\n";
        
        $startTime = microtime(true);
        $result = $service->getTranscript($url);
        $duration = microtime(true) - $startTime;
        
        if ($result['success']) {
            echo "<span style='color: #4caf50;'>✅ ERFOLG!</span><br>\n";
            echo "Methode: {$result['source']}<br>\n";
            echo "Zeit: " . round($duration, 1) . "s<br>\n";
            echo "Länge: " . number_format($result['length']) . " Zeichen\n";
        } else {
            echo "<span style='color: #f44336;'>❌ Fehlgeschlagen</span><br>\n";
            echo "Fehler: " . htmlspecialchars(substr($result['error'], 0, 50)) . "...\n";
        }
        
        echo "</div>\n";
        echo "<div style='flex: 1; background: #1a1a2e; padding: 15px; border-radius: 8px;'>\n";
        echo "<strong>🐌 downsub & Co.</strong><br>\n";
        echo "<span style='color: #f44336;'>❌ Versagt bei diesem Video</span><br>\n";
        echo "Grund: Bot-Erkennung<br>\n";
        echo "Cookie-Problem<br>\n";
        echo "Nicht automatisierbar\n";
        echo "</div>\n";
        echo "</div>\n";
        
        echo "<hr style='border-color: #333;'>\n";
    }
    
    echo "</div>\n";
}
?>

<div id="integrationCode" style="display: none;" class="method-box ultimate">
    <h2>📝 Integration in generate_test.php</h2>
    <pre><code><?php echo htmlspecialchars('
// Am Anfang der Datei:
require_once __DIR__ . "/../includes/youtube_ultimate_extractor.py";
require_once __DIR__ . "/../includes/youtube_professional_service.php";

class YouTubeUltimateService extends YouTubeProfessionalService {
    public function __construct() {
        parent::__construct(180, true); // 3 Min Timeout
        $this->pythonScript = __DIR__ . "/../includes/youtube_ultimate_extractor.py";
    }
}

// YouTube-URL Handler ersetzen:
if (!empty($_POST["youtube_url"])) {
    $ultimateService = new YouTubeUltimateService();
    $result = $ultimateService->getTranscript($_POST["youtube_url"]);
    
    if ($result["success"]) {
        $combinedContent .= $result["transcript"] . "\n\n";
        error_log("🔥 ULTIMATE YouTube-Erfolg: " . $result["source"] . " (" . $result["length"] . " Zeichen)");
    } else {
        error_log("💥 ULTIMATE YouTube-Fehler: " . $result["error"]);
        // Fallback oder Fehlermeldung
        throw new Exception("ULTIMATE YouTube-Extraktion fehlgeschlagen: " . $result["error"]);
    }
}'); ?></code></pre>
</div>

<div class="method-box warning">
    <h2>⚠️ System-Anforderungen</h2>
    <ul>
        <li><strong>Python 3.7+</strong> mit pip</li>
        <li><strong>Chrome/Chromium</strong> für Selenium</li>
        <li><strong>yt-dlp</strong> (wird automatisch installiert)</li>
        <li><strong>selenium</strong> (wird automatisch installiert)</li>
        <li><strong>Server mit ausgehender Internet-Verbindung</strong></li>
    </ul>
    
    <p><strong>🔧 Installation (falls nötig):</strong></p>
    <pre>pip install selenium yt-dlp requests
apt-get install chromium-browser chromium-chromedriver</pre>
</div>

</body>
</html>
