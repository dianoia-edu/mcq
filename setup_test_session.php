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
    
    // Antwort je nach Request-Type
    if ($isGET && $redirect === 'test') {
        // GET mit redirect=test: Direkte Weiterleitung zu test.php
        header("Location: " . $testUrl);
        exit();
    } else {
        // POST oder GET ohne redirect: JSON-Antwort
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
    error_log("Setup Session Fehler: " . $e->getMessage());
    
    if ($isGET && $redirect === 'test') {
        // GET-Fehler: Zurück zu name_form.php mit Fehler
        $errorMsg = urlencode("Fehler: " . $e->getMessage());
        header("Location: name_form.php?code=" . urlencode($testCode ?? '') . "&error=" . $errorMsg);
        exit();
    } else {
        // POST-Fehler: JSON-Antwort
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
