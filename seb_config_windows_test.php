<?php
/**
 * Windows SEB Test-Konfiguration
 * Optimiert für Windows SEB ohne Admin-Passwort-Anfrage
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

// Windows-optimierte SEB-Konfiguration
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <!-- BASIC CONFIGURATION -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- WINDOWS: NO ADMIN PASSWORD REQUIRED -->
    <key>sebMode</key>
    <integer>0</integer>
    <key>sebRequiresAdminRights</key>
    <false/>
    <key>sebConfigurationMustBeUnlocked</key>
    <false/>
    <key>sebLocalSettingsEnabled</key>
    <false/>
    
    <!-- WINDOWS SERVICE SETTINGS -->
    <key>sebWindowsServicePolicy</key>
    <integer>0</integer>
    <key>sebWindowsServiceIgnore</key>
    <true/>
    <key>sebWindowsServiceEnable</key>
    <false/>
    
    <!-- CLIENT CONFIGURATION (NOT SYSTEM-WIDE) -->
    <key>sebClientConfiguration</key>
    <true/>
    <key>sebUserConfiguration</key>
    <true/>
    <key>sebSystemConfiguration</key>
    <false/>
    <key>sebGlobalConfiguration</key>
    <false/>
    
    <!-- TEMPORARY SESSION MARKER -->
    <key>configPurpose</key>
    <integer>1</integer>
    <key>sebConfigPurpose</key>
    <integer>1</integer>
    <key>examKeySalt</key>
    <string>windows-test-session-' . date('YmdHis') . '</string>
    
    <!-- NO SYSTEM CHANGES -->
    <key>allowPreferencesWindow</key>
    <false/>
    <key>allowDisplayMirroring</key>
    <false/>
    <key>allowWlan</key>
    <false/>
    <key>allowWindowCapture</key>
    <false/>
    <key>allowScreenSharing</key>
    <false/>
    
    <!-- CONFIGURATION MANAGEMENT -->
    <key>allowReconfiguration</key>
    <true/>
    <key>forceReconfiguration</key>
    <false/>
    <key>downloadAndOpenSebConfig</key>
    <true/>
    <key>examSessionClearCookiesOnStart</key>
    <true/>
    <key>restartExamUseStartURL</key>
    <true/>
    
    <!-- NO PASSWORDS -->
    <key>hashedAdminPassword</key>
    <string></string>
    <key>restartExamPasswordHash</key>
    <string></string>
    
    <!-- WARNINGS DISABLED -->
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    <key>showTime</key>
    <false/>
    
    <!-- QUIT CONFIGURATION -->
    <key>allowQuit</key>
    <true/>
    <key>quitURL</key>
    <string>seb://quit</string>
    <key>quitURLConfirm</key>
    <false/>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- URL FILTER DISABLED -->
    <key>URLFilterEnable</key>
    <false/>
    <key>URLFilterEnableContentFilter</key>
    <false/>
    
    <!-- BROWSER SETTINGS -->
    <key>browserWindowAllowReload</key>
    <false/>
    <key>browserWindowShowURL</key>
    <false/>
    <key>browserWindowAllowBackForward</key>
    <false/>
    <key>browserWindowAllowSpellCheck</key>
    <false/>
    <key>browserWindowAllowPrinting</key>
    <false/>
    <key>browserWindowAllowExternalLinks</key>
    <false/>
    <key>browserWindowAllowNavigation</key>
    <false/>
    
    <!-- KIOSK MODE -->
    <key>touchOptimized</key>
    <false/>
    <key>browserViewMode</key>
    <integer>0</integer>
    <key>mainBrowserWindowHeight</key>
    <string>100%</string>
    <key>mainBrowserWindowWidth</key>
    <string>100%</string>
    <key>browserWindowTitleBarHeight</key>
    <integer>0</integer>
    
    <!-- SECURITY MINIMAL -->
    <key>allowSwitchToApplications</key>
    <false/>
    <key>enableAppSwitcherCheck</key>
    <false/>
    <key>forceAppFolderInstall</key>
    <false/>
    <key>enableLogging</key>
    <false/>
    <key>logLevel</key>
    <integer>0</integer>
    
    <!-- ORIGINATOR INFO -->
    <key>originatorName</key>
    <string>MCQ Test System - Windows Test Config</string>
    <key>originatorVersion</key>
    <string>1.0.0</string>
    
</dict>
</plist>';

// Content-Type für .seb Datei setzen
header('Content-Type: application/seb');
header('Content-Disposition: attachment; filename="' . $testCode . '_windows_test.seb"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// SEB-Konfiguration ausgeben
echo $sebConfig;
?>
