<?php
/**
 * SEB Auto-Exit Check
 * Prüft ob Auto-Exit vorbereitet wurde
 */

session_start();
header('Content-Type: application/json');

try {
    $autoExitPrepared = isset($_SESSION['seb_auto_exit_prepared']) && $_SESSION['seb_auto_exit_prepared'] === true;
    $testCode = $_SESSION['seb_auto_exit_test'] ?? 'unknown';
    $preparedTime = $_SESSION['seb_auto_exit_time'] ?? 0;
    
    // Auto-Exit nur innerhalb von 10 Minuten nach Vorbereitung gültig
    $isValid = $autoExitPrepared && (time() - $preparedTime) < 600;
    
    if ($isValid) {
        // Lösche Auto-Exit Flag nach Verwendung
        unset($_SESSION['seb_auto_exit_prepared']);
        unset($_SESSION['seb_auto_exit_time']);
        unset($_SESSION['seb_auto_exit_test']);
        
        error_log("SEB Auto-Exit ausgeführt für Test: $testCode");
    }
    
    echo json_encode([
        'auto_exit_prepared' => $isValid,
        'test_code' => $testCode,
        'prepared_time' => $preparedTime,
        'current_time' => time()
    ]);
    
} catch (Exception $e) {
    error_log("SEB Auto-Exit Check Fehler: " . $e->getMessage());
    
    echo json_encode([
        'auto_exit_prepared' => false,
        'error' => $e->getMessage()
    ]);
}
?>
