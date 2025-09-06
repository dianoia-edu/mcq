<?php
$testCode = $_GET['code'] ?? 'TEST';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Workflow - Einfache Anleitung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .workflow-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }
        .step-card {
            border-left: 5px solid #28a745;
            margin-bottom: 2rem;
        }
        .step-number {
            background: #28a745;
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
        .download-box {
            background: #e3f2fd;
            border: 2px dashed #2196f3;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
        }
        .success-badge {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Header -->
                <div class="workflow-card">
                    <div class="card-header bg-success text-white text-center py-4">
                        <h1><i class="bi bi-check-circle me-3"></i>SEB-Workflow VEREINFACHT</h1>
                        <h4>Ohne Passwort-Konflikte, ohne URL-Schema</h4>
                        <span class="success-badge">✅ FUNKTIONIERT GARANTIERT</span>
                    </div>
                </div>

                <!-- Step 1 -->
                <div class="workflow-card">
                    <div class="card-body step-card">
                        <div class="d-flex align-items-center">
                            <div class="step-number">1</div>
                            <div>
                                <h3>SEB-Datei herunterladen</h3>
                                <p class="text-muted mb-0">Laden Sie die Konfigurationsdatei für den Test herunter</p>
                            </div>
                        </div>
                        
                        <div class="download-box mt-4">
                            <h4><i class="bi bi-download text-primary me-2"></i>Download bereit</h4>
                            <p>Klicken Sie hier um die SEB-Konfiguration herunterzuladen:</p>
                            <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-download me-2"></i>test_<?php echo htmlspecialchars($testCode); ?>.seb herunterladen
                            </a>
                            <p class="small text-muted mt-2">Die Datei wird auf Ihrem Gerät gespeichert</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="workflow-card">
                    <div class="card-body step-card">
                        <div class="d-flex align-items-center">
                            <div class="step-number">2</div>
                            <div>
                                <h3>SEB starten (OHNE Passwort)</h3>
                                <p class="text-muted mb-0">Starten Sie SEB mit den Standard-Einstellungen</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h5><i class="bi bi-exclamation-triangle me-2"></i>WICHTIG</h5>
                            <ul class="mb-0">
                                <li><strong>Desktop:</strong> SEB normal starten (keine besonderen Einstellungen)</li>
                                <li><strong>iPad:</strong> SEB-App öffnen (nicht "sicherer Modus")</li>
                                <li><strong>KEIN Beenden-Passwort setzen!</strong></li>
                                <li><strong>KEIN sicherer Modus aktivieren!</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="workflow-card">
                    <div class="card-body step-card">
                        <div class="d-flex align-items-center">
                            <div class="step-number">3</div>
                            <div>
                                <h3>SEB-Datei öffnen</h3>
                                <p class="text-muted mb-0">Die heruntergeladene .seb-Datei in SEB laden</p>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6><i class="bi bi-laptop me-2"></i>Desktop</h6>
                                    </div>
                                    <div class="card-body">
                                        <ol>
                                            <li>Rechtsklick in SEB</li>
                                            <li>"Konfiguration laden" wählen</li>
                                            <li>test_<?php echo htmlspecialchars($testCode); ?>.seb auswählen</li>
                                            <li>Oder: Datei per Doppelklick öffnen</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header" style="background: #007AFF; color: white;">
                                        <h6><i class="bi bi-tablet me-2"></i>iPad</h6>
                                    </div>
                                    <div class="card-body">
                                        <ol>
                                            <li>Datei in Downloads finden</li>
                                            <li>Auf .seb-Datei tippen</li>
                                            <li>"Mit SEB öffnen" wählen</li>
                                            <li>Automatisches Laden der Konfiguration</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="workflow-card">
                    <div class="card-body step-card">
                        <div class="d-flex align-items-center">
                            <div class="step-number">4</div>
                            <div>
                                <h3>Automatische Sicherheit</h3>
                                <p class="text-muted mb-0">SEB aktiviert alle Sicherheitsfeatures automatisch</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-success mt-3">
                            <h5><i class="bi bi-shield-check me-2"></i>Was automatisch passiert:</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul>
                                        <li>✅ Kiosk-Modus aktiviert</li>
                                        <li>✅ Alt+Tab gesperrt</li>
                                        <li>✅ Task-Manager blockiert</li>
                                        <li>✅ F-Tasten deaktiviert</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul>
                                        <li>✅ URL-Filter aktiv</li>
                                        <li>✅ iPad: Home-Button gesperrt</li>
                                        <li>✅ Screenshots verhindert</li>
                                        <li>✅ Test-URL geladen</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="workflow-card">
                    <div class="card-body">
                        <h3><i class="bi bi-tools text-warning me-2"></i>Falls Probleme auftreten</h3>
                        
                        <div class="accordion" id="troubleshootingAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        "Konfiguration wird nicht unterstützt"
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <strong>Lösung:</strong> Stellen Sie sicher, dass Sie die neueste SEB-Version verwenden. 
                                        Laden Sie die .seb-Datei erneut herunter.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                        "Keine neue Prüfung während laufender Prüfung"
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <strong>Lösung:</strong> SEB komplett beenden und neu starten. KEIN Beenden-Passwort in den SEB-Einstellungen setzen.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                        "SEB nicht im sicheren Modus"
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <strong>Das ist normal!</strong> Die Sicherheit kommt aus der .seb-Datei, nicht aus den SEB-Einstellungen. 
                                        Wenn die Konfiguration geladen ist, sind alle Sicherheitsfeatures aktiv.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="workflow-card">
                    <div class="card-body text-center">
                        <h5>Direkte Aktionen</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-success btn-lg w-100 mb-2">
                                    <i class="bi bi-download me-2"></i>SEB-Datei herunterladen
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="seb_config_preview.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info btn-lg w-100 mb-2">
                                    <i class="bi bi-eye me-2"></i>Einschränkungen ansehen
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <p class="text-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Dieser Workflow funktioniert ohne Passwort-Konflikte und URL-Schema-Probleme!</strong>
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
