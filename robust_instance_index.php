<?php
// Robuste index.php für Instanzen mit allen wichtigen Features
ob_start();
session_start();

// Sichere Einbindung der Funktionen mit Fallbacks
function safeInclude($file, $required = false) {
    if (file_exists($file)) {
        return include_once $file;
    } elseif ($required) {
        die("Erforderliche Datei nicht gefunden: $file");
    }
    return false;
}

// Versuche wichtige Dateien einzubinden
safeInclude('check_test_attempts.php');
safeInclude('includes/seb_functions.php');

// Fallback-Funktionen falls Dateien fehlen
if (!function_exists('hasCompletedTestToday')) {
    function hasCompletedTestToday($code, $studentName = null) {
        // Einfache Cookie-basierte Prüfung als Fallback
        $cookieName = 'test_completed_' . md5($code . date('Y-m-d'));
        return isset($_COOKIE[$cookieName]);
    }
}

if (!function_exists('isSEBBrowser')) {
    function isSEBBrowser() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return (strpos($userAgent, 'SEB') !== false);
    }
}

if (!function_exists('getBaseCode')) {
    function getBaseCode($code) {
        // Extrahiere die ersten 3 Zeichen als Basis-Code
        return strtoupper(substr(trim($code), 0, 3));
    }
}

// Lade Instanz-spezifische Konfiguration
function loadInstanceConfig() {
    $configPath = __DIR__ . '/config/app_config.json';
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        return $config ?: [];
    }
    return [];
}

// Fehler-Variable
$error = '';

// Lösche die Testergebnisse nach der ersten Anzeige
if (isset($_SESSION['test_results']) && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST)) {
    $temp_results = $_SESSION['test_results'];
    unset($_SESSION['test_results']);
    $_SESSION['show_results_once'] = $temp_results;
}

// POST-Verarbeitung für Zugangscode
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accessCode"])) {
    session_destroy();
    session_start();
    
    $accessCode = trim($_POST["accessCode"]);
    
    if (!empty($accessCode)) {
        // Lade Instanz-Konfiguration
        $config = loadInstanceConfig();
        $adminCode = $config['admin_access_code'] ?? '';
        
        // Prüfe auf Admin-Login
        if (!empty($adminCode) && $accessCode === $adminCode) {
            $_SESSION["teacher"] = true;
            header("Location: teacher/teacher_dashboard.php");
            exit();
        }
        
        // Fallback auf Standard-Admin-Code
        if ($accessCode === "admin123") {
            $_SESSION["teacher"] = true;
            header("Location: teacher/teacher_dashboard.php");
            exit();
        }
        
        // Prüfe auf Test bereits heute absolviert
        if (hasCompletedTestToday($accessCode)) {
            $error = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
        } else {
            // Test-Code prüfen
            $baseCode = getBaseCode($accessCode);
            $allFiles = glob("tests/*.xml");
            $testFiles = array_filter($allFiles, function($file) use ($baseCode) {
                $filename = basename($file);
                $fileCode = substr($filename, 0, 3);
                return (strtoupper($fileCode) === $baseCode);
            });
            
            if (!empty($testFiles)) {
                header("Location: index.php?code=" . urlencode($accessCode));
                exit();
            } else {
                $error = "Der eingegebene Zugangscode ist ungültig. Bitte überprüfen Sie Ihre Eingabe.";
            }
        }
    } else {
        $error = "Bitte geben Sie einen Zugangscode ein.";
    }
}

// Funktion zum Überprüfen, ob ein Testcode existiert
function testExists($code) {
    $baseCode = getBaseCode($code);
    $allFiles = glob("tests/*.xml");
    $matchingFiles = array_filter($allFiles, function($file) use ($baseCode) {
        $filename = basename($file);
        $fileCode = substr($filename, 0, 3);
        return (strtoupper($fileCode) === $baseCode);
    });
    return !empty($matchingFiles);
}

// SEB-Parameter Verarbeitung
if (isset($_GET['seb']) && $_GET['seb'] === 'true') {
    if (!isset($_GET['student_name'])) {
        include 'name_form.php';
        exit;
    }
    
    if (isSEBBrowser()) {
        $code = $_GET['code'];
    } else {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ((strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false)) {
            echo '<script>';
            echo 'setTimeout(function() {';
            echo '  window.location = "seb://start?url=' . urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/lehrer_instanzen/' . basename(dirname(__FILE__)) . '/mcq-test-system/index.php?code=' . urlencode($_GET['code']) . '&seb=true') . '";';
            echo '}, 500);';
            echo '</script>';
            echo '<div class="alert alert-info mt-3">Wenn sich der Safe Exam Browser nicht öffnet, können Sie den Test auch im normalen Browser durchführen.</div>';
        }
        $code = $_GET['code'];
    }
}

// Session setzen bei GET mit student_name und code
if (isset($_GET['student_name']) && isset($_GET['code'])) {
    $_SESSION['student_name'] = $_GET['student_name'];
    $_SESSION['test_code'] = $_GET['code'];
}

// Teststart bei GET mit code und student_name
if (isset($_GET['code']) && isset($_GET['student_name'])) {
    $code = $_GET['code'];
    $studentName = $_GET['student_name'];
    $_SESSION['student_name'] = $studentName;
    $_SESSION['test_code'] = $code;
    $baseCode = getBaseCode($code);
    $allFiles = glob("tests/*.xml");
    $testFiles = array_filter($allFiles, function($file) use ($baseCode) {
        $filename = basename($file);
        $fileCode = substr($filename, 0, 3);
        return (strtoupper($fileCode) === $baseCode);
    });
    $testFile = !empty($testFiles) ? reset($testFiles) : null;
    if ($testFile) {
        $_SESSION['test_file'] = $testFile;
        if (file_exists('test.php')) {
            ob_start();
            include 'test.php';
            $output = ob_get_clean();
            if (empty(trim($output))) {
                echo '<div class="container mt-5"><div class="alert alert-danger">Fehler beim Laden des Tests.</div></div>';
            } else {
                echo $output;
            }
        } else {
            echo '<div class="container mt-5"><div class="alert alert-danger">Test-Engine nicht gefunden.</div></div>';
        }
        exit;
    } else {
        echo '<div class="container mt-5"><div class="alert alert-danger">Ungültiger Testcode.</div></div>';
        exit;
    }
}

// Code-Verarbeitung bei GET
if (isset($_GET['code'])) {
    if (!isset($_POST['student_name'])) {
        session_destroy();
        session_start();
    }
    
    $code = $_GET['code'];
    
    if (testExists($code)) {
        if (!isset($_SESSION['student_name'])) {
            // Namenseingabe-Formular anzeigen
            $baseCode = getBaseCode($code);
            $allFiles = glob("tests/*.xml");
            $testFiles = array_filter($allFiles, function($file) use ($baseCode) {
                $filename = basename($file);
                $fileCode = substr($filename, 0, 3);
                return (strtoupper($fileCode) === $baseCode);
            });
            
            $testFile = !empty($testFiles) ? reset($testFiles) : null;
            $testTitle = "Test";
            
            if ($testFile) {
                try {
                    $xml = simplexml_load_file($testFile);
                    if ($xml !== false && isset($xml->title)) {
                        $testTitle = (string)$xml->title;
                    }
                } catch (Exception $e) {
                    // Fehler beim XML-Lesen ignorieren
                }
            }
            
            // Namenseingabe-HTML (kompakte Version)
            ?>
            <!DOCTYPE html>
            <html lang="de">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Namenseingabe - <?php echo htmlspecialchars($testTitle); ?></title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="mb-0">Test: <?php echo htmlspecialchars($testTitle); ?></h3>
                                    <p class="mb-0">Code: <strong><?php echo htmlspecialchars($code); ?></strong></p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="student_name" class="form-label">Vor- und Nachname:</label>
                                        <input type="text" class="form-control" id="student_name" placeholder="Vollständigen Namen eingeben" required>
                                    </div>
                                    <input type="hidden" id="test_code" value="<?php echo htmlspecialchars($code); ?>">
                                    <div class="d-grid gap-2">
                                        <button id="browserBtn" class="btn btn-primary">Test im Browser starten</button>
                                        <button id="sebBtn" class="btn btn-success">Test im Safe Exam Browser starten</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                document.getElementById('browserBtn').onclick = function() {
                    var name = encodeURIComponent(document.getElementById('student_name').value);
                    var code = encodeURIComponent(document.getElementById('test_code').value);
                    if (!name) { alert('Bitte geben Sie Ihren Namen ein.'); return; }
                    window.location.href = 'index.php?code=' + code + '&student_name=' + name;
                };
                document.getElementById('sebBtn').onclick = function() {
                    var name = encodeURIComponent(document.getElementById('student_name').value);
                    var code = encodeURIComponent(document.getElementById('test_code').value);
                    if (!name) { alert('Bitte geben Sie Ihren Namen ein.'); return; }
                    var url = '<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>';
                    url = url.split('?')[0] + '?code=' + code + '&seb=true&student_name=' + name;
                    window.location.href = 'seb://start?url=' + encodeURIComponent(url);
                };
                </script>
            </body>
            </html>
            <?php
        } else {
            // Test anzeigen
            $_SESSION['test_code'] = $code;
            $baseCode = getBaseCode($code);
            $allFiles = glob("tests/*.xml");
            $testFiles = array_filter($allFiles, function($file) use ($baseCode) {
                $filename = basename($file);
                $fileCode = substr($filename, 0, 3);
                return (strtoupper($fileCode) === $baseCode);
            });
            
            $_SESSION['test_file'] = !empty($testFiles) ? reset($testFiles) : null;
            
            if ($_SESSION['test_file'] && file_exists('test.php')) {
                ob_start();
                include 'test.php';
                $output = ob_get_clean();
                
                if (empty(trim($output))) {
                    echo '<div class="container mt-5"><div class="alert alert-danger">Fehler beim Laden des Tests.</div></div>';
                } else {
                    echo $output;
                }
            } else {
                echo '<div class="container mt-5"><div class="alert alert-danger">Test nicht gefunden.</div></div>';
            }
        }
    } else {
        // Test existiert nicht - Startseite mit Fehler
        $error = "Der eingegebene Zugangscode ist ungültig.";
    }
}
// POST-Verarbeitung für Namenseingabe
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name']) && isset($_POST['code'])) {
    $code = $_POST['code'];
    $baseCode = getBaseCode($code);
    $allFiles = glob("tests/*.xml");
    $testFiles = array_filter($allFiles, function($file) use ($baseCode) {
        $filename = basename($file);
        $fileCode = substr($filename, 0, 3);
        return (strtoupper($fileCode) === $baseCode);
    });
    
    $testFile = !empty($testFiles) ? reset($testFiles) : null;
    
    if ($testFile) {
        // Prüfe Test bereits heute absolviert (mit Schülername)
        if (hasCompletedTestToday($code, $_POST['student_name'])) {
            session_destroy();
            session_start();
            $_SESSION['error_message'] = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
            header("Location: index.php");
            exit();
        }
        
        $_SESSION['student_name'] = $_POST['student_name'];
        $_SESSION['test_code'] = $code;
        $_SESSION['test_file'] = $testFile;
        
        // Zwei-Button-Auswahl für Browser/SEB
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $sebUrl = 'seb://start?url=' . urlencode($baseUrl . '/index.php?code=' . urlencode($code) . '&seb=true&student_name=' . urlencode($_POST['student_name']));
        echo '<div class="container mt-5 text-center">';
        echo '<h2>Test starten</h2>';
        echo '<a href="index.php?code=' . urlencode($code) . '&student_name=' . urlencode($_POST['student_name']) . '" class="btn btn-primary btn-lg me-2">Test im Browser starten</a>';
        echo '<a href="' . htmlspecialchars($sebUrl) . '" class="btn btn-success btn-lg">Test im Safe Exam Browser starten</a>';
        echo '<div class="mt-4 text-muted">Sollte sich der Safe Exam Browser nicht öffnen, können Sie den Test auch im Browser durchführen.</div>';
        echo '</div>';
        exit;
    } else {
        $error = "Ungültiger Testcode";
    }
}

// Startseite anzeigen (wenn kein Code)
if (!isset($_GET['code'])) {
    $config = loadInstanceConfig();
    $schoolName = $config['schoolName'] ?? 'Online-Test-System';
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($schoolName); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            .card {
                border-radius: 15px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .card-access {
                border-left: 6px solid #0d6efd;
                background: linear-gradient(to right, #e6efff, #ffffff);
            }
            .form-control-large {
                height: 60px;
                font-size: 1.2rem;
                text-align: center;
                letter-spacing: 3px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .btn-access {
                height: 60px;
                font-size: 1.2rem;
                font-weight: bold;
            }
            .qr-container {
                text-align: center;
                margin-top: 40px;
            }
            .qr-code {
                max-width: 200px;
                margin: 0 auto;
            }
            .qr-text {
                margin-top: 15px;
                font-size: 0.9rem;
                color: #6c757d;
            }
        </style>
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center mb-4">
                    <h1 class="display-4 fw-bold text-primary"><?php echo htmlspecialchars($schoolName); ?></h1>
                    <p class="lead">Geben Sie den Zugangscode ein, um einen Test zu starten</p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger mb-4">
                            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="card card-access mb-4">
                        <div class="card-body p-5">
                            <h2 class="card-title text-primary mb-4">Zugangscode eingeben</h2>
                            <form action="index.php" method="POST">
                                <div class="mb-4">
                                    <input type="text" class="form-control form-control-large" id="accessCode" name="accessCode" 
                                        placeholder="z.B. ABC123" required
                                        autocomplete="off">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-access">
                                        <i class="bi bi-arrow-right-circle me-2"></i>Test starten
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR-Code Bereich -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="qr-container">
                        <h3 class="text-primary mb-3">Zugang via QR-Code</h3>
                        <div class="qr-code">
                            <?php
                            // QR-Code mit dem installierten phpqrcode-Modul erstellen
                            if (file_exists('includes/phpqrcode/qrlib.php')) {
                                require_once('includes/phpqrcode/qrlib.php');
                                
                                // Aktuelle URL für den QR-Code ermitteln
                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                                $host = $_SERVER['HTTP_HOST'];
                                $currentPath = $_SERVER['REQUEST_URI'];
                                $baseUrl = $protocol . $host . dirname($currentPath);
                                $qrCodeUrl = $baseUrl . "/index.php";
                                
                                // Temporäres Verzeichnis für QR-Code-Dateien erstellen, falls nicht vorhanden
                                $qrCodeDir = __DIR__ . '/temp_qrcodes';
                                if (!is_dir($qrCodeDir)) {
                                    mkdir($qrCodeDir, 0777, true);
                                }
                                
                                // QR-Code-Dateiname generieren
                                $qrCodeFile = $qrCodeDir . '/qrcode_' . md5($qrCodeUrl) . '.png';
                                $qrCodeWebPath = 'temp_qrcodes/qrcode_' . md5($qrCodeUrl) . '.png';
                                
                                // Erzeuge den QR-Code in blauer Farbe
                                QRcode::png($qrCodeUrl, $qrCodeFile, QR_ECLEVEL_M, 8, 2, false, 0x0000FF);
                                ?>
                                <img src="<?php echo $qrCodeWebPath; ?>" alt="QR-Code für Testzugang" class="img-fluid">
                                <?php
                            } else {
                                // Fallback wenn QR-Library nicht verfügbar
                                echo '<div class="alert alert-info">QR-Code-Generator nicht verfügbar</div>';
                            }
                            ?>
                        </div>
                        <p class="qr-text">Scannen Sie diesen QR-Code, um direkt zum Test-System zu gelangen</p>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

// Cache-Control Header
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
