<?php
session_start();

// Überprüfe, ob Testergebnisse vorhanden sind
if (!isset($_SESSION['test_results'])) {
    header("Location: index.php");
    exit();
}

// Prüfe, ob XML-Download explizit angefordert wurde (über einen Button, nicht automatisch)
if (isset($_GET['download']) && $_GET['download'] == 'xml' && isset($_SESSION['download_xml_file']) && file_exists($_SESSION['download_xml_file'])) {
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
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --danger-color: #ef4444;
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
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--card-background);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }
        
        .result-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .result-card {
            padding: 20px;
            background-color: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .percentage-display {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            margin: 20px 0;
        }
        
        .grade-display {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border-radius: 8px;
            background-color: var(--primary-color);
            color: white;
        }
        
        .score-details {
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-success:hover {
            background-color: #059669;
            border-color: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-header">
            <h1>Testergebnis</h1>
                        </div>
                        
        <div class="result-card">
            <div class="percentage-display">
                                            <?php echo $_SESSION['test_results']['percentage']; ?>%
                                        </div>
            
            <div class="grade-display">
                <?php echo $_SESSION['test_results']['grade']; ?>
                        </div>
                        
            <div class="score-details">
                Erreichte Punkte: <?php echo $_SESSION['test_results']['achieved']; ?> von <?php echo $_SESSION['test_results']['max']; ?>
                            </div>
                        </div>
                        
        <div class="action-buttons">
            <a href="result.php?download=xml" class="btn btn-success">XML-Ergebnis herunterladen</a>
            
            <form method="post" action="">
                <button type="submit" name="back_to_home" class="btn btn-primary">Zurück zur Startseite</button>
                            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 