<?php
/**
 * SEB EXAM MODE Konfiguration
 * Speziell für Windows SEB - KEIN Administrator-Passwort erforderlich
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

// MINIMALE EXAM MODE Konfiguration für Windows
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    
    <!-- ========================================== -->
    <!-- EXAM MODE CONFIGURATION                   -->
    <!-- ========================================== -->
    
    <!-- START URL -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- EXAM MODE AKTIVIEREN (KEIN ADMIN-PASSWORT!) -->
    <key>sebMode</key>
    <integer>1</integer>
    <key>configPurpose</key>
    <integer>0</integer>
    <key>sebConfigPurpose</key>
    <integer>0</integer>
    
    <!-- EXPLICIT EXAM MODE MARKERS -->
    <key>startExamMode</key>
    <true/>
    <key>examMode</key>
    <true/>
    <key>sebConfigurationIsExam</key>
    <true/>
    
    <!-- EXAM IDENTIFIER -->
    <key>examKeySalt</key>
    <string>exam-mode-' . date('YmdHis') . '</string>
    <key>examKey</key>
    <string>' . hash('sha256', 'exam-' . $testCode . '-' . date('YmdHis')) . '</string>
    
    <!-- ========================================== -->
    <!-- ADMIN RIGHTS PREVENTION                   -->
    <!-- ========================================== -->
    
    <!-- NO ADMIN RIGHTS REQUIRED -->
    <key>sebRequiresAdminRights</key>
    <false/>
    <key>sebConfigurationMustBeUnlocked</key>
    <false/>
    <key>sebLocalSettingsEnabled</key>
    <false/>
    <key>allowPreferencesWindow</key>
    <false/>
    
    <!-- WINDOWS SERVICE SETTINGS -->
    <key>sebWindowsServicePolicy</key>
    <integer>0</integer>
    <key>sebWindowsServiceIgnore</key>
    <true/>
    <key>sebWindowsServiceEnable</key>
    <false/>
    
    <!-- ========================================== -->
    <!-- BASIC SECURITY SETTINGS                   -->
    <!-- ========================================== -->
    
    <!-- QUIT CONFIGURATION -->
    <key>allowQuit</key>
    <true/>
    <key>quitURL</key>
    <string>seb://quit</string>
    <key>quitURLConfirm</key>
    <false/>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- BROWSER RESTRICTIONS -->
    <key>browserWindowAllowReload</key>
    <false/>
    <key>browserWindowShowURL</key>
    <false/>
    <key>browserWindowAllowBackForward</key>
    <false/>
    <key>browserWindowAllowNavigation</key>
    <false/>
    
    <!-- APPLICATION SWITCHING -->
    <key>allowSwitchToApplications</key>
    <false/>
    <key>enableAppSwitcherCheck</key>
    <true/>
    
    <!-- ========================================== -->
    <!-- URL FILTER (DISABLED)                     -->
    <!-- ========================================== -->
    
    <key>URLFilterEnable</key>
    <false/>
    <key>URLFilterEnableContentFilter</key>
    <false/>
    
    <!-- ========================================== -->
    <!-- CONFIGURATION MANAGEMENT                  -->
    <!-- ========================================== -->
    
    <!-- ALLOW RECONFIGURATION (NO FORCE) -->
    <key>allowReconfiguration</key>
    <true/>
    <key>forceReconfiguration</key>
    <false/>
    <key>downloadAndOpenSebConfig</key>
    <true/>
    
    <!-- NO WARNINGS -->
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    <key>showTime</key>
    <false/>
    
    <!-- SESSION MANAGEMENT -->
    <key>examSessionClearCookiesOnStart</key>
    <true/>
    <key>restartExamUseStartURL</key>
    <true/>
    
    <!-- ========================================== -->
    <!-- LOGGING (MINIMAL)                         -->
    <!-- ========================================== -->
    
    <key>enableLogging</key>
    <false/>
    <key>logLevel</key>
    <integer>0</integer>
    
    <!-- ========================================== -->
    <!-- WINDOW SETTINGS                           -->
    <!-- ========================================== -->
    
    <key>browserViewMode</key>
    <integer>0</integer>
    <key>mainBrowserWindowHeight</key>
    <string>100%</string>
    <key>mainBrowserWindowWidth</key>
    <string>100%</string>
    <key>browserWindowTitleBarHeight</key>
    <integer>0</integer>
    
    <!-- ========================================== -->
    <!-- METADATA                                  -->
    <!-- ========================================== -->
    
    <key>originatorName</key>
    <string>MCQ Test System - Exam Mode</string>
    <key>originatorVersion</key>
    <string>1.0.0</string>
    
</dict>
</plist>';

// Content-Type für .seb Datei setzen
header('Content-Type: application/seb');
header('Content-Disposition: attachment; filename="' . $testCode . '_exam_mode.seb"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// SEB-Konfiguration ausgeben
echo $sebConfig;
?>
