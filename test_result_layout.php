<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result-Seite Layout Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .result-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        
        .btn-seb-exit {
            background: linear-gradient(145deg, #ff6b35, #e55a2b);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-seb-exit:hover {
            background: linear-gradient(145deg, #e55a2b, #cc4d22);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(229, 90, 43, 0.4);
        }
        
        .percentage-display {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }
        
        .score-details {
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <h2 class="text-center mb-4">
            <i class="bi bi-check-circle text-success me-2"></i>
            Test erfolgreich abgeschlossen!
        </h2>
        
        <div class="percentage-display">85%</div>
        
        <div class="score-details">
            Erreichte Punkte: 17 von 20
        </div>
        
        <div class="action-buttons">
            <a href="#" class="btn btn-success">
                <i class="bi bi-download me-2"></i>XML-Ergebnis herunterladen
            </a>
            
            <div id="dynamicActionButton">
                <!-- Test verschiedene Button-Varianten -->
            </div>
        </div>
        
        <div class="mt-4">
            <h6>Layout-Test für verschiedene Browser/SEB-Modi:</h6>
            
            <div class="alert alert-info">
                <strong>Browser-Simulation:</strong>
                <button onclick="showBrowserButton()" class="btn btn-sm btn-outline-primary ms-2">Browser-Modus</button>
                <button onclick="showSEBiPadButton()" class="btn btn-sm btn-outline-warning ms-2">SEB iPad</button>
                <button onclick="showSEBDesktopButton()" class="btn btn-sm btn-outline-danger ms-2">SEB Desktop</button>
            </div>
        </div>
    </div>

    <script>
        function showBrowserButton() {
            const container = document.getElementById('dynamicActionButton');
            container.innerHTML = `
                <button onclick="alert('Zurück zur Startseite')" class="btn btn-primary">
                    <i class="bi bi-house me-2"></i>Zurück zur Startseite
                </button>
            `;
        }
        
        function showSEBiPadButton() {
            const container = document.getElementById('dynamicActionButton');
            container.innerHTML = `
                <a href="seb://quit" class="btn btn-seb-exit">
                    <i class="bi bi-power me-2"></i>SEB beenden
                </a>
            `;
        }
        
        function showSEBDesktopButton() {
            const container = document.getElementById('dynamicActionButton');
            container.innerHTML = `
                <button onclick="alert('SEB wird beendet...')" class="btn btn-seb-exit">
                    <i class="bi bi-power me-2"></i>SEB beenden
                </button>
            `;
        }
        
        // Standard: Browser-Modus anzeigen
        showBrowserButton();
        
        console.log('🎨 Result-Layout Test geladen');
        console.log('📱 Teste verschiedene Button-Kombinationen');
    </script>
</body>
</html>
