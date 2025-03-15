$(document).ready(function() {
    // Tab Navigation
    function activateTab(tabId) {
        $('.tab').removeClass('active');
        $('.tab-pane').removeClass('active').hide();
        
        $(`.tab[data-target="#${tabId}"]`).addClass('active');
        $(`#${tabId}`).addClass('active').show();
        
        localStorage.setItem('activeTab', tabId);
    }

    // Tab Click Handler
    $('.tab').on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('target').substring(1);
        activateTab(tabId);
    });

    // Restore last active tab
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab) {
        activateTab(savedTab);
    } else {
        // Default to first tab
        const firstTabId = $('.tab').first().data('target').substring(1);
        activateTab(firstTabId);
    }

    // Test Generator Form Handler
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: 'generate_test.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show test preview
                    $.get('tests/' + response.redirect.testId + '.txt', function(content) {
                        $('.test-content').text(content);
                        new bootstrap.Modal($('#testPreviewModal')).show();
                    });
                    
                    // Show success message
                    $('#generationResult').html(`
                        <div class="alert alert-success">
                            <h4>Test erfolgreich generiert!</h4>
                            <p>Dateiname: ${response.filename}</p>
                            <div class="access-code">
                                <p>Zugangscode:</p>
                                <p class="code">${response.accessCode}</p>
                            </div>
                        </div>
                    `);

                    // Switch to editor and load new test
                    setTimeout(() => {
                        activateTab('editor');
                        loadTest(response.redirect.testId);
                    }, 1500);
                } else {
                    $('#generationResult').html(`
                        <div class="alert alert-danger">
                            <h4>Fehler bei der Generierung</h4>
                            <p>${response.error}</p>
                        </div>
                    `);
                }
            }
        });
    });

    // Test Editor Functions
    function loadTest(testId) {
        $.get('tests/' + testId + '.txt', function(content) {
            $('#testContent').val(content);
        });
    }

    $('#testSelect').on('change', function() {
        const selectedFile = $(this).val();
        if (selectedFile) {
            loadTest(selectedFile.replace(/^.*[\\\/]/, '').replace('.txt', ''));
        }
    });

    $('#saveTest').on('click', function() {
        const testId = $('#testSelect').val();
        const content = $('#testContent').val();
        
        $.post('save_test.php', {
            testId: testId,
            content: content
        }, function(response) {
            if (response.success) {
                alert('Test erfolgreich gespeichert!');
            } else {
                alert('Fehler beim Speichern: ' + response.error);
            }
        });
    });

    // Configuration Functions
    $('#saveConfig').on('click', function() {
        const apiKey = $('#apiKey').val();
        
        $.post('save_config.php', {
            apiKey: apiKey
        }, function(response) {
            if (response.success) {
                alert('Konfiguration gespeichert!');
            } else {
                alert('Fehler beim Speichern der Konfiguration: ' + response.error);
            }
        });
    });
}); 