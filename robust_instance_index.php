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
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .main-container {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                overflow: hidden;
                max-width: 600px;
                width: 100%;
            }
            .login-header {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                color: white;
                padding: 40px 30px;
                text-align: center;
            }
            .login-header h1 {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 10px;
            }
            .login-body {
                padding: 40px 30px;
            }
            .form-control {
                border-radius: 15px;
                padding: 15px 20px;
                font-size: 1.1rem;
                border: 2px solid #e9ecef;
                transition: all 0.3s ease;
            }
            .form-control:focus {
                border-color: #4facfe;
                box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
            }
            .btn-primary {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                border: none;
                border-radius: 15px;
                padding: 15px 30px;
                font-size: 1.2rem;
                font-weight: 600;
                transition: all 0.3s ease;
                width: 100%;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(79, 172, 254, 0.3);
            }
            .alert {
                border-radius: 15px;
                border: none;
                padding: 15px 20px;
            }
            .access-info {
                background: #f8f9fa;
                border-radius: 15px;
                padding: 25px;
                margin-top: 20px;
                border-left: 5px solid #4facfe;
            }
        </style>
    </head>
    <body>
        <div class="main-container">
            <div class="login-card">
                <div class="login-header">
                    <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                    <p class="mb-0">Geben Sie den Zugangscode ein, um einen Test zu starten</p>
                </div>
                
                <div class="login-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                    
                    <div class="access-info">
                        <h5 class="text-primary mb-3">
                            <i class="bi bi-key-fill me-2"></i>Zugangscode eingeben
                        </h5>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <input type="text" 
                                       class="form-control" 
                                       name="accessCode" 
                                       placeholder="z.B. ABC123"
                                       required 
                                       autocomplete="off"
                                       autofocus>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-play-circle-fill me-2"></i>Test starten
                            </button>
                        </form>
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
