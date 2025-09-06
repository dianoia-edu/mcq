<?php
/**
 * SEB-Konfiguration Vorschau und Validierung
 * Zeigt an, welche Einschränkungen in der generierten .seb Datei enthalten sind
 */

// Hole Test-Code
$testCode = $_GET['code'] ?? '';

if (empty($testCode)) {
    die('Fehler: Kein Test-Code angegeben. URL: seb_config_preview.php?code=ABC');
}

// Bestimme Basis-Verzeichnis
$currentDir = dirname(__FILE__);
$isInTeacherDir = (basename($currentDir) === 'teacher');
$baseDir = $isInTeacherDir ? dirname($currentDir) : $currentDir;

// Finde Test-Datei
$testFile = $baseDir . '/tests/' . $testCode . '.xml';
if (!file_exists($testFile)) {
    $testPattern = $baseDir . '/tests/' . $testCode . '_*.xml';
    $matchingFiles = glob($testPattern);
    
    if (!empty($matchingFiles)) {
        $testFile = $matchingFiles[0];
    } else {
        die('Fehler: Test "' . htmlspecialchars($testCode) . '" nicht gefunden');
    }
}

// Test-Titel laden
$testTitle = $testCode;
try {
    $xml = simplexml_load_file($testFile);
    if ($xml && isset($xml->title)) {
        $testTitle = (string)$xml->title;
    }
} catch (Exception $e) {
    // Titel bleibt bei Fallback
}

// SEB-Konfiguration für Anzeige vorbereiten
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$testUrl = $baseUrl . dirname($_SERVER['PHP_SELF']) . '/name_form.php?code=' . urlencode($testCode) . '&seb=true';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB-Konfiguration Vorschau - <?php echo htmlspecialchars($testCode); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .config-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }
        .header-card {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .feature-enabled {
            color: #28a745;
            font-weight: bold;
        }
        .feature-disabled {
            color: #dc3545;
            font-weight: bold;
        }
        .feature-group {
            border-left: 4px solid #ff6b35;
            padding-left: 1rem;
            margin-bottom: 1.5rem;
        }
        .security-level {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        .level-extreme { background: #dc3545; color: white; }
        .level-high { background: #fd7e14; color: white; }
        .level-medium { background: #ffc107; color: black; }
        .level-low { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <!-- Header -->
                <div class="config-card">
                    <div class="header-card">
                        <h1><i class="bi bi-shield-lock me-3"></i>SEB-Konfiguration Vorschau</h1>
                        <h3><?php echo htmlspecialchars($testTitle); ?></h3>
                        <p class="mb-0">Test-Code: <strong><?php echo htmlspecialchars($testCode); ?></strong></p>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="bi bi-info-circle text-primary me-2"></i>Basis-Informationen</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Start-URL:</strong><br><small class="text-muted"><?php echo htmlspecialchars($testUrl); ?></small></li>
                                    <li><strong>Konfiguration:</strong> test_<?php echo htmlspecialchars($testCode); ?>.seb</li>
                                    <li><strong>Sicherheitslevel:</strong> <span class="security-level level-extreme">EXTREM</span></li>
                                </ul>
                                
                                <div class="mt-3 p-3 bg-warning rounded">
                                    <h6><i class="bi bi-key me-2"></i>SEB-Passwörter</h6>
                                    <p class="mb-1"><strong>Beenden-Passwort:</strong><br><code>LEHRER2024_<?php echo htmlspecialchars($testCode); ?></code></p>
                                    <p class="mb-0"><strong>Admin-Passwort:</strong><br><code>ADMIN2024_<?php echo htmlspecialchars($testCode); ?></code></p>
                                    <small class="text-muted">Diese Passwörter ermöglichen Ihnen das Beenden/Rekonfigurieren von SEB.</small>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary btn-lg">
                                    <i class="bi bi-download me-2"></i>SEB-Datei herunterladen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Browser-Einschränkungen -->
                <div class="config-card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="bi bi-browser-chrome me-2"></i>Browser-Einschränkungen</h4>
                    </div>
                    <div class="card-body">
                        <div class="feature-group">
                            <h6>Navigation & Steuerung</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Neu laden</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">URL-Leiste</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Vor/Zurück</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Drucken</span></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Externe Links</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Rechtschreibprüfung</span></li>
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">JavaScript</span></li>
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">JavaScript Alerts</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System-Sicherheit -->
                <div class="config-card">
                    <div class="card-header bg-danger text-white">
                        <h4><i class="bi bi-shield-exclamation me-2"></i>System-Sicherheit <span class="security-level level-extreme">EXTREM</span></h4>
                    </div>
                    <div class="card-body">
                        <div class="feature-group">
                            <h6>Kiosk-Modus</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">Neuer Desktop erstellen</span></li>
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">Explorer beenden</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">SEB beenden</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">App-Wechsel</span></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">Prozess-Überwachung</span></li>
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">Beendigungswarnung</span></li>
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">Neu-Laden-Warnung</span></li>
                                        <li><i class="bi bi-check-circle text-success me-2"></i><span class="feature-enabled">App-Switcher-Check</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tastenkombinationen -->
                <div class="config-card">
                    <div class="card-header bg-warning text-dark">
                        <h4><i class="bi bi-keyboard me-2"></i>Gesperrte Tastenkombinationen</h4>
                    </div>
                    <div class="card-body">
                        <div class="feature-group">
                            <h6>Funktionstasten</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>F1-F12</li>
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>ESC</li>
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>Print Screen</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>Alt + Tab</li>
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>Alt + F4</li>
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>Ctrl + Alt + Del</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>Windows-Taste</li>
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>Startmenü</li>
                                        <li><i class="bi bi-x-circle text-danger me-1"></i>Rechtsklick</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- iPad-spezifische Einstellungen -->
                <div class="config-card">
                    <div class="card-header" style="background: linear-gradient(45deg, #007AFF, #5856D6); color: white;">
                        <h4><i class="bi bi-tablet me-2"></i>iPad/iOS-spezifische Sicherheit 🍎</h4>
                    </div>
                    <div class="card-body">
                        <div class="feature-group">
                            <h6>Hardware-Buttons</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Home-Button</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Lautstärke-Tasten</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Power-Button</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Bildschirm-Rotation</span></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">App-Switcher</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Kontrollzentrum</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Kamera-App</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Diktierfunktion</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="feature-group">
                            <h6>Touch-Gesten</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">4-Finger-Swipe</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Zoom-Gesten</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Long-Press</span></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Display-Mirroring</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Screen-Sharing</span></li>
                                        <li><i class="bi bi-x-circle text-danger me-2"></i><span class="feature-disabled">Video-Aufnahme</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- URL-Filter -->
                <div class="config-card">
                    <div class="card-header bg-success text-white">
                        <h4><i class="bi bi-funnel me-2"></i>URL-Filter (Whitelist)</h4>
                    </div>
                    <div class="card-body">
                        <div class="feature-group">
                            <h6>Erlaubte Domains</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check-circle text-success me-2"></i><code><?php echo htmlspecialchars($baseUrl); ?>/*</code></li>
                                <li><i class="bi bi-info-circle text-info me-2"></i>Alle anderen URLs sind <strong>blockiert</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Download & Aktionen -->
                <div class="config-card">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-4">
                                <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary btn-lg w-100 mb-2">
                                    <i class="bi bi-download me-2"></i>SEB-Datei herunterladen
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="seb_start.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-warning btn-lg w-100 mb-2">
                                    <i class="bi bi-shield-lock me-2"></i>SEB-Test starten
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="seb_anleitung.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-info btn-lg w-100 mb-2">
                                    <i class="bi bi-question-circle me-2"></i>SEB-Anleitung
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="teacher/teacher_dashboard.php" class="btn btn-secondary btn-lg w-100 mb-2">
                                    <i class="bi bi-arrow-left me-2"></i>Zurück zum Dashboard
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <p class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Diese Konfiguration stellt sicher, dass der Test in einer vollständig kontrollierten Umgebung durchgeführt wird.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
