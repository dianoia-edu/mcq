<?php
/**
 * Repariert index.php in allen Instanzen für Admin-Login
 */

if (!isset($_GET['fix_key']) || $_GET['fix_key'] !== 'fix_index_2024') {
    die('Fix-Zugriff verweigert.');
}

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === 'true';

if ($isAjax) {
    header('Content-Type: application/json');
    
    try {
        $results = performIndexFix();
        echo json_encode($results);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Index.php Reparatur</title>
    <style>
        body { font-family: monospace; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Index.php Reparatur</h1>
    
    <div class="section warning">
        <h2>⚠️ Was wird gemacht</h2>
        <p>Dieses Script repariert die index.php Dateien in allen Instanzen:</p>
        <ul>
            <li>Fügt Admin-Code Validierung hinzu</li>
            <li>Verwendet Instanz-spezifische Admin-Codes aus config/app_config.json</li>
            <li>Behält Fallback auf admin123 bei</li>
            <li>Überschreibt existierende index.php Dateien</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Reparatur starten</h2>
        <button class="btn btn-primary" onclick="performFix()">🔧 Alle index.php reparieren</button>
        <div id="fixResult" style="margin-top: 15px;"></div>
    </div>
    
    <script>
    function performFix() {
        const resultDiv = document.getElementById('fixResult');
        resultDiv.innerHTML = '<p>🔄 Repariere index.php Dateien...</p>';
        
        fetch('?fix_key=fix_index_2024&ajax=true')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h3>✅ Reparatur erfolgreich</h3>
                            <p><strong>Reparierte Instanzen:</strong> ${data.fixed_count}</p>
                            <p><strong>Fehler:</strong> ${data.error_count}</p>
                            <details>
                                <summary>Details anzeigen</summary>
                                <pre>${JSON.stringify(data, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>❌ Reparatur fehlgeschlagen</h3>
                            <p>${data.error}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h3>❌ Kommunikationsfehler</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            });
    }
    </script>
    
</body>
</html>

<?php

function performIndexFix() {
    $instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
    $templatePath = __DIR__ . '/instance_index_template.php';
    
    $results = [
        'success' => true,
        'fixed_count' => 0,
        'error_count' => 0,
        'details' => []
    ];
    
    // Lade Template
    if (!file_exists($templatePath)) {
        throw new Exception('Template-Datei nicht gefunden: ' . $templatePath);
    }
    
    $templateContent = file_get_contents($templatePath);
    if (!$templateContent) {
        throw new Exception('Template konnte nicht gelesen werden');
    }
    
    // Durchsuche alle Instanzen
    if (!is_dir($instancesBasePath)) {
        throw new Exception('Instanzen-Verzeichnis nicht gefunden: ' . $instancesBasePath);
    }
    
    $dirs = scandir($instancesBasePath);
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $instancePath = $instancesBasePath . $dir;
        $mcqPath = $instancePath . '/mcq-test-system';
        
        if (!is_dir($mcqPath)) continue;
        
        $indexPath = $mcqPath . '/index.php';
        
        try {
            // Schreibe neue index.php
            if (file_put_contents($indexPath, $templateContent)) {
                $results['fixed_count']++;
                $results['details'][] = [
                    'instance' => $dir,
                    'status' => 'success',
                    'file' => $indexPath
                ];
            } else {
                throw new Exception('Konnte index.php nicht schreiben');
            }
            
        } catch (Exception $e) {
            $results['error_count']++;
            $results['details'][] = [
                'instance' => $dir,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $results;
}

?>
