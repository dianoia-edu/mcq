<?php
/**
 * Direkter SEB-Quit ohne HTML-Umwege
 * Laut SEB-Dokumentation: "Place a quit link on the feedback page"
 */

// Setze Headers für direkten Redirect zu seb://quit
header('Location: seb://quit');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Zusätzliche SEB-spezifische Header
header('X-SEB-Quit: true');
header('X-SEB-Exit: immediate');

// Debug-Logging
error_log("SEB Quit Direct: Redirect zu seb://quit gesendet");

// Falls der Header-Redirect nicht funktioniert (sollte aber), zeige minimale HTML-Seite
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SEB Quit</title>
    <meta http-equiv="refresh" content="0;url=seb://quit">
</head>
<body>
    <script>
        // Sofortiger JavaScript-Redirect
        window.location.href = 'seb://quit';
    </script>
    
    <!-- Fallback-Link falls alles andere fehlschlägt -->
    <a href="seb://quit" style="display: block; text-align: center; margin-top: 50px; font-size: 20px; color: blue;">
        SEB beenden
    </a>
</body>
</html>
