<?php
/**
 * Einfache SEB Auto-Exit Lösung
 * Generiert eine HTML-Seite die SEB zum Beenden auffordert
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

// Debug-Logging
error_log("SEB Auto-Exit Simple: Test-Code=$testCode, SEB-erkannt=" . ($isSEB ? 'ja' : 'nein'));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test beendet - SEB Auto-Exit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 2rem;
            font-family: 'Segoe UI', sans-serif;
        }
        .exit-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .countdown {
            font-size: 3rem;
            font-weight: bold;
            color: #28a745;
            margin: 2rem 0;
        }
        .password-display {
            background: #fff3cd;
            border: 3px solid #ffc107;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .exit-btn {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            margin: 1rem;
            transition: all 0.3s ease;
        }
        .exit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="exit-container">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        <h1 class="mt-3">Test erfolgreich abgeschlossen!</h1>
        <p class="lead">Safe Exam Browser wird automatisch beendet...</p>
        
        <div class="countdown" id="countdown">5</div>
        
        <div class="password-display">
            <i class="bi bi-key me-2"></i>Beenden-Passwort:<br>
            <span style="color: #dc3545;"><?php echo htmlspecialchars($quitPassword); ?></span>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle me-2"></i>Was passiert jetzt?</h6>
            <ol class="text-start">
                <li>Countdown läuft ab (5 Sekunden)</li>
                <li>SEB wird automatisch zum Beenden aufgefordert</li>
                <li>Falls nötig: Passwort manuell eingeben</li>
                <li>Computer kann normal genutzt werden</li>
            </ol>
        </div>
        
        <div class="d-grid gap-2">
            <button onclick="exitNow()" class="exit-btn">
                <i class="bi bi-power me-2"></i>Sofort beenden
            </button>
            
            <button onclick="copyPassword()" class="btn btn-outline-primary">
                <i class="bi bi-clipboard me-2"></i>Passwort kopieren
            </button>
            
            <button onclick="cancelExit()" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-2"></i>Beenden abbrechen
            </button>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                <?php if ($isSEB): ?>
                    ✅ SEB erkannt - Auto-Exit aktiv
                <?php else: ?>
                    ⚠️ Nicht im SEB - Simulation läuft
                <?php endif; ?>
            </small>
        </div>
    </div>

    <script>
        let countdown = 5;
        let countdownInterval;
        let exitCancelled = false;
        
        // Starte Countdown
        function startCountdown() {
            countdownInterval = setInterval(() => {
                countdown--;
                document.getElementById('countdown').textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    if (!exitCancelled) {
                        exitNow();
                    }
                }
            }, 1000);
        }
        
        // SEB beenden - MEHRERE METHODEN
        function exitNow() {
            console.log('🚪 Starte SEB Auto-Exit...');
            clearInterval(countdownInterval);
            
            const password = '<?php echo htmlspecialchars($quitPassword); ?>';
            const testCode = '<?php echo htmlspecialchars($testCode); ?>';
            
            // Zeige sofortigen Feedback
            document.getElementById('countdown').textContent = 'BEENDEN...';
            document.getElementById('countdown').style.color = '#dc3545';
            
            // 🔧 METHODE 1: SEB-spezifische URLs
            const sebExitUrls = [
                'seb-quit://',
                'seb://quit',
                'seb://exit',
                'safeexambrowser://quit'
            ];
            
            sebExitUrls.forEach((url, index) => {
                setTimeout(() => {
                    try {
                        console.log(`🔧 Versuche Exit-URL ${index + 1}: ${url}`);
                        window.location.href = url;
                    } catch (e) {
                        console.warn(`⚠️ Exit-URL ${index + 1} fehlgeschlagen:`, e);
                    }
                }, index * 500);
            });
            
            // 🔧 METHODE 2: JavaScript-Events
            setTimeout(() => {
                try {
                    console.log('🔧 Versuche JavaScript-Events...');
                    
                    // Custom Events
                    window.dispatchEvent(new CustomEvent('seb-quit', {
                        detail: { password: password, testCode: testCode }
                    }));
                    
                    // Keyboard Events
                    document.dispatchEvent(new KeyboardEvent('keydown', {
                        key: 'F4',
                        altKey: true,
                        bubbles: true
                    }));
                    
                } catch (e) {
                    console.warn('⚠️ JavaScript-Events fehlgeschlagen:', e);
                }
            }, 2000);
            
            // 🔧 METHODE 3: PHP-basierter Exit
            setTimeout(() => {
                console.log('🔧 Versuche PHP-basierten Exit...');
                
                fetch('seb_force_exit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `test_code=${encodeURIComponent(testCode)}&password=${encodeURIComponent(password)}`
                })
                .then(response => response.text())
                .then(data => {
                    console.log('✅ PHP-Exit Response:', data);
                })
                .catch(error => {
                    console.warn('⚠️ PHP-Exit fehlgeschlagen:', error);
                });
            }, 3000);
            
            // 🔧 METHODE 4: Window-Close (letzter Versuch)
            setTimeout(() => {
                console.log('🔧 Versuche Window-Close...');
                try {
                    window.close();
                } catch (e) {
                    console.warn('⚠️ Window-Close fehlgeschlagen:', e);
                }
                
                // Zeige manuelle Anweisungen
                showManualInstructions();
            }, 5000);
        }
        
        function showManualInstructions() {
            document.querySelector('.exit-container').innerHTML = `
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                <h2 class="mt-3">Manuelles Beenden erforderlich</h2>
                <div class="password-display">
                    <i class="bi bi-key me-2"></i>Beenden-Passwort:<br>
                    <span style="color: #dc3545;"><?php echo htmlspecialchars($quitPassword); ?></span>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="bi bi-info-circle me-2"></i>So beenden Sie SEB manuell:</h6>
                    <ol class="text-start">
                        <li><strong>Desktop:</strong> Rechtsklick auf SEB-Icon → "SEB beenden"</li>
                        <li><strong>iPad:</strong> 3-Finger-Triple-Tap → SEB-Menü</li>
                        <li>Passwort eingeben (siehe oben)</li>
                        <li>Mit "OK" bestätigen</li>
                    </ol>
                </div>
                <button onclick="copyPassword()" class="btn btn-primary btn-lg">
                    <i class="bi bi-clipboard me-2"></i>Passwort kopieren
                </button>
            `;
        }
        
        function copyPassword() {
            const password = '<?php echo htmlspecialchars($quitPassword); ?>';
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(password)
                    .then(() => alert('✅ Passwort kopiert!'))
                    .catch(() => fallbackCopy(password));
            } else {
                fallbackCopy(password);
            }
        }
        
        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                alert('✅ Passwort kopiert!');
            } catch (err) {
                alert('Passwort: ' + text);
            }
            
            document.body.removeChild(textArea);
        }
        
        function cancelExit() {
            exitCancelled = true;
            clearInterval(countdownInterval);
            document.getElementById('countdown').textContent = 'ABGEBROCHEN';
            document.getElementById('countdown').style.color = '#6c757d';
            
            console.log('🛑 Auto-Exit abgebrochen');
            
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        }
        
        // Debug-Info
        console.log('🚪 SEB Auto-Exit Simple geladen');
        console.log('📋 Test-Code:', '<?php echo htmlspecialchars($testCode); ?>');
        console.log('🔑 Passwort:', '<?php echo htmlspecialchars($quitPassword); ?>');
        console.log('📱 User-Agent:', navigator.userAgent.substring(0, 50) + '...');
        console.log('🔍 SEB erkannt:', <?php echo $isSEB ? 'true' : 'false'; ?>);
        
        // Starte Auto-Exit
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown();
        });
    </script>
</body>
</html>
