// Debug-Script für Teacher Dashboard Test-Generator
// Dieses Script kann in der Browser-Console ausgeführt werden

console.log('🔍 Starte Teacher Dashboard Debug...');

// 1. Prüfe Tab-Zustand
function debugTabs() {
    console.log('📋 Tab-Debug:');
    
    const tabs = document.querySelectorAll('.tab');
    console.log('Gefundene Tabs:', tabs.length);
    
    tabs.forEach((tab, index) => {
        console.log(`Tab ${index}:`, {
            text: tab.textContent,
            classList: Array.from(tab.classList),
            onclick: tab.onclick,
            href: tab.href
        });
    });
    
    const tabPanes = document.querySelectorAll('.tab-pane');
    console.log('Gefundene Tab-Panes:', tabPanes.length);
    
    tabPanes.forEach((pane, index) => {
        console.log(`Tab-Pane ${index}:`, {
            id: pane.id,
            classList: Array.from(pane.classList),
            display: window.getComputedStyle(pane).display,
            visible: pane.offsetParent !== null
        });
    });
}

// 2. Prüfe Test-Generator Form
function debugTestGeneratorForm() {
    console.log('📝 Test-Generator Form Debug:');
    
    const form = document.getElementById('uploadForm');
    if (!form) {
        console.error('❌ uploadForm nicht gefunden!');
        return;
    }
    
    console.log('✅ uploadForm gefunden:', form);
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);
    console.log('Form enctype:', form.enctype);
    
    // Prüfe Form-Felder
    const fields = form.querySelectorAll('input, select, textarea');
    console.log('Form-Felder gefunden:', fields.length);
    
    fields.forEach((field, index) => {
        console.log(`Feld ${index}:`, {
            name: field.name,
            type: field.type,
            value: field.value,
            required: field.required
        });
    });
    
    // Prüfe Submit-Button
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        console.log('✅ Submit-Button gefunden:', submitBtn.textContent);
        console.log('Button disabled:', submitBtn.disabled);
    } else {
        console.error('❌ Submit-Button nicht gefunden!');
    }
}

// 3. Prüfe Event-Handler
function debugEventHandlers() {
    console.log('🎯 Event-Handler Debug:');
    
    const form = document.getElementById('uploadForm');
    if (!form) {
        console.error('❌ Form nicht gefunden für Event-Handler Check');
        return;
    }
    
    // Test ob jQuery Event-Handler existiert
    if (typeof $ !== 'undefined') {
        const jqEvents = $._data(form, 'events');
        console.log('jQuery Events on form:', jqEvents);
        
        // Test ob main.js Event-Handler registriert ist
        if (jqEvents && jqEvents.submit) {
            console.log('✅ Submit Event-Handler gefunden:', jqEvents.submit.length);
        } else {
            console.error('❌ Kein Submit Event-Handler gefunden!');
        }
    }
    
    // Native Event Listener Check
    console.log('Form Event Listeners:', getEventListeners ? getEventListeners(form) : 'getEventListeners nicht verfügbar');
}

// 4. Test Form-Submit
function testFormSubmit() {
    console.log('🧪 Teste Form-Submit...');
    
    const form = document.getElementById('uploadForm');
    if (!form) {
        console.error('❌ Form nicht gefunden!');
        return;
    }
    
    // Fülle Test-Daten aus
    const urlField = form.querySelector('input[name="webpage_url"]');
    if (urlField) {
        urlField.value = 'https://de.wikipedia.org/wiki/Deutschland';
        console.log('✅ Test-URL eingefügt');
    }
    
    const questionCount = form.querySelector('input[name="question_count"]');
    if (questionCount) {
        questionCount.value = '3';
        console.log('✅ Fragenzahl gesetzt');
    }
    
    console.log('📤 Sende Form-Submit Event...');
    
    // Trigger Submit Event
    const submitEvent = new Event('submit', {
        bubbles: true,
        cancelable: true
    });
    
    const prevented = !form.dispatchEvent(submitEvent);
    console.log('Submit Event gesendet, preventDefault:', prevented);
    
    // Alternative: jQuery Submit
    if (typeof $ !== 'undefined') {
        console.log('🔄 Teste jQuery Submit...');
        $(form).trigger('submit');
    }
}

// 5. Prüfe AJAX-Konfiguration
function debugAjaxConfig() {
    console.log('🌐 AJAX-Konfiguration Debug:');
    
    console.log('window.mcqPaths:', window.mcqPaths);
    console.log('window.mcqConfig:', window.mcqConfig);
    
    if (typeof getTeacherUrl === 'function') {
        console.log('getTeacherUrl Test:', getTeacherUrl('generate_test.php'));
    } else {
        console.error('❌ getTeacherUrl Funktion nicht verfügbar!');
    }
    
    if (typeof getIncludesUrl === 'function') {
        console.log('getIncludesUrl Test:', getIncludesUrl('test.php'));
    } else {
        console.error('❌ getIncludesUrl Funktion nicht verfügbar!');
    }
}

// 6. Vollständiger Debug-Lauf
function runFullDebug() {
    console.log('🚀 Starte vollständigen Debug...');
    console.log('==========================================');
    
    debugTabs();
    console.log('------------------------------------------');
    
    debugTestGeneratorForm();
    console.log('------------------------------------------');
    
    debugEventHandlers();
    console.log('------------------------------------------');
    
    debugAjaxConfig();
    console.log('------------------------------------------');
    
    console.log('✅ Debug abgeschlossen');
    console.log('🧪 Für Test-Submit rufen Sie testFormSubmit() auf');
}

// Automatischer Start
setTimeout(() => {
    runFullDebug();
}, 1000);

// Globale Funktionen verfügbar machen
window.teacherDashboardDebug = {
    runFullDebug,
    debugTabs,
    debugTestGeneratorForm, 
    debugEventHandlers,
    debugAjaxConfig,
    testFormSubmit
};

console.log('🔧 Debug-Funktionen verfügbar unter: window.teacherDashboardDebug');
console.log('Beispiel: teacherDashboardDebug.testFormSubmit()');
