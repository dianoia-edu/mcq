<?php
/**
 * SEB Start-Seite - Weiterleitung zur SEB-Klausurumgebung
 * Diese Datei läuft parallel zum bestehenden System
 */

// Fehlerberichterstattung für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lade bestehende SEB-Funktionen
require_once __DIR__ . '/includes/seb_functions.php';

// Hole Test-Code aus URL
$testCode = $_GET['code'] ?? '';

if (empty($testCode)) {
    die('❌ Fehler: Kein Test-Code angegeben. URL: seb_start.php?code=ABC');
}

// Bestimme Basis-Verzeichnis (wie im teacher_dashboard.php)
$currentDir = dirname(__FILE__);
$isInTeacherDir = (basename($currentDir) === 'teacher');
$baseDir = $isInTeacherDir ? dirname($currentDir) : $currentDir;

// Validiere Test-Code (prüfe ob Test existiert mit Pattern-Matching)
// Erste Variante: Exakter Name
$testFile = $baseDir . '/tests/' . $testCode . '.xml';

// Zweite Variante: Mit Titel-Suffix (z.B. POT_die-potsdamer-konferenz...)
if (!file_exists($testFile)) {
    $testPattern = $baseDir . '/tests/' . $testCode . '_*.xml';
    $matchingFiles = glob($testPattern);
    
    if (!empty($matchingFiles)) {
        $testFile = $matchingFiles[0]; // Nimm die erste gefundene Datei
        error_log("SEB Start: Test gefunden mit Pattern: " . $testFile);
    }
}

error_log("SEB Start: Finale Test-Datei: " . $testFile);

if (!file_exists($testFile)) {
    // Debug: Zeige verfügbare Tests
    $availableTests = glob($baseDir . '/tests/*.xml');
    $testCodes = [];
    foreach ($availableTests as $file) {
        $filename = basename($file, '.xml');
        $code = explode('_', $filename)[0]; // Extrahiere Code vor dem ersten _
        $testCodes[] = $code;
    }
    
    error_log("SEB Start: Verfügbare Test-Codes: " . implode(', ', array_unique($testCodes)));
    
    die('❌ Fehler: Test "' . htmlspecialchars($testCode) . '" nicht gefunden.<br>' .
        'Gesuchte Datei: ' . htmlspecialchars($testFile) . '<br>' .
        'Verfügbare Test-Codes: ' . implode(', ', array_unique($testCodes)) . '<br>' .
        'Verfügbare Dateien: ' . count($availableTests) . ' Tests im Verzeichnis');
}

// Debug-Logging
error_log("SEB Start: Test-Code = $testCode, User-Agent = " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

// Prüfe ob bereits SEB-Browser (erweiterte Erkennung)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isSEB = (strpos($userAgent, 'SEB') !== false || 
          strpos($userAgent, 'SafeExamBrowser') !== false ||
          strpos($userAgent, 'SEB_iOS') !== false ||
          strpos($userAgent, 'SEB/') !== false ||
          strpos($userAgent, 'SEB ') !== false ||
          isset($_GET['seb']) ||
          isset($_POST['seb']));

if ($isSEB) {
    // Bereits im SEB - direkt zur Namenseingabe weiterleiten
    $redirectUrl = 'name_form.php?code=' . urlencode($testCode) . '&seb=true';
    header('Location: ' . $redirectUrl);
    error_log("SEB Start: Bereits im SEB, weiterleiten zu: $redirectUrl");
    exit;
}

// Nicht im SEB - SEB-Konfiguration generieren und starten
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Start für Test <?php echo htmlspecialchars($testCode); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .seb-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .seb-icon {
            font-size: 4rem;
            color: #ff6b35;
            margin-bottom: 1.5rem;
        }
        .btn-seb {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
        }
        .btn-seb:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 53, 0.3);
            color: white;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #ff6b35;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 5px;
            text-align: left;
        }
        .debug-info {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
            margin-top: 2rem;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="seb-card">
        <i class="bi bi-shield-lock seb-icon"></i>
        <h1 class="h3 mb-3">Safe Exam Browser</h1>
        <h2 class="h5 text-muted mb-4">Test: <?php echo htmlspecialchars($testCode); ?></h2>
        
        <div class="info-box">
            <h6><i class="bi bi-info-circle me-2"></i>
                <?php if (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'iPad') !== false): ?>
                    🍎 iPad-Klausurumgebung starten
                <?php else: ?>
                    Klausurumgebung starten
                <?php endif; ?>
            </h6>
            <p class="mb-0">
                <?php if (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'iPad') !== false): ?>
                    <strong>iPad-Hinweis:</strong> Der Test wird in der SEB-App geöffnet. 
                    Nach dem Start ist das Verlassen der App gesperrt.
                    <br><br>
                    <small>⚠️ Home-Button, App-Switcher und Kontrollzentrum werden deaktiviert.</small>
                <?php else: ?>
                    Der Test wird in der sicheren SEB-Umgebung geöffnet. 
                    Bitte wählen Sie eine der folgenden Optionen:
                <?php endif; ?>
            </p>
        </div>
        
        <div class="d-grid gap-3">
            <!-- Automatischer SEB-Start (für mobile Geräte) -->
            <a href="javascript:void(0)" id="autoStartBtn" class="btn-seb">
                <i class="bi bi-play-fill me-2"></i>
                Automatisch starten
            </a>
            
            <!-- SEB-Config Download -->
            <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn-seb">
                <i class="bi bi-download me-2"></i>
                SEB-Konfiguration herunterladen
            </a>
            
            <!-- Direkter Test-Start (Fallback) -->
            <a href="name_form.php?code=<?php echo urlencode($testCode); ?>&seb=manual" class="btn btn-outline-primary">
                <i class="bi bi-arrow-right me-2"></i>
                Direkt zum Test (ohne SEB)
            </a>
        </div>
        
        <div class="debug-info">
            <strong>Debug-Info:</strong><br>
            Test-Code: <?php echo htmlspecialchars($testCode); ?><br>
            SEB erkannt: <?php echo $isSEB ? 'Ja' : 'Nein'; ?><br>
            User-Agent: <?php echo htmlspecialchars(substr($userAgent, 0, 100)); ?>...
        </div>
    </div>

    <script>
        // Automatischer SEB-Start
        document.getElementById('autoStartBtn').addEventListener('click', function() {
            console.log('🔒 Starte SEB automatisch für Test: <?php echo $testCode; ?>');
            
            // Versuche verschiedene SEB-Start-Methoden
            const testCode = '<?php echo $testCode; ?>';
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
            
            // Methode 1: SEB-URL-Scheme (iOS/Android)
            const sebUrl = 'seb://start?url=' + encodeURIComponent(baseUrl + 'name_form.php?code=' + testCode + '&seb=true');
            console.log('🔒 SEB-URL:', sebUrl);
            
            // Methode 2: SEB-Config-URL
            const configUrl = baseUrl + 'seb_config.php?code=' + testCode;
            
            // Versuche SEB-Start
            try {
                window.location.href = sebUrl;
                
                // Fallback nach 3 Sekunden
                setTimeout(function() {
                    console.log('🔒 Fallback zu Config-Download');
                    window.location.href = configUrl;
                }, 3000);
                
            } catch (e) {
                console.error('🔒 SEB-Start fehlgeschlagen:', e);
                window.location.href = configUrl;
            }
        });
        
        // Debug-Output
        console.log('🔒 SEB-Start-Seite geladen');
        console.log('📱 User-Agent:', navigator.userAgent);
        console.log('🌐 URL:', window.location.href);
    </script>
</body>
</html>
