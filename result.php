<?php
session_start();

// Überprüfe, ob Testergebnisse vorhanden sind
if (!isset($_SESSION['test_results'])) {
    header("Location: index.php");
    exit();
}

// Prüfe, ob XML-Download explizit angefordert wurde (über einen Button, nicht automatisch)
if (isset($_GET['download']) && $_GET['download'] == 'xml' && isset($_SESSION['download_xml_file']) && file_exists($_SESSION['download_xml_file'])) {
    $file_path = $_SESSION['download_xml_file'];
    $file_name = $_SESSION['download_xml_filename'];
    
    // Stelle sicher, dass die Datei existiert und lesbar ist
    if (file_exists($file_path) && is_readable($file_path)) {
        // Setze die richtigen Header für den Download
        header('Content-Description: File Transfer');
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        
        // Lese und sende die Datei
        readfile($file_path);
        
        // Beende das Skript nach dem Download
        exit;
    }
}

// Wenn der "Zurück zur Startseite" Button geklickt wurde
if (isset($_POST['back_to_home'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testergebnis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-primary: #1f2937;
            --border-color: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--card-background);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }
        
        .result-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .result-card {
            padding: 20px;
            background-color: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .percentage-display {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            margin: 20px 0;
        }
        
        .grade-display {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border-radius: 8px;
            background-color: var(--primary-color);
            color: white;
        }
        
        .score-details {
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-success:hover {
            background-color: #059669;
            border-color: #059669;
        }
        
        .btn-seb-exit {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            border: none;
            color: white;
            padding: 15px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        
        .btn-seb-exit:hover {
            background: linear-gradient(45deg, #e55a2b, #e8821a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
            color: white;
        }
        
        .btn-seb-exit i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-header">
            <h1>Testergebnis</h1>
                        </div>
                        
        <div class="result-card">
            <div class="percentage-display">
                                            <?php echo $_SESSION['test_results']['percentage']; ?>%
                                        </div>
            
            <div class="grade-display">
                <?php echo $_SESSION['test_results']['grade']; ?>
                        </div>
                        
            <div class="score-details">
                Erreichte Punkte: <?php echo $_SESSION['test_results']['achieved']; ?> von <?php echo $_SESSION['test_results']['max']; ?>
                            </div>
                        </div>
                        
        <div class="action-buttons">
            <a href="result.php?download=xml" class="btn btn-success">XML-Ergebnis herunterladen</a>
            
            <!-- SEB-Exit Button (nur im SEB) oder Back-Button (normaler Browser) -->
            <div id="dynamicActionButton">
                <!-- Wird von JavaScript gefüllt -->
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SEB AUTO-EXIT NACH TEST-ABSCHLUSS -->
    <script>
        // 🚪 SEB AUTO-EXIT NACH TESTERGEBNIS
        function handleSEBAutoExit() {
            const userAgent = navigator.userAgent;
            const isSEB = userAgent.includes('SEB') || userAgent.includes('SafeExamBrowser');
            
            if (!isSEB) {
                console.log('🔒 Nicht im SEB - kein Auto-Exit');
                return;
            }
            
            console.log('🚪 Test abgeschlossen - SEB Auto-Exit wird gestartet...');
            
            // Prüfe ob Auto-Exit vorbereitet wurde
            fetch('seb_auto_exit_check.php')
                .then(response => response.json())
                .then(data => {
                    if (data.auto_exit_prepared) {
                        console.log('✅ Auto-Exit war vorbereitet - starte SEB-Beendigung');
                        
                        // Zeige Beendigungs-Modal für 5 Sekunden
                        showSEBExitModal();
                        
                        // SEB automatisch beenden nach 5 Sekunden
                        setTimeout(() => {
                            exitSEB();
                        }, 5000);
                        
                    } else {
                        console.log('ℹ️ Auto-Exit nicht vorbereitet - SEB läuft weiter');
                    }
                })
                .catch(error => {
                    console.error('❌ Auto-Exit Check Fehler:', error);
                });
        }
        
        function showSEBExitModal() {
            // Erstelle Modal-HTML
            const modalHTML = `
                <div class="modal fade" id="sebExitModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-check-circle me-2"></i>Test erfolgreich abgeschlossen
                                </h5>
                            </div>
                            <div class="modal-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
                                </div>
                                <h6>SEB wird automatisch beendet...</h6>
                                <div class="progress mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%" id="exitProgress">
                                    </div>
                                </div>
                                <p class="small text-muted">Sie können das Gerät nach dem Beenden normal nutzen.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" onclick="cancelAutoExit()">
                                    Abbrechen (SEB weiterlaufen lassen)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Modal zum DOM hinzufügen
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Modal anzeigen
            const modal = new bootstrap.Modal(document.getElementById('sebExitModal'));
            modal.show();
            
            // Progress-Bar animieren
            animateProgressBar();
        }
        
        function animateProgressBar() {
            const progressBar = document.getElementById('exitProgress');
            let progress = 0;
            
            const interval = setInterval(() => {
                progress += 2; // 2% pro 100ms = 5 Sekunden
                progressBar.style.width = progress + '%';
                
                if (progress >= 100) {
                    clearInterval(interval);
                }
            }, 100);
        }
        
        function exitSEB() {
            console.log('🚪 Beende SEB automatisch...');
            
            // Versuche verschiedene SEB-Exit-Methoden
            try {
                // Methode 1: SEB beenden ohne Passwort (falls konfiguriert)
                if (window.seb && window.seb.quit) {
                    window.seb.quit();
                    return;
                }
                
                // Methode 2: JavaScript-basierter Exit
                window.location.href = 'seb-exit://';
                
                // Methode 3: Weiterleitung zur Exit-Seite
                setTimeout(() => {
                    window.location.href = 'seb_exit_page.php';
                }, 1000);
                
            } catch (error) {
                console.error('❌ SEB Auto-Exit fehlgeschlagen:', error);
                console.log('ℹ️ Fallback: Manueller Exit erforderlich');
            }
        }
        
        function cancelAutoExit() {
            console.log('🛑 Auto-Exit abgebrochen von Benutzer');
            const modal = bootstrap.Modal.getInstance(document.getElementById('sebExitModal'));
            modal.hide();
        }
        
        // Initialisierung nach DOM-Load
        document.addEventListener('DOMContentLoaded', function() {
            // Prüfe ob SEB läuft und zeige entsprechenden Button
            const userAgent = navigator.userAgent;
            const isSEB = userAgent.includes('SEB') || userAgent.includes('SafeExamBrowser');
            const isIpad = userAgent.includes('iPad');
            const testCode = '<?php echo $_SESSION['test_code'] ?? 'UNKNOWN'; ?>';
            
            const dynamicButtonContainer = document.getElementById('dynamicActionButton');
            
            if (isSEB) {
                console.log('🔒 SEB erkannt - zeige orangen SEB-Exit Button');
                
                // Oranger SEB-Exit Button
                dynamicButtonContainer.innerHTML = `
                    <button onclick="exitSEBNow()" class="btn btn-seb-exit">
                        <i class="bi bi-power"></i>SEB beenden
                    </button>
                `;
                
            } else {
                console.log('🌐 Normaler Browser - zeige Zurück-Button');
                
                // Normaler Zurück-Button (wie vorher)
                dynamicButtonContainer.innerHTML = `
                    <form method="post" action="" style="margin: 0;">
                        <button type="submit" name="back_to_home" class="btn btn-primary">
                            <i class="bi bi-house"></i> Zurück zur Startseite
                        </button>
                    </form>
                `;
            }
        });
        
        // SEB-Exit Funktion - DIREKT ohne Umwege
        function exitSEBNow() {
            console.log('🚪 Benutzer klickt orangen SEB-Exit Button - direkter Exit');
            
            // Button-Feedback
            const btn = document.querySelector('.btn-seb-exit');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i>SEB wird beendet...';
            btn.disabled = true;
            
            // DIREKTE SEB-EXIT-METHODEN (ohne Alert/Umwege)
            let exitSuccessful = false;
            
            try {
                console.log('🔧 Versuche direkten SEB-Exit...');
                
                // Methode 1: Direkte seb://quit URL (sofort)
                console.log('  → Methode 1: seb://quit (direkt)');
                window.location.href = 'seb://quit';
                
                // Auch iframe-Methode parallel versuchen
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = 'seb://quit';
                document.body.appendChild(iframe);
                
                exitSuccessful = true;
                
                // Fallback-Methoden für verschiedene SEB-Versionen
                setTimeout(() => {
                    if (!exitSuccessful) {
                        console.log('  → Methode 2: safeexambrowser://quit');
                        window.location.href = 'safeexambrowser://quit';
                    }
                }, 500);
                
                setTimeout(() => {
                    if (!exitSuccessful) {
                        console.log('  → Methode 3: seb-quit://');
                        window.location.href = 'seb-quit://';
                    }
                }, 1000);
                
                setTimeout(() => {
                    if (!exitSuccessful) {
                        console.log('  → Methode 4: seb://exit');
                        window.location.href = 'seb://exit';
                    }
                }, 1500);
                
                // iPad-spezifische Methode
                const isIpad = navigator.userAgent.includes('iPad');
                if (isIpad) {
                    setTimeout(() => {
                        if (!exitSuccessful) {
                            console.log('  → iPad-Methode: Touch-Exit-Event');
                            try {
                                // Versuche verschiedene iPad-spezifische Exit-Events
                                if (typeof TouchEvent !== 'undefined' && typeof Touch !== 'undefined') {
                                    const event = new TouchEvent('touchstart', {
                                        touches: [
                                            new Touch({ identifier: 1, target: document.body, clientX: 100, clientY: 100 }),
                                            new Touch({ identifier: 2, target: document.body, clientX: 200, clientY: 100 }),
                                            new Touch({ identifier: 3, target: document.body, clientX: 300, clientY: 100 })
                                        ]
                                    });
                                    document.dispatchEvent(event);
                                }
                                
                                // Alternative: Custom SEB-Events für iPad
                                window.dispatchEvent(new CustomEvent('seb-ios-quit', { 
                                    detail: { source: 'result-button', password: 'admin123' } 
                                }));
                                
                            } catch (touchError) {
                                console.warn('Touch-Event fehlgeschlagen:', touchError);
                            }
                        }
                    }, 2000);
                }
                
                // Letzter Fallback: Window-Close
                setTimeout(() => {
                    if (!exitSuccessful) {
                        console.log('  → Fallback: window.close()');
                        window.close();
                    }
                }, 3000);
                
                // Wenn nach 5 Sekunden nichts passiert ist, zeige einfachen Hinweis
                setTimeout(() => {
                    if (!exitSuccessful) {
                        console.log('⚠️ Alle direkten Exit-Methoden fehlgeschlagen');
                        btn.innerHTML = '<i class="bi bi-info-circle"></i>Bitte SEB manuell beenden';
                        btn.style.background = 'linear-gradient(45deg, #ffc107, #fd7e14)';
                        
                        // Einfacher, nicht-störender Hinweis
                        const hint = document.createElement('div');
                        hint.style.cssText = `
                            position: fixed; 
                            bottom: 20px; 
                            right: 20px; 
                            background: #343a40; 
                            color: white; 
                            padding: 15px; 
                            border-radius: 8px; 
                            font-size: 14px;
                            z-index: 9999;
                            max-width: 300px;
                        `;
                        hint.innerHTML = `
                            <strong>SEB beenden:</strong><br>
                            • Desktop: Rechtsklick auf SEB → "Beenden"<br>
                            • iPad: 3-Finger-Triple-Tap<br>
                            • Passwort: admin123
                        `;
                        document.body.appendChild(hint);
                        
                        // Hinweis nach 10 Sekunden automatisch entfernen
                        setTimeout(() => {
                            if (hint.parentNode) {
                                hint.parentNode.removeChild(hint);
                            }
                        }, 10000);
                    }
                }, 5000);
                
            } catch (error) {
                console.error('❌ Fehler beim direkten SEB-Exit:', error);
                btn.innerHTML = '<i class="bi bi-exclamation-triangle"></i>Manuell beenden';
                btn.style.background = 'linear-gradient(45deg, #dc3545, #fd7e14)';
            }
        }
        
        console.log('🚪 SEB Auto-Exit System geladen');
    </script>
</body>
</html> 