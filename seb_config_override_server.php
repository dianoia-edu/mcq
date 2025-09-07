<?php
/**
 * SEB-Konfiguration MIT EXPLIZITER SERVER-ÜBERSCHREIBUNG
 * Überschreibt alle Server-Einstellungen aus SebClientSettings.seb
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

// SEB-Konfiguration MIT EXPLIZITER SERVER-ÜBERSCHREIBUNG
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    
    <!-- ===== BASIC EXAM SETTINGS ===== -->
    
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <key>sebMode</key>
    <integer>1</integer>
    
    <key>configPurpose</key>
    <integer>0</integer>
    
    <!-- ===== SERVER KOMPLETT DEAKTIVIERT ===== -->
    
    <key>sebServerEnabled</key>
    <false/>
    
    <key>sebServicePolicy</key>
    <integer>0</integer>
    
    <key>allowReconfiguration</key>
    <true/>
    
    <!-- ===== QUIT SETTINGS ===== -->
    
    <key>allowQuit</key>
    <true/>
    
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- ===== MINIMAL BROWSER RESTRICTIONS ===== -->
    
    <key>URLFilterEnable</key>
    <false/>
    
    <key>browserWindowAllowReload</key>
    <false/>
    
    <key>browserWindowShowURL</key>
    <false/>
    
    <!-- ===== DISABLE ALL OPTIONAL FEATURES ===== -->
    
    <key>enableLogging</key>
    <false/>
    
    <key>showReloadWarning</key>
    <false/>
    
    <key>showQuitWarning</key>
    <false/>
    
    <key>sebRequiresAdminRights</key>
    <false/>
    
    <key>allowReconfiguration</key>
    <true/>
    
    <key>forceReconfiguration</key>
    <false/>
    
    <!-- ===== BILDSCHIRM-EINSTELLUNGEN (MULTI-MONITOR) ===== -->
    
    <key>allowedDisplaysMaxNumber</key>
    <integer>10</integer>
    
    <key>allowDisplayMirroring</key>
    <true/>
    
    <!-- ===== METADATA ===== -->
    
    <key>originatorName</key>
    <string>MCQ Test - Server Disabled</string>
    
</dict>
</plist>';

// Content-Type für .seb Datei setzen
header('Content-Type: application/seb');
header('Content-Disposition: attachment; filename="' . $testCode . '_server_disabled.seb"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// SEB-Konfiguration ausgeben
echo $sebConfig;
?>
