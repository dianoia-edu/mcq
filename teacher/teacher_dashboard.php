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
    <script>
    // SOFORTIGE DEBUG-AUSGABE
    console.log('DIREKTE DEBUG AUSGABE - ' + new Date().toLocaleString());
    
    // Tab-Funktion vor HTML-Code definieren
    function activateTab(tabId) {
        console.log('Aktiviere Tab:', tabId);
        
        // Alle Tabs deaktivieren
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Alle Tab-Inhalte ausblenden
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
            pane.style.display = 'none';
        });
        
        // Gewählten Tab aktivieren
        document.getElementById('tab-' + tabId).classList.add('active');
        
        // Gewählten Tab-Inhalt anzeigen
        const tabContent = document.getElementById(tabId);
        tabContent.classList.add('active');
        tabContent.style.display = 'block';
        
        // URL aktualisieren
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabId);
        window.history.pushState({}, '', url);
        
        console.log('Tab aktiviert:', tabId);
    }
    </script>
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
            <a href="#" class="tab" id="tab-generator" onclick="activateTab('generator')">Test-Generator</a>
            <a href="#" class="tab" id="tab-editor" onclick="activateTab('editor')">Test-Editor</a>
            <a href="#" class="tab" id="tab-testResults" onclick="activateTab('testResults')">Testergebnisse</a>
            <a href="#" class="tab" id="tab-configuration" onclick="activateTab('configuration')">Konfiguration</a>
        </div>

        <div class="tab-content">
            <div id="generator" class="tab-pane">
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
        // Initial den richtigen Tab aktivieren
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM geladen, initialisiere Tabs');
            
            // Prüfe URL-Parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam && document.getElementById(tabParam)) {
                console.log('Aktiviere Tab aus URL:', tabParam);
                activateTab(tabParam);
            } else {
                console.log('Aktiviere Standard-Tab: generator');
                activateTab('generator');
            }
            
            // Wenn der Editor-Tab aktiviert wird und keine Fragen vorhanden sind
            if (tabParam === 'editor' && 
                document.querySelectorAll('#questionsContainer .question-card').length === 0 && 
                document.getElementById('testSelector').value === '') {
                console.log('Füge Standardfrage hinzu');
                addQuestion();
            }
        });
    </script>
</body>
</html>