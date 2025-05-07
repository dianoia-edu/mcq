<?php
// Starte Session
session_start();

// Lade benötigte Funktionen
require_once 'includes/seb_functions.php';

// Extrahiere den Code aus GET oder POST
$code = $_GET['code'] ?? $_POST['code'] ?? '';
$seb = $_GET['seb'] ?? 'false';

error_log("Name Form Debug - Code: " . $code);
error_log("Name Form Debug - SEB: " . $seb);

// Finde die Testdatei
$baseCode = getBaseCode($code);
$searchCode = $baseCode;
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

error_log("Name Form Debug - Test File: " . ($testFile ?? 'Nicht gefunden'));
error_log("Name Form Debug - Test Title: " . $testTitle);
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
            border-top: 5px solid #0d6efd;
        }
        
        .test-code-info {
            background-color: #f0f7ff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
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
            <div id="nameForm">
                <div class="mb-4">
                    <label for="student_name" class="name-label">Vor- und Nachname:</label>
                    <input type="text" class="name-input" id="student_name" name="student_name" placeholder="Bitte vollständigen Namen eingeben" required>
                </div>
                <input type="hidden" id="test_code" value="<?php echo htmlspecialchars($code); ?>">
                <div class="d-grid gap-2">
                    <button id="browserBtn" class="btn btn-primary btn-lg mb-2">Test im Browser starten</button>
                    <button id="sebBtn" class="btn btn-success btn-lg">Test im Safe Exam Browser starten</button>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-muted small">
                Mit dem Absenden bestätigen Sie, dass Sie den Test selbständig bearbeiten werden.
            </p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('browserBtn').onclick = function() {
        var name = encodeURIComponent(document.getElementById('student_name').value);
        var code = encodeURIComponent(document.getElementById('test_code').value);
        if (!name) {
            alert('Bitte geben Sie Ihren Namen ein.');
            return;
        }
        window.location.href = 'index.php?code=' + code + '&student_name=' + name;
    };
    document.getElementById('sebBtn').onclick = function() {
        var name = encodeURIComponent(document.getElementById('student_name').value);
        var code = encodeURIComponent(document.getElementById('test_code').value);
        if (!name) {
            alert('Bitte geben Sie Ihren Namen ein.');
            return;
        }
        var url = '<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); ?>' + '/index.php?code=' + code + '&seb=true&student_name=' + name;
        window.location.href = 'seb://start?url=' + encodeURIComponent(url);
    };
    </script>
</body>
</html> 