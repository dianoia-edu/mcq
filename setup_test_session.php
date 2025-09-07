<?php
/**
 * Session-Setup für Tests aus der SEB-Integration
 * Bereitet die Session korrekt vor, damit test.php funktioniert
 */

// Session starten
session_start();

// JSON-Header setzen
header('Content-Type: application/json');

// Fehlerberichterstattung
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Parameter aus POST holen
    $studentName = $_POST['student_name'] ?? '';
    $testCode = $_POST['test_code'] ?? '';
    $isSEB = ($_POST['seb'] ?? 'false') === 'true';
    
    // Validierung
    if (empty($studentName)) {
        throw new Exception('Schülername ist erforderlich');
    }
    
    if (empty($testCode)) {
        throw new Exception('Test-Code ist erforderlich');
    }
    
    // Finde Test-Datei (konsistent mit name_form.php)
    $testFile = "tests/" . $testCode . ".xml";
    
    // Mit Titel-Suffix suchen falls nicht gefunden
    if (!file_exists($testFile)) {
        $testPattern = "tests/" . $testCode . "_*.xml";
        $matchingFiles = glob($testPattern);
        
        if (!empty($matchingFiles)) {
            $testFile = $matchingFiles[0];
            error_log("Setup Session: Test gefunden mit Pattern: " . $testFile);
        }
    }
    
    if (!file_exists($testFile)) {
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
    
    // Erfolgreiche Antwort
    echo json_encode([
        'success' => true,
        'test_url' => $testUrl,
        'test_title' => $testTitle,
        'test_code' => $testCode,
        'student_name' => $studentName,
        'seb' => $isSEB
    ]);
    
} catch (Exception $e) {
    error_log("Setup Session Fehler: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
