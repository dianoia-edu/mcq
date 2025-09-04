<?php
/**
 * Reparatur-Script für Instanzen
 * Fügt fehlende Admin-Codes und API-Konfiguration hinzu
 */

// Sicherheitscheck
if (!isset($_GET['repair_key']) || $_GET['repair_key'] !== 'repair_instances_2024') {
    die('Reparatur-Zugriff verweigert.');
}

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === 'true';

if ($isAjax) {
    header('Content-Type: application/json');
    
    try {
        $results = performRepair();
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
    <title>Instanzen Reparatur</title>
    <style>
        body { font-family: monospace; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Instanzen Reparatur</h1>
    
    <div class="section warning">
        <h2>⚠️ Wichtiger Hinweis</h2>
        <p>Dieses Script repariert fehlende Konfigurationen in allen Instanzen:</p>
        <ul>
            <li>Fügt fehlende <code>admin_access_code</code> hinzu</li>
            <li>Kopiert API-Konfiguration vom Hauptsystem</li>
            <li>Aktualisiert <code>app_config.json</code> Dateien</li>
        </ul>
        <p><strong>Dies überschreibt existierende Konfigurationen!</strong></p>
    </div>
    
    <div class="section">
        <h2>Reparatur ausführen</h2>
        <button class="btn btn-primary" onclick="performRepair()">🔧 Alle Instanzen reparieren</button>
        <button class="btn btn-danger" onclick="testRepair()">🧪 Test-Modus (nur anzeigen)</button>
        <div id="repairResult" style="margin-top: 15px;"></div>
    </div>
    
    <script>
    function performRepair() {
        const resultDiv = document.getElementById('repairResult');
        resultDiv.innerHTML = '<p>🔄 Repariere Instanzen...</p>';
        
        fetch('?repair_key=repair_instances_2024&ajax=true')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h3>✅ Reparatur erfolgreich</h3>
                            <p><strong>Reparierte Instanzen:</strong> ${data.repaired_count}</p>
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
    
    function testRepair() {
        alert('Test-Modus wird implementiert...');
    }
    </script>
    
</body>
</html>

<?php

function performRepair() {
    $instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
    $mainConfigPath = '/var/www/dianoia-ai.de/mcq-test-system/config/app_config.json';
    
    $results = [
        'success' => true,
        'repaired_count' => 0,
        'error_count' => 0,
        'details' => []
    ];
    
    // Lade Haupt-Konfiguration
    if (!file_exists($mainConfigPath)) {
        throw new Exception('Haupt-Konfiguration nicht gefunden: ' . $mainConfigPath);
    }
    
    $mainConfig = json_decode(file_get_contents($mainConfigPath), true);
    if (!$mainConfig) {
        throw new Exception('Haupt-Konfiguration konnte nicht geladen werden');
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
        
        $configPath = $mcqPath . '/config/app_config.json';
        
        try {
            // Generiere Admin-Code (einfach für Reparatur)
            $adminCode = 'admin_' . $dir;
            
            // Lade existierende Konfiguration
            $instanceConfig = [];
            if (file_exists($configPath)) {
                $instanceConfig = json_decode(file_get_contents($configPath), true) ?: [];
            }
            
            // Füge fehlende Werte hinzu
            $updated = false;
            
            if (!isset($instanceConfig['admin_access_code'])) {
                $instanceConfig['admin_access_code'] = $adminCode;
                $updated = true;
            }
            
            // Kopiere API-Konfiguration vom Hauptsystem
            $apiKeys = ['api_key', 'api_base_url', 'max_tokens', 'temperature'];
            foreach ($apiKeys as $key) {
                if (isset($mainConfig[$key]) && !isset($instanceConfig[$key])) {
                    $instanceConfig[$key] = $mainConfig[$key];
                    $updated = true;
                }
            }
            
            if ($updated) {
                // Erstelle config-Verzeichnis falls es nicht existiert
                $configDir = dirname($configPath);
                if (!is_dir($configDir)) {
                    mkdir($configDir, 0777, true);
                }
                
                // Schreibe aktualisierte Konfiguration
                if (file_put_contents($configPath, json_encode($instanceConfig, JSON_PRETTY_PRINT))) {
                    $results['repaired_count']++;
                    $results['details'][] = [
                        'instance' => $dir,
                        'status' => 'success',
                        'admin_code' => $adminCode,
                        'config_path' => $configPath
                    ];
                } else {
                    throw new Exception('Konnte Konfiguration nicht schreiben');
                }
            } else {
                $results['details'][] = [
                    'instance' => $dir,
                    'status' => 'skipped',
                    'reason' => 'Konfiguration bereits vollständig'
                ];
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
