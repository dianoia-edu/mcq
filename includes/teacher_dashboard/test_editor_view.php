<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test-Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Globale CSS-Datei -->
    <link href="../css/global.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- QR-Code-Bibliothek hinzufügen -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        /* Reset für die Vorschau-Container */
        .preview-content {
            padding: 0 !important;
            background: transparent !important;
        }
        
        .preview-row > td {
            padding: 0 !important;
            background: transparent !important;
        }
        
        /* Basis-Styles für die Vorschau */
        .test-preview {
            padding: 1rem;
        }
        
        /* Fragen-Styles */
        .test-preview .question-block {
            margin-bottom: 1rem;
        }
        
        .test-preview .question-block:last-child {
            margin-bottom: 0;
        }
        
        .test-preview .question-block h4 {
            margin: 0 0 0.5rem 0;
            padding: 0;
            font-size: 1rem;
            font-weight: normal;
            line-height: 1.2;
        }
        
        /* Antworten-Container */
        .test-preview .answers {
            margin: 0 0 0 1.5rem;
            padding: 0;
        }
        
        /* Einzelne Antworten */
        .test-preview .answer {
            display: flex;
            align-items: center;
            margin: 0 0 0.25rem 0;
            padding: 0;
            color: #666;
            line-height: 1.2;
        }
        
        .test-preview .answer:last-child {
            margin-bottom: 0;
        }
        
        /* Icons in Antworten */
        .test-preview .answer i {
            width: 1rem;
            text-align: center;
            margin-right: 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Korrekte Antworten */
        .test-preview .answer.correct {
            color: #198754;
        }
        
        /* Farbliche Kennzeichnung der Antworten */
        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.15) !important;
        }
        
        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.15) !important;
        }
        
        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.15) !important;
        }
        
        .answer {
            padding: 0.5rem !important;
            margin-bottom: 0.25rem !important;
            border-radius: 0.25rem !important;
        }
    </style>
</head>
<div class="container mt-4">
    <h2>Test-Editor</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Test erstellen oder bearbeiten
        </div>
        <div class="card-body">
            <form id="testEditorForm">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="testSelector" class="form-label">Vorhandenen Test auswählen</label>
                        <select class="form-select" id="testSelector">
                            <option value="">-- Neuen Test erstellen --</option>
                            <?php foreach ($tests as $test): ?>
                            <option value="<?php echo htmlspecialchars($test['name']); ?>" 
                                    data-access-code="<?php echo htmlspecialchars($test['accessCode']); ?>"
                                    data-title="<?php echo htmlspecialchars($test['title']); ?>">
                                <?php echo htmlspecialchars($test['accessCode']); ?> - <?php echo htmlspecialchars($test['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-8">
                                <label for="testTitle" class="form-label">Titel</label>
                                <input type="text" class="form-control" id="testTitle" name="testTitle" required>
                            </div>
                            <div class="col-md-4">
                                <label for="accessCode" class="form-label">Zugangscode</label>
                                <input type="text" class="form-control" id="accessCode" name="accessCode" maxlength="3" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="questionsContainer" class="mb-3">
                    <!-- Hier werden die Fragen dynamisch eingefügt -->
                </div>
                
                <div class="mb-3">
                    <button type="button" id="addQuestionBtn" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Frage hinzufügen
                    </button>
                    <button type="button" id="importQuestionsBtn" class="btn btn-success ms-2">
                        <i class="bi bi-download"></i> Fragen aus Test importieren
                    </button>
                </div>
                
                <div class="d-flex gap-2 mb-3 button-container">
                    <button type="button" id="previewTestBtn" class="btn btn-secondary">Vorschau</button>
                    <button type="button" id="saveTestBtn" class="btn btn-secondary" disabled>Test speichern</button>
                    <button type="button" id="deleteTestBtn" class="btn btn-success" disabled>Test löschen</button>
                    <button type="button" id="showQrCodeBtn" class="btn btn-success" disabled title="QR-Code anzeigen">
                        <i class="bi bi-qr-code"></i> QR-Code anzeigen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Frage-Template (wird ausgeblendet) -->
<template id="questionTemplate">
    <div class="question-card card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="question-number mb-0">Frage 1</h5>
            <button type="button" class="btn btn-sm btn-danger remove-question">
                <i class="bi bi-trash"></i> Entfernen
            </button>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Fragetext</label>
                <input type="text" class="form-control question-text" placeholder="Geben Sie hier Ihre Frage ein">
            </div>
            
            <div class="answers-container">
                <!-- Hier werden die Antworten dynamisch eingefügt -->
            </div>
            
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-primary add-answer">
                    <i class="bi bi-plus-circle"></i> Antwort hinzufügen
                </button>
            </div>
        </div>
    </div>
</template>

<!-- Antwort-Template (wird ausgeblendet) -->
<template id="answerTemplate">
    <div class="answer-item d-flex align-items-center mb-2">
        <div class="form-check me-2">
            <input class="form-check-input answer-correct" type="checkbox">
            <label class="form-check-label">Richtig</label>
        </div>
        <input type="text" class="form-control answer-text" placeholder="Antworttext eingeben">
        <button type="button" class="btn btn-sm btn-outline-danger ms-2 remove-answer">
            <i class="bi bi-x"></i>
        </button>
    </div>
</template>

<!-- Test Preview Modal -->
<div class="modal fade" id="testEditorPreviewModal" tabindex="-1" aria-labelledby="testEditorPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="testEditorPreviewModalLabel">Test Vorschau</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="test-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- Überschreiben Bestätigungs-Modal -->
<div class="modal fade" id="overwriteConfirmModal" tabindex="-1" aria-labelledby="overwriteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="overwriteConfirmModalLabel">Test überschreiben?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="padding-left: 15px; margin-left: 10px;">Es existiert bereits ein Test mit dem Zugangscode <strong id="existingAccessCode"></strong>:</p>
                <div class="alert alert-info" style="display: flex; align-items: center;">
                    <i class="bi bi-info-circle-fill" style="margin-right: 10px;"></i>
                    <span><strong>Titel:</strong> <span id="existingTitle"></span></span>
                </div>
                <p style="padding-left: 15px; margin-left: 10px;">Möchten Sie diesen Test mit Ihrem neuen Test überschreiben?</p>
                <div class="alert alert-warning" style="display: flex; align-items: center;">
                    <i class="bi bi-exclamation-triangle-fill" style="margin-right: 10px;"></i>
                    <span><strong>Neuer Titel:</strong> <span id="newTitle"></span></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-warning" id="confirmOverwrite">Überschreiben</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Fragen Modal -->
<div class="modal fade" id="importQuestionsModal" tabindex="-1" aria-labelledby="importQuestionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="importQuestionsModalLabel">Fragen aus bestehendem Test importieren</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Zugangscode</th>
                                <th>Titel</th>
                                <th>Erstellt am</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody id="availableTestsList">
                            <?php
                            // Sortiere Tests nach Erstellungsdatum absteigend
                            usort($tests, function($a, $b) {
                                $timeA = filemtime($a['file']);
                                $timeB = filemtime($b['file']);
                                return $timeB - $timeA;
                            });
                            
                            foreach ($tests as $test): 
                                $createdAt = date("d.m.Y H:i", filemtime($test['file']));
                            ?>
                            <tr class="test-row" data-file="<?php echo htmlspecialchars($test['file']); ?>">
                                <td><?php echo htmlspecialchars($test['accessCode']); ?></td>
                                <td><?php echo htmlspecialchars($test['title']); ?></td>
                                <td><?php echo htmlspecialchars($createdAt); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success import-questions-btn">
                                        Fragen übernehmen
                                    </button>
                                </td>
                            </tr>
                            <tr class="preview-row d-none">
                                <td colspan="4" class="p-0">
                                    <div class="preview-content bg-light p-3"></div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast für Erfolgsmeldung -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3">
    <div id="successToast" class="toast align-items-center text-dark bg-success bg-opacity-25 border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Schließen"></button>
        </div>
    </div>
</div>

<script>
// Hilfsfunktionen für das Hinzufügen von Fragen und Antworten
function addQuestion() {
    // Hole das Template
    const template = document.getElementById('questionTemplate');
    const clone = document.importNode(template.content, true);
    
    // Aktualisiere die Fragennummer
    const questionCount = $('#questionsContainer .question-card').length + 1;
    $(clone).find('.question-number').text('Frage ' + questionCount);
    
    // Füge das geklonte Template zum Container hinzu
    $('#questionsContainer').append(clone);
    
    // Füge standardmäßig 4 Antwortoptionen hinzu
    const answersContainer = $('#questionsContainer .question-card:last-child .answers-container');
    for (let i = 0; i < 4; i++) {
        addAnswer(answersContainer);
    }
}

function addAnswer(container) {
    // Hole das Template
    const template = document.getElementById('answerTemplate');
    const clone = document.importNode(template.content, true);
    
    // Füge das geklonte Template zum Container hinzu
    $(container).append(clone);
}

function updateQuestionNumbers() {
    $('#questionsContainer .question-card').each(function(index) {
        $(this).find('.question-number').text('Frage ' + (index + 1));
    });
    updateButtonVisibility();
}

function updateButtonVisibility() {
    const hasTest = $('#testSelector').val() !== '';
    const hasQuestions = $('#questionsContainer .question-card').length > 0;
    const hasAccessCode = $('#accessCode').val().trim().length === 3;
    
    // Debug-Logging
    console.log('updateButtonVisibility called:', {
        hasTest,
        hasQuestions,
        questionsCount: $('#questionsContainer .question-card').length,
        hasAccessCode,
        accessCodeValue: $('#accessCode').val().trim()
    });
    
    // Speichern-Button: Aktiviert, wenn Fragen vorhanden sind
    $('#saveTestBtn').prop('disabled', !hasQuestions);
    
    // Löschen-Button: Aktiviert, wenn ein Test ausgewählt ist ODER wenn Fragen und ein Zugangscode vorhanden sind
    const showButtons = hasTest || (hasQuestions && hasAccessCode);
    $('#deleteTestBtn').prop('disabled', !showButtons);
}

// Funktion zum Speichern des Tests
function saveTest(forceOverwrite = false) {
    // Validiere Formular
    const title = $('#testTitle').val().trim();
    const accessCode = $('#accessCode').val().trim();
    
    if (!title || !accessCode) {
        alert('Bitte füllen Sie Titel und Zugangscode aus.');
        return;
    }
    
    if (accessCode.length !== 3) {
        alert('Der Zugangscode muss genau 3 Zeichen lang sein.');
        return;
    }
    
    // Prüfe, ob Fragen vorhanden sind
    if ($('#questionsContainer .question-card').length === 0) {
        alert('Bitte fügen Sie mindestens eine Frage hinzu.');
        return;
    }
    
    // Prüfe, ob der Zugangscode bereits existiert (außer bei dem aktuell bearbeiteten Test)
    let existingTest = null;
    const currentTestCode = window.currentTestFilename ? window.currentTestFilename.split('_')[0] : null;
    
    if (!forceOverwrite) {
        $('#testSelector option').each(function() {
            const optionCode = $(this).data('access-code');
            if (optionCode && optionCode === accessCode && optionCode !== currentTestCode) {
                existingTest = {
                    code: optionCode,
                    title: $(this).data('title'),
                    filename: $(this).val()
                };
                return false;
            }
        });
        
        if (existingTest) {
            // Zeige das Überschreiben-Modal
            $('#existingAccessCode').text(existingTest.code);
            $('#existingTitle').text(existingTest.title);
            $('#newTitle').text(title);
            
            const overwriteModal = new bootstrap.Modal(document.getElementById('overwriteConfirmModal'));
            overwriteModal.show();
            return;
        }
    }
    
    // Sammle Daten aus dem Formular
    const questions = [];
    let answerType = 'single';
    let hasValidQuestions = false;
    
    $('#questionsContainer .question-card').each(function() {
        const questionText = $(this).find('.question-text').val().trim();
        if (!questionText) {
            return true;
        }
        
        const answers = [];
        let correctCount = 0;
        let hasValidAnswers = true;
        
        $(this).find('.answer-item').each(function() {
            const answerText = $(this).find('.answer-text').val().trim();
            const isCorrect = $(this).find('.answer-correct').is(':checked');
            
            if (!answerText) {
                hasValidAnswers = false;
                return false;
            }
            
            answers.push({
                text: answerText,
                correct: isCorrect
            });
            
            if (isCorrect) {
                correctCount++;
            }
        });
        
        if (correctCount === 0 || !hasValidAnswers) {
            return true;
        }
        
        if (correctCount > 1) {
            answerType = 'multiple';
        }
        
        questions.push({
            text: questionText,
            answers: answers
        });
        
        hasValidQuestions = true;
    });
    
    if (!hasValidQuestions || questions.length === 0) {
        alert('Bitte stellen Sie sicher, dass mindestens eine Frage vollständig ausgefüllt ist (mit Text und mindestens einer richtigen Antwort).');
        return;
    }
    
    // Sende Daten an den Server
    $.ajax({
        url: 'save_test.php',
        type: 'POST',
        data: {
            title: title,
            access_code: accessCode,
            questions: JSON.stringify(questions),
            answer_type: answerType,
            current_filename: window.currentTestFilename || ''
        },
        success: function(response) {
            if (response.success) {
                // Aktualisiere den currentTestFilename
                window.currentTestFilename = response.filename;
                
                // Aktualisiere die Option im Dropdown
                const existingOption = $(`#testSelector option[value="${window.currentTestFilename}"]`);
                if (existingOption.length) {
                    existingOption.text(`${accessCode} - ${title}`);
                    existingOption.data('access-code', accessCode);
                    existingOption.data('title', title);
                } else {
                    $('#testSelector').append(
                        $('<option>', {
                            value: window.currentTestFilename,
                            text: `${accessCode} - ${title}`,
                            'data-access-code': accessCode,
                            'data-title': title
                        })
                    );
                }
                
                // Wähle den Test im Dropdown aus
                $('#testSelector').val(window.currentTestFilename);
                
                // Zeige Erfolgsmeldung
                showSuccessMessage('Test wurde erfolgreich gespeichert!');
                
                // Aktualisiere die Button-Sichtbarkeit
                updateButtonVisibility();
                
                // Zeige QR-Code mit der globalen Funktion aus main.js
                showQrCode(false, 'editor');
            } else {
                alert('Fehler beim Speichern: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            alert('Fehler beim Speichern: ' + error);
        }
    });
}

// Event-Handler für Buttons
$(document).ready(function() {
    // Initial Button-Status setzen
    updateButtonVisibility();
    
    // Aktualisiere Button-Status bei Änderung des Zugangscodes
    $('#accessCode').on('input', updateButtonVisibility);

    // Aktualisiere Button-Status bei Änderungen im Test-Selector
    $('#testSelector').on('change', updateButtonVisibility);

    // Überwache Änderungen im questionsContainer
    const observer = new MutationObserver(function(mutations) {
        updateButtonVisibility();
    });

    observer.observe(document.getElementById('questionsContainer'), {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: true
    });

    // Save Test Button
    $('#saveTestBtn').on('click', function() {
        saveTest();
    });

    // QR Code Button - Verwende die globale showQrCode Funktion aus main.js
    $('#showQrCodeBtn').on('click', function() {
        showQrCode(false, 'editor');
    });
    
    // Add Question Button
    $('#addQuestionBtn').on('click', function() {
        addQuestion();
    });
    
    // Remove Question Button
    $(document).on('click', '.remove-question', function() {
        $(this).closest('.question-card').remove();
        updateQuestionNumbers();
    });
    
    // Add Answer Button
    $(document).on('click', '.add-answer', function() {
        const container = $(this).closest('.question-card').find('.answers-container');
        addAnswer(container);
    });
    
    // Remove Answer Button
    $(document).on('click', '.remove-answer', function() {
        $(this).closest('.answer-item').remove();
    });
    
    // Event-Handler für das Überschreiben-Modal
    $('#confirmOverwrite').on('click', function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('overwriteConfirmModal'));
        modal.hide();
        saveTest(true);
    });

    // Event-Handler für den Import-Button
    $('#importQuestionsBtn').on('click', function() {
        const importModal = new bootstrap.Modal(document.getElementById('importQuestionsModal'));
        importModal.show();
    });

    // Event-Handler für die Import-Buttons in der Tabelle
    $(document).on('click', '.import-questions-btn', function() {
        const row = $(this).closest('tr');
        const file = row.data('file');
        
        console.log('Starting import from file:', file);
        
        // Lade die Fragen aus der XML-Datei
        $.ajax({
            url: '../includes/load_test_questions.php',
            method: 'POST',
            data: { file: file },
            dataType: 'json',
            success: function(response) {
                console.log('Import response:', response);
                
                if (response.success && response.questions) {
                    console.log('Questions to import:', response.questions.length);
                    
                    // Füge jede Frage zum aktuellen Test hinzu
                    response.questions.forEach((question, index) => {
                        console.log(`Adding question ${index + 1}`);
                        // Hole das Template
                        const template = document.getElementById('questionTemplate');
                        const clone = document.importNode(template.content, true);
                        
                        // Aktualisiere die Fragennummer
                        const questionCount = $('#questionsContainer .question-card').length + 1;
                        $(clone).find('.question-number').text('Frage ' + questionCount);
                        
                        // Setze den Fragetext
                        $(clone).find('.question-text').val(question.text);
                        
                        // Füge das geklonte Template zum Container hinzu
                        $('#questionsContainer').append(clone);
                        
                        // Füge die Antworten hinzu
                        const answersContainer = $('#questionsContainer .question-card:last-child .answers-container');
                        question.answers.forEach(answer => {
                            const answerTemplate = document.getElementById('answerTemplate');
                            const answerClone = document.importNode(answerTemplate.content, true);
                            
                            // Setze Antworttext und Korrektheit
                            $(answerClone).find('.answer-text').val(answer.text);
                            $(answerClone).find('.answer-correct').prop('checked', answer.correct);
                            
                            answersContainer.append(answerClone);
                        });
                    });
                    
                    // Schließe das Modal
                    bootstrap.Modal.getInstance(document.getElementById('importQuestionsModal')).hide();
                    
                    // Zeige Erfolgsmeldung
                    showSuccessMessage('Fragen wurden erfolgreich importiert!');

                    // Markiere den Test als geändert und aktualisiere die Button-Sichtbarkeit
                    markAsChanged();
                    updateButtonVisibility();
                } else {
                    alert('Fehler beim Laden der Fragen: ' + (response.error || 'Unbekannter Fehler'));
                }
            },
            error: function() {
                alert('Fehler bei der Kommunikation mit dem Server');
            }
        });
    });

    // Event-Handler für Klicks auf Tabellenzeilen im Import-Modal
    $(document).on('click', '.test-row', function(e) {
        if ($(e.target).closest('.import-questions-btn').length) {
            return;
        }

        const row = $(this);
        const previewRow = row.next('.preview-row');
        const previewContent = previewRow.find('.preview-content');
        const file = row.data('file');

        if (previewContent.data('loaded')) {
            previewRow.toggleClass('d-none');
            return;
        }

        $.ajax({
            url: '../includes/load_test_questions.php',
            method: 'POST',
            data: { file: file },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.questions) {
                    const previewHtml = generatePreview(response.questions);
                    previewContent.html(previewHtml);
                    previewContent.data('loaded', true);
                    previewRow.removeClass('d-none');
                }
            }
        });
    });
});

<?php if (isset($selectedTest)): ?>
// Warte bis das Dokument und alle Funktionen geladen sind
$(document).ready(function() {
    console.log("Selected test:", <?php echo json_encode($selectedTest); ?>);
    
    // Warte kurz, bis der Test-Editor vollständig initialisiert ist
    setTimeout(function() {
        // Setze zuerst die Grunddaten
        $('#testTitle').val('<?php echo addslashes($selectedTest['title']); ?>');
        $('#accessCode').val('<?php echo addslashes($selectedTest['accessCode']); ?>');
        
        // Wähle den Test im Dropdown aus
        $('#testSelector').val('<?php echo addslashes($selectedTest['name']); ?>');
        
        // Setze currentTestFilename direkt hier
        window.currentTestFilename = '<?php echo basename($selectedTest['file']); ?>';
        
        // Aktiviere die Buttons sofort
        $('#deleteTestBtn').prop('disabled', false);
        $('#showQrCodeBtn').prop('disabled', false);
        $('#saveTestBtn').prop('disabled', false);
        updateButtonVisibility();
        
        // Zeige die Button-Container an
        $('.button-container').show();
        
        // Zeige Ladeindikator
        $('#questionsContainer').html('<div class="alert alert-info">Lade Testinhalt...</div>');
        
        // Lade den Test mit korrektem Pfad
        $.ajax({
            url: 'load_test.php',
            type: 'GET',
            data: { 
                test_name: '<?php echo addslashes($selectedTest['name']); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log("Test data loaded successfully");
                    
                    // Leere den Fragen-Container
                    $('#questionsContainer').empty();
                    
                    // Verarbeite die XML-Daten
                    const parser = new DOMParser();
                    const xmlDoc = parser.parseFromString(response.xml_content, "text/xml");
                    
                    // Extrahiere Fragen und Antworten
                    const questions = xmlDoc.getElementsByTagName("question");
                    console.log("Found questions:", questions.length);
                    
                    for (let i = 0; i < questions.length; i++) {
                        // Füge eine neue Frage hinzu
                        addQuestion();
                        
                        // Hole die aktuelle Frage
                        const questionCard = $('#questionsContainer .question-card').last();
                        
                        // Setze den Fragetext
                        const questionText = questions[i].getElementsByTagName("text")[0].textContent;
                        questionCard.find('.question-text').val(questionText);
                        
                        // Leere die Antworten-Container
                        const answersContainer = questionCard.find('.answers-container');
                        answersContainer.empty();
                        
                        // Füge Antworten hinzu
                        const answers = questions[i].getElementsByTagName("answer");
                        for (let j = 0; j < answers.length; j++) {
                            // Füge eine neue Antwort hinzu
                            addAnswer(answersContainer);
                            
                            // Hole die aktuelle Antwort
                            const answerItem = answersContainer.find('.answer-item').last();
                            
                            // Setze den Antworttext
                            const answerText = answers[j].getElementsByTagName("text")[0].textContent;
                            answerItem.find('.answer-text').val(answerText);
                            
                            // Setze den Richtig-Status
                            const isCorrect = answers[j].getElementsByTagName("correct")[0].textContent === "1";
                            answerItem.find('.answer-correct').prop('checked', isCorrect);
                        }
                    }
                    
                    // Aktualisiere die Button-Sichtbarkeit
                    updateButtonVisibility();
                    
                    console.log("Test loaded successfully");
                } else {
                    console.error("Error loading test:", response.error);
                    alert("Fehler beim Laden des Tests: " + response.error);
                    $('#questionsContainer').empty();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading test:", error);
                console.error("Response:", xhr.responseText);
                alert("Fehler beim Laden des Tests: " + error);
                $('#questionsContainer').empty();
            }
        });
    }, 100); // Warte 100ms auf die vollständige Initialisierung
});
<?php endif; ?>

// Funktion zum Generieren der Vorschau
function generatePreview(questions) {
    let html = '<div class="test-preview">';
    
    questions.forEach((question, qIndex) => {
        html += `
            <div class="question-block">
                <h4>${qIndex + 1}. ${question.text}</h4>
                <div class="answers">`;
        
        question.answers.forEach((answer, aIndex) => {
            const letterPrefix = String.fromCharCode(65 + aIndex);
            if (answer.correct) {
                html += `
                    <div class="answer correct">
                        <i class="bi bi-record-circle-fill"></i>
                        ${letterPrefix}. ${answer.text}
                    </div>`;
            } else {
                html += `
                    <div class="answer">
                        <i class="bi bi-circle"></i>
                        ${letterPrefix}. ${answer.text}
                    </div>`;
            }
        });
        
        html += `
                </div>
            </div>`;
    });
    
    html += '</div>';
    return html;
}
</script> 