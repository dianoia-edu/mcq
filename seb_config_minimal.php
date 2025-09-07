<?php
/**
 * MINIMALE SEB-Konfiguration
 * Löst "Konnte keine neue Sitzung starten" Fehler
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

// ABSOLUT MINIMALE SEB-Konfiguration
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    
    <!-- ========================================== -->
    <!-- BASIC EXAM CONFIGURATION                  -->
    <!-- ========================================== -->
    
    <!-- START URL -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- EXAM MODE (SIMPLE) -->
    <key>sebMode</key>
    <integer>1</integer>
    <key>configPurpose</key>
    <integer>0</integer>
    
    <!-- ========================================== -->
    <!-- MINIMAL SECURITY                          -->
    <!-- ========================================== -->
    
    <!-- ALLOW QUIT -->
    <key>allowQuit</key>
    <true/>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- NO URL FILTER -->
    <key>URLFilterEnable</key>
    <false/>
    
    <!-- BROWSER BASICS -->
    <key>browserWindowAllowReload</key>
    <false/>
    <key>browserWindowShowURL</key>
    <false/>
    
    <!-- ========================================== -->
    <!-- WINDOWS COMPATIBILITY                     -->
    <!-- ========================================== -->
    
    <!-- NO ADMIN RIGHTS -->
    <key>sebRequiresAdminRights</key>
    <false/>
    <key>sebLocalSettingsEnabled</key>
    <false/>
    
    <!-- ========================================== -->
    <!-- SERVER SETTINGS (DISABLE ALL)             -->
    <!-- ========================================== -->
    
    <!-- NO SEB SERVER -->
    <key>sebServerConfiguration</key>
    <string></string>
    <key>sebServerURL</key>
    <string></string>
    <key>sebServerFallback</key>
    <false/>
    <key>sebServerFallbackAttemptInterval</key>
    <integer>0</integer>
    <key>sebServerFallbackAttempts</key>
    <integer>0</integer>
    <key>sebServerFallbackPasswordHash</key>
    <string></string>
    <key>sebServerFallbackTimeout</key>
    <integer>0</integer>
    
    <!-- NO LOGGING -->
    <key>enableLogging</key>
    <false/>
    <key>logLevel</key>
    <integer>0</integer>
    
    <!-- NO WARNINGS -->
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    
    <!-- SIMPLE RECONFIGURATION -->
    <key>allowReconfiguration</key>
    <true/>
    <key>forceReconfiguration</key>
    <false/>
    
    <!-- ========================================== -->
    <!-- METADATA                                  -->
    <!-- ========================================== -->
    
    <key>originatorName</key>
    <string>MCQ Test System - Minimal Config</string>
    
</dict>
</plist>';

// Content-Type für .seb Datei setzen
header('Content-Type: application/seb');
header('Content-Disposition: attachment; filename="' . $testCode . '_minimal.seb"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// SEB-Konfiguration ausgeben
echo $sebConfig;
?>
