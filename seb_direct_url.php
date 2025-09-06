<?php
/**
 * SEB-Direct-URL Generator
 * Generiert verschiedene SEB-URL-Schemas für automatisches .seb-Laden
 */

$testCode = $_GET['code'] ?? '';

if (empty($testCode)) {
    http_response_code(400);
    die('Fehler: Test-Code fehlt');
}

// Basis-URL ermitteln
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$baseUrl = rtrim($baseUrl, '/');

// Verschiedene SEB-URL-Schemas
$sebConfigUrl = $baseUrl . '/seb_config.php?code=' . urlencode($testCode);
$nameFormUrl = $baseUrl . '/name_form.php?code=' . urlencode($testCode) . '&seb=true';

// SEB-URL-Schemas (verschiedene Varianten)
$sebSchemas = [
    // 1. SEB mit direkter Config-URL (empfohlen)
    'seb_config_direct' => 'seb://' . urlencode($sebConfigUrl),
    
    // 2. SEB mit Start-URL und Config
    'seb_start_config' => 'seb://start?config=' . urlencode($sebConfigUrl),
    
    // 3. SEB mit Start-URL
    'seb_start_url' => 'seb://start?url=' . urlencode($nameFormUrl),
    
    // 4. SafeExamBrowser Schema
    'safeexambrowser_config' => 'safeexambrowser://config?url=' . urlencode($sebConfigUrl),
    
    // 5. SEB-Config Schema (iOS-spezifisch)
    'seb_config_ios' => 'seb-config://' . urlencode($sebConfigUrl),
    
    // 6. Custom SEB-Schema
    'custom_seb' => 'seb://open?config=' . urlencode($sebConfigUrl) . '&autostart=true',
];

// Content-Type für QR-Code-Erkennung
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Direct-URL für Test <?php echo htmlspecialchars($testCode); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .url-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .qr-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
        }
        .url-schema {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .url-text {
            font-family: monospace;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            word-break: break-all;
            font-size: 0.9rem;
        }
        .qr-code-canvas {
            margin: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .test-button {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="url-container">
        <h1><i class="bi bi-qr-code me-2"></i>SEB Direct-URL für Test: <?php echo htmlspecialchars($testCode); ?></h1>
        <p class="text-muted">Verschiedene SEB-URL-Schemas für automatisches .seb-Laden</p>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle me-2"></i>Ziel</h6>
            <p class="mb-0">QR-Code scannen → SEB öffnet automatisch → .seb-Datei lädt → Test startet ohne manuelles Eingreifen</p>
        </div>
        
        <!-- Basis-URLs -->
        <div class="url-schema">
            <h6><i class="bi bi-link me-2"></i>Basis-URLs</h6>
            <div class="url-text">
                <strong>SEB-Config:</strong> <?php echo htmlspecialchars($sebConfigUrl); ?>
            </div>
            <div class="url-text">
                <strong>Name-Form:</strong> <?php echo htmlspecialchars($nameFormUrl); ?>
            </div>
        </div>
        
        <?php foreach ($sebSchemas as $schemaName => $schemaUrl): ?>
        <div class="url-schema">
            <h6><i class="bi bi-<?php echo $schemaName === 'seb_config_direct' ? 'star' : 'arrow-right'; ?> me-2"></i>
                <?php echo str_replace('_', ' ', ucfirst($schemaName)); ?>
                <?php if ($schemaName === 'seb_config_direct'): ?>
                    <span class="badge bg-success ms-2">EMPFOHLEN</span>
                <?php endif; ?>
            </h6>
            
            <div class="url-text mb-3">
                <?php echo htmlspecialchars($schemaUrl); ?>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="qr-container">
                        <div id="qr_<?php echo $schemaName; ?>"></div>
                        <small class="text-muted">QR-Code für <?php echo $schemaName; ?></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-grid gap-2">
                        <button onclick="testSchema('<?php echo $schemaName; ?>', '<?php echo addslashes($schemaUrl); ?>')" 
                                class="btn btn-primary test-button">
                            <i class="bi bi-play me-2"></i>Schema testen
                        </button>
                        <button onclick="copyToClipboard('<?php echo addslashes($schemaUrl); ?>')" 
                                class="btn btn-outline-secondary test-button">
                            <i class="bi bi-clipboard me-2"></i>URL kopieren
                        </button>
                        <a href="<?php echo htmlspecialchars($schemaUrl); ?>" 
                           class="btn btn-outline-warning test-button" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Direkter Link
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Fallback: Direkte .seb-Datei -->
        <div class="url-schema">
            <h6><i class="bi bi-download me-2"></i>Fallback: Direkte .seb-Datei</h6>
            <div class="url-text mb-3">
                <?php echo htmlspecialchars($sebConfigUrl); ?>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="qr-container">
                        <div id="qr_fallback"></div>
                        <small class="text-muted">QR-Code für direkte .seb-Datei</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-grid gap-2">
                        <a href="<?php echo htmlspecialchars($sebConfigUrl); ?>" 
                           class="btn btn-success test-button" target="_blank">
                            <i class="bi bi-download me-2"></i>.seb-Datei herunterladen
                        </a>
                        <button onclick="copyToClipboard('<?php echo addslashes($sebConfigUrl); ?>')" 
                                class="btn btn-outline-secondary test-button">
                            <i class="bi bi-clipboard me-2"></i>URL kopieren
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-warning mt-4">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Hinweise</h6>
            <ul class="mb-0">
                <li><strong>iOS/iPad:</strong> Meist funktioniert nur die direkte .seb-URL</li>
                <li><strong>Android:</strong> SEB-Schemas können unterstützt werden</li>
                <li><strong>Desktop:</strong> Alle Schemas sollten funktionieren</li>
                <li><strong>QR-Code-Apps:</strong> Erkennen SEB-Schemas unterschiedlich</li>
            </ul>
        </div>
        
        <div id="testResults" class="mt-4" style="display: none;">
            <h6>Test-Ergebnisse:</h6>
            <div id="testLog"></div>
        </div>
    </div>

    <script>
        // Generiere QR-Codes für alle Schemas
        const schemas = <?php echo json_encode($sebSchemas); ?>;
        const fallbackUrl = '<?php echo addslashes($sebConfigUrl); ?>';
        
        // QR-Codes generieren
        Object.entries(schemas).forEach(([name, url]) => {
            try {
                QRCode.toCanvas(document.getElementById('qr_' + name), url, {
                    width: 200,
                    height: 200,
                    color: {
                        dark: name === 'seb_config_direct' ? '#ff6b35' : '#000000',
                        light: '#ffffff'
                    }
                });
            } catch (e) {
                console.error('QR-Code Fehler für', name, ':', e);
                document.getElementById('qr_' + name).innerHTML = 
                    '<div class="alert alert-danger">QR-Code konnte nicht generiert werden</div>';
            }
        });
        
        // Fallback QR-Code
        QRCode.toCanvas(document.getElementById('qr_fallback'), fallbackUrl, {
            width: 200,
            height: 200,
            color: {
                dark: '#28a745',
                light: '#ffffff'
            }
        });
        
        function testSchema(schemaName, url) {
            console.log('🧪 Teste Schema:', schemaName, '→', url);
            
            const testResults = document.getElementById('testResults');
            const testLog = document.getElementById('testLog');
            
            testResults.style.display = 'block';
            
            // Log-Eintrag hinzufügen
            const timestamp = new Date().toLocaleTimeString();
            testLog.innerHTML += `
                <div class="alert alert-info alert-sm">
                    <strong>${timestamp}</strong> - Teste ${schemaName}: 
                    <code>${url}</code>
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
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('URL in Zwischenablage kopiert!');
            }).catch(err => {
                console.error('Kopieren fehlgeschlagen:', err);
                prompt('URL kopieren:', text);
            });
        }
        
        console.log('🔒 SEB Direct-URL Seite geladen');
        console.log('📱 User-Agent:', navigator.userAgent);
        console.log('🎯 Test-Code:', '<?php echo $testCode; ?>');
        console.log('🌐 Schemas:', schemas);
    </script>
</body>
</html>
