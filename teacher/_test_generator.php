<?php
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: index.php');
    exit();
}
?>

<div class="generator-container">
    <h2>Automatische Test-Generierung</h2>
    
    <div class="test-generation-wrapper">
        <form id="generatorForm" action="javascript:void(0);" method="post" enctype="multipart/form-data">
            <div class="generator-options">
                <h3>Quelle auswählen</h3>
                <div class="source-selector">
                    <label class="source-option">
                        <input type="radio" name="source_type" value="file" checked required> 
                        <span class="option-text">Datei hochladen (Bild/PDF)</span>
                    </label>
                    <label class="source-option">
                        <input type="radio" name="source_type" value="youtube" required> 
                        <span class="option-text">YouTube-Link</span>
                    </label>
                    <label class="source-option">
                        <input type="radio" name="source_type" value="math" required> 
                        <span class="option-text">Mathematik-Aufgaben</span>
                    </label>
                </div>

                <div id="fileUpload" class="source-input">
                    <div class="file-upload">
                        <input 
                            type="file" 
                            name="source_file_upload" 
                            accept=".jpg,.jpeg,.png,.pdf" 
                            id="sourceFile" 
                            class="file-input"
                            data-type="file"
                        >
                        <label for="sourceFile" class="file-label">
                            <span class="file-button">Datei auswählen</span>
                            <span class="file-name">Keine Datei ausgewählt</span>
                        </label>
                    </div>
                    <div class="file-info">Erlaubte Formate: JPG, PNG, PDF</div>
                </div>

                <div id="youtubeInput" class="source-input" style="display:none;">
                    <input type="url" name="youtube_url" placeholder="YouTube-URL eingeben" pattern="^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$">
                </div>

                <div id="mathInfo" class="source-input" style="display:none;">
                    <p>Laden Sie Bilder oder PDFs von Mathematikaufgaben hoch. Der Generator erstellt ähnliche Aufgaben mit LaTeX-Unterstützung.</p>
                    <div class="file-upload">
                        <input 
                            type="file" 
                            name="source_file_math" 
                            accept=".jpg,.jpeg,.png,.pdf" 
                            id="mathFile" 
                            class="file-input"
                            data-type="math"
                        >
                        <label for="mathFile" class="file-label">
                            <span class="file-button">Datei auswählen</span>
                            <span class="file-name">Keine Datei ausgewählt</span>
                        </label>
                    </div>
                    <div class="file-info">Erlaubte Formate: JPG, PNG, PDF</div>
                </div>

                <h3>Test-Einstellungen</h3>
                <div class="test-settings">
                    <div class="setting-group">
                        <label for="questionCount">Anzahl der Fragen:</label>
                        <input type="number" id="questionCount" name="question_count" min="1" max="20" value="10">
                    </div>

                    <div class="setting-group">
                        <label for="answerCount">Maximale Anzahl Antwortoptionen:</label>
                        <input type="number" id="answerCount" name="answer_count" min="2" max="6" value="4">
                    </div>

                    <div class="setting-group">
                        <label>Art der richtigen Antworten:</label>
                        <select name="correct_answer_type">
                            <option value="single">Immer nur eine richtige Antwort</option>
                            <option value="multiple">Mehrere richtige Antworten möglich</option>
                            <option value="mixed">Gemischt (zufällig)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn primary-btn">Test generieren</button>
            </div>
        </form>

        <div id="generationProgress" style="display:none;" class="progress-container">
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
            <p class="status-text">Test wird generiert...</p>
        </div>

        <div id="generationResult" style="display:none;" class="result-container">
            <h3>Test wurde generiert!</h3>
            <div class="result-info">
                <p>Zugangscode: <strong id="accessCode"></strong></p>
                <div class="qr-code" id="qrCode"></div>
                <a href="#" id="downloadQR" class="btn" download>QR-Code herunterladen</a>
            </div>
        </div>
    </div>
</div>

<div style="position: fixed; bottom: 5px; right: 5px; font-size: 0.7em; color: #666; opacity: 0.5;">
    <?php echo "Letzte Änderung: " . date('d.m.Y H:i:s', filemtime(__FILE__)); ?>
</div>

<style>
.generator-container {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.source-selector {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.source-option {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.source-option:hover {
    background: #f5f5f5;
}

.option-text {
    margin-left: 8px;
}

.source-input {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}

.file-upload {
    position: relative;
    display: inline-block;
    width: 100%;
    margin-bottom: 10px;
}

.file-input {
    position: absolute;
    left: -9999px;
}

.file-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.file-button {
    background: #4CAF50;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
}

.file-name {
    color: #666;
}

.file-info {
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.setting-group {
    margin-bottom: 15px;
}

.setting-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.setting-group input,
.setting-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.progress-container {
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.result-container {
    margin: 20px 0;
    padding: 20px;
    background: #f0f7f0;
    border-radius: 8px;
    border: 1px solid #4CAF50;
    text-align: center;
}

.progress-bar {
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 20px 0;
    border: 1px solid #ddd;
}

.progress {
    width: 0%;
    height: 100%;
    background: #4CAF50;
    transition: width 0.5s ease;
}

.status-text {
    text-align: center;
    color: #666;
    margin: 10px 0;
}

.result-info {
    text-align: center;
    margin: 20px 0;

    strong {
        font-size: 1.2em;
        color: #4CAF50;
    }
}

.qr-code {
    margin: 20px auto;
    max-width: 200px;
    padding: 10px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.qr-code img {
    width: 100%;
    height: auto;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
}

.btn:hover {
    background: #45a049;
}

.test-generation-wrapper {
    position: relative;
    min-height: 200px;
}

.progress-container,
.result-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    z-index: 100;
}
</style>

<script>
// Füge Timestamp für Cache-Busting hinzu
const scriptVersion = '<?php echo filemtime(__FILE__); ?>';

document.addEventListener('DOMContentLoaded', function() {
    const sourceTypeInputs = document.querySelectorAll('input[name="source_type"]');
    const fileUpload = document.getElementById('fileUpload');
    const youtubeInput = document.getElementById('youtubeInput');
    const mathInfo = document.getElementById('mathInfo');

    // Quelle umschalten
    sourceTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Hole alle file inputs
            const fileInputs = document.querySelectorAll('input[type="file"]');

            // Verstecke alle Eingabefelder
            fileUpload.style.display = 'none';
            youtubeInput.style.display = 'none';
            mathInfo.style.display = 'none';

            // Deaktiviere alle file inputs
            fileInputs.forEach(input => {
                input.value = ''; // Setze Wert zurück
            });

            // Zeige das ausgewählte Eingabefeld
            if (this.value === 'file') {
                fileUpload.style.display = 'block';
                document.getElementById('sourceFile').disabled = false;
            } else if (this.value === 'youtube') {
                youtubeInput.style.display = 'block';
            } else if (this.value === 'math') {
                mathInfo.style.display = 'block';
                document.getElementById('mathFile').disabled = false;
            }
            
            // Setze alle File-Inputs zurück
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.value = '';
                const nameSpan = input.parentNode.querySelector('.file-name');
                if (nameSpan) {
                    nameSpan.textContent = 'Keine Datei ausgewählt';
                }
            });
        });
    });

    // Formular-Handler
    document.getElementById('generatorForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Hole die Container
        const progress = document.getElementById('generationProgress');
        const progressBar = progress.querySelector('.progress');
        const statusText = progress.querySelector('.status-text');
        const resultDiv = document.getElementById('generationResult');
        
        console.log('Progress elements:', { progress, progressBar, statusText, resultDiv });
        
        // Zeige Fortschritt, verstecke Ergebnis
        progress.style.display = 'block';
        resultDiv.style.display = 'none';
        progressBar.style.width = '0%';
        progressBar.style.backgroundColor = '#4CAF50';
        statusText.style.color = '#666';
        statusText.textContent = 'Test wird generiert...';
        
        console.log('Progress bar should be visible now');
        
        // Deaktiviere den Submit-Button während der Verarbeitung
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Wird verarbeitet...';
        
        // Aktiviere den Button wieder bei Fehler oder nach Abschluss
        const resetButton = () => {
            submitButton.disabled = false;
            submitButton.textContent = 'Test generieren';
        };

        // Validiere Formular vor dem Absenden
        const sourceType = document.querySelector('input[name="source_type"]:checked').value;
        if (sourceType === 'file' || sourceType === 'math') {
            const fileInput = sourceType === 'file' 
                ? document.querySelector('#fileUpload input[type="file"]')
                : document.querySelector('#mathInfo input[type="file"]');
            
            console.log('Selected file input:', fileInput);
            console.log('Files:', fileInput?.files);
            
            if (!fileInput || !fileInput.files || !fileInput.files.length) {
                resetButton();
                alert('Bitte wählen Sie eine Datei aus.');
                return;
            }

            const file = fileInput.files[0];
            console.log('Selected file:', file);
            
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                resetButton();
                alert('Ungültiger Dateityp. Erlaubt sind: JPG, PNG, PDF');
                return;
            }

            // Prüfe Dateigröße (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                resetButton();
                alert('Die Datei ist zu groß. Maximale Größe ist 10MB.');
                return;
            }
        }

        if (sourceType === 'youtube' && !document.querySelector('input[name="youtube_url"]').value.trim()) {
            resetButton();
            alert('Bitte geben Sie eine YouTube-URL ein.');
            return;
        }
        
        try {
            const formData = new FormData(this);
            
            // Entferne leere File-Inputs
            if (formData.has('source_file_upload') && !formData.get('source_file_upload').size) {
                formData.delete('source_file_upload');
            }
            if (formData.has('source_file_math') && !formData.get('source_file_math').size) {
                formData.delete('source_file_math');
            }
            
            // Füge die richtige Datei hinzu
            const sourceType = formData.get('source_type');
            if (sourceType === 'file' || sourceType === 'math') {
                const fileInput = sourceType === 'file' 
                    ? document.querySelector('#sourceFile')
                    : document.querySelector('#mathFile');
                
                if (fileInput.files.length) {
                    formData.append('source_file', fileInput.files[0]);
                }
            }
            
            // Debug: Zeige detaillierte FormData-Inhalte
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ', pair[1]);
                if (pair[0] === 'source_file' && pair[1] instanceof File) {
                    console.log('File details:', {
                        name: pair[1].name,
                        type: pair[1].type,
                        size: pair[1].size
                    });
                }
            }
            
            // Zeige Wartestatus sofort
            progressBar.style.width = '90%';
            statusText.textContent = 'Generiere Test mit ChatGPT... (kann einige Minuten dauern)';
            
            // Debug: Zeige Request-Details
            console.log('Sending request to:', 'teacher/generate_test.php');
            console.log('Request method:', 'POST');
            console.log('FormData contains file:', formData.has('source_file'));
            
            const response = await fetch('teacher/generate_test.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response received:', response);
            const result = await response.json();
            console.log('Result:', result);
            
            if (result.success) {
                // Zeige Erfolg
                progressBar.style.width = '100%';
                statusText.textContent = 'Test wurde erfolgreich generiert!';
                statusText.style.color = '#4CAF50';
                
                // Zeige Ergebnis
                resultDiv.style.display = 'block';
                document.getElementById('accessCode').textContent = result.accessCode;
                document.getElementById('qrCode').innerHTML = `<img src="qrcodes/${result.qrcode}" alt="QR-Code">`;
                document.getElementById('downloadQR').href = `qrcodes/${result.qrcode}`;
            } else {
                let errorMessage = result.error || 'Unbekannter Fehler';
                
                if (result.details) {
                    console.error('Error details:', result.details);
                    errorMessage += '\n\nDetails:\n' + 
                        `Message: ${result.details.message}\n` +
                        `File: ${result.details.file}\n` +
                        `Line: ${result.details.line}`;
                }
                
                if (result.errorDetails) {
                    console.error('Error context:', result.errorDetails);
                    errorMessage += '\n\nContext:\n' + JSON.stringify(result.errorDetails, null, 2);
                }
                
                throw new Error(errorMessage);
            }
            
        } catch (error) {
            console.error('Error:', error);
            resetButton();
            progressBar.style.width = '100%';
            progressBar.style.backgroundColor = '#ff4444';
            // Formatiere die Fehlermeldung für bessere Lesbarkeit
            statusText.innerHTML = '<strong>Fehler:</strong><br><pre style="text-align: left; margin-top: 10px; padding: 10px; background: #fff0f0; border-radius: 4px; overflow: auto; max-height: 200px;">' + 
                error.message
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\n/g, '<br>')
                    .replace(/  /g, '&nbsp;&nbsp;') +
                '</pre>';
            statusText.style.color = '#ff0000';
        }
    });

    // Validiere Dateiauswahl sofort
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function(e) {
            if (!this.files || !this.files.length) {
                alert('Bitte wählen Sie eine Datei aus.');
                return;
            }

            const file = this.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                this.value = ''; // Setze Input zurück
                alert('Ungültiger Dateityp. Erlaubt sind: JPG, PNG, PDF');
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                this.value = ''; // Setze Input zurück
                alert('Die Datei ist zu groß. Maximale Größe ist 10MB.');
                return;
            }

            const fileName = file.name;
            this.parentNode.querySelector('.file-name').textContent = fileName;
        });
    });

    // Debug: Prüfe ob das Script geladen wird
    console.log('Script loaded');
    
    // Debug: Prüfe ob das Formular gefunden wird
    console.log('Form element:', document.getElementById('generatorForm'));
});
</script> 