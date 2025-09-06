<?php
/**
 * SEB Exit Page
 * Finale Seite zum Beenden von SEB
 */

session_start();

// Teste Code aus Session oder GET
$testCode = $_SESSION['test_code'] ?? $_GET['code'] ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test abgeschlossen - SEB beenden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .exit-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        .btn-exit {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }
        .btn-exit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .password-display {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            font-family: monospace;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="exit-card">
        <i class="bi bi-check-circle success-icon"></i>
        <h1 class="h3 mb-3">Test erfolgreich abgeschlossen!</h1>
        <h2 class="h5 text-muted mb-4">Safe Exam Browser beenden</h2>
        
        <div class="alert alert-success">
            <h6><i class="bi bi-trophy me-2"></i>Herzlichen Glückwunsch!</h6>
            <p class="mb-0">Sie haben den Test erfolgreich abgeschlossen. Die Ergebnisse wurden gespeichert.</p>
        </div>
        
        <div class="password-display">
            <h6><i class="bi bi-key me-2"></i>Universelles Beenden-Passwort:</h6>
            <strong>admin123</strong>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle me-2"></i>So beenden Sie SEB:</h6>
            <ol class="text-start">
                <li><strong>Desktop:</strong> Rechtsklick auf SEB-Icon → "SEB beenden"</li>
                <li><strong>iPad:</strong> 3-Finger-Triple-Tap → SEB-Menü</li>
                <li>Passwort eingeben (siehe oben)</li>
                <li>Bestätigen</li>
            </ol>
        </div>
        
        <div class="d-grid gap-2">
            <button onclick="tryAutoExit()" class="btn-exit">
                <i class="bi bi-power me-2"></i>
                SEB automatisch beenden
            </button>
            
            <button onclick="copyPassword()" class="btn btn-outline-primary">
                <i class="bi bi-clipboard me-2"></i>
                Passwort kopieren
            </button>
            
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-house me-2"></i>
                Zur Startseite (ohne SEB beenden)
            </a>
        </div>
        
        <div class="mt-4">
            <p class="text-muted small">
                <i class="bi bi-shield-check me-1"></i>
                Der Test wurde sicher durchgeführt und alle Daten wurden gespeichert.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function tryAutoExit() {
            console.log('🚪 Versuche SEB automatisch zu beenden...');
            
            // Versuche verschiedene SEB-Exit-Methoden
            const methods = [
                () => {
                    // Methode 1: SEB-spezifische JavaScript-API
                    if (window.seb && typeof window.seb.quit === 'function') {
                        console.log('🔧 Methode 1: SEB.quit()');
                        window.seb.quit();
                        return true;
                    }
                    return false;
                },
                () => {
                    // Methode 2: Keyboard-Simulation (Strg+Q)
                    console.log('🔧 Methode 2: Keyboard Event');
                    const event = new KeyboardEvent('keydown', {
                        key: 'q',
                        ctrlKey: true,
                        bubbles: true
                    });
                    document.dispatchEvent(event);
                    return true;
                },
                () => {
                    // Methode 3: Custom Event
                        console.log('🔧 Methode 3: Custom Event');
                        const customEvent = new CustomEvent('seb-quit', {
                            detail: { password: 'admin123' }
                        });
                        window.dispatchEvent(customEvent);
                    return true;
                },
                () => {
                    // Methode 4: URL-Schema
                    console.log('🔧 Methode 4: URL Schema');
                    window.location.href = 'seb-quit://';
                    return true;
                }
            ];
            
            let success = false;
            for (const method of methods) {
                try {
                    if (method()) {
                        success = true;
                        break;
                    }
                } catch (error) {
                    console.warn('🔧 Methode fehlgeschlagen:', error);
                }
            }
            
            if (!success) {
                console.log('⚠️ Automatisches Beenden nicht möglich - zeige manuelle Anweisungen');
                alert('Automatisches Beenden nicht möglich.\n\nBitte verwenden Sie:\n• Rechtsklick → "SEB beenden"\n• Passwort: admin123');
            } else {
                console.log('✅ SEB-Exit erfolgreich ausgelöst');
            }
        }
        
        function copyPassword() {
            const password = 'admin123';
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(password).then(() => {
                    alert('Passwort wurde in die Zwischenablage kopiert!');
                }).catch(err => {
                    console.error('Clipboard-Fehler:', err);
                    fallbackCopy(password);
                });
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
                alert('Passwort wurde kopiert!');
            } catch (err) {
                console.error('Fallback-Copy fehlgeschlagen:', err);
                alert('Passwort: ' + text + '\n\nBitte manuell kopieren.');
            }
            
            document.body.removeChild(textArea);
        }
        
        console.log('🚪 SEB Exit Page geladen');
        console.log('🔑 Test-Code:', '<?php echo htmlspecialchars($testCode); ?>');
        console.log('📱 User-Agent:', navigator.userAgent.substring(0, 50));
    </script>
</body>
</html>
