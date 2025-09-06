<div class="container mt-4">
    <h2>Konfiguration</h2>
    
    <!-- Allgemeine Einstellungen -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Allgemeine Einstellungen
        </div>
        <div class="card-body">
            <form id="configForm">
                <div class="mb-3">
                    <label for="schoolName" class="form-label">Schulname</label>
                    <input type="text" class="form-control" id="schoolName" name="schoolName">
                </div>
                <div class="mb-3">
                    <label for="defaultTimeLimit" class="form-label">Standard-Zeitlimit (Minuten)</label>
                    <input type="number" class="form-control" id="defaultTimeLimit" name="defaultTimeLimit" min="1" value="45">
                </div>
                <div class="mb-3">
                    <label for="resultStorage" class="form-label">Speicherort für Ergebnisse</label>
                    <input type="text" class="form-control" id="resultStorage" name="resultStorage" value="results">
                </div>
                
                <!-- Neuer Bereich für Test-Funktionseinstellungen -->
                <div class="mb-4 mt-4 pt-2 border-top">
                    <h5 class="mb-3">Test-Funktionen</h5>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="disableAttentionButton" name="disableAttentionButton">
                            <label class="form-check-label" for="disableAttentionButton">
                                Aufmerksamkeitsbutton deaktivieren
                            </label>
                            <div class="form-text text-muted">
                                Wenn aktiviert, wird der rote Aufmerksamkeitsbutton während der Tests nicht angezeigt.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="disableDailyTestLimit" name="disableDailyTestLimit">
                            <label class="form-check-label" for="disableDailyTestLimit">
                                Tägliche Test-Begrenzung deaktivieren
                            </label>
                            <div class="form-text text-muted">
                                Wenn aktiviert, können Schüler denselben Test mehrmals am selben Tag durchführen.
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
            </form>
        </div>
    </div>

    <!-- Instanzen-Update -->
    <!-- Datenbank-Synchronisation -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Datenbank-Synchronisation
        </div>
        <div class="card-body">
            <p class="mb-3">
                Synchronisiert die Datenbank mit den XML-Dateien im results-Ordner. 
                Dies stellt sicher, dass:
                <ul>
                    <li>Alle XML-Dateien in der Datenbank erfasst sind</li>
                    <li>Keine verwaisten Datenbankeinträge ohne zugehörige XML-Datei existieren</li>
                </ul>
            </p>
            <div class="d-flex gap-2">
                <button id="syncDatabaseBtn" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat"></i> Datenbank synchronisieren
                </button>
                <button id="manageTestResultsBtn" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#testResultsManagerModal">
                    <i class="bi bi-gear"></i> Testergebnisse verwalten
                </button>
                <button id="fixDuplicatesBtn" class="btn btn-warning">
                    <i class="bi bi-tools"></i> Duplikate bereinigen
                </button>
            </div>
            <div id="syncProgress" class="progress mt-3 d-none">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
            <div id="syncResult" class="mt-3"></div>
        </div>
    </div>

    <!-- Notenschema -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Notenschema
        </div>
        <div class="card-body">
            <p class="mb-3">
                Wählen Sie das zu verwendende Notenschema für die Auswertung von Tests aus. 
                Das Schema wird automatisch beim Auswählen aktiviert.
            </p>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="gradeSchema" class="form-label">Notenschema auswählen</label>
                        <select class="form-select" id="gradeSchema">
                            <!-- Wird dynamisch befüllt -->
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mt-3">
                        <div id="schemaStatus" class="mb-2"></div>
                        <div id="currentSchema" class="table-responsive">
                            <!-- Wird dynamisch befüllt -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Test-Ergebnis-Verwaltung -->
<div class="modal fade" id="testResultsManagerModal" tabindex="-1" aria-labelledby="testResultsManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="testResultsManagerModalLabel">Verwaltung der Testergebnisse</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <button id="refreshTestDataBtn" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-clockwise"></i> Daten aktualisieren
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="selectAllBtn" class="btn btn-sm btn-outline-secondary">Alle auswählen</button>
                        <button id="deselectAllBtn" class="btn btn-sm btn-outline-secondary">Auswahl aufheben</button>
                    </div>
                </div>
                
                <div id="testResultsTableContainer" class="table-responsive">
                    <div class="d-flex justify-content-center my-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Lade Daten...</span>
                        </div>
                        <span class="ms-3">Daten werden geladen...</span>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle-fill me-2"></i> Legende:
                    <ul class="mb-0 mt-2">
                        <li><span class="badge bg-success">Beide</span> - Datei existiert im results-Ordner und ist in der Datenbank eingetragen</li>
                        <li><span class="badge bg-warning">Nur XML</span> - Datei existiert nur im results-Ordner, ist nicht in der Datenbank eingetragen</li>
                        <li><span class="badge bg-danger">Nur DB</span> - Datei ist nur in der Datenbank eingetragen, existiert nicht im results-Ordner</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100 justify-content-between">
                    <div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="deleteFilesCheckbox">
                            <label class="form-check-label" for="deleteFilesCheckbox">
                                Auch XML-Dateien löschen
                            </label>
                        </div>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" id="deleteEmptyDirsCheckbox">
                            <label class="form-check-label" for="deleteEmptyDirsCheckbox">
                                Leere Ordner nach dem Löschen entfernen
                            </label>
                        </div>
                        <button id="deleteSelectedBtn" class="btn btn-danger mt-2" disabled>
                            <i class="bi bi-trash"></i> Ausgewählte Einträge löschen
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    console.log('Konfigurationsseite geladen');
    
    // Initial laden
    loadGradeSchemas();
    loadConfig();

    // Helper-Funktionen: Erstelle Pfade für includes und teacher-Dateien
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

    // Konfiguration laden
    function loadConfig() {
        $.ajax({
            url: getIncludesUrl('teacher_dashboard/get_config.php'),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Formularfelder mit den gespeicherten Werten füllen
                    if (response.config) {
                        const config = response.config;
                        $('#schoolName').val(config.schoolName || '');
                        $('#defaultTimeLimit').val(config.defaultTimeLimit || 45);
                        $('#resultStorage').val(config.resultStorage || 'results');
                        
                        // Neue Optionen setzen
                        $('#disableAttentionButton').prop('checked', config.disableAttentionButton === true);
                        $('#disableDailyTestLimit').prop('checked', config.disableDailyTestLimit === true);
                    }
                } else {
                    console.error('Fehler beim Laden der Konfiguration:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler beim Laden der Konfiguration:', status, error);
            }
        });
    }

    // Event-Handler für das Instanzen-Update (entfernt - verschoben zur Instanzverwaltung)
    /*
    // $('#updateInstancesBtn').on('click', function() {
        const btn = $(this);
        const result = $('#updateResult');
        
        // Button deaktivieren
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Update läuft...');
        result.html('<div class="alert alert-info">Instanzen-Update wird durchgeführt...</div>');
        
        // AJAX-Request für Update (Pfad relativ zum Hauptverzeichnis)
        const updateUrl = window.mcqPaths && window.mcqPaths.isInTeacherDir 
            ? '../update_instances.php?admin_key=update_instances_2024&ajax=true'
            : 'update_instances.php?admin_key=update_instances_2024&ajax=true';
        
        $.ajax({
            url: updateUrl,
            method: 'GET',
            dataType: 'json',
            timeout: 120000, // 2 Minuten Timeout
            success: function(response) {
                if (response.success) {
                    // Erfolgreiche Aktualisierung
                    const stats = response.statistics;
                    
                    // Prüfe ob keine Instanzen gefunden wurden
                    if (stats.instances_processed === 0) {
                        const modal = createUpdateModal(
                            'Keine Instanzen gefunden',
                            `
                            <div class="alert alert-info">
                                <h5><i class="bi bi-info-circle-fill me-2"></i>Keine Lehrerinstanzen zum Aktualisieren</h5>
                                <hr>
                                <p><strong>Status:</strong> ${response.message || 'Keine Instanzen vorhanden'}</p>
                                ${response.info ? `<p><strong>Hinweis:</strong> ${response.info}</p>` : ''}
                            </div>
                            <div class="alert alert-warning">
                                <strong>💡 Nächste Schritte:</strong>
                                <ol>
                                    <li>Erstellen Sie zuerst Lehrerinstanzen über die Instanzverwaltung</li>
                                    <li>Nach dem Erstellen von Instanzen können Sie das Update ausführen</li>
                                    <li>Das Update-System wird dann alle korrigierten Dateien verteilen</li>
                                </ol>
                            </div>
                            `,
                            'info'
                        );
                        
                        result.html(`
                            <div class="alert alert-info">
                                <strong>ℹ️ Keine Instanzen:</strong> Es wurden keine Lehrerinstanzen zum Aktualisieren gefunden.
                            </div>
                        `);
                    } else {
                        // Normale Erfolgs-Anzeige mit Instanzen
                        const modal = createUpdateModal(
                            'Instanzen-Update erfolgreich!',
                            `
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle-fill me-2"></i>Alle Instanzen wurden erfolgreich aktualisiert!</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>📊 Statistiken:</strong>
                                        <ul class="list-unstyled mt-2">
                                            <li>✅ <strong>Instanzen verarbeitet:</strong> ${stats.instances_processed || 0}</li>
                                            <li>📁 <strong>Dateien aktualisiert:</strong> ${stats.files_updated || 0}</li>
                                            <li>❌ <strong>Fehler:</strong> ${stats.errors || 0}</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>📋 Aktualisierte Dateien:</strong>
                                        <ul class="list-unstyled mt-2 small">
                                            <li>• Teacher Dashboard</li>
                                            <li>• Test Generator</li>
                                            <li>• JavaScript Main</li>
                                            <li>• Dashboard Views</li>
                                            <li>• Database Config</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            ${response.instances && response.instances.length > 0 ? `
                            <div class="alert alert-info">
                                <strong>🧪 Testen Sie die aktualisierten Instanzen:</strong>
                                <div class="mt-2">
                                    ${response.instances.map(instance => 
                                        `<a href="/lehrer_instanzen/${instance}/mcq-test-system/teacher/teacher_dashboard.php" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-1">
                                            🔗 ${instance}
                                        </a>`
                                    ).join('')}
                                </div>
                            </div>
                            ` : ''}
                            `,
                            'success'
                        );
                        
                        result.html(`
                            <div class="alert alert-success">
                                <strong>✅ Update erfolgreich!</strong> 
                                ${stats.instances_processed || 0} Instanzen aktualisiert, ${stats.files_updated || 0} Dateien übertragen.
                            </div>
                        `);
                    }
                } else {
                    // Fehler beim Update
                    const modal = createUpdateModal(
                        'Fehler beim Instanzen-Update',
                        `
                        <div class="alert alert-danger">
                            <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Update konnte nicht vollständig durchgeführt werden</h5>
                            <hr>
                            <strong>Fehlermeldung:</strong><br>
                            <code>${response.error || 'Unbekannter Fehler'}</code>
                        </div>
                        <div class="alert alert-warning">
                            <strong>Nächste Schritte:</strong>
                            <ol>
                                <li>Prüfen Sie die Dateiberechtigungen</li>
                                <li>Stellen Sie sicher, dass alle Instanzen-Verzeichnisse beschreibbar sind</li>
                                <li>Führen Sie das Update manuell aus: <code>update_instances.php?admin_key=update_instances_2024</code></li>
                            </ol>
                        </div>
                        `,
                        'error'
                    );
                    
                    result.html(`
                        <div class="alert alert-danger">
                            <strong>❌ Update fehlgeschlagen:</strong> ${response.error || 'Unbekannter Fehler'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                const modal = createUpdateModal(
                    'Verbindungsfehler beim Update',
                    `
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-wifi-off me-2"></i>Verbindung zum Update-Service fehlgeschlagen</h5>
                        <hr>
                        <strong>Technische Details:</strong><br>
                        <code>Status: ${status}, Fehler: ${error}</code>
                    </div>
                    <div class="alert alert-info">
                        <strong>Alternative:</strong> Führen Sie das Update manuell aus:<br>
                        <a href="update_instances.php?admin_key=update_instances_2024" target="_blank" class="btn btn-primary btn-sm mt-2">
                            🔧 Manuelles Update ausführen
                        </a>
                    </div>
                    `,
                    'error'
                );
                
                result.html(`
                    <div class="alert alert-danger">
                        <strong>❌ Verbindungsfehler:</strong> ${error || 'Timeout oder Serverfehler'}
                    </div>
                `);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> Alle Instanzen aktualisieren');
            }
        });
    });
    */

    // Event-Handler für die Datenbank-Synchronisation
    $('#syncDatabaseBtn').on('click', function() {
        const btn = $(this);
        const progress = $('#syncProgress');
        const result = $('#syncResult');
        
        // Button deaktivieren und Progress-Bar anzeigen
        btn.prop('disabled', true);
        progress.removeClass('d-none');
        result.html('<div class="alert alert-info">Synchronisation läuft...</div>');
        
        // Starte Synchronisation
        $.ajax({
            url: getIncludesUrl('teacher_dashboard/sync_database.php'),
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    result.html(`
                        <div class="alert alert-success">
                            <h5>Synchronisation erfolgreich</h5>
                            <p class="mb-2">Zusammenfassung:</p>
                            <ul class="mb-0">
                                <li>Neue Einträge hinzugefügt: ${response.added}</li>
                                <li>Verwaiste Einträge gelöscht: ${response.deleted}</li>
                                <li>Bestehende Einträge aktualisiert: ${response.updated || 0}</li>
                            </ul>
                        </div>
                    `);
                } else {
                    result.html(`
                        <div class="alert alert-danger">
                            <h5>Fehler bei der Synchronisation</h5>
                            <p class="mb-0">${response.error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                result.html(`
                    <div class="alert alert-danger">
                        <h5>Fehler bei der Synchronisation</h5>
                        <p class="mb-0">Es ist ein unerwarteter Fehler aufgetreten.</p>
                    </div>
                `);
            },
            complete: function() {
                btn.prop('disabled', false);
                progress.addClass('d-none');
            }
        });
    });

    // Event-Handler für das Konfigurations-Formular
    $('#configForm').on('submit', function(e) {
        e.preventDefault();
        
        // Konfigurationsdaten sammeln
        const config = {
            schoolName: $('#schoolName').val(),
            defaultTimeLimit: parseInt($('#defaultTimeLimit').val()) || 45,
            resultStorage: $('#resultStorage').val(),
            
            // Neue Optionen
            disableAttentionButton: $('#disableAttentionButton').is(':checked'),
            disableDailyTestLimit: $('#disableDailyTestLimit').is(':checked')
        };
        
        // Konfiguration speichern
        $.ajax({
            url: getIncludesUrl('teacher_dashboard/save_config.php'),
            method: 'POST',
            data: JSON.stringify(config),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Einstellungen wurden erfolgreich gespeichert.');
                } else {
                    alert('Fehler beim Speichern der Einstellungen: ' + (response.error || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', status, error);
                alert('Fehler beim Speichern der Einstellungen: ' + error);
            }
        });
    });
    
    // Event-Handler für die Test-Ergebnis-Verwaltung
    $('#manageTestResultsBtn, #refreshTestDataBtn').on('click', function() {
        loadTestResultsData();
    });
    
    // Notenschema-Funktionalität
    // Lade die verfügbaren Notenschema-Dateien
    function loadGradeSchemas() {
        console.log('Lade verfügbare Notenschemas...');
        
        // Füge Cache-Buster hinzu
        const timestamp = new Date().getTime();
        
        $.ajax({
            url: getIncludesUrl('teacher_dashboard/get_grade_schemas.php'),
            method: 'GET',
            data: { _t: timestamp }, // Cache-Buster
            dataType: 'json',
            cache: false,
            success: function(response) {
                const select = $('#gradeSchema');
                select.empty();
                
                if (response.success && response.schemas.length > 0) {
                    console.log('Geladene Schemas:', response.schemas);
                    console.log('Aktives Schema:', response.activeSchema);
                    
                    // Sortiere Schemas nach Nummer
                    response.schemas.sort((a, b) => {
                        const numA = parseInt(a.id.split('_')[0]);
                        const numB = parseInt(b.id.split('_')[0]);
                        return numA - numB;
                    });
                    
                    // Füge Optionen hinzu
                    response.schemas.forEach(schema => {
                        const option = $('<option></option>')
                            .attr('value', schema.id)
                            .text(schema.name)
                            .data('file', schema.file);
                        
                        // Markiere aktives Schema
                        if (schema.active) {
                            option.prop('selected', true);
                            console.log('Option als ausgewählt markiert:', schema.id);
                        }
                        
                        select.append(option);
                    });
                    
                    // Zeige das aktuelle Schema an
                    if (response.activeSchema && response.activeSchema.entries) {
                        console.log('Zeige aktives Schema an mit ' + response.activeSchema.entries.length + ' Einträgen');
                        
                        // Erzwinge Neuzeichnung mit einer Kopie
                        const schemaCopy = JSON.parse(JSON.stringify(response.activeSchema));
                        displayCurrentSchema(schemaCopy);
                    } else {
                        console.error('Kein aktives Schema in der Antwort gefunden!');
                        
                        // Fallback: Versuche, das Schema des ausgewählten Elements zu laden
                        const selectedId = select.val();
                        console.log('Fallback: Versuche Schema zu laden für ID:', selectedId);
                        
                        // Lade Schema explizit
                        $.ajax({
                            url: getIncludesUrl('teacher_dashboard/set_active_schema.php'),
                            method: 'POST',
                            data: { 
                                schema_id: selectedId,
                                _t: new Date().getTime() // Cache-Buster
                            },
                            dataType: 'json',
                            cache: false,
                            success: function(activateResponse) {
                                console.log('Explizit geladenes Schema:', activateResponse);
                                if (activateResponse.success && activateResponse.schema) {
                                    // Erzwinge Neuzeichnung mit einer Kopie
                                    const schemaCopy = JSON.parse(JSON.stringify(activateResponse.schema));
                                    displayCurrentSchema(schemaCopy);
                                } else {
                                    $('#currentSchema').html('<div class="alert alert-warning">Schema konnte nicht geladen werden</div>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX-Fehler beim Nachladen:', status, error);
                                console.error('Server-Antwort:', xhr.responseText);
                                $('#currentSchema').html('<div class="alert alert-danger">Fehler beim Laden des Schemas</div>');
                            }
                        });
                    }
                } else {
                    select.append($('<option></option>').text('Keine Schemas gefunden'));
                    $('#currentSchema').html('<div class="alert alert-warning">Keine Notenschema-Dateien gefunden</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', status, error);
                console.error('Server-Antwort:', xhr.responseText);
                $('#currentSchema').html('<div class="alert alert-danger">Fehler beim Laden der Notenschema-Dateien</div>');
            }
        });
    }
    
    // Stelle das aktuelle Notenschema dar
    function displayCurrentSchema(schema) {
        console.log('Zeige Schema an:', schema);
        
        // Überprüfe, ob Einträge vorhanden sind
        if (!schema || !schema.entries || schema.entries.length === 0) {
            console.error('Keine Einträge im Schema gefunden!');
            $('#currentSchema').html('<div class="alert alert-warning">Schema enthält keine Einträge</div>');
            return;
        }
        
        // Sortiere die Einträge nach Threshold (absteigend)
        schema.entries.sort((a, b) => b.threshold - a.threshold);
        console.log('Sortierte Einträge:', schema.entries);
        
        // Debug: Schema-ID ausgeben
        console.log('Schema-ID:', schema.id);
        console.log('Schema-Name:', schema.name);
        console.log('Schema-Datei:', schema.file);
        
        let tableHTML = `
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <strong>Aktuelles Notenschema: ${schema.name}</strong>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Note</th>
                                <th>Mindestpunktzahl (%)</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        // Füge die Einträge hinzu
        schema.entries.forEach((entry, index) => {
            console.log(`Eintrag ${index}: Note ${entry.grade}, Schwelle ${entry.threshold}%`);
            tableHTML += `
                <tr>
                    <td>${entry.grade}</td>
                    <td>${entry.threshold}%</td>
                </tr>
            `;
        });
        
        tableHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        $('#currentSchema').html(tableHTML);
    }
    
    // Lädt die Testergebnisdaten und zeigt sie in der Tabelle an
    function loadTestResultsData() {
        const $container = $('#testResultsTableContainer');
        
        // Zeige Ladeindikator
        $container.html(`
            <div class="d-flex justify-content-center my-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Lade Daten...</span>
                </div>
                <span class="ms-3">Daten werden geladen...</span>
            </div>
        `);
        
        // Lade Daten über AJAX
        $.ajax({
            url: getIncludesUrl('teacher_dashboard/get_test_results_data.php'),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    if (data.length === 0) {
                        $container.html(`
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i> Keine Testergebnisse gefunden.
                            </div>
                        `);
                        return;
                    }
                    
                    // Gruppiere Daten nach Test
                    const groupedData = {};
                    data.forEach(item => {
                        const testKey = item.access_code;
                        if (!groupedData[testKey]) {
                            groupedData[testKey] = {
                                title: item.title || `Test ${item.access_code}`,
                                access_code: item.access_code,
                                items: []
                            };
                        }
                        groupedData[testKey].items.push(item);
                    });
                    
                    // Erstelle Tabelle
                    let html = `
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30px;">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="checkAll">
                                        </div>
                                    </th>
                                    <th>Test</th>
                                    <th>Student</th>
                                    <th>Datum</th>
                                    <th>Ergebnis</th>
                                    <th>Note</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    // Sortiere Tests nach Zugangscodoe
                    const sortedTests = Object.keys(groupedData).sort();
                    
                    sortedTests.forEach(testKey => {
                        const test = groupedData[testKey];
                        
                        // Füge Testgruppen-Header hinzu
                        html += `
                            <tr class="table-active">
                                <td colspan="7">
                                    <strong>${escapeHtml(test.title)}</strong>
                                    <span class="badge bg-secondary ms-2">${test.access_code}</span>
                                    <span class="badge bg-primary ms-2">${test.items.length} Ergebnisse</span>
                                </td>
                            </tr>
                        `;
                        
                        // Sortiere Einträge nach Datum (neueste zuerst)
                        test.items.sort((a, b) => {
                            return new Date(b.completed_at) - new Date(a.completed_at);
                        });
                        
                        // Füge jeden Eintrag hinzu
                        test.items.forEach((item, index) => {
                            const rowId = `result-${testKey}-${index}`;
                            const statusBadge = getStatusBadge(item.status);
                            
                            html += `
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input test-result-checkbox" 
                                                id="${rowId}" 
                                                data-id="${item.id || ''}"
                                                data-type="${item.status}"
                                                data-test-id="${item.test_id || ''}"
                                                data-file-path="${escapeHtml(item.xml_file_path || '')}">
                                        </div>
                                    </td>
                                    <td>${escapeHtml(test.title)}</td>
                                    <td>${escapeHtml(item.student_name)}</td>
                                    <td>${formatDateTime(item.completed_at)}</td>
                                    <td>
                                        ${item.points_achieved}/${item.points_maximum}
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar" role="progressbar" 
                                                style="width: ${item.percentage}%;" 
                                                aria-valuenow="${item.percentage}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100"></div>
                                        </div>
                                    </td>
                                    <td>${escapeHtml(item.grade || '-')}</td>
                                    <td>${statusBadge}</td>
                                </tr>
                            `;
                        });
                    });
                    
                    html += `
                            </tbody>
                        </table>
                    `;
                    
                    $container.html(html);
                    
                    // Event-Handler für "Alle auswählen" Checkbox
                    $('#checkAll').on('change', function() {
                        $('.test-result-checkbox').prop('checked', $(this).is(':checked'));
                        updateDeleteButtonState();
                    });
                    
                } else {
                    $container.html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Fehler beim Laden der Daten: ${response.error || 'Unbekannter Fehler'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $container.html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Fehler beim Laden der Daten: ${error || 'Verbindungsfehler'}
                    </div>
                `);
            }
        });
    }
    
    // Helper-Funktion: Formatiert Datum und Uhrzeit
    function formatDateTime(dateTimeStr) {
        if (!dateTimeStr) return '-';
        
        const date = new Date(dateTimeStr.replace(' ', 'T'));
        if (isNaN(date.getTime())) return dateTimeStr;
        
        return date.toLocaleString('de-DE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Helper-Funktion: HTML escapen
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Helper-Funktion: Gibt das passende Badge für den Status zurück
    function getStatusBadge(status) {
        switch (status) {
            case 'both':
                return '<span class="badge bg-success">Beide</span>';
            case 'db':
                return '<span class="badge bg-danger">Nur DB</span>';
            case 'xml':
                return '<span class="badge bg-warning">Nur XML</span>';
            default:
                return '<span class="badge bg-secondary">Unbekannt</span>';
        }
    }
    
    // Aktiviere ein Notenschema
    $('#gradeSchema').on('change', function() {
        const schemaId = $(this).val();
        const schemaName = $(this).find('option:selected').text();
        
        console.log('Schema geändert zu:', schemaId, schemaName);
        $('#schemaStatus').html('<div class="alert alert-info">Schema wird aktiviert...</div>');
        
        // Füge Cache-Buster hinzu
        const timestamp = new Date().getTime();
        
        $.ajax({
            url: getIncludesUrl('teacher_dashboard/set_active_schema.php'),
            method: 'POST',
            data: { 
                schema_id: schemaId,
                _t: timestamp // Cache-Buster
            },
            dataType: 'json',
            cache: false,
            success: function(response) {
                console.log('Server-Antwort erhalten:', response);
                if (response.success) {
                    $('#schemaStatus').html(`
                        <div class="alert alert-success">
                            Schema "${schemaName}" wurde aktiviert
                        </div>
                    `);
                    
                    // Stelle sicher, dass das Schema-Objekt mit Einträgen vorhanden ist
                    if (response.schema && response.schema.entries) {
                        console.log('Zeige Schema an mit ' + response.schema.entries.length + ' Einträgen');
                        console.log('Schema-Einträge:', response.schema.entries);
                        
                        // Erzwinge Neuzeichnung, indem wir eine Kopie des Objekts verwenden
                        const schemaCopy = JSON.parse(JSON.stringify(response.schema));
                        displayCurrentSchema(schemaCopy);
                    } else {
                        console.error('Fehler: Ungültige Schema-Daten empfangen', response);
                        $('#currentSchema').html('<div class="alert alert-warning">Schema-Daten konnten nicht geladen werden</div>');
                    }
                } else {
                    $('#schemaStatus').html(`
                        <div class="alert alert-danger">
                            Fehler beim Aktivieren des Schemas: ${response.error || 'Unbekannter Fehler'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', status, error);
                console.error('Server-Antwort:', xhr.responseText);
                
                $('#schemaStatus').html(`
                    <div class="alert alert-danger">
                        Fehler bei der Kommunikation mit dem Server: ${error}
                    </div>
                `);
            }
        });
    });

    // Auswahl aller Checkboxen
    $('#selectAllBtn').on('click', function() {
        $('.test-result-checkbox').prop('checked', true);
        updateDeleteButtonState();
    });
    
    // Aufheben aller Auswahlen
    $('#deselectAllBtn').on('click', function() {
        $('.test-result-checkbox').prop('checked', false);
        updateDeleteButtonState();
    });
    
    // Löschen der ausgewählten Einträge
    $('#deleteSelectedBtn').on('click', function() {
        const selectedItems = $('.test-result-checkbox:checked');
        
        if (selectedItems.length === 0) {
            return;
        }
        
        if (!confirm(`Sind Sie sicher, dass Sie ${selectedItems.length} ausgewählte Einträge löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.`)) {
            return;
        }
        
        const itemsToDelete = [];
        selectedItems.each(function() {
            itemsToDelete.push({
                id: $(this).data('id'),
                type: $(this).data('type'), // 'xml', 'db', oder 'both'
                test_id: $(this).data('test-id'),
                file_path: $(this).data('file-path')
            });
        });
        
        // Debug: Zeige die zu löschenden Einträge
        console.log("Zu löschende Einträge:", itemsToDelete);
        
        // Button deaktivieren und Ladeindikator anzeigen
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Löschen...');
        
        // Starte Löschvorgang
        $.ajax({
            url: getIncludesUrl('teacher_dashboard/delete_test_results.php'),
            method: 'POST',
            data: JSON.stringify({
                entries: itemsToDelete,
                delete_files: $('#deleteFilesCheckbox').prop('checked'),
                delete_empty_dirs: $('#deleteEmptyDirsCheckbox').prop('checked')
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // Aktualisiere die Tabelle nach dem Löschen
                    loadTestResultsData();
                    
                    // Zeige Erfolgsmeldung
                    alert(`Einträge wurden erfolgreich gelöscht: ${response.stats.db_deleted} aus der Datenbank, ${response.stats.files_deleted} Dateien${response.stats.dirs_deleted ? ', ' + response.stats.dirs_deleted + ' leere Ordner' : ''}.`);
                } else {
                    alert(`Fehler beim Löschen: ${response.error}`);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX-Fehler:", status, error);
                alert('Es ist ein unerwarteter Fehler aufgetreten: ' + error);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-trash"></i> Ausgewählte Einträge löschen');
            }
        });
    });
    
    // Event-Delegation für Checkbox-Änderungen
    $(document).on('change', '.test-result-checkbox', function() {
        updateDeleteButtonState();
    });
    
    // Aktualisiert den Zustand des Löschen-Buttons basierend auf ausgewählten Checkboxen
    function updateDeleteButtonState() {
        const selectedItems = $('.test-result-checkbox:checked').length;
        const $deleteBtn = $('#deleteSelectedBtn');
        
        if (selectedItems > 0) {
            $deleteBtn.prop('disabled', false)
                .html(`<i class="bi bi-trash"></i> ${selectedItems} Einträge löschen`);
        } else {
            $deleteBtn.prop('disabled', true)
                .html('<i class="bi bi-trash"></i> Ausgewählte Einträge löschen');
        }
    }
    
    // Button zur Duplikatbereinigung
    $('#fixDuplicatesBtn').on('click', function() {
        // Öffne das Skript in einem neuen Tab oder Fenster
        window.open('../fix_duplicate_entries.php', '_blank');
    });
    
    // Funktion zum Erstellen und Anzeigen eines Update-Modals
    function createUpdateModal(title, content, type) {
        if (typeof type === 'undefined') {
            type = 'info';
        }
        // Entferne vorherige Modals
        $('#updateModal').remove();
        
        // Icon basierend auf Typ
        let icon = '';
        let headerClass = '';
        switch(type) {
            case 'success':
                icon = '<i class="bi bi-check-circle-fill me-2"></i>';
                headerClass = 'bg-success text-white';
                break;
            case 'error':
                icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
                headerClass = 'bg-danger text-white';
                break;
            case 'warning':
                icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
                headerClass = 'bg-warning text-dark';
                break;
            default:
                icon = '<i class="bi bi-info-circle-fill me-2"></i>';
                headerClass = 'bg-primary text-white';
        }
        
        // Modal HTML erstellen
        const modalHtml = `
            <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header ${headerClass}">
                            <h5 class="modal-title" id="updateModalLabel">
                                ${icon}${title}
                            </h5>
                            <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'}" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                            ${type === 'success' ? '<button type="button" class="btn btn-primary" data-bs-dismiss="modal">Verstanden</button>' : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Modal zum DOM hinzufügen
        $('body').append(modalHtml);
        
        // Modal anzeigen
        const modal = new bootstrap.Modal(document.getElementById('updateModal'));
        modal.show();
        
        // Modal nach dem Schließen aus DOM entfernen
        $('#updateModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
        
        return modal;
    }
});
</script> 