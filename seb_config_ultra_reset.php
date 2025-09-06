<?php
/**
 * ULTRA-NUKLEAR SEB-Reset
 * Komplette SEB-Zurücksetzung mit aggressiven Methoden
 */

require_once __DIR__ . '/includes/seb_functions.php';

// Hole Test-Code
$testCode = $_GET['code'] ?? '';

if (empty($testCode)) {
    http_response_code(400);
    die('Fehler: Test-Code fehlt');
}

// Suche nach Test-Datei
$testFile = null;
$searchPatterns = [
    __DIR__ . "/tests/{$testCode}.xml",
    __DIR__ . "/tests/{$testCode}_*.xml"
];

foreach ($searchPatterns as $pattern) {
    $files = glob($pattern);
    if (!empty($files)) {
        $testFile = $files[0];
        break;
    }
}

if (!$testFile || !file_exists($testFile)) {
    http_response_code(404);
    die("Fehler: Test '$testCode' nicht gefunden");
}

// Generiere SEB-Konfiguration
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$testUrl = $baseUrl . dirname($_SERVER['PHP_SELF']) . '/name_form.php?code=' . urlencode($testCode) . '&seb=true';

// ULTRA-AGGRESSIVE SEB-Reset-Konfiguration
$timestamp = time();
$uniqueId = uniqid();
$randomSalt = bin2hex(random_bytes(32));

$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <!-- ULTRA-NUKLEAR: KOMPLETT NEUE IDENTITÄT -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- VOLLSTÄNDIGER RESET ALLER SEB-EINSTELLUNGEN -->
    <key>configFileCreateDefault</key>
    <false/>
    <key>cryptoIdentity</key>
    <integer>0</integer>
    <key>encryptionCertificateRef</key>
    <string></string>
    
    <!-- ULTRA-EINDEUTIGE IDENTIFIKATOREN -->
    <key>originatorName</key>
    <string>ULTRA-RESET-' . $timestamp . '-' . $uniqueId . '</string>
    <key>originatorVersion</key>
    <string>9.9.' . rand(100, 999) . '</string>
    
    <!-- AGGRESSIVER EXAM-KEY-RESET -->
    <key>examKeySalt</key>
    <data>' . base64_encode('ULTRA_RESET_' . $randomSalt . '_' . $timestamp) . '</data>
    <key>sendBrowserExamKey</key>
    <true/>
    <key>browserExamKey</key>
    <string>' . hash('sha256', 'ULTRA_RESET_' . $randomSalt . '_' . $timestamp) . '</string>
    <key>configKey</key>
    <string>' . hash('md5', 'CONFIG_RESET_' . $timestamp . '_' . $uniqueId) . '</string>
    
    <!-- KONFIGURATIONSWECHSEL ULTIMATIV ERZWINGEN -->
    <key>allowReconfiguration</key>
    <true/>
    <key>forceReconfiguration</key>
    <true/>
    <key>downloadAndOpenSebConfig</key>
    <true/>
    <key>examSessionClearCookiesOnStart</key>
    <true/>
    <key>examSessionClearSessionOnStart</key>
    <true/>
    <key>restartExamUseStartURL</key>
    <true/>
    <key>restartExamText</key>
    <string>SEB wird komplett zurückgesetzt...</string>
    
    <!-- ALLE ADMIN-BESCHRÄNKUNGEN ENTFERNEN -->
    <key>hashedAdminPassword</key>
    <string></string>
    <key>adminPasswordRequired</key>
    <false/>
    <key>restartExamPasswordHash</key>
    <string></string>
    
    <!-- ALLE WARNUNGEN UND DIALOGE DEAKTIVIEREN -->
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    <key>showTime</key>
    <false/>
    <key>showInputLanguage</key>
    <false/>
    <key>showTaskBar</key>
    <false/>
    <key>showMenuBar</key>
    <false/>
    
    <!-- PASSWORT-SCHUTZ KOMPLETT AUSSCHALTEN -->
    <key>allowQuit</key>
    <true/>
    <key>hashedQuitPassword</key>
    <string></string>
    <key>quitURL</key>
    <string>seb://quit</string>
    <key>quitURLConfirm</key>
    <false/>
    
    <!-- URL-FILTER KOMPLETT DEAKTIVIERT -->
    <key>URLFilterEnable</key>
    <false/>
    <key>URLFilterEnableContentFilter</key>
    <false/>
    <key>urlFilterTrustedContent</key>
    <true/>
    <key>urlFilterRules</key>
    <array></array>
    
    <!-- BROWSER-BESCHRÄNKUNGEN MINIMAL -->
    <key>browserWindowAllowReload</key>
    <true/>
    <key>browserWindowShowURL</key>
    <false/>
    <key>browserWindowAllowBackForward</key>
    <false/>
    <key>browserWindowAllowSpellCheck</key>
    <false/>
    <key>browserWindowAllowPrinting</key>
    <false/>
    <key>browserWindowAllowNavigation</key>
    <false/>
    <key>browserWindowAllowConfigFileDownload</key>
    <true/>
    <key>newBrowserWindowByLinkPolicy</key>
    <integer>0</integer>
    <key>newBrowserWindowByScriptPolicy</key>
    <integer>0</integer>
    
    <!-- SEB-BROWSER GRUNDKONFIGURATION -->
    <key>enableSebBrowser</key>
    <true/>
    <key>sebServicePolicy</key>
    <integer>0</integer>
    <key>browserURLSalt</key>
    <false/>
    
    <!-- ALLE SICHERHEIT DEAKTIVIERT (für Kompatibilität) -->
    <key>allowSwitchToApplications</key>
    <true/>
    <key>enableAppSwitcherCheck</key>
    <false/>
    <key>forceAppFolderInstall</key>
    <false/>
    <key>killExplorerShell</key>
    <false/>
    <key>createNewDesktop</key>
    <false/>
    <key>hookKeys</key>
    <false/>
    
    <!-- ALLE TASTATUR-SPERREN AUS -->
    <key>enableAltTab</key>
    <true/>
    <key>enableAltEsc</key>
    <true/>
    <key>enableAltF4</key>
    <true/>
    <key>enableCtrlEsc</key>
    <true/>
    <key>enableStartMenu</key>
    <true/>
    <key>enablePrintScreen</key>
    <true/>
    <key>enableRightMouse</key>
    <true/>
    <key>enableF1</key>
    <true/>
    <key>enableF2</key>
    <true/>
    <key>enableF3</key>
    <true/>
    <key>enableF4</key>
    <true/>
    <key>enableF5</key>
    <true/>
    <key>enableF6</key>
    <true/>
    <key>enableF7</key>
    <true/>
    <key>enableF8</key>
    <true/>
    <key>enableF9</key>
    <true/>
    <key>enableF10</key>
    <true/>
    <key>enableF11</key>
    <true/>
    <key>enableF12</key>
    <true/>
    
    <!-- PROZESS-MONITORING KOMPLETT AUS -->
    <key>monitorProcesses</key>
    <false/>
    <key>prohibitedProcesses</key>
    <array></array>
    <key>permittedProcesses</key>
    <array></array>
    <key>processWatchdog</key>
    <false/>
    
    <!-- MOBILE/TOUCH EINSTELLUNGEN OFFEN -->
    <key>touchOptimized</key>
    <false/>
    <key>enableTouchExit</key>
    <true/>
    <key>iOSBetaVersionExpiryDate</key>
    <date>2030-12-31T23:59:59Z</date>
    
    <!-- iOS-BESCHRÄNKUNGEN ALLE AUS -->
    <key>iOSEnableGuidedAccessLinkTransform</key>
    <false/>
    <key>iOSAllowCameraApp</key>
    <true/>
    <key>iOSAllowDictation</key>
    <true/>
    <key>iOSAllowKeyboardShortcuts</key>
    <true/>
    <key>iOSAllowSpellCheck</key>
    <true/>
    <key>iOSAllowAutoCorrect</key>
    <true/>
    <key>iOSShowMenuBar</key>
    <true/>
    <key>iOSAllowAssistiveTouch</key>
    <true/>
    <key>iOSAllowInAppPurchases</key>
    <true/>
    <key>iOSAllowVideoRecording</key>
    <true/>
    
    <!-- AUDIO/VIDEO ALLES ERLAUBT -->
    <key>audioControlEnabled</key>
    <false/>
    <key>audioMute</key>
    <false/>
    <key>audioSetVolumeLevel</key>
    <false/>
    
    <!-- BROWSER-FEATURES ALLE AKTIVIERT -->
    <key>browserMediaAutoplay</key>
    <true/>
    <key>browserMediaCaptureCamera</key>
    <true/>
    <key>browserMediaCaptureMicrophone</key>
    <true/>
    <key>browserMediaCaptureScreen</key>
    <true/>
    <key>browserPopupPolicy</key>
    <integer>0</integer>
    <key>browserScreenKeyboard</key>
    <false/>
    <key>browserViewMode</key>
    <integer>0</integer>
    
    <!-- LOGGING AKTIVIERT FÜR DEBUG -->
    <key>enableLogging</key>
    <true/>
    <key>logLevel</key>
    <integer>4</integer>
    <key>logDirectoryOSX</key>
    <string>~/Library/Logs/SafeExamBrowser/</string>
    <key>logDirectoryWin</key>
    <string>%LOCALAPPDATA%\\SafeExamBrowser\\Logs\\</string>
    
    <!-- SESSION-MANAGEMENT ZURÜCKSETZEN -->
    <key>examSessionService</key>
    <integer>0</integer>
    <key>browserSessionMode</key>
    <integer>0</integer>
    <key>sessionTimeout</key>
    <integer>0</integer>
    
    <!-- PROXY-EINSTELLUNGEN NEUTRAL -->
    <key>proxySettingsPolicy</key>
    <integer>0</integer>
    
    <!-- SPELLCHECKER UND AUTOCORRECT -->
    <key>allowSpellCheck</key>
    <true/>
    <key>allowSpellCheckDictionary</key>
    <true/>
    
    <!-- DOWNLOAD-VERHALTEN -->
    <key>allowDownUploads</key>
    <true/>
    <key>allowCustomDownloadLocation</key>
    <true/>
    <key>downloadDirectoryOSX</key>
    <string>~/Downloads</string>
    <key>downloadDirectoryWin</key>
    <string>%USERPROFILE%\\Downloads</string>
    
    <!-- ULTRA-EINDEUTIGE METADATEN -->
    <key>hashedQuitPassword</key>
    <string></string>
    <key>hashedAdminPassword</key>
    <string></string>
</dict>
</plist>';

// Debug-Logging
error_log("SEB-Ultra-Reset-Config generiert für Test: $testCode, Timestamp: $timestamp, UniqueID: $uniqueId");

// Headers für Download setzen
header('Content-Type: application/x-apple-plist');
header('Content-Disposition: attachment; filename="ULTRA_RESET_' . $testCode . '_' . $timestamp . '.seb"');
header('Content-Length: ' . strlen($sebConfig));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Konfiguration ausgeben
echo $sebConfig;
?>
