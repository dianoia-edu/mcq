<?php
/**
 * SEB Auto-Exit Handler
 * Bereitet automatisches SEB-Beenden nach Test-Abschluss vor
 */

session_start();
header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    $testCode = $_POST['test_code'] ?? '';
    
    if ($action === 'prepare_exit') {
        // Markiere, dass Auto-Exit vorbereitet ist
        $_SESSION['seb_auto_exit_prepared'] = true;
        $_SESSION['seb_auto_exit_time'] = time();
        $_SESSION['seb_auto_exit_test'] = $testCode;
        
        error_log("SEB Auto-Exit vorbereitet für Test: $testCode");
        
        echo json_encode([
            'success' => true,
            'message' => 'Auto-Exit vorbereitet',
            'test_code' => $testCode
        ]);
        
    } else {
        throw new Exception('Unbekannte Aktion: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("SEB Auto-Exit Fehler: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
