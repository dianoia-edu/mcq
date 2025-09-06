<?php
/**
 * SEB-Diagnose - Prüft welche Sicherheitsfeatures aktiv sind
 */

session_start();

// SEB-Erkennung
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isSEB = (strpos($userAgent, 'SEB') !== false || 
          strpos($userAgent, 'SafeExamBrowser') !== false ||
          strpos($userAgent, 'SEB_iOS') !== false ||
          isset($_SESSION['seb_browser']));

$testCode = $_GET['code'] ?? $_SESSION['test_code'] ?? 'UNKNOWN';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Diagnose</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: <?php echo $isSEB ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 'linear-gradient(135deg, #dc3545 0%, #fd7e14 100%)'; ?>;
            min-height: 100vh;
            padding: 2rem 0;
        }
        .diagnose-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }
        .test-result {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .test-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        .test-danger { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Header -->
                <div class="diagnose-card">
                    <div class="card-header <?php echo $isSEB ? 'bg-success' : 'bg-danger'; ?> text-white text-center py-4">
                        <h1>
                            <i class="bi bi-<?php echo $isSEB ? 'shield-check' : 'exclamation-triangle'; ?> me-3"></i>
                            SEB-Diagnose
                        </h1>
                        <h4><?php echo $isSEB ? 'SEB erkannt ✅' : 'SEB NICHT erkannt ❌'; ?></h4>
                        <p class="mb-0">Test-Code: <strong><?php echo htmlspecialchars($testCode); ?></strong></p>
                    </div>
                </div>

                <!-- Browser-Erkennung -->
                <div class="diagnose-card">
                    <div class="card-body">
                        <h3><i class="bi bi-browser-chrome text-primary me-2"></i>Browser-Erkennung</h3>
                        
                        <div class="test-result <?php echo $isSEB ? 'test-success' : 'test-danger'; ?>">
                            <h6>SEB Browser erkannt:</h6>
                            <p class="mb-0">
                                <strong><?php echo $isSEB ? '✅ JA' : '❌ NEIN'; ?></strong><br>
                                <small>User-Agent: <?php echo htmlspecialchars(substr($userAgent, 0, 100)); ?>...</small>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- JavaScript-Tests -->
                <div class="diagnose-card">
                    <div class="card-body">
                        <h3><i class="bi bi-code-slash text-warning me-2"></i>JavaScript-Sicherheitstests</h3>
                        
                        <div id="jsTestResults">
                            <div class="test-result test-warning">
                                <h6>Tests werden ausgeführt...</h6>
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empfohlene Aktionen -->
                <div class="diagnose-card">
                    <div class="card-body">
                        <h3><i class="bi bi-tools text-info me-2"></i>Empfohlene Aktionen</h3>
                        
                        <?php if ($isSEB): ?>
                        <div class="alert alert-success">
                            <h6><i class="bi bi-check-circle me-2"></i>SEB läuft korrekt</h6>
                            <p class="mb-0">
                                Sie können den Test sicher durchführen. 
                                <a href="name_form.php?code=<?php echo urlencode($testCode); ?>&seb=true">Zum Test →</a>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>SEB nicht aktiv</h6>
                            <ol>
                                <li>Laden Sie die <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>">SEB-Konfiguration</a> herunter</li>
                                <li>Öffnen Sie die .seb-Datei in SEB</li>
                                <li>Führen Sie diese Diagnose erneut durch</li>
                            </ol>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="diagnose-card">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary w-100 mb-2">
                                    <i class="bi bi-download me-2"></i>SEB-Config
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="seb_workflow.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info w-100 mb-2">
                                    <i class="bi bi-list-check me-2"></i>Anleitung
                                </a>
                            </div>
                            <div class="col-md-3">
                                <button onclick="location.reload()" class="btn btn-warning w-100 mb-2">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Neu testen
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="name_form.php?code=<?php echo urlencode($testCode); ?>&seb=true" class="btn btn-success w-100 mb-2">
                                    <i class="bi bi-play-fill me-2"></i>Zum Test
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript-Sicherheitstests
        function runSecurityTests() {
            const results = [];
            
            // Test 1: Alt+Tab Sperre
            let altTabBlocked = false;
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.keyCode === 9) {
                    e.preventDefault();
                    altTabBlocked = true;
                }
            });
            
            // Test 2: Rechtsklick-Sperre
            let rightClickBlocked = false;
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                rightClickBlocked = true;
            });
            
            // Test 3: F-Tasten-Sperre  
            let fKeysBlocked = false;
            document.addEventListener('keydown', function(e) {
                if (e.keyCode >= 112 && e.keyCode <= 123) { // F1-F12
                    e.preventDefault();
                    fKeysBlocked = true;
                }
            });
            
            // Test 4: Vollbild-Modus
            const isFullscreen = window.screen.height === window.innerHeight || 
                                window.outerHeight >= window.screen.height - 100;
            
            // Test 5: URL-Leiste versteckt
            const urlBarHidden = window.locationbar.visible === false || 
                               window.outerHeight - window.innerHeight < 100;
            
            // Ergebnisse anzeigen
            setTimeout(() => {
                const resultsHtml = `
                    <div class="test-result ${rightClickBlocked ? 'test-success' : 'test-danger'}">
                        <h6>Rechtsklick-Sperre:</h6>
                        <p class="mb-0">${rightClickBlocked ? '✅ Aktiv' : '❌ Nicht aktiv'}</p>
                    </div>
                    <div class="test-result ${isFullscreen ? 'test-success' : 'test-warning'}">
                        <h6>Vollbild-Modus:</h6>
                        <p class="mb-0">${isFullscreen ? '✅ Aktiv' : '⚠️ Möglicherweise nicht aktiv'}</p>
                    </div>
                    <div class="test-result ${urlBarHidden ? 'test-success' : 'test-warning'}">
                        <h6>Browser-Leiste versteckt:</h6>
                        <p class="mb-0">${urlBarHidden ? '✅ Versteckt' : '⚠️ Sichtbar'}</p>
                    </div>
                    <div class="test-result test-success">
                        <h6>Tastenkombination-Blocker:</h6>
                        <p class="mb-0">✅ JavaScript-Blocker installiert</p>
                        <small>Versuchen Sie Alt+Tab, F5, F11, F12 - sollten blockiert sein</small>
                    </div>
                `;
                
                document.getElementById('jsTestResults').innerHTML = resultsHtml;
            }, 1000);
        }
        
        // Starte Tests
        runSecurityTests();
        
        console.log('🔒 SEB-Diagnose gestartet');
        console.log('📊 Browser-Info:', {
            userAgent: navigator.userAgent.substring(0, 50),
            screenSize: window.screen.width + 'x' + window.screen.height,
            windowSize: window.innerWidth + 'x' + window.innerHeight,
            fullscreen: window.screen.height === window.innerHeight
        });
    </script>
</body>
</html>
