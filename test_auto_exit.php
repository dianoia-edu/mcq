<?php
/**
 * Test der neuen Auto-Exit Funktion
 */

$testCode = $_GET['code'] ?? 'TEST123';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Exit Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>🧪 SEB Auto-Exit Test</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Test-Code:</strong> <?php echo htmlspecialchars($testCode); ?></p>
                        <p><strong>User-Agent:</strong> <small id="userAgent"></small></p>
                        <p><strong>SEB erkannt:</strong> <span id="sebStatus"></span></p>
                        
                        <div class="d-grid gap-2">
                            <a href="seb_auto_exit_simple.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-success">
                                🚪 Auto-Exit testen
                            </a>
                            
                            <a href="seb_force_exit.php?test_code=<?php echo urlencode($testCode); ?>&password=admin123" class="btn btn-warning">
                                🔧 Force-Exit testen
                            </a>
                            
                            <a href="seb_exit_page.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info">
                                📋 Exit-Page testen
                            </a>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Debug-Konsole:</h6>
                            <div id="debug" style="background: #f8f9fa; padding: 1rem; border-radius: 5px; font-family: monospace; font-size: 0.8rem; max-height: 200px; overflow-y: auto;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Debug-Funktionen
        const debugDiv = document.getElementById('debug');
        
        function debugLog(message) {
            const timestamp = new Date().toLocaleTimeString();
            debugDiv.innerHTML += `[${timestamp}] ${message}<br>`;
            debugDiv.scrollTop = debugDiv.scrollHeight;
            console.log(message);
        }
        
        // Browser-Info anzeigen
        document.getElementById('userAgent').textContent = navigator.userAgent.substring(0, 100) + '...';
        
        const isSEB = navigator.userAgent.includes('SEB') || navigator.userAgent.includes('SafeExamBrowser');
        document.getElementById('sebStatus').innerHTML = isSEB ? 
            '<span class="badge bg-success">✅ JA</span>' : 
            '<span class="badge bg-danger">❌ NEIN</span>';
        
        debugLog('🧪 Auto-Exit Test gestartet');
        debugLog('📱 SEB erkannt: ' + (isSEB ? 'JA' : 'NEIN'));
        debugLog('🔑 Test-Code: <?php echo htmlspecialchars($testCode); ?>');
        debugLog('🔐 Passwort: admin123 (universal)');
        
        // Test-Buttons mit Debug
        document.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', function(e) {
                debugLog('🔗 Klick auf: ' + this.textContent.trim());
                debugLog('📍 URL: ' + this.href);
            });
        });
    </script>
</body>
</html>
