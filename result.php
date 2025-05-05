<?php
session_start();

// Überprüfe, ob Testergebnisse vorhanden sind
if (!isset($_SESSION['test_results'])) {
    header("Location: index.php");
    exit();
}

// Prüfe, ob XML-Download angefordert wurde
if (isset($_GET['download']) && $_GET['download'] == '1' && isset($_SESSION['download_xml_file']) && file_exists($_SESSION['download_xml_file'])) {
    $file_path = $_SESSION['download_xml_file'];
    $file_name = $_SESSION['download_xml_filename'];
    
    // Stelle sicher, dass die Datei existiert und lesbar ist
    if (file_exists($file_path) && is_readable($file_path)) {
        // Setze die richtigen Header für den Download
        header('Content-Description: File Transfer');
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        
        // Lese und sende die Datei
        readfile($file_path);
        
        // Entferne die Download-Information aus der Session
        unset($_SESSION['download_xml_file']);
        unset($_SESSION['download_xml_filename']);
        
        // Beende das Skript nach dem Download
        exit;
    }
}

// Wenn der "Zurück zur Startseite" Button geklickt wurde
if (isset($_POST['back_to_home'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testergebnis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .result-card {
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .result-value {
            font-size: 3rem;
            font-weight: bold;
        }
        .grade-display {
            font-size: 5rem;
            font-weight: bold;
            margin: 20px 0;
        }
        .grade-1, .grade-2 {
            color: #28a745; /* Grün für gute Noten */
        }
        .grade-3, .grade-4 {
            color: #fd7e14; /* Orange für mittlere Noten */
        }
        .grade-5, .grade-6 {
            color: #dc3545; /* Rot für schlechte Noten */
        }
        .home-button {
            padding: 15px 30px;
            font-size: 1.2rem;
            margin-top: 30px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        .home-button:hover {
            transform: scale(1.05);
        }
        .congratulation {
            margin-bottom: 30px;
        }
        .download-xml-btn {
            margin-top: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card result-card mb-4">
                    <div class="card-header bg-primary text-white py-4">
                        <h2 class="text-center mb-0">Dein Testergebnis</h2>
                    </div>
                    <div class="card-body p-5">
                        <div class="text-center congratulation">
                            <div class="display-1 text-success mb-3">✓</div>
                            <h3>Test erfolgreich abgeschlossen!</h3>
                            <p class="lead">Vielen Dank für deine Teilnahme, <strong><?php echo htmlspecialchars($_SESSION['student_name']); ?></strong>.</p>
                        </div>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card h-100 bg-light">
                                    <div class="card-body text-center p-4">
                                        <h4 class="text-primary mb-3">Erreichte Punkte</h4>
                                        <div class="result-value">
                                            <?php echo $_SESSION['test_results']['achieved']; ?> / <?php echo $_SESSION['test_results']['max']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 bg-light">
                                    <div class="card-body text-center p-4">
                                        <h4 class="text-primary mb-3">Prozent</h4>
                                        <div class="result-value">
                                            <?php echo $_SESSION['test_results']['percentage']; ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4 p-4 bg-light rounded">
                            <h4 class="text-center mb-3">Deine Note</h4>
                            <?php 
                            $grade = $_SESSION['test_results']['grade'];
                            $gradeClass = '';
                            if ($grade == '1' || $grade == '2') {
                                $gradeClass = 'grade-1';
                            } else if ($grade == '3' || $grade == '4') {
                                $gradeClass = 'grade-3';
                            } else if ($grade == '5' || $grade == '6') {
                                $gradeClass = 'grade-5';
                            }
                            ?>
                            <div class="grade-display <?php echo $gradeClass; ?>">
                                <?php echo $_SESSION['test_results']['grade']; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['download_xml_file']) && file_exists($_SESSION['download_xml_file'])): ?>
                        <div class="text-center download-xml-btn">
                            <a href="result.php?download=1" class="btn btn-success">
                                <i class="bi bi-file-earmark-code-fill me-2"></i>Ergebnis als XML herunterladen
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <form method="post">
                                <button type="submit" name="back_to_home" class="btn btn-primary btn-lg home-button">
                                    <i class="bi bi-house-door me-2"></i>Zurück zur Startseite
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html> 