<?php
session_start();

if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Ermittle den korrekten Includes-Pfad basierend auf der aktuellen Position
function getIncludesPath($relativePath) {
    // Prüfe, ob wir im teacher-Verzeichnis sind (Hauptinstanz)
    if (basename(dirname(__FILE__)) === 'teacher') {
        return dirname(__DIR__) . '/includes/' . $relativePath;
    } else {
        // Wir sind in einer Instanz im Hauptverzeichnis
        return 'includes/' . $relativePath;
    }
}

// Erstelle erforderliche Verzeichnisse
$directories = [
    dirname(__DIR__) . '/includes',
    dirname(__DIR__) . '/teacher',
    dirname(__DIR__) . '/tests',
    dirname(__DIR__) . '/results',
    dirname(__DIR__) . '/qrcodes'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Lade vorhandene Tests
$tests = [];
$selectedTest = null;
$testFiles = glob(dirname(__DIR__) . '/tests/*.xml');

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
        
        $title = (string)$xml->title;
        $accessCode = (string)$xml->access_code;
        
        if (empty($title)) {
            $title = basename($testFile, '.xml');
        }
        
        $testData = [
            "file" => $testFile,
            "name" => basename($testFile, '.xml'),
            "title" => $title,
            "accessCode" => $accessCode
        ];
        
        $tests[] = $testData;
        
        // Wenn dieser Test dem angeforderten Test entspricht
        if (isset($requestedTest) && $accessCode === $requestedTest) {
            $selectedTest = $testData;
            error_log("Found matching test: " . json_encode($testData));
        }
    } catch (Exception $e) {
        // Fehlerhafte XML-Datei überspringen
        error_log("Error loading test file: " . $testFile . " - " . $e->getMessage());
        continue;
    }
}

// Wenn ein Test ausgewählt wurde, aber nicht gefunden wurde
if (isset($requestedTest) && $selectedTest === null) {
    error_log("Requested test not found: " . $requestedTest);
}

// Definiere den Basispfad
define('BASE_PATH', __DIR__);

// DEBUG BLOCK START
echo "<!-- File Timestamps:\n";
$files = [
    dirname(__DIR__) . '/includes/ocr_helper.php',
    dirname(__DIR__) . '/includes/teacher_dashboard/test_generator.php',
    __DIR__ . '/generate_test.php'
];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo $file . ": " . date("Y-m-d H:i:s", filemtime($file)) . "\n";
    } else {
        echo $file . ": FILE NOT FOUND\n";
    }
}
echo "-->\n";
// DEBUG BLOCK END
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Lehrer-Dashboard</title>
    <script>
    // SOFORTIGE DEBUG-AUSGABE
    console.log('DIREKTE DEBUG AUSGABE - ' + new Date().toLocaleString());
    
    // Tab-Funktion vor HTML-Code definieren
    function activateTab(tabId) {
        console.log('Aktiviere Tab:', tabId);
        
        // Alle Tabs deaktivieren
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Alle Tab-Inhalte ausblenden
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
            pane.style.display = 'none';
        });
        
        // Gewählten Tab aktivieren (mit Null-Check)
        const tabElement = document.getElementById('tab-' + tabId);
        if (tabElement) {
            tabElement.classList.add('active');
        } else {
            console.error('Tab-Element nicht gefunden: tab-' + tabId);
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
            text-decoration: none;
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
            <a href="#" class="tab" id="tab-instance-management" onclick="activateTab('instance-management')"><i class="bi bi-hdd-stack-fill me-1"></i>Instanzverwaltung</a>
        </div>

        <div class="tab-content">
            <div id="generator" class="tab-pane">
                <?php include(getIncludesPath('teacher_dashboard/test_generator_view.php')); ?>
            </div>
            
            <div id="editor" class="tab-pane">
                <?php include(getIncludesPath('teacher_dashboard/test_editor_view.php')); ?>
            </div>
            
            <div id="testResults" class="tab-pane">
                <?php include(getIncludesPath('teacher_dashboard/test_results_view.php')); ?>
            </div>
            
            <div id="configuration" class="tab-pane">
                <?php include(getIncludesPath('teacher_dashboard/configuration_view.php')); ?>
            </div>

            <div id="instance-management" class="tab-pane">
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
                    <button type="submit" class="btn btn-primary">
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
        </div>
    </div>

    <!-- Test Preview Modal -->
    <div class="modal fade" id="testPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Vorschau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre class="test-content" style="white-space: pre-wrap;"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Teacher Dashboard wurde geladen');
            
            // Prüfe, ob ein Tab-Parameter in der URL vorhanden ist
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            console.log('URL-Parameter tab:', tabParam);
            
            // Prüfe ob Tab-Parameter existiert und aktiviere entsprechenden Tab
            if (tabParam && document.getElementById(tabParam)) {
                console.log('Aktiviere Tab aus URL:', tabParam);
                activateTab(tabParam);
            } else {
                console.log('Aktiviere Standard-Tab: generator');
                activateTab('generator');
            }
            
            // Wenn der Editor-Tab aktiviert wird und keine Fragen vorhanden sind
            if (tabParam === 'editor' && 
                document.querySelectorAll('#questionsContainer .question-card').length === 0 && 
                document.getElementById('testSelector').value === '') {
                console.log('Füge Standardfrage hinzu');
                addQuestion();
            }

            // Event-Listener für Tab-Änderung
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetId = this.id.replace('tab-', '');
                    console.log('Tab wurde geklickt:', targetId);
                    
                    // Wenn der Testergebnisse-Tab aktiviert wird
                    if (targetId === 'testResults') {
                        console.log('Testergebnisse-Tab wurde aktiviert, starte Synchronisation');
                        
                        // Zeige Bootstrap-Meldung, dass Daten aktualisiert werden
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-info alert-dismissible fade show position-fixed top-50 start-50 translate-middle';
                        alertDiv.style.zIndex = '9999';
                        alertDiv.innerHTML = `
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Wird geladen...</span>
                                </div>
                                <strong>Datenbank wird synchronisiert...</strong>
                            </div>
                            <div class="mt-2">Die Seite wird nach Abschluss automatisch neu geladen.</div>
                        `;
                        document.body.appendChild(alertDiv);
                        
                        // Korrekter absoluter Pfad zur API
                        const apiUrl = window.location.origin + '/mcq-test-system/includes/teacher_dashboard/sync_database.php';
                        console.log('Synchronisations-URL:', apiUrl);
                        
                        // Führe AJAX-Anfrage zur Synchronisation durch
                        fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Synchronisation abgeschlossen:', data);
                            
                            // Aktualisiere die Meldung
                            alertDiv.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <strong>Synchronisierung abgeschlossen!</strong>
                                </div>
                                <div class="mt-2">Seite wird neu geladen...</div>
                            `;
                            
                            // Warte kurz, dann lade die Seite neu
                            setTimeout(() => {
                                // Lade die Seite neu mit dem Tab-Parameter
                                window.location.reload();
                            }, 1000);
                        })
                        .catch(error => {
                            console.error('Fehler bei der Synchronisation:', error);
                            
                            // Zeige Fehlermeldung
                            alertDiv.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                                    <strong>Fehler bei der Synchronisierung:</strong>
                                </div>
                                <div class="mt-2">${error.message}</div>
                                <div class="mt-2">
                                    <button class="btn btn-primary btn-sm" onclick="window.location.reload()">
                                        Seite neu laden
                                    </button>
                                </div>
                            `;
                        });
                    }
                    
                    // Aktualisiere URL und aktiviere Tab
                    activateTab(targetId);
                    
                    // Löse ein Event aus für andere Komponenten
                    $(document).trigger('tabChanged', ['#' + targetId]);
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // ... existing code ...

            // Helper-Funktion: Erstelle Pfad für includes-Dateien
            function getIncludesUrl(path) {
                if (window.mcqPaths && window.mcqPaths.isInTeacherDir) {
                    return '../includes/' + path;
                } else {
                    return 'includes/' + path;
                }
            }

            // AJAX für Instanzerstellung
            $('#createInstanceForm').on('submit', function(e) {
                e.preventDefault();
                const instanceName = $('#instanceName').val();
                const adminAccessCode = $('#adminAccessCode').val();
                const resultDiv = $('#instanceCreationResult');

                resultDiv.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Erstelle Instanz...</span></div> <span class="ms-2">Erstelle Instanz, bitte warten...</span>');

                $.ajax({
                    url: 'create_instance.php', // Dieses Skript erstellen wir als Nächstes
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

            // Funktion zum Laden der Instanzliste
            function loadInstanceList() {
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
                        if (response.success) {
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
                        console.error('Fehler beim Laden der Instanzen:', error);
                        instanceListDiv.html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Verbindungsfehler: ${error}
                            </div>
                        `);
                    }
                });
            }
            
            // Funktion zur Anzeige der Instanzliste
            function displayInstanceList(instances, container) {
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
                                        <strong>Admin-Code:</strong>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" value="${instance.admin_code}" readonly id="adminCode_${instance.name}">
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('adminCode_${instance.name}')" title="Kopieren">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Datenbank:</strong> <code>${instance.database}</code><br>
                                        <strong>Erstellt:</strong> ${formatDateTime(instance.created_at)}<br>
                                        <strong>Letzte Aktivität:</strong> ${instance.last_activity !== 'Unbekannt' ? formatDateTime(instance.last_activity) : 'Keine'}
                                    </div>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col">
                                            <div class="fw-bold text-primary">${instance.test_count}</div>
                                            <small class="text-muted">Tests</small>
                                        </div>
                                        <div class="col">
                                            <div class="fw-bold text-success">${instance.result_count}</div>
                                            <small class="text-muted">Ergebnisse</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-grid gap-2">
                                        <a href="${instance.admin_url}" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Dashboard öffnen
                                        </a>
                                        <div class="btn-group" role="group">
                                            <a href="${instance.url}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-house me-1"></i>Startseite
                                            </a>
                                            <button class="btn btn-outline-info btn-sm" onclick="showInstanceDetails('${instance.name}')">
                                                <i class="bi bi-info-circle me-1"></i>Details
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
                    case 'active':
                        return '<span class="badge bg-success">Aktiv</span>';
                    case 'partial':
                        return '<span class="badge bg-warning">Teilweise</span>';
                    case 'error':
                        return '<span class="badge bg-danger">Fehler</span>';
                    default:
                        return '<span class="badge bg-secondary">Unbekannt</span>';
                }
            }
            
            function getStatusIcon(status) {
                switch(status) {
                    case 'active':
                        return '<i class="bi bi-check-circle-fill text-success"></i>';
                    case 'partial':
                        return '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
                    case 'error':
                        return '<i class="bi bi-x-circle-fill text-danger"></i>';
                    default:
                        return '<i class="bi bi-question-circle text-secondary"></i>';
                }
            }
            
            function formatDateTime(dateStr) {
                if (!dateStr || dateStr === 'Unbekannt') return 'Unbekannt';
                
                try {
                    const date = new Date(dateStr);
                    return date.toLocaleString('de-DE', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (e) {
                    return dateStr;
                }
            }
            
            function copyToClipboard(elementId) {
                const element = document.getElementById(elementId);
                element.select();
                element.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(element.value).then(() => {
                    // Kurzes visuelles Feedback
                    const btn = element.nextElementSibling;
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check"></i>';
                    btn.classList.add('btn-success');
                    btn.classList.remove('btn-outline-secondary');
                    
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-secondary');
                    }, 1000);
                }).catch(err => {
                    console.error('Fehler beim Kopieren:', err);
                });
            }
            
            function showInstanceDetails(instanceName) {
                // TODO: Implementiere Detail-Modal
                alert('Details für Instanz: ' + instanceName + '\n\nDetails-Ansicht wird in einer späteren Version implementiert.');
            }
            
            // Lade Instanzliste beim Tab-Wechsel
            $(document).on('tabChanged', function(event, tabId) {
                if (tabId === '#instance-management') {
                    loadInstanceList();
                }
            });
            
            // Lade Instanzliste beim ersten Laden falls Tab aktiv
            if (window.location.hash === '#instance-management' || 
                new URLSearchParams(window.location.search).get('tab') === 'instance-management') {
                setTimeout(() => loadInstanceList(), 500);
        });
    </script>
</body>
</html>