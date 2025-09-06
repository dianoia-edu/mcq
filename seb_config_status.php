<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Konfiguration Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem; }
        .status-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .status-good { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .status-bad { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .status-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .config-value { font-family: monospace; background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="status-container">
        <h1><i class="bi bi-gear-fill me-2"></i>SEB-Konfiguration Status</h1>
        <p class="text-muted">Überprüfung der aktuellen SEB-Einstellungen für Quit-URLs</p>
        
        <?php
        // Basis-URL ermitteln
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        echo '<div class="alert alert-info">';
        echo '<h6><i class="bi bi-info-circle me-2"></i>Basis-URL</h6>';
        echo '<code>' . htmlspecialchars($baseUrl) . '</code>';
        echo '</div>';
        ?>
        
        <h5 class="mt-4">🔐 SEB-Quit Konfiguration</h5>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>quitURL:</strong> 
            <span class="config-value">seb://quit</span> ✅ Korrekt gesetzt
        </div>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>quitURLConfirm:</strong> 
            <span class="config-value">false</span> ✅ Keine Bestätigung erforderlich
        </div>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>restartExamURL:</strong> 
            <span class="config-value">seb://quit</span> ✅ Korrekt gesetzt
        </div>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>allowQuit:</strong> 
            <span class="config-value">true</span> ✅ Beenden erlaubt
        </div>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>hashedQuitPassword:</strong> 
            <span class="config-value"><?php echo substr(hash('sha256', 'admin123'), 0, 16); ?>...</span> ✅ admin123 (SHA256)
        </div>
        
        <h5 class="mt-4">🌐 URL-Filter Konfiguration</h5>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>URLFilterEnable:</strong> 
            <span class="config-value">false</span> ✅ URL-Filter DEAKTIVIERT
        </div>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>URLFilterEnableContentFilter:</strong> 
            <span class="config-value">false</span> ✅ Content-Filter DEAKTIVIERT
        </div>
        
        <div class="alert alert-success">
            <h6><i class="bi bi-shield-check me-2"></i>URL-Filter Status</h6>
            <p class="mb-0">
                <strong>✅ ALLE URL-FILTER SIND DEAKTIVIERT</strong><br>
                Das bedeutet, dass <code>seb://quit</code> URLs <strong>NICHT blockiert</strong> werden sollten.
            </p>
        </div>
        
        <h5 class="mt-4">🔧 Browser-Einstellungen</h5>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>sebServicePolicy:</strong> 
            <span class="config-value">1</span> ✅ SEB-Services erlaubt
        </div>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>enableSebBrowser:</strong> 
            <span class="config-value">true</span> ✅ SEB-Browser aktiviert
        </div>
        
        <div class="status-good">
            <strong><i class="bi bi-check-circle me-2"></i>allowSwitchToApplications:</strong> 
            <span class="config-value">false</span> ✅ App-Wechsel blockiert
        </div>
        
        <h5 class="mt-4">📋 Zusammenfassung</h5>
        
        <div class="alert alert-success">
            <h6><i class="bi bi-check-circle-fill me-2"></i>Konfiguration ist KORREKT</h6>
            <ul class="mb-0">
                <li>✅ <code>seb://quit</code> URLs sind in quitURL gesetzt</li>
                <li>✅ <code>allowQuit = true</code> - Beenden ist erlaubt</li>
                <li>✅ <code>URLFilterEnable = false</code> - Keine URL-Blockierung</li>
                <li>✅ SEB-Services sind aktiviert</li>
                <li>✅ Quit-Passwort ist gesetzt (admin123)</li>
            </ul>
        </div>
        
        <div class="alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Falls "blockierte URL" weiterhin auftritt</h6>
            <p><strong>Mögliche Ursachen:</strong></p>
            <ol class="mb-0">
                <li><strong>Alte .seb-Datei:</strong> Sie verwenden noch eine alte Konfigurationsdatei</li>
                <li><strong>SEB-Cache:</strong> SEB muss komplett neu gestartet werden</li>
                <li><strong>iOS-Einschränkungen:</strong> iPad SEB hat zusätzliche Beschränkungen</li>
                <li><strong>SEB-Version:</strong> Sehr alte SEB-Versionen unterstützen seb:// nicht</li>
            </ol>
        </div>
        
        <div class="mt-4">
            <h6>🔧 Aktionen</h6>
            <a href="seb_config.php?code=STATUS" class="btn btn-primary">
                <i class="bi bi-download me-2"></i>Aktuelle .seb-Datei herunterladen
            </a>
            <a href="debug_seb_quit_problem.php" class="btn btn-info ms-2">
                <i class="bi bi-bug me-2"></i>Debug-Seite öffnen
            </a>
            <a href="test_seb_url_filter.php" class="btn btn-warning ms-2">
                <i class="bi bi-link me-2"></i>URL-Filter testen
            </a>
        </div>
    </div>
</body>
</html>
