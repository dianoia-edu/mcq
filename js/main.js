let testEditorPreviewModal = null;
let testGeneratorPreviewModal = null;
let testHasChanges = false;
let currentTestFilename = null;

// Hilfsfunktion für dynamische URL-Erstellung basierend auf der aktuellen Position
function getTeacherUrl(filename) {
    // Prüfe, ob wir uns bereits im teacher-Verzeichnis befinden
    if (window.location.pathname.includes('/teacher/')) {
        return filename;
    } else {
        return 'teacher/' + filename;
    }
}

// Hilfsfunktion für Includes-Pfade
function getIncludesUrl(path) {
    // Prüfe, ob wir uns im teacher-Verzeichnis befinden
    if (window.location.pathname.includes('/teacher/')) {
        return '../includes/' + path;
    } else {
        return 'includes/' + path;
    }
}

// Globale Konfiguration für AJAX-Pfade
window.mcqConfig = {
    basePath: window.location.pathname.includes('/teacher/') ? '../' : '',
    teacherPath: window.location.pathname.includes('/teacher/') ? '' : 'teacher/',
    includesPath: window.location.pathname.includes('/teacher/') ? '../includes/' : 'includes/'
};

$(document).ready(function() {
    console.log('Document ready, initializing...');
    
    // Tab-Funktionalität
    function initializeTabs() {
        // console.log('Initializing tabs in main.js... DEAKTIVIERT - verwende direkte Tab-Funktionalität');
        
        // Diese Funktion ist deaktiviert, da wir jetzt direkte Tab-Funktionalität in teacher_dashboard.php verwenden
        return;
        
        // Standardmäßig den Generator-Tab aktivieren, falls kein Tab in der URL angegeben ist
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            console.log('Tab from URL:', tabParam);
            // Aktiviere den entsprechenden Tab
            const targetTab = $(`.tab[data-target="#${tabParam}"]`);
            if (targetTab.length) {
                // Deaktiviere alle Tabs
                $('.tab').removeClass('active');
                $('.tab-pane').removeClass('active').hide();
                
                // Aktiviere den gewünschten Tab
                targetTab.addClass('active');
                $(`#${tabParam}`).addClass('active').show();
                
                console.log('Activated tab:', tabParam);
            }
        } else {
            // Generator-Tab ist bereits durch HTML als aktiv markiert
            $('#generator').addClass('active').show();
        }
        
        // Tab-Click-Handler - verbesserte Version mit mehr Debugging und Fehlerbehandlung
        $('.tab').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Verhindere Event-Bubbling
            
            const target = $(this).data('target');
            // console.log('Tab clicked:', target, 'Element:', this);
            
            if (!target) {
                // console.error('Tab clicked but no target specified');
                return;
            }
            
            // Prüfe, ob das Ziel existiert
            if ($(target).length === 0) {
                // console.error('Target element not found:', target);
                return;
            }
            
            // Aktiven Tab markieren
            $('.tab').removeClass('active');
            $(this).addClass('active');
            
            // Tab-Inhalte anzeigen/verstecken
            $('.tab-pane').removeClass('active').hide();
            $(target).addClass('active').fadeIn();
            
            // URL aktualisieren
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('tab', target.replace('#', ''));
            window.history.pushState({}, '', newUrl);
            
            // console.log('Tab content visibility updated. Active tab is now:', target);
            
            // Löse ein Event aus, um andere Komponenten zu informieren
            $(document).trigger('tabChanged', [target]);
        });
        
        // Debug: Liste alle Tab-Elemente auf
        /*
        console.log('Available tabs:');
        $('.tab').each(function() {
            console.log(' - Target:', $(this).data('target'), 'Text:', $(this).text());
        });
        
        console.log('Available tab panes:');
        $('.tab-pane').each(function() {
            console.log(' - ID:', this.id, 'Visible:', $(this).is(':visible'));
        });
        */
    }
    
    // Tabs initialisieren
    initializeTabs();
    
    // YouTube Video-Titel laden
    function loadYouTubeVideoTitle(url, inputElement) {
        // Extrahiere Video-ID aus der URL
        const videoId = extractYouTubeVideoId(url);
        if (!videoId) return;
        
        // Zeige Loading-Indikator
        const originalValue = inputElement.val();
        inputElement.val('Lade Video-Titel...');
        inputElement.prop('readonly', true);
        
        // Verwende YouTube oEmbed API
        $.ajax({
            url: 'https://www.youtube.com/oembed',
            method: 'GET',
            data: {
                url: url,
                format: 'json'
            },
            success: function(data) {
                if (data && data.title) {
                    // Speichere Original-URL als data-Attribut
                    inputElement.data('original-url', originalValue);
                    inputElement.val(data.title);
                    inputElement.addClass('youtube-title-loaded');
                    
                    // Füge Tooltip hinzu
                    inputElement.attr('title', 'Video-Titel: ' + data.title + '\nOriginal-URL: ' + originalValue);
                } else {
                    inputElement.val(originalValue);
                }
            },
            error: function() {
                inputElement.val(originalValue);
            },
            complete: function() {
                inputElement.prop('readonly', false);
            }
        });
    }
    
    // YouTube Video-ID extrahieren
    function extractYouTubeVideoId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }
    
    // Event-Handler für die Eingabefelder
    $(document).on('input change', '#uploadForm input[name="source_file[]"], #uploadForm input[name="webpage_url[]"], #uploadForm input[name="youtube_url"]', function() {
        // Aktiviere den Submit-Button, wenn mindestens ein Feld ausgefüllt ist
        let hasFile = false;
        $('input[name="source_file[]"]').each(function() {
            if (this.files && this.files.length > 0) {
                hasFile = true;
            }
        });
        
        let hasUrl = false;
        $('input[name="webpage_url[]"]').each(function() {
            const value = $(this).val();
            if (value && value.trim() !== '') {
                hasUrl = true;
            }
        });
        
        const youtubeInput = $('input[name="youtube_url"]');
        const youtubeValue = youtubeInput.val();
        const hasYoutube = youtubeInput.length > 0 && youtubeValue && youtubeValue.trim() !== '';
        
        $('#uploadForm button[type="submit"]').prop('disabled', !hasFile && !hasUrl && !hasYoutube);
    });
    
    // Automatische URL-Validierung während der Eingabe
    $(document).on('input', 'input[name="webpage_url[]"]', function() {
        const url = $(this).val();
        if (url && url.trim() !== '' && !isValidUrl(url.trim())) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Bitte geben Sie eine gültige URL ein.</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Automatische YouTube-URL-Validierung während der Eingabe (Event Delegation)
    $(document).on('input', 'input[name="youtube_url"]', function() {
        const url = $(this).val();
        if (!url) return;
        const trimmedUrl = url.trim();
        const subtitleBtn = $('#subtitleToBtn');
        
        if (trimmedUrl && !isValidYoutubeUrl(trimmedUrl)) {
            $(this).addClass('is-invalid');
            subtitleBtn.prop('disabled', true);
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Bitte geben Sie eine gültige YouTube-URL ein.</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            subtitleBtn.prop('disabled', !trimmedUrl);
            
            // Versuche Video-Titel zu laden, wenn URL gültig ist
            if (trimmedUrl) {
                loadYouTubeVideoTitle(trimmedUrl, $(this));
            }
        }
    });
    
    // Subtitle.to Button Handler (Event Delegation für dynamische Inhalte)
    $(document).on('click', '#subtitleToBtn', function() {
        console.log('📥 Subtitle.to Button geklickt!');
        console.log('🔍 Browser:', navigator.userAgent);
        console.log('🔍 jQuery verfügbar:', typeof $ !== 'undefined');
        console.log('🔍 Bootstrap verfügbar:', typeof bootstrap !== 'undefined');
        
        var youtubeUrl = $('#youtube_url').val();
        if (!youtubeUrl) {
            alert('Bitte geben Sie zuerst eine YouTube-URL ein.');
            return;
        }
        // Verwende Original-URL falls Video-Titel geladen wurde
        youtubeUrl = $('#youtube_url').data('original-url') || youtubeUrl;
        youtubeUrl = youtubeUrl.trim();
        console.log('YouTube-URL:', youtubeUrl);
        
        console.log('🔍 Prüfe YouTube-URL...');
        var isValid = isValidYoutubeUrl(youtubeUrl);
        console.log('🔍 URL-Validierung Ergebnis:', isValid);
        
        if (!isValid) {
            alert('Bitte geben Sie eine gültige YouTube-URL ein.');
            $('#youtube_url').focus();
            return;
        }
        
        console.log('Öffne subtitle.to Modal...');
        // Öffne subtitle.to Modal
        openSubtitleToModal(youtubeUrl);
    });
    
    // Debug: Prüfe ob Bootstrap verfügbar ist
    $(document).ready(function() {
        console.log('🔍 Bootstrap Modal verfügbar:', typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined');
        console.log('🔍 jQuery verfügbar:', typeof $ !== 'undefined');
        
        // Warte auf Tab-Laden
        setTimeout(() => {
            console.log('🔍 Subtitle-Button vorhanden:', $('#subtitleToBtn').length > 0);
            console.log('🔍 Modal vorhanden:', $('#subtitleToModal').length > 0);
            console.log('🔍 YouTube-Input vorhanden:', $('input[name="youtube_url"]').length > 0);
            
            // Detailcheck für Generator-Tab
            if ($('#generator').length > 0) {
                console.log('🔍 Generator-Tab gefunden');
                console.log('🔍 Generator aktiv:', $('#generator').hasClass('active') || $('#generator').is(':visible'));
                
                // Suche alle Input-Felder
                console.log('🔍 Alle Input-Felder:', $('input').length);
                $('input').each(function(i, el) {
                    if (el.name) console.log(`  - Input ${i}: name="${el.name}" id="${el.id}"`);
                });
                
                // Suche alle Buttons  
                console.log('🔍 Alle Buttons:', $('button').length);
                $('button').each(function(i, el) {
                    if (el.id) console.log(`  - Button ${i}: id="${el.id}" text="${$(el).text().trim()}"`);
                });
            }
        }, 2000); // 2 Sekunden warten
    });
    
    // Modals initialisieren
    const editorModalElement = document.getElementById('testEditorPreviewModal');
    const generatorModalElement = document.getElementById('testGeneratorPreviewModal');
    
    if (editorModalElement) {
        testEditorPreviewModal = new bootstrap.Modal(editorModalElement);
        console.log('Test Editor Modal initialized successfully');
    } else {
        console.error('Test Editor Modal element not found in DOM');
    }
    
    if (generatorModalElement) {
        testGeneratorPreviewModal = new bootstrap.Modal(generatorModalElement);
        console.log('Test Generator Modal initialized successfully');
    } else {
        console.error('Test Generator Modal element not found in DOM');
    }

    // Prüfe URL-Parameter
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const testParam = urlParams.get('test');
    const errorParam = urlParams.get('error');

    // Zeige Fehlermeldung wenn nötig
    if (errorParam === 'test_not_found') {
        showErrorMessage('Der angeforderte Test wurde nicht gefunden.');
    }

    // Lade Test wenn Parameter vorhanden
    if (testParam) {
        $('#testSelector').val(testParam).trigger('change');
    }

    // Test-Editor Funktionalität
    if ($('#testEditorForm').length > 0) {
        initTestEditor();
    }

    // Füge das Überschreiben-Modal zum DOM hinzu
    const overwriteModalHtml = `
        <div class="modal fade" id="overwriteConfirmModal" tabindex="-1" aria-labelledby="overwriteConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="overwriteConfirmModalLabel">Test überschreiben?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Es existiert bereits ein Test mit dem Zugangscode <strong id="existingAccessCode"></strong>:</p>
                        <div class="alert alert-info">
                            <strong>Titel:</strong> <span id="existingTitle"></span>
                        </div>
                        <p>Möchten Sie diesen Test mit Ihrem neuen Test überschreiben?</p>
                        <div class="alert alert-warning">
                            <strong>Neuer Titel:</strong> <span id="newTitle"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="button" class="btn btn-warning" id="confirmOverwrite">Überschreiben</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    $('body').append(overwriteModalHtml);

    // Event-Handler für das Überschreiben-Modal
    $('#confirmOverwrite').on('click', function() {
        const overwriteModal = bootstrap.Modal.getInstance(document.getElementById('overwriteConfirmModal'));
        overwriteModal.hide();
        // Setze eine Flag, die anzeigt, dass wir gerade überschreiben
        window.isOverwriting = true;
        saveTest(true);
    });
});

function generateAccessCode(length) {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let code = '';
    for (let i = 0; i < length; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return code;
}

function standardizeTestFormat(response) {
    if (!response.success || !response.test_content) {
        console.error('Invalid response format');
        return null;
    }

    try {
        const accessCode = response.access_code || generateAccessCode(6);
        const title = response.title || 'Unbenannter Test';
        
        const lines = response.test_content.split('\n');
        let questions = [];
        let currentQuestion = null;
        let currentAnswers = [];
        
        lines.forEach(line => {
            line = line.trim();
            if (!line) return;
            
            if (line.match(/^(?:\*\*)?Frage \d+:/)) {
                if (currentQuestion) {
                    questions.push({
                        question: currentQuestion,
                        answers: currentAnswers
                    });
                }
                currentQuestion = line.replace(/^\*\*/, '');
                currentAnswers = [];
            } else if (line.includes('*[richtig]') || line.startsWith('Falsche Antwort:') || line.startsWith('-')) {
                currentAnswers.push(line);
            }
        });
        
        if (currentQuestion) {
            questions.push({
                question: currentQuestion,
                answers: currentAnswers
            });
        }
        
        let standardContent = `${accessCode}\n${title}\n\n`;
        
        questions.forEach((q, index) => {
            standardContent += `${q.question}\n`;
            q.answers.forEach(answer => {
                if (answer.includes('*[richtig]')) {
                    const cleanAnswer = answer.replace(/\*\[richtig\]/, '').trim();
                    standardContent += `* [richtig] ${cleanAnswer}\n`;
                } else {
                    const cleanAnswer = answer
                        .replace(/^-/, '')
                        .replace(/^Falsche Antwort:/, '')
                        .trim();
                    standardContent += `Falsche Antwort: ${cleanAnswer}\n`;
                }
            });
            standardContent += '\n';
        });
        
        response.test_content = standardContent;
        return response;
        
    } catch (error) {
        console.error('Error during standardization:', error);
        return null;
    }
}

function formatTestPreview(content) {
    const lines = content.split('\n');
    let html = '<div class="test-preview">';
    
    html += `<div class="access-code">Zugangscode: ${lines[0]}</div>`;
    html += `<h2 class="test-title">${lines[1]}</h2>`;
    
    let currentQuestion = '';
    lines.slice(2).forEach(line => {
        line = line.trim();
        if (!line) return;
        
        if (line.startsWith('Frage')) {
            if (currentQuestion) html += '</div></div>';
            html += `<div class="question-block">
                      <h4>${line}</h4>
                      <div class="answers">`;
        } else if (line.startsWith('* [richtig]')) {
            const answer = line.replace('* [richtig]', '').trim();
            html += `<div class="answer correct">
                      <i class="fas fa-check"></i>
                      ${answer}
                    </div>`;
        } else if (line.startsWith('Falsche Antwort:')) {
            const answer = line.replace('Falsche Antwort:', '').trim();
            html += `<div class="answer">
                      ${answer}
                    </div>`;
        }
    });
    
    html += '</div></div></div>';
    return html;
}

// Hilfsfunktionen zur URL-Validierung
function isValidUrl(url) {
    console.log('🔍 isValidUrl aufgerufen mit:', url);
    try {
        if (typeof URL !== 'undefined') {
        new URL(url);
            console.log('🔍 URL-Validierung mit URL() erfolgreich');
        return true;
        } else {
            // Fallback für ältere Browser - einfache Regex-Prüfung
            const urlPattern = /^https?:\/\/.+/i;
            const result = urlPattern.test(url);
            console.log('🔍 URL-Validierung mit Regex:', result);
            return result;
        }
    } catch (e) {
        console.log('🔍 URL-Validierung Fehler:', e);
        return false;
    }
}

function isValidYoutubeUrl(url) {
    console.log('🔍 isValidYoutubeUrl aufgerufen mit:', url);
    
    const basicValid = isValidUrl(url);
    console.log('🔍 Basis-URL-Validierung:', basicValid);
    if (!basicValid) return false;
    
    try {
        // Edge-kompatible URL-Parsing
        let urlObj;
        if (typeof URL !== 'undefined') {
            urlObj = new URL(url);
        } else {
            // Fallback für ältere Browser
            const a = document.createElement('a');
            a.href = url;
            urlObj = {
                hostname: a.hostname,
                pathname: a.pathname,
                search: a.search
            };
        }
    
    // Erlaubte YouTube-Domains
    const validDomains = ['youtube.com', 'youtu.be', 'www.youtube.com'];
        
        // Prüfe Domain (Edge-kompatibel)
        let domainMatch = false;
        for (let i = 0; i < validDomains.length; i++) {
            if (urlObj.hostname === validDomains[i]) {
                domainMatch = true;
                break;
            }
        }
        if (!domainMatch) {
        return false;
    }
    
    // Prüfe auf Video-ID
    if (urlObj.hostname === 'youtu.be') {
        return urlObj.pathname.length > 1; // Mindestens ein Zeichen nach "/"
    } else {
            // Einfache Regex-Prüfung für v-Parameter (Edge-kompatibel)
            return /[?&]v=([^&]+)/.test(urlObj.search || '');
        }
    } catch (e) {
        console.error('Fehler bei YouTube-URL Validierung:', e);
        return false;
    }
}

// AGGRESSIVE Anti-Werbung Funktion
function removeAdsAggressively(iframe) {
    console.log('💥 AGGRESSIVE Werbung-Entfernung gestartet!');
    
    // Methode 1: Direkte iframe-Manipulation (wenn möglich)
    try {
        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        if (iframeDoc) {
            console.log('🎯 iframe-Dokument zugänglich - direkte Manipulation');
            injectAggressiveAdBlock(iframeDoc);
        } else {
            console.log('🔒 iframe-Dokument blockiert - verwende CSS-Override');
            applyExternalAdBlock(iframe);
        }
    } catch (e) {
        console.log('🔒 CORS blockiert - verwende CSS-Override:', e.message);
        applyExternalAdBlock(iframe);
    }
    
    // Methode 2: Continuous Monitoring für dynamische Werbung
    setInterval(() => {
        try {
            monitorAndRemoveAds(iframe);
        } catch (e) {
            // Ignoriere Fehler
        }
    }, 1000);
}

function injectAggressiveAdBlock(doc) {
    console.log('💉 Injiziere AGGRESSIVE Anti-Werbung CSS...');
    
    // HARDCORE CSS - entfernt fast alles außer Hauptinhalt
    var aggressiveCSS = 
        '/* AGGRESSIVE AD BLOCKING - NUCLEAR OPTION */' +
        '/* Taboola und verwandte */ ' +
        'div[class*="taboola"], div[id*="taboola"], .taboola, [data-name*="taboola"], ' +
        'div[class*="outbrain"], div[id*="outbrain"], .outbrain, ' +
        '/* Google Ads */ ' +
        '.adsbygoogle, div[id*="google_ads"], div[class*="google-ad"], ' +
        'ins.adsbygoogle, .google-ads, .google-ad, ' +
        '/* Generische Werbung */ ' +
        '.advertisement, .ad, .ads, .ad-container, .ad-wrapper, .ad-banner, ' +
        '.ad-space, .ad-unit, .ad-block, .ad-section, ' +
        '/* Popups und Overlays */ ' +
        '.popup, .popup-overlay, .overlay, .modal-backdrop, ' +
        '.notification, .notification-bar, .notification-popup, ' +
        '/* Cookie Banner */ ' +
        '.cookie-banner, .cookie-notice, .cookie-consent, .gdpr-banner, ' +
        '/* Störende Elemente */ ' +
        'div[style*="position: fixed"][style*="z-index"], ' +
        'div[style*="position: absolute"][style*="z-index"], ' +
        '[data-testid*="ad"], [data-name*="ad"], [class*="sponsored"], ' +
        'iframe[src*="doubleclick"], iframe[src*="googlesyndication"], ' +
        'iframe[src*="googleadservices"], iframe[src*="amazon-adsystem"] ' +
        '{ ' +
            'display: none !important; ' +
            'visibility: hidden !important; ' +
            'opacity: 0 !important; ' +
            'height: 0 !important; ' +
            'width: 0 !important; ' +
            'max-height: 0 !important; ' +
            'max-width: 0 !important; ' +
            'margin: 0 !important; ' +
            'padding: 0 !important; ' +
            'overflow: hidden !important; ' +
            'position: absolute !important; ' +
            'left: -9999px !important; ' +
            'top: -9999px !important; ' +
        '} ' +
        '/* Entferne störende Styles */ ' +
        'body { ' +
            'overflow: auto !important; ' +
        '} ' +
        '/* Stelle sicher dass wichtige Inhalte sichtbar sind */ ' +
        '.main, .main-content, .content, .container, ' +
        '.download, .download-section, .video-info, .subtitle-info, ' +
        'form, .form, input, button, .btn, .download-btn ' +
        '{ ' +
            'display: block !important; ' +
            'visibility: visible !important; ' +
            'opacity: 1 !important; ' +
            'position: relative !important; ' +
        '}';
    
    // CSS injizieren
    var style = doc.createElement('style');
    style.type = 'text/css';
    style.innerHTML = aggressiveCSS;
    
    var head = doc.head || doc.getElementsByTagName('head')[0];
    if (head) {
        head.appendChild(style);
        console.log('✅ AGGRESSIVE CSS injiziert');
    }
    
    // JavaScript-basierte Entfernung nach Delay
    setTimeout(() => {
        removeAdElementsAggressively(doc);
    }, 1000);
    
    setTimeout(() => {
        removeAdElementsAggressively(doc);
    }, 3000);
    
    setTimeout(() => {
        removeAdElementsAggressively(doc);
    }, 5000);
}

function removeAdElementsAggressively(doc) {
    console.log('🔥 AGGRESSIVE Element-Entfernung...');
    
    var removedCount = 0;
    
    // Aggressive Selektoren
    var aggressiveSelectors = [
        // Taboola
        'div[class*="taboola"]', 'div[id*="taboola"]', '.taboola-wrapper',
        // Outbrain  
        'div[class*="outbrain"]', 'div[id*="outbrain"]',
        // Google Ads
        '.adsbygoogle', 'ins.adsbygoogle', 'div[id*="google_ads"]',
        // Generische Werbung
        '.advertisement', '.ad', '.ads', '.ad-container', '.ad-banner',
        // Popups
        '.popup', '.popup-overlay', '.modal-backdrop.show',
        // Störende iframes
        'iframe[src*="doubleclick"]', 'iframe[src*="googlesyndication"]',
        'iframe[src*="amazon-adsystem"]', 'iframe[src*="googleadservices"]'
    ];
    
    aggressiveSelectors.forEach(function(selector) {
        try {
            var elements = doc.querySelectorAll(selector);
            for (var i = 0; i < elements.length; i++) {
                var el = elements[i];
                if (el && el.parentNode) {
                    el.parentNode.removeChild(el);
                    removedCount++;
                }
            }
        } catch (e) {
            // Ignoriere Fehler
        }
    });
    
    console.log('🔥 ' + removedCount + ' Werbung-Elemente VERNICHTET');
}

function applyExternalAdBlock(iframe) {
    console.log('🛡️ Externe CSS-Override für iframe...');
    
    // CSS für das iframe-Element selbst
    iframe.style.filter = 'contrast(1.1) brightness(1.1)';
    
    // Versuche iframe-URL zu modifizieren (Ad-Block Parameter)
    var originalSrc = iframe.src;
    if (originalSrc && !originalSrc.includes('adblock=1')) {
        iframe.src = originalSrc + (originalSrc.includes('?') ? '&' : '?') + 'adblock=1&no_ads=1';
        console.log('🔧 iframe-URL modifiziert für Ad-Blocking');
    }
}

function monitorAndRemoveAds(iframe) {
    try {
        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        if (iframeDoc) {
            // Kontinuierliche Überwachung für neue Werbung
            var newAds = iframeDoc.querySelectorAll(
                'div[class*="taboola"]:not([data-removed]), ' +
                '.adsbygoogle:not([data-removed]), ' +
                '.advertisement:not([data-removed])'
            );
            
            if (newAds.length > 0) {
                console.log('🎯 ' + newAds.length + ' neue Werbung-Elemente erkannt');
                for (var i = 0; i < newAds.length; i++) {
                    newAds[i].style.display = 'none';
                    newAds[i].setAttribute('data-removed', 'true');
                }
            }
        }
    } catch (e) {
        // CORS blockiert - ignoriere
    }
}

// Subtitle.to Modal Funktionen
function openSubtitleToModal(youtubeUrl) {
    console.log('🚀 openSubtitleToModal aufgerufen mit URL:', youtubeUrl);
    
    // Prüfe Bootstrap-Verfügbarkeit
    if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
        console.error('❌ Bootstrap Modal nicht verfügbar!');
        alert('Bootstrap Modal ist nicht verfügbar. Öffne subtitle.to in neuem Tab.');
        window.open(`https://www.subtitle.to/${youtubeUrl}`, '_blank');
        return;
    }
    
    // Prüfe Modal-Element
    const modalElement = document.getElementById('subtitleToModal');
    if (!modalElement) {
        console.error('❌ Modal-Element #subtitleToModal nicht gefunden!');
        alert('Modal-Element nicht gefunden. Öffne subtitle.to in neuem Tab.');
        window.open(`https://www.subtitle.to/${youtubeUrl}`, '_blank');
        return;
    }
    
    const frameContainer = document.getElementById('subtitleToFrame');
    const externalBtn = document.getElementById('openSubtitleToExternal');
    
    if (!frameContainer) {
        console.error('❌ Frame-Container nicht gefunden!');
        return;
    }
    
    console.log('✅ Alle Modal-Elemente gefunden');
    
    // Erstelle subtitle.to URL mit Ad-Blocking Parametern (Edge-kompatibel)
    var subtitleToUrl = 'https://www.subtitle.to/' + youtubeUrl;
    
    // NUCLEAR OPTION: Füge Ad-Blocking Parameter hinzu
    subtitleToUrl += (subtitleToUrl.includes('?') ? '&' : '?') + 
                     'adblock=1&no_ads=1&block_ads=true&disable_ads=1';
    
    console.log('📝 Erstelle URL mit Ad-Blocking:', subtitleToUrl);
    
    // Reset Frame Container (Edge-kompatibel)
    frameContainer.innerHTML = 
        '<div class="text-center p-5">' +
            '<div class="spinner-border text-primary" role="status">' +
                '<span class="visually-hidden">Lade subtitle.to...</span>' +
            '</div>' +
            '<p class="mt-3">Lade subtitle.to Seite...</p>' +
            '<p class="text-muted">URL: ' + subtitleToUrl + '</p>' +
        '</div>';
    
    // External Button Handler
    if (externalBtn) {
        externalBtn.onclick = function() {
            console.log('🔗 Öffne in neuem Tab:', subtitleToUrl);
            window.open(subtitleToUrl, '_blank');
        };
    }
    
    try {
        // Erstelle Modal-Instanz
        const modal = new bootstrap.Modal(modalElement);
        console.log('✅ Modal-Instanz erstellt');
        
        // Zeige Modal
        modal.show();
        console.log('✅ Modal wird angezeigt');
        
        // Versuche iframe zu laden, Fallback zu Button
        setTimeout(() => {
            console.log('🔄 Lade iframe...');
            
            // Erstelle iframe
            const iframe = document.createElement('iframe');
            iframe.src = subtitleToUrl;
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.border = 'none';
            
            iframe.sandbox = 'allow-same-origin allow-scripts allow-forms allow-downloads allow-top-navigation';
            
            // Kombinierter Event-Listener
            iframe.onload = function() {
                console.log('✅ iframe erfolgreich geladen!');
                try {
                    console.log('💥 Starte AGGRESSIVE Werbung-Entfernung...');
                    removeAdsAggressively(iframe);
                } catch (e) {
                    console.log('ℹ️ Werbung-Entfernung nicht möglich (CORS):', e.message);
                }
            };
            
            iframe.onerror = function() {
                console.log('❌ iframe Fehler - zeige Fallback');
                showFallbackButton();
            };
            
            // iframe hinzufügen
            frameContainer.innerHTML = '';
            frameContainer.appendChild(iframe);
            
            console.log('✅ iframe erstellt und hinzugefügt');
            
            // Nach 3 Sekunden prüfen ob iframe blockiert ist
            setTimeout(() => {
                try {
                    if (!iframe.contentDocument && !iframe.contentWindow) {
                        console.log('⚠️ iframe blockiert - zeige Fallback');
                        showFallbackButton();
                    }
                } catch (e) {
                    console.log('⚠️ iframe Cross-Origin - zeige Fallback');
                    showFallbackButton();
                }
            }, 3000);
            
            function showFallbackButton() {
                frameContainer.innerHTML = 
                    '<div class="alert alert-info m-3">' +
                        '<h6>🔗 subtitle.to öffnen</h6>' +
                        '<p>iframe wird blockiert - öffnen Sie subtitle.to in einem neuen Tab:</p>' +
                        '<div class="text-center">' +
                            '<a href="' + subtitleToUrl + '" target="_blank" class="btn btn-primary btn-lg">' +
                                '🔗 subtitle.to öffnen' +
                            '</a>' +
                        '</div>' +
                    '</div>';
            }
        }, 500);
        
    } catch (modalError) {
        console.error('❌ Fehler beim Erstellen/Anzeigen des Modals:', modalError);
        alert('Fehler beim Öffnen des Modals. Öffne subtitle.to in neuem Tab.');
        window.open(subtitleToUrl, '_blank');
    }
}

// Subtitle Modal Workflow Funktionen
let subtitleFileContent = null;
let subtitleFileName = null;

function handleSubtitleFileUpload(input) {
    const file = input.files[0];
    
    if (!file) {
        document.getElementById('uploadStatus').style.display = 'none';
        document.getElementById('proceedToGenerate').disabled = true;
        return;
    }
    
    console.log('📤 Datei ausgewählt:', file.name, file.size, 'bytes');
    
    // Validierung
    if (file.size > 10 * 1024 * 1024) { // 10MB
        alert('Datei ist zu groß! Maximum: 10MB');
        input.value = '';
        return;
    }
    
    const validTypes = ['text/plain', 'application/x-subrip', 'text/vtt', 'text/srt'];
    const validExtensions = ['.txt', '.srt', '.vtt', '.sbv'];
    const hasValidExtension = validExtensions.some(ext => file.name.toLowerCase().endsWith(ext));
    
    if (!hasValidExtension) {
        alert('Ungültiger Dateityp! Erlaubt: .txt, .srt, .vtt, .sbv');
        input.value = '';
        return;
    }
    
    // Datei lesen
    const reader = new FileReader();
    reader.onload = function(e) {
        const content = e.target.result;
        
        console.log('📄 Datei gelesen:', content.length, 'Zeichen');
        
        // Content validieren
        if (content.length < 50) {
            alert('Datei ist zu kurz! Mindestens 50 Zeichen erforderlich.');
            input.value = '';
            return;
        }
        
        // Content speichern
        subtitleFileContent = content;
        subtitleFileName = file.name;
        
        // UI aktualisieren
        showFilePreview(file, content);
        document.getElementById('proceedToGenerate').disabled = false;
        
        console.log('✅ Datei erfolgreich verarbeitet');
    };
    
    reader.onerror = function() {
        alert('Fehler beim Lesen der Datei!');
        input.value = '';
    };
    
    reader.readAsText(file, 'UTF-8');
}

function showFilePreview(file, content) {
    // Datei-Info anzeigen
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
    document.getElementById('fileLength').textContent = content.length.toLocaleString() + ' Zeichen';
    
    // Preview-Text (erste 500 Zeichen)
    const preview = content.substring(0, 500);
    const previewElement = document.getElementById('filePreview');
    previewElement.textContent = preview;
    
    if (content.length > 500) {
        previewElement.innerHTML += '<br><br><em>... (' + (content.length - 500) + ' weitere Zeichen)</em>';
    }
    
    // Status anzeigen
    document.getElementById('uploadStatus').style.display = 'block';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Test-Generierung im Modal
$(document).on('click', '#startTestGeneration', function() {
    if (!subtitleFileContent) {
        alert('Bitte laden Sie zuerst eine Untertitel-Datei hoch!');
        $('#upload-tab').tab('show');
        return;
    }
    
    console.log('🚀 Starte Test-Generierung mit Subtitle-Content');
    
    // Progress anzeigen
    const progressDiv = document.getElementById('generationProgress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');
    const startButton = document.getElementById('startTestGeneration');
    
    progressDiv.style.display = 'block';
    startButton.disabled = true;
    
    // Sammle Form-Daten
    const questionCount = document.getElementById('modalQuestionCount').value;
    const answerCount = document.getElementById('modalAnswerCount').value;
    const testTitle = document.getElementById('modalTestTitle').value;
    
    // Progress-Simulation
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        
        progressBar.style.width = progress + '%';
        
        if (progress < 30) {
            progressText.textContent = 'Verarbeite Untertitel...';
        } else if (progress < 60) {
            progressText.textContent = 'Analysiere Inhalt...';
        } else {
            progressText.textContent = 'Generiere Fragen...';
        }
    }, 200);
    
    // FormData für AJAX-Request vorbereiten
    const formData = new FormData();
    
    // Erstelle Blob für File-Upload-Simulation
    const blob = new Blob([subtitleFileContent], { type: 'text/plain' });
    formData.append('source_file', blob, subtitleFileName);
    formData.append('question_count', questionCount);
    formData.append('answer_count', answerCount);
    if (testTitle) {
        formData.append('test_title', testTitle);
    }
    formData.append('debug', '1');
    
    console.log('📤 Sende Test-Generierung Request...');
    
    // AJAX-Request
    $.ajax({
        url: getTeacherUrl('generate_test.php'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 300000, // 5 Minuten
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 100;
                    if (percentComplete < 90) {
                        progressBar.style.width = percentComplete + '%';
                    }
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            clearInterval(progressInterval);
            progressBar.style.width = '100%';
            progressText.textContent = 'Test erfolgreich generiert!';
            
            console.log('✅ Test-Generierung erfolgreich:', response);
            
            setTimeout(() => {
                // Modal schließen
                const modal = bootstrap.Modal.getInstance(document.getElementById('subtitleToModal'));
                modal.hide();
                
                // Erfolg anzeigen
                $('#generationResult').html(`
                    <div class="alert alert-success">
                        <h4>✅ Test erfolgreich generiert!</h4>
                        <p><strong>Datei:</strong> ${subtitleFileName}</p>
                        <p><strong>Fragen:</strong> ${questionCount}</p>
                        <p><strong>Antworten pro Frage:</strong> ${answerCount}</p>
                        ${response}
                    </div>
                `);
                
                // Scroll zu Ergebnis
                document.getElementById('generationResult').scrollIntoView({ behavior: 'smooth' });
                
            }, 1000);
        },
        error: function(xhr, status, error) {
            clearInterval(progressInterval);
            console.error('❌ Test-Generierung fehlgeschlagen:', error);
            
            progressDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6>❌ Fehler bei der Test-Generierung</h6>
                    <p><strong>Status:</strong> ${status}</p>
                    <p><strong>Fehler:</strong> ${error}</p>
                    <p><strong>Response:</strong> ${xhr.responseText}</p>
                </div>
            `;
            
            startButton.disabled = false;
        }
    });
});

// Modal Reset bei Schließen
$(document).on('hidden.bs.modal', '#subtitleToModal', function() {
    console.log('🔄 Modal wird zurückgesetzt...');
    
    // Markiere YouTube-URL als "bearbeitet" für subtitle.to
    markYouTubeUrlAsProcessed();
    
    // Zeige Hinweis für Datei-Upload
    showSubtitleUploadReminder();
    
    // Reset nur die Daten-Variablen (da wir jetzt einfaches Modal haben)
    subtitleFileContent = null;
    subtitleFileName = null;
    
    // Reset nur Elemente die auch existieren (Sicherheitsprüfung)
    const subtitleFileUpload = document.getElementById('subtitleFileUpload');
    if (subtitleFileUpload) {
        subtitleFileUpload.value = '';
    }
    
    const uploadStatus = document.getElementById('uploadStatus');
    if (uploadStatus) {
        uploadStatus.style.display = 'none';
    }
    
    const proceedToGenerate = document.getElementById('proceedToGenerate');
    if (proceedToGenerate) {
        proceedToGenerate.disabled = true;
    }
    
    const generationProgress = document.getElementById('generationProgress');
    if (generationProgress) {
        generationProgress.style.display = 'none';
    }
    
    const startTestGeneration = document.getElementById('startTestGeneration');
    if (startTestGeneration) {
        startTestGeneration.disabled = false;
    }
    
    const modalTestTitle = document.getElementById('modalTestTitle');
    if (modalTestTitle) {
        modalTestTitle.value = '';
    }
    
    console.log('✅ Modal zurückgesetzt');
});

// YouTube-URL als verarbeitet markieren
function markYouTubeUrlAsProcessed() {
    const youtubeInput = document.getElementById('youtube_url');
    const subtitleBtn = document.getElementById('subtitleToBtn');
    
    if (youtubeInput && subtitleBtn) {
        // Input-Feld styling ändern
        youtubeInput.style.backgroundColor = '#d4edda';
        youtubeInput.style.borderColor = '#28a745';
        youtubeInput.style.color = '#155724';
        youtubeInput.readOnly = true;
        
        // Button ändern
        subtitleBtn.innerHTML = '✅ Untertitel verarbeitet';
        subtitleBtn.className = 'btn btn-success';
        subtitleBtn.disabled = false;
        
        // Verstecktes Flag setzen für Form-Submit
        let processedFlag = document.getElementById('youtube_processed_flag');
        if (!processedFlag) {
            processedFlag = document.createElement('input');
            processedFlag.type = 'hidden';
            processedFlag.id = 'youtube_processed_flag';
            processedFlag.name = 'youtube_processed';
            processedFlag.value = '1';
            youtubeInput.parentNode.appendChild(processedFlag);
        }
        
        console.log('✅ YouTube-URL als verarbeitet markiert');
    }
}

// Upload-Hinweis anzeigen
function showSubtitleUploadReminder() {
    // Entferne vorherige Hinweise
    $('.subtitle-upload-reminder').remove();
    
    // Finde das Upload-Formular
    const uploadArea = document.querySelector('#uploadForm, .upload-area, [data-upload-area]');
    if (!uploadArea) {
        console.warn('Upload-Bereich nicht gefunden');
        return;
    }
    
    // Erstelle Hinweis
    const reminder = document.createElement('div');
    reminder.className = 'alert alert-warning subtitle-upload-reminder';
    reminder.innerHTML = `
        <h6><i class="bi bi-exclamation-triangle-fill me-2"></i>Nächster Schritt: Untertitel hochladen</h6>
        <p class="mb-2">
            <strong>Sie haben subtitle.to verwendet.</strong> Laden Sie jetzt die heruntergeladene Untertitel-Datei hier hoch:
        </p>
        <ul class="mb-3">
            <li>📁 <strong>Suchen Sie die .txt oder .srt Datei</strong> in Ihrem Download-Ordner</li>
            <li>📤 <strong>Ziehen Sie die Datei in diesen Bereich</strong> oder klicken Sie "Datei auswählen"</li>
            <li>🚀 <strong>Generieren Sie dann Ihren Test</strong></li>
        </ul>
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                💡 <strong>Tipp:</strong> Die Datei ist normalerweise im Download-Ordner und heißt wie das YouTube-Video.
            </small>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="$(this).closest('.alert').fadeOut()">
                ✕ Ausblenden
            </button>
        </div>
    `;
    
    // Hinweis vor Upload-Bereich einfügen
    uploadArea.parentNode.insertBefore(reminder, uploadArea);
    
    // Auto-Hide nach 15 Sekunden
    setTimeout(() => {
        $(reminder).fadeOut();
    }, 15000);
    
    console.log('✅ Upload-Hinweis angezeigt');
}

// Form Submit Handler
$('#uploadForm').on('submit', function(e) {
    e.preventDefault();
    // console.log('Form submitted');
    
    // Verstecke vorherige Fehlermeldungen
    $('#generationResult').empty();
    
    // Prüfe, ob mindestens eine Quelle angegeben wurde
    const fileInputs = $(this).find('input[name="source_file[]"]');
    const urlInputs = $(this).find('input[name="webpage_url[]"]');
    const youtubeInput = $(this).find('input[name="youtube_url"]');
    
    let hasFile = false;
    fileInputs.each(function() {
        if (this.files && this.files.length > 0) {
            hasFile = true;
        }
    });
    
    let hasUrl = false;
    urlInputs.each(function() {
        const value = $(this).val();
        if (value && value.trim() !== '') {
            hasUrl = true;
        }
    });
    
    const youtubeValue = youtubeInput.val();
    const hasYoutube = youtubeInput.length > 0 && youtubeValue && youtubeValue.trim() !== '';
    
    // Sammle Validierungsfehler
    const errors = [];
    
    if (!hasFile && !hasUrl && !hasYoutube) {
        errors.push('Bitte geben Sie mindestens eine der folgenden Quellen an: Datei, Webseiten-URL oder YouTube-Link.');
    }
    
    // Validiere Webseiten-URLs
    if (hasUrl) {
        urlInputs.each(function() {
            const url = $(this).val();
            if (url && url.trim() !== '' && !isValidUrl(url.trim())) {
                errors.push('Eine der eingegebenen Webseiten-URLs ist ungültig: ' + url);
            }
        });
    }
    
    // Validiere YouTube-URL
    if (hasYoutube) {
        const youtubeUrl = youtubeInput.val();
        // Verwende Original-URL falls Video-Titel geladen wurde
        const actualUrl = youtubeInput.data('original-url') || youtubeUrl;
        if (actualUrl && !isValidYoutubeUrl(actualUrl.trim())) {
            errors.push('Die eingegebene YouTube-URL ist ungültig. Bitte geben Sie einen gültigen YouTube-Video-Link ein.');
        }
    }
    
    // Zeige Fehler an, falls vorhanden
    if (errors.length > 0) {
        $('#generationResult').html(`
            <div class="alert alert-danger">
                <h4>Fehler bei der Testgenerierung</h4>
                <ul>
                    ${errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            </div>
        `);
        return;
    }
    
    const formData = new FormData(this);
    formData.append('debug', '1');
    
    // Verwende Original-URL für YouTube falls Video-Titel geladen wurde
    const youtubeInput = $('#youtube_url');
    if (youtubeInput.data('original-url')) {
        formData.set('youtube_url', youtubeInput.data('original-url'));
    }
    
    // Debug: Log FormData
    /*
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    */
    
    // Progress-Bar und Status-Text initialisieren
    $('.progress').show();
    if (!$('#progressStatus').length) {
        $('.progress').after('<div id="progressStatus" class="text-center text-muted mt-2"></div>');
    }
    $('.progress-bar')
        .css('width', '0%')
        .attr('aria-valuenow', 0)
        .removeClass('bg-success')
        .addClass('progress-bar-striped progress-bar-animated');
    
    // Status-Updates definieren
    const updateProgress = (percent, status) => {
        $('.progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
        $('#progressStatus').text(status);
    };

    // Starte den 30-Sekunden-Timer für den Fortschrittsbalken
    let progress = 0;
    const totalDuration = 30000; // 30 Sekunden
    const updateInterval = 200; // Update alle 200ms
    const stepsTotal = totalDuration / updateInterval;
    const progressPerStep = 95 / stepsTotal; // Gehe bis 95% in 30 Sekunden

    const progressInterval = setInterval(() => {
        progress += progressPerStep;
        
        // Bestimme die Statusmeldung basierend auf dem Fortschritt
        let statusMessage = '';
        
        // Angepasste Statusmeldungen für verschiedene Quellen
        if (progress <= 20) {
            const sources = [];
            if (hasFile) sources.push('Datei');
            if (hasUrl) sources.push('Webseite');
            if (hasYoutube) sources.push('YouTube-Video');
            
            if (sources.length === 1) {
                statusMessage = `${sources[0]} wird verarbeitet...`;
            } else {
                const lastSource = sources.pop();
                statusMessage = `${sources.join(', ')} und ${lastSource} werden verarbeitet...`;
            }
        }
        else if (progress > 20 && progress <= 40) statusMessage = 'Anfrage wird an ChatGPT gesendet...';
        else if (progress > 40 && progress <= 60) statusMessage = 'Warte auf Antwort von ChatGPT...';
        else if (progress > 60 && progress <= 80) statusMessage = 'Antwort wird verarbeitet...';
        else statusMessage = 'Test wird generiert...';
        
        if (progress >= 95) {
            progress = 95; // Bleibe bei 95% bis der Test fertig ist
            clearInterval(progressInterval);
        }
        
        updateProgress(progress, statusMessage);
    }, updateInterval);

    // Speichere das Interval global, um es später stoppen zu können
    window.currentProgressInterval = progressInterval;
    
    $.ajax({
        url: getTeacherUrl('generate_test.php'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Response:', response);
            
            // Stoppe den Fortschrittsbalken-Timer
            if (window.currentProgressInterval) {
                clearInterval(window.currentProgressInterval);
            }
            
            if (response.success) {
                // Zeige 100% an und entferne die Animation
                updateProgress(100, 'Test wurde erfolgreich generiert!');
                $('.progress-bar')
                    .removeClass('progress-bar-striped progress-bar-animated')
                    .addClass('bg-success');
                
                // Warte 1 Sekunde, dann zeige das Ergebnis
                setTimeout(() => {
                    // Progress-Bar und Status ausblenden
                    $('.progress').hide();
                    $('#progressStatus').remove();
                    $('.progress-bar')
                        .css('width', '0%')
                        .attr('aria-valuenow', 0)
                        .removeClass('bg-success');
                    
                    // Zeige Erfolg
                    $('#generationResult').html(`
                        <div class="alert alert-success">
                            <h4>Test wurde erfolgreich generiert!</h4>
                            <p><strong>Titel:</strong> ${response.title}</p>
                            <p><strong>Zugangscode:</strong> ${response.access_code}</p>
                            <p><strong>Anzahl Fragen:</strong> ${response.question_count}</p>
                            <p><strong>Antworttyp:</strong> ${response.answer_type}</p>
                            <div class="mt-3">
                                <button class="btn btn-success" id="showPreviewBtn">Vorschau anzeigen</button>
                                <button class="btn btn-success" id="showQrCodeBtn">
                                    <i class="bi bi-qr-code"></i> QR-Code anzeigen
                                </button>
                                <button class="btn btn-info" id="showDebugBtn">Debug-Informationen anzeigen</button>
                            </div>
                        </div>
                    `);
                    
                    // Speichere die Debug-Informationen
                    if (response.debug) {
                        window.debugInfo = response.debug;
                    }
                    
                    // Speichere die XML-Daten für die Vorschau
                    if (response.preview_data && response.preview_data.xml_content) {
                        window.currentTestXML = response.preview_data.xml_content;
                        window.currentTestId = response.preview_data.xml_path.split('/').pop();
                        
                        // Speichere Test-Daten für den "Test bearbeiten" Button
                        window.currentGeneratedTest = {
                            access_code: response.access_code,
                            title: response.title,
                            test_id: response.test_id || window.currentTestId
                        };
                        console.log('✅ currentGeneratedTest gespeichert:', window.currentGeneratedTest);
                        
                        // Zeige Vorschau direkt an
                        showXMLPreview(response.preview_data.xml_content, 'generator');
                    }
                }, 1000);
            } else {
                let errorMessage = response.error || 'Unbekannter Fehler';
                if (response.details && response.details.message) {
                    errorMessage = response.details.message;
                }
                
                // Spezielle Behandlung für Rate Limit
                if (errorMessage.includes('Rate Limit')) {
                    $('#uploadForm button[type="submit"]').prop('disabled', true);
                    setTimeout(function() {
                        $('#uploadForm button[type="submit"]').prop('disabled', false);
                    }, 20000); // 20 Sekunden Wartezeit
                }
                
                $('#generationResult').html(`
                    <div class="alert alert-danger">
                        <h4>Fehler bei der Testgenerierung</h4>
                        <p>${errorMessage}</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            $('.progress').hide();
            console.error('Ajax error:', error);
            console.error('Response:', xhr.responseText);
            
            let errorMessage = 'Fehler beim Generieren des Tests';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMessage = response.error;
                }
            } catch (e) {
                errorMessage += ': ' + error;
            }
            
            $('#generationResult').html(`
                <div class="alert alert-danger">
                    <h4>Fehler bei der Testgenerierung</h4>
                    <p>${errorMessage}</p>
                </div>
            `);
        }
    });
});

// Event-Handler für den Vorschau-Button
$(document).on('click', '#showPreviewBtn', function() {
    if (window.currentTestXML) {
        showXMLPreview(window.currentTestXML, 'generator');
    }
});

// Event-Handler für den QR-Code-Button in der Erfolgsmeldung
$(document).on('click', '#generationResult #showQrCodeBtn', function() {
    showQrCode(false, 'generator');
});

// Event-Handler für den Debug-Button
$(document).on('click', '#showDebugBtn', function() {
    if (window.debugInfo) {
        showDebugInfo(window.debugInfo);
    }
});

// Funktion zum Anzeigen der Debug-Informationen
function showDebugInfo(debugInfo) {
    // Modal-Inhalt erstellen
    let modalContent = `
        <div class="modal fade" id="debugModal" tabindex="-1" aria-labelledby="debugModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="debugModalLabel">Debug-Informationen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h4>Prompt an ChatGPT</h4>
                        <div class="card mb-3">
                            <div class="card-header">System-Nachricht</div>
                            <div class="card-body">
                                <pre>${escapeHTML(debugInfo.prompt.system_message)}</pre>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">Benutzer-Nachricht</div>
                            <div class="card-body">
                                <pre>${escapeHTML(debugInfo.prompt.user_message)}</pre>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header">Konfiguration</div>
                            <div class="card-body">
                                <p><strong>Modell:</strong> ${debugInfo.prompt.model}</p>
                                <p><strong>Temperatur:</strong> ${debugInfo.prompt.temperature}</p>
                                <p><strong>Max Tokens:</strong> ${debugInfo.prompt.max_tokens}</p>
                            </div>
                        </div>
                        <h4>Antwort von ChatGPT</h4>
                        <div class="card">
                            <div class="card-body">
                                <pre>${escapeHTML(debugInfo.response)}</pre>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Füge das Modal zum DOM hinzu
    $('body').append(modalContent);
    
    // Zeige das Modal an
    const debugModal = new bootstrap.Modal(document.getElementById('debugModal'));
    debugModal.show();
    
    // Event-Handler zum Entfernen des Modals aus dem DOM, wenn es geschlossen wird
    $('#debugModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

// Funktion zum Anzeigen der XML-Vorschau
function showXMLPreview(xmlContent, modalType = 'generator') {
    try {
        // Versuche, das XML zu parsen
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlContent, "text/xml");
        
        // Prüfe auf Parsing-Fehler
        const parseError = xmlDoc.getElementsByTagName("parsererror");
        if (parseError.length > 0) {
            console.error("XML Parsing Error:", parseError[0].textContent);
            // Zeige Rohtext bei Parsing-Fehlern
            $('.modal-title').text('Test Vorschau (Rohformat)');
            $('.test-content').html(`<pre style="padding: 10px;">${escapeHTML(xmlContent)}</pre>`);
        } else {
            // Extrahiere Informationen aus dem XML
            const title = xmlDoc.getElementsByTagName("title")[0]?.textContent || "Unbenannter Test";
            const accessCode = xmlDoc.getElementsByTagName("access_code")[0]?.textContent || "Kein Code";
            const questionCount = xmlDoc.getElementsByTagName("question_count")[0]?.textContent || "0";
            const answerCount = xmlDoc.getElementsByTagName("answer_count")[0]?.textContent || "0";
            const answerType = xmlDoc.getElementsByTagName("answer_type")[0]?.textContent || "unbekannt";
            
            // Setze currentTestId für den Edit-Button
            window.currentTestId = accessCode;
            
            // Übersetze den Antworttyp in einen benutzerfreundlichen Text
            let answerTypeText = "Unbekannt";
            switch(answerType) {
                case 'single':
                    answerTypeText = "Immer nur eine richtige Antwort";
                    break;
                case 'multiple':
                    answerTypeText = "Mehrere richtige Antworten möglich";
                    break;
                case 'mixed':
                    answerTypeText = "Gemischt (einzelne und mehrere)";
                    break;
            }
            
            // Erstelle HTML für die Vorschau mit minimalem Styling
            // Verwende eindeutige Klassennamen mit 'preview-' Präfix
            let html = `
                <style>
                    .preview-test-container {
                        font-family: Arial, sans-serif;
                        font-size: 14px;
                    }
                    .preview-test-header {
                        padding: 8px;
                        border-bottom: 1px solid #ddd;
                    }
                    .preview-test-title {
                        font-size: 16px;
                        font-weight: bold;
                        margin: 0 0 5px 0;
                    }
                    .preview-test-info {
                        margin: 0 0 5px 0;
                    }
                    .preview-test-meta {
                        font-size: 12px;
                        color: #666;
                    }
                    .preview-question {
                        margin: 5px 0;
                        padding: 5px;
                        background-color: #f9f9f9;
                    }
                    .preview-question-header {
                        display: flex;
                        align-items: center;
                    }
                    .preview-question-number {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 20px;
                        height: 20px;
                        background-color: #666;
                        color: white;
                        border-radius: 50%;
                        font-size: 11px;
                        margin-right: 8px;
                        flex-shrink: 0;
                    }
                    .preview-question-text {
                        margin: 0;
                        font-weight: normal;
                    }
                    .preview-answers {
                        margin-top: 5px;
                        margin-left: 28px;
                    }
                    .preview-answer {
                        display: flex;
                        align-items: center;
                        margin-bottom: 3px;
                    }
                    .preview-answer-letter {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 16px;
                        height: 16px;
                        border-radius: 50%;
                        font-size: 10px;
                        margin-right: 6px;
                        flex-shrink: 0;
                    }
                    .preview-answer-text {
                        margin: 0;
                    }
                    .preview-correct {
                        background-color: #f0f9f0;
                    }
                    .preview-correct .preview-answer-letter {
                        background-color: #28a745;
                        color: white;
                    }
                    .preview-correct .preview-answer-text {
                        font-weight: bold;
                    }
                    .preview-correct-badge {
                        font-size: 10px;
                        background-color: #28a745;
                        color: white;
                        padding: 1px 4px;
                        border-radius: 3px;
                        margin-left: 5px;
                    }
                </style>
                <div class="preview-test-container">
                    <div class="preview-test-header">
                        <div class="preview-test-title">${title}</div>
                        <div class="preview-test-info">Zugangscode: ${accessCode}</div>
                        <div class="preview-test-meta">
                            Fragen: ${questionCount} | Antworten/Frage: ${answerCount} | Typ: ${answerTypeText}
                        </div>
                    </div>
            `;
            
            // Extrahiere und formatiere Fragen
            const questions = xmlDoc.getElementsByTagName("question");
            for (let i = 0; i < questions.length; i++) {
                const question = questions[i];
                const questionText = question.getElementsByTagName("text")[0]?.textContent || "Keine Frage";
                const answers = question.getElementsByTagName("answer");
                
                html += `
                    <div class="preview-question">
                        <div class="preview-question-header">
                            <div class="preview-question-number">${i+1}</div>
                            <div class="preview-question-text">${questionText}</div>
                        </div>
                        <div class="preview-answers">
                `;
                
                // Extrahiere und formatiere Antworten
                for (let j = 0; j < answers.length; j++) {
                    const answer = answers[j];
                    const answerText = answer.getElementsByTagName("text")[0]?.textContent || "Keine Antwort";
                    const isCorrect = answer.getElementsByTagName("correct")[0]?.textContent === "1";
                    
                    const answerLetter = String.fromCharCode(65 + j); // A, B, C, D, ...
                    
                    html += `
                        <div class="preview-answer ${isCorrect ? 'preview-correct' : ''}">
                            <div class="preview-answer-letter" style="background-color: ${isCorrect ? '#28a745' : '#e9ecef'}; color: ${isCorrect ? 'white' : '#333'};">${answerLetter}</div>
                            <div class="preview-answer-text">${answerText} ${isCorrect ? '<span class="preview-correct-badge">Richtig</span>' : ''}</div>
                        </div>
                    `;
                }
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            html += `</div>`;
            
            // Zeige formatierte Vorschau
            $('.modal-title').text('Test Vorschau');
            $('.test-content').html(html);
        }
        
        // Zeige das entsprechende Modal an
        if (modalType === 'generator' && testGeneratorPreviewModal) {
            // Entferne den Speichern-Button aus der Vorschau im Generator-Modus
            $('.modal-footer #saveTest').remove();
            
            // Setze Access-Code für "Test bearbeiten" Button
            let accessCodeToSet = '';
            
            // Versuche verschiedene Quellen für den Access-Code
            if (window.currentGeneratedTest && window.currentGeneratedTest.access_code) {
                accessCodeToSet = window.currentGeneratedTest.access_code;
                console.log('✅ Access-Code aus currentGeneratedTest:', accessCodeToSet);
            } else if (window.currentTestXML) {
                // Extrahiere Access-Code aus XML
                try {
                    const parser = new DOMParser();
                    const xmlDoc = parser.parseFromString(window.currentTestXML, "text/xml");
                    accessCodeToSet = xmlDoc.getElementsByTagName("access_code")[0]?.textContent || '';
                    console.log('✅ Access-Code aus XML extrahiert:', accessCodeToSet);
                } catch (error) {
                    console.error('❌ Fehler beim Extrahieren des Access-Codes aus XML:', error);
                }
            }
            
            if (accessCodeToSet) {
                $('#editGeneratedTest').attr('data-access-code', accessCodeToSet);
                console.log('✅ Access-Code für editGeneratedTest gesetzt:', accessCodeToSet);
            } else {
                console.error('❌ Kein Access-Code für editGeneratedTest verfügbar!');
            }
            
            testGeneratorPreviewModal.show();
        } else if (modalType === 'editor' && testEditorPreviewModal) {
            testEditorPreviewModal.show();
        } else {
            console.error(`Modal vom Typ '${modalType}' nicht gefunden!`);
        }
    } catch (e) {
        console.error("Error displaying XML preview:", e);
        // Fallback zur Rohtext-Anzeige
        $('.modal-title').text('Test Vorschau (Rohformat)');
        $('.test-content').html(`<pre style="padding: 10px;">${escapeHTML(xmlContent)}</pre>`);
        
        // Zeige das entsprechende Modal an
        if (modalType === 'generator' && testGeneratorPreviewModal) {
            testGeneratorPreviewModal.show();
        } else if (modalType === 'editor' && testEditorPreviewModal) {
            testEditorPreviewModal.show();
        } else {
            console.error(`Modal vom Typ '${modalType}' nicht gefunden!`);
        }
    }
}

// Hilfsfunktion zum Escapen von HTML
function escapeHTML(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Event-Handler für den Speichern-Button im Modal
$(document).on('click', '#saveTest', function() {
    if (window.currentTestId) {
        // Zeige Erfolgsmeldung
        alert('Test wurde erfolgreich gespeichert!');
        
        // Optional: Weiterleitung zur Testliste
        // window.location.href = 'teacher_dashboard.php';
    }
});

// Event-Handler für den Bearbeiten-Button im Modal
$(document).on('click', '#editTest', function() {
    console.log('Edit-Button wurde geklickt');
    
    // Hole den Zugangscode aus dem XML-Inhalt
    let accessCode = '';
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(window.currentTestXML, "text/xml");
        accessCode = xmlDoc.getElementsByTagName("access_code")[0]?.textContent;
        console.log('Zugangscode aus XML:', accessCode);
    } catch (error) {
        console.error('Fehler beim Extrahieren des Zugangscodes:', error);
    }
    
    if (accessCode) {
        console.log('Leite weiter zum Editor mit Code:', accessCode);
        
        // Konstruiere die URL für den Editor mit dem Tab-Parameter
        const editorUrl = 'teacher_dashboard.php?tab=editor&test=' + encodeURIComponent(accessCode);
        
        // Navigiere zum Editor
        window.location.href = editorUrl;
    } else {
        console.error('Kein Zugangscode verfügbar');
    }
});

// Flag um mehrfache Initialisierung zu verhindern
let testEditorInitialized = false;

function initTestEditor() {
    if (testEditorInitialized) {
        console.log('🔧 initTestEditor bereits initialisiert, überspringe');
        return;
    }
    testEditorInitialized = true;
    console.log('🔧 initTestEditor wird initialisiert');
    
    // Generiere zufälligen Zugangscode beim Laden
    generateRandomAccessCode();
    
    // Prüfe, ob ein Test in der URL angegeben ist
    const urlParams = new URLSearchParams(window.location.search);
    const testParam = urlParams.get('test');
    
    if (testParam) {
        console.log('Test aus URL laden:', testParam);
        let testFound = false;
        
        // Suche die Option mit dem entsprechenden Zugangscode
        $('#testSelector option').each(function() {
            const optionAccessCode = $(this).data('access-code');
            console.log('Prüfe Option:', {
                value: $(this).val(),
                accessCode: optionAccessCode,
                searchedCode: testParam
            });
            
            if (optionAccessCode === testParam) {
                console.log('Test gefunden:', $(this).val());
                testFound = true;
                // Wähle den Test aus und triggere das Change-Event
                $('#testSelector').val($(this).val()).trigger('change');
                return false; // Schleife beenden
            }
        });
        
        if (!testFound) {
            console.error('Test mit Zugangscode', testParam, 'nicht gefunden');
        }
    }
    
    // Dupliziere die Buttons am Anfang des Formulars
    const buttonContainer = $('.button-container').first().clone();
    
    // Ändere IDs der geklonten Buttons um Konflikte zu vermeiden
    buttonContainer.find('#previewTestBtn').attr('id', 'previewTestBtn2');
    buttonContainer.find('#saveTestBtn').attr('id', 'saveTestBtn2');
    buttonContainer.find('#deleteTestBtn').attr('id', 'deleteTestBtn2');
    buttonContainer.find('#showQrCodeBtn').attr('id', 'showQrCodeBtn2');
    
    $('#testEditorForm').prepend(buttonContainer);
    
    // Füge nur den Reset-Button zu beiden Button-Containern hinzu
    $('.button-container').each(function() {
        $(this).append(`
            <button type="button" class="reset-btn btn btn-secondary ms-2" title="Test-Editor zurücksetzen">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
        `);
    });
    
    // Verstecke die Buttons am Anfang, wenn kein Test geladen ist
    updateButtonVisibility();
    
    // Event-Handler für QR-Code-Button wird unten global definiert
    
    // Event-Handler für Reset-Button wird unten global definiert
    /*
    $('.button-container').on('click', '#resetBtn', function() {
        if (testHasChanges) {
            // Entferne ein möglicherweise vorhandenes altes Modal
            $('#resetConfirmModal').remove();

            // Erstelle das Modal für die Reset-Bestätigung
            const modalContent = `
                <div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetConfirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title" id="resetConfirmModalLabel">Test-Editor zurücksetzen?</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Achtung:</strong> Alle nicht gespeicherten Änderungen gehen verloren.
                                </div>
                                <p>Möchten Sie wirklich fortfahren?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                <button type="button" class="btn btn-warning" id="confirmReset">Zurücksetzen</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Füge das Modal zum DOM hinzu
            $('body').append(modalContent);

            // Zeige das Modal an
            const resetModal = new bootstrap.Modal(document.getElementById('resetConfirmModal'));
            resetModal.show();

            // Event-Handler für den Bestätigen-Button
            $('#confirmReset').on('click', function() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal'));
                modal.hide();
                resetTestEditor();
            });

            // Event-Handler zum Entfernen des Modals
            $('#resetConfirmModal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        } else {
            resetTestEditor();
        }
    });
    */
    
    // Event-Handler für Test-Auswahl
    $('#testSelector').on('change', function(e, skipQrCode) {
        const selectedValue = $(this).val();
        
        if (selectedValue === '') {
            // Neuer Test wurde ausgewählt
            $('#testTitle').val('');
            $('#accessCode').val('');
            generateRandomAccessCode();
            
            // Leere den Fragen-Container
            $('#questionsContainer').empty();
            
            // Setze Änderungsstatus zurück
            testHasChanges = false;
            currentTestFilename = null;
            
            // Aktualisiere Button-Sichtbarkeit
            updateButtonVisibility();
        } else {
            // Vorhandener Test wurde ausgewählt
            const selectedOption = $(this).find('option:selected');
            const accessCode = selectedOption.data('access-code');
            const title = selectedOption.data('title');
            
            $('#accessCode').val(accessCode);
            $('#testTitle').val(title);
            
            // Speichere den Dateinamen für Löschoperationen
            currentTestFilename = selectedValue + '.xml';
            
            // Setze Änderungsstatus zurück
            testHasChanges = false;
            
            // Lade den Testinhalt
            loadTestContent(selectedValue);
            
            // Aktualisiere Button-Sichtbarkeit
            updateButtonVisibility();
        }
    });
    
    // Event-Handler für Frage hinzufügen
    $('#addQuestionBtn').on('click', function() {
        addQuestion();
        markAsChanged();
    });
    
    // Event-Handler für Titel- und Access-Code-Änderungen
    $('#testTitle, #accessCode').on('input', function() {
        markAsChanged();
    });
    
    // Event-Handler für Speichern-Button (beide)
    $(document).on('click', '#saveTestBtn, #saveTestBtn2', function() {
        saveTest();
    });
    
    // Event-Handler für Vorschau-Button (beide)
    $(document).on('click', '#previewTestBtn, #previewTestBtn2', function() {
        previewTest();
    });
    
    // Event-Handler für Löschen-Button (beide)
    $(document).on('click', '#deleteTestBtn, #deleteTestBtn2', function() {
        deleteTest();
    });
    
    // Event-Handler für QR-Code-Button (beide) - mit Debounce
    $(document).off('click', '#showQrCodeBtn, #showQrCodeBtn2').on('click', '#showQrCodeBtn, #showQrCodeBtn2', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Verhindere mehrfache schnelle Klicks
        if ($(this).data('processing')) {
            console.log('QR-Button bereits in Verarbeitung, ignoriere Klick');
            return;
        }
        
        $(this).data('processing', true);
        
        setTimeout(() => {
            $(this).data('processing', false);
        }, 500);
        
        showQrCode(false, 'editor');
    });
    
    // Event-Handler für Reset-Button (beide)
    $(document).on('click', '.reset-btn', function() {
        if (confirm('Möchten Sie den Test-Editor wirklich zurücksetzen? Alle ungespeicherten Änderungen gehen verloren.')) {
            resetTestEditor();
        }
    });
    
    // Event-Handler für Frage entfernen (delegiert)
    $('#questionsContainer').on('click', '.remove-question', function() {
        $(this).closest('.question-card').remove();
        updateQuestionNumbers();
        markAsChanged();
    });
    
    // Event-Handler für Änderungen an Frage-Inhalten (delegiert)
    $('#questionsContainer').on('input', 'input[type="text"], textarea', function() {
        markAsChanged();
    });
    
    // Event-Handler für Checkbox-Änderungen (delegiert)
    $('#questionsContainer').on('change', 'input[type="checkbox"]', function() {
        markAsChanged();
    });
    
    // Event-Handler für Antwort hinzufügen (delegiert)
    $('#questionsContainer').on('click', '.add-answer', function() {
        const answersContainer = $(this).closest('.card-body').find('.answers-container');
        addAnswer(answersContainer);
        markAsChanged();
    });
    
    // Event-Handler für Antwort entfernen (delegiert)
    $('#questionsContainer').on('click', '.remove-answer', function() {
        $(this).closest('.answer-item').remove();
        markAsChanged();
    });
    
    // Event-Handler für Änderungen an Eingabefeldern
    $('#testEditorForm').on('input', 'input, textarea, select', function() {
        markAsChanged();
    });
    
    // Event-Handler für Änderungen an Checkboxen
    $('#testEditorForm').on('change', 'input[type="checkbox"]', function() {
        markAsChanged();
    });
}

function generateRandomAccessCode() {
    // Generiere einen zufälligen 3-stelligen alphanumerischen Code
    const chars = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
    let code = '';
    for (let i = 0; i < 3; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $('#accessCode').val(code);
}

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
}

function loadTestContent(testName) {
    // Zeige Ladeindikator
    $('#questionsContainer').html('<div class="alert alert-info">Lade Testinhalt...</div>');
    
    console.log('Lade Test:', testName);
    
    // Lade den Testinhalt via AJAX
    $.ajax({
        url: getTeacherUrl('load_test.php'),
        type: 'GET',
        data: { test_name: testName },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Leere den Fragen-Container
                $('#questionsContainer').empty();
                
                // Verarbeite die XML-Daten
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(response.xml_content, "text/xml");
                
                // Extrahiere Titel und Zugangscode
                const title = xmlDoc.getElementsByTagName("title")[0].textContent;
                const accessCode = xmlDoc.getElementsByTagName("access_code")[0].textContent;
                
                // Setze Titel und Zugangscode in die Eingabefelder
                $('#testTitle').val(title);
                $('#accessCode').val(accessCode);
                
                // Setze den aktuellen Dateinamen
                window.currentTestFilename = testName;
                console.log("Test geladen - currentTestFilename gesetzt auf:", testName);
                
                // Extrahiere Fragen und Antworten
                const questions = xmlDoc.getElementsByTagName("question");
                
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
                
                // Setze Änderungsstatus zurück
                testHasChanges = false;
                updateButtonVisibility();
            } else {
                alert('Fehler beim Laden des Tests: ' + response.error);
                $('#questionsContainer').empty();
            }
        },
        error: function(xhr, status, error) {
            console.error('Fehler beim Laden des Tests:', error);
            console.error('Response:', xhr.responseText);
            alert('Fehler beim Laden des Tests: ' + error);
            $('#questionsContainer').empty();
        }
    });
}

// Funktion zum Anzeigen von Erfolgsmeldungen
function showSuccessMessage(message) {
    // Entferne vorhandene Erfolgsmeldungen
    $('.success-message').remove();
    
    // Erstelle die Erfolgsmeldung
    const successMessage = $(`
        <div class="success-message alert alert-success" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <i class="bi bi-check-circle-fill me-2"></i> ${message}
        </div>
    `);
    
    // Füge die Meldung zum DOM hinzu
    $('body').append(successMessage);
    
    // Blende die Meldung nach 3 Sekunden aus
    setTimeout(function() {
        successMessage.fadeOut(1000, function() {
            $(this).remove();
        });
    }, 3000);
}

function saveTest(overwrite = false) {
    // Wenn wir nicht überschreiben und die Überschreiben-Flag gesetzt ist, beende die Funktion
    if (!overwrite && window.isOverwriting) {
        window.isOverwriting = false;
        return;
    }
    
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
    
    // Prüfe, ob der Zugangscode bereits existiert
    let existingTest = null;
    const currentTestCode = currentTestFilename ? currentTestFilename.split('_')[0] : null;
    
    if (!overwrite) {
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
            // Aktualisiere und zeige das Überschreiben-Modal
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
    let answerType = 'single'; // Standard: single
    let hasValidQuestions = false;
    
    $('#questionsContainer .question-card').each(function() {
        const questionText = $(this).find('.question-text').val().trim();
        if (!questionText) {
            return true; // Skip this iteration (continue)
        }
        
        const answers = [];
        let correctCount = 0;
        let hasValidAnswers = true;
        
        $(this).find('.answer-item').each(function() {
            const answerText = $(this).find('.answer-text').val().trim();
            const isCorrect = $(this).find('.answer-correct').is(':checked');
            
            if (!answerText) {
                hasValidAnswers = false;
                return false; // Break the loop
            }
            
            answers.push({
                text: answerText,
                correct: isCorrect
            });
            
            if (isCorrect) {
                correctCount++;
            }
        });
        
        // Prüfe, ob mindestens eine Antwort korrekt ist und alle Antworten gültig sind
        if (correctCount === 0 || !hasValidAnswers) {
            return true; // Skip this iteration (continue)
        }
        
        // Bestimme den Antworttyp
        if (correctCount > 1) {
            answerType = 'multiple';
        }
        
        questions.push({
            text: questionText,
            answers: answers
        });
        
        hasValidQuestions = true;
    });
    
    // Prüfe, ob gültige Fragen gesammelt wurden
    if (!hasValidQuestions || questions.length === 0) {
        alert('Bitte stellen Sie sicher, dass mindestens eine Frage vollständig ausgefüllt ist (mit Text und mindestens einer richtigen Antwort).');
        return;
    }
    
    // Erstelle einen sicheren Dateinamen
    const safeTitle = title.toLowerCase().replace(/[^a-z0-9]/g, '-');
    const filename = accessCode + '_' + safeTitle;
    
    // Erstelle XML-Struktur
    const xmlDoc = document.implementation.createDocument(null, "test", null);
    const rootElement = xmlDoc.documentElement;
    
    // Füge Metadaten hinzu
    const titleElement = xmlDoc.createElement("title");
    titleElement.textContent = title;
    rootElement.appendChild(titleElement);
    
    const accessCodeElement = xmlDoc.createElement("access_code");
    accessCodeElement.textContent = accessCode;
    rootElement.appendChild(accessCodeElement);
    
    const questionCountElement = xmlDoc.createElement("question_count");
    questionCountElement.textContent = questions.length;
    rootElement.appendChild(questionCountElement);
    
    const answerCountElement = xmlDoc.createElement("answer_count");
    answerCountElement.textContent = questions[0].answers.length;
    rootElement.appendChild(answerCountElement);
    
    const answerTypeElement = xmlDoc.createElement("answer_type");
    answerTypeElement.textContent = answerType;
    rootElement.appendChild(answerTypeElement);
    
    // Füge Fragen hinzu
    const questionsElement = xmlDoc.createElement("questions");
    rootElement.appendChild(questionsElement);
    
    questions.forEach((question, index) => {
        const questionElement = xmlDoc.createElement("question");
        questionElement.setAttribute("nr", index + 1);
        
        const questionTextElement = xmlDoc.createElement("text");
        questionTextElement.textContent = question.text;
        questionElement.appendChild(questionTextElement);
        
        const answersElement = xmlDoc.createElement("answers");
        questionElement.appendChild(answersElement);
        
        question.answers.forEach((answer, answerIndex) => {
            const answerElement = xmlDoc.createElement("answer");
            answerElement.setAttribute("nr", String.fromCharCode(65 + answerIndex)); // A, B, C, D, ...
            
            const answerTextElement = xmlDoc.createElement("text");
            answerTextElement.textContent = answer.text;
            answerElement.appendChild(answerTextElement);
            
            const correctElement = xmlDoc.createElement("correct");
            correctElement.textContent = answer.correct ? "1" : "0";
            answerElement.appendChild(correctElement);
            
            answersElement.appendChild(answerElement);
        });
        
        questionsElement.appendChild(questionElement);
    });
    
    // Konvertiere XML-Dokument in String
    const serializer = new XMLSerializer();
    const xmlString = serializer.serializeToString(xmlDoc);
    
    // Speichere den Test via AJAX
    $.ajax({
        url: getTeacherUrl('save_test_xml.php'),
        type: 'POST',
        data: {
            title: title,
            access_code: accessCode,
            question_count: questions.length,
            answer_count: questions[0].answers.length,
            answer_type: answerType,
            filename: filename,
            xml_content: xmlString
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Zeige schöne Erfolgsmeldung statt Alert
                showSuccessMessage('Test erfolgreich gespeichert!');
                
                // Setze Änderungsstatus zurück
                testHasChanges = false;
                
                // Lade die Testliste neu
                reloadTestList(function() {
                    // Setze den Editor zurück
                    resetTestEditor();
                    
                    // Wähle den gerade gespeicherten Test aus
                    $('#testSelector option').each(function() {
                        const optionCode = $(this).data('access-code');
                        if (optionCode === accessCode) {
                            $(this).prop('selected', true);
                            $(this).trigger('change', [true]); // true verhindert QR-Code-Anzeige
                            
                            // Zeige QR-Code nur an, wenn wir nicht im Überschreiben-Modus sind
                            if (!window.isOverwriting) {
                                setTimeout(function() {
                                    showQrCode(true, 'editor');
                                }, 500);
                            }
                            window.isOverwriting = false;
                            return false;
                        }
                    });
                });
            } else {
                alert('Fehler beim Speichern des Tests: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            alert('Fehler beim Speichern des Tests: ' + error);
        }
    });
}

// Funktion zum Neuladen der Testliste
function reloadTestList(callback) {
    $.ajax({
        url: getTeacherUrl('load_test_list.php'),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Leere die aktuelle Liste
                const testSelector = $('#testSelector');
                testSelector.find('option:not(:first)').remove();
                
                // Füge die neuen Tests hinzu
                response.tests.forEach(function(test) {
                    testSelector.append(`
                        <option value="${test.name}" 
                                data-access-code="${test.accessCode}"
                                data-title="${test.title}">
                            ${test.accessCode} - ${test.title}
                        </option>
                    `);
                });
                
                // Rufe den Callback auf, wenn vorhanden
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                console.error('Fehler beim Laden der Testliste:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Fehler beim Laden der Testliste:', error);
        }
    });
}

function previewTest() {
    // Validiere Formular
    const title = $('#testTitle').val().trim();
    const accessCode = $('#accessCode').val().trim();
    
    // Sammle alle Fehler
    const errors = [];
    
    if (!title) {
        errors.push('Der Titel des Tests fehlt.');
    }
    
    if (!accessCode) {
        errors.push('Der Zugangscode fehlt.');
    } else if (accessCode.length !== 3) {
        errors.push('Der Zugangscode muss genau 3 Zeichen lang sein.');
    }
    
    // Prüfe, ob Fragen vorhanden sind
    if ($('#questionsContainer .question-card').length === 0) {
        errors.push('Es wurden keine Fragen hinzugefügt.');
        
        // Zeige Fehlermeldung und breche ab, wenn Fehler gefunden wurden
        if (errors.length > 0) {
            alert('Folgende Fehler müssen behoben werden:\n\n' + errors.join('\n'));
            return;
        }
        
        return;
    }
    
    // Sammle Daten aus dem Formular - ohne DOM-Manipulation
    const questions = [];
    let answerType = 'single'; // Standard: single
    let hasValidQuestions = false;
    const invalidQuestions = [];
    
    // Erstelle eine Kopie der Fragen, um die originalen nicht zu beeinflussen
    $('#questionsContainer .question-card').each(function(questionIndex) {
        const questionNumber = questionIndex + 1;
        const questionText = $(this).find('.question-text').val().trim();
        
        if (!questionText) {
            invalidQuestions.push('Frage ' + questionNumber + ': Der Fragetext fehlt.');
            return true; // Skip this iteration (continue)
        }
        
        const answers = [];
        let correctCount = 0;
        let hasValidAnswers = true;
        const invalidAnswers = [];
        
        $(this).find('.answer-item').each(function(answerIndex) {
            const answerNumber = answerIndex + 1;
            const answerText = $(this).find('.answer-text').val().trim();
            const isCorrect = $(this).find('.answer-correct').is(':checked');
            
            if (!answerText) {
                invalidAnswers.push('Frage ' + questionNumber + ', Antwort ' + answerNumber + ': Der Antworttext fehlt.');
                hasValidAnswers = false;
            }
            
            answers.push({
                text: answerText,
                correct: isCorrect
            });
            
            if (isCorrect) {
                correctCount++;
            }
        });
        
        // Prüfe, ob mindestens eine Antwort korrekt ist
        if (correctCount === 0) {
            invalidQuestions.push('Frage ' + questionNumber + ': Keine Antwort ist als richtig markiert.');
        }
        
        // Prüfe, ob alle Antworten gültig sind
        if (!hasValidAnswers) {
            invalidQuestions.push.apply(invalidQuestions, invalidAnswers);
        }
        
        // Prüfe, ob mindestens eine Antwort korrekt ist und alle Antworten gültig sind
        if (correctCount === 0 || !hasValidAnswers) {
            return true; // Skip this iteration (continue)
        }
        
        // Bestimme den Antworttyp
        if (correctCount > 1) {
            answerType = 'multiple';
        }
        
        questions.push({
            text: questionText,
            answers: answers,
            index: questionIndex // Speichere den originalen Index
        });
        
        hasValidQuestions = true;
    });
    
    // Füge Fragenfehler zu den Gesamtfehlern hinzu
    if (invalidQuestions.length > 0) {
        errors.push.apply(errors, invalidQuestions);
    }
    
    // Prüfe, ob gültige Fragen gesammelt wurden
    if (!hasValidQuestions || questions.length === 0) {
        errors.push('Es gibt keine vollständig ausgefüllte Frage mit mindestens einer richtigen Antwort.');
    }
    
    // Zeige Fehlermeldung und breche ab, wenn Fehler gefunden wurden
    if (errors.length > 0) {
        alert('Folgende Fehler müssen behoben werden:\n\n' + errors.join('\n'));
        return;
    }
    
    // Erstelle XML-Struktur
    const xmlDoc = document.implementation.createDocument(null, "test", null);
    const rootElement = xmlDoc.documentElement;
    
    // Füge Metadaten hinzu
    const titleElement = xmlDoc.createElement("title");
    titleElement.textContent = title;
    rootElement.appendChild(titleElement);
    
    const accessCodeElement = xmlDoc.createElement("access_code");
    accessCodeElement.textContent = accessCode;
    rootElement.appendChild(accessCodeElement);
    
    const questionCountElement = xmlDoc.createElement("question_count");
    questionCountElement.textContent = questions.length;
    rootElement.appendChild(questionCountElement);
    
    const answerCountElement = xmlDoc.createElement("answer_count");
    answerCountElement.textContent = questions[0].answers.length;
    rootElement.appendChild(answerCountElement);
    
    const answerTypeElement = xmlDoc.createElement("answer_type");
    answerTypeElement.textContent = answerType;
    rootElement.appendChild(answerTypeElement);
    
    // Füge Fragen hinzu
    const questionsElement = xmlDoc.createElement("questions");
    rootElement.appendChild(questionsElement);
    
    questions.forEach((question, index) => {
        const questionElement = xmlDoc.createElement("question");
        questionElement.setAttribute("nr", index + 1);
        
        const questionTextElement = xmlDoc.createElement("text");
        questionTextElement.textContent = question.text;
        questionElement.appendChild(questionTextElement);
        
        const answersElement = xmlDoc.createElement("answers");
        questionElement.appendChild(answersElement);
        
        question.answers.forEach((answer, answerIndex) => {
            const answerElement = xmlDoc.createElement("answer");
            answerElement.setAttribute("nr", String.fromCharCode(65 + answerIndex)); // A, B, C, D, ...
            
            const answerTextElement = xmlDoc.createElement("text");
            answerTextElement.textContent = answer.text;
            answerElement.appendChild(answerTextElement);
            
            const correctElement = xmlDoc.createElement("correct");
            correctElement.textContent = answer.correct ? "1" : "0";
            answerElement.appendChild(correctElement);
            
            answersElement.appendChild(answerElement);
        });
        
        questionsElement.appendChild(questionElement);
    });
    
    // Konvertiere XML-Dokument in String
    const serializer = new XMLSerializer();
    const xmlString = serializer.serializeToString(xmlDoc);
    
    // Zeige Vorschau ohne DOM-Manipulation des Editors
    showXMLPreview(xmlString, 'editor');
}

// Funktion zum Markieren des Tests als geändert
function markAsChanged() {
    testHasChanges = true;
    updateButtonVisibility();
}

// Funktion zur Aktualisierung der Button-Sichtbarkeit
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
        accessCodeValue: $('#accessCode').val().trim(),
        isCurrentTestFilename: !!currentTestFilename,
        testHasChanges: testHasChanges,
        enableSaveButton: hasQuestions && testHasChanges,
        enableQrButton: hasTest || currentTestFilename || (hasQuestions && hasAccessCode),
        enableDeleteButton: hasTest || currentTestFilename || (hasQuestions && hasAccessCode)
    });
    
    // Speichern-Button: Nur aktiv wenn Änderungen gemacht wurden UND Fragen vorhanden sind
    const enableSaveButton = hasQuestions && testHasChanges;
    $('#saveTestBtn, #saveTestBtn2').prop('disabled', !enableSaveButton);
    
    // Färbe Speichern-Button orange wenn aktiviert, grau wenn deaktiviert
    if (enableSaveButton) {
        $('#saveTestBtn, #saveTestBtn2').removeClass('btn-secondary').addClass('btn-warning');
    } else {
        $('#saveTestBtn, #saveTestBtn2').removeClass('btn-warning').addClass('btn-secondary');
    }
    
    // QR-Code Button: Immer aktiv wenn ein Test geladen ist (hasTest) ODER wenn neue Fragen mit Access-Code vorhanden sind
    const enableQrButton = hasTest || currentTestFilename || (hasQuestions && hasAccessCode);
    $('#showQrCodeBtn, #showQrCodeBtn2').prop('disabled', !enableQrButton);
    
    // Löschen-Button: Immer aktiv wenn ein Test geladen ist (wie QR-Code Button)
    const enableDeleteButton = hasTest || currentTestFilename || (hasQuestions && hasAccessCode);
    $('#deleteTestBtn, #deleteTestBtn2').prop('disabled', !enableDeleteButton);
    
    // Vorschau-Button ist immer aktiv und grün
    $('#previewTestBtn, #previewTestBtn2').prop('disabled', false);
    $('#previewTestBtn, #previewTestBtn2').removeClass('btn-secondary').addClass('btn-success');
}

function deleteTest() {
    if (currentTestFilename) {
        // Hole den aktuellen Zugangscode und Titel
        const accessCode = $('#accessCode').val().trim();
        const title = $('#testTitle').val().trim();

        // Entferne ein möglicherweise vorhandenes altes Modal
        $('#deleteConfirmModal').remove();

        // Erstelle das Modal für die Löschbestätigung
        const modalContent = `
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteConfirmModalLabel">Test löschen?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Möchten Sie wirklich den folgenden Test löschen?</p>
                            <div class="alert alert-info">
                                <strong>Zugangscode:</strong> ${accessCode}<br>
                                <strong>Titel:</strong> ${title}
                            </div>
                            <p class="text-danger"><strong>Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">Löschen</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Füge das Modal zum DOM hinzu
        $('body').append(modalContent);

        // Zeige das Modal an
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();

        // Event-Handler für den Löschen-Button
        $('#confirmDelete').on('click', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
            modal.hide();

            // Lösche den Test via AJAX
            $.ajax({
                url: getTeacherUrl('delete_test.php'),
                type: 'POST',
                data: { filename: currentTestFilename },
                success: function(response) {
                    if (response.success) {
                        // Zeige schöne Erfolgsmeldung
                        showSuccessMessage('Test erfolgreich gelöscht!');
                        
                        // Lade die Testliste neu
                        reloadTestList(function() {
                            // Setze den Editor zurück
                            resetTestEditor();
                        });
                    } else {
                        alert('Fehler beim Löschen des Tests: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Fehler beim Löschen des Tests: ' + error);
                }
            });
        });

        // Event-Handler zum Entfernen des Modals
        $('#deleteConfirmModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }
}

// Funktion zum Zurücksetzen des Test-Editors
function resetTestEditor() {
    // Wähle den "Neuen Test erstellen" Eintrag
    $('#testSelector').val('').trigger('change');
    
    // Generiere einen neuen Zugangscode
    generateRandomAccessCode();
    
    // Leere den Titel
    $('#testTitle').val('');
    
    // Leere den Fragen-Container
    $('#questionsContainer').empty();
    
    // Setze Änderungsstatus zurück
    testHasChanges = false;
    currentTestFilename = null;
    
    // Aktualisiere Button-Sichtbarkeit
    updateButtonVisibility();
}

// Variable für Debouncing
let qrModalTimeout = null;

// Funktion zum Anzeigen des QR-Codes
function showQrCode(automatisch = false, modalType = 'editor') {
    // Debounce - verhindere mehrfaches schnelles Öffnen
    if (qrModalTimeout) {
        clearTimeout(qrModalTimeout);
    }
    
    // Prüfe ob Modal bereits offen ist
    if ($('#qrCodeModal').hasClass('show')) {
        console.log('QR-Modal bereits offen, ignoriere weiteren Aufruf');
        return;
    }
    
    qrModalTimeout = setTimeout(() => {
        qrModalTimeout = null;
        console.log('🎯 showQrCode() gestartet - modalType:', modalType, 'automatisch:', automatisch);
        
    // Hole den Zugangscode - im Generator-Kontext aus der Response
    let accessCode = '';
    let title = '';
    
    if (modalType === 'generator' && window.currentTestXML) {
        try {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(window.currentTestXML, "text/xml");
            accessCode = xmlDoc.getElementsByTagName("access_code")[0]?.textContent;
            title = xmlDoc.getElementsByTagName("title")[0]?.textContent;
        } catch (error) {
            console.error('Fehler beim Extrahieren des Zugangscodes:', error);
        }
    } else {
        accessCode = $('#accessCode').val().trim();
        title = $('#testTitle').val();
    }
    
    if (!accessCode) {
        alert('Der Zugangscode ist leer. Bitte geben Sie einen gültigen Zugangscode ein.');
        return;
    }
    
    // Entferne ein möglicherweise vorhandenes altes Modal
    $('#qrCodeModal').remove();
    
    // Erstelle die URL für den QR-Code
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/teacher\/.*$|\/[^\/]*$/, '/');
    const testUrl = baseUrl + 'index.php?code=' + accessCode;
    
    // Erstelle das Modal für den QR-Code
    const modalContent = `
        <style>
            #qrCodeModal .modal-dialog {
                max-width: 400px;
            }
            #qrCodeModal .modal-title {
                font-size: 1rem;
            }
            #qrCodeModal .modal-body {
                padding: 1rem;
            }
        </style>
        <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrCodeModalLabel">QR-Code für Test: ${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
            ${automatisch ? '<div class="alert alert-success m-3">Test wurde erfolgreich gespeichert!</div>' : ''}
            <div class="modal-body text-center">
                <div id="qrcode" class="mb-3 d-inline-block"></div>
                <div class="input-group mb-3">
                    <input type="text" id="testUrlInput" class="form-control" value="${testUrl}" readonly>
                    <button class="btn btn-outline-secondary copy-url-btn" type="button" title="URL kopieren">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success copy-qr-btn">
                        <i class="bi bi-clipboard"></i> QR-Code in Zwischenablage kopieren
                    </button>
                    <button type="button" class="btn btn-secondary save-qr-btn">
                        <i class="bi bi-download"></i> QR-Code als Bild speichern
                    </button>
                    <button type="button" class="btn btn-warning seb-qr-btn">
                        <i class="bi bi-shield-lock me-2"></i>SEB-QR-Code anzeigen
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                ${modalType === 'generator' ? `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    <button type="button" class="btn btn-primary" id="editTest" data-access-code="${accessCode}">
                        <i class="bi bi-pencil-square me-2"></i>Test bearbeiten
                    </button>
                ` : `
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Schließen</button>
                `}
            </div>
        </div>
    </div>
</div>
`;
    
    // Entferne eventuell vorhandenes QR-Modal
    $('#qrCodeModal').remove();
    
    // Füge das Modal zum DOM hinzu
    $('body').append(modalContent);
    
    // Entferne Focus vom aktiven Element um Aria-Warnungen zu vermeiden
    if (document.activeElement && document.activeElement.blur) {
        document.activeElement.blur();
    }
    
    // Zeige das Modal an
    const qrModal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
    qrModal.show();
    
    // Setze Focus auf das Modal nach dem Öffnen
    $('#qrCodeModal').on('shown.bs.modal', function() {
        $(this).focus();
    });
    
    // Lösche alten QR-Code falls vorhanden
    $('#qrcode').empty();
    
    // Generiere den QR-Code
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById("qrcode"), {
            text: testUrl,
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    } else {
        $('#qrcode').html(`
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=${encodeURIComponent(testUrl)}" 
                 alt="QR-Code für ${accessCode}" class="img-fluid">
        `);
    }
    
    // Event-Handler zum Kopieren der URL (nur einmal registrieren)
    $('.copy-url-btn').off('click').on('click', function() {
        const urlInput = document.getElementById('testUrlInput');
        urlInput.select();
        document.execCommand('copy');
        
        const originalHtml = $(this).html();
        $(this).html('<i class="bi bi-check"></i>');
        setTimeout(() => {
            $(this).html(originalHtml);
        }, 2000);
        
        showSuccessMessage('URL wurde in die Zwischenablage kopiert!');
    });
    
    // Event-Handler zum Kopieren des QR-Codes (nur einmal registrieren)
    $('.copy-qr-btn').off('click').on('click', function() {
        const qrImg = $('#qrcode img').get(0);
        if (qrImg) {
            const canvas = document.createElement('canvas');
            canvas.width = qrImg.width;
            canvas.height = qrImg.height;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(qrImg, 0, 0);
            
            canvas.toBlob(function(blob) {
                const item = new ClipboardItem({ 'image/png': blob });
                
                navigator.clipboard.write([item]).then(function() {
                    const originalHtml = $('.copy-qr-btn').html();
                    $('.copy-qr-btn').html('<i class="bi bi-check"></i> In Zwischenablage kopiert!');
                    setTimeout(() => {
                        $('.copy-qr-btn').html(originalHtml);
                    }, 2000);
                    
                    showSuccessMessage('QR-Code wurde in die Zwischenablage kopiert!');
                }).catch(function(error) {
                    console.error('Fehler beim Kopieren des QR-Codes:', error);
                    alert('Der QR-Code konnte nicht in die Zwischenablage kopiert werden.');
                });
            });
        }
    });
    
    // Event-Handler zum Speichern des QR-Codes (nur einmal registrieren)
    $('.save-qr-btn').off('click').on('click', function() {
        const qrImg = $('#qrcode img').attr('src');
        if (qrImg) {
            const a = document.createElement('a');
            a.href = qrImg;
            a.download = `qrcode_${accessCode}.png`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            showSuccessMessage('QR-Code wurde als Bild gespeichert!');
        }
    });
    
    // Event-Handler für SEB-QR-Code Button (nur einmal registrieren)
    $('.seb-qr-btn').off('click').on('click', function() {
        console.log('🔒 SEB-QR-Code Button geklickt für Test:', accessCode);
        showSEBQRCode(accessCode, title);
    });
    
    // Event-Handler für "Test bearbeiten" Button
    $(document).on('click', '#editTest', function() {
        const accessCode = $(this).data('access-code');
        console.log('🔧 Test bearbeiten geklickt für Code:', accessCode);
        
        if (accessCode) {
            // Modal schließen
            const qrModal = bootstrap.Modal.getInstance(document.getElementById('qrCodeModal'));
            if (qrModal) {
                qrModal.hide();
            }
            
            // Zum Editor-Tab wechseln
            console.log('🔧 Wechsle zum Editor-Tab');
            activateTab('editor');
            
            // Test im Editor laden
            setTimeout(() => {
                console.log('🔧 Lade Test im Editor:', accessCode);
                
                // Finde und wähle den Test in der Dropdown aus
                let testFound = false;
                $('#testSelector option').each(function() {
                    const optionText = $(this).text();
                    const optionValue = $(this).val();
                    
                    // Prüfe genau den Access-Code
                    const optionAccessCode = $(this).data('access-code');
                    if (optionAccessCode === accessCode) {
                        console.log('✅ Test gefunden und auswählen:', optionText);
                        $(this).prop('selected', true);
                        $('#testSelector').trigger('change');
                        testFound = true;
                        return false; // break
                    }
                });
                
                // Falls Test nicht in Dropdown gefunden, lade Testliste neu
                if (!testFound) {
                    console.log('🔧 Test nicht in Dropdown, lade Testliste neu...');
                    reloadTestList(() => {
                        // Nochmal versuchen nach Reload
                        $('#testSelector option').each(function() {
                            const optionText = $(this).text();
                            const optionValue = $(this).val();
                            
                            if (optionText.includes(accessCode) || optionValue.includes(accessCode)) {
                                console.log('✅ Test nach Reload gefunden:', optionText);
                                $(this).prop('selected', true);
                                $('#testSelector').trigger('change');
                                return false;
                            }
                        });
                    });
                }
            }, 500);
        } else {
            console.error('❌ Kein Access-Code für Test bearbeiten gefunden');
            alert('Fehler: Test-Code nicht gefunden. Bitte wählen Sie den Test manuell im Editor aus.');
        }
    });
    
    // Event-Handler zum Entfernen des Modals (nur einmal registrieren)
    $('#qrCodeModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
        $(this).remove();
        // Entferne auch den Modal-Backdrop
        $('.modal-backdrop').remove();
        // Entferne die modal-open Klasse vom Body
        $('body').removeClass('modal-open').css('padding-right', '');
    });
    
    }, 100); // Ende setTimeout - 100ms Debounce
}

// Funktion zum Anzeigen des SEB-QR-Codes (parallel zur normalen QR-Code Funktion)
function showSEBQRCode(accessCode, title) {
    console.log('🔒 Generiere SEB-QR-Code für Test:', accessCode);
    
    // Entferne eventuell vorhandenes SEB-Modal
    $('#sebQrCodeModal').remove();
    
    // Erstelle verschiedene SEB-URLs für automatisches Laden
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/teacher\/.*$|\/[^\/]*$/, '/');
    const sebConfigUrl = baseUrl + 'seb_config.php?code=' + accessCode;
    
    // EMBEDDED SEB-KONFIGURATION - EIN QR-CODE FÜR ALLES
    const sebConfigFlexibleUrl = baseUrl + 'seb_config_override_server.php?code=' + accessCode;
    const nameFormUrl = baseUrl + 'name_form.php?code=' + accessCode;
    
    // KORREKTE EMBEDDED CONFIG URLs (sebs:// Schema für HTTPS) - SERVER OVERRIDE CONFIG
    const host = window.location.host;
    const path = window.location.pathname.replace(/\/teacher\/.*$|\/[^\/]*$/, '/');
    const sebsEmbeddedUrl = 'sebs://' + host + path + 'seb_config_override_server.php?code=' + accessCode;
    
    // ALTERNATIVE URLS
    const sebDirectUrl = 'seb://' + encodeURIComponent(sebConfigFlexibleUrl);     // Fallback HTTP
    const directTestUrl = nameFormUrl;                                           // Direkte Test-URL
    
    console.log('🔒 Base URL:', baseUrl);
    console.log('🔒 Access Code:', accessCode);
    
    console.log('🔒 SEB-Config URL:', sebConfigUrl);
    console.log('🔧 SEB-Flexible URL:', sebConfigFlexibleUrl);
    console.log('⭐ SEBS-Embedded URL:', sebsEmbeddedUrl);
    console.log('🔗 SEB-Direct URL:', sebDirectUrl);
    console.log('🌐 Direct Test URL:', directTestUrl);
    
    // Erstelle das SEB-Modal
    const modalContent = 
        '<div class="modal fade" id="sebQrCodeModal" tabindex="-1" aria-labelledby="sebQrCodeModalLabel" aria-hidden="true">' +
            '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content">' +
                    '<div class="modal-header bg-warning">' +
                        '<h5 class="modal-title" id="sebQrCodeModalLabel">' +
                            '<i class="bi bi-shield-lock me-2"></i>SEB-QR-Code für Test: ' + title +
                        '</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>' +
                    '</div>' +
                    '<div class="modal-body text-center">' +
                        '<div class="alert alert-warning">' +
                            '<h6><i class="bi bi-qr-code-scan me-2"></i>SEB-Konfiguration per QR-Code</h6>' +
                            '<p class="mb-0">Scannen Sie diesen QR-Code um SEB automatisch zu konfigurieren und den Test zu starten.</p>' +
                        '</div>' +
                        
                        '<div class="d-flex justify-content-center mb-4">' +
                            '<div id="sebQrcodeMain"></div>' +
                        '</div>' +
                        '<div class="input-group mb-3">' +
                            '<input type="text" id="sebTestUrlInput" class="form-control" value="' + sebsEmbeddedUrl + '" readonly>' +
                            '<button class="btn btn-outline-secondary copy-seb-url-btn" type="button" title="SEB-URL kopieren">' +
                                '<i class="bi bi-clipboard"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="d-grid gap-2">' +
                            '<button type="button" class="btn btn-outline-success save-seb-qr-btn">' +
                                '<i class="bi bi-image me-2"></i>QR-Code speichern' +
                            '</button>' +
                            '<button type="button" class="btn btn-outline-primary download-seb-btn">' +
                                '<i class="bi bi-download me-2"></i>SEB herunterladen' +
                            '</button>' +
                            '<button type="button" class="btn btn-outline-info seb-restrictions-btn">' +
                                '<i class="bi bi-shield-lock me-2"></i>Einschränkungen' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    
    // Füge das Modal zum DOM hinzu
    $('body').append(modalContent);
    
    // Zeige das Modal an
    const sebQrModal = new bootstrap.Modal(document.getElementById('sebQrCodeModal'));
    sebQrModal.show();
    
    // Generiere den einzigen SEB-QR-Code (Orange)
    generateSEBQRCodeMain(sebsEmbeddedUrl, accessCode);    // SEBS Embedded Config (Orange)
    
    // Event-Handler für SEB-Modal Buttons
    setupSEBModalEventHandlers(accessCode, sebConfigUrl, sebDirectUrl, sebsEmbeddedUrl);
    
    // Auto-remove Modal nach schließen
    $('#sebQrCodeModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

// Hilfsfunktion zum Generieren des direkten URL QR-Codes
function generateSEBQRCode(directTestUrl, accessCode) {
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById("sebQrcode"), {
            text: directTestUrl,
            width: 200,
            height: 200,
            colorDark: "#17a2b8",  // Blau für direkte URL
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        console.log('✅ Direkte Test-URL QR-Code mit QRCode.js generiert');
    } else {
        $('#sebQrcode').html(
            '<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&color=17a2b8&data=' + 
            encodeURIComponent(directTestUrl) + '" alt="Direkte Test-URL QR-Code für ' + accessCode + '" class="img-fluid">'
        );
        console.log('✅ Direkte Test-URL QR-Code mit Online-Generator erstellt');
    }
}

// Hilfsfunktion zum Generieren des direkten SEB-Schema QR-Codes
function generateSEBQRCodeDirect(sebDirectUrl, accessCode) {
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById("sebQrcodeDirect"), {
            text: sebDirectUrl,
            width: 200,
            height: 200,
            colorDark: "#ff6b35",  // Orange für direkte SEB-URL
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        console.log('✅ SEB-Direct-QR-Code mit QRCode.js generiert:', sebDirectUrl);
    } else {
        $('#sebQrcodeDirect').html(
            '<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&color=ff6b35&data=' + 
            encodeURIComponent(sebDirectUrl) + '" alt="SEB-Direct-QR-Code für ' + accessCode + '" class="img-fluid">'
        );
        console.log('✅ SEB-Direct-QR-Code mit Online-Generator erstellt');
    }
}

// Hilfsfunktion zum Generieren des Haupt-SEB-QR-Codes (Orange)
function generateSEBQRCodeMain(sebsEmbeddedUrl, accessCode) {
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById("sebQrcodeMain"), {
            text: sebsEmbeddedUrl,
            width: 250,
            height: 250,
            colorDark: "#fd7e14",  // Orange für Haupt-QR-Code
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        console.log('✅ Haupt-SEB-QR-Code (Orange) mit QRCode.js generiert:', sebsEmbeddedUrl);
    } else {
        $('#sebQrcodeMain').html(
            '<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&color=fd7e14&data=' + 
            encodeURIComponent(sebsEmbeddedUrl) + '" alt="Haupt-SEB-QR-Code für ' + accessCode + '" class="img-fluid">'
        );
        console.log('✅ Haupt-SEB-QR-Code (Orange) mit Online-Generator erstellt');
    }
}

// Event-Handler für SEB-Modal Buttons
function setupSEBModalEventHandlers(accessCode, sebConfigUrl, sebDirectUrl, sebStartConfigUrl) {
    // URL kopieren
    $('.copy-seb-url-btn').off('click').on('click', function() {
        const sebUrl = $('#sebTestUrlInput').val();
        navigator.clipboard.writeText(sebUrl).then(function() {
            console.log('🔗 SEB-Embedded-URL kopiert:', sebUrl);
            showSuccessMessage('SEB-URL wurde in die Zwischenablage kopiert!');
        }).catch(function(err) {
            console.error('❌ Fehler beim Kopieren der SEB-URL:', err);
            // Fallback
            const urlInput = document.getElementById('sebTestUrlInput');
            urlInput.select();
            document.execCommand('copy');
            showSuccessMessage('SEB-URL wurde in die Zwischenablage kopiert!');
        });
    });
    
}

// GLOBALE SEB-MODAL EVENT-HANDLER (Event-Delegation)
// SEB-Datei herunterladen
$(document).on('click', '.download-seb-btn', function() {
    // AccessCode aus dem Modal extrahieren
    const modalAccessCode = $('#sebTestUrlInput').val().match(/code=([^&]+)/);
    const extractedCode = modalAccessCode ? modalAccessCode[1] : 'TEST';
    
    console.log('💾 SEB-Datei herunterladen für:', extractedCode);
    
    // BaseUrl dynamisch ermitteln - korrigiert für Instanzen
    let currentBaseUrl;
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('/lehrer_instanzen/')) {
        // Wir sind in einer Instanz - extrahiere den Instanz-Pfad
        const instanceMatch = currentPath.match(/\/lehrer_instanzen\/([^\/]+)\/mcq-test-system/);
        if (instanceMatch) {
            currentBaseUrl = window.location.origin + '/lehrer_instanzen/' + instanceMatch[1] + '/mcq-test-system/';
        } else {
            // Fallback für Instanzen
            currentBaseUrl = window.location.origin + currentPath.replace(/\/teacher\/.*$/, '/');
        }
    } else {
        // Wir sind im Hauptsystem
        currentBaseUrl = window.location.origin + window.location.pathname.replace(/\/teacher\/.*$|\/[^\/]*$/, '/');
    }
    
    const sebDownloadUrl = currentBaseUrl + 'seb_config_override_server.php?code=' + extractedCode;
    console.log('🔗 SEB-Download-URL:', sebDownloadUrl);
    window.open(sebDownloadUrl, '_blank');
});

// SEB-Einschränkungen anzeigen
$(document).on('click', '.seb-restrictions-btn', function() {
    // AccessCode aus dem Modal extrahieren
    const modalAccessCode = $('#sebTestUrlInput').val().match(/code=([^&]+)/);
    const extractedCode = modalAccessCode ? modalAccessCode[1] : 'TEST';
    
    console.log('🔒 Zeige SEB-Einschränkungen für:', extractedCode);
    
    // BaseUrl dynamisch ermitteln - korrigiert für Instanzen
    let currentBaseUrl;
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('/lehrer_instanzen/')) {
        // Wir sind in einer Instanz - extrahiere den Instanz-Pfad
        const instanceMatch = currentPath.match(/\/lehrer_instanzen\/([^\/]+)\/mcq-test-system/);
        if (instanceMatch) {
            currentBaseUrl = window.location.origin + '/lehrer_instanzen/' + instanceMatch[1] + '/mcq-test-system/';
        } else {
            // Fallback für Instanzen
            currentBaseUrl = window.location.origin + currentPath.replace(/\/teacher\/.*$/, '/');
        }
    } else {
        // Wir sind im Hauptsystem
        currentBaseUrl = window.location.origin + window.location.pathname.replace(/\/teacher\/.*$|\/[^\/]*$/, '/');
    }
    
    const previewUrl = currentBaseUrl + 'seb_config_preview.php?code=' + extractedCode;
    console.log('🔗 SEB-Preview-URL:', previewUrl);
    window.open(previewUrl, '_blank');
});

// QR-Code speichern
$(document).on('click', '.save-seb-qr-btn', function() {
    // AccessCode aus dem Modal extrahieren
    const modalAccessCode = $('#sebTestUrlInput').val().match(/code=([^&]+)/);
    const extractedCode = modalAccessCode ? modalAccessCode[1] : 'TEST';
    
    console.log('💾 QR-Code speichern für:', extractedCode);
    
    // Versuche QR-Code aus dem Canvas zu holen (wenn QRCode.js verwendet wird)
    const qrCanvas = $('#sebQrcodeMain canvas').get(0);
    if (qrCanvas) {
        // Canvas zu Blob konvertieren und downloaden
        qrCanvas.toBlob(function(blob) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'seb_qrcode_' + extractedCode + '.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showSuccessMessage('SEB-QR-Code wurde als Bild gespeichert!');
        });
    } else {
        // Fallback: Wenn Bild-Element vorhanden ist
        const qrImg = $('#sebQrcodeMain img').attr('src');
        if (qrImg) {
            const a = document.createElement('a');
            a.href = qrImg;
            a.download = 'seb_qrcode_' + extractedCode + '.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            showSuccessMessage('SEB-QR-Code wurde als Bild gespeichert!');
        } else {
            console.error('❌ Kein QR-Code zum Speichern gefunden');
            alert('Fehler: QR-Code konnte nicht gefunden werden.');
        }
    }
});

// Event-Handler für "Test bearbeiten" Button (Test-Vorschau Modal)
$(document).on('click', '#editGeneratedTest', function() {
    const accessCode = $(this).data('access-code');
    console.log('🔧 Test bearbeiten (Vorschau) geklickt für Code:', accessCode);
    
    if (accessCode) {
        // Modal schließen
        const previewModal = bootstrap.Modal.getInstance(document.getElementById('testGeneratorPreviewModal'));
        if (previewModal) {
            previewModal.hide();
        }
        
        // Delay für sauberen Übergang
        setTimeout(() => {
                // Wechsle zum Editor-Tab
                activateTab('editor');
            
            // Warte bis Tab geladen ist, dann Test laden
            setTimeout(() => {
                // Reload Test-Liste falls nötig
                if (typeof reloadTestList === 'function') {
                    reloadTestList(() => {
                        // Nach Reload: Setze den Test im Dropdown
                        let testFound = false;
                        $('#testSelector option').each(function() {
                            const optionAccessCode = $(this).data('access-code');
                            if (optionAccessCode === accessCode) {
                                $(this).prop('selected', true);
                                $('#testSelector').trigger('change');
                                console.log('✅ Test automatisch ausgewählt (Vorschau):', accessCode);
                                testFound = true;
                                return false;
                            }
                        });
                        
                        if (!testFound) {
                            console.error('❌ Test mit Access-Code nicht gefunden:', accessCode);
                            // Fallback: Suche im Text
                            $('#testSelector option').each(function() {
                                if ($(this).text().includes('[' + accessCode + ']')) {
                                    $(this).prop('selected', true);
                                    $('#testSelector').trigger('change');
                                    console.log('✅ Test über Text-Suche gefunden (Vorschau):', accessCode);
                                    return false;
                                }
                            });
                        }
                    });
                } else {
                    // Fallback: Setze den Test direkt im Dropdown
                    let testFound = false;
                    $('#testSelector option').each(function() {
                        const optionAccessCode = $(this).data('access-code');
                        if (optionAccessCode === accessCode) {
                            $(this).prop('selected', true);
                            $('#testSelector').trigger('change');
                            console.log('✅ Test automatisch ausgewählt (Vorschau, Fallback):', accessCode);
                            testFound = true;
                            return false;
                        }
                    });
                    
                    if (!testFound) {
                        console.error('❌ Test mit Access-Code nicht gefunden (Fallback):', accessCode);
                        // Letzte Chance: Suche im Text
                        $('#testSelector option').each(function() {
                            if ($(this).text().includes('[' + accessCode + ']')) {
                                $(this).prop('selected', true);
                                $('#testSelector').trigger('change');
                                console.log('✅ Test über Text-Suche gefunden (Fallback):', accessCode);
                                return false;
                            }
                        });
                    }
                }
            }, 500);
        }, 500);
    } else {
        console.error('❌ Kein Access-Code für Test bearbeiten (Vorschau) gefunden');
        alert('Fehler: Test-Code nicht gefunden. Bitte wählen Sie den Test manuell im Editor aus.');
    }
});

// Funktion zum Neuladen der Test-Liste im Editor
function reloadTestList(callback) {
    console.log('🔄 Lade Test-Liste neu...');
    
    $.ajax({
        url: getIncludesUrl('teacher_dashboard/reload_test_list.php'),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.tests) {
                console.log('✅ Test-Liste erfolgreich geladen:', response.tests.length, 'Tests');
                
                // Aktualisiere das Dropdown
                const $selector = $('#testSelector');
                const currentValue = $selector.val();
                
                // Behalte die erste Option bei
                $selector.find('option:first').siblings().remove();
                
                // Füge Tests hinzu (bereits sortiert vom Server)
                response.tests.forEach(test => {
                    $selector.append(`
                        <option value="${escapeHTML(test.name)}" 
                                data-access-code="${escapeHTML(test.accessCode)}"
                                data-title="${escapeHTML(test.title)}">
                            [${escapeHTML(test.accessCode)}] ${escapeHTML(test.title)}
                        </option>
                    `);
                });
                
                console.log('✅ Test-Dropdown aktualisiert');
                
                if (callback) {
                    callback();
                }
            } else {
                console.error('❌ Fehler beim Laden der Test-Liste:', response.error || 'Unbekannter Fehler');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX-Fehler beim Laden der Test-Liste:', error);
            if (callback) {
                callback();
            }
        }
    });
}

// Hilfsfunktion zum Anzeigen von Fehlermeldungen
function showErrorMessage(message) {
    const errorHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ${escapeHTML(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
    `;
    
    // Füge die Fehlermeldung am Anfang des Hauptcontainers ein
    $('.container').first().prepend(errorHtml);
    
    // Optional: Scrolle zur Fehlermeldung
    window.scrollTo(0, 0);
    
    // Protokolliere den Fehler in der Konsole
    console.error('Fehler:', message);
} 