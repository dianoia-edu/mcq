<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Manuelle Bereinigung - Ultimative Anleitung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .cleanup-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .danger-alert {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            border: none;
            color: white;
            font-weight: bold;
        }
        .step-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        .code-block {
            background: #2d3748;
            color: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        .platform-tab {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .platform-tab.active {
            background: #dc3545;
            color: white;
        }
        .platform-content {
            display: none;
        }
        .platform-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="cleanup-container">
        <h1><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>SEB Manuelle Bereinigung</h1>
        <p class="text-muted">Ultimative Anleitung für hartnäckige SEB-Konfigurationsprobleme</p>
        
        <div class="alert danger-alert">
            <h5><i class="bi bi-radioactive me-2"></i>NUKLEAR-OPTION ERFORDERLICH</h5>
            <p class="mb-0">Standard-Reset hat nicht funktioniert. SEB ist extrem hartnäckig und muss manuell bereinigt werden.</p>
        </div>
        
        <!-- Platform Selector -->
        <div class="mb-4">
            <h5>Wählen Sie Ihr System:</h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary platform-tab active" onclick="showPlatform('windows')">
                    <i class="bi bi-windows me-2"></i>Windows
                </button>
                <button type="button" class="btn btn-outline-primary platform-tab" onclick="showPlatform('mac')">
                    <i class="bi bi-apple me-2"></i>macOS
                </button>
                <button type="button" class="btn btn-outline-primary platform-tab" onclick="showPlatform('ios')">
                    <i class="bi bi-tablet me-2"></i>iOS/iPad
                </button>
            </div>
        </div>
        
        <!-- Windows Instructions -->
        <div id="windows-content" class="platform-content active">
            <h4><i class="bi bi-windows me-2"></i>Windows SEB-Bereinigung</h4>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">1</span>
                    <div>
                        <h6>SEB komplett beenden</h6>
                        <p>Öffnen Sie den Task-Manager (Ctrl+Shift+Esc) und beenden Sie alle SEB-Prozesse:</p>
                        <ul>
                            <li><code>SafeExamBrowser.exe</code></li>
                            <li><code>SafeExamBrowserService.exe</code></li>
                            <li><code>SebWindowsService.exe</code></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">2</span>
                    <div>
                        <h6>Registry bereinigen</h6>
                        <p>Öffnen Sie regedit.exe als Administrator und löschen Sie:</p>
                        <div class="code-block">
HKEY_CURRENT_USER\Software\SafeExamBrowser<br>
HKEY_LOCAL_MACHINE\SOFTWARE\SafeExamBrowser<br>
HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\SafeExamBrowser
                        </div>
                        <div class="alert alert-warning">
                            <strong>⚠️ Warnung:</strong> Erstellen Sie vorher ein Registry-Backup!
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">3</span>
                    <div>
                        <h6>AppData-Ordner löschen</h6>
                        <p>Löschen Sie alle SEB-Daten aus dem Benutzerverzeichnis:</p>
                        <div class="code-block">
%APPDATA%\SafeExamBrowser\<br>
%LOCALAPPDATA%\SafeExamBrowser\<br>
%TEMP%\SafeExamBrowser\<br>
%USERPROFILE%\Downloads\*.seb
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">4</span>
                    <div>
                        <h6>SEB-Konfigurationsdateien entfernen</h6>
                        <p>Suchen Sie nach allen .seb-Dateien und löschen Sie sie:</p>
                        <div class="code-block">
Desktop\*.seb<br>
Downloads\*.seb<br>
C:\ProgramData\SafeExamBrowser\*<br>
C:\Windows\Temp\SafeExamBrowser\*
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">5</span>
                    <div>
                        <h6>Windows neu starten</h6>
                        <p>Starten Sie Windows komplett neu, um alle Speicher-Reste zu entfernen.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- macOS Instructions -->
        <div id="mac-content" class="platform-content">
            <h4><i class="bi bi-apple me-2"></i>macOS SEB-Bereinigung</h4>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">1</span>
                    <div>
                        <h6>SEB beenden</h6>
                        <p>Beenden Sie SEB über Activity Monitor oder Terminal:</p>
                        <div class="code-block">
sudo killall "Safe Exam Browser"<br>
sudo killall SafeExamBrowser
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">2</span>
                    <div>
                        <h6>Preferences löschen</h6>
                        <p>Entfernen Sie alle SEB-Einstellungen:</p>
                        <div class="code-block">
rm -rf ~/Library/Preferences/org.safeexambrowser.*<br>
rm -rf ~/Library/Application\ Support/SafeExamBrowser/<br>
rm -rf ~/Library/Caches/org.safeexambrowser.*<br>
rm -rf ~/Library/Logs/SafeExamBrowser/
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">3</span>
                    <div>
                        <h6>Keychain bereinigen</h6>
                        <p>Entfernen Sie SEB-Einträge aus dem Keychain:</p>
                        <div class="code-block">
security delete-generic-password -s "SafeExamBrowser"<br>
security delete-generic-password -s "SEB"
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">4</span>
                    <div>
                        <h6>.seb-Dateien entfernen</h6>
                        <p>Suchen und löschen Sie alle .seb-Dateien:</p>
                        <div class="code-block">
find ~ -name "*.seb" -delete<br>
rm -rf ~/Downloads/*.seb<br>
rm -rf ~/Desktop/*.seb
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- iOS Instructions -->
        <div id="ios-content" class="platform-content">
            <h4><i class="bi bi-tablet me-2"></i>iOS/iPad SEB-Bereinigung</h4>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">1</span>
                    <div>
                        <h6>SEB-App komplett löschen</h6>
                        <p>Halten Sie die SEB-App gedrückt und wählen Sie "App löschen"</p>
                        <p>Dies entfernt alle App-Daten und Konfigurationen.</p>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">2</span>
                    <div>
                        <h6>Safari-Daten löschen</h6>
                        <p>Gehen Sie zu Einstellungen → Safari → Verlauf und Websitedaten löschen</p>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">3</span>
                    <div>
                        <h6>iPad neu starten</h6>
                        <p>Halten Sie Power + Home (oder Volume) für 10 Sekunden gedrückt</p>
                    </div>
                </div>
            </div>
            
            <div class="step-card">
                <div class="d-flex align-items-start">
                    <span class="step-number">4</span>
                    <div>
                        <h6>SEB-App neu installieren</h6>
                        <p>Installieren Sie SEB frisch aus dem App Store</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- After Cleanup -->
        <div class="mt-4">
            <h5>Nach der Bereinigung:</h5>
            
            <div class="alert alert-success">
                <h6><i class="bi bi-check-circle me-2"></i>Ultra-Reset-Konfiguration verwenden</h6>
                <p>Verwenden Sie die neue Ultra-Reset .seb-Datei:</p>
                <div class="d-flex gap-2">
                    <a href="seb_config_ultra_reset.php?code=<?php echo $_GET['code'] ?? 'TEST'; ?>" class="btn btn-danger" target="_blank">
                        <i class="bi bi-download me-2"></i>Ultra-Reset .seb herunterladen
                    </a>
                    <a href="seb_reset_strategies.php?code=<?php echo $_GET['code'] ?? 'TEST'; ?>" class="btn btn-warning" target="_blank">
                        <i class="bi bi-arrow-clockwise me-2"></i>Zurück zu Reset-Strategien
                    </a>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h6><i class="bi bi-lightbulb me-2"></i>Warum ist SEB so hartnäckig?</h6>
                <ul class="mb-0">
                    <li><strong>Sicherheitsfeature:</strong> SEB speichert Konfigurationen persistent</li>
                    <li><strong>Prüfungsintegrität:</strong> Verhindert Manipulation während Prüfungen</li>
                    <li><strong>Admin-Schutz:</strong> Erfordert bewusste Bereinigung</li>
                    <li><strong>Plattform-Integration:</strong> Tief im System verankert</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function showPlatform(platform) {
            // Alle Tabs deaktivieren
            document.querySelectorAll('.platform-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Alle Inhalte ausblenden
            document.querySelectorAll('.platform-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Ausgewählten Tab aktivieren
            event.target.closest('.platform-tab').classList.add('active');
            
            // Ausgewählten Inhalt anzeigen
            document.getElementById(platform + '-content').classList.add('active');
            
            console.log('🔄 Platform gewechselt zu:', platform);
        }
        
        console.log('🧹 SEB Manual Cleanup Guide geladen');
        console.log('💻 User-Agent:', navigator.userAgent);
    </script>
</body>
</html>
