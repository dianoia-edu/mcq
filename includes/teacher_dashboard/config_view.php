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

<script>
$(document).ready(function() {
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
});
</script> 