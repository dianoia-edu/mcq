<?php
/**
 * Session-Setup für Tests aus der SEB-Integration
 * Bereitet die Session korrekt vor, damit test.php funktioniert
 */

// Session starten
session_start();

// Erkennung ob GET oder POST Request
$isGET = $_SERVER['REQUEST_METHOD'] === 'GET';

// Header für GET (Redirect) oder POST (JSON)
if ($isGET) {
    // GET: Normale HTML-Antwort mit Redirect
} else {
    // POST: JSON-Header setzen
    header('Content-Type: application/json');
}

// Fehlerberichterstattung
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Debug-Logging
    error_log("=== SETUP SESSION START ===");
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("GET: " . print_r($_GET, true));
    error_log("POST: " . print_r($_POST, true));
    
    // Parameter aus POST oder GET holen
    if ($isGET) {
        $studentName = $_GET['student_name'] ?? '';
        $testCode = $_GET['test_code'] ?? '';
        $isSEB = ($_GET['seb'] ?? 'false') === 'true';
        $redirect = $_GET['redirect'] ?? 'json';
    } else {
        $studentName = $_POST['student_name'] ?? '';
        $testCode = $_POST['test_code'] ?? '';
        $isSEB = ($_POST['seb'] ?? 'false') === 'true';
        $redirect = 'json';
    }
    
    error_log("Extracted Parameters:");
    error_log("- studentName: " . $studentName);
    error_log("- testCode: " . $testCode);
    error_log("- isSEB: " . ($isSEB ? 'true' : 'false'));
    error_log("- redirect: " . $redirect);
    
    // Validierung
    if (empty($studentName)) {
        throw new Exception('Schülername ist erforderlich');
    }
    
    if (empty($testCode)) {
        throw new Exception('Test-Code ist erforderlich');
    }
    
    // Universelle Test-Suche (konsistent mit index.php)
    // Fallback-Funktion falls nicht definiert
    if (!function_exists('getBaseCode')) {
        function getBaseCode($code) {
            return strtoupper(substr(trim($code), 0, 3));
        }
    }
    
    // Finde Test-Datei - MEHRSTUFIGE SUCHE
    $testFile = null;
    
    // 1. Versuch: Exakter Name
    $exactFile = "tests/" . $testCode . ".xml";
    if (file_exists($exactFile)) {
        $testFile = $exactFile;
        error_log("Setup Session: Test gefunden (exakt): " . $testFile);
    }
    
    // 2. Versuch: Mit Titel-Suffix (z.B. POT_die-potsdamer-konferenz...)
    if (!$testFile) {
        $testPattern = "tests/" . $testCode . "_*.xml";
        $matchingFiles = glob($testPattern);
        
        if (!empty($matchingFiles)) {
            $testFile = $matchingFiles[0];
            error_log("Setup Session: Test gefunden (Pattern): " . $testFile);
        }
    }
    
    // 3. Versuch: Basis-Code-Suche (erste 3 Zeichen)
    if (!$testFile) {
        $baseCode = getBaseCode($testCode);
        $searchCode = $baseCode;
        $allFiles = glob("tests/*.xml");
        $testFiles = array_filter($allFiles, function($file) use ($searchCode) {
            $filename = basename($file);
            $fileCode = substr($filename, 0, 3);
            return ($fileCode === $searchCode);
        });
        
        if (!empty($testFiles)) {
            $testFile = reset($testFiles);
            error_log("Setup Session: Test gefunden (Basis-Code): " . $testFile);
        }
    }
    
    if (!$testFile || !file_exists($testFile)) {
        error_log("Setup Session: Test NICHT gefunden für Code: " . $testCode);
        error_log("Setup Session: Vorhandene Tests: " . print_r(glob("tests/*.xml"), true));
        throw new Exception('Test "' . $testCode . '" nicht gefunden');
    }
    
    // Test-Details aus XML laden
    $testTitle = $testCode; // Fallback
    $timeLimit = null;
    
    try {
        $xml = simplexml_load_file($testFile);
        if ($xml !== false) {
            if (isset($xml->title)) {
                $testTitle = (string)$xml->title;
            }
            if (isset($xml->timeLimit)) {
                $timeLimit = (int)$xml->timeLimit;
            }
        }
    } catch (Exception $e) {
        error_log("Fehler beim Lesen der Test-XML: " . $e->getMessage());
    }
    
    // Session-Variablen setzen (wie test.php sie erwartet)
    $_SESSION['test_file'] = $testFile;
    $_SESSION['test_code'] = $testCode;
    $_SESSION['student_name'] = trim($studentName);
    $_SESSION['testName'] = $testTitle;
    $_SESSION['testFile'] = $testFile; // Zusätzlich für Kompatibilität
    $_SESSION['studentName'] = trim($studentName); // Zusätzlich für Kompatibilität
    $_SESSION['loginTime'] = time();
    
    if ($timeLimit) {
        $_SESSION['timeLimit'] = $timeLimit;
    }
    
    // SEB-Erkennung
    if ($isSEB) {
        $_SESSION['seb_browser'] = true;
    }
    
    // URL für Test generieren
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $dir = dirname($_SERVER['PHP_SELF']);
    $baseUrl = $protocol . $host . $dir;
    
    // Test-URL ohne SEB-Parameter (für normale Browser)
    $testUrl = $baseUrl . '/test.php';
    
    // Debug-Logging
    error_log("Setup Session erfolgreich:");
    error_log("- Student: " . $studentName);
    error_log("- Test-Code: " . $testCode);
    error_log("- Test-Datei: " . $testFile);
    error_log("- Test-Titel: " . $testTitle);
    error_log("- SEB: " . ($isSEB ? 'ja' : 'nein'));
    error_log("- Test-URL: " . $testUrl);
    
    // DEBUG: Session nach dem Setzen anzeigen
    error_log("=== SETUP SESSION ERFOLGREICH ===");
    error_log("Session nach Setup: " . print_r($_SESSION, true));
    error_log("Test-URL: " . $testUrl);
    error_log("Redirect-Parameter: " . $redirect);
    
    // Antwort je nach Request-Type
    if ($isGET && $redirect === 'test') {
        // GET mit redirect=test: Direkte Weiterleitung zu test.php
        error_log("GET-Redirect zu: " . $testUrl);
        header("Location: " . $testUrl);
        exit();
    } else {
        // POST oder GET ohne redirect: JSON-Antwort
        error_log("JSON-Antwort wird gesendet");
        echo json_encode([
            'success' => true,
            'test_url' => $testUrl,
            'test_title' => $testTitle,
            'test_code' => $testCode,
            'student_name' => $studentName,
            'seb' => $isSEB
        ]);
    }
    
} catch (Exception $e) {
    error_log("=== SETUP SESSION FEHLER ===");
    error_log("Fehler: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Redirect Parameter: " . ($redirect ?? 'nicht gesetzt'));
    
    if ($isGET && $redirect === 'test') {
        // GET-Fehler: Debug-Seite statt direkter Weiterleitung
        echo '<!DOCTYPE html><html><head><title>Setup Session Fehler</title></head><body>';
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px;">';
        echo '<h2>❌ SETUP SESSION FEHLER</h2>';
        echo '<p><strong>Fehler:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Test-Code:</strong> ' . htmlspecialchars($testCode ?? 'nicht gesetzt') . '</p>';
        echo '<p><strong>Student-Name:</strong> ' . htmlspecialchars($studentName ?? 'nicht gesetzt') . '</p>';
        echo '<p><strong>Request Method:</strong> ' . htmlspecialchars($_SERVER['REQUEST_METHOD']) . '</p>';
        echo '<p><strong>GET Parameter:</strong></p><pre>' . print_r($_GET, true) . '</pre>';
        echo '<p><strong>POST Parameter:</strong></p><pre>' . print_r($_POST, true) . '</pre>';
        echo '<p>Weiterleitung zu name_form.php in 10 Sekunden...</p>';
        echo '<script>setTimeout(function() { window.location.href = "name_form.php?code=' . urlencode($testCode ?? '') . '&error=' . urlencode("Fehler: " . $e->getMessage()) . '"; }, 10000);</script>';
        echo '</div>';
        echo '</body></html>';
        exit();
    } else {
        // POST-Fehler: JSON-Antwort
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'get' => $_GET,
                'post' => $_POST,
                'trace' => $e->getTraceAsString()
            ]
        ]);
    }
}
?>
