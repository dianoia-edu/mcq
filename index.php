<?php
// Starte Output-Buffering
ob_start();

// Starte    Session 
session_start();

require_once 'check_test_attempts.php';
require_once 'includes/seb_functions.php';

// Debug-Informationen für alle Anfragen
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));
error_log("GET Data: " . print_r($_GET, true));
error_log("Session Data: " . print_r($_SESSION, true));

// Lösche die Testergebnisse nach der ersten Anzeige
if (isset($_SESSION['test_results']) && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST)) {
    $temp_results = $_SESSION['test_results'];
    unset($_SESSION['test_results']);
    $_SESSION['show_results_once'] = $temp_results;
}

// Überprüfe POST-Anfrage
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["accessCode"])) {
        // Lösche die alte Session beim Start eines neuen Tests
        session_destroy();
        session_start();
        
        $accessCode = trim($_POST["accessCode"]);
        
        // Prüfe zuerst auf Lehrer-Login
        if ($accessCode === "admin123") {
            $_SESSION["teacher"] = true;
            header("Location: teacher/teacher_dashboard.php");
            exit();
        }
        
        // Extrahiere den Basis-Code für die Dateisuche
        $baseCode = getBaseCode($accessCode);
        
        // Prüfe, ob der Test bereits heute absolviert wurde
        // Der Schülername ist an dieser Stelle noch nicht bekannt, daher nur Cookie-Prüfung
        if (hasCompletedTestToday($accessCode)) {
            $errorMessage = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
            $errorType = "warning";
        }
        
        // Test-Zugangscode prüfen
        $searchCode = $baseCode;
        // Finde die Testdatei anhand der ersten 3 Zeichen des Dateinamens
        $allFiles = glob("tests/*.xml");
        $testFiles = array_filter($allFiles, function($file) use ($searchCode) {
            $filename = basename($file);
            $fileCode = substr($filename, 0, 3);
            return ($fileCode === $searchCode);
        });
        
        if (!empty($testFiles)) {
            // Test existiert
            header("Location: index.php?code=" . urlencode($accessCode));
            exit();
        } else {
            $errorMessage = "Der eingegebene Zugangscode ist ungültig. Bitte überprüfen Sie Ihre Eingabe.";
            $errorType = "danger";
        }
    }
}

// Überprüfe, ob SEB-Parameter vorhanden ist
if (isset($_GET['seb']) && $_GET['seb'] === 'true') {
    error_log("SEB Parameter gefunden - Code: " . $_GET['code']);
    error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Nicht gesetzt'));
    
    if (isSEBBrowser()) {
        error_log("SEB Browser erkannt - Starte normalen Testablauf");
        // SEB ist bereits aktiv, normaler Testablauf
        $code = $_GET['code'];
    } else {
        error_log("SEB Browser nicht erkannt - Versuche SEB zu starten");
        // SEB ist nicht aktiv, starte SEB
        startSEB($_GET['code']);
    }
}

// Funktion zum Überprüfen, ob ein Testcode existiert
function testExists($code) {
    // Extrahiere den Basis-Code für die Dateisuche
    $baseCode = getBaseCode($code); // Erst Basis-Code extrahieren
    $searchCode = $baseCode; // Case-sensitive
    
    error_log("Test-Existenz-Prüfung:");
    error_log("Original Code: " . $code);
    error_log("Basis Code: " . $baseCode);
    error_log("Such-Code: " . $searchCode);
    
    // Suche nach allen XML-Dateien im tests-Verzeichnis
    $allFiles = glob("tests/*.xml");
    error_log("Alle XML-Dateien: " . print_r($allFiles, true));
    
    // Filtere nach dem Basis-Zugangscode (erste 3 Zeichen des Dateinamens)
    $matchingFiles = array_filter($allFiles, function($file) use ($searchCode) {
        $filename = basename($file);
        // Extrahiere die ersten 3 Zeichen des Dateinamens
        $fileCode = substr($filename, 0, 3);
        
        error_log("Prüfe Datei: $filename - Code in Datei: $fileCode - Such-Code: $searchCode");
        
        // Vergleiche die ersten 3 Zeichen mit dem Suchcode
        $matches = ($fileCode === $searchCode);
        
        error_log("Match-Ergebnis für $filename: " . ($matches ? "ja" : "nein"));
        return $matches;
    });
    
    error_log("Gefundene passende Dateien für Code '$searchCode': " . print_r($matchingFiles, true));
    return !empty($matchingFiles);
}

// Wenn ein Code übergeben wurde
if (isset($_GET['code'])) {
    // Lösche die alte Session beim direkten Aufruf eines Tests
    if (!isset($_POST['student_name'])) {
        session_destroy();
        session_start();
    }
    
    $code = $_GET['code']; // Nicht direkt in Großbuchstaben umwandeln
    $baseCode = getBaseCode($code); // Erst Basis-Code extrahieren
    $searchCode = $baseCode; // Nicht in Großbuchstaben umwandeln
    
    error_log("Code-Verarbeitung:");
    error_log("Original Code: " . $code);
    error_log("Basis Code: " . $baseCode);
    error_log("Such-Code: " . $searchCode);
    
    // Wenn der Test existiert
    if (testExists($code)) {
        // Wenn noch kein Name eingegeben wurde
        if (!isset($_SESSION['student_name'])) {
            // Finde die Testdatei anhand der ersten 3 Zeichen des Dateinamens
            $allFiles = glob("tests/*.xml");
            $testFiles = array_filter($allFiles, function($file) use ($searchCode) {
                $filename = basename($file);
                $fileCode = substr($filename, 0, 3);
                return ($fileCode === $searchCode);
            });
            
            $testFile = !empty($testFiles) ? reset($testFiles) : null;
            $testTitle = "Test";
            
            // Lese den Testtitel aus der XML-Datei
            if ($testFile) {
                try {
                    $xml = simplexml_load_file($testFile);
                    if ($xml !== false && isset($xml->title)) {
                        $testTitle = (string)$xml->title;
                    }
                } catch (Exception $e) {
                    error_log("Fehler beim Lesen des Testtitels: " . $e->getMessage());
                }
            }
            
            // Zeige das Namenseingabeformular
            ?>
            <!DOCTYPE html>
            <html lang="de">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Namenseingabe - <?php echo htmlspecialchars($testTitle); ?></title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    .name-form-container {
                        max-width: 600px;
                        margin: 2rem auto;
                        padding: 2rem;
                        background-color: #ffffff;
                        border-radius: 12px;
                        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
                        border-top: 5px solid #0d6efd; /* Blaue Akzentfarbe */
                    }
                    
                    .test-code-info {
                        background-color: #f0f7ff; /* Blassblau als Hintergrund */
                        border-radius: 8px;
                        padding: 1.5rem;
                        margin-bottom: 2rem;
                        text-align: center;
                        /* Rahmen entfernt */
                    }
                    
                    .testcode-badge {
                        background-color: #0d6efd;
                        color: white;
                        font-size: 1.5rem;
                        padding: 0.25rem 1rem;
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
                    
                    .form-title {
                        color: #0d6efd;
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
                        border-color: #0d6efd;
                        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
                        outline: none;
                    }
                    
                    .name-label {
                        font-weight: 600;
                        color: #374151;
                        display: block;
                        margin-bottom: 0.5rem;
                    }
                    
                    .submit-btn {
                        background-color: #0d6efd;
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
                        background-color: #0b5ed7;
                        transform: translateY(-2px);
                    }
                </style>
            </head>
            <body class="bg-light">
                <div class="name-form-container">
                    <div class="test-code-info">
                        <h2>Testcode: <span class="testcode-badge"><?php echo htmlspecialchars($code); ?></span></h2>
                        <p class="mt-3 fw-bold"><?php echo htmlspecialchars($testTitle); ?></p>
                    </div>
                    
                    <div class="student-name-form">
                        <h3 class="form-title">Teilnehmerdaten eingeben</h3>
                        <form action="index.php" method="POST">
                            <input type="hidden" name="code" value="<?php echo htmlspecialchars($code); ?>">
                            <div class="mb-4">
                                <label for="student_name" class="name-label">Vor- und Nachname:</label>
                                <input type="text" class="name-input" id="student_name" name="student_name" 
                                       placeholder="Bitte vollständigen Namen eingeben" required>
                            </div>
                            <button type="submit" class="submit-btn">Test jetzt starten</button>
                        </form>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted small">
                            Mit dem Absenden bestätigen Sie, dass Sie den Test selbständig bearbeiten werden.
                        </p>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script>
                    console.log('Debug Information - Namenseingabe:');
                    console.log('Original Code:', '<?php echo htmlspecialchars($code); ?>');
                    console.log('Basis Code:', '<?php echo htmlspecialchars($baseCode); ?>');
                    console.log('Such-Code:', '<?php echo htmlspecialchars($searchCode); ?>');
                    console.log('Test File:', '<?php echo htmlspecialchars(glob("tests/" . $searchCode . "*.xml")[0] ?? 'Nicht gefunden'); ?>');
                    console.log('Session:', <?php echo json_encode($_SESSION); ?>);
                    console.log('Form Action:', 'index.php');
                    console.log('Form Method:', 'POST');
                    console.log('Form Data:', {
                        code: '<?php echo htmlspecialchars($code); ?>',
                        student_name: '[wird eingegeben]'
                    });
                </script>
            </body>
            </html>
            <?php
        } else {
            // Wenn ein Name eingegeben wurde, zeige den Test
            $_SESSION['test_code'] = $code;
            $baseCode = getBaseCode($code);
            $searchCode = $baseCode;
            
            // Finde die Testdatei anhand der ersten 3 Zeichen des Dateinamens
            $allFiles = glob("tests/*.xml");
            $testFiles = array_filter($allFiles, function($file) use ($searchCode) {
                $filename = basename($file);
                $fileCode = substr($filename, 0, 3);
                return ($fileCode === $searchCode);
            });
            
            $_SESSION['test_file'] = !empty($testFiles) ? reset($testFiles) : null;
            ?>
            <script>
                console.log('Debug Information - Test Anzeige:');
                console.log('Code:', '<?php echo htmlspecialchars($code); ?>');
                console.log('Test File:', '<?php echo htmlspecialchars($_SESSION['test_file'] ?? 'Nicht gefunden'); ?>');
                console.log('Session:', <?php echo json_encode($_SESSION); ?>);
                console.log('Student Name:', '<?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Nicht gesetzt'); ?>');
            </script>
            <?php
            if ($_SESSION['test_file']) {
                error_log("Versuche test.php einzubinden (get) - Pfad: " . $_SESSION['test_file']);
                error_log("Datei existiert: " . (file_exists($_SESSION['test_file']) ? "Ja" : "Nein"));
                
                // Starte Output-Buffering für die Fehlersuche
                ob_start();
                include 'test.php';
                $output = ob_get_clean();
                
                // Prüfe, ob die Ausgabe leer ist
                if (empty(trim($output))) {
                    error_log("WARNUNG: test.php hat keine Ausgabe erzeugt bei 'Wenn ein Name eingegeben wurde'!");
                    ?>
                    <!DOCTYPE html>
                    <html lang="de">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Fehler beim Laden des Tests</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    </head>
                    <body class="bg-light">
                        <div class="container mt-5">
                            <div class="alert alert-danger">
                                <h4>Fehler beim Laden des Tests</h4>
                                <p>Es ist ein Problem beim Laden des Tests aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie den Administrator.</p>
                                <p>Test-Datei: <?php echo htmlspecialchars($_SESSION['test_file']); ?></p>
                                <p>Zugangscode: <?php echo htmlspecialchars($code); ?></p>
                                <a href="index.php" class="btn btn-primary">Zurück zur Startseite</a>
                            </div>
                        </div>
                    </body>
                    </html>
                    <?php
                } else {
                    echo $output;
                }
            } else {
                echo '<div class="container mt-5"><div class="alert alert-danger">Der angegebene Test konnte nicht gefunden werden. Bitte überprüfen Sie den Zugangscode.</div>';
                echo '<a href="index.php" class="btn btn-primary">Zurück zur Startseite</a></div>';
            }
        }
    } else {
        // Wenn der Test nicht existiert, zeige die Startseite mit Fehlermeldung
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>MCQ Test System</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-<?php echo isset($errorType) ? htmlspecialchars($errorType) : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($errorMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="text-center">MCQ Test System</h3>
                            </div>
                            <div class="card-body">
                                <form action="index.php" method="POST">
                                    <div class="mb-3">
                                        <label for="accessCode" class="form-label">Bitte geben Sie Ihren Testcode ein:</label>
                                        <input type="text" class="form-control" id="accessCode" name="accessCode" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Test starten</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                console.log('Debug Information - Fehlerseite:');
                console.log('Original Code:', '<?php echo htmlspecialchars($code); ?>');
                console.log('Basis Code:', '<?php echo htmlspecialchars(getBaseCode($code)); ?>');
                console.log('Such-Code:', '<?php echo htmlspecialchars(getBaseCode($code)); ?>');
                console.log('Test File:', '<?php 
                    $searchCode = getBaseCode($code);
                    echo htmlspecialchars(glob("tests/" . $searchCode . "*.xml")[0] ?? 'Nicht gefunden'); 
                ?>');
                console.log('Session:', <?php echo json_encode($_SESSION); ?>);
            </script>
        </body>
        </html>
        <?php
    }
} 
// Wenn ein Name über POST übermittelt wurde
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name']) && isset($_POST['code'])) {
    error_log("Verarbeite Namenseingabe:");
    error_log("POST Data: " . print_r($_POST, true));
    error_log("Vorherige Session: " . print_r($_SESSION, true));
    
    $code = $_POST['code']; // Nicht umwandeln
    $baseCode = getBaseCode($code); // Erst Basis-Code extrahieren
    $searchCode = $baseCode; // Nicht in Großbuchstaben umwandeln
    
    error_log("Code-Verarbeitung bei Namenseingabe:");
    error_log("Original Code: " . $code);
    error_log("Basis Code: " . $baseCode);
    error_log("Such-Code: " . $searchCode);
    
    // Finde die Testdatei anhand der ersten 3 Zeichen des Dateinamens
    $allFiles = glob("tests/*.xml");
    $testFiles = array_filter($allFiles, function($file) use ($searchCode) {
        $filename = basename($file);
        $fileCode = substr($filename, 0, 3);
        return ($fileCode === $searchCode);
    });
    
    $testFile = !empty($testFiles) ? reset($testFiles) : null;
    
    if ($testFile) {
        // Prüfe, ob der Test heute bereits absolviert wurde (mit dem Schülernamen)
        if (hasCompletedTestToday($code, $_POST['student_name'])) {
            // Setze eine Fehlermeldung und leite zur Startseite weiter
            session_destroy();
            session_start();
            $_SESSION['error_message'] = "Sie haben diesen Test heute bereits absolviert. Bitte versuchen Sie es morgen wieder.";
            $_SESSION['error_type'] = "danger"; // Rot für Fehler
            header("Location: index.php");
            exit();
        }
        
        $_SESSION['student_name'] = $_POST['student_name'];
        $_SESSION['test_code'] = $code; // Original-Code speichern
        $_SESSION['test_file'] = $testFile;
        
        error_log("Neue Session: " . print_r($_SESSION, true));
        error_log("Test-Datei gefunden: " . $testFile);
        error_log("Student Name: " . $_SESSION['student_name']);
        error_log("Test Code: " . $_SESSION['test_code']);
        error_log("Weiterleitung zum Test");
        error_log("Versuche test.php einzubinden (post) - Pfad: " . $testFile);
        error_log("Datei existiert: " . (file_exists($testFile) ? "Ja" : "Nein"));
        
        // Starte Output-Buffering für die Fehlersuche
        ob_start();
        include 'test.php';
        $output = ob_get_clean();
        
        // Prüfe, ob die Ausgabe leer ist
        if (empty(trim($output))) {
            error_log("WARNUNG: test.php hat keine Ausgabe erzeugt!");
            echo '<!DOCTYPE html>
            <html lang="de">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Fehler beim Laden des Tests</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container mt-5">
                    <div class="alert alert-danger">
                        <h4>Fehler beim Laden des Tests</h4>
                        <p>Es ist ein Problem beim Laden des Tests aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie den Administrator.</p>
                        <p>Test-Datei: ' . htmlspecialchars($testFile) . '</p>
                        <p>Zugangscode: ' . htmlspecialchars($code) . '</p>
                        <a href="index.php" class="btn btn-primary">Zurück zur Startseite</a>
                    </div>
                </div>
            </body>
            </html>';
        } else {
            echo $output;
        }
        exit;
    } else {
        $errorMessage = "Ungültiger Testcode";
    }
}
// Wenn kein Code übergeben wurde
else {
    // Default-Inhalte für die Startseite anzeigen
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online-Test-System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <h1 class="display-4 fw-bold text-primary">Online-Test-System</h1>
                    <p class="lead">Geben Sie den Zugangscode ein, um einen Test zu starten</p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-<?php echo $errorType; ?> mb-4">
                            <?php echo $errorMessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['error_type']; ?> mb-4">
                            <?php echo $_SESSION['error_message']; ?>
                        </div>
                        <?php 
                        // Lösche die Fehlermeldung nach der Anzeige
                        unset($_SESSION['error_message']);
                        unset($_SESSION['error_type']);
                        ?>
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
                            require_once('includes/phpqrcode/qrlib.php');
                            
                            // Aktuelle URL für den QR-Code ermitteln
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                            $host = $_SERVER['HTTP_HOST'];
                            $baseUrl = $protocol . $host . dirname($_SERVER['PHP_SELF']);
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

// Setze Header für keine Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>