<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Generierung Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem; }
        .test-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .status-good { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .status-bad { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .status-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="bi bi-gear-fill me-2"></i>SEB-Generierung Test</h1>
        <p class="text-muted">Prüfung ob seb_config.php korrekt funktioniert</p>
        
        <?php
        $testCode = 'TEST';
        $sebConfigUrl = 'seb_config.php?code=' . urlencode($testCode);
        
        echo '<div class="alert alert-info">';
        echo '<h6><i class="bi bi-info-circle me-2"></i>Test-Parameter</h6>';
        echo '<p><strong>Test-Code:</strong> <code>' . htmlspecialchars($testCode) . '</code></p>';
        echo '<p><strong>SEB-Config URL:</strong> <code>' . htmlspecialchars($sebConfigUrl) . '</code></p>';
        echo '</div>';
        
        // Versuche, seb_config.php zu laden
        echo '<h5 class="mt-4">🧪 SEB-Config Generierung testen</h5>';
        
        $sebConfigPath = __DIR__ . '/seb_config.php';
        
        if (!file_exists($sebConfigPath)) {
            echo '<div class="status-bad">';
            echo '<strong><i class="bi bi-x-circle me-2"></i>FEHLER:</strong> ';
            echo 'seb_config.php nicht gefunden!';
            echo '</div>';
        } else {
            echo '<div class="status-good">';
            echo '<strong><i class="bi bi-check-circle me-2"></i>OK:</strong> ';
            echo 'seb_config.php existiert';
            echo '</div>';
            
            // Teste PHP-Syntax
            $syntaxCheck = shell_exec('php -l ' . escapeshellarg($sebConfigPath) . ' 2>&1');
            
            if (strpos($syntaxCheck, 'No syntax errors') !== false) {
                echo '<div class="status-good">';
                echo '<strong><i class="bi bi-check-circle me-2"></i>PHP-SYNTAX:</strong> ';
                echo 'Keine Syntaxfehler gefunden';
                echo '</div>';
            } else {
                echo '<div class="status-bad">';
                echo '<strong><i class="bi bi-x-circle me-2"></i>PHP-SYNTAX FEHLER:</strong><br>';
                echo '<pre>' . htmlspecialchars($syntaxCheck) . '</pre>';
                echo '</div>';
            }
            
            // Versuche Output-Buffering zum Testen
            ob_start();
            $oldGet = $_GET;
            $_GET['code'] = $testCode;
            
            try {
                include $sebConfigPath;
                $output = ob_get_contents();
                ob_end_clean();
                $_GET = $oldGet;
                
                if (strlen($output) > 100) {
                    echo '<div class="status-good">';
                    echo '<strong><i class="bi bi-check-circle me-2"></i>GENERIERUNG:</strong> ';
                    echo 'SEB-Config erfolgreich generiert (' . strlen($output) . ' Zeichen)';
                    echo '</div>';
                    
                    // Zeige erste 500 Zeichen
                    echo '<div class="mt-3">';
                    echo '<h6>📄 Generierte SEB-Config (Auszug):</h6>';
                    echo '<pre>' . htmlspecialchars(substr($output, 0, 500)) . '...</pre>';
                    echo '</div>';
                    
                } else {
                    echo '<div class="status-bad">';
                    echo '<strong><i class="bi bi-x-circle me-2"></i>GENERIERUNG FEHLGESCHLAGEN:</strong> ';
                    echo 'Output zu kurz oder leer (' . strlen($output) . ' Zeichen)';
                    if (!empty($output)) {
                        echo '<pre>' . htmlspecialchars($output) . '</pre>';
                    }
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                ob_end_clean();
                $_GET = $oldGet;
                
                echo '<div class="status-bad">';
                echo '<strong><i class="bi bi-x-circle me-2"></i>EXCEPTION:</strong> ';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        ?>
        
        <h5 class="mt-4">🔗 Direkte Tests</h5>
        
        <div class="mt-3">
            <a href="seb_config.php?code=TEST" class="btn btn-primary" target="_blank">
                <i class="bi bi-download me-2"></i>SEB-Datei herunterladen (TEST)
            </a>
            <a href="seb_config_preview.php?code=TEST" class="btn btn-info ms-2" target="_blank">
                <i class="bi bi-eye me-2"></i>SEB-Config ansehen
            </a>
            <a href="seb_config_status.php" class="btn btn-success ms-2" target="_blank">
                <i class="bi bi-check-circle me-2"></i>Config-Status
            </a>
        </div>
        
        <div class="mt-4 alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Troubleshooting</h6>
            <p><strong>Falls Download fehlschlägt:</strong></p>
            <ul class="mb-0">
                <li>📋 PHP-Syntax-Fehler in seb_config.php</li>
                <li>🔧 Fehlerhafte XML-Struktur</li>
                <li>💾 Header-Probleme (Content-Type, Content-Disposition)</li>
                <li>⚠️ PHP-Code im XML-Bereich (nicht im PHP-Bereich)</li>
            </ul>
        </div>
    </div>
</body>
</html>
