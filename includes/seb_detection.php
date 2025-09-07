<?php
/**
 * SEB-Erkennungs-Funktionen
 * Erkennt ob die Seite in Safe Exam Browser läuft
 */

/**
 * Erweiterte SEB-Erkennung (nutzt bestehende isSEBBrowser aus seb_functions.php)
 * @return bool True wenn SEB erkannt wird
 */
function isSEBBrowserExtended() {
    // Nutze die bereits vorhandene Basic-Funktion
    if (function_exists('isSEBBrowser') && isSEBBrowser()) {
        return true;
    }
    
    // Erweiterte Erkennungsmethoden:
    
    // Methode 1: SEB-spezifische Header prüfen
    $sebHeaders = [
        'HTTP_X_SAFEEXAMBROWSER_REQUESTHASH',
        'HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH',
        'HTTP_X_SAFEEXAMBROWSER_BROWSERTITLE',
        'HTTP_X_SEB_BROWSER',
        'HTTP_X_SEB_SESSION'
    ];
    
    foreach ($sebHeaders as $header) {
        if (isset($_SERVER[$header])) {
            return true;
        }
    }
    
    // Methode 2: URL-Parameter seb=true prüfen (für Tests)
    if (isset($_GET['seb']) && $_GET['seb'] === 'true') {
        return true;
    }
    
    // Methode 3: Session-Variable prüfen (wenn bereits gesetzt)
    if (isset($_SESSION['is_seb_session']) && $_SESSION['is_seb_session'] === true) {
        return true;
    }
    
    return false;
}

/**
 * Markiert die aktuelle Session als SEB- oder Browser-Session
 * @param bool $isSEB
 */
function markSessionAsSEB($isSEB = null) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if ($isSEB === null) {
        $isSEB = isSEBBrowserExtended();
    }
    
    $_SESSION['is_seb_session'] = $isSEB;
    $_SESSION['session_type'] = $isSEB ? 'SEB' : 'Browser';
    $_SESSION['session_marked_at'] = date('Y-m-d H:i:s');
    
    // Debug-Logging
    error_log("Session markiert als: " . ($isSEB ? 'SEB' : 'Browser') . " - User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
}

/**
 * Generiert SEB-Warnbalken HTML
 * @param string $testCode Optional: Test-Code für direkten SEB-Link
 * @return string HTML für Warnbalken
 */
function getSEBWarningBar($testCode = null) {
    if (isSEBBrowserExtended()) {
        return ''; // Keine Warnung in SEB
    }
    
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    $projectPath = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($projectPath) !== 'mcq-test-system') {
        $projectPath = dirname($projectPath);
    }
    $sebUrl = $baseUrl . $projectPath;
    
    if ($testCode) {
        $sebUrl .= '/seb_start.php?code=' . urlencode($testCode);
    }
    
    $html = '
    <div id="sebWarningBar" style="
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 12px 20px;
        text-align: center;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        z-index: 9999;
        border-bottom: 2px solid #b21e2d;
        font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;
    ">
        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 16px;"></i>
        <strong>⚠️ SICHERHEITSWARNUNG:</strong> 
        Dieser Test sollte im Safe Exam Browser ausgeführt werden!
        <a href="' . htmlspecialchars($sebUrl) . '" 
           style="color: #fff; text-decoration: underline; margin-left: 15px; font-weight: bold;"
           onmouseover="this.style.color=\'#ffeb3b\'" 
           onmouseout="this.style.color=\'#fff\'">
            <i class="bi bi-shield-lock me-1"></i>Zum sicheren SEB-Modus wechseln →
        </a>
    </div>
    <style>
        body { margin-top: 60px !important; }
        .container, .container-fluid { margin-top: 20px; }
        
        @media (max-width: 768px) {
            #sebWarningBar {
                font-size: 12px;
                padding: 10px 15px;
            }
            body { margin-top: 80px !important; }
        }
        
        #sebWarningBar a:hover {
            text-shadow: 0 0 5px rgba(255, 235, 59, 0.5);
        }
    </style>';
    
    return $html;
}

/**
 * Generiert JavaScript für SEB-Warnung
 * @return string JavaScript Code
 */
function getSEBWarningJS() {
    if (isSEBBrowserExtended()) {
        return ''; // Kein JS in SEB nötig
    }
    
    return '
    <script>
        // SEB-Warnung JavaScript
        document.addEventListener("DOMContentLoaded", function() {
            console.log("🛡️ SEB-Warnung geladen: Normaler Browser erkannt");
            
            // Warnung nach 3 Sekunden anzeigen falls noch nicht sichtbar
            setTimeout(function() {
                const warningBar = document.getElementById("sebWarningBar");
                if (warningBar && !warningBar.style.display) {
                    warningBar.style.animation = "slideDown 0.5s ease-out";
                }
            }, 3000);
            
            // Animation für Slide-Down
            const style = document.createElement("style");
            style.textContent = `
                @keyframes slideDown {
                    from { transform: translateY(-100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        });
    </script>';
}
?>
