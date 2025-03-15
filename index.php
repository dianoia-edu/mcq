<?php
// Starte Output-Buffering
ob_start();

// Starte Session
session_start();

require_once 'check_test_attempts.php';
require_once 'config.php';

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
        // Wenn noch kein Name eingegeben wurde
        if (!isset($_SESSION['student_name'])) {
            // Zeige das Namenseingabeformular
            ?>
            <!DOCTYPE html>
            <html lang="de">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Namenseingabe - Test</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            // Wenn ein Name eingegeben wurde, zeige den Test
            $_SESSION['test_code'] = $code;
            $baseCode = getBaseCode($code);
            $_SESSION['test_file'] = glob("tests/" . $baseCode . "*.xml")[0];
            ?>
            <script>
                console.log('Debug Information - Test Anzeige:');
                console.log('Code:', '<?php echo htmlspecialchars($code); ?>');
                console.log('Test File:', '<?php echo htmlspecialchars($_SESSION['test_file'] ?? 'Nicht gefunden'); ?>');
                console.log('Session:', <?php echo json_encode($_SESSION); ?>);
                console.log('Student Name:', '<?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Nicht gesetzt'); ?>');
            </script>
            <?php
            include 'test.php';
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
                console.log('Such-Code:', '<?php echo htmlspecialchars(strtoupper(getBaseCode($code))); ?>');
                console.log('Test File:', '<?php 
                    $searchCode = strtoupper(getBaseCode($code));
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
    
    $code = $_POST['code']; // Nicht direkt in Großbuchstaben umwandeln
    $baseCode = getBaseCode($code); // Erst Basis-Code extrahieren
    $searchCode = strtoupper($baseCode); // Dann in Großbuchstaben umwandeln
    
    error_log("Code-Verarbeitung bei Namenseingabe:");
    error_log("Original Code: " . $code);
    error_log("Basis Code: " . $baseCode);
    error_log("Such-Code: " . $searchCode);
    
    $testFile = glob("tests/" . $searchCode . "*.xml")[0];
    
    if ($testFile) {
        $_SESSION['student_name'] = $_POST['student_name'];
        $_SESSION['test_code'] = $code; // Original-Code speichern
        $_SESSION['test_file'] = $testFile;
        
        error_log("Neue Session: " . print_r($_SESSION, true));
        error_log("Weiterleitung zum Test");
        
        include 'test.php';
        exit;
    } else {
        $errorMessage = "Ungültiger Testcode";
    }
}
// Wenn kein Code übergeben wurde
else {
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
            console.log('Debug Information - Startseite:');
            console.log('Session:', <?php echo json_encode($_SESSION); ?>);
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