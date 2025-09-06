<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Reset Strategien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .strategy-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .strategy-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }
        .strategy-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }
        .strategy-nuclear {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5, #ffffff);
        }
        .strategy-gentle {
            border-color: #28a745;
            background: linear-gradient(135deg, #f0fff4, #ffffff);
        }
        .strategy-medium {
            border-color: #ffc107;
            background: linear-gradient(135deg, #fffbf0, #ffffff);
        }
        .qr-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 10px 0;
        }
        .step-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="strategy-container">
        <h1><i class="bi bi-arrow-clockwise me-2"></i>SEB-Reset Strategien</h1>
        <p class="text-muted">Verschiedene Ansätze um SEB zu zwingen, eine neue Konfiguration zu laden</p>
        
        <?php
        $testCode = $_GET['code'] ?? 'TEST';
        $baseUrl = 'http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        echo '<div class="alert alert-info">';
        echo '<h6><i class="bi bi-info-circle me-2"></i>Problem</h6>';
        echo '<p class="mb-0"><strong>SEB startet immer mit der letzten Konfiguration.</strong> Hier sind verschiedene Lösungsansätze:</p>';
        echo '</div>';
        ?>
        
        <!-- Strategie 0: Ultra-Nuklear-Option -->
        <div class="strategy-card" style="border-color: #dc3545; background: linear-gradient(135deg, #fff5f5, #ffe6e6);">
            <h5><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Strategie 0: ULTRA-NUKLEAR-RESET</h5>
            <p><strong>Ansatz:</strong> Aggressivste SEB-Zurücksetzung mit komplett deaktivierten Sicherheitsfeatures</p>
            
            <div class="step-list">
                <h6>Ultra-Aggressive Methoden:</h6>
                <ul>
                    <li><strong>Eindeutige Zeitstempel-IDs</strong> in allen Konfigurationsfeldern</li>
                    <li><strong>Alle Sicherheitsfeatures deaktiviert</strong> (keine Keyboard-Hooks, keine Prozess-Überwachung)</li>
                    <li><strong>Alle Admin-Passwörter entfernt</strong></li>
                    <li><strong>Force-Reconfiguration auf Maximum</strong></li>
                    <li><strong>Alle URL-Filter und Einschränkungen aus</strong></li>
                </ul>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="generateUltraNuclearQR()" class="btn btn-danger">
                    <i class="bi bi-radioactive me-2"></i>Ultra-Nuklear QR-Code
                </button>
                <a href="seb_config_ultra_reset.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-outline-danger" target="_blank">
                    <i class="bi bi-download me-2"></i>Ultra-Reset-Config herunterladen
                </a>
                <a href="seb_manual_cleanup_guide.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-warning" target="_blank">
                    <i class="bi bi-tools me-2"></i>Manuelle Bereinigung
                </a>
            </div>
            
            <div class="qr-preview" id="ultraNuclearQRContainer" style="display: none;">
                <div id="ultraNuclearQR"></div>
                <small class="text-muted">Ultra-Nuklear-Reset QR-Code</small>
            </div>
        </div>
        
        <!-- Strategie 1: Nuklear-Option -->
        <div class="strategy-card strategy-nuclear">
            <h5><i class="bi bi-radioactive text-danger me-2"></i>Strategie 1: Standard-Nuklear-Reset</h5>
            <p><strong>Ansatz:</strong> SEB komplett zurücksetzen und alle gespeicherten Konfigurationen löschen</p>
            
            <div class="step-list">
                <h6>Schritte:</h6>
                <ol>
                    <li><strong>SEB komplett beenden</strong> (Taskmanager falls nötig)</li>
                    <li><strong>SEB-Registry löschen</strong> (Windows) oder Einstellungen zurücksetzen (Mac/iOS)</li>
                    <li><strong>Alle .seb-Dateien löschen</strong> aus Downloads/Desktop</li>
                    <li><strong>SEB neu starten</strong></li>
                    <li><strong>Neue .seb-Datei öffnen</strong></li>
                </ol>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="generateNuclearQR()" class="btn btn-danger">
                    <i class="bi bi-qr-code me-2"></i>Nuklear-Reset QR-Code
                </button>
                <a href="seb_config_reset.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-outline-danger" target="_blank">
                    <i class="bi bi-download me-2"></i>Reset-Config herunterladen
                </a>
            </div>
            
            <div class="qr-preview" id="nuclearQRContainer" style="display: none;">
                <div id="nuclearQR"></div>
                <small class="text-muted">Nuklear-Reset QR-Code</small>
            </div>
        </div>
        
        <!-- Strategie 2: Einstellungen-Override -->
        <div class="strategy-card strategy-medium">
            <h5><i class="bi bi-gear-fill text-warning me-2"></i>Strategie 2: Einstellungen-Override</h5>
            <p><strong>Ansatz:</strong> SEB-Konfiguration mit speziellen "Force-Reload" Einstellungen</p>
            
            <div class="step-list">
                <h6>Force-Reload Einstellungen:</h6>
                <ul>
                    <li><code>forceReconfiguration: true</code></li>
                    <li><code>examSessionClearCookiesOnStart: true</code></li>
                    <li><code>examSessionClearSessionOnStart: true</code></li>
                    <li><code>cryptoIdentity: 0</code> (macht Config "neutral")</li>
                    <li>Eindeutige <code>examKeySalt</code> mit Timestamp</li>
                </ul>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="generateOverrideQR()" class="btn btn-warning">
                    <i class="bi bi-qr-code me-2"></i>Override QR-Code
                </button>
                <a href="seb_config_flexible.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-outline-warning" target="_blank">
                    <i class="bi bi-download me-2"></i>Override-Config herunterladen
                </a>
            </div>
            
            <div class="qr-preview" id="overrideQRContainer" style="display: none;">
                <div id="overrideQR"></div>
                <small class="text-muted">Override QR-Code</small>
            </div>
        </div>
        
        <!-- Strategie 3: Sanfter Neustart -->
        <div class="strategy-card strategy-gentle">
            <h5><i class="bi bi-arrow-repeat text-success me-2"></i>Strategie 3: Sanfter Neustart</h5>
            <p><strong>Ansatz:</strong> SEB mit Admin-Passwort beenden und sauber neu starten</p>
            
            <div class="step-list">
                <h6>Schritte:</h6>
                <ol>
                    <li><strong>Ctrl+Alt+F9</strong> in SEB drücken</li>
                    <li><strong>Admin-Passwort eingeben:</strong> <code>admin123</code></li>
                    <li><strong>"SEB beenden" wählen</strong></li>
                    <li><strong>Neue .seb-Datei öffnen</strong></li>
                </ol>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="generateGentleQR()" class="btn btn-success">
                    <i class="bi bi-qr-code me-2"></i>Standard QR-Code
                </button>
                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-outline-success" target="_blank">
                    <i class="bi bi-download me-2"></i>Standard-Config herunterladen
                </a>
            </div>
            
            <div class="qr-preview" id="gentleQRContainer" style="display: none;">
                <div id="gentleQR"></div>
                <small class="text-muted">Standard QR-Code</small>
            </div>
        </div>
        
        <!-- Strategie 4: URL-Parameter-Trick -->
        <div class="strategy-card">
            <h5><i class="bi bi-link-45deg text-info me-2"></i>Strategie 4: URL-Parameter-Trick</h5>
            <p><strong>Ansatz:</strong> URL-Parameter verwenden um SEB zu "verwirren"</p>
            
            <div class="step-list">
                <h6>URL-Tricks:</h6>
                <ul>
                    <li>Timestamp als Parameter: <code>?t=<?php echo time(); ?></code></li>
                    <li>Random-Hash: <code>&h=<?php echo substr(md5(rand()), 0, 8); ?></code></li>
                    <li>Force-Reload: <code>&force=1</code></li>
                    <li>Session-Clear: <code>&clear=session</code></li>
                </ul>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="generateTrickQR()" class="btn btn-info">
                    <i class="bi bi-qr-code me-2"></i>URL-Trick QR-Code
                </button>
                <button onclick="copyTrickURL()" class="btn btn-outline-info">
                    <i class="bi bi-clipboard me-2"></i>Trick-URL kopieren
                </button>
            </div>
            
            <div class="qr-preview" id="trickQRContainer" style="display: none;">
                <div id="trickQR"></div>
                <small class="text-muted">URL-Trick QR-Code</small>
            </div>
        </div>
        
        <!-- Empfehlung -->
        <div class="alert alert-danger mt-4">
            <h6><i class="bi bi-exclamation-triangle-fill me-2"></i>Neue Empfehlung bei hartnäckigen Problemen</h6>
            <p><strong>Wenn Standard-Nuklear-Reset fehlgeschlagen ist:</strong></p>
            <ol class="mb-0">
                <li><strong>Ultra-Nuklear-Reset</strong> - Aggressivste .seb-Konfiguration</li>
                <li><strong>Manuelle Bereinigung</strong> - Registry/AppData/Preferences löschen</li>
                <li><strong>SEB komplett neu installieren</strong> - Falls alles andere fehlschlägt</li>
                <li><strong>System-Neustart</strong> - Nach jeder Bereinigung erforderlich</li>
            </ol>
        </div>
        
        <div class="alert alert-warning mt-3">
            <h6><i class="bi bi-info-circle me-2"></i>Warum ist SEB so hartnäckig?</h6>
            <p>SEB ist darauf ausgelegt, Manipulationen während Prüfungen zu verhindern. Diese Sicherheitsfeatures machen es auch schwer, Konfigurationen zu ändern. Die Ultra-Nuklear-Option deaktiviert alle Sicherheitsfeatures temporär.</p>
        </div>
        
        <!-- Test-Bereich -->
        <div class="mt-4" id="testResults" style="display: none;">
            <h6><i class="bi bi-clipboard-check me-2"></i>Test-Ergebnisse</h6>
            <div id="testLog"></div>
        </div>
    </div>

    <script>
        const testCode = '<?php echo $testCode; ?>';
        const baseUrl = '<?php echo $baseUrl; ?>';
        
        function generateUltraNuclearQR() {
            generateQRSimple('ultraNuclearQR', 'ultraNuclearQRContainer', 
                           'seb://' + encodeURIComponent(baseUrl + '/seb_config_ultra_reset.php?code=' + testCode), 
                           '#dc3545', 'Ultra-Nuklear QR-Code');
        }
        
        function generateNuclearQR() {
            const url = 'seb://' + encodeURIComponent(baseUrl + '/seb_config_reset.php?code=' + testCode);
            console.log('🔴 Generiere Nuklear QR-Code:', url);
            
            const element = document.getElementById('nuclearQR');
            const container = document.getElementById('nuclearQRContainer');
            
            if (!element || !container) {
                console.error('❌ Nuklear-Elemente nicht gefunden');
                return;
            }
            
            container.style.display = 'block';
            element.innerHTML = '<div class="text-center"><div class="spinner-border text-danger" role="status"></div><br>Generiere QR-Code...</div>';
            
            setTimeout(() => {
                try {
                    if (typeof QRCode !== 'undefined') {
                        element.innerHTML = '';
                        new QRCode(element, {
                            text: url,
                            width: 200,
                            height: 200,
                            colorDark: '#dc3545',
                            colorLight: '#ffffff'
                        });
                        logTest('Nuklear-Reset QR-Code generiert', 'danger');
                    } else {
                        // Fallback
                        element.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + 
                                           encodeURIComponent(url) + '" alt="Nuklear QR-Code" class="img-fluid border-danger" style="border: 2px solid #dc3545;">';
                        logTest('Nuklear-Reset QR-Code (Fallback) generiert', 'warning');
                    }
                } catch (e) {
                    console.error('❌ Nuklear QR-Code Fehler:', e);
                    element.innerHTML = '<div class="alert alert-danger alert-sm">QR-Code konnte nicht erstellt werden</div>';
                    logTest('Nuklear QR-Code Fehler: ' + e.message, 'danger');
                }
            }, 500);
        }
        
        function generateOverrideQR() {
            generateQRSimple('overrideQR', 'overrideQRContainer', 
                           'seb://' + encodeURIComponent(baseUrl + '/seb_config_flexible.php?code=' + testCode), 
                           '#ffc107', 'Override QR-Code');
        }
        
        function generateGentleQR() {
            generateQRSimple('gentleQR', 'gentleQRContainer', 
                           'seb://' + encodeURIComponent(baseUrl + '/seb_config.php?code=' + testCode), 
                           '#28a745', 'Standard QR-Code');
        }
        
        function generateTrickQR() {
            const timestamp = Date.now();
            const randomHash = Math.random().toString(36).substring(2, 10);
            const trickUrl = baseUrl + '/seb_config.php?code=' + testCode + 
                            '&t=' + timestamp + '&h=' + randomHash + '&force=1&clear=session';
            const url = 'seb://' + encodeURIComponent(trickUrl);
            generateQRSimple('trickQR', 'trickQRContainer', url, '#17a2b8', 'URL-Trick QR-Code');
            logTest('URL-Trick QR-Code generiert mit Parameters: t=' + timestamp + ', h=' + randomHash, 'info');
        }
        
        // Vereinfachte QR-Code-Generierung
        function generateQRSimple(elementId, containerId, url, color, name) {
            console.log('🔄 Generiere ' + name + ':', url);
            
            const element = document.getElementById(elementId);
            const container = document.getElementById(containerId);
            
            if (!element || !container) {
                console.error('❌ ' + name + ' Elemente nicht gefunden:', elementId, containerId);
                return;
            }
            
            container.style.display = 'block';
            element.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><br>Generiere QR-Code...</div>';
            
            setTimeout(() => {
                try {
                    if (typeof QRCode !== 'undefined') {
                        element.innerHTML = '';
                        new QRCode(element, {
                            text: url,
                            width: 200,
                            height: 200,
                            colorDark: color,
                            colorLight: '#ffffff'
                        });
                        console.log('✅ ' + name + ' erfolgreich generiert');
                        logTest(name + ' generiert', 'success');
                    } else {
                        // Fallback zu Online-API
                        element.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + 
                                           encodeURIComponent(url) + '" alt="' + name + '" class="img-fluid" style="border: 2px solid ' + color + ';">';
                        console.log('✅ ' + name + ' (Fallback) generiert');
                        logTest(name + ' (Fallback) generiert', 'warning');
                    }
                } catch (e) {
                    console.error('❌ ' + name + ' Fehler:', e);
                    element.innerHTML = '<div class="alert alert-danger alert-sm">' + name + ' konnte nicht erstellt werden: ' + e.message + '</div>';
                    logTest(name + ' Fehler: ' + e.message, 'danger');
                }
            }, 200);
        }
        
        function copyTrickURL() {
            const timestamp = Date.now();
            const randomHash = Math.random().toString(36).substring(2, 10);
            const trickUrl = baseUrl + '/seb_config.php?code=' + testCode + 
                            '&t=' + timestamp + '&h=' + randomHash + '&force=1&clear=session';
            
            navigator.clipboard.writeText(trickUrl).then(() => {
                logTest('URL-Trick URL in Zwischenablage kopiert', 'success');
                alert('URL kopiert: ' + trickUrl);
            }).catch(err => {
                logTest('Kopieren fehlgeschlagen: ' + err.message, 'danger');
                prompt('URL manuell kopieren:', trickUrl);
            });
        }
        
        
        function logTest(message, type) {
            const testResults = document.getElementById('testResults');
            const testLog = document.getElementById('testLog');
            
            testResults.style.display = 'block';
            
            const timestamp = new Date().toLocaleTimeString();
            const alertClass = 'alert-' + type;
            
            testLog.innerHTML += `
                <div class="alert ${alertClass} alert-sm">
                    <strong>${timestamp}</strong> - ${message}
                </div>
            `;
            
            console.log('📊', message);
        }
        
        // Debug-Informationen
        console.log('🔄 SEB-Reset Strategien geladen');
        console.log('🎯 Test-Code:', testCode);
        console.log('🌐 Base-URL:', baseUrl);
        console.log('📚 QRCode verfügbar:', typeof QRCode !== 'undefined');
        
        // Teste ob alle Elemente vorhanden sind
        const elements = ['nuclearQR', 'overrideQR', 'gentleQR', 'trickQR'];
        elements.forEach(id => {
            const element = document.getElementById(id);
            console.log('🔍 Element', id + ':', element ? 'gefunden' : 'NICHT GEFUNDEN');
        });
        
        // QR-Code Generation Info
        logTest('Seite geladen - QR-Codes können über Buttons generiert werden', 'info');
    </script>
</body>
</html>
