<?php
/**
 * Debug für Tab-Inhalte in Instanzen
 */
session_start();

// Ermittle, ob wir in einer Instanz sind
$isInInstance = (strpos(__DIR__, 'lehrer_instanzen') !== false);
$currentDir = __DIR__;
$scriptName = basename($_SERVER['SCRIPT_NAME']);

// Basis-Pfad ermitteln
if ($isInInstance) {
    // In einer Instanz: mcq-test-system ist das aktuelle Verzeichnis
    $baseDir = __DIR__;
    $includesPath = $baseDir . '/includes/teacher_dashboard/';
} else {
    // Im Hauptsystem
    $baseDir = __DIR__;
    $includesPath = $baseDir . '/includes/teacher_dashboard/';
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Instance Tab Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>🔍 Instance Tab Debug</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Pfad-Informationen</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Aktuelles Verzeichnis:</strong><br><code><?php echo $currentDir; ?></code></p>
                        <p><strong>Script Name:</strong><br><code><?php echo $scriptName; ?></code></p>
                        <p><strong>In Instanz:</strong> <?php echo $isInInstance ? '✅ JA' : '❌ NEIN'; ?></p>
                        <p><strong>Base Dir:</strong><br><code><?php echo $baseDir; ?></code></p>
                        <p><strong>Includes Path:</strong><br><code><?php echo $includesPath; ?></code></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Include-Datei Tests</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $testFiles = [
                            'test_generator_view.php',
                            'configuration_view.php', 
                            'test_results_view.php',
                            'test_editor_view.php'
                        ];
                        
                        foreach ($testFiles as $file) {
                            $fullPath = $includesPath . $file;
                            $exists = file_exists($fullPath);
                            $readable = $exists ? is_readable($fullPath) : false;
                            $size = $exists ? filesize($fullPath) : 0;
                            
                            echo '<p><strong>' . $file . ':</strong><br>';
                            echo $exists ? '✅ Existiert' : '❌ Fehlt';
                            if ($exists) {
                                echo ' | ' . ($readable ? '✅ Lesbar' : '❌ Nicht lesbar');
                                echo ' | ' . number_format($size) . ' Bytes';
                            }
                            echo '<br><code>' . $fullPath . '</code></p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Teacher Dashboard Test</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $dashboardPath = $baseDir . '/teacher/teacher_dashboard.php';
                        $dashboardExists = file_exists($dashboardPath);
                        
                        echo '<p><strong>Teacher Dashboard:</strong><br>';
                        echo $dashboardExists ? '✅ Existiert' : '❌ Fehlt';
                        echo '<br><code>' . $dashboardPath . '</code></p>';
                        
                        if ($dashboardExists) {
                            echo '<a href="teacher/teacher_dashboard.php" class="btn btn-primary">Dashboard öffnen</a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Server-Variablen</h5>
                    </div>
                    <div class="card-body">
                        <pre style="max-height: 300px; overflow-y: auto;"><?php
                        $serverVars = [
                            'DOCUMENT_ROOT',
                            'SCRIPT_FILENAME', 
                            'REQUEST_URI',
                            'PHP_SELF',
                            'HTTP_HOST'
                        ];
                        
                        foreach ($serverVars as $var) {
                            echo $var . ': ' . ($_SERVER[$var] ?? 'NICHT GESETZT') . "\n";
                        }
                        ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
