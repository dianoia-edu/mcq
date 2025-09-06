<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Konfigurationsprobleme lösen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .help-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .problem-card {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .solution-card {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
        .config-comparison {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="help-container">
        <h1><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>SEB-Konfigurationsprobleme lösen</h1>
        <p class="text-muted">Hilfe bei der Fehlermeldung "Laden von neuen SEB-Einstellungen nicht erlaubt"</p>
        
        <!-- Problem-Beschreibung -->
        <div class="problem-card">
            <h5><i class="bi bi-bug me-2"></i>Das Problem</h5>
            <p><strong>Fehlermeldung:</strong> "Das Laden von neuen SEB-Einstellungen ist nicht erlaubt"</p>
            <p><strong>Ursache:</strong> SEB läuft bereits mit einer anderen Konfiguration und blockiert neue Configs</p>
            <p class="mb-0"><strong>Symptom:</strong> QR-Code führt zu SEB, aber neue Konfiguration wird abgelehnt</p>
        </div>
        
        <!-- Lösungsschritte -->
        <div class="solution-card">
            <h5><i class="bi bi-check-circle me-2"></i>Sofort-Lösung</h5>
            
            <div class="d-flex align-items-start mb-3">
                <span class="step-number">1</span>
                <div>
                    <strong>SEB komplett schließen</strong><br>
                    <small class="text-muted">SEB über Taskmanager beenden oder Passwort eingeben (admin123)</small>
                </div>
            </div>
            
            <div class="d-flex align-items-start mb-3">
                <span class="step-number">2</span>
                <div>
                    <strong>QR-Code erneut scannen</strong><br>
                    <small class="text-muted">Mit komplett neuem SEB-Start wird die neue Konfiguration akzeptiert</small>
                </div>
            </div>
            
            <div class="d-flex align-items-start mb-3">
                <span class="step-number">3</span>
                <div>
                    <strong>Flexible Konfiguration verwenden</strong><br>
                    <small class="text-muted">Die neuen QR-Codes verwenden bereits eine flexible Konfiguration</small>
                </div>
            </div>
        </div>
        
        <!-- Konfigurationsvergleich -->
        <h5 class="mt-4">🔧 Neue flexible Konfiguration</h5>
        
        <div class="row">
            <div class="col-md-6">
                <div class="config-comparison">
                    <h6 class="text-danger">❌ Alte Config (problematisch)</h6>
                    <ul class="small mb-0">
                        <li><code>allowReconfiguration: false</code></li>
                        <li><code>forceReconfiguration: false</code></li>
                        <li><code>showReloadWarning: true</code></li>
                        <li>Strikte Sicherheitseinstellungen</li>
                        <li>Komplexe Passwort-Protection</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="config-comparison">
                    <h6 class="text-success">✅ Neue Config (flexibel)</h6>
                    <ul class="small mb-0">
                        <li><code>allowReconfiguration: true</code></li>
                        <li><code>forceReconfiguration: true</code></li>
                        <li><code>showReloadWarning: false</code></li>
                        <li>Minimale Sicherheitseinstellungen</li>
                        <li>Vereinfachte Passwort-Struktur</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Test-Links -->
        <div class="mt-4">
            <h6>🧪 Test verschiedene Konfigurationen</h6>
            
            <?php
            $testCode = $_GET['code'] ?? 'TEST';
            $baseUrl = 'http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
            $baseUrl = rtrim($baseUrl, '/');
            ?>
            
            <div class="d-flex gap-2 flex-wrap">
                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-warning" target="_blank">
                    <i class="bi bi-download me-2"></i>Standard-Config (.seb)
                </a>
                <a href="seb_config_flexible.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-success" target="_blank">
                    <i class="bi bi-download me-2"></i>Flexible-Config (.seb)
                </a>
                <a href="test_seb_direct_urls.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info" target="_blank">
                    <i class="bi bi-qr-code me-2"></i>QR-Code Test
                </a>
            </div>
        </div>
        
        <!-- Erweiterte Lösungen -->
        <div class="mt-4">
            <h6>🔧 Erweiterte Lösungsansätze</h6>
            
            <div class="accordion" id="advancedSolutions">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#solution1">
                            Option 1: SEB-Einstellungen zurücksetzen
                        </button>
                    </h2>
                    <div id="solution1" class="accordion-collapse collapse" data-bs-parent="#advancedSolutions">
                        <div class="accordion-body">
                            <ol>
                                <li>SEB komplett deinstallieren</li>
                                <li>Alle SEB-Konfigurationsdateien löschen</li>
                                <li>SEB neu installieren</li>
                                <li>Mit neuer .seb-Datei starten</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#solution2">
                            Option 2: SEB-Registry bereinigen (Windows)
                        </button>
                    </h2>
                    <div id="solution2" class="accordion-collapse collapse" data-bs-parent="#advancedSolutions">
                        <div class="accordion-body">
                            <ol>
                                <li>Registry-Editor öffnen (regedit)</li>
                                <li>Zu <code>HKEY_CURRENT_USER\Software\SafeExamBrowser</code> navigieren</li>
                                <li>SEB-Einstellungen löschen</li>
                                <li>SEB neu starten</li>
                            </ol>
                            <div class="alert alert-danger alert-sm">
                                <strong>⚠️ Warnung:</strong> Registry-Änderungen können das System beschädigen!
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#solution3">
                            Option 3: Admin-Passwort verwenden
                        </button>
                    </h2>
                    <div id="solution3" class="accordion-collapse collapse" data-bs-parent="#advancedSolutions">
                        <div class="accordion-body">
                            <ol>
                                <li>In SEB Ctrl+Alt+F9 drücken</li>
                                <li>Admin-Passwort eingeben: <code>admin123</code></li>
                                <li>SEB-Einstellungen ändern oder SEB beenden</li>
                                <li>Neue Konfiguration laden</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Debug-Info -->
        <div class="mt-4 alert alert-info">
            <h6><i class="bi bi-info-circle me-2"></i>Debug-Information</h6>
            <p><strong>Aktueller Test-Code:</strong> <code><?php echo htmlspecialchars($testCode); ?></code></p>
            <p><strong>Flexible Config URL:</strong> <code><?php echo htmlspecialchars($baseUrl . '/seb_config_flexible.php?code=' . urlencode($testCode)); ?></code></p>
            <p class="mb-0"><strong>Direkte SEB-URL:</strong> <code>seb://<?php echo urlencode($baseUrl . '/seb_config_flexible.php?code=' . urlencode($testCode)); ?></code></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('🔧 SEB-Konfigurationsprobleme Hilfe geladen');
        console.log('📱 Test-Code:', '<?php echo $testCode; ?>');
    </script>
</body>
</html>
