<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Admin-Passwort Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="bi bi-shield-lock me-2"></i>SEB Admin-Passwort Problem Test</h1>
        
        <?php
        $testCode = $_GET['code'] ?? 'TEST';
        
        // Basis-URL ermitteln
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $baseUrl = rtrim($baseUrl, '/');
        
        echo '<div class="alert alert-info">';
        echo '<h6><i class="bi bi-info-circle me-2"></i>Test-Informationen</h6>';
        echo '<p><strong>Test-Code:</strong> ' . htmlspecialchars($testCode) . '</p>';
        echo '<p><strong>Problem:</strong> Windows SEB verlangt Administrator-Passwort</p>';
        echo '</div>';
        ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="bi bi-gear me-2"></i>Standard Config</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Normale SEB-Konfiguration mit Admin-Einstellungen</p>
                        <div class="d-grid gap-2">
                            <a href="seb_config.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-outline-primary" target="_blank">
                                <i class="bi bi-download me-2"></i>Standard Config testen
                            </a>
                        </div>
                        <small class="text-danger mt-2 d-block">⚠️ Verlangt möglicherweise Admin-Passwort</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5><i class="bi bi-lightning me-2"></i>Flexible Config</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Flexible Rekonfiguration ohne Admin-Settings</p>
                        <div class="d-grid gap-2">
                            <a href="seb_config_flexible.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-outline-warning" target="_blank">
                                <i class="bi bi-download me-2"></i>Flexible Config testen
                            </a>
                        </div>
                        <small class="text-warning mt-2 d-block">⚡ Reduzierte Admin-Anfragen</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-mortarboard me-2"></i>Exam Mode Config</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><strong>NEUE LÖSUNG:</strong> Exam Mode ohne Admin-Rechte</p>
                        <div class="d-grid gap-2">
                            <a href="seb_config_exam_mode.php?code=<?php echo urlencode($testCode); ?>" class="btn btn-success" target="_blank">
                                <i class="bi bi-download me-2"></i>Exam Mode testen
                            </a>
                        </div>
                        <small class="text-success mt-2 d-block">✅ KEIN Admin-Passwort erforderlich</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="bi bi-list-check me-2"></i>Unterschiede der Konfigurationen</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Setting</th>
                                        <th>Standard Config</th>
                                        <th>Flexible Config</th>
                                        <th class="table-success">Exam Mode Config</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>sebMode</code></td>
                                        <td>1 (Exam)</td>
                                        <td>0 (Client)</td>
                                        <td class="table-success"><strong>1 (Exam)</strong></td>
                                    </tr>
                                    <tr>
                                        <td><code>configPurpose</code></td>
                                        <td>0</td>
                                        <td>1</td>
                                        <td class="table-success"><strong>0 (Exam)</strong></td>
                                    </tr>
                                    <tr>
                                        <td><code>sebConfigPurpose</code></td>
                                        <td>0</td>
                                        <td>1</td>
                                        <td class="table-success"><strong>0 (Exam)</strong></td>
                                    </tr>
                                    <tr>
                                        <td><code>startExamMode</code></td>
                                        <td>true</td>
                                        <td>❌ nicht gesetzt</td>
                                        <td class="table-success"><strong>true</strong></td>
                                    </tr>
                                    <tr>
                                        <td><code>examMode</code></td>
                                        <td>true</td>
                                        <td>❌ nicht gesetzt</td>
                                        <td class="table-success"><strong>true</strong></td>
                                    </tr>
                                    <tr>
                                        <td><code>sebConfigurationIsExam</code></td>
                                        <td>true</td>
                                        <td>❌ nicht gesetzt</td>
                                        <td class="table-success"><strong>true</strong></td>
                                    </tr>
                                    <tr>
                                        <td><code>sebRequiresAdminRights</code></td>
                                        <td>false</td>
                                        <td>false</td>
                                        <td class="table-success"><strong>false</strong></td>
                                    </tr>
                                    <tr>
                                        <td><code>forceReconfiguration</code></td>
                                        <td>false</td>
                                        <td>false</td>
                                        <td class="table-success"><strong>false</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-success">
                    <h5><i class="bi bi-lightbulb me-2"></i>Lösung: Exam Mode Config</h5>
                    <p><strong>Die neue "Exam Mode Config" sollte das Admin-Passwort Problem lösen:</strong></p>
                    <ul class="mb-0">
                        <li><strong>sebMode = 1:</strong> Exam Mode statt Client Mode</li>
                        <li><strong>configPurpose = 0:</strong> Exam-Zweck statt Client-Zweck</li>
                        <li><strong>startExamMode = true:</strong> Explizite Exam-Mode Aktivierung</li>
                        <li><strong>examMode = true:</strong> Zusätzliche Exam-Mode Markierung</li>
                        <li><strong>sebConfigurationIsExam = true:</strong> Config als Exam markiert</li>
                        <li><strong>Minimale Sicherheitseinstellungen:</strong> Nur notwendige Restriktionen</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Test-Anleitung</h6>
                    <ol>
                        <li>Testen Sie zuerst die <strong>Standard Config</strong> - sollte Admin-Passwort verlangen</li>
                        <li>Dann testen Sie die <strong>Exam Mode Config</strong> - sollte KEIN Admin-Passwort verlangen</li>
                        <li>Falls weiterhin Probleme: Prüfen Sie ob SEB als Administrator installiert wurde</li>
                        <li>Möglicherweise Antivirus-Software temporär deaktivieren</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
