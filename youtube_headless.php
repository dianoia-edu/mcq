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
    global $tempDir;
    
    // Output-Pfad definieren
    $outputFile = $tempDir . '/youtube_data_' . $videoId . '.json';
    
    // Proxy-Konfiguration
    $proxyConfig = '';
    if (!empty($proxyUrl)) {
        $proxyConfig = "args: ['--proxy-server=$proxyUrl'],";
    }
    
    // Skript mit korrektem Pfad zum Puppeteer-Modul
    $script = <<<EOT
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Setze den Cache-Pfad für Puppeteer
process.env.PUPPETEER_CACHE_DIR = '$tempDir/puppeteer_cache';

(async () => {
    try {
        console.log('Starte Browser...');
        const browser = await puppeteer.launch({
            headless: true,
            executablePath: '/home/mcqadmin/.cache/puppeteer/chrome/linux-134.0.6998.35/chrome-linux64/chrome',
            $proxyConfig
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--disable-extensions',
                '--disable-software-rasterizer',
                '--disable-features=IsolateOrigins,site-per-process',
                '--disable-site-isolation-trials',
                '--disable-web-security',
                '--allow-running-insecure-content',
                '--window-size=1920,1080',
                '--remote-debugging-port=9222',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-breakpad',
                '--disable-component-extensions-with-background-pages',
                '--disable-features=TranslateUI,BlinkGenPropertyTrees',
                '--disable-ipc-flooding-protection',
                '--enable-features=NetworkService,NetworkServiceInProcess',
                '--disable-features=IsolateOrigins,site-per-process',
                '--disable-site-isolation-trials',
                '--disable-web-security',
                '--allow-running-insecure-content'
            ],
            ignoreHTTPSErrors: true,
            timeout: 60000,
            pipe: true
        });
        
        console.log('Öffne neue Seite...');
        const page = await browser.newPage();
        
        // Setze User-Agent
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        
        // Setze Viewport
        await page.setViewport({ width: 1920, height: 1080 });
        
        // Setze zusätzliche Timeouts
        await page.setDefaultNavigationTimeout(60000);
        await page.setDefaultTimeout(60000);
        
        // Navigiere zu YouTube mit verbesserter Fehlerbehandlung
        console.log('Navigiere zu YouTube...');
        try {
            // Warte auf die Hauptseite mit mehreren Versuchen
            let retryCount = 0;
            const maxRetries = 3;
            
            while (retryCount < maxRetries) {
                try {
                    await page.goto('https://www.youtube.com', {
                        waitUntil: ['networkidle0', 'domcontentloaded'],
                        timeout: 60000
                    });
                    break;
                } catch (error) {
                    retryCount++;
                    console.log(`Versuch ${retryCount} fehlgeschlagen:`, error.message);
                    if (retryCount === maxRetries) throw error;
                    await page.waitForTimeout(2000);
                }
            }
            
            // Kurze Pause
            await page.waitForTimeout(2000);
            
            // Navigiere zum Video mit mehreren Versuchen
            retryCount = 0;
            while (retryCount < maxRetries) {
                try {
                    await page.goto('https://www.youtube.com/watch?v=$videoId', {
                        waitUntil: ['networkidle0', 'domcontentloaded'],
                        timeout: 60000
                    });
                    break;
                } catch (error) {
                    retryCount++;
                    console.log(`Versuch ${retryCount} fehlgeschlagen:`, error.message);
                    if (retryCount === maxRetries) throw error;
                    await page.waitForTimeout(2000);
                }
            }
            
            // Warte auf die Hauptelemente mit mehreren Versuchen
            retryCount = 0;
            while (retryCount < maxRetries) {
                try {
                    await Promise.race([
                        page.waitForSelector('h1.ytd-video-primary-info-renderer', { timeout: 30000 }),
                        page.waitForSelector('#player-container', { timeout: 30000 })
                    ]);
                    break;
                } catch (error) {
                    retryCount++;
                    console.log(`Versuch ${retryCount} fehlgeschlagen:`, error.message);
                    if (retryCount === maxRetries) throw error;
                    await page.waitForTimeout(2000);
                }
            }
            
            // Kurze Pause für stabilere Ausführung
            await page.waitForTimeout(2000);
            
        } catch (error) {
            console.error('Fehler bei der Navigation:', error);
            throw error;
        }
        
        // Warte auf Cookie-Banner und klicke "Alle Cookies akzeptieren"
        try {
            const acceptButton = await page.waitForSelector('button[aria-label="Alle Cookies akzeptieren"]', { timeout: 5000 });
            if (acceptButton) {
                console.log('Akzeptiere Cookies...');
                await acceptButton.click();
                await page.waitForTimeout(2000);
            }
        } catch (e) {
            console.log('Kein Cookie-Banner gefunden oder bereits akzeptiert');
        }
        
        // Warte auf Video-Titel
        console.log('Warte auf Video-Titel...');
        await page.waitForSelector('h1.ytd-video-primary-info-renderer', { timeout: 10000 });
        
        // Extrahiere Video-Titel
        const title = await page.evaluate(() => {
            const titleElement = document.querySelector('h1.ytd-video-primary-info-renderer');
            return titleElement ? titleElement.textContent.trim() : '';
        });
        
        // Extrahiere Beschreibung
        console.log('Extrahiere Beschreibung...');
        const description = await page.evaluate(() => {
            const descriptionElement = document.querySelector('#description-text');
            return descriptionElement ? descriptionElement.textContent.trim() : '';
        });
        
        // Speichere HTML-Inhalt für Debugging
        const htmlContent = await page.content();
        const timestamp = Date.now();
        fs.writeFileSync(path.join('$tempDir', `page_content_\${timestamp}.html`), htmlContent);
        
        // Prüfe auf Untertitel
        console.log('Prüfe auf Untertitel...');
        let captionText = '';
        try {
            // Klicke auf "Mehr anzeigen" in der Beschreibung
            const moreButton = await page.waitForSelector('#description #expand', { timeout: 5000 });
            if (moreButton) {
                await moreButton.click();
                await page.waitForTimeout(1000);
            }
            
            // Prüfe auf CC-Button
            const ccButton = await page.waitForSelector('.ytp-subtitles-button', { timeout: 5000 });
            if (ccButton) {
                const isCCEnabled = await page.evaluate(() => {
                    const button = document.querySelector('.ytp-subtitles-button');
                    return button.getAttribute('aria-pressed') === 'true';
                });
                
                if (!isCCEnabled) {
                    await ccButton.click();
                    await page.waitForTimeout(1000);
                }
                
                // Extrahiere Untertitel
                captionText = await page.evaluate(() => {
                    const captions = document.querySelectorAll('.ytp-caption-segment');
                    return Array.from(captions).map(caption => caption.textContent.trim()).join(' ');
                });
            }
        } catch (e) {
            console.log('Keine Untertitel gefunden oder Fehler beim Extrahieren:', e.message);
        }
        
        // Speichere Screenshot
        console.log('Erstelle Screenshot...');
        await page.screenshot({
            path: path.join('$tempDir', `youtube_\${timestamp}.png`),
            fullPage: true
        });
        
        // Speichere Ergebnis
        const result = {
            success: true,
            title: title,
            description: description,
            captionText: captionText,
            videoId: '$videoId',
            timestamp: new Date().toISOString()
        };
        
        fs.writeFileSync('$outputFile', JSON.stringify(result, null, 2));
        console.log('Ergebnis gespeichert');
        
        await browser.close();
        console.log('Browser geschlossen');
        
    } catch (error) {
        console.error('Fehler:', error);
        const errorResult = {
            success: false,
            error: error.message,
            videoId: '$videoId',
            timestamp: new Date().toISOString()
        };
        fs.writeFileSync('$outputFile', JSON.stringify(errorResult, null, 2));
        process.exit(1);
    }
})();
EOT;
    
    return $script;
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
        return [
            'success' => false,
            'error' => 'Ungültige YouTube-URL',
            'videoId' => null
        ];
    }
    
    logMessage("Verarbeite YouTube-Video mit ID: $videoId", 'INFO');
    
    // Output-Datei definieren
    $outputFile = $tempDir . '/youtube_data_' . $videoId . '.json';
    
    // Puppeteer-Skript erstellen
    $scriptContent = createPuppeteerScript($videoId, $username, $password, $proxyUrl);
    $scriptFile = $tempDir . '/puppeteer_script_' . uniqid() . '.js';
    
    if (!file_put_contents($scriptFile, $scriptContent)) {
        logMessage("Konnte Skriptdatei nicht erstellen", 'ERROR');
        return [
            'success' => false,
            'error' => 'Konnte Skriptdatei nicht erstellen',
            'videoId' => $videoId
        ];
    }
    
    // Node.js-Prozess starten
    $nodeExecutable = '/usr/bin/node';
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
        
        $errorMessage = 'Unbekannter Fehler';
        if (strpos($errorOutput, 'Error: Failed to launch the browser') !== false) {
            $errorMessage = 'Konnte Browser nicht starten. Ist Chromium installiert?';
            logMessage($errorMessage, 'ERROR');
            logMessage("Installieren Sie Chromium mit: apt-get install chromium-browser", 'INFO');
        } elseif (strpos($errorOutput, 'Cannot find module') !== false) {
            $errorMessage = 'Node.js-Module fehlen. Führen Sie "npm install puppeteer" aus.';
            logMessage($errorMessage, 'ERROR');
        } elseif (empty($output)) {
            $errorMessage = 'Node.js ist nicht installiert oder nicht verfügbar';
            logMessage($errorMessage, 'ERROR');
            logMessage("Installieren Sie Node.js mit: apt-get install nodejs npm", 'INFO');
        }
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'videoId' => $videoId,
            'rawOutput' => $errorOutput
        ];
    }
    
    // JSON-Ergebnis aus der Datei lesen
    logMessage("Lese Ergebnisdatei: " . $outputFile, "INFO");
    if (!file_exists($outputFile)) {
        logMessage("Ergebnisdatei wurde nicht erstellt!", "ERROR");
        return [
            'success' => false,
            'error' => 'Keine Ergebnisdatei gefunden',
            'videoId' => $videoId,
            'rawOutput' => implode("\n", $output)
        ];
    }
    
    $jsonOutput = file_get_contents($outputFile);
    $data = json_decode($jsonOutput, true);
    
    // Temporäre Datei löschen
    unlink($outputFile);
    
    if (!$data) {
        logMessage("Konnte JSON-Daten nicht parsen", "ERROR");
        logMessage("Raw-Output: " . substr($jsonOutput, 0, 500), "DEBUG");
        return [
            'success' => false,
            'error' => 'Ungültiges JSON-Format',
            'videoId' => $videoId,
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
        if (isset($data['captionText'])) {
            $outputContent .= "Untertitel:\n" . $data['captionText'] . "\n";
        }
        
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