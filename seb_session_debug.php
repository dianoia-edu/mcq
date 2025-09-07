<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Session Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="bi bi-bug me-2"></i>SEB Session Debug</h1>
        
        <?php
        $testCode = $_GET['code'] ?? 'TEST';
        
        // Basis-URL ermitteln
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        echo '<div class="alert alert-danger">';
        echo '<h6><i class="bi bi-exclamation-triangle me-2"></i>SEB Fehler: "Konnte keine neue Sitzung starten"</h6>';
        echo '<p><strong>Log-Datei:</strong> C:\Users\kaaag\AppData\Local\SafeExamBrowser\Logs\2025-09-07_10h22m04s_Runtime.log</p>';
        echo '<p><strong>Test-Code:</strong> ' . htmlspecialchars($testCode) . '</p>';
        echo '</div>';
        ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5><i class="bi bi-exclamation-circle me-2"></i>Mögliche Ursachen</h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li><strong>Bereits laufende SEB-Instanz</strong></li>
                            <li><strong>Beschädigte SEB-Installation</strong></li>
                            <li><strong>Konflikte mit anderen Programmen</strong></li>
                            <li><strong>Fehlerhafte Konfigurationsdatei</strong></li>
                            <li><strong>Antivirus-Software blockiert SEB</strong></li>
                            <li><strong>Unzureichende Benutzerrechte</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5><i class="bi bi-tools me-2"></i>Lösungsschritte</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>SEB komplett schließen</strong> (Task-Manager prüfen)</li>
                            <li><strong>Antivirus temporär deaktivieren</strong></li>
                            <li><strong>Als Administrator ausführen</strong></li>
                            <li><strong>Minimale Konfiguration testen</strong></li>
                            <li><strong>SEB-Cache leeren</strong></li>
                            <li><strong>SEB neu installieren</strong></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-download me-2"></i>Test-Konfigurationen</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6><i class="bi bi-circle me-2"></i>Minimal Config</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">Absolut minimale Einstellungen</p>
                                        <a href="seb_config_minimal.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-success w-100" target="_blank">
                                            <i class="bi bi-download me-2"></i>Minimal testen
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6><i class="bi bi-mortarboard me-2"></i>Exam Mode</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">Exam Mode Konfiguration</p>
                                        <a href="seb_config_exam_mode.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-primary w-100" target="_blank">
                                            <i class="bi bi-download me-2"></i>Exam Mode testen
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6><i class="bi bi-lightning me-2"></i>Flexible</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">Flexible Konfiguration</p>
                                        <a href="seb_config_flexible.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-warning w-100" target="_blank">
                                            <i class="bi bi-download me-2"></i>Flexible testen
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>Direkte URL Tests</h6>
                    <p><strong>Test-URLs prüfen:</strong></p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group mb-2">
                                <span class="input-group-text">Test-Seite:</span>
                                <input type="text" class="form-control" value="<?php echo $baseUrl; ?>/name_form.php?code=<?php echo urlencode($testCode); ?>&seb=true" readonly>
                                <a href="<?php echo $baseUrl; ?>/name_form.php?code=<?php echo urlencode($testCode); ?>&seb=true" class="btn btn-outline-primary" target="_blank">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group mb-2">
                                <span class="input-group-text">Index-Seite:</span>
                                <input type="text" class="form-control" value="<?php echo $baseUrl; ?>/index.php?code=<?php echo urlencode($testCode); ?>" readonly>
                                <a href="<?php echo $baseUrl; ?>/index.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-outline-primary" target="_blank">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>SEB Reset Anleitung</h6>
                    <p><strong>Falls weiterhin Probleme:</strong></p>
                    <ol>
                        <li><strong>Task-Manager öffnen</strong> → Alle "SafeExamBrowser" Prozesse beenden</li>
                        <li><strong>Windows + R</strong> → <code>%appdata%</code> → SafeExamBrowser Ordner löschen</li>
                        <li><strong>Windows + R</strong> → <code>%localappdata%</code> → SafeExamBrowser Ordner löschen</li>
                        <li><strong>SEB neu starten</strong> und minimale Konfiguration testen</li>
                        <li><strong>Falls immer noch Fehler:</strong> SEB komplett neu installieren</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="bi bi-file-text me-2"></i>SEB Log-Datei Analyse</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Log-Datei-Pfad:</strong></p>
                        <code>C:\Users\kaaag\AppData\Local\SafeExamBrowser\Logs\2025-09-07_10h22m04s_Runtime.log</code>
                        
                        <p class="mt-3"><strong>Häufige Fehlerursachen in Log-Dateien:</strong></p>
                        <ul>
                            <li><code>AccessViolationException</code> → SEB-Installation beschädigt</li>
                            <li><code>FileNotFoundException</code> → Fehlende SEB-Dateien</li>
                            <li><code>UnauthorizedAccessException</code> → Unzureichende Berechtigungen</li>
                            <li><code>ConfigurationException</code> → Fehlerhafte .seb-Datei</li>
                            <li><code>NetworkException</code> → Netzwerk-/URL-Probleme</li>
                        </ul>
                        
                        <p class="mt-3"><strong>So öffnen Sie die Log-Datei:</strong></p>
                        <ol>
                            <li>Windows-Explorer öffnen</li>
                            <li>In die Adressleiste eingeben: <code>%localappdata%\SafeExamBrowser\Logs</code></li>
                            <li>Neueste .log-Datei öffnen</li>
                            <li>Nach Fehlermeldungen suchen</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
