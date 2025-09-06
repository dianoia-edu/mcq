<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Quit Problem Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        .debug-box { background: white; border-radius: 8px; padding: 1.5rem; margin: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error { background: #fff5f5; border: 1px solid #fed7d7; color: #c53030; }
        .success { background: #f0fff4; border: 1px solid #9ae6b4; color: #38a169; }
        .warning { background: #fffbeb; border: 1px solid #f6e05e; color: #d69e2e; }
        .test-link { display: block; margin: 10px 0; padding: 10px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; text-align: center; }
        .test-link:hover { background: #5a67d8; color: white; }
        .btn-seb-exit { background: linear-gradient(145deg, #ff6b35, #e55a2b); border: none; color: white; padding: 10px 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1><i class="bi bi-bug me-2"></i>SEB-Quit Problem Debug</h1>
        
        <div class="debug-box error">
            <h5><i class="bi bi-exclamation-triangle me-2"></i>PROBLEM: "Blockierte URL" bei seb://quit</h5>
            <p><strong>Symptom:</strong> Beim Klick auf "SEB beenden" Button erscheint "blockierte URL" Fehlermeldung</p>
            <p><strong>Vermutete Ursache:</strong> SEB-Konfiguration blockiert seb:// Schema-URLs</p>
        </div>
        
        <div class="debug-box">
            <h6><i class="bi bi-info-circle me-2"></i>Browser-Information</h6>
            <p><strong>User-Agent:</strong> <code id="userAgent"></code></p>
            <p><strong>SEB erkannt:</strong> <span id="sebStatus"></span></p>
            <p><strong>Platform:</strong> <span id="platform"></span></p>
        </div>
        
        <div class="debug-box">
            <h6><i class="bi bi-gear me-2"></i>SEB-Konfiguration Status</h6>
            <p><strong>URL-Filter:</strong> <span class="badge bg-success">DEAKTIVIERT</span> (URLFilterEnable = false)</p>
            <p><strong>allowQuit:</strong> <span class="badge bg-success">AKTIVIERT</span> (true)</p>
            <p><strong>sebServicePolicy:</strong> <span class="badge bg-info">1</span> (Erlaubt)</p>
            <p><strong>quitURL:</strong> <span class="badge bg-warning">seb://quit</span></p>
        </div>
        
        <div class="debug-box warning">
            <h6><i class="bi bi-lightbulb me-2"></i>Test-Strategie</h6>
            <p>1. <strong>Neue .seb-Datei herunterladen</strong> mit korrigierter Konfiguration</p>
            <p>2. <strong>SEB komplett schließen</strong> und neu starten</p>
            <p>3. <strong>Neue .seb-Datei öffnen</strong> (nicht die alte verwenden!)</p>
            <p>4. <strong>Test durchführen</strong> bis zur Result-Seite</p>
            <p>5. <strong>Button testen</strong></p>
        </div>
        
        <div class="debug-box">
            <h6><i class="bi bi-link me-2"></i>SEB-Quit URL Tests</h6>
            <p>Klicke auf diese Links, um verschiedene Quit-Methoden zu testen:</p>
            
            <a href="seb://quit" class="test-link">
                <i class="bi bi-1-square me-2"></i>seb://quit (Direkt)
            </a>
            
            <a href="javascript:window.location.href='seb://quit'" class="test-link">
                <i class="bi bi-2-square me-2"></i>JavaScript: window.location.href = 'seb://quit'
            </a>
            
            <button onclick="testSEBQuit()" class="btn btn-seb-exit w-100 mb-2">
                <i class="bi bi-3-square me-2"></i>Button mit exitSEBNow() Funktion
            </button>
            
            <a href="seb_quit_direct.php" class="test-link">
                <i class="bi bi-4-square me-2"></i>PHP-Redirect zu seb://quit
            </a>
        </div>
        
        <div class="debug-box">
            <h6><i class="bi bi-download me-2"></i>Neue SEB-Konfiguration</h6>
            <p>Lade die korrigierte SEB-Konfiguration herunter:</p>
            <a href="seb_config.php?code=DEBUG" class="btn btn-primary">
                <i class="bi bi-download me-2"></i>Neue .seb-Datei herunterladen (DEBUG)
            </a>
            <a href="seb_config_preview.php?code=DEBUG" class="btn btn-info ms-2">
                <i class="bi bi-eye me-2"></i>Konfiguration ansehen
            </a>
        </div>
        
        <div class="debug-box" id="testResults">
            <h6><i class="bi bi-clipboard-check me-2"></i>Test-Ergebnisse</h6>
            <p>Klick-Ergebnisse werden hier angezeigt...</p>
        </div>
    </div>

    <script>
        // Browser-Info anzeigen
        const userAgent = navigator.userAgent;
        const isSEB = userAgent.includes('SEB') || userAgent.includes('SafeExamBrowser');
        const isIpad = userAgent.includes('iPad');
        
        document.getElementById('userAgent').textContent = userAgent;
        document.getElementById('sebStatus').innerHTML = isSEB ? 
            '<span class="badge bg-success">✅ SEB</span>' : 
            '<span class="badge bg-danger">❌ Kein SEB</span>';
        document.getElementById('platform').innerHTML = isIpad ? 
            '<span class="badge bg-info">📱 iPad</span>' : 
            '<span class="badge bg-secondary">💻 Desktop</span>';
        
        function logResult(message, type = 'info') {
            const results = document.getElementById('testResults');
            const timestamp = new Date().toLocaleTimeString();
            const badge = type === 'error' ? 'bg-danger' : type === 'success' ? 'bg-success' : 'bg-info';
            
            results.innerHTML += `
                <p class="mb-1">
                    <span class="badge ${badge}">${timestamp}</span> ${message}
                </p>
            `;
        }
        
        function testSEBQuit() {
            logResult('🔗 Teste seb://quit via JavaScript...', 'info');
            
            try {
                window.location.href = 'seb://quit';
                logResult('✅ window.location.href = "seb://quit" ausgeführt', 'success');
            } catch (e) {
                logResult('❌ Fehler: ' + e.message, 'error');
            }
        }
        
        // Click-Tracking
        document.querySelectorAll('.test-link').forEach(link => {
            link.addEventListener('click', function(e) {
                logResult('🔗 Klick auf: ' + this.href, 'info');
                
                // Für seb:// URLs
                if (this.href.startsWith('seb://')) {
                    if (!isSEB) {
                        logResult('⚠️ Nicht im SEB - seb:// URLs funktionieren nur in SEB', 'error');
                    }
                }
            });
        });
        
        // Onload
        logResult('🧪 Debug-Seite geladen', 'success');
        if (isSEB) {
            logResult('✅ SEB erkannt - seb:// Tests sollten funktionieren', 'success');
        } else {
            logResult('⚠️ Kein SEB erkannt - seb:// Tests zeigen "Protokoll unbekannt"', 'error');
        }
        
        console.log('🐛 SEB-Quit Debug geladen');
        console.log('📊 SEB:', isSEB, '| iPad:', isIpad);
    </script>
</body>
</html>
