<?php
/**
 * iPad-spezifische SEB-Exit Seite
 * WICHTIG: Auto-Exit ist auf iPad UNMÖGLICH - Benutzer muss aktiv klicken!
 */

session_start();

// Test-Code aus verschiedenen Quellen
$testCode = $_GET['code'] ?? $_SESSION['test_code'] ?? $_POST['test_code'] ?? 'UNKNOWN';

// Prüfe ob SEB-Browser
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isSEB = (strpos($userAgent, 'SEB') !== false || 
          strpos($userAgent, 'SafeExamBrowser') !== false ||
          strpos($userAgent, 'SEB_iOS') !== false);

$isIpad = (strpos($userAgent, 'iPad') !== false);

// Debug-Logging
error_log("SEB Manual Exit iPad: Test-Code=$testCode, SEB=" . ($isSEB ? 'ja' : 'nein') . ", iPad=" . ($isIpad ? 'ja' : 'nein'));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test beendet - SEB manuell beenden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 1rem;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }
        .exit-container {
            max-width: 600px;
            margin: 10vh auto;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
        }
        .success-icon {
            font-size: 6rem;
            color: #28a745;
            margin-bottom: 1rem;
            animation: checkmark 0.6s ease-in-out;
        }
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .exit-button {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
            border-radius: 20px;
            padding: 2rem 3rem;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin: 2rem 0;
            box-shadow: 0 15px 30px rgba(220, 53, 69, 0.4);
            transition: all 0.3s ease;
            animation: pulse-button 2s infinite;
        }
        .exit-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(220, 53, 69, 0.5);
            color: white;
        }
        @keyframes pulse-button {
            0% { box-shadow: 0 15px 30px rgba(220, 53, 69, 0.4); }
            50% { box-shadow: 0 15px 30px rgba(220, 53, 69, 0.7); }
            100% { box-shadow: 0 15px 30px rgba(220, 53, 69, 0.4); }
        }
        .instruction-box {
            background: #fff3cd;
            border: 3px solid #ffc107;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        .warning-box {
            background: #f8d7da;
            border: 3px solid #dc3545;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        .ipad-specific {
            background: #d1ecf1;
            border: 3px solid #17a2b8;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        .step-number {
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="exit-container">
        <i class="bi bi-check-circle-fill success-icon"></i>
        <h1 class="mb-4">🎉 Test erfolgreich abgeschlossen!</h1>
        
        <?php if ($isIpad): ?>
        <div class="ipad-specific">
            <h5><i class="bi bi-tablet me-2"></i>iPad-Hinweis</h5>
            <p class="mb-0">
                <strong>Automatisches Beenden ist auf iPad nicht möglich.</strong><br>
                Sie müssen SEB manuell über den Button unten beenden.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="warning-box">
            <h6><i class="bi bi-exclamation-triangle text-danger me-2"></i>WICHTIG: Klicken Sie auf den roten Button!</h6>
            <p class="mb-0">
                Der Safe Exam Browser kann sich auf iPad nicht automatisch beenden. 
                Sie <strong>müssen</strong> den Button unten anklicken, um SEB zu verlassen.
            </p>
        </div>
        
        <!-- GROSSER EXIT-BUTTON -->
        <a href="seb://quit" class="btn exit-button d-block" id="exitButton">
            <i class="bi bi-power me-3"></i>
            SEB JETZT BEENDEN
        </a>
        
        <div class="instruction-box">
            <h6><i class="bi bi-list-check me-2"></i>So beenden Sie SEB richtig:</h6>
            <div class="mt-3">
                <div class="mb-2">
                    <span class="step-number">1</span>
                    <strong>Klicken Sie den roten "SEB BEENDEN" Button oben</strong>
                </div>
                <div class="mb-2">
                    <span class="step-number">2</span>
                    SEB wird automatisch geschlossen
                </div>
                <div class="mb-2">
                    <span class="step-number">3</span>
                    iPad kehrt zum Home-Bildschirm zurück
                </div>
                <div>
                    <span class="step-number">4</span>
                    Alle Apps sind wieder verfügbar
                </div>
            </div>
        </div>
        
        <?php if ($isSEB): ?>
        <div class="alert alert-success">
            <small>
                ✅ SEB erkannt | Test: <?php echo htmlspecialchars($testCode); ?> | 
                <?php echo $isIpad ? 'iPad-Modus' : 'Desktop-Modus'; ?>
            </small>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <small>⚠️ Nicht im SEB - Simulation läuft</small>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Falls der Button nicht funktioniert: 3-Finger-Triple-Tap → SEB-Menü → "Beenden" → Passwort: admin123
            </small>
        </div>
    </div>

    <script>
        console.log('📱 SEB Manual Exit für iPad geladen');
        console.log('🔍 Test-Code:', '<?php echo htmlspecialchars($testCode); ?>');
        console.log('📱 iPad erkannt:', <?php echo $isIpad ? 'true' : 'false'; ?>);
        console.log('🔒 SEB erkannt:', <?php echo $isSEB ? 'true' : 'false'; ?>);
        
        // Button-Klick tracken
        document.getElementById('exitButton').addEventListener('click', function(e) {
            console.log('🚪 SEB-Exit Button geklickt - versuche seb://quit');
            
            // Visuelles Feedback
            this.innerHTML = '<i class="bi bi-hourglass-split me-3"></i>SEB WIRD BEENDET...';
            this.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
            
            // Zusätzlicher Fallback nach 2 Sekunden
            setTimeout(() => {
                if (window.location.href === window.location.href) {
                    console.log('⚠️ seb://quit hat nicht funktioniert - zeige Anleitung');
                    this.innerHTML = '<i class="bi bi-exclamation-triangle me-3"></i>MANUELL BEENDEN';
                    this.style.background = 'linear-gradient(45deg, #ffc107, #fd7e14)';
                    
                    alert('SEB konnte nicht automatisch beendet werden.\n\n' +
                          'Bitte verwenden Sie:\n' +
                          '• 3-Finger-Triple-Tap → SEB-Menü\n' +
                          '• Passwort: admin123\n' +
                          '• "Beenden" wählen');
                }
            }, 2000);
        });
        
        // Für Desktop: Alternative Exit-Methoden
        <?php if (!$isIpad): ?>
        // Desktop kann zusätzliche Exit-Methoden versuchen
        window.addEventListener('beforeunload', function() {
            console.log('✅ SEB wird beendet - beforeunload erkannt');
        });
        <?php endif; ?>
    </script>
</body>
</html>
