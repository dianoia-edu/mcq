<?php
session_start();

// Überprüfe, ob Testergebnisse vorhanden sind
if (!isset($_SESSION['test_results'])) {
    header("Location: index.php");
    exit();
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
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Globale CSS-Datei -->
    <link href="css/global.css" rel="stylesheet">
    <style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="text-center mb-0">Ihre Testergebnisse</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="display-1 text-success mb-3">✓</div>
                            <h4>Test erfolgreich abgeschlossen!</h4>
                            <p>Vielen Dank für Ihre Teilnahme, <?php echo htmlspecialchars($_SESSION['student_name']); ?>.</p>
                        </div>
                        <div class="result-box bg-light p-3 rounded">
                            <h5 class="text-center mb-3">Ihre Ergebnisse</h5>
                            <div class="row text-center">
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1">Erreichte Punkte:</p>
                                    <h3 class="text-primary">
                                        <?php echo $_SESSION['test_results']['achieved']; ?> von <?php echo $_SESSION['test_results']['max']; ?>
                                    </h3>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1">Prozent:</p>
                                    <h3 class="text-primary">
                                        <?php echo $_SESSION['test_results']['percentage']; ?>%
                                    </h3>
                                </div>
                                <div class="col-12">
                                    <p class="mb-1">Note:</p>
                                    <h2 class="text-danger">
                                        <?php echo $_SESSION['test_results']['grade']; ?>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <form method="post">
                                <button type="submit" name="back_to_home" class="btn btn-primary">Zurück zur Startseite</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 