<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Direct-URLs Test</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .qr-card {
            height: 100%;
            transition: transform 0.3s ease;
        }
        .qr-card:hover {
            transform: translateY(-5px);
        }
        .url-display {
            font-family: monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            word-break: break-all;
            font-size: 0.8rem;
            margin: 10px 0;
        }
        #testResults {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="bi bi-qr-code me-2"></i>SEB Direct-URLs Test</h1>
        <p class="text-muted">Teste verschiedene SEB-URL-Schemas für automatisches .seb-Laden</p>
        
        <?php
        $testCode = $_GET['code'] ?? 'TEST';
        
        // Basis-URL ermitteln
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        $sebConfigUrl = $baseUrl . '/seb_config.php?code=' . urlencode($testCode);
        
        // Verschiedene SEB-URL-Schemas
        $schemas = [
            'direct' => [
                'name' => 'Direkt-Schema (EMPFOHLEN)',
                'url' => 'seb://' . urlencode($sebConfigUrl),
                'description' => 'Direktes SEB-Schema für iOS/Android',
                'color' => '#ff6b35',
                'icon' => 'star'
            ],
            'start' => [
                'name' => 'Start-Schema',
                'url' => 'seb://start?config=' . urlencode($sebConfigUrl),
                'description' => 'SEB-Start mit Config-Parameter',
                'color' => '#007bff',
                'icon' => 'play'
            ],
            'safeexambrowser' => [
                'name' => 'SafeExamBrowser',
                'url' => 'safeexambrowser://config?url=' . urlencode($sebConfigUrl),
                'description' => 'Offizielles SafeExamBrowser-Schema',
                'color' => '#6f42c1',
                'icon' => 'shield'
            ],
            'fallback' => [
                'name' => 'Fallback (.seb-Datei)',
                'url' => $sebConfigUrl,
                'description' => 'Direkte .seb-Datei für manuelles Öffnen',
                'color' => '#28a745',
                'icon' => 'download'
            ]
        ];
        
        echo '<div class="alert alert-info">';
        echo '<h6><i class="bi bi-info-circle me-2"></i>Test-Parameter</h6>';
        echo '<p><strong>Test-Code:</strong> <code>' . htmlspecialchars($testCode) . '</code></p>';
        echo '<p class="mb-0"><strong>SEB-Config-URL:</strong> <code>' . htmlspecialchars($sebConfigUrl) . '</code></p>';
        echo '</div>';
        ?>
        
        <div class="row mt-4">
            <?php foreach ($schemas as $key => $schema): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card qr-card">
                    <div class="card-header text-center" style="background-color: <?php echo $schema['color']; ?>20; border-color: <?php echo $schema['color']; ?>;">
                        <h6 class="mb-0">
                            <i class="bi bi-<?php echo $schema['icon']; ?> me-2" style="color: <?php echo $schema['color']; ?>;"></i>
                            <?php echo $schema['name']; ?>
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div id="qr_<?php echo $key; ?>" class="mb-3"></div>
                        
                        <div class="url-display">
                            <?php echo htmlspecialchars($schema['url']); ?>
                        </div>
                        
                        <p class="text-muted small mb-3">
                            <?php echo $schema['description']; ?>
                        </p>
                        
                        <div class="d-grid gap-2">
                            <button onclick="testSchema('<?php echo $key; ?>', '<?php echo addslashes($schema['url']); ?>')" 
                                    class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-play me-1"></i>Testen
                            </button>
                            <button onclick="copyUrl('<?php echo addslashes($schema['url']); ?>')" 
                                    class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-clipboard me-1"></i>Kopieren
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="alert alert-warning mt-4">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Erwartetes Verhalten</h6>
            <ul class="mb-0">
                <li><strong>QR-Code scannen →</strong> SEB öffnet automatisch</li>
                <li><strong>.seb-Datei lädt →</strong> Konfiguration wird angewendet</li>
                <li><strong>Test startet →</strong> Namenseingabe erscheint automatisch</li>
                <li><strong>Kein manuelles Eingreifen</strong> mehr erforderlich</li>
            </ul>
        </div>
        
        <div class="mt-4">
            <h6><i class="bi bi-gear me-2"></i>Weitere Aktionen</h6>
            <div class="d-flex gap-2 flex-wrap">
                <a href="seb_direct_url.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Erweiterte URL-Tests
                </a>
                <a href="teacher_dashboard.php?tab=generator" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>Zurück zum Generator
                </a>
                <button onclick="generateAllQRCodes()" class="btn btn-success">
                    <i class="bi bi-arrow-clockwise me-2"></i>QR-Codes neu generieren
                </button>
            </div>
        </div>
        
        <div id="testResults" class="mt-4" style="display: none;">
            <h6><i class="bi bi-clipboard-check me-2"></i>Test-Ergebnisse</h6>
            <div id="testLog"></div>
        </div>
    </div>

    <script>
        const schemas = <?php echo json_encode($schemas); ?>;
        
        function generateAllQRCodes() {
            console.log('🔄 Generiere alle QR-Codes neu...');
            
            Object.entries(schemas).forEach(([key, schema]) => {
                const container = document.getElementById('qr_' + key);
                
                // Container leeren
                container.innerHTML = '';
                
                try {
                    QRCode.toCanvas(container, schema.url, {
                        width: 150,
                        height: 150,
                        color: {
                            dark: schema.color,
                            light: '#ffffff'
                        },
                        correctLevel: QRCode.CorrectLevel.H
                    }, function(error) {
                        if (error) {
                            console.error('QR-Code Fehler für ' + key + ':', error);
                            container.innerHTML = '<div class="alert alert-danger alert-sm">QR-Code Fehler</div>';
                        } else {
                            console.log('✅ QR-Code generiert:', key);
                        }
                    });
                } catch (e) {
                    console.error('QR-Code Exception für ' + key + ':', e);
                    container.innerHTML = '<div class="alert alert-danger alert-sm">QR-Code konnte nicht generiert werden</div>';
                }
            });
        }
        
        function testSchema(schemaKey, url) {
            console.log('🧪 Teste Schema:', schemaKey, '→', url);
            
            const testResults = document.getElementById('testResults');
            const testLog = document.getElementById('testLog');
            
            testResults.style.display = 'block';
            
            const timestamp = new Date().toLocaleTimeString();
            const schema = schemas[schemaKey];
            
            testLog.innerHTML += `
                <div class="alert alert-info alert-sm">
                    <strong>${timestamp}</strong> - Teste ${schema.name}: 
                    <code style="font-size: 0.8rem;">${url}</code>
                </div>
            `;
            
            try {
                // Versuche Schema zu öffnen
                window.location.href = url;
                
                testLog.innerHTML += `
                    <div class="alert alert-success alert-sm">
                        <strong>${timestamp}</strong> - Schema-Aufruf erfolgreich ausgeführt
                    </div>
                `;
            } catch (e) {
                testLog.innerHTML += `
                    <div class="alert alert-danger alert-sm">
                        <strong>${timestamp}</strong> - Fehler: ${e.message}
                    </div>
                `;
            }
        }
        
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                console.log('📋 URL kopiert:', url);
                
                // Temporäres Feedback
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = '<i class="bi bi-check2 me-2"></i>URL in Zwischenablage kopiert!';
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 2000);
            }).catch(err => {
                console.error('Kopieren fehlgeschlagen:', err);
                prompt('URL manuell kopieren:', url);
            });
        }
        
        // Initial QR-Codes generieren
        generateAllQRCodes();
        
        console.log('🎯 SEB Direct-URLs Test geladen');
        console.log('📱 User-Agent:', navigator.userAgent);
        console.log('🔗 Schemas:', schemas);
    </script>
</body>
</html>
