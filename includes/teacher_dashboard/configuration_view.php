<link href="../css/global.css" rel="stylesheet">

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
                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
            </form>
        </div>
    </div>

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
            <button id="syncDatabaseBtn" class="btn btn-primary">
                <i class="bi bi-arrow-repeat"></i> Datenbank synchronisieren
            </button>
            <div id="syncProgress" class="progress mt-3 d-none">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
            <div id="syncResult" class="mt-3"></div>
        </div>
    </div>
    
    <!-- Test-Verwaltung -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            Test-Verwaltung
        </div>
        <div class="card-body">
            <p class="mb-3">
                Hier können Sie Tests und zugehörige Daten vollständig aus dem System entfernen.
                <span style="display: flex; align-items: center; margin-top: 5px;">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="margin-right: 10px;"></i>
                    <strong class="text-danger">Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden!
                </span>
            </p>
            <button id="deleteTestsBtn" class="btn btn-danger">
                <i class="bi bi-trash"></i> Tests löschen
            </button>
            <div id="deleteResult" class="mt-3"></div>
        </div>
    </div>
</div>

<!-- Modal für Test-Auswahl -->
<div class="modal fade" id="selectTestsModal" tabindex="-1" aria-labelledby="selectTestsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="selectTestsModalLabel">Tests zum Löschen auswählen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div id="testLoadingSpinner" class="text-center my-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Lade Tests...</span>
                    </div>
                    <p class="mt-2">Lade verfügbare Tests...</p>
                </div>
                
                <div id="testListContainer" class="d-none">
                    <div class="alert alert-warning" style="display: flex; align-items: center; padding: 15px;">
                        <i class="bi bi-exclamation-triangle-fill" style="margin-right: 10px; flex-shrink: 0;"></i>
                        <span style="flex-grow: 1;">Wählen Sie die Tests aus, die Sie löschen möchten. Diese Aktion kann nicht rückgängig gemacht werden!</span>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check" style="margin-left: 5px; padding-left: 25px;">
                            <input class="form-check-input" type="checkbox" id="selectAllTests">
                            <label class="form-check-label" for="selectAllTests">
                                <strong>Alle Tests auswählen</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Zugangscode</th>
                                    <th>Titel</th>
                                    <th>Fragen</th>
                                    <th>Versuche</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="testList">
                                <!-- Tests werden hier dynamisch eingefügt -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="noTestsMessage" class="alert alert-info d-none" style="display: flex; align-items: center; padding: 15px;">
                    <i class="bi bi-info-circle-fill" style="margin-right: 10px; flex-shrink: 0;"></i>
                    <span style="flex-grow: 1;">Es wurden keine Tests gefunden.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" id="confirmTestSelection" disabled>
                    Ausgewählte Tests löschen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Löschbestätigung -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Löschen bestätigen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display: flex; align-items: center; padding: 15px;">
                    <i class="bi bi-exclamation-triangle-fill" style="margin-right: 10px; flex-shrink: 0;"></i>
                    <span style="flex-grow: 1;"><strong>Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden!</span>
                </div>
                
                <p style="padding-left: 15px; margin-left: 10px;">Folgende Daten werden gelöscht:</p>
                <ul>
                    <li><span id="deleteFileCount">0</span> Test(s) aus dem Dateisystem</li>
                    <li><span id="deleteDbCount">0</span> Test(s) aus der Datenbank</li>
                    <li><span id="deleteAttemptCount">0</span> Schüler-Testergebnis(se)</li>
                </ul>
                
                <p class="mb-0" style="padding-left: 15px; margin-left: 10px;">Möchten Sie wirklich fortfahren?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Löschen</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Spezifische Styles für den Löschbestätigungsdialog */
#confirmDeleteModal .alert-danger {
    display: flex !important;
    align-items: center !important;
    padding: 15px !important;
}

#confirmDeleteModal .alert-danger i,
#confirmDeleteModal .alert-danger .bi,
#confirmDeleteModal .alert-danger svg {
    margin-right: 10px !important;
    flex-shrink: 0 !important;
}

#confirmDeleteModal .alert-danger span {
    flex-grow: 1 !important;
}

#confirmDeleteModal .alert-danger strong {
    margin-right: 5px !important;
}
</style>

<script>
$(document).ready(function() {
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
            url: '../includes/teacher_dashboard/sync_database.php',
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
                                <li>Gesamtanzahl Datensätze: ${response.total}</li>
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
        // Hier können Sie die Logik zum Speichern der Konfiguration implementieren
        alert('Einstellungen wurden gespeichert');
    });
    
    // Event-Handler für den Tests-Löschen-Button
    $('#deleteTestsBtn').on('click', function() {
        // Zeige das Modal zur Test-Auswahl
        const modal = new bootstrap.Modal(document.getElementById('selectTestsModal'));
        modal.show();
        
        // Lade die Tests
        loadTests();
    });
    
    // Event-Handler für "Alle Tests auswählen" Checkbox
    $('#selectAllTests').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.test-checkbox').prop('checked', isChecked);
        updateDeleteButtonState();
    });
    
    // Event-Handler für Test-Checkboxen (delegiert)
    $(document).on('change', '.test-checkbox', function() {
        updateDeleteButtonState();
        
        // Prüfe, ob alle Checkboxen ausgewählt sind
        const allChecked = $('.test-checkbox:checked').length === $('.test-checkbox').length;
        $('#selectAllTests').prop('checked', allChecked);
    });
    
    // Event-Handler für den "Ausgewählte Tests löschen" Button
    $('#confirmTestSelection').on('click', function() {
        // Sammle ausgewählte Test-IDs
        const selectedTests = [];
        $('.test-checkbox:checked').each(function() {
            selectedTests.push($(this).val());
        });
        
        // Zähle die zu löschenden Elemente
        let fileCount = 0;
        let dbCount = 0;
        let attemptCount = 0;
        
        selectedTests.forEach(function(testId) {
            const row = $(`tr[data-test-id="${testId}"]`);
            if (row.data('file-exists')) fileCount++;
            if (row.data('in-database')) dbCount++;
            attemptCount += parseInt(row.data('attempt-count') || 0);
        });
        
        // Aktualisiere die Zahlen im Bestätigungsmodal
        $('#deleteFileCount').text(fileCount);
        $('#deleteDbCount').text(dbCount);
        $('#deleteAttemptCount').text(attemptCount);
        
        // Schließe das Auswahlmodal
        bootstrap.Modal.getInstance(document.getElementById('selectTestsModal')).hide();
        
        // Zeige das Bestätigungsmodal
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        confirmModal.show();
    });
    
    // Event-Handler für den endgültigen Löschen-Button
    $('#confirmDelete').on('click', function() {
        // Sammle ausgewählte Test-IDs und Dateinamen
        const selectedTests = [];
        $('.test-checkbox:checked').each(function() {
            const testId = $(this).val();
            const row = $(`tr[data-test-id="${testId}"]`);
            const fileName = row.data('file-name');
            
            selectedTests.push({
                test_id: testId,
                file_name: fileName
            });
        });
        
        // Schließe das Bestätigungsmodal
        bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal')).hide();
        
        // Zeige Ladeindikator
        $('#deleteResult').html('<div class="alert alert-info">Lösche Tests...</div>');
        
        // Sende Anfrage zum Löschen
        $.ajax({
            url: 'delete_tests_batch.php',
            method: 'POST',
            data: {
                test_ids: JSON.stringify(selectedTests)
            },
            success: function(response) {
                if (response.success) {
                    let debugHtml = '';
                    
                    // Wenn Debug-Informationen vorhanden sind, zeige sie an
                    if (response.debug_info && response.debug_info.length > 0) {
                        debugHtml = `
                            <div class="mt-3">
                                <button class="btn btn-sm btn-outline-secondary" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#debugInfo" 
                                        aria-expanded="false" aria-controls="debugInfo">
                                    Debug-Informationen anzeigen
                                </button>
                                <div class="collapse mt-2" id="debugInfo">
                                    <div class="card card-body">
                                        <h6>Debug-Informationen:</h6>
                                        <pre style="max-height: 300px; overflow-y: auto;">${JSON.stringify(response.debug_info, null, 2)}</pre>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    
                    $('#deleteResult').html(`
                        <div class="alert alert-success">
                            <h5>Tests erfolgreich gelöscht</h5>
                            <p class="mb-2">Zusammenfassung:</p>
                            <ul class="mb-0">
                                <li>Gelöschte Test-Dateien: ${response.deleted_files}</li>
                                <li>Gelöschte Tests aus Datenbank: ${response.deleted_db_tests}</li>
                                <li>Gelöschte Testergebnisse: ${response.deleted_attempts}</li>
                            </ul>
                        </div>
                        ${debugHtml}
                    `);
                } else {
                    $('#deleteResult').html(`
                        <div class="alert alert-danger">
                            <h5>Fehler beim Löschen der Tests</h5>
                            <p class="mb-0">${response.error}</p>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Es ist ein unerwarteter Fehler aufgetreten.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {}
                
                $('#deleteResult').html(`
                    <div class="alert alert-danger">
                        <h5>Fehler beim Löschen der Tests</h5>
                        <p class="mb-0">${errorMessage}</p>
                    </div>
                `);
            }
        });
    });
    
    // Funktion zum Laden der Tests
    function loadTests() {
        // Zeige Ladeindikator
        $('#testLoadingSpinner').removeClass('d-none');
        $('#testListContainer').addClass('d-none');
        $('#noTestsMessage').addClass('d-none');
        
        // Leere die Testliste
        $('#testList').empty();
        
        // Lade Tests via AJAX
        $.ajax({
            url: 'load_all_tests.php',
            method: 'GET',
            success: function(response) {
                if (response.success && response.tests.length > 0) {
                    // Füge Tests zur Liste hinzu
                    response.tests.forEach(function(test) {
                        const statusBadge = getStatusBadge(test);
                        const fileName = test.file_name || test.test_id;
                        
                        $('#testList').append(`
                            <tr data-test-id="${test.test_id}" 
                                data-file-exists="${test.file_exists}" 
                                data-in-database="${test.in_database}"
                                data-attempt-count="${test.attempt_count}"
                                data-file-name="${fileName}">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input test-checkbox" type="checkbox" value="${test.test_id}">
                                    </div>
                                </td>
                                <td>${test.access_code}</td>
                                <td>${test.title}</td>
                                <td>${test.question_count}</td>
                                <td>${test.attempt_count}</td>
                                <td>${statusBadge}</td>
                            </tr>
                        `);
                    });
                    
                    // Zeige die Testliste
                    $('#testListContainer').removeClass('d-none');
                } else {
                    // Zeige Nachricht, wenn keine Tests gefunden wurden
                    $('#noTestsMessage').removeClass('d-none');
                }
            },
            error: function() {
                // Zeige Fehlermeldung
                $('#testList').html(`
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Fehler beim Laden der Tests
                        </td>
                    </tr>
                `);
                $('#testListContainer').removeClass('d-none');
            },
            complete: function() {
                // Verstecke Ladeindikator
                $('#testLoadingSpinner').addClass('d-none');
            }
        });
    }
    
    // Funktion zum Erstellen eines Status-Badges
    function getStatusBadge(test) {
        if (test.file_exists && test.in_database) {
            return '<span class="badge bg-success">Vollständig</span>';
        } else if (test.file_exists) {
            return '<span class="badge bg-warning">Nur Datei</span>';
        } else if (test.in_database) {
            return '<span class="badge bg-danger">Nur Datenbank</span>';
        } else {
            return '<span class="badge bg-secondary">Unbekannt</span>';
        }
    }
    
    // Funktion zum Aktualisieren des Zustands des Löschen-Buttons
    function updateDeleteButtonState() {
        const selectedCount = $('.test-checkbox:checked').length;
        $('#confirmTestSelection').prop('disabled', selectedCount === 0);
        $('#confirmTestSelection').text(
            selectedCount > 0 ? 
            `${selectedCount} Test${selectedCount !== 1 ? 'e' : ''} löschen` : 
            'Ausgewählte Tests löschen'
        );
    }
});
</script> 