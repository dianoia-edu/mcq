<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Workflow erklärt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .workflow-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .workflow-step {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            position: relative;
        }
        .step-number {
            position: absolute;
            top: -15px;
            left: 20px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .method-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
        .method-recommended {
            border-color: #28a745;
            background: linear-gradient(135deg, #f0fff4, #ffffff);
        }
        .method-alternative {
            border-color: #ffc107;
            background: linear-gradient(135deg, #fffbf0, #ffffff);
        }
        .method-manual {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5, #ffffff);
        }
    </style>
</head>
<body>
    <div class="workflow-container">
        <h1><i class="bi bi-diagram-3 me-2"></i>SEB-Workflow erklärt</h1>
        <p class="text-muted">Wie bekommt SEB die Konfiguration? Verschiedene Methoden erklärt</p>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-question-circle me-2"></i>Ihre Frage: "Wo bekommt man die .seb-Datei her?"</h6>
            <p class="mb-0">Excellente Frage! Es gibt mehrere Wege, wie SEB an seine Konfiguration kommt. Hier sind alle Optionen erklärt:</p>
        </div>
        
        <!-- Methode 1: Empfohlen -->
        <div class="method-card method-recommended">
            <h5><i class="bi bi-star-fill text-success me-2"></i>Methode 1: Automatische SEB-Konfiguration (EMPFOHLEN)</h5>
            <p><strong>Wie es funktioniert:</strong> QR-Code → SEB öffnet Test-URL → Server erkennt SEB → Automatische Config-Übertragung</p>
            
            <div class="workflow-step">
                <span class="step-number">1</span>
                <strong>Schüler scannt QR-Code:</strong>
                <code>seb://start?url=https://domain.de/name_form.php?code=ABC&seb=true</code>
            </div>
            
            <div class="workflow-step">
                <span class="step-number">2</span>
                <strong>SEB öffnet und ruft Test-URL auf</strong><br>
                Server erkennt SEB am User-Agent: <code>SafeExamBrowser</code>
            </div>
            
            <div class="workflow-step">
                <span class="step-number">3</span>
                <strong>Server sendet SEB-Config automatisch</strong><br>
                Via HTTP-Header: <code>seb-config-key</code> und <code>seb-file-header</code>
            </div>
            
            <div class="workflow-step">
                <span class="step-number">4</span>
                <strong>SEB wendet Konfiguration an</strong><br>
                Sichere Umgebung wird aktiviert, Test startet
            </div>
            
            <div class="alert alert-success">
                <strong>✅ Vorteile:</strong> Ein QR-Code, automatisch, keine manuellen Downloads
            </div>
        </div>
        
        <!-- Methode 2: Alternative -->
        <div class="method-card method-alternative">
            <h5><i class="bi bi-arrow-repeat text-warning me-2"></i>Methode 2: Zwei-Schritt-Verfahren</h5>
            <p><strong>Wie es funktioniert:</strong> Erst .seb-Datei herunterladen, dann öffnen</p>
            
            <div class="workflow-step">
                <span class="step-number">1</span>
                <strong>Schüler scannt QR-Code für .seb-Download:</strong>
                <code>seb://https://domain.de/seb_config.php?code=ABC</code>
            </div>
            
            <div class="workflow-step">
                <span class="step-number">2</span>
                <strong>SEB lädt .seb-Datei herunter</strong><br>
                Datei wird im Downloads-Ordner gespeichert
            </div>
            
            <div class="workflow-step">
                <span class="step-number">3</span>
                <strong>Schüler öffnet .seb-Datei</strong><br>
                Doppelklick oder "Öffnen mit SEB"
            </div>
            
            <div class="workflow-step">
                <span class="step-number">4</span>
                <strong>SEB startet mit Konfiguration</strong><br>
                Test-URL wird automatisch geladen
            </div>
            
            <div class="alert alert-warning">
                <strong>⚠️ Nachteile:</strong> Zwei Schritte, manuelle Interaktion erforderlich
            </div>
        </div>
        
        <!-- Methode 3: Manuell -->
        <div class="method-card method-manual">
            <h5><i class="bi bi-download text-danger me-2"></i>Methode 3: Manueller Download</h5>
            <p><strong>Wie es funktioniert:</strong> Klassischer Weg über Browser-Download</p>
            
            <div class="workflow-step">
                <span class="step-number">1</span>
                <strong>Lehrer gibt Download-Link:</strong>
                <code>https://domain.de/seb_config.php?code=ABC</code>
            </div>
            
            <div class="workflow-step">
                <span class="step-number">2</span>
                <strong>Schüler lädt .seb-Datei im Browser herunter</strong>
            </div>
            
            <div class="workflow-step">
                <span class="step-number">3</span>
                <strong>Schüler öffnet .seb-Datei</strong><br>
                SEB startet mit Konfiguration
            </div>
            
            <div class="alert alert-danger">
                <strong>❌ Nachteile:</strong> Umständlich, fehleranfällig, kein QR-Code
            </div>
        </div>
        
        <!-- Implementierung für Methode 1 -->
        <div class="mt-4">
            <h5>🔧 Implementierung der automatischen SEB-Konfiguration</h5>
            
            <div class="alert alert-info">
                <h6><i class="bi bi-code me-2"></i>Was noch implementiert werden muss:</h6>
                <p>Damit Methode 1 funktioniert, muss der Server SEB erkennen und automatisch die Konfiguration übertragen:</p>
            </div>
            
            <div class="d-flex gap-2">
                <a href="implement_auto_seb_config.php?code=<?php echo $_GET['code'] ?? 'TEST'; ?>" class="btn btn-success">
                    <i class="bi bi-gear me-2"></i>Auto-SEB-Config implementieren
                </a>
                <a href="seb_config.php?code=<?php echo $_GET['code'] ?? 'TEST'; ?>" class="btn btn-warning" target="_blank">
                    <i class="bi bi-download me-2"></i>.seb-Datei herunterladen (Methode 2)
                </a>
                <a href="seb_url_test.php?code=<?php echo $_GET['code'] ?? 'TEST'; ?>" class="btn btn-info" target="_blank">
                    <i class="bi bi-link me-2"></i>URL-Formate testen
                </a>
            </div>
        </div>
        
        <!-- Aktueller Status -->
        <div class="alert alert-warning mt-4">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Aktueller Status</h6>
            <p><strong>Derzeit implementiert:</strong> Methode 2 (Zwei-Schritt-Verfahren)</p>
            <p><strong>Empfehlung:</strong> Implementierung von Methode 1 für nahtlose User Experience</p>
            <p class="mb-0"><strong>Fallback:</strong> Methode 2 funktioniert bereits und ist eine solide Lösung</p>
        </div>
        
        <!-- Vergleichstabelle -->
        <div class="mt-4">
            <h6>📊 Methodenvergleich</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Methode</th>
                            <th>Schritte für Schüler</th>
                            <th>QR-Code</th>
                            <th>Automatisch</th>
                            <th>Fehlerrisiko</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-success">
                            <td><strong>Auto-Config</strong></td>
                            <td>1 (QR scannen)</td>
                            <td>✅</td>
                            <td>✅</td>
                            <td>Niedrig</td>
                        </tr>
                        <tr class="table-warning">
                            <td><strong>Zwei-Schritt</strong></td>
                            <td>2 (Scannen + Öffnen)</td>
                            <td>✅</td>
                            <td>Teilweise</td>
                            <td>Mittel</td>
                        </tr>
                        <tr class="table-danger">
                            <td><strong>Manuell</strong></td>
                            <td>3+ (URL, Download, Öffnen)</td>
                            <td>❌</td>
                            <td>❌</td>
                            <td>Hoch</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
