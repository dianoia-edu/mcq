<?php
$testCode = $_GET['code'] ?? 'TEST';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Passwort Workflow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .workflow-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }
        .step-number {
            background: #007bff;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        .password-highlight {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 1.5rem;
            font-family: monospace;
            font-size: 1.2rem;
            text-align: center;
            margin: 1rem 0;
        }
        .method-card {
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin: 1rem 0;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Header -->
                <div class="workflow-card">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h1><i class="bi bi-key me-3"></i>SEB-Passwort Workflow</h1>
                        <h4>Sicherer Modus MIT Beenden-Passwort</h4>
                        <p class="mb-0">Test-Code: <strong><?php echo htmlspecialchars($testCode); ?></strong></p>
                    </div>
                </div>

                <!-- Warum Passwort? -->
                <div class="workflow-card">
                    <div class="card-body">
                        <h3><i class="bi bi-info-circle text-primary me-2"></i>Warum braucht SEB ein Beenden-Passwort?</h3>
                        
                        <div class="alert alert-info">
                            <h6><i class="bi bi-shield-lock me-2"></i>Sicherer Modus erfordert Passwort</h6>
                            <p class="mb-0">
                                Ohne Beenden-Passwort kann SEB nicht in den <strong>sicheren Modus</strong> wechseln. 
                                Der sichere Modus ist aber notwendig, um eine echte Klausurumgebung zu schaffen.
                            </p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6>❌ Ohne Passwort</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li>Kein sicherer Modus</li>
                                            <li>App-Switcher funktioniert</li>
                                            <li>Alt+Tab möglich</li>
                                            <li>Andere Programme startbar</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6>✅ Mit Passwort</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li>Sicherer Modus aktiv</li>
                                            <li>Vollständiger Kiosk-Modus</li>
                                            <li>Alle Tastenkombinationen gesperrt</li>
                                            <li>Echte Klausurumgebung</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Die Passwörter -->
                <div class="workflow-card">
                    <div class="card-body">
                        <h3><i class="bi bi-key text-warning me-2"></i>Ihre Passwörter für Test <?php echo htmlspecialchars($testCode); ?></h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="password-highlight">
                                    <h6>🔐 Beenden-Passwort</h6>
                                    <strong>LEHRER2024_<?php echo htmlspecialchars($testCode); ?></strong>
                                    <p class="small mt-2 mb-0">Zum Beenden von SEB nach dem Test</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="password-highlight">
                                    <h6>⚙️ Admin-Passwort</h6>
                                    <strong>ADMIN2024_<?php echo htmlspecialchars($testCode); ?></strong>
                                    <p class="small mt-2 mb-0">Für SEB-Einstellungen während des Tests</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Neue Workflow-Schritte -->
                <div class="workflow-card">
                    <div class="card-body">
                        <h3><i class="bi bi-list-ol text-primary me-2"></i>Korrekter Workflow mit Passwort</h3>
                        
                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">1</div>
                            </div>
                            <div class="col">
                                <h5>SEB-Konfiguration herunterladen</h5>
                                <p class="mb-0">Die neue .seb-Datei enthält jetzt das Beenden-Passwort.</p>
                                <div class="mt-2">
                                    <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary">
                                        <i class="bi bi-download me-2"></i>Neue SEB-Datei herunterladen
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">2</div>
                            </div>
                            <div class="col">
                                <h5>SEB normal starten</h5>
                                <p class="mb-0">Starten Sie SEB <strong>ohne</strong> Beenden-Passwort in den SEB-Einstellungen zu setzen!</p>
                                <div class="alert alert-warning mt-2">
                                    <small><strong>Wichtig:</strong> Das Passwort kommt aus der .seb-Datei, nicht aus den SEB-Einstellungen!</small>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">3</div>
                            </div>
                            <div class="col">
                                <h5>SEB-Datei öffnen</h5>
                                <p class="mb-0">Öffnen Sie die heruntergeladene .seb-Datei in SEB.</p>
                                <div class="alert alert-success mt-2">
                                    <small><strong>Ergebnis:</strong> SEB wechselt automatisch in den sicheren Modus!</small>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">4</div>
                            </div>
                            <div class="col">
                                <h5>Test durchführen</h5>
                                <p class="mb-0">Jetzt läuft der Test in der vollständig gesicherten Umgebung.</p>
                                <div class="method-card">
                                    <strong>Was jetzt gesperrt ist:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Alt+Tab, App-Switcher</li>
                                        <li>Task-Manager, andere Programme</li>
                                        <li>F-Tasten, ESC, Windows-Taste</li>
                                        <li>Rechtsklick, Kontextmenüs</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="step-number">5</div>
                            </div>
                            <div class="col">
                                <h5>SEB beenden (nach dem Test)</h5>
                                <p class="mb-0">Verwenden Sie das Beenden-Passwort um SEB zu verlassen.</p>
                                <div class="method-card">
                                    <strong>So geht's:</strong>
                                    <ol class="mb-0 mt-2">
                                        <li>Rechtsklick auf SEB-Icon oder Strg+F1</li>
                                        <li>"SEB beenden" wählen</li>
                                        <li>Passwort eingeben: <code>LEHRER2024_<?php echo htmlspecialchars($testCode); ?></code></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnose -->
                <div class="workflow-card">
                    <div class="card-body">
                        <h3><i class="bi bi-tools text-info me-2"></i>Testen Sie die Sicherheit</h3>
                        
                        <div class="alert alert-info">
                            <h6><i class="bi bi-check-circle me-2"></i>So prüfen Sie ob alles funktioniert:</h6>
                            <ol>
                                <li>Laden Sie die neue SEB-Konfiguration</li>
                                <li>Öffnen Sie sie in SEB</li>
                                <li>Besuchen Sie die <a href="seb_diagnose.php?code=<?php echo urlencode($testCode); ?>">Diagnose-Seite</a></li>
                                <li>Testen Sie Alt+Tab → sollte nicht funktionieren</li>
                                <li>Versuchen Sie andere Programme zu öffnen → sollte blockiert sein</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="workflow-card">
                    <div class="card-body text-center">
                        <h5>Schnelle Aktionen</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary btn-lg w-100 mb-2">
                                    <i class="bi bi-download me-2"></i>Neue SEB-Datei
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="seb_diagnose.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-warning btn-lg w-100 mb-2">
                                    <i class="bi bi-check-circle me-2"></i>Sicherheit testen
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="seb_config_preview.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info btn-lg w-100 mb-2">
                                    <i class="bi bi-eye me-2"></i>Einschränkungen
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="name_form.php?code=<?php echo urlencode($testCode); ?>&seb=true" class="btn btn-success btn-lg w-100 mb-2">
                                    <i class="bi bi-play-fill me-2"></i>Zum Test
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <p class="text-primary">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Mit Beenden-Passwort ist jetzt echter sicherer Modus möglich!</strong>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
