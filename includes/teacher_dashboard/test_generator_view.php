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
            <div class="col-md-12">
                <label for="answer_type" class="form-label">Art der richtigen Antworten:</label>
                <select class="form-select" name="answer_type" id="answer_type">
                    <option value="single">Immer nur eine richtige Antwort</option>
                    <option value="multiple">Mehrere richtige Antworten möglich</option>
                    <option value="mixed">Gemischt (einzelne und mehrere)</option>
                </select>
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
                <input type="url" class="form-control" name="youtube_url" id="youtube_url" 
                       placeholder="https://www.youtube.com/watch?v=...">
                <div class="invalid-feedback" id="youtube_url_error"></div>
                <div class="form-text">Geben Sie die URL eines YouTube-Videos ein, dessen Inhalt für die Testgenerierung verwendet werden soll.</div>
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