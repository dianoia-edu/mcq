<?php
/**
 * SEB-Konfiguration RESET Generator
 * Erstellt eine "neutrale" SEB-Config die SEB zurücksetzt
 */

require_once __DIR__ . '/includes/seb_functions.php';

// Hole Test-Code
$testCode = $_GET['code'] ?? '';

if (empty($testCode)) {
    http_response_code(400);
    die('Fehler: Test-Code fehlt');
}

// Suche nach Test-Datei (flexibel mit Patterns)
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

// RESET-SEB-Konfiguration (zwingt SEB zum Neustart)
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <!-- Basis-Konfiguration -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- KRITISCH: SEB ZWINGEN NEUE CONFIG ZU LADEN -->
    <key>configFileCreateDefault</key>
    <false/>
    <key>cryptoIdentity</key>
    <integer>0</integer>
    <key>encryptionCertificateRef</key>
    <string></string>
    
    <!-- NEUE EINDEUTIGE HASH-WERTE (zwingt SEB zur Neuerkennung) -->
    <key>originatorName</key>
    <string>MCQ-Test-System-Reset-' . time() . '</string>
    <key>originatorVersion</key>
    <string>3.4.' . rand(100, 999) . '</string>
    
    <!-- EXAM-KEY MIT EINDEUTIGER SALT (macht Config einzigartig) -->
    <key>examKeySalt</key>
    <data>' . base64_encode('reset_' . $testCode . '_' . time() . '_' . rand(1000, 9999)) . '</data>
    <key>sendBrowserExamKey</key>
    <true/>
    <key>browserExamKey</key>
    <string>' . hash('sha256', 'reset_' . $testCode . '_' . time()) . '</string>
    
    <!-- KONFIGURATIONSWECHSEL ERZWINGEN -->
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
    <string>Test wird neu gestartet...</string>
    
    <!-- ALLE WARNUNGEN DEAKTIVIERT -->
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    <key>showTime</key>
    <false/>
    <key>showInputLanguage</key>
    <false/>
    
    <!-- PASSWORT-SCHUTZ MINIMAL -->
    <key>hashedAdminPassword</key>
    <string></string>
    <key>allowQuit</key>
    <true/>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    <key>quitURL</key>
    <string>seb://quit</string>
    <key>quitURLConfirm</key>
    <false/>
    
    <!-- URL-FILTER KOMPLETT AUS -->
    <key>URLFilterEnable</key>
    <false/>
    <key>URLFilterEnableContentFilter</key>
    <false/>
    <key>urlFilterTrustedContent</key>
    <true/>
    
    <!-- BROWSER-EINSTELLUNGEN (MINIMAL) -->
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
    <integer>2</integer>
    <key>newBrowserWindowByScriptPolicy</key>
    <integer>2</integer>
    
    <!-- SEB-BROWSER AKTIVIERT -->
    <key>enableSebBrowser</key>
    <true/>
    <key>sebServicePolicy</key>
    <integer>1</integer>
    <key>browserURLSalt</key>
    <true/>
    
    <!-- MINIMALE SICHERHEIT (für bessere Kompatibilität) -->
    <key>allowSwitchToApplications</key>
    <false/>
    <key>enableAppSwitcherCheck</key>
    <false/>
    <key>forceAppFolderInstall</key>
    <false/>
    <key>killExplorerShell</key>
    <false/>
    <key>createNewDesktop</key>
    <false/>
    <key>hookKeys</key>
    <true/>
    
    <!-- TASTATUR-SPERREN (MINIMAL) -->
    <key>enableAltTab</key>
    <false/>
    <key>enableAltEsc</key>
    <false/>
    <key>enableAltF4</key>
    <false/>
    <key>enableCtrlEsc</key>
    <false/>
    <key>enableStartMenu</key>
    <false/>
    <key>enablePrintScreen</key>
    <false/>
    <key>enableRightMouse</key>
    <true/>
    
    <!-- PROZESS-MONITORING AUS (verhindert Konflikte) -->
    <key>monitorProcesses</key>
    <false/>
    <key>prohibitedProcesses</key>
    <array></array>
    <key>permittedProcesses</key>
    <array></array>
    
    <!-- MOBILE/TOUCH EINSTELLUNGEN -->
    <key>touchOptimized</key>
    <true/>
    <key>enableTouchExit</key>
    <false/>
    <key>iOSBetaVersionExpiryDate</key>
    <date>2025-12-31T23:59:59Z</date>
    
    <!-- iOS-EINSTELLUNGEN (MINIMAL) -->
    <key>iOSEnableGuidedAccessLinkTransform</key>
    <false/>
    <key>iOSAllowCameraApp</key>
    <false/>
    <key>iOSAllowDictation</key>
    <false/>
    <key>iOSAllowKeyboardShortcuts</key>
    <false/>
    <key>iOSAllowSpellCheck</key>
    <false/>
    <key>iOSAllowAutoCorrect</key>
    <false/>
    <key>iOSShowMenuBar</key>
    <false/>
    <key>iOSAllowAssistiveTouch</key>
    <false/>
    
    <!-- AUDIO/VIDEO EINSTELLUNGEN -->
    <key>audioControlEnabled</key>
    <true/>
    <key>audioMute</key>
    <false/>
    <key>audioSetVolumeLevel</key>
    <false/>
    
    <!-- PRÜFUNGSMODUS-EINSTELLUNGEN -->
    <key>browserMediaAutoplay</key>
    <false/>
    <key>browserMediaCaptureCamera</key>
    <false/>
    <key>browserMediaCaptureMicrophone</key>
    <false/>
    <key>browserMediaCaptureScreen</key>
    <false/>
    
    <!-- LOGGING UND DEBUG -->
    <key>enableLogging</key>
    <true/>
    <key>logLevel</key>
    <integer>1</integer>
    
    <!-- EINDEUTIGE SESSION-ID (verhindert Konflikte) -->
    <key>examSessionService</key>
    <integer>0</integer>
    <key>browserSessionMode</key>
    <integer>0</integer>
</dict>
</plist>';

// Debug-Logging
error_log("SEB-Reset-Config generiert für Test: $testCode, URL: $testUrl, Timestamp: " . time());

// Headers für Download setzen
header('Content-Type: application/x-apple-plist');
header('Content-Disposition: attachment; filename="test_' . $testCode . '_reset_' . time() . '.seb"');
header('Content-Length: ' . strlen($sebConfig));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Konfiguration ausgeben
echo $sebConfig;
?>
