<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Client Settings Reparieren</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="bi bi-tools me-2"></i>SEB Client Settings Reparieren</h1>
        
        <div class="alert alert-success">
            <h6><i class="bi bi-check-circle me-2"></i>PROBLEM VERSTANDEN!</h6>
            <p><strong>Ihr Test zeigt:</strong></p>
            <ul class="mb-0">
                <li>✅ SebClientSettings.seb gelöscht → "keine sebconfig vorhanden"</li>
                <li>❌ SebClientSettings.seb wiederhergestellt → UriFormatException</li>
                <li><strong>→ SebClientSettings.seb MUSS existieren, ABER repariert werden!</strong></li>
            </ul>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5><i class="bi bi-exclamation-triangle me-2"></i>Problem in SebClientSettings.seb</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Beschädigte Server-URL in:</strong></p>
                        <code>C:\Users\kaaag\AppData\Roaming\SafeExamBrowser\SebClientSettings.seb</code>
                        
                        <p class="mt-3"><strong>Symptom:</strong></p>
                        <ul>
                            <li>UriFormatException in ServerOperation</li>
                            <li>4,876 KB Datei mit ungültiger Server-Konfiguration</li>
                            <li>Überschreibt unsere .seb-Einstellungen</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-download me-2"></i>Lösung: Saubere SebClientSettings.seb</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>1. Aktuelle Datei sichern:</strong></p>
                        <ol>
                            <li>SebClientSettings.seb → SebClientSettings_BACKUP.seb</li>
                        </ol>
                        
                        <p><strong>2. Saubere Datei herunterladen:</strong></p>
                        <div class="d-grid">
                            <a href="#" class="btn btn-success" onclick="downloadCleanClientSettings()">
                                <i class="bi bi-download me-2"></i>Saubere SebClientSettings.seb
                            </a>
                        </div>
                        
                        <p class="mt-3"><strong>3. Ersetzen:</strong></p>
                        <ul class="mb-0">
                            <li>Neue Datei in AppData\Roaming\SafeExamBrowser\ kopieren</li>
                            <li>SEB neu starten</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="bi bi-code-square me-2"></i>Saubere SebClientSettings.seb Generieren</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="generateCleanClientSettings()">
                                <i class="bi bi-gear me-2"></i>Saubere Client Settings Generieren
                            </button>
                        </div>
                        
                        <div id="generatedConfig" class="mt-3" style="display: none;">
                            <h6>Generierte saubere SebClientSettings.seb:</h6>
                            <textarea id="configOutput" class="form-control" rows="10" readonly></textarea>
                            <div class="mt-2">
                                <button class="btn btn-success" onclick="downloadConfig()">
                                    <i class="bi bi-download me-2"></i>Als .seb-Datei herunterladen
                                </button>
                                <button class="btn btn-outline-primary" onclick="copyConfig()">
                                    <i class="bi bi-clipboard me-2"></i>XML kopieren
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-info-circle me-2"></i>WICHTIG: Warum das funktioniert</h6>
                    <ul class="mb-0">
                        <li><strong>SEB Lade-Reihenfolge:</strong></li>
                        <li>1. SebClientSettings.seb (MUSS existieren, globale Basis-Einstellungen)</li>
                        <li>2. Spezifische .seb-Datei (unsere Exam-Konfiguration)</li>
                        <li><strong>Problem:</strong> Server-Einstellungen aus (1) können NICHT von (2) überschrieben werden</li>
                        <li><strong>Lösung:</strong> Saubere SebClientSettings.seb OHNE Server-Konfiguration</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-success">
                    <h6><i class="bi bi-check-circle-fill me-2"></i>Nach der Reparatur testen</h6>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="seb_config_override_server.php?code=TEST" class="btn btn-success" target="_blank">
                            <i class="bi bi-download me-2"></i>Server Override Config testen
                        </a>
                        <a href="seb_config_ultra_minimal.php?code=TEST" class="btn btn-primary" target="_blank">
                            <i class="bi bi-download me-2"></i>Ultra Minimal Config testen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generateCleanClientSettings() {
            const cleanConfig = `<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    
    <!-- ===== SAUBERE CLIENT-EINSTELLUNGEN (OHNE SERVER) ===== -->
    
    <key>sebMode</key>
    <integer>0</integer>
    
    <key>configPurpose</key>
    <integer>1</integer>
    
    <!-- ===== KEINE SERVER-KONFIGURATION ===== -->
    <!-- Alle sebServer* Keys absichtlich weggelassen -->
    
    <!-- ===== BASIC BROWSER SETTINGS ===== -->
    
    <key>browserWindowAllowReload</key>
    <true/>
    
    <key>browserWindowShowURL</key>
    <true/>
    
    <key>URLFilterEnable</key>
    <false/>
    
    <!-- ===== QUIT SETTINGS ===== -->
    
    <key>allowQuit</key>
    <true/>
    
    <key>hashedQuitPassword</key>
    <string></string>
    
    <!-- ===== LOGGING ===== -->
    
    <key>enableLogging</key>
    <false/>
    
    <key>logLevel</key>
    <integer>0</integer>
    
    <!-- ===== RECONFIGURATION ===== -->
    
    <key>allowReconfiguration</key>
    <true/>
    
    <key>forceReconfiguration</key>
    <false/>
    
    <!-- ===== ADMIN RIGHTS ===== -->
    
    <key>sebRequiresAdminRights</key>
    <false/>
    
    <key>sebLocalSettingsEnabled</key>
    <true/>
    
    <!-- ===== METADATA ===== -->
    
    <key>originatorName</key>
    <string>Clean Client Settings (No Server)</string>
    
</dict>
</plist>`;
            
            document.getElementById('configOutput').value = cleanConfig;
            document.getElementById('generatedConfig').style.display = 'block';
        }
        
        function downloadConfig() {
            const configContent = document.getElementById('configOutput').value;
            const blob = new Blob([configContent], { type: 'application/seb' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'SebClientSettings_CLEAN.seb';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            // Feedback
            const toast = document.createElement('div');
            toast.className = 'alert alert-success position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = '<i class="bi bi-check2 me-2"></i>SebClientSettings_CLEAN.seb heruntergeladen!';
            document.body.appendChild(toast);
            setTimeout(() => document.body.removeChild(toast), 3000);
        }
        
        function copyConfig() {
            const configContent = document.getElementById('configOutput').value;
            navigator.clipboard.writeText(configContent).then(() => {
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = '<i class="bi bi-check2 me-2"></i>XML in Zwischenablage kopiert!';
                document.body.appendChild(toast);
                setTimeout(() => document.body.removeChild(toast), 2000);
            }).catch(err => {
                prompt('Manuell kopieren:', configContent);
            });
        }
        
        function downloadCleanClientSettings() {
            generateCleanClientSettings();
            setTimeout(downloadConfig, 100);
        }
    </script>
</body>
</html>
