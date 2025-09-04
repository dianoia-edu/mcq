<?php
session_start();

if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Ermittle den korrekten Includes-Pfad basierend auf der aktuellen Position
function getIncludesPath($relativePath) {
    $currentDir = dirname(__FILE__);
    $currentBasename = basename($currentDir);
    
    // Debug-Logging
    error_log("getIncludesPath Debug: currentDir=$currentDir, basename=$currentBasename, relativePath=$relativePath");
    
    // Prüfe, ob wir im teacher-Verzeichnis sind (Hauptinstanz)
    if ($currentBasename === 'teacher') {
        // Hauptinstanz: /xampp/htdocs/mcq-test-system/teacher -> /xampp/htdocs/mcq-test-system/includes
        $path = dirname($currentDir) . '/includes/' . $relativePath;
        error_log("getIncludesPath: Hauptinstanz-Pfad: $path");
        return $path;
    } else {
        // Instanz: Dashboard liegt in /lehrer_instanzen/instanzX/mcq-test-system/teacher
        // aber currentDir ist /lehrer_instanzen/instanzX/mcq-test-system, da das Dashboard im Root liegt
        // Korrigiere: Wenn wir das teacher_dashboard.php direkt im Root haben
        $path = $currentDir . '/includes/' . $relativePath;
        error_log("getIncludesPath: Instanz-Pfad: $path");
        return $path;
    }
}

// Ermittle Base-Pfad basierend auf aktueller Position
$currentDir = dirname(__FILE__);
$isInTeacherDir = basename($currentDir) === 'teacher';

// Zusätzliche Prüfung: Sind wir im Hauptsystem oder in einer Instanz?
$isMainSystem = (strpos($currentDir, 'lehrer_instanzen') === false);
$showInstanceManagement = $isInTeacherDir && $isMainSystem;

if ($isInTeacherDir) {
    // Hauptinstanz: Verzeichnisse eine Ebene höher erstellen
    $baseDir = dirname($currentDir);
} else {
    // Instanz: Verzeichnisse im aktuellen Verzeichnis erstellen  
    $baseDir = $currentDir;
}

error_log("Directory creation: currentDir=$currentDir, isInTeacherDir=" . ($isInTeacherDir ? 'true' : 'false') . ", baseDir=$baseDir");

// Erstelle erforderliche Verzeichnisse
$directories = [
    $baseDir . '/includes',
    $baseDir . '/teacher', 
    $baseDir . '/tests',
    $baseDir . '/results',
    $baseDir . '/qrcodes'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            error_log("Created directory: $dir");
        } else {
            error_log("Failed to create directory: $dir");
        }
    } else {
        error_log("Directory already exists: $dir");
    }
}

// Lade vorhandene Tests
$tests = [];
$selectedTest = null;
$testFiles = glob($baseDir . '/tests/*.xml');

error_log("Loading tests from: " . $baseDir . '/tests/*.xml - Found: ' . count($testFiles) . ' files');

// Wenn ein Test ausgewählt wurde
if (isset($_GET['test']) && !empty($_GET['test'])) {
    $requestedTest = $_GET['test'];
    error_log("Requested test: " . $requestedTest);
}

foreach ($testFiles as $testFile) {
    try {
        $xml = simplexml_load_file($testFile);
        if ($xml === false) {
            continue;
        }
        
        $testName = pathinfo($testFile, PATHINFO_FILENAME);
        $tests[$testName] = [
            'name' => $testName,
            'title' => (string)$xml->title ?: $testName,
            'questions' => count($xml->questions->question),
            'file' => $testFile
        ];
        
        // Prüfe ob dies der gewünschte Test ist
        if (isset($requestedTest) && $testName === $requestedTest) {
            $selectedTest = $tests[$testName];
            error_log("Selected test found: " . $selectedTest['title']);
        }
    } catch (Exception $e) {
        error_log("Error loading test file {$testFile}: " . $e->getMessage());
    }
}

// $isInTeacherDir wurde bereits oben definiert
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ Test System - Teacher Dashboard</title>
    
    <!-- Tab-Funktion VOR HTML-Code definieren -->
    <script>
    // SOFORTIGE DEBUG AUSGABE
    console.log('DIREKTE DEBUG AUSGABE - ' + new Date().toLocaleString());
    
    // Tab-Funktion vor HTML-Code definieren
    function activateTab(tabId) {
        console.log('Aktiviere Tab:', tabId);
        
        // Alle Tabs deaktivieren
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            if (tab) {
                tab.classList.remove('active');
            }
        });
        
        // Alle Tab-Inhalte verstecken
        const tabPanes = document.querySelectorAll('.tab-pane');
        tabPanes.forEach(pane => {
            if (pane) {
                pane.classList.remove('active');
                pane.style.display = 'none';
            }
        });
        
        // Gewählten Tab aktivieren (mit Null-Check)
        const activeTab = document.getElementById('tab-' + tabId);
        if (activeTab) {
            activeTab.classList.add('active');
            console.log('Tab Element aktiviert:', 'tab-' + tabId);
        } else {
            console.error('Tab-Element nicht gefunden:', 'tab-' + tabId);
            console.log('Verfügbare Tabs:', Array.from(document.querySelectorAll('.tab')).map(t => t.id));
        }
        
        // Gewählten Tab-Inhalt anzeigen (mit Null-Check)
        const tabContent = document.getElementById(tabId);
        if (tabContent) {
        tabContent.classList.add('active');
        tabContent.style.display = 'block';
            console.log('Tab aktiviert:', tabId);
        } else {
            console.error('Tab-Inhalt nicht gefunden:', tabId);
            console.log('Verfügbare Tab-Panes:', Array.from(document.querySelectorAll('.tab-pane')).map(p => p.id));
        }
        
        // URL aktualisieren
        try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabId);
        window.history.pushState({}, '', url);
        } catch (e) {
            console.warn('Konnte URL nicht aktualisieren:', e);
        }
    }
    </script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    
    <style>
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .tab {
            display: inline-block;
            padding: 10px 20px;
            margin-right: 5px;
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid transparent;
            border-radius: 4px 4px 0 0;
            position: relative;
            top: 1px;
        }
        .tab:hover {
            color: #0d6efd;
            border-color: #e9ecef #e9ecef #dee2e6;
            text-decoration: none;
        }
        .tab.active {
            color: #0d6efd;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .tab-pane {
            display: none;
            padding: 20px 0;
        }
        .tab-pane.active {
            display: block;
        }
    </style>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Timestamp Block Start -->
        <div class="text-muted small text-end mb-3">
            <?php 
            $lastModified = filemtime(__FILE__);
            echo "Zuletzt aktualisiert: " . date("d.m.Y H:i:s", $lastModified); 
            ?>
        </div>
        <!-- Timestamp Block End -->

        <div class="nav-tabs">
            <a href="#" class="tab" id="tab-generator" onclick="activateTab('generator')">Test-Generator</a>
            <a href="#" class="tab" id="tab-editor" onclick="activateTab('editor')">Test-Editor</a>
            <a href="#" class="tab" id="tab-testResults" onclick="activateTab('testResults')">Testergebnisse</a>
            <a href="#" class="tab" id="tab-configuration" onclick="activateTab('configuration')">Konfiguration</a>
            <?php if ($showInstanceManagement): ?>
            <a href="#" class="tab" id="tab-instance-management" onclick="activateTab('instance-management')"><i class="bi bi-hdd-stack-fill me-1"></i>Instanzverwaltung</a>
            <?php endif; ?>
        </div>

        <div class="tab-content">
            <div id="generator" class="tab-pane">
                <?php include getIncludesPath('teacher_dashboard/test_generator_view.php'); ?>
            </div>

            <div id="editor" class="tab-pane">
                <?php include getIncludesPath('teacher_dashboard/test_editor_view.php'); ?>
            </div>

            <div id="testResults" class="tab-pane">
                <?php include getIncludesPath('teacher_dashboard/test_results_view.php'); ?>
            </div>

            <div id="configuration" class="tab-pane">
                <?php include getIncludesPath('teacher_dashboard/configuration_view.php'); ?>
            </div>

            <?php if ($showInstanceManagement): ?>
            <div id="instance-management" class="tab-pane">
                <!-- Management-Buttons -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-arrow-clockwise me-2"></i>Instanzen aktualisieren</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Verteilt alle aktuellen Dateien an bestehende Instanzen.</p>
                                <button type="button" class="btn btn-primary" id="updateInstancesBtn">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Alle Instanzen aktualisieren
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="bi bi-trash3 me-2"></i>Alle Instanzen löschen</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">⚠️ Löscht ALLE bestehenden Instanzen unwiderruflich!</p>
                                <button type="button" class="btn btn-danger" id="deleteAllInstancesBtn">
                                    <i class="bi bi-trash3 me-2"></i>Alle Instanzen löschen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <h2>Neue Instanz erstellen</h2>
                <form id="createInstanceForm">
                    <div class="mb-3">
                        <label for="instanceName" class="form-label">Name des Lehrers / der Instanz:</label>
                        <input type="text" class="form-control" id="instanceName" name="instanceName" required>
                        <div class="form-text">Wird für den Ordner- und Datenbanknamen verwendet (z.B. "max_mustermann" oder "realschule_xy"). Keine Leerzeichen oder Sonderzeichen außer Bindestrich und Unterstrich.</div>
                    </div>
                    <div class="mb-3">
                        <label for="adminAccessCode" class="form-label">Admin-Zugangscode für neue Instanz:</label>
                        <input type="text" class="form-control" id="adminAccessCode" name="adminAccessCode" required value="admin123">
                        <div class="form-text">Der Code, mit dem sich der Lehrer in seiner neuen Instanz als Admin anmeldet.</div>
                    </div>
                    <button type="button" class="btn btn-primary" id="createInstanceBtn">
                        <i class="bi bi-plus-circle-fill me-2"></i>Neue Instanz erstellen
                    </button>
                </form>
                <div id="instanceCreationResult" class="mt-3"></div>

                <h3 class="mt-5">Erstellte Instanzen</h3>
                <div id="instanceList" class="list-group">
                    <!-- Liste wird dynamisch gefüllt -->
                    <p class="text-muted">Hier werden erstellte Instanzen angezeigt.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Instance Detail Modal -->
    <div class="modal fade" id="instanceDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Instanz-Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="instanceDetailContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Lädt...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Preview Modal -->
    <div class="modal fade" id="testPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test-Vorschau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="testPreviewContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Lädt...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>

    <!-- Globale Instanzverwaltungs-Funktionen -->
    <script>
        // MCQ Paths für dynamische Pfad-Auflösung
        window.mcqPaths = {
            isInTeacherDir: <?php echo $isInTeacherDir ? 'true' : 'false'; ?>
        };
        console.log('MCQ Paths configured:', window.mcqPaths);

        // GLOBALE Funktionen für Instanzverwaltung
        function loadInstanceList() {
            console.log('loadInstanceList() aufgerufen');
            const instanceListDiv = $('#instanceList');
            
            // Loading-Zustand
            instanceListDiv.html(`
                <div class="d-flex justify-content-center my-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Lade Instanzen...</span>
                    </div>
                    <span class="ms-3">Lade Instanzen...</span>
                </div>
            `);
            
            $.ajax({
                url: getIncludesUrl('teacher_dashboard/get_instances.php'),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Instanzen geladen:', response);
                    if (response.success) {
                        // Cache die Instanzen-Daten für Details-Modal
                        window.lastLoadedInstances = response.instances;
                        displayInstanceList(response.instances, instanceListDiv);
                    } else {
                        instanceListDiv.html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Fehler beim Laden der Instanzen: ${response.error || 'Unbekannter Fehler'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX-Fehler beim Laden der Instanzen:', error);
                    instanceListDiv.html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Fehler beim Laden der Instanzen: ${error}
                        </div>
                    `);
                }
            });
        }
        
        function displayInstanceList(instances, container) {
            console.log('displayInstanceList() aufgerufen mit', instances.length, 'Instanzen');
            
            if (instances.length === 0) {
                container.html(`
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Noch keine Instanzen erstellt. Erstellen Sie eine neue Instanz über das Formular oben.
                    </div>
                `);
                return;
            }
            
            let html = `
                <div class="row mb-3">
                    <div class="col">
                        <h4>Verfügbare Instanzen (${instances.length})</h4>
                        <p class="text-muted">Verwalten Sie hier alle erstellten Lehrerinstanzen</p>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary btn-sm" onclick="loadInstanceList()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Aktualisieren
                        </button>
                    </div>
                </div>
            `;
            
            // Instanzen-Karten
            html += '<div class="row">';
            
            instances.forEach(instance => {
                const statusBadge = getStatusBadge(instance.status);
                const statusIcon = getStatusIcon(instance.status);
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 ${instance.status === 'error' ? 'border-danger' : ''}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    ${statusIcon} ${instance.display_name}
                                </h6>
                                ${statusBadge}
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Admin-Zugangscode:</small><br>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="adminCode_${instance.name}" value="${instance.admin_code}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('adminCode_${instance.name}')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Direktlinks:</small><br>
                                    <div class="btn-group btn-group-sm d-flex" role="group">
                                        <a href="${instance.url}" target="_blank" class="btn btn-outline-primary">
                                            <i class="bi bi-house-door me-1"></i>Homepage
                                        </a>
                                        <a href="${instance.admin_url}" target="_blank" class="btn btn-outline-success">
                                            <i class="bi bi-gear me-1"></i>Admin
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="row text-center">
                                    <div class="col">
                                        <small class="text-muted">Tests</small><br>
                                        <strong>${instance.test_count}</strong>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted">Ergebnisse</small><br>
                                        <strong>${instance.result_count}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>
                                    <i class="bi bi-calendar3 me-1"></i>
                                    Erstellt: ${formatDateTime(instance.created_at)}<br>
                                    <i class="bi bi-activity me-1"></i>
                                    Letzte Aktivität: ${instance.last_activity !== 'Unbekannt' ? formatDateTime(instance.last_activity) : 'Unbekannt'}
                                </small>
                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button class="btn btn-outline-info" onclick="showInstanceDetails('${instance.name}')">
                                            <i class="bi bi-info-circle me-1"></i>Details
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteInstance('${instance.name}')" title="Instanz l\u00f6schen">
                                            <i class="bi bi-trash me-1"></i>L\u00f6schen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
        }
        
        // Helper-Funktionen
        function getStatusBadge(status) {
            switch(status) {
                case 'active': return '<span class="badge bg-success">Aktiv</span>';
                case 'partial': return '<span class="badge bg-warning">Teilweise</span>';
                case 'inactive': return '<span class="badge bg-secondary">Inaktiv</span>';
                case 'error': return '<span class="badge bg-danger">Fehler</span>';
                default: return '<span class="badge bg-light text-dark">Unbekannt</span>';
            }
        }
        
        function getStatusIcon(status) {
            switch(status) {
                case 'active': return '<i class="bi bi-check-circle-fill text-success"></i>';
                case 'partial': return '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
                case 'inactive': return '<i class="bi bi-pause-circle-fill text-secondary"></i>';
                case 'error': return '<i class="bi bi-x-circle-fill text-danger"></i>';
                default: return '<i class="bi bi-question-circle-fill text-muted"></i>';
            }
        }
        
        function formatDateTime(dateStr) {
            if (!dateStr || dateStr === 'Unbekannt') return 'Unbekannt';
            try {
                return new Date(dateStr).toLocaleString('de-DE');
            } catch (e) {
                return dateStr;
            }
        }
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.select();
                element.setSelectionRange(0, 99999);
                document.execCommand('copy');
                
                // Kurzes Feedback
                const originalBg = element.style.backgroundColor;
                element.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    element.style.backgroundColor = originalBg;
                }, 500);
            }
        }
        
        function showInstanceDetails(instanceName) {
            // Finde die Instanz-Daten aus der bereits geladenen Liste
            let instanceData = null;
            
            // Suche in der zuletzt geladenen Instanzen-Liste
            if (window.lastLoadedInstances) {
                instanceData = window.lastLoadedInstances.find(inst => inst.name === instanceName);
            }
            
            if (instanceData) {
                // Zeige Modal mit vorhandenen Daten
                displayInstanceDetails(instanceData);
                const modal = new bootstrap.Modal(document.getElementById('instanceDetailModal'));
                modal.show();
            } else {
                // Fallback: Lade alle Instanzen neu und zeige dann Details
                $.ajax({
                    url: getIncludesUrl('teacher_dashboard/get_instances.php'),
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.lastLoadedInstances = response.instances;
                            const instance = response.instances.find(inst => inst.name === instanceName);
                            
                            if (instance) {
                                displayInstanceDetails(instance);
                                const modal = new bootstrap.Modal(document.getElementById('instanceDetailModal'));
                                modal.show();
                            } else {
                                alert('Instanz "' + instanceName + '" nicht gefunden.');
                            }
                        } else {
                            alert('Fehler beim Laden der Instanz-Details: ' + (response.error || 'Unbekannt'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Fehler beim Laden der Instanz-Details: ' + error);
                    }
                });
            }
        }
        
        function displayInstanceDetails(instance) {
            const content = document.getElementById('instanceDetailContent');
            
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Grundinformationen</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Name:</strong></td><td>${instance.display_name}</td></tr>
                            <tr><td><strong>Verzeichnis:</strong></td><td><code>${instance.name}</code></td></tr>
                            <tr><td><strong>Status:</strong></td><td>${getStatusBadge(instance.status)} ${getStatusIcon(instance.status)}</td></tr>
                            <tr><td><strong>Admin-Code:</strong></td><td><code>${instance.admin_code}</code></td></tr>
                            <tr><td><strong>Datenbank:</strong></td><td><code>${instance.database}</code></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Aktivität & Statistiken</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Erstellt:</strong></td><td>${formatDateTime(instance.created_at)}</td></tr>
                            <tr><td><strong>Letzte Aktivität:</strong></td><td>${instance.last_activity !== 'Unbekannt' ? formatDateTime(instance.last_activity) : 'Unbekannt'}</td></tr>
                            <tr><td><strong>Tests:</strong></td><td>${instance.test_count}</td></tr>
                            <tr><td><strong>Ergebnisse:</strong></td><td>${instance.result_count}</td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>System-Status</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card ${instance.files_ok ? 'border-success' : 'border-danger'}">
                                    <div class="card-body text-center">
                                        <i class="bi ${instance.files_ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'} fs-3"></i>
                                        <h6 class="card-title mt-2">Dateien</h6>
                                        <p class="card-text">${instance.files_ok ? 'Alle wichtigen Dateien vorhanden' : 'Fehlende Dateien erkannt'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card ${instance.database_ok ? 'border-success' : 'border-warning'}">
                                    <div class="card-body text-center">
                                        <i class="bi ${instance.database_ok ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning'} fs-3"></i>
                                        <h6 class="card-title mt-2">Datenbank</h6>
                                        <p class="card-text">${instance.database_ok ? 'Datenbank verfügbar' : 'Datenbank nicht erreichbar'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Schnellzugriff</h6>
                        <div class="btn-group w-100" role="group">
                            <a href="${instance.url}" target="_blank" class="btn btn-outline-primary">
                                <i class="bi bi-house-door me-1"></i>Homepage öffnen
                            </a>
                            <a href="${instance.admin_url}" target="_blank" class="btn btn-outline-success">
                                <i class="bi bi-gear me-1"></i>Admin-Dashboard öffnen
                            </a>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard('modal_admin_code')">
                                <i class="bi bi-clipboard me-1"></i>Admin-Code kopieren
                            </button>
                        </div>
                        <input type="hidden" id="modal_admin_code" value="${instance.admin_code}">
                    </div>
                </div>
            `;
        }
        
        function deleteInstance(instanceName) {
            if (!confirm(`Sind Sie sicher, dass Sie die Instanz "${instanceName}" unwiderruflich löschen möchten?\n\nDies wird folgendes entfernen:\n- Alle Dateien der Instanz\n- Die zugehörige Datenbank\n- Alle Test-Ergebnisse\n\nDieser Vorgang kann NICHT rückgängig gemacht werden!`)) {
                return;
            }
            
            // Zweite Bestätigung
            const confirmText = prompt('Bitte geben Sie "LÖSCHEN" ein, um zu bestätigen:');
            if (confirmText !== 'LÖSCHEN') {
                alert('Löschvorgang abgebrochen.');
                return;
            }
            
            // Zeige Lade-Indikator
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
            button.disabled = true;
            
            $.ajax({
                url: getTeacherUrl('delete_instance.php'),
                method: 'POST',
                data: { 
                    instance_name: instanceName,
                    confirm: 'LÖSCHEN'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Entferne alle Modal-Backdrops
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        
                        alert('Instanz "' + instanceName + '" wurde erfolgreich gelöscht.');
                        loadInstanceList(); // Liste neu laden
                    } else {
                        alert('Fehler beim Löschen: ' + (response.error || 'Unbekannter Fehler'));
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    }
                },
                error: function(xhr, status, error) {
                    alert('Kommunikationsfehler beim Löschen: ' + error);
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                }
            });
        }
        
        // Helper für Pfad-Auflösung
        function getIncludesUrl(path) {
            if (window.mcqPaths && window.mcqPaths.isInTeacherDir) {
                return '../includes/' + path;
            } else {
                return 'includes/' + path;
            }
        }
        
        function getTeacherUrl(filename) {
            if (window.mcqPaths && window.mcqPaths.isInTeacherDir) {
                return filename;
            } else {
                return 'teacher/' + filename;
            }
        }
    </script>

    <!-- Document Ready Block -->
    <script>
        $(document).ready(function() {
            console.log('Document ready, initializing...');
            
            // Initialisierung
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            // Prüfe ob Tab-Parameter existiert und aktiviere entsprechenden Tab
            if (tabParam && document.getElementById(tabParam)) {
                console.log('Aktiviere Tab aus URL:', tabParam);
                activateTab(tabParam);
            } else {
                console.log('Aktiviere Standard-Tab: generator');
                activateTab('generator');
            }
            
            // Event-Listener für Tab-Änderung
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    // Nur reagieren wenn es ein direkter Tab-Click ist
                    if (e.target !== this) {
                        return;
                    }
                    
                    // Verhindere weitere Event-Propagation
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const targetId = this.id.replace('tab-', '');
                    console.log('Tab wurde geklickt:', targetId);
                    
                    // Wenn der Instanzverwaltung-Tab aktiviert wird
                    if (targetId === 'instance-management') {
                        console.log('Instanzverwaltung-Tab aktiviert - lade Instanzliste');
                        setTimeout(() => loadInstanceList(), 100);
                    }
                    
                    // Aktualisiere URL und aktiviere Tab
                    activateTab(targetId);
                });
            });
            
            // AJAX für Instanzerstellung - verwende Button-Click statt Form-Submit
            $('#createInstanceBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const instanceName = $('#instanceName').val();
                const adminAccessCode = $('#adminAccessCode').val();
                const resultDiv = $('#instanceCreationResult');
                
                // Validierung
                if (!instanceName || !adminAccessCode) {
                    resultDiv.html('<div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill me-2"></i>Bitte füllen Sie alle Felder aus.</div>');
                    return;
                }

                resultDiv.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Erstelle Instanz...</span></div> <span class="ms-2">Erstelle Instanz, bitte warten...</span>');

                $.ajax({
                    url: getTeacherUrl('create_instance.php'),
                    type: 'POST',
                    data: {
                        instance_name: instanceName,
                        admin_access_code: adminAccessCode
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Instanz erfolgreich erstellt!<ul>' +
                                '<li><strong>URL:</strong> <a href="' + response.url + '" target="_blank">' + response.url + '</a></li>' +
                                '<li><strong>Admin-Zugang:</strong> ' + response.admin_code + '</li>' +
                            '</ul></div>');
                            $('#createInstanceForm')[0].reset();
                            loadInstanceList(); // Liste der Instanzen neu laden
                        } else {
                            resultDiv.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Fehler: ' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        resultDiv.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kommunikationsfehler: ' + error + '</div>');
                    }
                });
            });
            
            // Lade Instanzliste beim ersten Laden falls Tab aktiv
            if (window.location.hash === '#instance-management' || 
                new URLSearchParams(window.location.search).get('tab') === 'instance-management') {
                console.log('Instanzverwaltung Tab erkannt - lade Instanzliste');
                setTimeout(() => loadInstanceList(), 500);
            }
        });

        // Teste Funktionen beim Laden
        console.log('Funktionen verfügbar:');
        console.log('- loadInstanceList:', typeof loadInstanceList);
        console.log('- displayInstanceList:', typeof displayInstanceList);
        console.log('- getStatusBadge:', typeof getStatusBadge);
        console.log('- getStatusIcon:', typeof getStatusIcon);
        
        // Update Instances Button
        $('#updateInstancesBtn').on('click', function() {
            const button = $(this);
            const originalText = button.html();
            
            button.prop('disabled', true).html('<i class="bi bi-arrow-clockwise spinner-border spinner-border-sm me-2"></i>Aktualisiere...');
            
            $.ajax({
                url: (<?php echo $isInTeacherDir ? "'../update_instances.php'" : "'update_instances.php'"; ?>) + '?admin_key=update_instances_2024&ajax=true',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showModal('Update erfolgreich', 
                            '<div class="alert alert-success">' +
                            '<h5>✅ Instanzen wurden aktualisiert!</h5>' +
                            '<p><strong>Instanzen verarbeitet:</strong> ' + response.statistics.instances_processed + '</p>' +
                            '<p><strong>Dateien aktualisiert:</strong> ' + response.statistics.files_updated + '</p>' +
                            (response.statistics.errors > 0 ? '<p class="text-warning"><strong>Fehler:</strong> ' + response.statistics.errors + '</p>' : '') +
                            '</div>'
                        );
                    } else {
                        showModal('Update-Fehler', '<div class="alert alert-danger">Fehler: ' + (response.error || 'Unbekannter Fehler') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    showModal('Update-Fehler', '<div class="alert alert-danger">Fehler beim Update: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Delete All Instances Button
        $('#deleteAllInstancesBtn').on('click', function() {
            const confirmation = prompt('⚠️ WARNUNG: Alle Instanzen werden unwiderruflich gelöscht!\n\nGeben Sie "DELETE_ALL_INSTANCES" ein, um fortzufahren:');
            
            if (confirmation !== 'DELETE_ALL_INSTANCES') {
                alert('Löschung abgebrochen.');
                return;
            }
            
            const secondConfirmation = confirm('Sind Sie ABSOLUT SICHER? Diese Aktion kann nicht rückgängig gemacht werden!');
            if (!secondConfirmation) {
                alert('Löschung abgebrochen.');
                return;
            }
            
            const button = $(this);
            const originalText = button.html();
            
            button.prop('disabled', true).html('<i class="bi bi-trash3 spinner-border spinner-border-sm me-2"></i>Lösche...');
            
            $.ajax({
                url: getTeacherUrl('delete_all_instances.php'),
                method: 'POST',
                data: { confirmation: 'DELETE_ALL_INSTANCES' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showModal('Instanzen gelöscht', 
                            '<div class="alert alert-success">' +
                            '<h5>✅ Alle Instanzen wurden gelöscht!</h5>' +
                            '<p><strong>Gelöschte Instanzen:</strong> ' + response.deleted_count + '</p>' +
                            '<p><strong>Gelöschte Namen:</strong> ' + response.deleted_instances.join(', ') + '</p>' +
                            (response.errors.length > 0 ? '<p class="text-warning"><strong>Fehler:</strong> ' + response.errors.join(', ') + '</p>' : '') +
                            '</div>'
                        );
                        
                        // Reload instance list
                        loadInstanceList();
                    } else {
                        showModal('Lösch-Fehler', '<div class="alert alert-danger">Fehler: ' + (response.message || 'Unbekannter Fehler') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    showModal('Lösch-Fehler', '<div class="alert alert-danger">Fehler beim Löschen: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Helper function for modals
        function showModal(title, content) {
            const modalHtml = `
                <div class="modal fade" id="actionModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                ${content}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal
            $('#actionModal').remove();
            
            // Add new modal
            $('body').append(modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            modal.show();
            
            // Clean up after hide
            $('#actionModal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }
    </script>
</body>
</html>
