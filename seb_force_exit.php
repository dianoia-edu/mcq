<?php
/**
 * SEB Force Exit - Erzwingt SEB-Beendigung über verschiedene Methoden
 */

session_start();
header('Content-Type: text/plain');

try {
    $testCode = $_POST['test_code'] ?? $_GET['test_code'] ?? '';
    $password = $_POST['password'] ?? $_GET['password'] ?? '';
    
    error_log("SEB Force Exit: Test-Code=$testCode, Passwort-Length=" . strlen($password));
    
    // Verschiedene Exit-Strategien
    $strategies = [];
    
    // 1. HTML-Response mit SEB-Exit JavaScript
    $strategies[] = "
    <script>
        console.log('🔧 SEB Force Exit aktiviert');
        
        // Alle bekannten SEB-Exit-Methoden
        const exitMethods = [
            () => window.close(),
            () => window.location.href = 'seb-quit://',
            () => window.location.href = 'seb://quit',
            () => window.history.back(),
            () => { if(window.seb) window.seb.quit(); }
        ];
        
        exitMethods.forEach((method, i) => {
            setTimeout(method, i * 1000);
        });
        
        // Fallback: Zum Exit-Manual
        setTimeout(() => {
            window.location.href = 'seb_exit_page.php?code=' + encodeURIComponent('$testCode');
        }, 5000);
    </script>
    ";
    
    // 2. HTTP-Headers für SEB
    header('X-SEB-Quit: true');
    header('X-SEB-Password: ' . $password);
    header('X-SEB-Force-Exit: 1');
    header('Connection: close');
    
    // 3. Response Body
    echo "SEB-FORCE-EXIT-SIGNAL\n";
    echo "TEST: $testCode\n";
    echo "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
    echo "PASSWORD: $password\n";
    echo "\n";
    echo $strategies[0];
    
    // 4. Session-Cleanup
    session_destroy();
    
    error_log("SEB Force Exit: Signal gesendet für Test $testCode");
    
} catch (Exception $e) {
    error_log("SEB Force Exit Fehler: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage();
}
?>
