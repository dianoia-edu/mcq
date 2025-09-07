<?php
/**
 * ULTRA-MINIMALE SEB-Konfiguration
 * Löst NullReferenceException in ServerOperation
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

// ULTRA-MINIMAL SEB-Konfiguration (nur absolut notwendige Settings)
$sebConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    
    <!-- START URL (PFLICHT) -->
    <key>startURL</key>
    <string>' . htmlspecialchars($testUrl) . '</string>
    
    <!-- EXAM MODE (PFLICHT) -->
    <key>sebMode</key>
    <integer>1</integer>
    <key>configPurpose</key>
    <integer>0</integer>
    
    <!-- QUIT SETTINGS (PFLICHT) -->
    <key>allowQuit</key>
    <true/>
    <key>hashedQuitPassword</key>
    <string>' . hash('sha256', 'admin123') . '</string>
    
    <!-- DISABLE ALL OPTIONAL FEATURES -->
    <key>URLFilterEnable</key>
    <false/>
    <key>enableLogging</key>
    <false/>
    <key>showReloadWarning</key>
    <false/>
    <key>showQuitWarning</key>
    <false/>
    <key>sebRequiresAdminRights</key>
    <false/>
    
    <!-- COMPLETE SERVER DISABLE (NO SERVER SETTINGS AT ALL) -->
    
    <!-- BASIC INFO -->
    <!-- BILDSCHIRM-EINSTELLUNGEN (MULTI-MONITOR) -->
    <key>allowedDisplaysMaxNumber</key>
    <integer>10</integer>
    <key>allowDisplayMirroring</key>
    <true/>
    
    <key>originatorName</key>
    <string>MCQ Test - Ultra Minimal</string>
    
</dict>
</plist>';

// Content-Type für .seb Datei setzen
header('Content-Type: application/seb');
header('Content-Disposition: attachment; filename="' . $testCode . '_ultra_minimal.seb"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// SEB-Konfiguration ausgeben
echo $sebConfig;
?>
