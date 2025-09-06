<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Embedded Configuration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .config-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .qr-display {
            background: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
        }
        .url-display {
            background: #2d3748;
            color: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            margin: 15px 0;
        }
        .workflow-step {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="config-container">
        <h1><i class="bi bi-qr-code-scan me-2"></i>SEB Embedded Configuration</h1>
        <p class="text-muted">Ein QR-Code für Konfiguration UND Test-Start</p>
        
        <?php
        $testCode = $_GET['code'] ?? 'TEST';
        
        // Basis-URL ermitteln
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        // URLs generieren
        $sebConfigUrl = $baseUrl . '/seb_config.php?code=' . urlencode($testCode);
        $sebConfigFlexibleUrl = $baseUrl . '/seb_config_flexible.php?code=' . urlencode($testCode);
        
        // KORREKTE SEB-URLs für embedded config
        $sebsConfigUrl = 'sebs://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/seb_config.php?code=' . urlencode($testCode);
        $sebsFlexibleUrl = 'sebs://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/seb_config_flexible.php?code=' . urlencode($testCode);
        
        echo '<div class="alert alert-success">';
        echo '<h6><i class="bi bi-check-circle me-2"></i>Embedded Config Workflow</h6>';
        echo '<p><strong>Test-Code:</strong> ' . htmlspecialchars($testCode) . '</p>';
        echo '<p class="mb-0"><strong>Ein QR-Code = Konfiguration + Test-Start</strong></p>';
        echo '</div>';
        ?>
        
        <!-- Hauptergebnis -->
        <div class="qr-display">
            <h4><i class="bi bi-star-fill text-warning me-2"></i>EMPFOHLENER QR-CODE</h4>
            <div id="embeddedQR" class="mb-3"></div>
            <h6>sebs:// Schema - Embedded Config</h6>
            <div class="url-display"><?php echo htmlspecialchars($sebsFlexibleUrl); ?></div>
            
            <div class="d-flex gap-2 justify-content-center mt-3">
                <button onclick="copyURL('<?php echo addslashes($sebsFlexibleUrl); ?>')" class="btn btn-outline-primary">
                    <i class="bi bi-clipboard me-2"></i>URL kopieren
                </button>
                <a href="<?php echo htmlspecialchars($sebConfigFlexibleUrl); ?>" class="btn btn-outline-success" target="_blank">
                    <i class="bi bi-download me-2"></i>.seb-Datei testen
                </a>
            </div>
        </div>
        
        <!-- Workflow-Erklärung -->
        <h5 class="mt-4">🔄 Wie funktioniert das?</h5>
        
        <div class="workflow-step">
            <div class="d-flex align-items-start">
                <span class="step-number">1</span>
                <div>
                    <h6>QR-Code scannen</h6>
                    <p class="mb-0">Schüler scannt QR-Code mit SEB oder QR-Code-App</p>
                </div>
            </div>
        </div>
        
        <div class="workflow-step">
            <div class="d-flex align-items-start">
                <span class="step-number">2</span>
                <div>
                    <h6>sebs:// URL erkannt</h6>
                    <p class="mb-0">Das <code>sebs://</code> Schema öffnet automatisch SEB</p>
                </div>
            </div>
        </div>
        
        <div class="workflow-step">
            <div class="d-flex align-items-start">
                <span class="step-number">3</span>
                <div>
                    <h6>.seb-Konfiguration geladen</h6>
                    <p class="mb-0">SEB lädt die Konfigurationsdatei von der URL</p>
                </div>
            </div>
        </div>
        
        <div class="workflow-step">
            <div class="d-flex align-items-start">
                <span class="step-number">4</span>
                <div>
                    <h6>Test startet automatisch</h6>
                    <p class="mb-0">SEB öffnet die in der Config definierte Start-URL (Namenseingabe)</p>
                </div>
            </div>
        </div>
        
        <!-- Alternative URLs -->
        <h5 class="mt-4">🔄 Alternative URLs zum Testen</h5>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Standard Config</h6>
                    </div>
                    <div class="card-body">
                        <div id="standardQR" class="text-center mb-3"></div>
                        <div class="url-display" style="font-size: 0.8rem;">
                            <?php echo htmlspecialchars($sebsConfigUrl); ?>
                        </div>
                        <button onclick="copyURL('<?php echo addslashes($sebsConfigUrl); ?>')" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-clipboard me-2"></i>Standard URL kopieren
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Flexible Config</h6>
                    </div>
                    <div class="card-body">
                        <div id="flexibleQR" class="text-center mb-3"></div>
                        <div class="url-display" style="font-size: 0.8rem;">
                            <?php echo htmlspecialchars($sebsFlexibleUrl); ?>
                        </div>
                        <button onclick="copyURL('<?php echo addslashes($sebsFlexibleUrl); ?>')" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-clipboard me-2"></i>Flexible URL kopieren
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Technical Details -->
        <div class="alert alert-info mt-4">
            <h6><i class="bi bi-info-circle me-2"></i>Technische Details</h6>
            <ul class="mb-0">
                <li><strong>sebs://</strong> = SEB HTTPS Schema (sicher)</li>
                <li><strong>seb://</strong> = SEB HTTP Schema (unsicher)</li>
                <li><strong>Embedded Config:</strong> Konfiguration wird live von Server geladen</li>
                <li><strong>Start-URL:</strong> In der .seb-Datei definiert</li>
                <li><strong>Ein Scan:</strong> Konfiguration + Test-Start in einem Schritt</li>
            </ul>
        </div>
        
        <!-- Troubleshooting -->
        <div class="alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Falls es nicht funktioniert</h6>
            <p><strong>Mögliche Probleme:</strong></p>
            <ul class="mb-0">
                <li><strong>SEB nicht installiert:</strong> App muss auf dem Gerät installiert sein</li>
                <li><strong>Alte SEB-Version:</strong> sebs:// Schema nicht unterstützt</li>
                <li><strong>Netzwerk-Problem:</strong> .seb-Datei nicht erreichbar</li>
                <li><strong>HTTPS erforderlich:</strong> Server muss SSL-Zertifikat haben</li>
            </ul>
        </div>
        
        <!-- Test-Log -->
        <div id="testResults" class="mt-4" style="display: none;">
            <h6><i class="bi bi-clipboard-check me-2"></i>Test-Ergebnisse</h6>
            <div id="testLog"></div>
        </div>
    </div>

    <script>
        // QR-Codes generieren
        const embeddedUrl = '<?php echo addslashes($sebsFlexibleUrl); ?>';
        const standardUrl = '<?php echo addslashes($sebsConfigUrl); ?>';
        const flexibleUrl = '<?php echo addslashes($sebsFlexibleUrl); ?>';
        
        // Haupt-QR-Code (embedded)
        QRCode.toCanvas(document.getElementById('embeddedQR'), embeddedUrl, {
            width: 300,
            height: 300,
            color: {
                dark: '#28a745',
                light: '#ffffff'
            },
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Standard-QR-Code
        QRCode.toCanvas(document.getElementById('standardQR'), standardUrl, {
            width: 150,
            height: 150,
            color: {
                dark: '#17a2b8',
                light: '#ffffff'
            }
        });
        
        // Flexible-QR-Code
        QRCode.toCanvas(document.getElementById('flexibleQR'), flexibleUrl, {
            width: 150,
            height: 150,
            color: {
                dark: '#ffc107',
                light: '#ffffff'
            }
        });
        
        function copyURL(url) {
            navigator.clipboard.writeText(url).then(() => {
                logResult('URL in Zwischenablage kopiert: ' + url.substring(0, 50) + '...', 'success');
                
                // Feedback
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = '<i class="bi bi-check2 me-2"></i>URL kopiert!';
                document.body.appendChild(toast);
                setTimeout(() => document.body.removeChild(toast), 2000);
            }).catch(err => {
                logResult('Kopieren fehlgeschlagen: ' + err.message, 'danger');
                prompt('URL manuell kopieren:', url);
            });
        }
        
        function logResult(message, type) {
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
        
        console.log('🔗 SEB Embedded Configuration geladen');
        console.log('📱 Embedded URL:', embeddedUrl);
        console.log('⚙️ Standard URL:', standardUrl);
        console.log('🔧 Flexible URL:', flexibleUrl);
        
        // Initial-Log
        logResult('SEB Embedded Config geladen - ein QR-Code für alles!', 'success');
    </script>
</body>
</html>
