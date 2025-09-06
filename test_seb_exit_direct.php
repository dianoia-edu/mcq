<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Direct Exit Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .exit-btn {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            border: none;
            color: white;
            padding: 15px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            margin: 10px;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        .exit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
            color: white;
        }
        .log-area {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="bi bi-gear me-2"></i>SEB Direct Exit Test</h1>
        <p class="text-muted">Teste verschiedene direkte SEB-Exit-Methoden (ohne Alert/Umwege)</p>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle me-2"></i>Hinweis</h6>
            <p class="mb-0">Diese Seite testet direkte SEB-Exit-URLs. Funktioniert nur innerhalb von SEB!</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h5>URL-basierte Exit-Methoden:</h5>
                <div class="d-grid gap-2">
                    <button onclick="testExit('seb://quit')" class="exit-btn">
                        <i class="bi bi-1-square me-2"></i>seb://quit
                    </button>
                    
                    <button onclick="testExit('safeexambrowser://quit')" class="exit-btn">
                        <i class="bi bi-2-square me-2"></i>safeexambrowser://quit
                    </button>
                    
                    <button onclick="testExit('seb-quit://')" class="exit-btn">
                        <i class="bi bi-3-square me-2"></i>seb-quit://
                    </button>
                    
                    <button onclick="testExit('seb://exit')" class="exit-btn">
                        <i class="bi bi-4-square me-2"></i>seb://exit
                    </button>
                    
                    <button onclick="testExit('seb://close')" class="exit-btn">
                        <i class="bi bi-5-square me-2"></i>seb://close
                    </button>
                </div>
            </div>
            
            <div class="col-md-6">
                <h5>Alternative Methoden:</h5>
                <div class="d-grid gap-2">
                    <button onclick="testWindowClose()" class="exit-btn">
                        <i class="bi bi-window me-2"></i>window.close()
                    </button>
                    
                    <button onclick="testIframeExit()" class="exit-btn">
                        <i class="bi bi-picture-in-picture me-2"></i>Iframe Exit
                    </button>
                    
                    <button onclick="testCustomEvent()" class="exit-btn">
                        <i class="bi bi-lightning me-2"></i>Custom Event
                    </button>
                    
                    <button onclick="testAllMethods()" class="exit-btn">
                        <i class="bi bi-arrow-repeat me-2"></i>Alle Methoden
                    </button>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h6>Debug-Log:</h6>
            <div id="logArea" class="log-area"></div>
            <button onclick="clearLog()" class="btn btn-outline-secondary btn-sm mt-2">
                <i class="bi bi-trash me-1"></i>Log löschen
            </button>
        </div>
        
        <div class="mt-4 alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>SEB-Status</h6>
            <p><strong>Browser:</strong> <span id="browserInfo"></span></p>
            <p><strong>SEB erkannt:</strong> <span id="sebStatus"></span></p>
            <p class="mb-0"><strong>User-Agent:</strong> <small id="userAgent"></small></p>
        </div>
    </div>

    <script>
        // Debug-Funktionen
        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            const logArea = document.getElementById('logArea');
            logArea.innerHTML += `[${timestamp}] ${message}\n`;
            logArea.scrollTop = logArea.scrollHeight;
            console.log(message);
        }
        
        function clearLog() {
            document.getElementById('logArea').innerHTML = '';
        }
        
        // Browser-Info anzeigen
        const userAgent = navigator.userAgent;
        const isSEB = userAgent.includes('SEB') || userAgent.includes('SafeExamBrowser');
        const isIpad = userAgent.includes('iPad');
        
        document.getElementById('browserInfo').textContent = isIpad ? 'iPad' : 'Desktop';
        document.getElementById('sebStatus').innerHTML = isSEB ? 
            '<span class="badge bg-success">✅ SEB erkannt</span>' : 
            '<span class="badge bg-danger">❌ Kein SEB</span>';
        document.getElementById('userAgent').textContent = userAgent.substring(0, 100) + '...';
        
        // Test-Funktionen
        function testExit(url) {
            log(`🚪 Teste Exit-URL: ${url}`);
            
            try {
                window.location.href = url;
                log(`✅ URL-Aufruf erfolgreich gesendet`);
            } catch (error) {
                log(`❌ Fehler beim URL-Aufruf: ${error.message}`);
            }
        }
        
        function testWindowClose() {
            log('🚪 Teste window.close()');
            
            try {
                window.close();
                log('✅ window.close() ausgeführt');
            } catch (error) {
                log(`❌ window.close() fehlgeschlagen: ${error.message}`);
            }
        }
        
        function testIframeExit() {
            log('🚪 Teste Iframe-basierte Exit-URLs');
            
            const urls = ['seb://quit', 'safeexambrowser://quit', 'seb-quit://'];
            
            urls.forEach((url, index) => {
                setTimeout(() => {
                    try {
                        const iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = url;
                        document.body.appendChild(iframe);
                        log(`✅ Iframe für ${url} erstellt`);
                        
                        // Iframe nach 2 Sekunden entfernen
                        setTimeout(() => {
                            if (iframe.parentNode) {
                                iframe.parentNode.removeChild(iframe);
                            }
                        }, 2000);
                        
                    } catch (error) {
                        log(`❌ Iframe für ${url} fehlgeschlagen: ${error.message}`);
                    }
                }, index * 500);
            });
        }
        
        function testCustomEvent() {
            log('🚪 Teste Custom Events für SEB');
            
            const events = ['seb-quit', 'sebQuit', 'SEB_QUIT', 'seb-ios-quit'];
            
            events.forEach(eventName => {
                try {
                    const event = new CustomEvent(eventName, {
                        detail: { 
                            password: 'admin123',
                            source: 'direct-test'
                        }
                    });
                    
                    window.dispatchEvent(event);
                    document.dispatchEvent(event);
                    
                    log(`✅ Custom Event "${eventName}" gesendet`);
                    
                } catch (error) {
                    log(`❌ Custom Event "${eventName}" fehlgeschlagen: ${error.message}`);
                }
            });
        }
        
        function testAllMethods() {
            log('🚀 TESTE ALLE METHODEN NACHEINANDER...');
            
            // URL-Methoden
            const urls = [
                'seb://quit',
                'safeexambrowser://quit', 
                'seb-quit://',
                'seb://exit',
                'seb://close'
            ];
            
            urls.forEach((url, index) => {
                setTimeout(() => testExit(url), index * 1000);
            });
            
            // Iframe-Methode nach 6 Sekunden
            setTimeout(testIframeExit, 6000);
            
            // Custom Events nach 8 Sekunden
            setTimeout(testCustomEvent, 8000);
            
            // Window-Close nach 10 Sekunden
            setTimeout(testWindowClose, 10000);
        }
        
        // Initial-Log
        log('🧪 SEB Direct Exit Test geladen');
        log(`📱 Platform: ${isIpad ? 'iPad' : 'Desktop'}`);
        log(`🔒 SEB Status: ${isSEB ? 'Erkannt' : 'Nicht erkannt'}`);
        
        if (!isSEB) {
            log('⚠️ WARNUNG: Nicht im SEB - Exit-Tests werden nicht funktionieren!');
        } else {
            log('✅ SEB erkannt - Exit-Tests sollten funktionieren');
        }
    </script>
</body>
</html>
