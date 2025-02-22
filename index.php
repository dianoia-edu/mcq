<?php
// Starte Output-Buffering
ob_start();

// Starte Session
session_start();

require_once 'check_test_attempts.php';
require_once 'config.php';

// Lösche Session bei GET-Anfragen ohne POST-Daten
if ($_SERVER["REQUEST_METHOD"] === "GET" && empty($_POST)) {
    session_destroy();
    session_start();
}

// Überprüfe POST-Anfrage
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["accessCode"])) {
        $accessCode = trim($_POST["accessCode"]);
        
        // Prüfe zuerst auf Lehrer-Login
        if ($accessCode === "admin123") {
            $_SESSION["teacher"] = true;
            header("Location: teacher_dashboard.php");
            exit();
        }
        
        // Test-Zugangscode prüfen
        $testFiles = glob("tests/*.txt");
        foreach ($testFiles as $testFile) {
            $lines = file($testFile, FILE_IGNORE_NEW_LINES);
            if ($lines && trim($lines[0]) === $accessCode) {
                // Prüfe ob der Test bereits absolviert wurde
                if (hasCompletedTestToday(trim($lines[1]))) {
                    $errorMessage = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
                    break;
                }
                
                // Setze neue Session-Variablen
                $_SESSION = array();
                $_SESSION["testFile"] = $testFile;
                $_SESSION["testName"] = trim($lines[1]);
                $_SESSION["accessCode"] = $accessCode;
                $_SESSION["loginTime"] = time();
                
                // Stelle sicher, dass keine Ausgabe gesendet wurde
                if (ob_get_length()) ob_clean();
                
                header("Location: student_name_form.php");
                exit();
            }
        }
        
        if (!isset($errorMessage)) {
            $errorMessage = "Ungültiger Zugangscode";
        }
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
    <title>MCQ Test System - Login</title>
    <style>
        /* Grundlegende Stile inline, falls externes CSS nicht lädt */
        :root {
            --primary-color: #2563eb;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-primary: #1f2937;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .login-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }

        h1, h2 {
            color: var(--text-primary);
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            color: white;
            background-color: var(--primary-color);
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .error-message {
            color: #dc2626;
            background-color: #fee2e2;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .test-mode-warning {
            background-color: #ff4444;
            color: white;
            padding: 1rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            font-weight: bold;
        }
    </style>
    <link rel="stylesheet" href="./styles.css">
</head>
<body>
    <?php echo getTestModeWarning(); ?>
    
    <div class="container">
        <h1>MCQ Test System - Login</h1>
        
        <?php if (isset($_SESSION["error"])): ?>
            <div class="error-message"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php elseif (isset($errorMessage)): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <div class="login-form">
            <h2>Zugang zum Test</h2>
            <form method="post">
                <div class="form-group">
                    <label for="accessCode">Zugangscode:</label>
                    <input type="text" name="accessCode" id="accessCode" required>
                </div>
                <div>
                    <button type="submit" class="btn primary-btn">Test starten</button>
                </div>
            </form>
        </div>
    </div>
    <div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
        <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
    </div>
</body>
</html>