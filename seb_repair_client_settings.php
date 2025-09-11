<?php
/**
 * SEB Client Settings Reparatur Tool
 * 
 * Dieses Tool hilft dabei, beschädigte SebClientSettings.seb zu reparieren
 * indem es eine saubere XML-Version generiert.
 */

// Basis-Sicherheit
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'seb_repair_2024') {
    http_response_code(403);
    die('Zugriff verweigert');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Client Settings Reparatur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h3><i class="bi bi-tools"></i> SEB Client Settings Reparatur</h3>
                        <p class="mb-0">Repariert beschädigte <code>SebClientSettings.seb</code> Dateien</p>
                    </div>
                    <div class="card-body">
                        
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Was macht dieses Tool?</h5>
                            <p>Dieses Tool generiert eine saubere <code>SebClientSettings.seb</code> XML-Datei, die Sie manuell ersetzen können.</p>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle"></i> Wichtiger Hinweis</h6>
                            <p class="mb-0">Schließen Sie SEB komplett bevor Sie die Datei ersetzen!</p>
                        </div>
                        
                        <!-- Schritt-für-Schritt Anleitung -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5><i class="bi bi-list-ol"></i> Schritt-für-Schritt Anleitung</h5>
                            </div>
                            <div class="card-body">
                                <ol>
                                    <li><strong>SEB komplett schließen</strong></li>
                                    <li>Klicken Sie auf <span class="badge bg-primary">"Saubere Client Settings generieren"</span></li>
                                    <li>Kopieren Sie den generierten XML-Inhalt</li>
                                    <li>Navigieren Sie zu: <code>%APPDATA%\\SafeExamBrowser\\</code></li>
                                    <li>Öffnen Sie <code>SebClientSettings.seb</code> mit einem Texteditor</li>
                                    <li>Ersetzen Sie den gesamten Inhalt mit dem generierten XML</li>
                                    <li>Speichern Sie die Datei</li>
                                    <li>Starten Sie SEB neu</li>
                                </ol>
                            </div>
                        </div>
                        
                        <!-- Generierung -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5><i class="bi bi-gear"></i> XML-Generierung</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-primary btn-lg" onclick="generateCleanClientSettings()">
                                    <i class="bi bi-file-earmark-code"></i> Saubere Client Settings generieren
                                </button>
                                
                                <div id="generatedXML" class="mt-4" style="display: none;">
                                    <h6>Generierte XML-Datei:</h6>
                                    <div class="position-relative">
                                        <textarea id="xmlContent" class="form-control" rows="20" readonly></textarea>
                                        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2" onclick="copyToClipboard()">
                                            <i class="bi bi-clipboard"></i> Kopieren
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pfad-Hilfe -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5><i class="bi bi-folder2-open"></i> Pfad-Hilfe</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Standard-Pfad zur SebClientSettings.seb:</strong></p>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="%APPDATA%\\SafeExamBrowser\\SebClientSettings.seb" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyPathToClipboard(this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Fügen Sie diesen Pfad in den Windows Explorer ein (Windows + R → %APPDATA%\\SafeExamBrowser\\)</small>
                            </div>
                        </div>
                        
                        <!-- Debug -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5><i class="bi bi-bug"></i> Debug-Informationen</h5>
                            </div>
                            <div class="card-body">
                                <div id="debugInfo"></div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generateCleanClientSettings() {
            const cleanConfig = '<?xml version="1.0" encoding="UTF-8"?>' + '\\n' +
'<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">' + '\\n' +
'<plist version="1.0">' + '\\n' +
'<dict>' + '\\n' +
'    ' + '\\n' +
'    <!-- ===== SAUBERE CLIENT-EINSTELLUNGEN (OHNE SERVER) ===== -->' + '\\n' +
'    ' + '\\n' +
'    <key>sebMode</key>' + '\\n' +
'    <integer>0</integer>' + '\\n' +
'    ' + '\\n' +
'    <key>configPurpose</key>' + '\\n' +
'    <integer>1</integer>' + '\\n' +
'    ' + '\\n' +
'    <!-- ===== KEINE SERVER-KONFIGURATION ===== -->' + '\\n' +
'    <!-- Alle sebServer* Keys absichtlich weggelassen -->' + '\\n' +
'    ' + '\\n' +
'    <!-- ===== BASIC BROWSER SETTINGS ===== -->' + '\\n' +
'    ' + '\\n' +
'    <key>browserWindowAllowReload</key>' + '\\n' +
'    <true/>' + '\\n' +
'    ' + '\\n' +
'    <key>browserWindowShowURL</key>' + '\\n' +
'    <true/>' + '\\n' +
'    ' + '\\n' +
'    <key>URLFilterEnable</key>' + '\\n' +
'    <false/>' + '\\n' +
'    ' + '\\n' +
'    <!-- ===== QUIT SETTINGS ===== -->' + '\\n' +
'    ' + '\\n' +
'    <key>quitURL</key>' + '\\n' +
'    <string>seb://quit</string>' + '\\n' +
'    ' + '\\n' +
'    <!-- ===== MULTI-MONITOR SUPPORT ===== -->' + '\\n' +
'    ' + '\\n' +
'    <key>allowedDisplaysMaxNumber</key>' + '\\n' +
'    <integer>10</integer>' + '\\n' +
'    ' + '\\n' +
'    <key>allowDisplayMirroring</key>' + '\\n' +
'    <true/>' + '\\n' +
'    ' + '\\n' +
'    <!-- ===== SERVICE SETTINGS ===== -->' + '\\n' +
'    ' + '\\n' +
'    <key>sebServiceIgnore</key>' + '\\n' +
'    <true/>' + '\\n' +
'    ' + '\\n' +
'    <key>sebServicePolicy</key>' + '\\n' +
'    <integer>0</integer>' + '\\n' +
'    ' + '\\n' +
'    <!-- ===== EXAM KEYS ===== -->' + '\\n' +
'    ' + '\\n' +
'    <key>examKey</key>' + '\\n' +
'    <string></string>' + '\\n' +
'    ' + '\\n' +
'    <key>hashedAdminPassword</key>' + '\\n' +
'    <string></string>' + '\\n' +
'    ' + '\\n' +
'    <key>hashedQuitPassword</key>' + '\\n' +
'    <string></string>' + '\\n' +
'    ' + '\\n' +
'</dict>' + '\\n' +
'</plist>';

            // XML in Textarea anzeigen
            document.getElementById('xmlContent').value = cleanConfig;
            document.getElementById('generatedXML').style.display = 'block';
            
            // Debug-Info
            const debugInfo = document.getElementById('debugInfo');
            debugInfo.innerHTML = `
                <div class="alert alert-success">
                    <h6><i class="bi bi-check-circle"></i> XML erfolgreich generiert</h6>
                    <p class="mb-0">Zeichen: ${cleanConfig.length} | Zeilen: ${cleanConfig.split('\\n').length}</p>
                </div>
            `;
            
            // Scroll zur generierten XML
            document.getElementById('generatedXML').scrollIntoView({ behavior: 'smooth' });
        }
        
        function copyToClipboard() {
            const textarea = document.getElementById('xmlContent');
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Feedback
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i> Kopiert!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        }
        
        function copyPathToClipboard(btn) {
            const input = btn.previousElementSibling;
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Feedback
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i>';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 1500);
        }
    </script>
</body>
</html>