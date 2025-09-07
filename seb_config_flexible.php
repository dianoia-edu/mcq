<?php
/**
 * Flexible SEB-Konfiguration Generator
 * Optimiert für Konfigurationswechsel und automatisches Laden
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

// Test-Titel aus XML extrahieren
$testTitle = $testCode;
try {
    $xml = simplexml_load_file($testFile);
    if ($xml && isset($xml->title)) {
        $testTitle = (string)$xml->title;
    }
} catch (Exception $e) {
    error_log('Fehler beim Laden des Test-Titels: ' . $e->getMessage());
}

// Generiere SEB-Konfiguration
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$testUrl = $baseUrl . dirname($_SERVER['PHP_SELF']) . '/name_form.php?code=' . urlencode($testCode) . '&seb=true';

// FLEXIBLE SEB-Konfiguration (optimiert für Konfigurationswechsel)
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <!-- Basis-Konfiguration -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- WINDOWS: FLEXIBLES SEB-KONFIGURATIONSMANAGEMENT OHNE ADMIN-PROMPT -->
    <key>allowReconfiguration</key>
    <true/>
    <key>forceReconfiguration</key>
    <false/>
    <key>downloadAndOpenSebConfig</key>
    <true/>
    <key>configFileCreateDefault</key>
    <false/>
    <key>examSessionClearCookiesOnStart</key>
    <true/>
    <key>restartExamUseStartURL</key>
    <true/>
    
    <!-- KEINE ADMINISTRATOR-RECHTE ERFORDERLICH -->
    <key>sebRequiresAdminRights</key>
    <false/>
    <key>sebConfigurationMustBeUnlocked</key>
    <false/>
    <key>sebLocalSettingsEnabled</key>
    <false/>
    <key>sebWindowsServicePolicy</key>
    <integer>0</integer>
    <key>sebWindowsServiceIgnore</key>
    <true/>
    <key>sebWindowsServiceEnable</key>
    <false/>
    
    <!-- CLIENT-KONFIGURATION (KEINE ADMIN-INSTALLATION) -->
    <key>sebClientConfiguration</key>
    <true/>
    <key>sebUserConfiguration</key>
    <true/>
    <key>sebSystemConfiguration</key>
    <false/>
    <key>sebGlobalConfiguration</key>
    <false/>
    <key>configPurpose</key>
    <integer>1</integer>
    <key>sebConfigPurpose</key>
    <integer>1</integer>
    <key>examKeySalt</key>
    <string>client-exam-session-' . date('YmdHis') . '</string>
    
    <!-- KEIN PASSWORT-SCHUTZ FÜR KONFIGURATIONSWECHSEL -->
    <key>hashedAdminPassword</key>
    <string></string>
    <key>restartExamPasswordHash</key>
    <string></string>
    
    <!-- WARNUNGEN DEAKTIVIERT -->
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    <key>showTime</key>
    <false/>
    
    <!-- QUIT-KONFIGURATION (EINFACH) -->
    <key>allowQuit</key>
    <true/>
    <key>quitURL</key>
    <string>seb://quit</string>
    <key>quitURLConfirm</key>
    <false/>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- URL-FILTER KOMPLETT DEAKTIVIERT -->
    <key>URLFilterEnable</key>
    <false/>
    <key>URLFilterEnableContentFilter</key>
    <false/>
    
    <!-- MINIMALE BROWSER-EINSCHRÄNKUNGEN -->
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
    
    <!-- SEB-SERVICES AKTIVIERT -->
    <key>sebServicePolicy</key>
    <integer>1</integer>
    <key>enableSebBrowser</key>
    <true/>
    
    <!-- MINIMALE SICHERHEITSEINSTELLUNGEN -->
    <key>allowSwitchToApplications</key>
    <false/>
    <key>enableAppSwitcherCheck</key>
    <true/>
    <key>forceAppFolderInstall</key>
    <true/>
    
    <!-- GRUNDLEGENDE SPERRE-EINSTELLUNGEN -->
    <key>killExplorerShell</key>
    <false/>
    <key>createNewDesktop</key>
    <false/>
    <key>hookKeys</key>
    <true/>
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
    
    <!-- PROZESS-MONITORING (MINIMAL) -->
    <key>monitorProcesses</key>
    <true/>
    <key>prohibitedProcesses</key>
    <array>
        <dict>
            <key>active</key>
            <true/>
            <key>currentUser</key>
            <true/>
            <key>executable</key>
            <string>taskmgr.exe</string>
        </dict>
    </array>
    
    <!-- TOUCH/MOBILE OPTIMIERUNGEN -->
    <key>touchOptimized</key>
    <true/>
    <key>enableTouchExit</key>
    <false/>
    
    <!-- MINIMALE IOS-EINSTELLUNGEN -->
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
    
    <!-- EXAM-KEYS (OPTIONAL) -->
    <key>examKeySalt</key>
    <data>' . base64_encode('test_' . $testCode . '_salt') . '</data>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- SEB-METADATEN -->
    <key>originatorName</key>
    <string>MCQ Test System - Flexible Config</string>
    <key>originatorVersion</key>
    <string>3.4.0</string>
</dict>
</plist>';

// Debug-Logging
error_log("SEB-Flexible-Config generiert für Test: $testCode, URL: $testUrl");

// Headers für Download setzen
header('Content-Type: application/x-apple-plist');
header('Content-Disposition: attachment; filename="test_' . $testCode . '_flexible.seb"');
header('Content-Length: ' . strlen($sebConfig));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Konfiguration ausgeben
echo $sebConfig;
?>
