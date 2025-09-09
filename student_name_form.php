<?php
// Starte Output-Buffering
ob_start();

// Starte Session
session_start();

require_once 'check_test_attempts.php';

// Überprüfe, ob die notwendigen Session-Variablen gesetzt sind
if (!isset($_SESSION["testFile"]) || !isset($_SESSION["testName"]) || !isset($_SESSION["loginTime"])) {
    header("Location: index.php");
    exit;
}

// Überprüfe, ob der Test bereits absolviert wurde
// Der Schülername ist an dieser Stelle noch nicht bekannt, daher nur Cookie-Prüfung
if (hasCompletedTestToday($_SESSION["testName"])) {
    $_SESSION["error"] = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
    if (ob_get_length()) ob_clean();
    header("Location: index.php");
    exit;
}

// Lade Konfiguration - Anpassung, da config.php nicht mehr existiert
// $config = loadConfig();

// Verarbeite POST-Anfrage
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["studentName"])) {
    $studentName = trim(htmlspecialchars($_POST["studentName"]));
    
    if (!empty($studentName)) {
        $_SESSION["studentName"] = $studentName;
        
        // Stelle sicher, dass keine Ausgabe gesendet wurde
        if (ob_get_length()) ob_clean();
        
        header("Location: test.php");
        exit;
    }
}

// Setze Header für keine Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo $_SESSION["testName"]; ?> - Name eingeben</title>
    <link rel="stylesheet" href="./styles.css">
    <style>
        .student-form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #10b981; /* Grün als Akzentfarbe */
        }
        
        .test-info {
            background-color: #e6f7ee; /* Blassgrün als Hintergrund */
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            /* Rahmen entfernt */
            box-shadow: none; /* Kein Schatten */
        }
        
        .testcode-badge {
            background-color: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
            margin-left: 0.5rem;
        }
        
        .student-name-form {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .student-form-title {
            color: #047857;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .name-input {
            border: 2px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .name-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            outline: none;
        }
        
        .name-label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .submit-btn {
            background-color: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #047857;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php echo getTestModeWarning(); ?>
    
    <div class="student-form-container">
        <div class="test-info">
            <h1><?php echo $_SESSION["testName"]; ?></h1>
            <p><strong>Testcode:</strong> <span class="testcode-badge"><?php echo substr($_SESSION["testFile"], strrpos($_SESSION["testFile"], "/") + 1, 3); ?></span></p>
            <p><strong>Testdauer:</strong> <?php echo isset($_SESSION["timeLimit"]) ? $_SESSION["timeLimit"] . " Minuten" : "Unbegrenzt"; ?></p>
        </div>
        
        <div class="student-name-form">
            <h2 class="student-form-title">Teilnehmerdaten</h2>
            <form method="post" id="studentForm">
                <div class="form-group">
                    <label for="studentName" class="name-label">Vor- und Nachname:</label>
                    <input type="text" id="studentName" name="studentName" class="name-input" required 
                           placeholder="Bitte vollständigen Namen eingeben"
                           pattern="[A-Za-zÄäÖöÜüß\s-]+" 
                           title="Bitte geben Sie Ihren vollständigen Namen ein (nur Buchstaben, Leerzeichen und Bindestriche erlaubt)">
                </div>
                <button type="submit" class="submit-btn">Test jetzt starten</button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 1.5rem;">
            <p style="font-size: 0.9rem; color: #6b7280;">
                Mit dem Absenden bestätigen Sie, dass Sie den Test selbständig bearbeiten werden.
            </p>
        </div>
    </div>
    
    <script>
    // Vollbildmodus-Funktionen
    function enterFullscreen() {
        const element = document.documentElement;
        
        try {
            // iOS Safari spezifische Implementierung
            if (element.webkitEnterFullscreen) {
                element.webkitEnterFullscreen();
            } else if (element.requestFullscreen) {
                element.requestFullscreen().catch(err => {
                    console.warn('Vollbildmodus konnte nicht aktiviert werden:', err);
                });
            } else if (element.webkitRequestFullscreen) { // Desktop Safari
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) { // IE11
                element.msRequestFullscreen();
            }
        } catch (error) {
            console.warn('Vollbildmodus-Fehler:', error);
        }
    }

    // Formular-Submit-Event abfangen
    document.getElementById('studentForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Verhindere das Standard-Formular-Submit
        
        // Aktiviere Vollbildmodus
        enterFullscreen();
        
        // Warte kurz und sende dann das Formular ab
        setTimeout(function() {
            document.getElementById('studentForm').submit();
        }, 100);
    });
    </script>
    
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>
</body>
</html>