<?php
// YouTube Headless Browser Extractor
header('Content-Type: text/html; charset=utf-8');

// Konfiguration
$outputDir = __DIR__ . '/output';
$logsDir = __DIR__ . '/logs';
$cookieDir = __DIR__ . '/cookies';
$tempDir = sys_get_temp_dir();

// Verzeichnisse erstellen
foreach ([$outputDir, $logsDir, $cookieDir] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Logging-Funktion
function logMessage($message) {
    global $logsDir;
    $logFile = $logsDir . '/headless_' . date('Y-m-d') . '.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp $message";
    echo $logEntry . "<br>\n";
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
    return $logEntry;
}

// Hilfsfunktion: Video-ID aus YouTube-URL extrahieren
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// Node.js-Script erstellen
function createPuppeteerScript($videoId, $username = '', $password = '') {
    global $cookieDir, $tempDir;
    
    $cookieFile = $cookieDir . '/youtube_cookies.json';
    $outputFile = $tempDir . '/yt_result_' . uniqid() . '.json';
    $hasCredentials = !empty($username) && !empty($password);
    
    $loginCode = '';
    if ($hasCredentials) {
        $loginCode = <<<EOT
        // Login durchführen
        console.log('Starte Login-Prozess...');
        await page.goto('https://accounts.google.com/signin');
        
        // E-Mail eingeben
        await page.waitForSelector('input[type="email"]');
        await page.type('input[type="email"]', '$username');
        await page.keyboard.press('Enter');
        
        // Kurze Pause für die Überleitung
        await page.waitForTimeout(2000);
        
        // Passwort eingeben
        await page.waitForSelector('input[type="password"]');
        await page.type('input[type="password"]', '$password');
        await page.keyboard.press('Enter');
        
        // Warten auf erfolgreichen Login
        console.log('Warte auf erfolgreichen Login...');
        await page.waitForNavigation({ waitUntil: 'networkidle0' });
        
EOT;
    }

    $script = <<<EOT
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
    console.log('Starte Headless-Browser...');
    const browser = await puppeteer.launch({
        headless: "new",
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--disable-gpu',
            '--window-size=1920x1080'
        ]
    });
    
    let result = {
        videoId: '$videoId',
        success: false,
        error: null,
        title: null,
        description: null,
        hasSubtitles: false,
        captionText: ''
    };
    
    try {
        const page = await browser.newPage();
        
        // Browser-Fingerprint anpassen
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        await page.setViewport({ width: 1920, height: 1080 });
        
        // Cookies laden, falls vorhanden
        const cookieFile = '$cookieFile';
        if (fs.existsSync(cookieFile)) {
            console.log('Lade bestehende Cookies...');
            const cookiesString = fs.readFileSync(cookieFile, 'utf8');
            const cookies = JSON.parse(cookiesString);
            await page.setCookie(...cookies);
        }
        
        $loginCode
        
        // YouTube-Video besuchen
        console.log('Besuche YouTube-Video...');
        await page.goto('https://www.youtube.com/watch?v=$videoId', { 
            waitUntil: 'networkidle2',
            timeout: 60000
        });
        
        // Überprüfen, ob wir auf das Video zugreifen können
        const isBlocked = await page.evaluate(() => {
            return document.body.innerText.includes("confirm you're not a robot");
        });
        
        if (isBlocked) {
            console.log('Blockierung erkannt. Bot-Erkennung aktiv.');
            result.error = 'YouTube blockiert den Zugriff. Manuelle Anmeldung erforderlich.';
            throw new Error(result.error);
        }
        
        // Cookies speichern
        console.log('Speichere Cookies...');
        const cookies = await page.cookies();
        fs.writeFileSync(cookieFile, JSON.stringify(cookies, null, 2));
        
        // Videotitel extrahieren
        console.log('Extrahiere Videotitel...');
        await page.waitForSelector('h1.ytd-watch-metadata', { timeout: 10000 })
            .catch(() => console.log('Timeout beim Warten auf den Titel.'));
            
        const videoTitle = await page.evaluate(() => {
            const titleElement = document.querySelector('h1.ytd-watch-metadata');
            return titleElement ? titleElement.innerText.trim() : '';
        });
        
        console.log('Videotitel:', videoTitle);
        result.title = videoTitle;
        
        // Videobeschreibung extrahieren
        console.log('Extrahiere Videobeschreibung...');
        const videoDescription = await page.evaluate(() => {
            // Beschreibung aufklappen, falls möglich
            const expandButton = document.querySelector('#expand');
            if (expandButton) expandButton.click();
            
            // Kurze Pause
            return new Promise(resolve => {
                setTimeout(() => {
                    const descriptionElement = document.querySelector('#description-inline-expander, #description-inner');
                    resolve(descriptionElement ? descriptionElement.innerText.trim() : '');
                }, 2000);
            });
        });
        
        console.log('Beschreibung erhalten, Länge:', videoDescription.length);
        result.description = videoDescription;
        
        // Untertitel-Button finden und prüfen
        console.log('Suche nach Untertitel-Option...');
        await page.waitForSelector('.ytp-subtitles-button', { timeout: 5000 })
            .catch(() => console.log('Kein Untertitel-Button gefunden.'));
            
        // Überprüfen, ob Untertitel verfügbar sind
        const hasCaptions = await page.evaluate(() => {
            const captionButton = document.querySelector('.ytp-subtitles-button');
            return captionButton ? !captionButton.classList.contains('ytp-button-disabled') : false;
        });
        
        result.hasSubtitles = hasCaptions;
        
        if (hasCaptions) {
            console.log('Untertitel sind verfügbar. Aktiviere sie...');
            
            // Klicke auf den Untertitel-Button, falls Untertitel nicht bereits aktiviert sind
            const isSubtitleActive = await page.evaluate(() => {
                const captionButton = document.querySelector('.ytp-subtitles-button');
                return captionButton ? captionButton.getAttribute('aria-pressed') === 'true' : false;
            });
            
            if (!isSubtitleActive) {
                await page.click('.ytp-subtitles-button');
                await page.waitForTimeout(1000);
            }
            
            // Starte Video und sammle Untertitel
            console.log('Sammle Untertitel...');
            
            // Maximiere den Untertitel-Erfassungsbereich
            await page.evaluate(() => {
                const video = document.querySelector('video');
                if (video) {
                    video.muted = true;
                    video.play();
                }
            });
            
            // Sammle 90 Sekunden lang Untertitel
            const captionTexts = new Set();
            
            for (let i = 0; i < 60; i++) {
                const newCaptions = await page.evaluate(() => {
                    const captionElements = document.querySelectorAll('.ytp-caption-segment');
                    return Array.from(captionElements).map(el => el.innerText.trim()).filter(text => text.length > 0);
                });
                
                if (newCaptions.length > 0) {
                    newCaptions.forEach(text => captionTexts.add(text));
                    console.log(`Gefundene Untertitel: ${captionTexts.size}`);
                }
                
                // Alle 1,5 Sekunden prüfen
                await page.waitForTimeout(1500);
                
                // Optional: Spule im Video vor, wenn nach 30 Sekunden nichts gefunden wurde
                if (i === 20 && captionTexts.size === 0) {
                    await page.evaluate(() => {
                        const video = document.querySelector('video');
                        if (video) video.currentTime += 30;
                    });
                }
                
                // Optional: Brich ab, wenn genug Untertitel gesammelt wurden
                if (captionTexts.size > 50) break;
            }
            
            // Stoppe Video
            await page.evaluate(() => {
                const video = document.querySelector('video');
                if (video) video.pause();
            });
            
            // Speichere gesammelte Untertitel
            result.captionText = Array.from(captionTexts).join(' ');
            console.log(`Insgesamt ${captionTexts.size} Untertitel-Segmente gesammelt.`);
        } else {
            console.log('Keine Untertitel für dieses Video verfügbar.');
        }
        
        // Prüfe auf alternatives Transkript (manchmal unter dem Video verfügbar)
        console.log('Suche nach Transkript-Tab...');
        const hasTranscriptTab = await page.evaluate(() => {
            // Scrollen, um alle Tabs sichtbar zu machen
            window.scrollBy(0, 500);
            
            // Suche nach Transkript-Tab
            const tabs = document.querySelectorAll('tp-yt-paper-tab');
            for (const tab of tabs) {
                if (tab.innerText.includes('Transkript')) {
                    tab.click();
                    return true;
                }
            }
            return false;
        });
        
        if (hasTranscriptTab) {
            console.log('Transkript-Tab gefunden, warte auf Laden...');
            await page.waitForTimeout(2000);
            
            const transcriptText = await page.evaluate(() => {
                const segments = document.querySelectorAll('yt-formatted-string.segment-text');
                return Array.from(segments).map(el => el.innerText.trim()).join(' ');
            });
            
            if (transcriptText.length > 0) {
                console.log('Transkript aus Tab geladen.');
                result.captionText += ' ' + transcriptText;
            }
        }
        
        result.success = true;
        
    } catch (error) {
        console.error('Fehler:', error.message);
        result.error = error.message;
    } finally {
        // Speichere Ergebnis in die Datei
        console.log('Speichere Ergebnisse...');
        fs.writeFileSync('$outputFile', JSON.stringify(result, null, 2));
        
        await browser.close();
        console.log('Browser geschlossen.');
        
        // Gib Ergebnis zurück
        console.log(JSON.stringify(result));
    }
})();
EOT;

    return [
        'script' => $script,
        'outputFile' => $outputFile
    ];
}

// Shell-Befehl ausführen
function execCommand($command) {
    logMessage("Ausführen: $command");
    $output = [];
    $returnVar = 0;
    exec($command . " 2>&1", $output, $returnVar);
    $outputStr = implode("\n", $output);
    logMessage("Ausgabe (gekürzt): " . substr($outputStr, 0, 300) . (strlen($outputStr) > 300 ? "..." : ""));
    return [
        'output' => $outputStr,
        'exitCode' => $returnVar
    ];
}

// Hauptprogramm
$videoUrl = isset($_POST['video_url']) ? trim($_POST['video_url']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// HTML-Header ausgeben
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Headless Browser Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .success {
            color: green;
            border-left: 4px solid green;
            padding-left: 10px;
        }
        .error {
            color: red;
            border-left: 4px solid red;
            padding-left: 10px;
        }
        .warning {
            color: orange;
            border-left: 4px solid orange;
            padding-left: 10px;
        }
        .info {
            color: #0066cc;
            border-left: 4px solid #0066cc;
            padding-left: 10px;
        }
        form {
            background: #f4f4f4;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], 
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        button {
            padding: 10px 15px;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        pre {
            background: #f9f9f9;
            padding: 10px;
            overflow-x: auto;
            border: 1px solid #ddd;
            max-height: 500px;
            overflow-y: auto;
        }
        .description {
            white-space: pre-line;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        #status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: none;
        }
        .loading {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
    </style>
</head>
<body>
    <h1>YouTube Headless Browser Tool</h1>
    
    <div class="info">
        <p>Dieses Tool verwendet einen Headless-Browser, um YouTube-Videos zu analysieren und Inhalte zu extrahieren.
        Es kann Videobeschreibungen und in manchen Fällen auch Untertitel abrufen.</p>
    </div>
    
    <form method="post" action="" id="extractForm">
        <h3>YouTube-URL eingeben:</h3>
        <input type="text" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($videoUrl); ?>" required>
        
        <h3>Google-Anmeldedaten (optional):</h3>
        <p class="info">Die Anmeldedaten werden nur für die Authentifizierung verwendet und nicht gespeichert.</p>
        
        <label for="username">E-Mail:</label>
        <input type="text" id="username" name="username" placeholder="deine.email@gmail.com" value="<?php echo htmlspecialchars($username); ?>">
        
        <label for="password">Passwort:</label>
        <input type="password" id="password" name="password" placeholder="Dein Passwort">
        
        <button type="submit">Video analysieren</button>
    </form>
    
    <div id="status" class="info loading">
        <p>Browser wird gestartet, bitte warten...</p>
        <p>Dies kann bis zu 2 Minuten dauern. Bitte die Seite nicht aktualisieren.</p>
    </div>

<?php
// Wenn ein Video-URL übermittelt wurde
if (!empty($videoUrl)) {
    $videoId = extractYoutubeId($videoUrl);
    
    if (!$videoId) {
        echo "<h3 class='error'>Ungültige YouTube-URL! Bitte gib eine gültige URL ein.</h3>";
    } else {
        echo "<h2>Verarbeite Video: ID $videoId</h2>";
        logMessage("Starte Verarbeitung für: $videoUrl");
        logMessage("Extrahierte Video-ID: $videoId");
        
        // Prüfen, ob Node.js installiert ist
        $nodeCheck = execCommand("node --version");
        if ($nodeCheck['exitCode'] !== 0) {
            echo "<h3 class='error'>Node.js ist nicht installiert. Bitte installiere Node.js auf dem Server.</h3>";
            echo "<pre>" . htmlspecialchars($nodeCheck['output']) . "</pre>";
            exit;
        }
        
        logMessage("Node.js Version: " . trim($nodeCheck['output']));
        
        // Puppeteer prüfen
        $puppeteerCheck = execCommand("npm list -g puppeteer");
        if (strpos($puppeteerCheck['output'], "puppeteer") === false) {
            echo "<h3 class='error'>Puppeteer ist nicht global installiert. Bitte installiere es mit:</h3>";
            echo "<pre>npm install -g puppeteer</pre>";
            exit;
        }
        
        logMessage("Puppeteer ist installiert.");
        
        // Script erstellen
        $scriptData = createPuppeteerScript($videoId, $username, $password);
        $scriptFile = $tempDir . '/yt_script_' . uniqid() . '.js';
        file_put_contents($scriptFile, $scriptData['script']);
        $outputFile = $scriptData['outputFile'];
        
        // Script ausführen
        echo "<script>document.getElementById('status').style.display = 'block';</script>";
        echo str_repeat(' ', 1024);
        ob_flush();
        flush();
        
        logMessage("Starte Headless-Browser...");
        $result = execCommand("node " . escapeshellarg($scriptFile));
        
        // Script-Datei löschen
        @unlink($scriptFile);
        
        echo "<script>document.getElementById('status').style.display = 'none';</script>";
        
        if ($result['exitCode'] !== 0) {
            echo "<h3 class='error'>Fehler bei der Ausführung des Headless-Browsers:</h3>";
            echo "<pre>" . htmlspecialchars($result['output']) . "</pre>";
        } else {
            // Ergebnisse aus der Output-Datei lesen
            if (file_exists($outputFile)) {
                $jsonContent = file_get_contents($outputFile);
                $data = json_decode($jsonContent, true);
                @unlink($outputFile); // Temporäre Datei löschen
                
                if ($data && isset($data['success']) && $data['success']) {
                    // Video-Informationen anzeigen
                    echo "<h3 class='success'>Video erfolgreich analysiert:</h3>";
                    echo "<p><strong>Titel:</strong> " . htmlspecialchars($data['title']) . "</p>";
                    
                    // Speichern der strukturierten Daten
                    $videoData = [
                        'id' => $videoId,
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'has_subtitles' => $data['hasSubtitles'],
                        'caption_text' => $data['captionText'],
                        'processed_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $jsonFile = "$outputDir/video_$videoId.json";
                    file_put_contents($jsonFile, json_encode($videoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    
                    // Extrahiere Kapitelmarken
                    $chapters = [];
                    preg_match_all('/(\d+:\d+(?::\d+)?)\s+(.+)/', $data['description'], $matches, PREG_SET_ORDER);
                    
                    if (!empty($matches)) {
                        echo "<h3 class='info'>Gefundene Kapitel:</h3>";
                        echo "<ul>";
                        foreach ($matches as $match) {
                            $timestamp = $match[1];
                            $chapterTitle = $match[2];
                            echo "<li><strong>$timestamp</strong> - " . htmlspecialchars($chapterTitle) . "</li>";
                            $chapters[] = ['time' => $timestamp, 'title' => $chapterTitle];
                        }
                        echo "</ul>";
                    }
                    
                    // Videobeschreibung anzeigen
                    echo "<h3>Videobeschreibung:</h3>";
                    echo "<div class='description'>" . nl2br(htmlspecialchars($data['description'])) . "</div>";
                    
                    // Untertitel, falls vorhanden
                    if ($data['hasSubtitles'] && !empty($data['captionText'])) {
                        echo "<h3 class='success'>Extrahierte Untertitel:</h3>";
                        echo "<pre>" . htmlspecialchars($data['captionText']) . "</pre>";
                        
                        // Untertitel als separate Datei speichern
                        $captionFile = "$outputDir/caption_$videoId.txt";
                        file_put_contents($captionFile, $data['captionText']);
                        echo "<p>Untertitel gespeichert in: $captionFile</p>";
                    } else {
                        echo "<h3 class='warning'>Keine Untertitel extrahiert.</h3>";
                        
                        if ($data['hasSubtitles']) {
                            echo "<p>Das Video hat Untertitel, aber sie konnten nicht extrahiert werden. " . 
                                 "Dies kann daran liegen, dass sie dynamisch geladen werden oder ein spezielles Format haben.</p>";
                        } else {
                            echo "<p>Dieses Video hat keine Untertitel.</p>";
                        }
                    }
                    
                    echo "<p>Alle Daten wurden gespeichert in: $jsonFile</p>";
                    
                } else {
                    echo "<h3 class='error'>Fehler bei der Analyse des Videos:</h3>";
                    $errorMsg = isset($data['error']) ? $data['error'] : "Unbekannter Fehler";
                    echo "<p>" . htmlspecialchars($errorMsg) . "</p>";
                    echo "<pre>" . htmlspecialchars($result['output']) . "</pre>";
                }
            } else {
                echo "<h3 class='error'>Keine Ergebnisdatei gefunden. Fehler bei der Verarbeitung:</h3>";
                echo "<pre>" . htmlspecialchars($result['output']) . "</pre>";
            }
        }
    }
}
?>

<script>
document.getElementById('extractForm').addEventListener('submit', function() {
    document.getElementById('status').style.display = 'block';
});
</script>

</body>
</html> 