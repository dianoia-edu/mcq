<?php
/**
 * YouTube-Lösung: Da BaseURLs blockiert werden, erstelle eine Lösung
 * die den Nutzer bei der Transcript-Beschaffung unterstützt
 */

require_once 'includes/youtube_transcript_php.php';

$service = new YouTubeTranscriptPHP();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🎯 YouTube-Lösung für Test-Generator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .solution-box { margin: 20px 0; padding: 20px; border-radius: 8px; }
        .success { background: #e8f5e8; border: 2px solid #4caf50; }
        .info { background: #e3f2fd; border: 2px solid #2196f3; }
        .warning { background: #fff3e0; border: 2px solid #ff9800; }
        .error { background: #ffebee; border: 2px solid #f44336; }
        button { padding: 12px 24px; font-size: 16px; cursor: pointer; margin: 5px; }
        .btn-primary { background: #2196f3; color: white; border: none; border-radius: 4px; }
        .btn-success { background: #4caf50; color: white; border: none; border-radius: 4px; }
        textarea { width: 100%; height: 200px; padding: 10px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; border-radius: 4px; }
        .step { margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #2196f3; }
        .copy-button { font-size: 12px; padding: 5px 10px; }
    </style>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('In Zwischenablage kopiert!');
            });
        }
        
        function showManualInput() {
            document.getElementById('manualInputSection').style.display = 'block';
        }
    </script>
</head>
<body>

<h1>🎯 YouTube-Lösung für Test-Generator</h1>

<div class="solution-box error">
    <h2>❌ Problem diagnostiziert</h2>
    <p><strong>YouTube blockiert automatische Transcript-Zugriffe vom Server:</strong></p>
    <ul>
        <li>✅ Caption-Tracks werden gefunden</li>
        <li>❌ BaseURL-Requests werden blockiert (0 Zeichen)</li>
        <li>❌ Alle Header-Kombinationen schlagen fehl</li>
        <li>❌ Python und PHP haben dasselbe Problem</li>
    </ul>
    <p><strong>Grund:</strong> YouTube erkennt Server-Requests als Bots und blockiert sie.</p>
</div>

<div class="solution-box info">
    <h2>💡 Praktische Lösungen</h2>
    
    <h3>🔧 Lösung 1: Halbautomatisch (Empfohlen)</h3>
    <div class="step">
        <h4>Schritt 1: YouTube-Link erkennen</h4>
        <p>Test-Generator erkennt YouTube-URLs und zeigt Hilfe-Anweisungen</p>
    </div>
    
    <div class="step">
        <h4>Schritt 2: Benutzer-Anleitung</h4>
        <p>Zeige dem Benutzer, wie er Untertitel manuell kopiert:</p>
        <ol>
            <li>YouTube-Video öffnen</li>
            <li>Untertitel aktivieren (CC-Button)</li>
            <li>Drei-Punkte-Menü → "Transkript anzeigen"</li>
            <li>Text kopieren und einfügen</li>
        </ol>
    </div>
    
    <div class="step">
        <h4>Schritt 3: Automatische Bereinigung</h4>
        <p>Der eingefügte Text wird automatisch bereinigt (Timestamps entfernt, etc.)</p>
    </div>
</div>

<div class="solution-box success">
    <h2>🚀 Sofort umsetzbare Lösung</h2>
    <p>Ich kann eine <strong>YouTube-Hilfe-Komponente</strong> für den Test-Generator erstellen:</p>
    
    <h3>📋 Features:</h3>
    <ul>
        <li>✅ <strong>YouTube-URL-Erkennung</strong></li>
        <li>✅ <strong>Schritt-für-Schritt-Anleitung</strong></li>
        <li>✅ <strong>Automatische Text-Bereinigung</strong></li>
        <li>✅ <strong>Transcript-Qualitätsprüfung</strong></li>
        <li>✅ <strong>Fallback für andere Inhaltsquellen</strong></li>
    </ul>
    
    <button class="btn-success" onclick="document.getElementById('implementationCode').style.display='block'">
        📝 Implementation anzeigen
    </button>
</div>

<div id="implementationCode" style="display: none;" class="solution-box info">
    <h2>💻 Code für generate_test.php</h2>
    
    <h3>JavaScript für YouTube-Erkennung:</h3>
    <pre><code>// YouTube-URL erkennen und Hilfe anzeigen
function checkForYouTubeURL() {
    const youtubeUrl = document.getElementById('youtube_url').value;
    const isYouTube = /youtube\.com|youtu\.be/.test(youtubeUrl);
    
    if (isYouTube && youtubeUrl.trim() !== '') {
        document.getElementById('youtubeHelper').style.display = 'block';
        showYouTubeInstructions(youtubeUrl);
    } else {
        document.getElementById('youtubeHelper').style.display = 'none';
    }
}

function showYouTubeInstructions(url) {
    document.getElementById('youtubeLink').href = url;
    document.getElementById('youtubeLink').textContent = url;
}</code></pre>
    
    <button class="copy-button btn-primary" onclick="copyToClipboard(document.querySelector('pre code').textContent)">
        📋 Kopieren
    </button>
    
    <h3>PHP für Text-Bereinigung:</h3>
    <pre><code><?php
function cleanYouTubeTranscript($text) {
    // Timestamps entfernen (z.B. "0:15", "1:23:45")
    $text = preg_replace('/\d{1,2}:\d{2}(?::\d{2})?\s*/', '', $text);
    
    // Zeilennummern entfernen
    $text = preg_replace('/^\d+\s*$/m', '', $text);
    
    // Mehrfache Leerzeichen/Zeilenumbrüche
    $text = preg_replace('/\s+/', ' ', $text);
    $text = preg_replace('/\n+/', '\n', $text);
    
    return trim($text);
}

// Usage in generate_test.php:
if (!empty($_POST['youtube_url']) && !empty($_POST['manual_transcript'])) {
    $content = cleanYouTubeTranscript($_POST['manual_transcript']);
    // Weiter mit Test-Generation...
}
?></code></pre>
    
    <button class="copy-button btn-primary" onclick="copyToClipboard(document.querySelectorAll('pre code')[1].textContent)">
        📋 Kopieren
    </button>
</div>

<div class="solution-box warning">
    <h2>🧪 Test der Bereinigungsfunktion</h2>
    <p>Teste hier, wie die automatische Text-Bereinigung funktioniert:</p>
    
    <form method="post">
        <h4>Beispiel YouTube-Transcript (mit Timestamps):</h4>
        <textarea name="test_transcript" placeholder="Füge hier einen YouTube-Transcript mit Timestamps ein...
Beispiel:
0:15 Welcome to this video
0:18 Today we're going to learn
1:23 about important topics
..."><?= htmlspecialchars($_POST['test_transcript'] ?? '') ?></textarea>
        <br>
        <button type="submit" name="clean_test" class="btn-primary">🧹 Text bereinigen</button>
    </form>
    
    <?php
    if (isset($_POST['clean_test']) && !empty($_POST['test_transcript'])) {
        function cleanYouTubeTranscript($text) {
            // Timestamps entfernen
            $text = preg_replace('/\d{1,2}:\d{2}(?::\d{2})?\s*/', '', $text);
            // Zeilennummern entfernen
            $text = preg_replace('/^\d+\s*$/m', '', $text);
            // Mehrfache Leerzeichen/Zeilenumbrüche
            $text = preg_replace('/\s+/', ' ', $text);
            $text = preg_replace('/\n+/', '\n', $text);
            return trim($text);
        }
        
        $cleaned = cleanYouTubeTranscript($_POST['test_transcript']);
        
        echo "<h4>✅ Bereinigter Text:</h4>\n";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 4px;'>\n";
        echo "<pre>" . htmlspecialchars($cleaned) . "</pre>\n";
        echo "</div>\n";
        
        echo "<p><strong>Länge:</strong> " . strlen($cleaned) . " Zeichen</p>\n";
        
        if (strlen($cleaned) > 100) {
            echo "<div class='success' style='padding: 10px; margin: 10px 0;'>\n";
            echo "✅ <strong>Text ist lang genug für Test-Generation!</strong>\n";
            echo "</div>\n";
        }
    }
    ?>
</div>

<div class="solution-box info">
    <h2>🎯 Nächste Schritte</h2>
    <ol>
        <li><strong>Soll ich die YouTube-Hilfe-Komponente implementieren?</strong></li>
        <li><strong>Integration in den bestehenden Test-Generator?</strong></li>
        <li><strong>UI/UX für die Schritt-für-Schritt-Anleitung?</strong></li>
    </ol>
    
    <p>Diese Lösung ist <strong>praktisch und benutzerfreundlich</strong> - der User bekommt klare Anweisungen und der Text wird automatisch bereinigt!</p>
</div>

</body>
</html>
