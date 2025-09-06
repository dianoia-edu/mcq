<?php
/**
 * SEB-Konfiguration Tester
 * Erstellt eine minimal-funktionale .seb-Datei zum Testen
 */

$testCode = $_GET['code'] ?? 'TEST';

// Minimal-SEB-Konfiguration (nur das Nötigste)
$minimalConfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <!-- Basis-URL -->
    <key>startURL</key>
    <string>https://dianoia-ai.de/mcq-test-system/name_form.php?code=' . htmlspecialchars($testCode) . '&seb=true</string>
    
    <!-- URL-Filter -->
    <key>URLFilterEnable</key>
    <true/>
    <key>URLFilterRules</key>
    <array>
        <dict>
            <key>action</key>
            <integer>1</integer>
            <key>active</key>
            <true/>
            <key>expression</key>
            <string>https://dianoia-ai.de/.*</string>
            <key>regex</key>
            <true/>
        </dict>
    </array>
    
    <!-- Basis-Sicherheit -->
    <key>allowQuit</key>
    <false/>
    <key>allowSwitchToApplications</key>
    <false/>
    <key>enableAppSwitcherCheck</key>
    <true/>
    
    <!-- Browser-Einstellungen -->
    <key>browserWindowAllowReload</key>
    <false/>
    <key>browserWindowShowURL</key>
    <false/>
    <key>enableJavaScript</key>
    <true/>
    
    <!-- Minimal-Metadata -->
    <key>originatorVersion</key>
    <string>3.4.0</string>
</dict>
</plist>';

// Headers für Download
header('Content-Type: application/x-apple-plist');
header('Content-Disposition: attachment; filename="test_' . $testCode . '_minimal.seb"');
header('Content-Length: ' . strlen($minimalConfig));
header('Cache-Control: no-cache, must-revalidate');

// Ausgabe
echo $minimalConfig;
?>
