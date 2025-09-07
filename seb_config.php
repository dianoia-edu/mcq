<?php
/**
 * Dynamische SEB-Konfiguration Generator
 * Erstellt für jeden Test eine individuelle .seb Datei
 */

require_once __DIR__ . '/includes/seb_functions.php';

// Hole Test-Code
$testCode = $_GET['code'] ?? '';

if (empty($testCode)) {
    http_response_code(400);
    die('Fehler: Kein Test-Code angegeben');
}

// Bestimme Basis-Verzeichnis (konsistent mit der Projektstruktur)
$currentDir = dirname(__FILE__);
$isInTeacherDir = (basename($currentDir) === 'teacher');
$baseDir = $isInTeacherDir ? dirname($currentDir) : $currentDir;

// Validiere Test mit Pattern-Matching
// Erste Variante: Exakter Name
$testFile = $baseDir . '/tests/' . $testCode . '.xml';

// Zweite Variante: Mit Titel-Suffix (z.B. POT_die-potsdamer-konferenz...)
if (!file_exists($testFile)) {
    $testPattern = $baseDir . '/tests/' . $testCode . '_*.xml';
    $matchingFiles = glob($testPattern);
    
    if (!empty($matchingFiles)) {
        $testFile = $matchingFiles[0]; // Nimm die erste gefundene Datei
        error_log("SEB Config: Test gefunden mit Pattern: " . $testFile);
    }
}

error_log("SEB Config: Finale Test-Datei: " . $testFile);

if (!file_exists($testFile)) {
    $availableTests = glob($baseDir . '/tests/*.xml');
    $testCodes = [];
    foreach ($availableTests as $file) {
        $filename = basename($file, '.xml');
        $code = explode('_', $filename)[0];
        $testCodes[] = $code;
    }
    
    error_log("SEB Config: Verfügbare Test-Codes: " . implode(', ', array_unique($testCodes)));
    
    http_response_code(404);
    die('Fehler: Test "' . htmlspecialchars($testCode) . '" nicht gefunden. Verfügbare Codes: ' . implode(', ', array_unique($testCodes)));
}

// Lade Test-Titel aus XML
$testTitle = $testCode; // Fallback
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

// Erweiterte SEB-Konfiguration als XML
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <!-- Basis-Konfiguration -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- OFFIZIELLE SEB iOS QUIT-KONFIGURATION -->
    <key>quitURL</key>
    <string>seb://quit</string>
    <key>quitURLConfirm</key>
    <false/>
    <key>restartExamURL</key>
    <string>seb://quit</string>
    
    <!-- URL-Filter DEAKTIVIERT für SEB-Quit-URLs -->
    <key>URLFilterEnable</key>
    <false/>
    <key>URLFilterEnableContentFilter</key>
    <false/>
    
    <!-- Browser-Einstellungen -->
    <key>browserWindowAllowReload</key>
    <false/>
    <key>browserWindowShowURL</key>
    <false/>
    
    <!-- WICHTIG: URLs für App-Quit zulassen -->
    <key>sebServicePolicy</key>
    <integer>1</integer>
    <key>browserURLSalt</key>
    <true/>
    
    <!-- SEB-KONFIGURATION UND NEUSTART-EINSTELLUNGEN -->
    <key>allowSwitchToApplications</key>
    <false/>
    <key>enableSebBrowser</key>
    <true/>
    
    <!-- WICHTIG: Erlaube Konfigurationswechsel -->
    <key>downloadAndOpenSebConfig</key>
    <true/>
    <key>examSessionClearCookiesOnStart</key>
    <true/>
    <key>restartExamUseStartURL</key>
    <true/>
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
    <key>browserWindowAllowConfigFileDownload</key>
    <false/>
    
    <!-- SICHERHEITSEINSTELLUNGEN MIT BEENDEN-PASSWORT -->
    <key>allowQuit</key>
    <true/>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- WINDOWS: AUTOMATISCHE KONFIGURATION OHNE ADMIN-PROMPT -->
    <key>allowReconfiguration</key>
    <true/>
    <key>forceReconfiguration</key>
    <false/>
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    <key>sebWindowsServicePolicy</key>
    <integer>0</integer>
    <key>sebWindowsServiceIgnore</key>
    <true/>
    <key>sebWindowsServiceEnable</key>
    <false/>
    
    <!-- KEINE ADMINISTRATOR-RECHTE ERFORDERLICH -->
    <key>sebRequiresAdminRights</key>
    <false/>
    <key>sebConfigurationMustBeUnlocked</key>
    <false/>
    <key>sebLocalSettingsEnabled</key>
    <false/>
    <key>allowSwitchToApplications</key>
    <false/>
    <key>enableAppSwitcherCheck</key>
    <true/>
    <key>forceAppFolderInstall</key>
    <true/>
    
    <!-- WINDOWS SEB: EXAM MODE (KEIN ADMIN-PASSWORT) -->
    <key>sebMode</key>
    <integer>1</integer>
    <key>sebConfigPurpose</key>
    <integer>0</integer>
    <key>configPurpose</key>
    <integer>0</integer>
    
    <!-- EXAM MODE SETTINGS -->
    <key>startExamMode</key>
    <true/>
    <key>examMode</key>
    <true/>
    <key>sebConfigurationIsExam</key>
    <true/>
    
    <!-- KEINE ADMIN-RECHTE NÖTIG -->
    <key>allowPreferencesWindow</key>
    <false/>
    <key>sebRequiresAdminRights</key>
    <false/>
    <key>sebConfigurationMustBeUnlocked</key>
    <false/>
    <key>sebLocalSettingsEnabled</key>
    <false/>
    <key>enableLogging</key>
    <false/>
    <key>logLevel</key>
    <integer>0</integer>
    
    <!-- STANDALONE MODUS (KEIN SERVER) -->
    <key>sebMode</key>
    <integer>0</integer>
    
    <!-- EXAM SESSION IDENTIFIER -->
    <key>examKeySalt</key>
    <string>exam-session-' . date('YmdHis') . '</string>
    <key>examKey</key>
    <string>' . hash('sha256', 'exam-' . $testCode . '-' . date('YmdHis')) . '</string>
    
    <!-- BILDSCHIRM-FUNKTIONEN ERLAUBT (MULTI-MONITOR) -->
    <key>allowDisplayMirroring</key>
    <true/>
    <key>allowWlan</key>
    <false/>
    <key>allowWindowCapture</key>
    <false/>
    <key>allowScreenSharing</key>
    <false/>
    <key>allowVirtualMachine</key>
    <false/>
    
    <!-- KIOSK-MODUS ERZWINGEN -->
    <key>touchOptimized</key>
    <true/>
    <key>browserViewMode</key>
    <integer>0</integer>
    <key>mainBrowserWindowWidth</key>
    <string>100%</string>
    <key>mainBrowserWindowHeight</key>
    <string>100%</string>
    
    <!-- KONFIGURATION-VERHALTEN -->
    <key>allowReconfiguration</key>
    <false/>
    
    <!-- KIOSK-MODUS (VOLLSTÄNDIG GESPERRT) -->
    <key>createNewDesktop</key>
    <true/>
    <key>killExplorerShell</key>
    <true/>
    <key>enableKioskMode</key>
    <true/>
    
    <!-- PROZESS-ÜBERWACHUNG (vereinfacht) -->
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
        <dict>
            <key>active</key>
            <true/>
            <key>currentUser</key>
            <true/>
            <key>executable</key>
            <string>cmd.exe</string>
        </dict>
    </array>
    
    <!-- BILDSCHIRM-EINSTELLUNGEN (MULTI-MONITOR ERLAUBT) -->
    <key>allowedDisplaysMaxNumber</key>
    <integer>10</integer>
    <key>allowDisplayMirroring</key>
    <true/>
    <key>allowScreenSharing</key>
    <false/>
    <key>allowVideoCapture</key>
    <false/>
    <key>allowAudioCapture</key>
    <false/>
    
    <!-- IPAD TOUCH/GESTURE EINSTELLUNGEN -->
    <key>enableTouchExit</key>
    <false/>
    <key>touchOptimized</key>
    <true/>
    <key>enableAppSwitcherCheck</key>
    <true/>
    <key>forceAppFolderInstall</key>
    <true/>
    
    <!-- IOS APP-SWITCHER BLOCKIEREN -->
    <key>iOSEnableGuidedAccessLinkTransform</key>
    <true/>
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
    <key>iOSAllowPredictiveText</key>
    <false/>
    
    <!-- IPAD HARDWARE-BUTTONS SPERREN -->
    <key>iOSDisableHomeButton</key>
    <true/>
    <key>iOSDisableVolumeButtons</key>
    <true/>
    <key>iOSDisablePowerButton</key>
    <true/>
    <key>iOSDisableScreenRotation</key>
    <true/>
    
    <!-- GUIDED ACCESS EINSTELLUNGEN (vereinfacht) -->
    <key>iOSEnableGuidedAccessLinkTransform</key>
    <true/>
    
    <!-- Mobile Einstellungen -->
    <key>enableJavaScript</key>
    <true/>
    <key>enableJavaScriptAlert</key>
    <true/>
    <key>enableJavaScriptConfirm</key>
    <true/>
    <key>enableJavaScriptPrompt</key>
    <true/>
    
    <!-- WICHTIGE TASTENSPERREN -->
    <key>enableAltTab</key>
    <false/>
    <key>enableAltF4</key>
    <false/>
    <key>enableStartMenu</key>
    <false/>
    <key>enableRightMouse</key>
    <false/>
    <key>enableTouchExit</key>
    <false/>
    <key>enableEsc</key>
    <false/>
    <key>enableCtrlEsc</key>
    <false/>
    <key>enableAltEsc</key>
    <false/>
    <key>enableF1</key>
    <false/>
    <key>enableF3</key>
    <false/>
    <key>enableF4</key>
    <false/>
    <key>enableF5</key>
    <false/>
    <key>enableF11</key>
    <false/>
    <key>enableF12</key>
    <false/>
    <key>enablePrintScreen</key>
    <false/>
    
    <!-- SYSTEM-SPERREN -->
    <key>detectStoppedProcess</key>
    <true/>
    <key>hookKeys</key>
    <true/>
    
    <!-- Exam-Konfiguration -->
    <key>examKeySalt</key>
    <data>' . base64_encode($testCode . '_' . date('Y-m-d')) . '</data>
    <key>examSessionClearCookiesOnStart</key>
    <true/>
    <key>examSessionClearCookiesOnEnd</key>
    <true/>
    
    <!-- Metadata (korrigiert) -->
    <key>sebConfigPurpose</key>
    <integer>1</integer>
    <key>originatorVersion</key>
    <string>3.4.0</string>
</dict>
</plist>';

// Debug-Logging
error_log("SEB-Config generiert für Test: $testCode, URL: $testUrl");

// Headers für Download setzen
header('Content-Type: application/x-apple-plist');
header('Content-Disposition: attachment; filename="test_' . $testCode . '.seb"');
header('Content-Length: ' . strlen($sebConfig));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Konfiguration ausgeben
echo $sebConfig;
?>
