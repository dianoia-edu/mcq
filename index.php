<?php
// Aktiviere Fehlerberichterstattung für die Entwicklung
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug-Ausgabe direkt am  Anfang
echo "<!-- Debug: index.php wird geladen - " . date('Y-m-d H:i:s') . " -->";

// Starte Output-Buffering
ob_start();

// Starte Session 
session_start();



// Versuche, die erforderlichen Dateien einzubinden
try {
    require_once 'check_test_attempts.php';
    echo "<!-- Debug: check_test_attempts.php erfolgreich eingebunden -->";
} catch (Exception $e) {
    echo "Fehler beim Einbinden von check_test_attempts.php: " . $e->getMessage();
    error_log("Fehler beim Einbinden von check_test_attempts.php: " . $e->getMessage());
}


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

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/logs/debug.log';
    $logDir = dirname($logFile);
    
    // Erstelle das Log-Verzeichnis, falls es nicht existiert
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Protokolliere den Seitenaufruf
writeLog("index.php aufgerufen - " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Prüfe, ob eine Fehlermeldung angezeigt werden soll
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_code':
            $errorMessage = 'Ungültiger Zugangscode. Bitte versuchen Sie es erneut.';
            break;
        case 'no_results':
            $errorMessage = 'Keine Testergebnisse gefunden. Bitte versuchen Sie es erneut.';
            break;
        case 'db_error':
            $errorMessage = 'Datenbankfehler. Bitte versuchen Sie es später erneut.';
            break;
        case 'missing_data':
            $errorMessage = 'Fehlende Daten. Bitte versuchen Sie es erneut.';
            break;
        default:
            $errorMessage = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
    }
    writeLog("Fehler angezeigt: " . $_GET['error'] . " - " . $errorMessage);
}

// Wenn ein Fehler in der Session gespeichert ist, diesen anzeigen und aus der Session entfernen
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
    writeLog("Fehler aus Session angezeigt: " . $errorMessage);
}

// Überprüfe POST-Anfrage
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo "<!-- Debug: POST-Anfrage erkannt -->";
    if (isset($_POST["accessCode"])) {
        echo "<!-- Debug: accessCode gefunden: " . htmlspecialchars($_POST["accessCode"]) . " -->";
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
        $testFile = "tests/" . $baseCode . "*.xml";
        $files = glob($testFile);
        
        if (!empty($files)) {
            // Test existiert
            header("Location: index.php?code=" . urlencode($accessCode));
            exit();
        } else {
            $errorMessage = "Der eingegebene Zugangscode ist ungültig. Bitte überprüfen Sie Ihre Eingabe.";
            $errorType = "danger";
        }
    } elseif (isset($_POST["student_name"]) && isset($_POST["code"])) {
        echo "<!-- Debug: student_name und code gefunden -->";
        // Speichere den Namen und Code in der Session
        $_SESSION['student_name'] = trim($_POST["student_name"]);
        $_SESSION['test_code'] = trim($_POST["code"]);
        $_SESSION['test_started_at'] = date('Y-m-d H:i:s');
        
        // Extrahiere den Basis-Code für die Dateisuche
        $baseCode = getBaseCode($_SESSION['test_code']);
        
        // Suche nach der Testdatei
        $files = glob("tests/" . $baseCode . "*.xml");
        if (!empty($files)) {
            $_SESSION['test_file'] = $files[0];
            
            // Weiterleitung zur Testseite
            header("Location: test.php");
            exit();
        } else {
            $errorMessage = "Der Test konnte nicht gefunden werden. Bitte versuchen Sie es erneut.";
            $errorType = "danger";
        }
    }
}



// Funktion zum Überprüfen, ob ein Testcode existiert
function testExists($code) {
    // Extrahiere den Basis-Code für die Dateisuche
    $baseCode = getBaseCode($code); // Erst Basis-Code extrahieren
    $searchCode = strtoupper($baseCode); // Dann in Großbuchstaben umwandeln
    
    error_log("Test-Existenz-Prüfung:");
    error_log("Original Code: " . $code);
    error_log("Basis Code: " . $baseCode);
    error_log("Such-Code: " . $searchCode);
    
    // Suche nach allen XML-Dateien im tests-Verzeichnis
    $allFiles = glob("tests/*.xml");
    error_log("Alle XML-Dateien: " . print_r($allFiles, true));
    
    // Filtere nach dem Basis-Zugangscode
    $matchingFiles = array_filter($allFiles, function($file) use ($searchCode) {
        $filename = strtoupper(basename($file));
        $matches = strpos($filename, $searchCode) === 0;
        error_log("Prüfe Datei: $filename - Such-Code: $searchCode - Match: " . ($matches ? "ja" : "nein"));
        return $matches;
    });
    
    error_log("Gefundene passende Dateien für Code '$searchCode': " . print_r($matchingFiles, true));
    return !empty($matchingFiles);
}

// Wenn ein Code übergeben wurde
if (isset($_GET['code'])) {
    echo "<!-- Debug: GET-Parameter 'code' gefunden: " . htmlspecialchars($_GET['code']) . " -->";
    // Lösche die alte Session beim direkten Aufruf eines Tests
    if (!isset($_POST['student_name'])) {
        session_destroy();
        session_start();
    }
    
    $code = $_GET['code']; // Nicht direkt in Großbuchstaben umwandeln
    $baseCode = getBaseCode($code); // Erst Basis-Code extrahieren
    $searchCode = strtoupper($baseCode); // Dann in Großbuchstaben umwandeln
    
    error_log("Code-Verarbeitung:");
    error_log("Original Code: " . $code);
    error_log("Basis Code: " . $baseCode);
    error_log("Such-Code: " . $searchCode);
    
    // Wenn der Test existiert
    if (testExists($code)) {
        echo "<!-- Debug: Test existiert -->";
        // Wenn noch kein Name eingegeben wurde
        if (!isset($_SESSION['student_name'])) {
            echo "<!-- Debug: Kein Schülername in der Session, zeige Namenseingabeformular -->";
            // Zeige das Namenseingabeformular
            ?>
            <!DOCTYPE html>
            <html lang="de">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Namenseingabe - Test</title>
                <!-- Favicon -->
                <link rel="icon" href="/favicon.ico" type="image/x-icon">
                <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
                <!-- Bootstrap CSS -->
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <!-- Globale CSS-Datei -->
                <link href="css/global.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="text-center">Bitte geben Sie Ihren Namen ein</h3>
                                </div>
                                <div class="card-body">
                                    <form action="index.php" method="POST">
                                        <input type="hidden" name="code" value="<?php echo htmlspecialchars($code); ?>">
                                        <div class="mb-3">
                                            <label for="student_name" class="form-label">Ihr Name:</label>
                                            <input type="text" class="form-control" id="student_name" name="student_name" required>
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
            echo "<!-- Debug: Schülername in der Session gefunden, leite weiter zu test.php -->";
            // Wenn ein Name eingegeben wurde, zeige den Test
            $_SESSION['test_code'] = $code;
            $baseCode = getBaseCode($code);
            $_SESSION['test_file'] = glob("tests/" . $baseCode . "*.xml")[0];
            
            // Weiterleitung zur Testseite
            header("Location: test.php");
            exit();
        }
    } else {
        echo "<!-- Debug: Test existiert nicht -->";
        // Wenn der Test nicht existiert, zeige die Startseite mit Fehlermeldung
        $errorMessage = "Der eingegebene Zugangscode ist ungültig. Bitte überprüfen Sie Ihre Eingabe.";
        $errorType = "danger";
    }
}

// Wenn kein Code übergeben wurde und keine POST-Anfrage vorliegt, zeige die Startseite
if (!isset($_GET['code']) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "<!-- Debug: Zeige Startseite -->";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ Test System</title>
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Globale CSS-Datei -->
    <link href="css/global.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">MCQ Test System</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-<?php echo isset($errorType) ? $errorType : 'danger'; ?> mb-3">
                                <?php echo htmlspecialchars($errorMessage); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="index.php" method="POST">
                            <div class="mb-3">
                                <label for="accessCode" class="form-label">Zugangscode:</label>
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
        console.log('Debug Information - Startseite:');
        console.log('Session:', <?php echo json_encode($_SESSION); ?>);
        console.log('Error Message:', '<?php echo addslashes($errorMessage); ?>');
        console.log('Error Type:', '<?php echo isset($errorType) ? $errorType : 'danger'; ?>');
    </script>
</body>
</html>
<?php
}

// Setze Header für keine Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>