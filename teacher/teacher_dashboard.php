<?php
session_start();

if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Erstelle erforderliche Verzeichnisse
$directories = [
    dirname(__DIR__) . '/includes',
    dirname(__DIR__) . '/teacher',
    dirname(__DIR__) . '/tests',
    dirname(__DIR__) . '/results',
    dirname(__DIR__) . '/qrcodes'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Lade vorhandene Tests
$tests = [];
$selectedTest = null;
$testFiles = glob(dirname(__DIR__) . '/tests/*.xml');

// Wenn ein Test ausgewählt wurde
if (isset($_GET['test']) && !empty($_GET['test'])) {
    $requestedTest = $_GET['test'];
    error_log("Requested test: " . $requestedTest);
}

foreach ($testFiles as $testFile) {
    try {
        $xml = simplexml_load_file($testFile);
        if ($xml === false) {
            continue;
        }
        
        $title = (string)$xml->title;
        $accessCode = (string)$xml->access_code;
        
        if (empty($title)) {
            $title = basename($testFile, '.xml');
        }
        
        $testData = [
            "file" => $testFile,
            "name" => basename($testFile, '.xml'),
            "title" => $title,
            "accessCode" => $accessCode
        ];
        
        $tests[] = $testData;
        
        // Wenn dieser Test dem angeforderten Test entspricht
        if (isset($requestedTest) && $accessCode === $requestedTest) {
            $selectedTest = $testData;
            error_log("Found matching test: " . json_encode($testData));
        }
    } catch (Exception $e) {
        // Fehlerhafte XML-Datei überspringen
        error_log("Error loading test file: " . $testFile . " - " . $e->getMessage());
        continue;
    }
}

// Wenn ein Test ausgewählt wurde, aber nicht gefunden wurde
if (isset($requestedTest) && $selectedTest === null) {
    error_log("Requested test not found: " . $requestedTest);
}

// Definiere den Basispfad
define('BASE_PATH', __DIR__);

// DEBUG BLOCK START
echo "<!-- File Timestamps:\n";
$files = [
    dirname(__DIR__) . '/includes/ocr_helper.php',
    dirname(__DIR__) . '/includes/teacher_dashboard/test_generator.php',
    __DIR__ . '/generate_test.php'
];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo $file . ": " . date("Y-m-d H:i:s", filemtime($file)) . "\n";
    } else {
        echo $file . ": FILE NOT FOUND\n";
    }
}
echo "-->\n";
// DEBUG BLOCK END
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Lehrer-Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .tab {
            display: inline-block;
            padding: 10px 20px;
            margin-right: 5px;
            text-decoration: none;
            color: #495057;
            border: 1px solid transparent;
            border-radius: 4px 4px 0 0;
            position: relative;
            top: 1px;
        }
        .tab:hover {
            color: #0d6efd;
            border-color: #e9ecef #e9ecef #dee2e6;
            text-decoration: none;
        }
        .tab.active {
            color: #0d6efd;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .tab-pane {
            display: none;
            padding: 20px 0;
        }
        .tab-pane.active {
            display: block;
        }
    </style>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Timestamp Block Start -->
        <div class="text-muted small text-end mb-3">
            <?php 
            $lastModified = filemtime(__FILE__);
            echo "Zuletzt aktualisiert: " . date("d.m.Y H:i:s", $lastModified); 
            ?>
        </div>
        <!-- Timestamp Block End -->

        <div class="nav-tabs">
            <a href="#" class="tab active" data-target="#generator">Test-Generator</a>
            <a href="#" class="tab" data-target="#editor">Test-Editor</a>
            <a href="#" class="tab" data-target="#testResults">Testergebnisse</a>
            <a href="#" class="tab" data-target="#configuration">Konfiguration</a>
        </div>

        <div class="tab-content">
            <div id="generator" class="tab-pane active">
                <?php include(dirname(__DIR__) . '/includes/teacher_dashboard/test_generator_view.php'); ?>
            </div>
            
            <div id="editor" class="tab-pane">
                <?php include(dirname(__DIR__) . '/includes/teacher_dashboard/test_editor_view.php'); ?>
            </div>
            
            <div id="testResults" class="tab-pane">
                <?php include(dirname(__DIR__) . '/includes/teacher_dashboard/test_results_view.php'); ?>
            </div>
            
            <div id="configuration" class="tab-pane">
                <?php include(dirname(__DIR__) . '/includes/teacher_dashboard/configuration_view.php'); ?>
            </div>
        </div>
    </div>

    <!-- Test Preview Modal -->
    <div class="modal fade" id="testPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Vorschau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre class="test-content" style="white-space: pre-wrap;"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        // Füge automatisch eine Frage mit 4 Antworten hinzu, wenn der Test-Editor leer ist
        $(document).ready(function() {
            // Wenn der Test-Editor Tab aktiviert wird und keine Fragen vorhanden sind
            $('.tab[data-target="#editor"]').on('click', function() {
                if ($('#questionsContainer .question-card').length === 0 && $('#testSelector').val() === '') {
            // Wenn der Test-Editor Tab aktiviert wird und keine Fragen vorhanden sind
            $('.tab[data-target="#editor"]').on('click', function() {
                if ($('#questionsContainer .question-card').length === 0 && $('#testSelector').val() === '') {
                    // Füge eine Frage hinzu
                    addQuestion();
                }
            });
        });
</body>
</html>