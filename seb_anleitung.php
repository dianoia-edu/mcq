<?php
$testCode = $_GET['code'] ?? 'XXX';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Anleitung - Sicherer Modus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .instruction-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }
        .step-number {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .problem-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .solution-box {
            background: #d1edff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .password-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Header -->
                <div class="instruction-card">
                    <div class="card-header bg-warning text-dark text-center py-4">
                        <h1><i class="bi bi-exclamation-triangle me-3"></i>SEB Sicherer Modus Problem</h1>
                        <h4>QR-Code kann nicht gescannt werden</h4>
                    </div>
                </div>

                <!-- Problem -->
                <div class="instruction-card">
                    <div class="card-body">
                        <h3><i class="bi bi-bug text-danger me-2"></i>Das Problem</h3>
                        <div class="problem-box">
                            <h5>Szenario 1: SEB im sicheren Modus</h5>
                            <p><strong>❌ Problem:</strong> "Während einer laufenden Prüfung kann keine neue Prüfung gestartet werden"</p>
                            
                            <h5 class="mt-3">Szenario 2: SEB nicht im sicheren Modus</h5>
                            <p><strong>❌ Problem:</strong> QR-Code startet Test, aber SEB-Sicherheit ist nicht vollständig aktiv</p>
                        </div>
                    </div>
                </div>

                <!-- Lösung -->
                <div class="instruction-card">
                    <div class="card-body">
                        <h3><i class="bi bi-lightbulb text-success me-2"></i>Die Lösung</h3>
                        <div class="solution-box">
                            <h5>✅ Passwort-geschützter SEB mit Rekonfiguration</h5>
                            <p>Die neue SEB-Konfiguration erlaubt das Laden neuer Tests, aber nur mit Admin-Passwort.</p>
                        </div>
                    </div>
                </div>

                <!-- Workflow -->
                <div class="instruction-card">
                    <div class="card-body">
                        <h3><i class="bi bi-list-ol text-primary me-2"></i>Korrekter Workflow</h3>
                        
                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">1</div>
                            </div>
                            <div class="col">
                                <h5>SEB im NORMALEN Modus starten</h5>
                                <p class="mb-0">Starten Sie SEB <strong>ohne</strong> "sicheren Modus". Die Sicherheit kommt aus der .seb-Datei!</p>
                            </div>
                        </div>

                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">2</div>
                            </div>
                            <div class="col">
                                <h5>QR-Code scannen oder .seb-Datei öffnen</h5>
                                <p class="mb-0">Jetzt kann der QR-Code gescannt werden oder die .seb-Datei direkt geöffnet werden.</p>
                            </div>
                        </div>

                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">3</div>
                            </div>
                            <div class="col">
                                <h5>SEB wird automatisch gesichert</h5>
                                <p class="mb-0">Die .seb-Konfiguration aktiviert alle Sicherheitsfeatures automatisch.</p>
                            </div>
                        </div>

                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">4</div>
                            </div>
                            <div class="col">
                                <h5>Test läuft in sicherer Umgebung</h5>
                                <p class="mb-0">Kiosk-Modus, gesperrte Tasten, iPad-Sicherheit - alles ist aktiv!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Passwörter -->
                <div class="instruction-card">
                    <div class="card-body">
                        <h3><i class="bi bi-key text-warning me-2"></i>Notfall-Passwörter</h3>
                        <p>Falls Sie SEB beenden oder rekonfigurieren müssen:</p>
                        
                        <div class="password-box">
                            <h6>✅ Universelles Passwort für alle Tests</h6>
                            <p><strong>SEB beenden:</strong> <code>admin123</code></p>
                            <p><strong>Einstellungen/Admin:</strong> <code>admin123</code></p>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6><i class="bi bi-info-circle me-2"></i>Passwort verwenden:</h6>
                            <ol>
                                <li>Rechtsklick auf SEB-Icon (Desktop) oder 3-Finger-Triple-Tap (iPad)</li>
                                <li>"SEB beenden" oder "Einstellungen" wählen</li>
                                <li>Passwort eingeben wenn gefragt</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- iPad-spezifisch -->
                <div class="instruction-card">
                    <div class="card-body">
                        <h3><i class="bi bi-tablet text-primary me-2"></i>iPad-spezifische Hinweise</h3>
                        
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>iPad SEB-Einstellungen</h6>
                            <ul>
                                <li><strong>Guided Access:</strong> NICHT manuell aktivieren - wird automatisch gesetzt</li>
                                <li><strong>Sicherer Modus:</strong> NICHT aktivieren in den iPad-Einstellungen</li>
                                <li><strong>Automatischer Start:</strong> Lassen Sie die .seb-Datei die Sicherheit regeln</li>
                            </ul>
                        </div>

                        <div class="alert alert-success">
                            <h6><i class="bi bi-check-circle me-2"></i>Was automatisch aktiviert wird:</h6>
                            <ul>
                                <li>Home-Button gesperrt</li>
                                <li>App-Switcher blockiert</li>
                                <li>Kontrollzentrum deaktiviert</li>
                                <li>4-Finger-Gesten blockiert</li>
                                <li>Screenshots verhindert</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="instruction-card">
                    <div class="card-body text-center">
                        <h5>Schnelle Aktionen</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary w-100 mb-2">
                                    <i class="bi bi-download me-2"></i>.seb-Datei laden
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="seb_start.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-warning w-100 mb-2">
                                    <i class="bi bi-qr-code me-2"></i>QR-Code Seite
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="seb_config_preview.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info w-100 mb-2">
                                    <i class="bi bi-eye me-2"></i>Einschränkungen
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="teacher/teacher_dashboard.php" class="btn btn-secondary w-100 mb-2">
                                    <i class="bi bi-arrow-left me-2"></i>Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
