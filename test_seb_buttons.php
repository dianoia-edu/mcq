<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Button Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="bi bi-bug me-2"></i>SEB Button Test</h1>
        
        <?php
        $testCode = $_GET['code'] ?? 'TEST';
        
        // Basis-URL ermitteln
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        // URLs generieren
        $sebConfigUrl = $baseUrl . '/seb_config.php?code=' . urlencode($testCode);
        $sebConfigFlexibleUrl = $baseUrl . '/seb_config_flexible.php?code=' . urlencode($testCode);
        $sebConfigPreviewUrl = $baseUrl . '/seb_config_preview.php?code=' . urlencode($testCode);
        
        echo '<div class="alert alert-info">';
        echo '<h6><i class="bi bi-info-circle me-2"></i>Test-Informationen</h6>';
        echo '<p><strong>Test-Code:</strong> ' . htmlspecialchars($testCode) . '</p>';
        echo '<p><strong>Basis-URL:</strong> ' . htmlspecialchars($baseUrl) . '</p>';
        echo '</div>';
        ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="bi bi-download me-2"></i>SEB-Dateien testen</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?php echo htmlspecialchars($sebConfigUrl); ?>" class="btn btn-outline-primary" target="_blank">
                                <i class="bi bi-file-earmark me-2"></i>seb_config.php testen
                            </a>
                            <a href="<?php echo htmlspecialchars($sebConfigFlexibleUrl); ?>" class="btn btn-outline-success" target="_blank">
                                <i class="bi bi-file-earmark-arrow-down me-2"></i>seb_config_flexible.php testen
                            </a>
                            <a href="<?php echo htmlspecialchars($sebConfigPreviewUrl); ?>" class="btn btn-outline-info" target="_blank">
                                <i class="bi bi-eye me-2"></i>seb_config_preview.php testen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5><i class="bi bi-check-circle me-2"></i>Dateien prüfen</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $files = [
                            'seb_config.php' => 'Standard SEB-Konfiguration',
                            'seb_config_flexible.php' => 'Flexible SEB-Konfiguration', 
                            'seb_config_preview.php' => 'SEB-Konfiguration Vorschau'
                        ];
                        
                        foreach ($files as $file => $description) {
                            $exists = file_exists($file);
                            $icon = $exists ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger';
                            $status = $exists ? 'Vorhanden' : 'Fehlt';
                            
                            echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                            echo '<span>' . htmlspecialchars($description) . '</span>';
                            echo '<span><i class="' . $icon . ' me-2"></i>' . $status . '</span>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-code me-2"></i>Debug URLs</h5>
                    </div>
                    <div class="card-body">
                        <h6>Standard Config URL:</h6>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($sebConfigUrl); ?>" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this)">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        
                        <h6>Flexible Config URL:</h6>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($sebConfigFlexibleUrl); ?>" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this)">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        
                        <h6>Preview URL:</h6>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($sebConfigPreviewUrl); ?>" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this)">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="bi bi-terminal me-2"></i>JavaScript Test</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testSebDownload()" class="btn btn-primary me-2">
                            <i class="bi bi-download me-2"></i>Test SEB Download
                        </button>
                        <button onclick="testSebPreview()" class="btn btn-info me-2">
                            <i class="bi bi-eye me-2"></i>Test SEB Preview
                        </button>
                        <button onclick="checkConsole()" class="btn btn-warning">
                            <i class="bi bi-terminal me-2"></i>Check Console
                        </button>
                        
                        <div id="testResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testSebDownload() {
            const testCode = '<?php echo addslashes($testCode); ?>';
            const baseUrl = '<?php echo addslashes($baseUrl); ?>';
            const sebDownloadUrl = baseUrl + '/seb_config_flexible.php?code=' + testCode;
            
            console.log('🧪 Testing SEB Download:', sebDownloadUrl);
            logResult('SEB Download Test gestartet: ' + sebDownloadUrl, 'info');
            
            try {
                window.open(sebDownloadUrl, '_blank');
                logResult('SEB Download erfolgreich ausgeführt', 'success');
            } catch (error) {
                console.error('❌ SEB Download Fehler:', error);
                logResult('SEB Download Fehler: ' + error.message, 'danger');
            }
        }
        
        function testSebPreview() {
            const testCode = '<?php echo addslashes($testCode); ?>';
            const baseUrl = '<?php echo addslashes($baseUrl); ?>';
            const previewUrl = baseUrl + '/seb_config_preview.php?code=' + testCode;
            
            console.log('🧪 Testing SEB Preview:', previewUrl);
            logResult('SEB Preview Test gestartet: ' + previewUrl, 'info');
            
            try {
                window.open(previewUrl, '_blank');
                logResult('SEB Preview erfolgreich ausgeführt', 'success');
            } catch (error) {
                console.error('❌ SEB Preview Fehler:', error);
                logResult('SEB Preview Fehler: ' + error.message, 'danger');
            }
        }
        
        function checkConsole() {
            console.log('🔍 Console Check ausgeführt');
            console.log('📊 Aktuelle Zeit:', new Date().toLocaleString());
            console.log('🌐 Current URL:', window.location.href);
            console.log('📱 User Agent:', navigator.userAgent);
            
            logResult('Console Check ausgeführt - siehe Browser-Konsole für Details', 'warning');
        }
        
        function copyToClipboard(button) {
            const input = button.parentElement.querySelector('input');
            const url = input.value;
            
            navigator.clipboard.writeText(url).then(() => {
                const icon = button.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'bi bi-check-circle-fill text-success';
                
                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
                
                logResult('URL kopiert: ' + url.substring(0, 50) + '...', 'success');
            }).catch(err => {
                logResult('Kopieren fehlgeschlagen: ' + err.message, 'danger');
            });
        }
        
        function logResult(message, type) {
            const testResults = document.getElementById('testResults');
            const timestamp = new Date().toLocaleTimeString();
            const alertClass = 'alert-' + type;
            
            testResults.innerHTML += `
                <div class="alert ${alertClass} alert-sm">
                    <strong>${timestamp}</strong> - ${message}
                </div>
            `;
            
            console.log('📊 ' + message);
        }
        
        // Initial log
        console.log('🔧 SEB Button Test Seite geladen');
        logResult('SEB Button Test Seite geladen', 'success');
    </script>
</body>
</html>
