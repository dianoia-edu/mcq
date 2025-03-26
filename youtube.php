<?php

$browser = await $puppeteer->launch([
    'headless' => true,
    'executablePath' => 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'args' => [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--disable-gpu',
        '--window-size=1920,1080',
        '--disable-web-security',
        '--disable-features=IsolateOrigins,site-per-process'
    ],
    'defaultViewport' => [
        'width' => 1920,
        'height' => 1080
    ]
]);

try {
    $page = await $browser->newPage();
    
    // Setze zusätzliche Browser-Einstellungen
    await $page->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    await $page->setExtraHTTPHeaders([
        'Accept-Language' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7'
    ]);

    // Konfiguriere Navigation
    await $page->setNavigationTimeout(30000);
    await $page->setDefaultNavigationTimeout(30000);
    await $page->setDefaultTimeout(30000);

    // Aktiviere JavaScript und Cookies
    await $page->setJavaScriptEnabled(true);
    await $page->setCookie([
        'name' => 'CONSENT',
        'value' => 'YES+cb.20231231-08-p',
        'domain' => '.youtube.com'
    ]);

    // Navigiere zur URL mit verbesserter Fehlerbehandlung
    $maxRetries = 3;
    $retryCount = 0;
    $success = false;

    while (!$success && $retryCount < $maxRetries) {
        try {
            $response = await $page->goto($url, [
                'waitUntil' => ['networkidle0', 'domcontentloaded'],
                'timeout' => 30000
            ]);

            if ($response && $response->status() === 200) {
                $success = true;
            } else {
                throw new Exception("Ungültiger Response-Status: " . ($response ? $response->status() : 'Keine Antwort'));
            }
        } catch (Exception $e) {
            $retryCount++;
            if ($retryCount >= $maxRetries) {
                throw $e;
            }
            await $page->waitForTimeout(2000); // Warte 2 Sekunden vor dem nächsten Versuch
        }
    }

    // Warte auf wichtige Elemente
    await $page->waitForSelector('h1.ytd-video-primary-info-renderer', ['timeout' => 10000]);
    await $page->waitForSelector('ytd-video-secondary-info-renderer', ['timeout' => 10000]);
    await $page->waitForSelector('ytd-video-description-renderer', ['timeout' => 10000]);
} catch (Exception $e) {
    echo "Fehler beim Laden der Seite: " . $e->getMessage();
} 