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

// Verzeichnisse festlegen
$outputDir = __DIR__ . '/output';
$logsDir = __DIR__ . '/logs';
$tempDir = __DIR__ . '/temp';
$cookieDir = __DIR__ . '/cookies';

// Verzeichnisse erstellen, falls sie nicht existieren
foreach ([$outputDir, $logsDir, $tempDir, $cookieDir] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Debugging-Info ausgeben
echo "<h2>YouTube Headless Browser Tool - Debug Mode</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Zeitlimit: " . ini_get('max_execution_time') . " Sekunden\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Arbeitsverzeichnis: " . __DIR__ . "\n";
echo "Hostname: " . gethostname() . "\n";
echo "Betriebssystem: " . PHP_OS . "\n";
echo "</pre>";

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

// Logging-Funktion verbessern
function logMessage($message, $level = 'INFO') {
    global $logsDir;
    $logFile = $logsDir . '/headless_' . date('Y-m-d') . '.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp [$level] $message";
    echo $logEntry . "<br>\n";
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
    
    // Sofort ausgeben für Live-Debugging
    ob_flush();
    flush();
    
    return $logEntry;
}

// Hilfsfunktion: Video-ID aus YouTube-URL extrahieren
function extractYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// Node.js-Script erstellen mit vereinfachten Optionen
function createPuppeteerScript($videoId, $username = '', $password = '') {
    global $cookieDir, $tempDir;
    
    $cookieFile = $cookieDir . '/youtube_cookies.json';
    $debugLogFile = $tempDir . '/puppeteer_debug_' . uniqid() . '.log';
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

// Debug-Logging-Funktion
function debug(message) {
    const timestamp = new Date().toISOString();
    const logMessage = `[\${timestamp}] \${message}`;
    console.log(logMessage);
    fs.appendFileSync('$debugLogFile', logMessage + '\\n');
}

// Prozess-Fehlerbehandlung
process.on('uncaughtException', (error) => {
    debug('CRITICAL ERROR: ' + error.message);
    debug(error.stack);
});

(async () => {
    debug('Starte Headless-Browser...');
    
    let browser;
    try {
        // Vereinfachte Browser-Einstellungen
        browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage'
            ],
            defaultViewport: { width: 1280, height: 720 }
        });
        
        debug('Browser erfolgreich gestartet');
    } catch (error) {
        debug('Browser-Start fehlgeschlagen: ' + error.message);
        fs.writeFileSync('$outputFile', JSON.stringify({
            videoId: '$videoId',
            success: false,
            error: 'Browser konnte nicht gestartet werden: ' + error.message
        }));
        process.exit(1);
    }
    
    let result = {
        videoId: '$videoId',
        success: false,
        error: null,
        title: null,
        description: null,
        hasSubtitles: false,
        captionText: ''
    };
    
    let page;
    
    try {
        debug('Erstelle neue Seite...');
        page = await browser.newPage();
        
        // Debug-Ereignisse
        page.on('console', msg => debug('CONSOLE: ' + msg.text()));
        page.on('pageerror', error => debug('PAGE ERROR: ' + error.message));
        page.on('error', error => debug('ERROR: ' + error.message));
        page.on('requestfailed', request => {
            debug(`REQUEST FAILED: \${request.url()} - \${request.failure().errorText}`);
        });
        
        // Browser-Fingerprint anpassen
        debug('Setze User-Agent und Viewport...');
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        
        // Cookies laden, falls vorhanden
        const cookieFile = '$cookieFile';
        if (fs.existsSync(cookieFile)) {
            debug('Lade bestehende Cookies...');
            try {
                const cookiesString = fs.readFileSync(cookieFile, 'utf8');
                const cookies = JSON.parse(cookiesString);
                await page.setCookie(...cookies);
                debug('Cookies erfolgreich geladen');
            } catch (err) {
                debug('Fehler beim Laden der Cookies: ' + err.message);
            }
        } else {
            debug('Keine Cookie-Datei gefunden: ' + cookieFile);
        }
        
        $loginCode
        
        // YouTube-Video besuchen
        debug('Besuche YouTube-Video...');
        try {
            await page.goto('https://www.youtube.com/watch?v=$videoId', { 
                waitUntil: 'networkidle2',
                timeout: 30000
            });
            debug('Seite erfolgreich geladen');
        } catch (error) {
            debug('Fehler beim Laden der Seite: ' + error.message);
            // Versuche es noch einmal mit einfacheren Optionen
            debug('Versuche erneut mit einfacheren Optionen...');
            await page.goto('https://www.youtube.com/watch?v=$videoId', { 
                waitUntil: 'domcontentloaded',
                timeout: 60000
            });
        }
        
        // Mache einen Screenshot für Debugging
        debug('Erstelle Screenshot...');
        await page.screenshot({ path: '$tempDir/youtube_debug_' + Date.now() + '.png' });
        
        // HTML-Inhalt protokollieren
        debug('Protokolliere HTML-Inhalt...');
        const htmlContent = await page.content();
        debug('HTML-Inhalt-Länge: ' + htmlContent.length);
        fs.writeFileSync('$tempDir/page_content_' + Date.now() + '.html', htmlContent);
        
        // Überprüfen, ob wir auf das Video zugreifen können
        debug('Prüfe auf Blockierung...');
        const isBlocked = await page.evaluate(() => {
            return document.body.innerText.includes("confirm you're not a robot");
        });
        
        if (isBlocked) {
            debug('Blockierung erkannt. Bot-Erkennung aktiv.');
            result.error = 'YouTube blockiert den Zugriff. Manuelle Anmeldung erforderlich.';
            throw new Error(result.error);
        }
        
        // Cookies speichern
        debug('Speichere Cookies...');
        const cookies = await page.cookies();
        fs.writeFileSync(cookieFile, JSON.stringify(cookies, null, 2));
        
        // Videotitel extrahieren
        debug('Extrahiere Videotitel...');
        try {
            await page.waitForSelector('h1.ytd-watch-metadata', { timeout: 10000 });
            debug('Titelselektorgefunden');
        } catch (error) {
            debug('Timeout beim Warten auf den Titel: ' + error.message);
            debug('Versuche alternative Selektoren...');
        }
            
        const videoTitle = await page.evaluate(() => {
            const selectors = [
                'h1.ytd-watch-metadata',
                'h1.title',
                '#container h1',
                '.title'
            ];
            
            for (const selector of selectors) {
                const element = document.querySelector(selector);
                if (element) {
                    console.log('Titel gefunden mit Selektor: ' + selector);
                    return element.innerText.trim();
                }
            }
            
            // Fallback auf meta tags
            const metaTitle = document.querySelector('meta[property="og:title"]');
            if (metaTitle) {
                return metaTitle.getAttribute('content');
            }
            
            return 'Titel nicht gefunden';
        });
        
        debug('Videotitel: ' + videoTitle);
        result.title = videoTitle;
        
        // Videobeschreibung extrahieren
        debug('Extrahiere Videobeschreibung...');
        const videoDescription = await page.evaluate(() => {
            // Beschreibung aufklappen, falls möglich
            try {
                const expandButtons = document.querySelectorAll('#expand, .more-button, .expand');
                for (const button of expandButtons) {
                    button.click();
                    console.log('Expand-Button geklickt');
                }
            } catch (e) {
                console.error('Fehler beim Klicken des Expand-Buttons:', e);
            }
            
            // Kurze Pause und dann versuchen, die Beschreibung zu finden
            return new Promise(resolve => {
                setTimeout(() => {
                    const selectors = [
                        '#description-inline-expander',
                        '#description-inner',
                        '#description',
                        '.description',
                        '[itemprop="description"]'
                    ];
                    
                    for (const selector of selectors) {
                        const element = document.querySelector(selector);
                        if (element && element.innerText.trim()) {
                            console.log('Beschreibung gefunden mit Selektor: ' + selector);
                            return resolve(element.innerText.trim());
                        }
                    }
                    
                    // Fallback auf meta
                    const metaDesc = document.querySelector('meta[property="og:description"]');
                    if (metaDesc) {
                        return resolve(metaDesc.getAttribute('content'));
                    }
                    
                    resolve('Keine Beschreibung gefunden');
                }, 3000);
            });
        });
        
        debug('Beschreibung erhalten, Länge: ' + videoDescription.length);
        result.description = videoDescription;
        
        // Untertitel-Button finden und prüfen
        debug('Suche nach Untertitel-Option...');
        let hasCaptions = false;
        
        try {
            await page.waitForSelector('.ytp-subtitles-button', { timeout: 5000 });
            debug('Untertitel-Button gefunden');
            
            // Überprüfen, ob Untertitel verfügbar sind
            hasCaptions = await page.evaluate(() => {
                const captionButton = document.querySelector('.ytp-subtitles-button');
                if (!captionButton) return false;
                
                const disabled = captionButton.classList.contains('ytp-button-disabled');
                console.log('Untertitel-Button Status: ' + (disabled ? 'deaktiviert' : 'aktiviert'));
                return !disabled;
            });
        } catch (error) {
            debug('Kein Untertitel-Button gefunden: ' + error.message);
        }
        
        debug('Untertitel verfügbar: ' + (hasCaptions ? 'Ja' : 'Nein'));
        result.hasSubtitles = hasCaptions;
        
        if (hasCaptions) {
            debug('Untertitel sind verfügbar. Versuche Aktivierung...');
            
            try {
                // Klicke auf den Untertitel-Button, falls Untertitel nicht bereits aktiviert sind
                const isSubtitleActive = await page.evaluate(() => {
                    const captionButton = document.querySelector('.ytp-subtitles-button');
                    const isActive = captionButton ? captionButton.getAttribute('aria-pressed') === 'true' : false;
                    console.log('Untertitel aktiv: ' + isActive);
                    return isActive;
                });
                
                if (!isSubtitleActive) {
                    debug('Aktiviere Untertitel durch Klick...');
                    await page.click('.ytp-subtitles-button');
                    await page.waitForTimeout(1000);
                    debug('Untertitel sollten jetzt aktiviert sein');
                } else {
                    debug('Untertitel sind bereits aktiviert');
                }
                
                // Starte Video und sammle Untertitel
                debug('Starte Video und sammle Untertitel...');
                
                // Maximiere den Untertitel-Erfassungsbereich
                await page.evaluate(() => {
                    const video = document.querySelector('video');
                    if (video) {
                        video.muted = true;
                        video.play();
                        console.log('Video gestartet (stumm)');
                    } else {
                        console.error('Kein Video-Element gefunden');
                    }
                });
                
                // Sammle 45 Sekunden lang Untertitel
                const captionTexts = new Set();
                
                for (let i = 0; i < 30; i++) {
                    debug('Sammle Untertitel (Durchlauf ' + (i+1) + '/30)...');
                    
                    const newCaptions = await page.evaluate(() => {
                        const captionElements = document.querySelectorAll('.ytp-caption-segment, .captions-text');
                        const captions = Array.from(captionElements).map(el => el.innerText.trim()).filter(text => text.length > 0);
                        console.log('Gefundene Untertitel in diesem Durchlauf: ' + captions.length);
                        return captions;
                    });
                    
                    if (newCaptions.length > 0) {
                        newCaptions.forEach(text => captionTexts.add(text));
                        debug('Gefundene Untertitel bisher: ' + captionTexts.size);
                    }
                    
                    // Alle 1,5 Sekunden prüfen
                    await page.waitForTimeout(1500);
                    
                    // Spule im Video vor, wenn nach einigen Versuchen nichts gefunden wurde
                    if (i % 5 === 4 && captionTexts.size < 5) {
                        debug('Nicht genug Untertitel gefunden, spule vor...');
                        await page.evaluate(() => {
                            const video = document.querySelector('video');
                            if (video) {
                                const newTime = video.currentTime + 30;
                                console.log('Spule vor von ' + video.currentTime + ' auf ' + newTime);
                                video.currentTime = newTime;
                            }
                        });
                    }
                    
                    // Brich ab, wenn genug Untertitel gesammelt wurden
                    if (captionTexts.size > 30) {
                        debug('Genug Untertitel gesammelt, breche Sammlung ab');
                        break;
                    }
                }
                
                // Stoppe Video
                await page.evaluate(() => {
                    const video = document.querySelector('video');
                    if (video) {
                        video.pause();
                        console.log('Video gestoppt');
                    }
                });
                
                // Speichere gesammelte Untertitel
                const captionArray = Array.from(captionTexts);
                result.captionText = captionArray.join(' ');
                debug('Insgesamt ' + captionArray.length + ' Untertitel-Segmente gesammelt.');
            } catch (error) {
                debug('Fehler bei der Untertitel-Extraktion: ' + error.message);
            }
        } else {
            debug('Keine Untertitel für dieses Video verfügbar.');
        }
        
        result.success = true;
        
    } catch (error) {
        debug('Allgemeiner Fehler: ' + error.message);
        debug(error.stack);
        result.error = error.message;
    } finally {
        // Speichere Ergebnis in die Datei
        debug('Speichere Ergebnisse in ' + '$outputFile');
        fs.writeFileSync('$outputFile', JSON.stringify(result, null, 2));
        
        if (page) {
            debug('Erstelle finalen Screenshot...');
            try {
                await page.screenshot({ path: '$tempDir/youtube_final_' + Date.now() + '.png' });
            } catch (e) {
                debug('Fehler beim finalen Screenshot: ' + e.message);
            }
        }
        
        if (browser) {
            debug('Schließe Browser...');
            await browser.close();
            debug('Browser geschlossen');
        }
        
        // Gib Ergebnis zurück
        console.log(JSON.stringify(result));
        debug('Script beendet');
    }
})();
EOT;

    return [
        'script' => $script,
        'outputFile' => $outputFile,
        'debugLogFile' => $debugLogFile
    ];
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

// Hauptfunktion zur Verarbeitung eines YouTube-Videos
function processYoutubeVideo($url, $username = '', $password = '') {
    global $outputDir, $tempDir, $DEBUG;
    
    // Videoanalyse starten
    logMessage("Starte Analyse für URL: $url", "INFO");
    
    // Video-ID extrahieren
    $videoId = extractYoutubeId($url);
    if (!$videoId) {
        logMessage("Keine gültige YouTube-ID gefunden in: $url", "ERROR");
        return [
            'success' => false,
            'error' => 'Keine gültige YouTube-URL'
        ];
    }
    
    logMessage("Extrahierte YouTube-ID: $videoId", "INFO");
    
    // Systemprüfungen
    logMessage("Überprüfe Node.js-Installation...", "INFO");
    $nodeCheck = execCommand('node --version');
    if ($nodeCheck['exitCode'] !== 0) {
        logMessage("Node.js ist nicht installiert oder nicht verfügbar", "ERROR");
        return [
            'success' => false,
            'error' => 'Node.js ist nicht verfügbar'
        ];
    }
    
    logMessage("Überprüfe Puppeteer-Installation...", "INFO");
    $puppeteerCheck = execCommand('npm list -g puppeteer');
    if (strpos($puppeteerCheck['output'], 'puppeteer@') === false) {
        logMessage("Puppeteer ist möglicherweise nicht installiert", "WARNING");
        logMessage("Versuche trotzdem fortzufahren...", "INFO");
    }
    
    // Puppeteer-Script erstellen
    logMessage("Erstelle Puppeteer-Script...", "INFO");
    $scriptData = createPuppeteerScript($videoId, $username, $password);
    $scriptFile = $tempDir . '/puppeteer_script_' . uniqid() . '.js';
    file_put_contents($scriptFile, $scriptData['script']);
    logMessage("Script gespeichert unter: $scriptFile", "DEBUG");
    
    // Script ausführen
    logMessage("Starte Puppeteer-Browser (kann einige Minuten dauern)...", "INFO");
    
    // Ausführungszeit erhöhen für komplexe Seiten
    set_time_limit(600);
    
    $debugOutputFile = $tempDir . '/node_debug_' . uniqid() . '.log';
    $cmd = "node $scriptFile 2>&1";
    if ($DEBUG) {
        logMessage("Ausführungsbefehl: $cmd", "DEBUG");
    }
    
    $startTime = microtime(true);
    $result = execCommand($cmd, 300); // 5-Minuten-Timeout
    $executionTime = round(microtime(true) - $startTime, 2);
    
    logMessage("Puppeteer-Ausführung abgeschlossen in $executionTime Sekunden", "INFO");
    
    // Ausgabe auswerten
    if ($result['exitCode'] !== 0) {
        logMessage("Puppeteer-Ausführung fehlgeschlagen mit Exit-Code: " . $result['exitCode'], "ERROR");
        
        // Mehr Kontext für Fehler bereitstellen
        logMessage("Versuche Debug-Log zu lesen...", "INFO");
        if (file_exists($scriptData['debugLogFile'])) {
            $debugLog = file_get_contents($scriptData['debugLogFile']);
            logMessage("Debug-Log (letzte 20 Zeilen):", "DEBUG");
            $debugLines = explode("\n", $debugLog);
            $lastLines = array_slice($debugLines, -20);
            foreach ($lastLines as $line) {
                logMessage("  " . $line, "LOG");
            }
        }
        
        return [
            'success' => false,
            'error' => 'Fehler bei der Ausführung von Puppeteer: ' . substr($result['output'], 0, 500),
            'rawOutput' => $result['output']
        ];
    }
    
    // JSON-Ergebnis aus der Datei lesen
    logMessage("Lese Ergebnisdatei: " . $scriptData['outputFile'], "INFO");
    if (!file_exists($scriptData['outputFile'])) {
        logMessage("Ergebnisdatei wurde nicht erstellt!", "ERROR");
        return [
            'success' => false,
            'error' => 'Keine Ergebnisdatei gefunden',
            'rawOutput' => $result['output']
        ];
    }
    
    $jsonOutput = file_get_contents($scriptData['outputFile']);
    $data = json_decode($jsonOutput, true);
    
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
    
    // Aufräumen - temporäre Script-Datei löschen, wenn nicht im Debug-Modus
    if (!$DEBUG) {
        @unlink($scriptFile);
    } else {
        logMessage("Debug-Modus aktiv: Temporäre Dateien werden beibehalten", "DEBUG");
    }
    
    return $data;
}

// HTML-Formular und Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formular wurde abgeschickt
    $youtubeUrl = $_POST['youtube_url'] ?? '';
    $username = $_POST['google_username'] ?? '';
    $password = $_POST['google_password'] ?? '';
    
    if (!empty($youtubeUrl)) {
        echo "<div class='processing-status'>";
        echo "<h3>Verarbeite Video...</h3>";
        echo "<div id='status-updates'></div>";
        
        // Sofortiges Flushen der Ausgabe
        echo str_pad('', 4096) . "\n";
        ob_flush();
        flush();
        
        // Verarbeite das Video
        $result = processYoutubeVideo($youtubeUrl, $username, $password);
        
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
        </ul>
    </div>
    <?php endif; ?>
</body>
</html> 