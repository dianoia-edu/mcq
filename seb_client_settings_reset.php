<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Client Settings Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="bi bi-arrow-clockwise me-2"></i>SEB Client Settings Reset</h1>
        
        <div class="alert alert-success">
            <h6><i class="bi bi-check-circle me-2"></i>OFFIZIELLE LÖSUNG GEFUNDEN!</h6>
            <p><strong>Laut SEB-Dokumentation:</strong> Das Problem liegt an beschädigten SebClientSettings.seb</p>
            <p><strong>Lösung:</strong> Client-Einstellungen zurücksetzen durch Löschen der Datei</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5><i class="bi bi-exclamation-triangle me-2"></i>Problem identifiziert</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Beschädigte Datei:</strong></p>
                        <code>C:\Users\kaaag\AppData\Roaming\SafeExamBrowser\SebClientSettings.seb</code>
                        
                        <p class="mt-3"><strong>Symptome:</strong></p>
                        <ul>
                            <li>UriFormatException in ServerOperation</li>
                            <li>"Das URI-Format konnte nicht bestimmt werden"</li>
                            <li>Session Start Failed</li>
                            <li>SEB versucht ungültige Server-URL zu laden</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-tools me-2"></i>Offizielle Lösung</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Schritte zum Reset:</strong></p>
                        <ol>
                            <li><strong>SEB komplett schließen</strong></li>
                            <li><strong>Windows + R</strong> drücken</li>
                            <li><strong>%appdata%</strong> eingeben und Enter</li>
                            <li><strong>SafeExamBrowser</strong> Ordner öffnen</li>
                            <li><strong>SebClientSettings.seb</strong> löschen</li>
                            <li><strong>SEB neu starten</strong></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-info-circle me-2"></i>Wichtige Hinweise</h6>
                    <ul class="mb-0">
                        <li><strong>Backup erstellen:</strong> Kopieren Sie die Datei vor dem Löschen (falls Sie sie wiederherstellen möchten)</li>
                        <li><strong>Alle SEB-Instanzen schließen:</strong> Prüfen Sie den Task-Manager auf laufende SafeExamBrowser Prozesse</li>
                        <li><strong>Lokale Einstellungen gehen verloren:</strong> Alle benutzerdefinierten Client-Einstellungen werden zurückgesetzt</li>
                        <li><strong>Neue Standard-Einstellungen:</strong> SEB erstellt automatisch neue, saubere Einstellungen</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="bi bi-folder me-2"></i>Datei-Pfade</h5>
                    </div>
                    <div class="card-body">
                        <h6>Windows AppData Verzeichnis:</h6>
                        <div class="input-group mb-3">
                            <span class="input-group-text">Schnell-Zugriff:</span>
                            <input type="text" class="form-control" value="%appdata%" readonly>
                            <button class="btn btn-outline-primary" onclick="copyToClipboard('%appdata%')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        
                        <h6>Vollständiger Pfad:</h6>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="C:\Users\kaaag\AppData\Roaming\SafeExamBrowser\" readonly>
                            <button class="btn btn-outline-primary" onclick="copyToClipboard('C:\\Users\\kaaag\\AppData\\Roaming\\SafeExamBrowser\\')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        
                        <h6>Zu löschende Datei:</h6>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="SebClientSettings.seb" readonly>
                            <button class="btn btn-outline-primary" onclick="copyToClipboard('SebClientSettings.seb')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-success">
                    <h6><i class="bi bi-check-circle-fill me-2"></i>Nach dem Reset testen</h6>
                    <p><strong>Wenn SebClientSettings.seb gelöscht wurde:</strong></p>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="seb_config_ultra_minimal.php?code=TEST" class="btn btn-success" target="_blank">
                            <i class="bi bi-download me-2"></i>Ultra Minimal Config testen
                        </a>
                        <a href="seb_config_override_server.php?code=TEST" class="btn btn-primary" target="_blank">
                            <i class="bi bi-download me-2"></i>Server Override Config testen
                        </a>
                        <a href="seb_config_no_server.php?code=TEST" class="btn btn-info" target="_blank">
                            <i class="bi bi-download me-2"></i>No Server Config testen
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="bi bi-book me-2"></i>Quelle: Offizielle SEB-Dokumentation</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Referenz:</strong></p>
                        <ul>
                            <li><a href="https://safeexambrowser.org" target="_blank">safeexambrowser.org</a> - Offizielle Website</li>
                            <li><a href="https://sourceforge.net/p/seb/discussion/844844/" target="_blank">SEB SourceForge Forum</a> - Community Support</li>
                            <li><strong>Zitat:</strong> "Sie können die lokalen SEB-Client-Einstellungen zurücksetzen, indem Sie die Datei 'SebClientSettings.seb' im AppData-Verzeichnis löschen"</li>
                        </ul>
                        
                        <p class="mt-3"><strong>Warum das funktioniert:</strong></p>
                        <ul class="mb-0">
                            <li>SEB lädt IMMER zuerst SebClientSettings.seb (globale Einstellungen)</li>
                            <li>Dann lädt SEB die spezifische .seb-Datei (unsere Konfiguration)</li>
                            <li>Server-Einstellungen aus SebClientSettings.seb können NICHT überschrieben werden</li>
                            <li>Durch Löschen von SebClientSettings.seb verwendet SEB Standard-Einstellungen ohne Server</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Feedback
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = '<i class="bi bi-check2 me-2"></i>In Zwischenablage kopiert!';
                document.body.appendChild(toast);
                setTimeout(() => document.body.removeChild(toast), 2000);
            }).catch(err => {
                prompt('Manuell kopieren:', text);
            });
        }
    </script>
</body>
</html>
