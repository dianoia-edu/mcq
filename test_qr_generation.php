<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR-Code Generierung Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1>QR-Code Generierung Test</h1>
        
        <!-- Test 1: Einfacher QR-Code -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Test 1: Einfacher QR-Code</h5>
            </div>
            <div class="card-body">
                <button onclick="generateSimpleQR()" class="btn btn-primary">QR-Code generieren</button>
                <div id="simpleQR" class="mt-3"></div>
            </div>
        </div>
        
        <!-- Test 2: Canvas QR-Code -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Test 2: Canvas QR-Code</h5>
            </div>
            <div class="card-body">
                <button onclick="generateCanvasQR()" class="btn btn-success">Canvas QR-Code</button>
                <div id="canvasQR" class="mt-3"></div>
            </div>
        </div>
        
        <!-- Test 3: Fallback QR-Code -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Test 3: Fallback QR-Code (Online API)</h5>
            </div>
            <div class="card-body">
                <button onclick="generateFallbackQR()" class="btn btn-warning">Fallback QR-Code</button>
                <div id="fallbackQR" class="mt-3"></div>
            </div>
        </div>
        
        <!-- Debug Info -->
        <div class="card">
            <div class="card-header">
                <h5>Debug Information</h5>
            </div>
            <div class="card-body">
                <div id="debugInfo"></div>
            </div>
        </div>
    </div>

    <script>
        // Debug-Informationen anzeigen
        function showDebugInfo() {
            const debugDiv = document.getElementById('debugInfo');
            debugDiv.innerHTML = `
                <p><strong>QRCode Bibliothek:</strong> ${typeof QRCode !== 'undefined' ? '✅ Verfügbar' : '❌ Nicht verfügbar'}</p>
                <p><strong>Browser:</strong> ${navigator.userAgent}</p>
                <p><strong>Canvas-Support:</strong> ${document.createElement('canvas').getContext ? '✅ Verfügbar' : '❌ Nicht verfügbar'}</p>
                <p><strong>Aktuelle URL:</strong> ${window.location.href}</p>
            `;
            
            if (typeof QRCode !== 'undefined') {
                debugDiv.innerHTML += `<p><strong>QRCode Version:</strong> ${QRCode.version || 'Unbekannt'}</p>`;
            }
        }
        
        // Test 1: Einfacher QR-Code
        function generateSimpleQR() {
            const element = document.getElementById('simpleQR');
            element.innerHTML = 'Generiere QR-Code...';
            
            try {
                if (typeof QRCode !== 'undefined') {
                    element.innerHTML = '';
                    const qr = new QRCode(element, {
                        text: 'https://example.com/test',
                        width: 200,
                        height: 200,
                        colorDark: '#000000',
                        colorLight: '#ffffff'
                    });
                    console.log('✅ Einfacher QR-Code erstellt');
                } else {
                    element.innerHTML = '<div class="alert alert-danger">QRCode-Bibliothek nicht verfügbar</div>';
                }
            } catch (e) {
                element.innerHTML = '<div class="alert alert-danger">Fehler: ' + e.message + '</div>';
                console.error('❌ Einfacher QR-Code Fehler:', e);
            }
        }
        
        // Test 2: Canvas QR-Code
        function generateCanvasQR() {
            const element = document.getElementById('canvasQR');
            element.innerHTML = 'Generiere Canvas QR-Code...';
            
            try {
                if (typeof QRCode !== 'undefined') {
                    element.innerHTML = '';
                    const canvas = document.createElement('canvas');
                    element.appendChild(canvas);
                    
                    QRCode.toCanvas(canvas, 'https://example.com/canvas-test', {
                        width: 200,
                        height: 200,
                        color: {
                            dark: '#0000FF',
                            light: '#FFFFFF'
                        }
                    }, function(error) {
                        if (error) {
                            element.innerHTML = '<div class="alert alert-danger">Canvas-Fehler: ' + error.message + '</div>';
                            console.error('❌ Canvas QR-Code Fehler:', error);
                        } else {
                            console.log('✅ Canvas QR-Code erstellt');
                        }
                    });
                } else {
                    element.innerHTML = '<div class="alert alert-danger">QRCode-Bibliothek nicht verfügbar</div>';
                }
            } catch (e) {
                element.innerHTML = '<div class="alert alert-danger">Fehler: ' + e.message + '</div>';
                console.error('❌ Canvas QR-Code Exception:', e);
            }
        }
        
        // Test 3: Fallback QR-Code
        function generateFallbackQR() {
            const element = document.getElementById('fallbackQR');
            element.innerHTML = 'Generiere Fallback QR-Code...';
            
            try {
                const url = 'https://example.com/fallback-test';
                const qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
                
                element.innerHTML = `
                    <img src="${qrImageUrl}" alt="Fallback QR-Code" class="img-fluid border" style="max-width: 200px;">
                    <p class="text-muted mt-2">Fallback QR-Code über API generiert</p>
                `;
                console.log('✅ Fallback QR-Code erstellt');
            } catch (e) {
                element.innerHTML = '<div class="alert alert-danger">Fallback-Fehler: ' + e.message + '</div>';
                console.error('❌ Fallback QR-Code Fehler:', e);
            }
        }
        
        // Beim Laden der Seite
        window.addEventListener('load', function() {
            showDebugInfo();
            console.log('🧪 QR-Code Test-Seite geladen');
            
            // Automatische Tests nach 1 Sekunde
            setTimeout(function() {
                console.log('🔄 Starte automatische Tests...');
                generateSimpleQR();
                
                setTimeout(() => generateCanvasQR(), 1000);
                setTimeout(() => generateFallbackQR(), 2000);
            }, 1000);
        });
    </script>
</body>
</html>