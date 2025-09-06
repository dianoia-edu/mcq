<div class="container mt-4">
    <h2>Test-Generator</h2>
    <form id="uploadForm" enctype="multipart/form-data" class="mb-4">
        <!-- Einstellungen für die Testgenerierung -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="question_count" class="form-label">Anzahl der Fragen:</label>
                <input type="number" class="form-control" name="question_count" id="question_count" 
                       value="10" min="1" max="50">
            </div>
            <div class="col-md-6">
                <label for="answer_count" class="form-label">Anzahl der Antwortmöglichkeiten:</label>
                <input type="number" class="form-control" name="answer_count" id="answer_count" 
                       value="4" min="2" max="6">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="answer_type" class="form-label">Art der richtigen Antworten:</label>
                <select class="form-select" name="answer_type" id="answer_type">
                    <option value="single">Immer nur eine richtige Antwort</option>
                    <option value="multiple">Mehrere richtige Antworten möglich</option>
                    <option value="mixed">Gemischt (einzelne und mehrere)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="ai_model" class="form-label">
                    KI-Modell
                    <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="refreshModels()" title="Modelle aktualisieren">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </label>
                <select class="form-select" name="ai_model" id="ai_model" required>
                    <option value="">Modelle werden geladen...</option>
                </select>
                <div class="form-text">
                    <span id="model-info" class="text-muted">Automatische Auswahl des besten verfügbaren Modells</span>
                </div>
                <div id="model-status" class="mt-2"></div>
            </div>
        </div>

        <!-- Datei-Upload -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="source_file" class="form-label">Datei auswählen:</label>
                <input type="file" class="form-control" name="source_file" id="source_file" 
                       accept=".pdf,.jpg,.jpeg,.png,.bmp,.txt,.doc,.docx">
                <div class="form-text">Erlaubte Dateitypen: PDF, JPG, PNG, BMP, TXT, DOC, DOCX</div>
            </div>
        </div>

        <!-- Webseiten-URL -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="webpage_url" class="form-label">Webseiten-URL:</label>
                <input type="url" class="form-control" name="webpage_url" id="webpage_url" 
                       placeholder="https://www.beispiel.de">
                <div class="invalid-feedback" id="webpage_url_error"></div>
                <div class="form-text">Geben Sie die URL einer Webseite ein, deren Inhalt für die Testgenerierung verwendet werden soll.</div>
            </div>
        </div>

        <!-- YouTube-Link -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="youtube_url" class="form-label">YouTube-Video-URL:</label>
                <div class="input-group">
                    <input type="url" class="form-control" name="youtube_url" id="youtube_url" 
                           placeholder="https://www.youtube.com/watch?v=...">
                    <button type="button" class="btn btn-outline-primary" id="subtitleToBtn" 
                            title="Öffne subtitle.to für Untertitel-Download">
                        📥 Untertitel laden
                    </button>
                </div>
                <div class="invalid-feedback" id="youtube_url_error"></div>
                <div class="form-text">
                    Geben Sie die URL eines YouTube-Videos ein. Mit "Untertitel laden" öffnen Sie subtitle.to zum direkten Download.
                </div>
            </div>
        </div>

        <!-- Hinweis für Quellen -->
        <div class="alert alert-info mb-3">
            <small>Bitte stellen Sie mindestens eine Quelle bereit (Datei, Webseite oder YouTube-Video). Sie können auch mehrere Quellen gleichzeitig verwenden.</small>
        </div>

        <!-- Progress Bar -->
        <div class="progress mb-3" style="display: none;">
            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
        </div>

        <!-- Submit Button -->
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Test generieren</button>
            </div>
        </div>
    </form>
    <div id="generationResult" class="mt-4"></div>
</div>

<!-- Subtitle.to Modal -->
<div class="modal fade" id="subtitleToModal" tabindex="-1" aria-labelledby="subtitleToModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subtitleToModalLabel">
                    📥 Untertitel mit subtitle.to herunterladen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Workflow-Tabs -->
                <ul class="nav nav-tabs" id="subtitleWorkflowTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="download-tab" data-bs-toggle="tab" data-bs-target="#download-pane" type="button" role="tab">
                            📥 1. Download
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-pane" type="button" role="tab">
                            📤 2. Upload
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate-pane" type="button" role="tab">
                            🚀 3. Generieren
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="subtitleWorkflowContent">
                    <!-- Tab 1: Download -->
                    <div class="tab-pane fade show active" id="download-pane" role="tabpanel">
                        <div class="alert alert-info m-3 mb-0">
                            <h6><strong>📋 Schritt 1: Untertitel herunterladen</strong></h6>
                            <ol class="mb-2">
                                <li>Warten Sie, bis die subtitle.to Seite geladen ist</li>
                                <li>Klicken Sie auf "Download" bei den gewünschten Untertiteln</li>
                                <li>Speichern Sie die .txt oder .srt Datei auf Ihrem Computer</li>
                                <li>Wechseln Sie dann zum "Upload" Tab</li>
                            </ol>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="openSubtitleToExternal">
                                    🔗 In neuem Tab öffnen
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="$('#upload-tab').tab('show')">
                                    ➡️ Weiter zu Upload
                                </button>
                            </div>
                        </div>
                        <div id="subtitleToFrame" style="height: 60vh;">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Lade subtitle.to...</span>
                                </div>
                                <p class="mt-3">Lade subtitle.to Seite...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 2: Upload -->
                    <div class="tab-pane fade" id="upload-pane" role="tabpanel">
                        <div class="p-4">
                            <div class="alert alert-success">
                                <h6><strong>📤 Schritt 2: Untertitel-Datei hochladen</strong></h6>
                                <p>Laden Sie die heruntergeladene Untertitel-Datei (.txt, .srt) hier hoch:</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="subtitleFileUpload" class="form-label">
                                    <strong>Untertitel-Datei auswählen:</strong>
                                </label>
                                <input type="file" class="form-control" id="subtitleFileUpload" 
                                       accept=".txt,.srt,.vtt,.sbv" 
                                       onchange="handleSubtitleFileUpload(this)">
                                <div class="form-text">
                                    Unterstützte Formate: .txt, .srt, .vtt, .sbv (max. 10MB)
                                </div>
                            </div>
                            
                            <div id="uploadStatus" class="mb-3" style="display: none;">
                                <div class="alert alert-info">
                                    <h6>📄 Datei-Vorschau:</h6>
                                    <div id="filePreview" style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;"></div>
                                    <div class="mt-2">
                                        <strong>Dateiname:</strong> <span id="fileName"></span><br>
                                        <strong>Größe:</strong> <span id="fileSize"></span><br>
                                        <strong>Zeichen:</strong> <span id="fileLength"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="$('#download-tab').tab('show')">
                                    ⬅️ Zurück zu Download
                                </button>
                                <button type="button" class="btn btn-success" id="proceedToGenerate" disabled onclick="$('#generate-tab').tab('show')">
                                    ➡️ Weiter zur Generierung
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 3: Generate -->
                    <div class="tab-pane fade" id="generate-pane" role="tabpanel">
                        <div class="p-4">
                            <div class="alert alert-warning">
                                <h6><strong>🚀 Schritt 3: Test generieren</strong></h6>
                                <p>Bereit für die Test-Generierung mit den Untertiteln!</p>
                            </div>
                            
                            <!-- Test-Optionen -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="modalQuestionCount" class="form-label">Anzahl Fragen:</label>
                                    <select class="form-select" id="modalQuestionCount">
                                        <option value="5">5 Fragen</option>
                                        <option value="10" selected>10 Fragen</option>
                                        <option value="15">15 Fragen</option>
                                        <option value="20">20 Fragen</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="modalAnswerCount" class="form-label">Antworten pro Frage:</label>
                                    <select class="form-select" id="modalAnswerCount">
                                        <option value="3">3 Antworten</option>
                                        <option value="4" selected>4 Antworten</option>
                                        <option value="5">5 Antworten</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="modalTestTitle" class="form-label">Test-Titel (optional):</label>
                                <input type="text" class="form-control" id="modalTestTitle" 
                                       placeholder="Automatisch basierend auf Video-Inhalt">
                            </div>
                            
                            <div id="generationProgress" style="display: none;">
                                <div class="alert alert-info">
                                    <h6>⏳ Test wird generiert...</h6>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div id="progressText" class="mt-2">Vorbereitung...</div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="$('#upload-tab').tab('show')">
                                    ⬅️ Zurück zu Upload
                                </button>
                                <button type="button" class="btn btn-primary btn-lg" id="startTestGeneration">
                                    🚀 Test jetzt generieren
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <div class="d-flex justify-content-between w-100">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        ❌ Abbrechen
                    </button>
                    <div class="text-muted small">
                        💡 Tipp: Sie können jederzeit zwischen den Tabs wechseln
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Lokale Helper-Funktionen für diese View
function getTeacherUrl(filename) {
    const isInTeacherDir = <?php echo json_encode(strpos($_SERVER['REQUEST_URI'], '/teacher/') !== false); ?>;
    return isInTeacherDir ? filename : 'teacher/' + filename;
}

function getIncludesUrl(path) {
    const isInTeacherDir = <?php echo json_encode(strpos($_SERVER['REQUEST_URI'], '/teacher/') !== false); ?>;
    return isInTeacherDir ? '../includes/' + path : 'includes/' + path;
}

// Pfad-Konfiguration direkt aus PHP
window.mcqPaths = {
    isInTeacherDir: <?php echo json_encode(strpos($_SERVER['REQUEST_URI'], '/teacher/') !== false); ?>,
    generateTestUrl: '<?php echo strpos($_SERVER['REQUEST_URI'], '/teacher/') !== false ? 'generate_test.php' : 'teacher/generate_test.php'; ?>',
    basePath: '<?php echo strpos($_SERVER['REQUEST_URI'], '/teacher/') !== false ? '' : 'teacher/'; ?>'
};

console.log('MCQ Paths configured:', window.mcqPaths);

// Modell-Verwaltung
let availableModels = [];
let currentBestModel = null;

// Lade verfügbare Modelle beim Seitenaufruf
$(document).ready(function() {
    loadAvailableModels();
});

// Helper-Funktion: Erstelle Pfad für includes-Dateien
function getIncludesUrl(path) {
    if (window.mcqPaths && window.mcqPaths.isInTeacherDir) {
        return '../includes/' + path;
    } else {
        return 'includes/' + path;
    }
}

// Lade verfügbare OpenAI-Modelle
function loadAvailableModels(forceRefresh = false) {
    const modelSelect = $('#ai_model');
    const modelInfo = $('#model-info');
    const modelStatus = $('#model-status');
    
    // Loading-Zustand
    if (!forceRefresh) {
        modelSelect.html('<option value="">Modelle werden geladen...</option>');
    }
    
    const refreshParam = forceRefresh ? '&refresh=true' : '';
    
    $.ajax({
        url: getIncludesUrl('teacher_dashboard/get_openai_models.php') + '?_t=' + Date.now() + refreshParam,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.models) {
                availableModels = response.models;
                currentBestModel = response.best_model;
                populateModelSelect(response.models, response.best_model);
                
                // Zeige Statistiken
                modelInfo.html(`${response.models.length} Modelle verfügbar. Empfohlen: <strong>${response.best_model.name}</strong>`);
                
                if (forceRefresh) {
                    showModelStatus('Modelle erfolgreich aktualisiert', 'success');
                }
            } else {
                // Verwende Fallback-Modelle
                const fallbackModels = response.fallback_models || getDefaultModels();
                availableModels = fallbackModels;
                currentBestModel = fallbackModels[0];
                populateModelSelect(fallbackModels, fallbackModels[0]);
                
                showModelStatus('API nicht verfügbar - verwende Standard-Modelle', 'warning');
                modelInfo.html('Standard-Modelle geladen (API-Fehler)');
            }
        },
        error: function(xhr, status, error) {
            console.error('Fehler beim Laden der Modelle:', error);
            
            // Fallback zu Standard-Modellen
            const defaultModels = getDefaultModels();
            availableModels = defaultModels;
            currentBestModel = defaultModels[0];
            populateModelSelect(defaultModels, defaultModels[0]);
            
            showModelStatus('Verbindungsfehler - verwende Standard-Modelle', 'warning');
            modelInfo.html('Offline-Modelle geladen');
        }
    });
}

// Fülle das Model-Select-Element
function populateModelSelect(models, bestModel) {
    const modelSelect = $('#ai_model');
    modelSelect.empty();
    
    // Automatische Auswahl Option
    modelSelect.append(new Option('🤖 Automatische Auswahl (Empfohlen)', 'auto', true, true));
    
    // Trennlinie
    modelSelect.append(new Option('─────────────────', '', false, false));
    
    // Modelle gruppieren: Empfohlene zuerst
    const recommendedModels = models.filter(m => m.recommended);
    const otherModels = models.filter(m => !m.recommended);
    
    if (recommendedModels.length > 0) {
        modelSelect.append(new Option('📈 EMPFOHLENE MODELLE', '', false, false));
        recommendedModels.forEach(model => {
            const contextInfo = model.context_window >= 32000 ? ' (Groß)' : ' (Standard)';
            const option = new Option(
                '  ' + model.name + contextInfo, 
                model.id, 
                false, 
                false
            );
            modelSelect.append(option);
        });
    }
    
    if (otherModels.length > 0) {
        modelSelect.append(new Option('📋 WEITERE MODELLE', '', false, false));
        otherModels.forEach(model => {
            const contextInfo = model.context_window >= 32000 ? ' (Groß)' : ' (Standard)';
            const option = new Option(
                '  ' + model.name + contextInfo, 
                model.id, 
                false, 
                false
            );
            modelSelect.append(option);
        });
    }
    
    // Deaktiviere Gruppen-Optionen
    modelSelect.find('option').each(function() {
        if (this.value === '' || this.text.startsWith('📈') || this.text.startsWith('📋')) {
            $(this).prop('disabled', true);
        }
    });
}

// Zeige Model-Status
function showModelStatus(message, type = 'info') {
    const statusDiv = $('#model-status');
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 
                      type === 'danger' ? 'alert-danger' : 'alert-info';
    
    statusDiv.html(`<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`);
    
    // Auto-dismiss nach 5 Sekunden
    setTimeout(() => {
        statusDiv.find('.alert').alert('close');
    }, 5000);
}

// Model-Refresh Funktion
function refreshModels() {
    showModelStatus('Aktualisiere verfügbare Modelle...', 'info');
    loadAvailableModels(true);
}

// Standard-Modelle als Fallback
function getDefaultModels() {
    return [
        {
            id: 'gpt-4o',
            name: 'GPT-4o (Neueste)',
            context_window: 128000,
            recommended: true
        },
        {
            id: 'gpt-4o-mini',
            name: 'GPT-4o Mini (Effizient)',
            context_window: 128000,
            recommended: true
        },
        {
            id: 'gpt-4-turbo',
            name: 'GPT-4 Turbo',
            context_window: 128000,
            recommended: true
        },
        {
            id: 'gpt-3.5-turbo',
            name: 'GPT-3.5 Turbo (Standard)',
            context_window: 4096,
            recommended: true
        }
    ];
}

// Event-Handler für Model-Auswahl-Änderung
$('#ai_model').on('change', function() {
    const selectedModel = $(this).val();
    const modelInfo = $('#model-info');
    
    if (selectedModel === 'auto') {
        modelInfo.html(`Automatische Auswahl: <strong>${currentBestModel ? currentBestModel.name : 'GPT-4o'}</strong>`);
    } else if (selectedModel) {
        const model = availableModels.find(m => m.id === selectedModel);
        if (model) {
            const contextInfo = model.context_window >= 32000 ? 
                ` (${Math.floor(model.context_window / 1000)}K Context)` : 
                ` (${model.context_window} Context)`;
            modelInfo.html(`Ausgewählt: <strong>${model.name}</strong>${contextInfo}`);
        }
    }
});

// Teste ausgewähltes Modell (optional)
function testSelectedModel() {
    const selectedModel = $('#ai_model').val();
    if (!selectedModel || selectedModel === 'auto') {
        showModelStatus('Bitte wählen Sie ein spezifisches Modell zum Testen', 'warning');
        return;
    }
    
    showModelStatus('Teste Modell...', 'info');
    
    $.ajax({
        url: getIncludesUrl('teacher_dashboard/get_openai_models.php') + '?test_model=' + encodeURIComponent(selectedModel),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.model_test) {
                const test = response.model_test;
                const message = `${test.model}: ${test.message}`;
                showModelStatus(message, test.working ? 'success' : 'danger');
            }
        },
        error: function() {
            showModelStatus('Fehler beim Testen des Modells', 'danger');
        }
    });
}
</script>

<!-- Test Preview Modal -->
<div class="modal fade" id="testGeneratorPreviewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalTitle">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="previewModalTitle">Test Vorschau</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body p-0">
                <div class="test-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-success" id="saveTest">Test speichern</button>
                <button type="button" class="btn btn-primary" id="editTest">Test bearbeiten</button>
            </div>
        </div>
    </div>
</div> 