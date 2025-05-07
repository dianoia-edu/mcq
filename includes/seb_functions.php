<?php
/**
 * SEB (Safe Exam Browser) Hilfsfunktionen
 */

/**
 * Prüft, ob der aktuelle Browser SEB ist
 */
function isSEBBrowser() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // Verbesserte SEB-Erkennung für iOS
    return (strpos($userAgent, 'SEB') !== false || 
            strpos($userAgent, 'SafeExamBrowser') !== false ||
            strpos($userAgent, 'SEB_iOS') !== false);
}

/**
 * Generiert die SEB-Konfiguration für einen Test
 */
function generateSEBConfig($testCode) {
    // Verwende die vollständige URL mit Protokoll
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $baseUrl = $protocol . $_SERVER['HTTP_HOST'];
    $testUrl = $baseUrl . "/index.php?code=" . urlencode($testCode);
    
    $config = [
        'startURL' => $testUrl,
        'security' => [
            'enableBrowserWindowToolbar' => false,
            'enableReloadButton' => false,
            'enableBackForwardNavigation' => false,
            'enableAddressBar' => false,
            'enableSpellCheck' => false
        ],
        'browserWindow' => [
            'hasTaskBar' => false,
            'hasMenuBar' => false,
            'hasToolbar' => false,
            'hasAddressBar' => false,
            'hasStatusBar' => false
        ]
    ];

    // Konvertiere zu XML
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><config></config>');
    
    // Füge Start-URL hinzu
    $xml->addChild('startURL', $config['startURL']);
    
    // Whitelist/URL-Filter aktivieren
    $xml->addChild('URLFilterEnable', 'true');
    $urlFilterRules = $xml->addChild('URLFilterRules');
    $urlFilterRules->addChild('string', '^https://www\\.dianoia-ai\\.de/.*$');
    $urlFilterRules->addChild('string', '^https://dianoia-ai\\.de/.*$');
    
    // Füge Sicherheitseinstellungen hinzu
    $security = $xml->addChild('security');
    foreach ($config['security'] as $key => $value) {
        $security->addChild($key, $value ? 'true' : 'false');
    }
    
    // Füge Browser-Fenster-Einstellungen hinzu
    $browserWindow = $xml->addChild('browserWindow');
    foreach ($config['browserWindow'] as $key => $value) {
        $browserWindow->addChild($key, $value ? 'true' : 'false');
    }

    return $xml->asXML();
}

/**
 * Startet SEB mit der generierten Konfiguration
 */
function startSEB($testCode) {
    $config = generateSEBConfig($testCode);
    
    // Debug-Logging in Datei
    $debugLog = "SEB Debug Log:\n";
    $debugLog .= "Zeit: " . date('Y-m-d H:i:s') . "\n";
    $debugLog .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Nicht gesetzt') . "\n";
    $debugLog .= "Config: " . $config . "\n";
    
    // Für iOS SEB - Verwende die korrekte URL-Struktur
    $sebUrl = "seb://start?config=" . urlencode(base64_encode($config));
    $debugLog .= "SEB URL: " . $sebUrl . "\n";
    $debugLog .= "----------------------------------------\n";
    
    // Schreibe in Debug-Datei im temp Verzeichnis
    $logFile = sys_get_temp_dir() . '/seb_debug.log';
    file_put_contents($logFile, $debugLog, FILE_APPEND);
    
    // Setze Header für iOS SEB
    header("Location: " . $sebUrl);
    exit;
} 