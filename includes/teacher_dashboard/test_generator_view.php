<?php
/**
 * Test Generator View - Hauptansicht für die Test-Generierung
 * Enthält das Formular und die Modals für Test-Erstellung
 */

// Sicherheitscheck - nur über teacher_dashboard.php aufrufbar
if (!defined('TEACHER_DASHBOARD_ACCESS')) {
    die('Direkter Zugriff nicht erlaubt');
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col">
            <h1 class="h3 mb-3 text-gray-800">Test Generator</h1>
            
            <form id="uploadForm" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-file-text me-2"></i>Test-Generator
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Eingabeoptionen -->
                                <div class="mb-3">
                                    <label class="form-label">Quelle für Test-Inhalte:</label>
                                    <div class="btn-group w-100" role="group" aria-label="Eingabequelle">
                                        <input type="radio" class="btn-check" name="inputType" id="fileInput" value="file" checked>
                                        <label class="btn btn-outline-primary" for="fileInput">📄 Datei-Upload</label>
                                        
                                        <input type="radio" class="btn-check" name="inputType" id="textInput" value="text">
                                        <label class="btn btn-outline-primary" for="textInput">📝 Text eingeben</label>
                                        
                                        <input type="radio" class="btn-check" name="inputType" id="urlInput" value="url">
                                        <label class="btn btn-outline-primary" for="urlInput">🌐 Website-URL</label>
                                        
                                        <input type="radio" class="btn-check" name="inputType" id="youtubeInput" value="youtube">
                                        <label class="btn btn-outline-primary" for="youtubeInput">📺 YouTube-Video</label>
                                    </div>
                                </div>

                                <!-- Datei-Upload -->
                                <div id="fileInputSection" class="input-section">
                                    <label for="source_file" class="form-label">Datei auswählen:</label>
                                    <input type="file" name="source_file" id="source_file" class="form-control" 
                                           accept=".txt,.pdf,.docx,.md,.srt,.vtt">
                                    <div class="form-text">
                                        Unterstützte Formate: TXT, PDF, DOCX, MD, SRT, VTT (max. 25MB)
                                    </div>
                                </div>

                                <!-- Text-Eingabe -->
                                <div id="textInputSection" class="input-section" style="display: none;">
                                    <label for="source_text" class="form-label">Text eingeben:</label>
                                    <textarea name="source_text" id="source_text" class="form-control" rows="8" 
                                              placeholder="Fügen Sie hier den Text ein, aus dem der Test erstellt werden soll..."></textarea>
                                    <div class="form-text">
                                        Mindestens 200 Zeichen für eine gute Test-Qualität empfohlen
                                    </div>
                                </div>

                                <!-- Website-URL -->
                                <div id="urlInputSection" class="input-section" style="display: none;">
                                    <label for="source_url" class="form-label">Website-URL:</label>
                                    <input type="url" name="source_url" id="source_url" class="form-control" 
                                           placeholder="https://beispiel.de/artikel">
                                    <div class="form-text">
                                        Geben Sie die URL einer Website ein, deren Inhalt für die Testgenerierung verwendet werden soll
                                    </div>
                                </div>

                                <!-- YouTube-URL -->
                                <div id="youtubeInputSection" class="input-section" style="display: none;">
                                    <label for="youtube_url" class="form-label">YouTube-Video-URL:</label>
                                    <div class="input-group">
                                        <input type="url" name="youtube_url" id="youtube_url" class="form-control" 
                                               placeholder="https://www.youtube.com/watch?v=...">
                                        <button type="button" class="btn btn-outline-info" id="subtitleToBtn" disabled>
                                            📥 Untertitel laden
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        Bitte stellen Sie mindestens eine Quelle bereit (Datei, Website oder YouTube-Video)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-gear me-2"></i>Test-Einstellungen
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Test-Einstellungen -->
                                <div class="mb-3">
                                    <label for="question_count" class="form-label">Anzahl Fragen:</label>
                                    <select name="question_count" id="question_count" class="form-select">
                                        <option value="5">5 Fragen</option>
                                        <option value="10" selected>10 Fragen</option>
                                        <option value="15">15 Fragen</option>
                                        <option value="20">20 Fragen</option>
                                        <option value="25">25 Fragen</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="answer_count" class="form-label">Antworten pro Frage:</label>
                                    <select name="answer_count" id="answer_count" class="form-select">
                                        <option value="3">3 Antworten</option>
                                        <option value="4" selected>4 Antworten</option>
                                        <option value="5">5 Antworten</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="ai_model" class="form-label">KI-Modell:</label>
                                    <div class="input-group">
                                        <select name="ai_model" id="ai_model" class="form-select">
                                            <option value="auto" selected>🤖 Automatische Auswahl (Empfohlen)</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-secondary" onclick="refreshModels()" title="Modelle aktualisieren">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                    <div id="model-info" class="form-text">45 Modelle verfügbar. Empfohlen: GPT-4o (Neueste)</div>
                                    <div id="model-status"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-magic me-2"></i>Test generieren
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="showCodeBtn()">
                                <i class="bi bi-qr-code me-2"></i>QR-Code anzeigen
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Ergebnis-Bereich -->
            <div id="generationResult"></div>
        </div>
    </div>
</div>

<!-- Subtitle.to Modal -->
<div class="modal fade" id="subtitleToModal" tabindex="-1" aria-labelledby="subtitleToModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="subtitleToModalLabel">
                    📥 Untertitel mit subtitle.to herunterladen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Einfache Tab-Navigation -->
                <div class="bg-light border-bottom">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="simpleDownloadTab" onclick="showSimpleTab('download')">
                            📥 1. Download
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="simpleUploadTab" onclick="showSimpleTab('upload')">
                            📤 2. Upload
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="simpleGenerateTab" onclick="showSimpleTab('generate')">
                            🚀 3. Generieren
                        </button>
                    </div>
                </div>
                
                <!-- Tab-Inhalte -->
                <div id="simpleTabContent">
                    <!-- Download Content (sichtbar) -->
                    <div id="simpleDownloadContent" class="simple-tab-pane">
                        <div class="alert alert-info m-3 mb-0">
                            <h6><strong>📋 Anleitung:</strong></h6>
                            <ol class="mb-0">
                                <li>Warten Sie, bis die Seite geladen ist</li>
                                <li>Klicken Sie auf "Download" bei den gewünschten Untertiteln</li>
                                <li>Laden Sie die .txt oder .srt Datei herunter</li>
                                <li>Wechseln Sie dann zum "Upload" Tab</li>
                            </ol>
                        </div>
                        <div id="subtitleToFrame" style="height: 70vh;">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Lade subtitle.to...</span>
                                </div>
                                <p class="mt-3">Lade subtitle.to Seite...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Content (versteckt) -->
                    <div id="simpleUploadContent" class="simple-tab-pane" style="display: none;">
                        <div class="p-4">
                            <div class="alert alert-success">
                                <h6><strong>📤 Upload Tab</strong></h6>
                                <p>Hier kommt später der Datei-Upload...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Generate Content (versteckt) -->
                    <div id="simpleGenerateContent" class="simple-tab-pane" style="display: none;">
                        <div class="p-4">
                            <div class="alert alert-warning">
                                <h6><strong>🚀 Generate Tab</strong></h6>
                                <p>Hier kommt später die Test-Generierung...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" id="openSubtitleToExternal">
                    🔗 In neuem Tab öffnen
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Einfache Tab-Switching Funktion
function showSimpleTab(tabName) {
    console.log('🔄 Wechsle zu Tab:', tabName);
    
    // Alle Tab-Buttons zurücksetzen
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Alle Tab-Inhalte verstecken
    document.querySelectorAll('.simple-tab-pane').forEach(pane => {
        pane.style.display = 'none';
    });
    
    // Gewählten Tab aktivieren
    const activeButton = document.getElementById('simple' + tabName.charAt(0).toUpperCase() + tabName.slice(1) + 'Tab');
    const activeContent = document.getElementById('simple' + tabName.charAt(0).toUpperCase() + tabName.slice(1) + 'Content');
    
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    if (activeContent) {
        activeContent.style.display = 'block';
    }
    
    console.log('✅ Tab gewechselt zu:', tabName);
}

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
        modelSelect.html('<option>Modelle werden geladen...</option>');
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
                modelInfo.html(`${response.models.length} Modelle verfügbar. Empfohlen: ${response.best_model.name}`);
                
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
                '   ' + model.name + contextInfo,
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
                '   ' + model.name + contextInfo,
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
        modelInfo.html(`Automatische Auswahl: ${currentBestModel ? currentBestModel.name : 'GPT-4o'}`);
    } else if (selectedModel) {
        const model = availableModels.find(m => m.id === selectedModel);
        if (model) {
            const contextInfo = model.context_window >= 32000 ? 
                ` (${Math.floor(model.context_window / 1000)}K Context)` : 
                ` (${model.context_window} Context)`;
            modelInfo.html(`Ausgewählt: ${model.name}${contextInfo}`);
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

<!-- Preview Modal für generierte Tests -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalTitle" aria-hidden="true">
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