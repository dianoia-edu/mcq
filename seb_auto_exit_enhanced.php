<?php
/**
 * Erweiterte SEB Auto-Exit Lösung mit verbesserter SEB-Integration
 */

session_start();

// Test-Code aus verschiedenen Quellen
$testCode = $_GET['code'] ?? $_SESSION['test_code'] ?? $_POST['test_code'] ?? 'UNKNOWN';
$quitPassword = 'admin123';

// Prüfe ob SEB-Browser
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isSEB = (strpos($userAgent, 'SEB') !== false || 
          strpos($userAgent, 'SafeExamBrowser') !== false ||
          strpos($userAgent, 'SEB_iOS') !== false);

// Basis-URL ermitteln
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . '://' . $host . $baseDir . '/';

// Debug-Logging
error_log("SEB Auto-Exit Enhanced: Test-Code=$testCode, SEB-erkannt=" . ($isSEB ? 'ja' : 'nein'));

// Wenn nicht im SEB, leite zur normalen Startseite um
if (!$isSEB) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test beendet - SEB wird beendet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 1rem;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden; /* Verhindere Scrollen */
        }
        .exit-container {
            max-width: 700px;
            margin: 5vh auto;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
        }
        .countdown {
            font-size: 4rem;
            font-weight: 900;
            color: #dc3545;
            margin: 2rem 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .status-display {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .password-emergency {
            background: #fff3cd;
            border: 3px solid #ffc107;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
            font-family: 'Courier New', monospace;
            font-size: 1.3rem;
            font-weight: bold;
            display: none; /* Versteckt bis nötig */
        }
        .exit-methods {
            text-align: left;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        .method-status {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            background: #6c757d;
            animation: pulse 1s infinite;
        }
        .method-success { background: #28a745 !important; animation: none; }
        .method-failed { background: #dc3545 !important; animation: none; }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .debug-info {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
            font-family: monospace;
            max-width: 300px;
        }
    </style>
</head>
<body>
    <div class="exit-container">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
        <h1 class="mt-3 mb-4">🎉 Test erfolgreich abgeschlossen!</h1>
        
        <div class="status-display">
            <div id="exitStatus">
                <i class="bi bi-gear-fill me-2"></i>
                <span id="statusText">Safe Exam Browser wird automatisch beendet...</span>
            </div>
        </div>
        
        <div class="countdown" id="countdown">10</div>
        
        <div class="exit-methods">
            <h6><i class="bi bi-list-check me-2"></i>Exit-Methoden werden getestet:</h6>
            <div id="methodsList">
                <div><span class="method-status" id="method1"></span>SEB-URL-Schemas (seb://quit)</div>
                <div><span class="method-status" id="method2"></span>JavaScript-Events & Keyboard</div>
                <div><span class="method-status" id="method3"></span>HTTP-Headers & Force-Exit</div>
                <div><span class="method-status" id="method4"></span>Window-Close & Fallbacks</div>
            </div>
        </div>
        
        <div class="password-emergency" id="emergencyPassword">
            <h6><i class="bi bi-exclamation-triangle text-warning me-2"></i>Manuelles Beenden erforderlich</h6>
            <div class="mb-3">
                <strong>Beenden-Passwort:</strong><br>
                <span style="color: #dc3545; font-size: 1.5rem;"><?php echo htmlspecialchars($quitPassword); ?></span>
            </div>
            <small>
                <strong>Desktop:</strong> Rechtsklick auf SEB → "SEB beenden"<br>
                <strong>iPad:</strong> 3-Finger-Triple-Tap → SEB-Menü
            </small>
        </div>
        
        <div class="d-grid gap-2">
            <button onclick="copyPassword()" class="btn btn-warning btn-lg">
                <i class="bi bi-clipboard me-2"></i>Passwort kopieren
            </button>
        </div>
    </div>

    <div class="debug-info" id="debugInfo">
        SEB Auto-Exit Enhanced<br>
        Test: <?php echo htmlspecialchars($testCode); ?><br>
        SEB: <?php echo $isSEB ? 'Erkannt' : 'Nicht erkannt'; ?><br>
        Zeit: <span id="debugTime"></span>
    </div>

    <script>
        let countdown = 10;
        let countdownInterval;
        let methodIndex = 0;
        let exitSuccessful = false;
        
        const password = '<?php echo htmlspecialchars($quitPassword); ?>';
        const testCode = '<?php echo htmlspecialchars($testCode); ?>';
        const baseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';
        
        // Debug-Zeit aktualisieren
        function updateDebugTime() {
            document.getElementById('debugTime').textContent = new Date().toLocaleTimeString();
        }
        setInterval(updateDebugTime, 1000);
        updateDebugTime();
        
        // Countdown starten
        function startCountdown() {
            countdownInterval = setInterval(() => {
                countdown--;
                document.getElementById('countdown').textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    startExitSequence();
                }
            }, 1000);
        }
        
        // Exit-Sequenz starten
        function startExitSequence() {
            console.log('🚪 Starte erweiterte SEB Auto-Exit-Sequenz...');
            document.getElementById('countdown').textContent = 'BEENDEN...';
            document.getElementById('statusText').innerHTML = '<i class="bi bi-arrows-fullscreen me-2"></i>Exit-Methoden werden ausgeführt...';
            
            // Methode 1: SEB-URL-Schemas (sofort)
            executeMethod1();
            
            // Methode 2: JavaScript-Events (nach 2s)
            setTimeout(executeMethod2, 2000);
            
            // Methode 3: HTTP-Force-Exit (nach 4s)
            setTimeout(executeMethod3, 4000);
            
            // Methode 4: Fallbacks (nach 6s)
            setTimeout(executeMethod4, 6000);
            
            // Notfall-Anzeige (nach 10s)
            setTimeout(showEmergencyInstructions, 10000);
        }
        
        // METHODE 1: SEB-URL-Schemas
        function executeMethod1() {
            console.log('🔧 METHODE 1: SEB-URL-Schemas');
            document.getElementById('method1').className = 'method-status';
            
            const sebUrls = [
                `seb://quit?password=${encodeURIComponent(password)}`,
                'seb://quit',
                'seb-quit://',
                'safeexambrowser://quit',
                'seb://exit',
                'seb://close',
                'seb://terminate'
            ];
            
            sebUrls.forEach((url, index) => {
                setTimeout(() => {
                    try {
                        console.log(`  Versuche: ${url}`);
                        
                        // Iframe-Methode
                        const iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = url;
                        document.body.appendChild(iframe);
                        
                        setTimeout(() => {
                            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
                        }, 1000);
                        
                        // Direkter Location-Wechsel für die ersten URLs
                        if (index < 3) {
                            window.location.href = url;
                        }
                        
                    } catch (e) {
                        console.warn(`  URL-Schema fehlgeschlagen: ${url}`, e);
                    }
                }, index * 200);
            });
            
            setTimeout(() => {
                document.getElementById('method1').className = 'method-status method-failed';
            }, 3000);
        }
        
        // METHODE 2: JavaScript-Events
        function executeMethod2() {
            console.log('🔧 METHODE 2: JavaScript-Events');
            document.getElementById('method2').className = 'method-status';
            
            try {
                // SEB-spezifische Events
                const events = [
                    'seb-quit', 'sebQuit', 'SEB_QUIT', 'safexam-quit',
                    'browser-exit', 'application-quit', 'exam-terminate'
                ];
                
                events.forEach(eventName => {
                    const event = new CustomEvent(eventName, {
                        detail: { 
                            password: password, 
                            testCode: testCode,
                            action: 'quit',
                            timestamp: Date.now()
                        },
                        bubbles: true,
                        cancelable: true
                    });
                    
                    window.dispatchEvent(event);
                    document.dispatchEvent(event);
                });
                
                // Keyboard-Events
                const keyEvents = [
                    { key: 'F4', altKey: true },
                    { key: 'q', ctrlKey: true },
                    { key: 'w', ctrlKey: true },
                    { key: 'F10', ctrlKey: true },
                    { key: 'Escape', ctrlKey: true, altKey: true }
                ];
                
                keyEvents.forEach(keyCombo => {
                    document.dispatchEvent(new KeyboardEvent('keydown', {
                        ...keyCombo,
                        bubbles: true,
                        cancelable: true
                    }));
                });
                
                document.getElementById('method2').className = 'method-status method-failed';
                
            } catch (e) {
                console.warn('JavaScript-Events fehlgeschlagen:', e);
                document.getElementById('method2').className = 'method-status method-failed';
            }
        }
        
        // METHODE 3: HTTP-Force-Exit
        function executeMethod3() {
            console.log('🔧 METHODE 3: HTTP-Force-Exit');
            document.getElementById('method3').className = 'method-status';
            
            fetch('seb_force_exit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `test_code=${encodeURIComponent(testCode)}&password=${encodeURIComponent(password)}`
            })
            .then(response => {
                console.log('Force-Exit Response Status:', response.status);
                return response.text();
            })
            .then(data => {
                console.log('Force-Exit Response:', data.substring(0, 200));
                document.getElementById('method3').className = 'method-status method-failed';
            })
            .catch(error => {
                console.warn('Force-Exit fehlgeschlagen:', error);
                document.getElementById('method3').className = 'method-status method-failed';
            });
        }
        
        // METHODE 4: Fallback-Methoden
        function executeMethod4() {
            console.log('🔧 METHODE 4: Fallback-Methoden');
            document.getElementById('method4').className = 'method-status';
            
            try {
                // Window.close
                window.close();
                
                // History-Manipulation
                window.history.back();
                
                // Location-Manipulation
                window.location.replace('about:blank');
                
                document.getElementById('method4').className = 'method-status method-failed';
                
            } catch (e) {
                console.warn('Fallback-Methoden fehlgeschlagen:', e);
                document.getElementById('method4').className = 'method-status method-failed';
            }
        }
        
        // Notfall-Anweisungen anzeigen
        function showEmergencyInstructions() {
            if (exitSuccessful) return;
            
            console.log('⚠️ Auto-Exit fehlgeschlagen - zeige manuelle Anweisungen');
            
            document.getElementById('statusText').innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-2"></i>Automatisches Beenden fehlgeschlagen';
            document.getElementById('countdown').textContent = 'MANUELL';
            document.getElementById('emergencyPassword').style.display = 'block';
            
            // Alle Methoden als fehlgeschlagen markieren
            for (let i = 1; i <= 4; i++) {
                document.getElementById(`method${i}`).className = 'method-status method-failed';
            }
        }
        
        // Passwort kopieren
        function copyPassword() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(password)
                    .then(() => {
                        alert('✅ Passwort in Zwischenablage kopiert!\n\nJetzt: Rechtsklick → "SEB beenden" → Passwort einfügen');
                    })
                    .catch(() => fallbackCopy());
            } else {
                fallbackCopy();
            }
        }
        
        function fallbackCopy() {
            const textArea = document.createElement('textarea');
            textArea.value = password;
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                alert('✅ Passwort kopiert!\n\nPasswort: ' + password);
            } catch (err) {
                alert('📋 Passwort zum Kopieren:\n\n' + password);
            }
            
            document.body.removeChild(textArea);
        }
        
        // Sofortiges Beenden (falls gewünscht)
        function exitImmediately() {
            clearInterval(countdownInterval);
            countdown = 0;
            startExitSequence();
        }
        
        // Event-Listener für SEB-spezifische Events
        window.addEventListener('beforeunload', function(e) {
            exitSuccessful = true;
            console.log('✅ SEB wird beendet - beforeunload Event erkannt');
        });
        
        window.addEventListener('unload', function(e) {
            exitSuccessful = true;
            console.log('✅ SEB wird beendet - unload Event erkannt');
        });
        
        // Tastatur-Listener für manuelle Exit-Trigger
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key === 'F4') {
                exitSuccessful = true;
                console.log('✅ Alt+F4 erkannt - SEB wird beendet');
            }
            if (e.ctrlKey && e.key === 'q') {
                exitSuccessful = true;
                console.log('✅ Ctrl+Q erkannt - SEB wird beendet');
            }
        });
        
        // Initialisierung
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚪 SEB Auto-Exit Enhanced initialisiert');
            console.log('📋 Test-Code:', testCode);
            console.log('🔑 Passwort:', password);
            console.log('📱 User-Agent:', navigator.userAgent.substring(0, 50) + '...');
            
            startCountdown();
        });
    </script>
</body>
</html>
