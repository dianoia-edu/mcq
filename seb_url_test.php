<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB URL-Schema Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .url-test-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            background: #f8f9fa;
        }
        .url-display {
            background: #2d3748;
            color: #f7fafc;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
        }
        .qr-container {
            text-align: center;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="bi bi-link-45deg me-2"></i>SEB URL-Schema Test</h1>
        <p class="text-muted">Teste verschiedene SEB-URL-Formate für QR-Code-Scans</p>
        
        <?php
        $testCode = $_GET['code'] ?? 'TEST';
        $baseUrl = 'http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        echo '<div class="alert alert-info">';
        echo '<h6><i class="bi bi-info-circle me-2"></i>Test-Parameter</h6>';
        echo '<p><strong>Test-Code:</strong> ' . htmlspecialchars($testCode) . '</p>';
        echo '<p class="mb-0"><strong>Base-URL:</strong> ' . htmlspecialchars($baseUrl) . '</p>';
        echo '</div>';
        
        // Verschiedene URL-Formate zum Testen
        $urlFormats = [
            'direct_config' => [
                'name' => 'Direkte .seb-Datei',
                'url' => $baseUrl . '/seb_config.php?code=' . urlencode($testCode),
                'description' => 'Direkter Link zur .seb-Konfigurationsdatei'
            ],
            'seb_encoded' => [
                'name' => 'seb:// mit URL-Encoding',
                'url' => 'seb://' . urlencode($baseUrl . '/seb_config.php?code=' . urlencode($testCode)),
                'description' => 'SEB-Schema mit URL-enkodierter Config-URL'
            ],
            'seb_start' => [
                'name' => 'seb://start mit config Parameter',
                'url' => 'seb://start?config=' . urlencode($baseUrl . '/seb_config.php?code=' . urlencode($testCode)),
                'description' => 'SEB-Start mit config-Parameter'
            ],
            'seb_load' => [
                'name' => 'seb://load mit URL',
                'url' => 'seb://load?url=' . urlencode($baseUrl . '/seb_config.php?code=' . urlencode($testCode)),
                'description' => 'SEB-Load mit URL-Parameter'
            ],
            'name_form_direct' => [
                'name' => 'Direkt zur Namenseingabe',
                'url' => $baseUrl . '/name_form.php?code=' . urlencode($testCode) . '&seb=true',
                'description' => 'Direkter Link zur Namenseingabe-Seite'
            ],
            'seb_name_form' => [
                'name' => 'seb:// zur Namenseingabe',
                'url' => 'seb://' . urlencode($baseUrl . '/name_form.php?code=' . urlencode($testCode) . '&seb=true'),
                'description' => 'SEB-Schema direkt zur Namenseingabe'
            ],
            'seb_start_name' => [
                'name' => 'seb://start zur Namenseingabe',
                'url' => 'seb://start?url=' . urlencode($baseUrl . '/name_form.php?code=' . urlencode($testCode) . '&seb=true'),
                'description' => 'SEB-Start direkt zur Test-Seite'
            ]
        ];
        ?>
        
        <?php foreach ($urlFormats as $key => $format): ?>
        <div class="url-test-card">
            <h5><i class="bi bi-<?php echo $key === 'direct_config' ? 'star' : 'arrow-right'; ?> me-2"></i><?php echo $format['name']; ?></h5>
            <p class="text-muted"><?php echo $format['description']; ?></p>
            
            <div class="url-display"><?php echo htmlspecialchars($format['url']); ?></div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="qr-container">
                        <div id="qr_<?php echo $key; ?>"></div>
                        <small class="text-muted">QR-Code für <?php echo $format['name']; ?></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-grid gap-2">
                        <button onclick="testURL('<?php echo addslashes($format['url']); ?>', '<?php echo $format['name']; ?>')" class="btn btn-primary">
                            <i class="bi bi-play me-2"></i>URL testen
                        </button>
                        <button onclick="copyURL('<?php echo addslashes($format['url']); ?>')" class="btn btn-outline-secondary">
                            <i class="bi bi-clipboard me-2"></i>URL kopieren
                        </button>
                        <a href="<?php echo htmlspecialchars($format['url']); ?>" class="btn btn-outline-info" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Direkt öffnen
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Troubleshooting -->
        <div class="alert alert-warning mt-4">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Troubleshooting "Verbindungsfehler"</h6>
            <p><strong>Mögliche Ursachen:</strong></p>
            <ul>
                <li><strong>Falsches URL-Schema:</strong> SEB erwartet möglicherweise ein anderes Format</li>
                <li><strong>URL-Encoding-Problem:</strong> Spezielle Zeichen nicht korrekt enkodiert</li>
                <li><strong>Netzwerk-Problem:</strong> SEB kann die URL nicht erreichen</li>
                <li><strong>HTTPS-Anforderung:</strong> SEB erwartet sichere Verbindungen</li>
                <li><strong>Content-Type-Problem:</strong> Server sendet falschen MIME-Type</li>
            </ul>
        </div>
        
        <!-- Empfehlung -->
        <div class="alert alert-success">
            <h6><i class="bi bi-lightbulb me-2"></i>Empfehlung zum Testen</h6>
            <p><strong>Reihenfolge ausprobieren:</strong></p>
            <ol class="mb-0">
                <li><strong>Direkte .seb-Datei</strong> - Einfachster Ansatz</li>
                <li><strong>Direkt zur Namenseingabe</strong> - Überspringt .seb-Download</li>
                <li><strong>seb://start zur Namenseingabe</strong> - Kombiniert beides</li>
                <li><strong>seb://start mit config</strong> - Falls SEB Config-Parameter erwartet</li>
            </ol>
        </div>
        
        <!-- Test-Log -->
        <div id="testResults" class="mt-4" style="display: none;">
            <h6><i class="bi bi-clipboard-check me-2"></i>Test-Ergebnisse</h6>
            <div id="testLog"></div>
        </div>
    </div>

    <script>
        const urlFormats = <?php echo json_encode($urlFormats); ?>;
        
        // QR-Codes generieren
        Object.entries(urlFormats).forEach(([key, format]) => {
            try {
                const qrElement = document.getElementById('qr_' + key);
                if (qrElement) {
                    QRCode.toCanvas(qrElement, format.url, {
                        width: 200,
                        height: 200,
                        color: {
                            dark: key === 'direct_config' ? '#ff6b35' : '#000000',
                            light: '#ffffff'
                        }
                    });
                }
            } catch (e) {
                console.error('QR-Code Fehler für', key, ':', e);
                const qrElement = document.getElementById('qr_' + key);
                if (qrElement) {
                    qrElement.innerHTML = '<div class="alert alert-danger alert-sm">QR-Code Fehler</div>';
                }
            }
        });
        
        function testURL(url, name) {
            console.log('🧪 Teste URL:', name, '→', url);
            logResult('Teste ' + name + ': ' + url, 'info');
            
            try {
                window.location.href = url;
                logResult('URL-Aufruf gestartet für: ' + name, 'success');
            } catch (e) {
                logResult('URL-Test Fehler für ' + name + ': ' + e.message, 'danger');
            }
        }
        
        function copyURL(url) {
            navigator.clipboard.writeText(url).then(() => {
                logResult('URL in Zwischenablage kopiert: ' + url.substring(0, 50) + '...', 'success');
                
                // Kurzes Feedback
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
        
        console.log('🔗 SEB URL-Schema Test geladen');
        console.log('📋 URL-Formate:', urlFormats);
        console.log('📱 User-Agent:', navigator.userAgent);
        
        // Initial-Log
        logResult('SEB URL-Test-Seite geladen - ' + Object.keys(urlFormats).length + ' URL-Formate zum Testen', 'info');
    </script>
</body>
</html>
