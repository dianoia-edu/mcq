// Browser-Konfiguration
$browser = await $puppeteer->launch([
    'headless' => true,
    'args' => [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--disable-gpu',
        '--window-size=1920,1080',
        '--disable-web-security',
        '--disable-features=IsolateOrigins,site-per-process',
        '--disable-site-isolation-trials'
    ],
    'ignoreHTTPSErrors' => true,
    'defaultViewport' => [
        'width' => 1920,
        'height' => 1080
    ],
    'executablePath' => 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
]);

try {
    // Neue Seite erstellen
    $page = await $browser->newPage();
    
    // Timeout für Navigation setzen
    await $page->setDefaultNavigationTimeout(30000);
    await $page->setDefaultTimeout(30000);
    
    // User-Agent setzen
    await $page->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    // Navigation mit Wartezeit
    await $page->goto($url, [
        'waitUntil' => ['networkidle0', 'domcontentloaded'],
        'timeout' => 30000
    ]);
    
    // Warte auf wichtige Elemente
    await $page->waitForSelector('h1.ytd-video-primary-info-renderer', ['timeout' => 10000]);
    await $page->waitForSelector('ytd-video-secondary-info-renderer', ['timeout' => 10000]);
    
    // Warte kurz, um sicherzustellen, dass alle Inhalte geladen sind
    await $page->waitForTimeout(2000);
    
    // Extrahiere Informationen
    $title = await $page->evaluate('() => {
        const titleElement = document.querySelector("h1.ytd-video-primary-info-renderer");
        return titleElement ? titleElement.textContent.trim() : "";
    }');
    
    $channel = await $page->evaluate('() => {
        const channelElement = document.querySelector("ytd-video-secondary-info-renderer ytd-channel-name");
        return channelElement ? channelElement.textContent.trim() : "";
    }');
    
    $views = await $page->evaluate('() => {
        const viewElement = document.querySelector("ytd-video-primary-info-renderer ytd-video-view-count-renderer");
        return viewElement ? viewElement.textContent.trim() : "";
    }');
    
    $description = await $page->evaluate('() => {
        const descElement = document.querySelector("ytd-video-secondary-info-renderer ytd-expander");
        return descElement ? descElement.textContent.trim() : "";
    }');
    
    // Schließe Browser
    await $browser->close();
    
    return [
        'success' => true,
        'data' => [
            'title' => $title,
            'channel' => $channel,
            'views' => $views,
            'description' => $description
        ]
    ];
    
} catch (Exception $e) {
    // Stelle sicher, dass der Browser geschlossen wird
    if (isset($browser)) {
        await $browser->close();
    }
    
    return [
        'success' => false,
        'error' => $e->getMessage()
    ];
} 