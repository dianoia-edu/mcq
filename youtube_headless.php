<?php
// Sofortige Ausgabe aktivieren
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Maximale Ausführungszeit auf 10 Minuten setzen
ini_set('max_execution_time', 600);
set_time_limit(600);

// Fehlerbehandlung verbessern
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug-Modus aktivieren
$DEBUG = true;

// Verzeichnisse festlegen - verwende bestehende Verzeichnisse, die bereits Berechtigungen haben
$outputDir = __DIR__ . '/output';
$logsDir = __DIR__ . '/logs';
$tempDir = __DIR__ . '/temp';
$cookieDir = $tempDir; // Cookies im bereits existierenden temp-Verzeichnis speichern

// Prüfe auf Schreibrechte, anstatt Verzeichnisse zu erstellen
function checkDirectoryPermissions($dir) {
    if (!file_exists($dir)) {
        return [
            'exists' => false,
            'writable' => false,
            'message' => "Verzeichnis $dir existiert nicht"
        ];
    }
    
    if (!is_writable($dir)) {
        return [
            'exists' => true,
            'writable' => false,
            'message' => "Verzeichnis $dir ist nicht beschreibbar"
        ];
    }
    
    return [
        'exists' => true,
        'writable' => true,
        'message' => "Verzeichnis $dir ist beschreibbar"
    ];
}

// Überprüfe die notwendigen Verzeichnisse
$dirChecks = [];
foreach ([$outputDir, $logsDir, $tempDir] as $dir) {
    $dirChecks[$dir] = checkDirectoryPermissions($dir);
}

// Logging-Funktion
function logMessage($message, $level = 'INFO') {
    global $logsDir;
    $logFile = $logsDir . '/youtube_headless_' . date('Y-m-d') . '.log';
    
    // Prüfe, ob ins Log geschrieben werden kann
    $canWriteLog = is_writable($logsDir) || (file_exists($logFile) && is_writable($logFile));
    
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp [$level] $message";
    
    // Log in Datei schreiben, wenn möglich
    if ($canWriteLog) {
        file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
    }
    
    // Ausgabe im Browser mit Farben
    $color = '#333';
    switch ($level) {
        case 'ERROR': $color = '#f44336'; break;
        case 'WARNING': $color = '#ff9800'; break;
        case 'SUCCESS': $color = '#4caf50'; break;
        case 'INFO': $color = '#2196f3'; break;
        case 'DEBUG': $color = '#9e9e9e'; break;
    }
    
    echo "<div style='color:$color; margin:2px 0;'>$logEntry</div>";
    
    // Sofort ausgeben für Live-Debugging
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
    
    return $logEntry;
}

// Status der Verzeichnisse anzeigen
foreach ($dirChecks as $dir => $check) {
    if (!$check['exists']) {
        logMessage($check['message'], 'ERROR');
    } elseif (!$check['writable']) {
        logMessage($check['message'], 'ERROR');
    } else {
        logMessage($check['message'], 'SUCCESS');
    }
}

// Hilfsfunktion: Video-ID aus YouTube-URL extrahieren
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// Browser mit Proxy-Unterstützung starten
function createPuppeteerScript($videoId, $username = '', $password = '', $proxyUrl = '') {
    global $cookieDir, $tempDir;
    
    $cookieFile = $cookieDir . '/youtube_cookies.json';
    $debugLogFile = $tempDir . '/puppeteer_debug_' . uniqid() . '.log';
    $outputFile = $tempDir . '/yt_result_' . uniqid() . '.json';
    $hasCredentials = !empty($username) && !empty($password);
    $hasProxy = !empty($proxyUrl);
    
    // Speichere den Output-Dateinamen, um ihn später zu verwenden
    $GLOBALS['outputFilePath'] = $outputFile;
    
    // Proxy-Konfiguration für Puppeteer
    $proxyArgs = '';
    if ($hasProxy) {
        $proxyArgs = <<<EOT
            '--proxy-server=$proxyUrl',
EOT;
    }
    
    $loginCode = '';
    if ($hasCredentials) {
        $loginCode = <<<EOT
        // Login-Versuch, wenn Anmeldedaten angegeben wurden
        try {
            console.log('Versuche YouTube-Login...');
            await page.goto('https://accounts.google.com/signin/v2/identifier?service=youtube', { waitUntil: 'networkidle2' });
            
            // E-Mail eingeben
            await page.type('input[type="email"]', '$username');
            await page.click('#identifierNext');
            
            // Warten auf Passwort-Eingabefeld
            await page.waitForSelector('input[type="password"]', { visible: true, timeout: 10000 });
            await page.type('input[type="password"]', '$password');
            await page.click('#passwordNext');
            
            // Warten auf Abschluss der Anmeldung
            await page.waitForNavigation({ waitUntil: 'networkidle2' });
            console.log('Login abgeschlossen');
            
            // Sicherstellen, dass wir auf YouTube sind
            if (!page.url().includes('youtube.com')) {
                await page.goto('https://www.youtube.com');
            }
        } catch (error) {
            console.error('Login fehlgeschlagen:', error.message);
            // Trotzdem fortfahren...
        }
EOT;
    }
    
    // Puppeteer-Skript für YouTube-Extraktion mit direktem Import und lokaler Installation
    $scriptContent = <<<EOT
// Puppeteer-Skript zur Extraktion von YouTube-Videoinformationen
const fs = require('fs');
const puppeteer = require('puppeteer');

// Konfiguration
const videoId = '$videoId';
const outputFile = '$outputFile';
const debugLogFile = '$debugLogFile';
const cookieFile = '$cookieFile';

// Debug-Log-Funktion
function logDebug(message) {
    const timestamp = new Date().toISOString();
    const logEntry = `\${timestamp} - \${message}`;
    console.log(logEntry);
    
    // In Debug-Datei schreiben
    fs.appendFileSync(debugLogFile, logEntry + "\\n", { encoding: 'utf8' });
}

// Ergebnisfunktion
function saveResult(data) {
    fs.writeFileSync(outputFile, JSON.stringify(data, null, 2), { encoding: 'utf8' });
    logDebug(`Ergebnis in \${outputFile} gespeichert`);
}

// Fehlerbehandlung
process.on('unhandledRejection', (error) => {
    logDebug(`Unbehandelte Ablehnung: \${error.message}`);
    logDebug(error.stack);
    
    saveResult({
        success: false,
        error: `Unbehandelte Ablehnung: \${error.message}`,
        stack: error.stack
    });
    
    process.exit(1);
});

// Hauptfunktion
(async () => {
    let browser = null;
    let page = null;
    
    try {
        logDebug('Starte Browser...');
        
        // Browser mit Konfiguration starten
        browser = await puppeteer.launch({
            headless: "new",
            executablePath: '/snap/bin/chromium',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--window-size=1280,800',
                $proxyArgs
            ]
        });
        
        page = await browser.newPage();
        
        // User-Agent setzen
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36');
        
        // Cookies laden, wenn vorhanden
        if (fs.existsSync(cookieFile)) {
            logDebug(`Lade Cookies aus \${cookieFile}`);
            try {
                const cookiesString = fs.readFileSync(cookieFile, 'utf8');
                const cookies = JSON.parse(cookiesString);
                await page.setCookie(...cookies);
                logDebug('Cookies erfolgreich geladen');
            } catch (error) {
                logDebug(`Fehler beim Laden der Cookies: \${error.message}`);
            }
        }
        
        // Page logging
        page.on('console', msg => logDebug(`PAGE LOG: \${msg.text()}`));
        page.on('error', err => logDebug(`PAGE ERROR: \${err.message}`));
        
        // Login ausführen, wenn Anmeldedaten vorhanden
        $loginCode
        
        // YouTube-Video besuchen
        logDebug(`Navigiere zu YouTube-Video \${videoId}...`);
        await page.goto(`https://www.youtube.com/watch?v=\${videoId}`, {
            waitUntil: 'networkidle2',
            timeout: 60000
        });
        
        // Videotitel extrahieren
        const title = await page.evaluate(() => {
            const titleElement = document.querySelector('h1.title.style-scope.ytd-video-primary-info-renderer');
            return titleElement ? titleElement.textContent.trim() : null;
        });
        
        logDebug(`Extrahierter Titel: \${title}`);
        
        // Videobeschreibung extrahieren
        const description = await page.evaluate(() => {
            const showMoreBtn = document.querySelector('#description-inline-expander tp-yt-paper-button#expand');
            if (showMoreBtn) {
                showMoreBtn.click();
            }
            
            // Warten, bis die Beschreibung erweitert wurde
            return new Promise((resolve) => {
                setTimeout(() => {
                    const descriptionElement = document.querySelector('#description-inline-expander #description');
                    resolve(descriptionElement ? descriptionElement.textContent.trim() : null);
                }, 1000);
            });
        });
        
        logDebug(`Beschreibung extrahiert: \${description ? description.substring(0, 100) + '...' : 'Nicht gefunden'}`);
        
        // Untertitel prüfen - Untertitel-Button anklicken
        await page.evaluate(() => {
            const captionsButton = document.querySelector('button.ytp-subtitles-button');
            if (captionsButton) {
                captionsButton.click();
            }
        });
        
        // Kurz warten und Verfügbarkeit prüfen
        await page.waitForTimeout(1000);
        
        const hasSubtitles = await page.evaluate(() => {
            const captionsPanel = document.querySelector('.ytp-caption-segment');
            const captionsButton = document.querySelector('button.ytp-subtitles-button');
            const isEnabled = captionsButton && captionsButton.getAttribute('aria-pressed') === 'true';
            return !!captionsPanel || isEnabled;
        });
        
        logDebug(`Untertitel verfügbar: \${hasSubtitles}`);
        
        // Speichere die aktuelle Cookies für spätere Verwendung
        const cookies = await page.cookies();
        fs.writeFileSync(cookieFile, JSON.stringify(cookies, null, 2), 'utf8');
        logDebug('Cookies gespeichert');
        
        // Erfolgreicher Abschluss
        saveResult({
            success: true,
            videoId: videoId,
            title: title,
            description: description,
            hasSubtitles: hasSubtitles,
            timestamp: new Date().toISOString()
        });
        
        logDebug('Extraktion erfolgreich abgeschlossen');
    } catch (error) {
        logDebug(`Fehler bei der Ausführung: \${error.message}`);
        logDebug(error.stack);
        
        let errorType = 'Unbekannter Fehler';
        
        if (error.message.includes('net::ERR_PROXY_CONNECTION_FAILED')) {
            errorType = 'Proxy-Verbindungsfehler';
        } else if (error.message.includes('Navigation timeout')) {
            errorType = 'Zeitüberschreitung bei der Navigation';
        } else if (error.message.includes('Target closed')) {
            errorType = 'Browser wurde unerwartet geschlossen';
        }
        
        saveResult({
            success: false,
            videoId: videoId,
            error: errorType,
            errorMessage: error.message,
            timestamp: new Date().toISOString()
        });
    } finally {
        // Aufräumen
        try {
            if (page) await page.close();
            if (browser) await browser.close();
        } catch (error) {
            logDebug(`Fehler beim Schließen des Browsers: \${error.message}`);
        }
        
        logDebug('Prozess beendet');
    }
})();
EOT;

    return $scriptContent;
}

// Shell-Befehl ausführen mit verbessertem Logging
function execCommand($command, $timeout = 300) {
    logMessage("Ausführen: $command", "CMD");
    
    // Setze Timeout für langwierige Prozesse
    set_time_limit($timeout + 30);
    
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];
    
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        // Nicht-blockierendes Lesen einrichten
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        
        $output = "";
        $start_time = time();
        
        // Echtzeit-Ausgabe des Prozesses
        while (true) {
            $status = proc_get_status($process);
            
            // Prozess beendet?
            if (!$status['running']) {
                break;
            }
            
            // Timeout prüfen
            if (time() - $start_time > $timeout) {
                logMessage("Prozess läuft zu lange (> $timeout Sekunden). Beende...", "ERROR");
                proc_terminate($process);
                break;
            }
            
            // Stdout und stderr auslesen
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            
            if ($stdout) {
                $output .= $stdout;
                logMessage("Ausgabe: " . trim($stdout), "STDOUT");
            }
            
            if ($stderr) {
                $output .= $stderr;
                logMessage("Fehler: " . trim($stderr), "STDERR");
            }
            
            // Kurz pausieren, um CPU-Last zu reduzieren
            usleep(100000); // 100ms
        }
        
        // Restliche Ausgabe einlesen
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        
        if ($stdout) {
            $output .= $stdout;
            logMessage("Finale Ausgabe: " . trim($stdout), "STDOUT");
        }
        
        if ($stderr) {
            $output .= $stderr;
            logMessage("Finale Fehler: " . trim($stderr), "STDERR");
        }
        
        // Prozess-Status
        $exitCode = proc_close($process);
        
        logMessage("Prozess beendet mit Exit-Code: $exitCode", $exitCode === 0 ? "SUCCESS" : "ERROR");
        
        return [
            'output' => $output,
            'exitCode' => $exitCode
        ];
    } else {
        logMessage("Konnte Prozess nicht starten: $command", "ERROR");
        return [
            'output' => "Prozessfehler",
            'exitCode' => -1
        ];
    }
}

// YouTube-Video mit Puppeteer verarbeiten
function processYoutubeVideo($youtubeUrl, $username = '', $password = '', $proxyUrl = '') {
    global $tempDir, $outputDir;
    
    // Video-ID extrahieren
    $videoId = extractYoutubeId($youtubeUrl);
    if (!$videoId) {
        logMessage("Ungültige YouTube-URL: $youtubeUrl", 'ERROR');
        return false;
    }
    
    logMessage("Verarbeite YouTube-Video mit ID: $videoId", 'INFO');
    
    // Puppeteer-Skript erstellen
    $scriptContent = createPuppeteerScript($videoId, $username, $password, $proxyUrl);
    $scriptFile = $tempDir . '/puppeteer_script_' . uniqid() . '.js';
    file_put_contents($scriptFile, $scriptContent);
    
    // Node.js-Prozess starten
    $nodeExecutable = '/usr/bin/node'; // Vollständiger Pfad zu Node.js
    $command = escapeshellcmd("$nodeExecutable $scriptFile");
    
    logMessage("Führe Node.js-Skript aus: $command", 'INFO');
    
    // Prozess ausführen mit Ausgabeerfassung
    $output = [];
    $exitCode = -1;
    
    exec($command . ' 2>&1', $output, $exitCode);
    
    // Temporäre Datei löschen
    if (file_exists($scriptFile)) {
        unlink($scriptFile);
    }
    
    // Ausgabe überprüfen
    if ($exitCode !== 0) {
        logMessage("Prozess beendet mit Exit-Code: $exitCode", 'ERROR');
        
        // Versuche nach bestimmten Mustern im Fehler zu suchen
        $errorOutput = implode("\n", $output);
        
        if (strpos($errorOutput, 'Error: Failed to launch the browser') !== false) {
            logMessage("Konnte Browser nicht starten. Ist Chromium installiert?", 'ERROR');
            logMessage("Installieren Sie Chromium mit: apt-get install chromium-browser", 'INFO');
        } elseif (strpos($errorOutput, 'Cannot find module') !== false) {
            logMessage("Node.js-Module fehlen. Führen Sie 'npm install puppeteer' aus.", 'ERROR');
        } elseif (empty($output)) {
            logMessage("Node.js ist nicht installiert oder nicht verfügbar", 'ERROR');
            logMessage("Installieren Sie Node.js mit: apt-get install nodejs npm", 'INFO');
        } else {
            logMessage("Fehler beim Ausführen des Node.js-Skripts:", 'ERROR');
            foreach ($output as $line) {
                logMessage($line, 'ERROR');
            }
        }
        
        return false;
    }
    
    // Globale Variable für die Output-Datei verwenden
    global $outputFilePath;
    
    // JSON-Ergebnis aus der Datei lesen
    logMessage("Lese Ergebnisdatei: " . $outputFilePath, "INFO");
    if (!file_exists($outputFilePath)) {
        logMessage("Ergebnisdatei wurde nicht erstellt!", "ERROR");
        return [
            'success' => false,
            'error' => 'Keine Ergebnisdatei gefunden',
            'rawOutput' => implode("\n", $output)
        ];
    }
    
    $jsonOutput = file_get_contents($outputFilePath);
    $data = json_decode($jsonOutput, true);
    
    // Temporäre Datei löschen
    unlink($outputFilePath);
    
    if (!$data) {
        logMessage("Konnte JSON-Daten nicht parsen", "ERROR");
        logMessage("Raw-Output: " . substr($jsonOutput, 0, 500), "DEBUG");
        return [
            'success' => false,
            'error' => 'Ungültiges JSON-Format',
            'rawOutput' => $jsonOutput
        ];
    }
    
    // Screenshot finden und anzeigen, falls vorhanden
    $screenshots = glob($tempDir . '/youtube_*_*.png');
    if (!empty($screenshots)) {
        $latestScreenshot = end($screenshots);
        logMessage("Screenshot erstellt: " . basename($latestScreenshot), "INFO");
        $data['screenshot'] = basename($latestScreenshot);
    }
    
    // HTML-Inhalt prüfen
    $htmlFiles = glob($tempDir . '/page_content_*.html');
    if (!empty($htmlFiles)) {
        $latestHtml = end($htmlFiles);
        $htmlSize = filesize($latestHtml);
        logMessage("HTML-Inhalt gespeichert: " . basename($latestHtml) . " ($htmlSize Bytes)", "INFO");
        
        // Prüfen auf bekannte Fehlermeldungen im HTML
        $htmlContent = file_get_contents($latestHtml);
        if (strpos($htmlContent, "captcha") !== false || 
            strpos($htmlContent, "robot") !== false ||
            strpos($htmlContent, "unusual traffic") !== false) {
            logMessage("Bot-Erkennung im HTML-Inhalt gefunden!", "WARNING");
            $data['botDetection'] = true;
        }
    }
    
    // Speichern der Ergebnisse
    if ($data['success']) {
        logMessage("Analyse erfolgreich abgeschlossen", "SUCCESS");
        
        // Speichere Text in Datei
        $outputFile = $outputDir . "/youtube_transcript_$videoId.txt";
        $outputContent = "Titel: " . $data['title'] . "\n\n";
        $outputContent .= "Beschreibung:\n" . $data['description'] . "\n\n";
        $outputContent .= "Untertitel:\n" . $data['captionText'] . "\n";
        
        file_put_contents($outputFile, $outputContent);
        logMessage("Ergebnis gespeichert in: $outputFile", "INFO");
        
        // Speichere JSON mit Metadaten
        $jsonFile = $outputDir . "/youtube_data_$videoId.json";
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        logMessage("Metadaten gespeichert in: $jsonFile", "INFO");
    } else {
        logMessage("Analyse fehlgeschlagen: " . ($data['error'] ?? 'Unbekannter Fehler'), "ERROR");
    }
    
    return $data;
}

// HTML-Formular und Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formular wurde abgeschickt
    $youtubeUrl = $_POST['youtube_url'] ?? '';
    $username = $_POST['google_username'] ?? '';
    $password = $_POST['google_password'] ?? '';
    $proxyUrl = $_POST['proxy_url'] ?? '';
    
    if (!empty($youtubeUrl)) {
        echo "<div class='processing-status'>";
        echo "<h3>Verarbeite Video...</h3>";
        echo "<div id='status-updates'></div>";
        
        // Sofortiges Flushen der Ausgabe
        echo str_pad('', 4096) . "\n";
        ob_flush();
        flush();
        
        // Verarbeite das Video mit Proxy
        $result = processYoutubeVideo($youtubeUrl, $username, $password, $proxyUrl);
        
        // Zeige Ergebnisse an
        echo "<div class='results'>";
        if ($result['success']) {
            echo "<h3>Videoanalyse erfolgreich</h3>";
            
            // Screenshot anzeigen, falls vorhanden
            if (isset($result['screenshot'])) {
                echo "<div class='screenshot'>";
                echo "<h4>Screenshot des Videos:</h4>";
                echo "<img src='temp/" . htmlspecialchars($result['screenshot']) . "' alt='Video Screenshot' style='max-width:100%;'>";
                echo "</div>";
            }
            
            echo "<div class='video-info'>";
            echo "<h4>Titel:</h4>";
            echo "<p>" . htmlspecialchars($result['title']) . "</p>";
            
            echo "<h4>Beschreibung:</h4>";
            echo "<pre>" . htmlspecialchars($result['description']) . "</pre>";
            
            echo "<h4>Untertitel/Transkript:</h4>";
            if (!empty($result['captionText'])) {
                echo "<pre>" . htmlspecialchars($result['captionText']) . "</pre>";
            } else {
                echo "<p>Keine Untertitel gefunden</p>";
            }
            echo "</div>";
            
            // Download-Links
            $videoId = extractYoutubeId($youtubeUrl);
            if ($videoId) {
                echo "<div class='download-links'>";
                echo "<h4>Downloads:</h4>";
                echo "<p><a href='output/youtube_transcript_$videoId.txt' download>Transkript als Text-Datei</a></p>";
                echo "<p><a href='output/youtube_data_$videoId.json' download>Metadaten als JSON-Datei</a></p>";
                echo "</div>";
            }
        } else {
            echo "<h3>Fehler bei der Videoanalyse</h3>";
            echo "<p>Fehler: " . htmlspecialchars($result['error'] ?? 'Unbekannter Fehler') . "</p>";
            
            if ($DEBUG && isset($result['rawOutput'])) {
                echo "<h4>Detaillierte Fehlermeldung:</h4>";
                echo "<pre>" . htmlspecialchars(substr($result['rawOutput'], 0, 2000)) . "</pre>";
            }
            
            // Screenshot anzeigen, falls vorhanden
            if (isset($result['screenshot'])) {
                echo "<div class='screenshot'>";
                echo "<h4>Screenshot (kann Hinweise auf Fehler geben):</h4>";
                echo "<img src='temp/" . htmlspecialchars($result['screenshot']) . "' alt='Error Screenshot' style='max-width:100%;'>";
                echo "</div>";
            }
        }
        echo "</div>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Headless Browser Extraktor</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 1000px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #333; }
        form { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4285f4; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #3367d6; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; max-height: 300px; }
        .optional-fields { margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        .processing-status { background: #e8f4fd; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .results { background: #f9f9f9; padding: 20px; border-radius: 5px; }
        .download-links { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; }
        .video-info { margin: 15px 0; }
        .screenshot { margin: 15px 0; }
        .proxy-section { margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <h1>YouTube Headless Browser Extraktor</h1>
    <p>Dieses Tool extrahiert Informationen von YouTube-Videos mit Hilfe eines Headless Browsers.</p>
    
    <?php if (!isset($_POST['youtube_url'])): ?>
    <form method="post" action="">
        <div>
            <label for="youtube_url">YouTube-URL:</label>
            <input type="text" name="youtube_url" id="youtube_url" required placeholder="https://www.youtube.com/watch?v=...">
        </div>
        
        <div class="proxy-section">
            <h3>Proxy-Einstellungen</h3>
            <p>Falls Ihre IP blockiert wird, können Sie einen Proxy-Server verwenden.</p>
            <div>
                <label for="proxy_url">Proxy-URL (optional):</label>
                <input type="text" name="proxy_url" id="proxy_url" placeholder="http://username:password@proxy.example.com:8080">
                <p class="hint">Format: http://username:password@host:port oder http://host:port</p>
            </div>
        </div>
        
        <div class="optional-fields">
            <h3>Optionale Google-Anmeldung</h3>
            <p>Die Anmeldung ermöglicht Zugriff auf altersbeschränkte Videos und mehr Inhalte.</p>
            <div>
                <label for="google_username">Google E-Mail (optional):</label>
                <input type="text" name="google_username" id="google_username" placeholder="example@gmail.com">
            </div>
            <div>
                <label for="google_password">Google Passwort (optional):</label>
                <input type="password" name="google_password" id="google_password" placeholder="Ihr Google-Passwort">
            </div>
        </div>
        
        <button type="submit">Video analysieren</button>
    </form>
    
    <div>
        <h3>Hinweise:</h3>
        <ul>
            <li>Der Prozess kann bis zu 2 Minuten dauern.</li>
            <li>Es wird ein Headless-Browser (Puppeteer) verwendet, um YouTube-Inhalte zu analysieren.</li>
            <li>Die Ergebnisse werden im Ordner "output" gespeichert.</li>
            <li>Falls YouTube Ihre Anfragen blockiert, versuchen Sie einen Proxy zu verwenden.</li>
            <li>Kostenlose Proxy-Listen finden Sie bei <a href="https://free-proxy-list.net/" target="_blank">free-proxy-list.net</a> oder <a href="https://www.proxynova.com/" target="_blank">ProxyNova</a>.</li>
        </ul>
    </div>
    <?php endif; ?>
</body>
</html> 