<?php
session_start();
require_once 'includes/TestDatabase.php';

// Debug-Logging
error_log("Session-Variablen in result.php: " . print_r($_SESSION, true));

// Prüfen, ob Testergebnisse in der Session vorhanden sind
if (!isset($_SESSION['test_results'])) {
    error_log("Keine Testergebnisse in der Session gefunden");
    
    // Versuchen, die Ergebnisse aus der Datenbank zu laden, wenn Code und Name vorhanden sind
    if (isset($_SESSION['test_code']) && isset($_SESSION['student_name'])) {
        error_log("Versuche, Ergebnisse aus der Datenbank zu laden für " . $_SESSION['student_name']);
        
        try {
            $db = new TestDatabase();
            $results = $db->getLatestTestResult($_SESSION['test_code'], $_SESSION['student_name']);
            
            if ($results) {
                $_SESSION['test_results'] = $results;
                error_log("Ergebnisse erfolgreich aus der Datenbank geladen");
            } else {
                error_log("Keine Ergebnisse in der Datenbank gefunden");
                header("Location: index.php?error=no_results");
                exit;
            }
        } catch (Exception $e) {
            error_log("Fehler beim Laden der Ergebnisse aus der Datenbank: " . $e->getMessage());
            header("Location: index.php?error=db_error");
            exit;
        }
    } else {
        error_log("Keine Test-Code oder Schülername in der Session gefunden");
        header("Location: index.php?error=missing_data");
        exit;
    }
}

$results = $_SESSION['test_results'];
$studentName = isset($_SESSION['student_name']) ? $_SESSION['student_name'] : 'Unbekannt';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testergebnis</title>
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Globale CSS-Datei -->
    <link href="css/global.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .result-box {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .display-1 {
            font-size: 4rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="result-box text-center">
                    <h1 class="mb-4">Test abgeschlossen!</h1>
                    <p class="lead">Vielen Dank, <?php echo htmlspecialchars($studentName); ?>!</p>
                    
                    <div class="my-5">
                        <h2>Dein Ergebnis:</h2>
                        <p class="display-1 mb-0"><?php echo $results['points_achieved']; ?> / <?php echo $results['points_maximum']; ?></p>
                        <p class="lead"><?php echo round($results['percentage']); ?>% - Note: <?php echo $results['grade']; ?></p>
                    </div>
                    
                    <a href="index.php" class="btn btn-primary btn-lg">Zurück zur Startseite</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 