<?php
// Starte Output-Buffering
ob_start();

// Starte Session
session_start();

require_once 'check_test_attempts.php';
require_once 'config.php';

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

// Lade Konfiguration
$config = loadConfig();

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
</head>
<body>
    <?php echo getTestModeWarning(); ?>
    <div class="container">
        <h1><?php echo $_SESSION["testName"]; ?></h1>
        <div class="login-form">
            <h2>Bitte geben Sie Ihren Namen ein</h2>
            <form method="post">
                <div class="form-group">
                    <label for="studentName">Vollständiger Name:</label>
                    <input type="text" id="studentName" name="studentName" required 
                           placeholder="Vor- und Nachname"
                           pattern="[A-Za-zÄäÖöÜüß\s-]+" 
                           title="Bitte geben Sie Ihren vollständigen Namen ein (nur Buchstaben, Leerzeichen und Bindestriche erlaubt)">
                </div>
                <button type="submit" class="btn primary-btn">Test starten</button>
            </form>
        </div>
    </div>
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>
</body>
</html>