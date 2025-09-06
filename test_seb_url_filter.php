<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB URL-Filter Test</title>
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
        .test-link {
            display: block;
            margin: 10px 0;
            padding: 15px;
            background: #ff6b35;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .test-link:hover {
            background: #e55a2b;
            color: white;
            transform: translateY(-2px);
        }
        .status-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .warning { background: #fff3cd; border-color: #ffeaa7; }
        .danger { background: #f8d7da; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="bi bi-shield-check me-2"></i>SEB URL-Filter Test</h1>
        <p class="text-muted">Teste, ob SEB-Quit-URLs in der Konfiguration freigeschaltet sind</p>
        
        <div class="status-box">
            <h6><i class="bi bi-info-circle me-2"></i>SEB-Status</h6>
            <p><strong>SEB erkannt:</strong> <span id="sebStatus"></span></p>
            <p><strong>Platform:</strong> <span id="platform"></span></p>
            <p class="mb-0"><strong>User-Agent:</strong> <small id="userAgent"></small></p>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-lightbulb me-2"></i>Test-Hinweis</h6>
            <p class="mb-0">
                Diese Links testen, ob die SEB-Quit-URLs in der .seb-Konfiguration 
                korrekt freigeschaltet sind. Bei "blockierte URL" fehlen die URLFilterRules.
            </p>
        </div>
        
        <h5 class="mt-4">SEB-Quit-URLs testen:</h5>
        
        <a href="seb://quit" class="test-link">
            <i class="bi bi-1-square me-2"></i>seb://quit (Hauptmethode)
        </a>
        
        <a href="safeexambrowser://quit" class="test-link">
            <i class="bi bi-2-square me-2"></i>safeexambrowser://quit
        </a>
        
        <a href="seb-quit://" class="test-link">
            <i class="bi bi-3-square me-2"></i>seb-quit:// (iOS-spezifisch)
        </a>
        
        <a href="seb://exit" class="test-link">
            <i class="bi bi-4-square me-2"></i>seb://exit (Alternative)
        </a>
        
        <a href="seb://close" class="test-link">
            <i class="bi bi-5-square me-2"></i>seb://close (Alternative)
        </a>
        
        <h5 class="mt-4">Backup-Lösungen:</h5>
        
        <a href="seb_quit_direct.php" class="test-link">
            <i class="bi bi-arrow-right me-2"></i>seb_quit_direct.php (PHP-Redirect)
        </a>
        
        <a href="seb_manual_exit_ipad.php?code=TEST" class="test-link">
            <i class="bi bi-tablet me-2"></i>seb_manual_exit_ipad.php (Manual-Exit)
        </a>
        
        <div class="mt-4 alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Erwartetes Verhalten</h6>
            <ul class="mb-0">
                <li><strong>Im SEB:</strong> Links sollten SEB beenden (kein "blockierte URL")</li>
                <li><strong>Im Browser:</strong> Links werden als "Protokoll unbekannt" angezeigt</li>
                <li><strong>Fehlermeldung "blockierte URL":</strong> URLFilterRules müssen angepasst werden</li>
            </ul>
        </div>
        
        <div class="mt-4">
            <h6>Aktuelle SEB-Konfiguration prüfen:</h6>
            <a href="seb_config_preview.php?code=TEST" class="btn btn-info">
                <i class="bi bi-eye me-2"></i>SEB-Konfiguration ansehen
            </a>
            <a href="seb_config.php?code=TEST" class="btn btn-primary ms-2">
                <i class="bi bi-download me-2"></i>Neue .seb-Datei herunterladen
            </a>
        </div>
    </div>

    <script>
        // Browser-Info anzeigen
        const userAgent = navigator.userAgent;
        const isSEB = userAgent.includes('SEB') || userAgent.includes('SafeExamBrowser');
        const isIpad = userAgent.includes('iPad');
        
        document.getElementById('sebStatus').innerHTML = isSEB ? 
            '<span class="badge bg-success">✅ SEB erkannt</span>' : 
            '<span class="badge bg-danger">❌ Kein SEB</span>';
        document.getElementById('platform').textContent = isIpad ? 'iPad' : 'Desktop';
        document.getElementById('userAgent').textContent = userAgent.substring(0, 80) + '...';
        
        console.log('🧪 SEB URL-Filter Test geladen');
        console.log('📱 Platform:', isIpad ? 'iPad' : 'Desktop');
        console.log('🔒 SEB Status:', isSEB ? 'Erkannt' : 'Nicht erkannt');
        
        if (!isSEB) {
            console.log('⚠️ WARNUNG: Nicht im SEB - URL-Tests zeigen nur "Protokoll unbekannt"');
        } else {
            console.log('✅ SEB erkannt - URL-Tests sollten SEB beenden oder "blockierte URL" anzeigen');
        }
        
        // Click-Tracking für Debug
        document.querySelectorAll('.test-link').forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('🔗 Klick auf:', this.href);
                
                if (!isSEB) {
                    console.log('ℹ️ Nicht im SEB - Link wird wahrscheinlich nicht funktionieren');
                }
            });
        });
    </script>
</body>
</html>
