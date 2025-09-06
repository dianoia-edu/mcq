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
        
        <!-- Strategie 1: Nuklear-Option -->
        <div class="strategy-card strategy-nuclear">
            <h5><i class="bi bi-radioactive text-danger me-2"></i>Strategie 1: Nuklear-Reset</h5>
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
        <div class="alert alert-success mt-4">
            <h6><i class="bi bi-lightbulb-fill me-2"></i>Empfehlung</h6>
            <p><strong>Reihenfolge ausprobieren:</strong></p>
            <ol class="mb-0">
                <li><strong>Sanfter Neustart</strong> - Am einfachsten für Benutzer</li>
                <li><strong>URL-Parameter-Trick</strong> - Technisch, aber effektiv</li>
                <li><strong>Einstellungen-Override</strong> - Wenn SEB stur ist</li>
                <li><strong>Nuklear-Reset</strong> - Letzte Option bei kompletten Problemen</li>
            </ol>
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
        
        function generateNuclearQR() {
            const url = 'seb://' + encodeURIComponent(baseUrl + '/seb_config_reset.php?code=' + testCode);
            generateQR('nuclearQR', 'nuclearQRContainer', url, '#dc3545');
            logTest('Nuklear-Reset QR-Code generiert', 'danger');
        }
        
        function generateOverrideQR() {
            const url = 'seb://' + encodeURIComponent(baseUrl + '/seb_config_flexible.php?code=' + testCode);
            generateQR('overrideQR', 'overrideQRContainer', url, '#ffc107');
            logTest('Override QR-Code generiert', 'warning');
        }
        
        function generateGentleQR() {
            const url = 'seb://' + encodeURIComponent(baseUrl + '/seb_config.php?code=' + testCode);
            generateQR('gentleQR', 'gentleQRContainer', url, '#28a745');
            logTest('Standard QR-Code generiert', 'success');
        }
        
        function generateTrickQR() {
            const timestamp = Date.now();
            const randomHash = Math.random().toString(36).substring(2, 10);
            const trickUrl = baseUrl + '/seb_config.php?code=' + testCode + 
                            '&t=' + timestamp + '&h=' + randomHash + '&force=1&clear=session';
            const url = 'seb://' + encodeURIComponent(trickUrl);
            generateQR('trickQR', 'trickQRContainer', url, '#17a2b8');
            logTest('URL-Trick QR-Code generiert mit Parameters: t=' + timestamp + ', h=' + randomHash, 'info');
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
        
        function generateQR(elementId, containerId, url, color) {
            const container = document.getElementById(containerId);
            const element = document.getElementById(elementId);
            
            // Container anzeigen
            container.style.display = 'block';
            
            // Element leeren
            element.innerHTML = '';
            
            try {
                QRCode.toCanvas(element, url, {
                    width: 200,
                    height: 200,
                    color: {
                        dark: color,
                        light: '#ffffff'
                    },
                    correctLevel: QRCode.CorrectLevel.H
                });
                console.log('✅ QR-Code generiert:', url);
            } catch (e) {
                console.error('❌ QR-Code Fehler:', e);
                element.innerHTML = '<div class="alert alert-danger">QR-Code konnte nicht generiert werden</div>';
            }
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
        
        console.log('🔄 SEB-Reset Strategien geladen');
        console.log('🎯 Test-Code:', testCode);
        console.log('🌐 Base-URL:', baseUrl);
    </script>
</body>
</html>
