/**
 * Debug-Tool für Tab-Verhalten
 * In Browser-Konsole ausführen auf der Teacher Dashboard Seite
 */

console.log('🔍 Tab Debug Tool geladen');

// Funktion zum Debuggen der Tab-Events
function debugTabBehavior() {
    console.log('=== TAB DEBUG START ===');
    
    // Alle Tab-Elemente finden
    const tabs = document.querySelectorAll('.tab');
    console.log('Gefundene Tabs:', tabs.length);
    
    tabs.forEach((tab, index) => {
        console.log(`Tab ${index + 1}:`, {
            id: tab.id,
            href: tab.href,
            onclick: tab.onclick ? tab.onclick.toString() : 'keine',
            eventListeners: getEventListeners ? getEventListeners(tab) : 'nicht verfügbar'
        });
    });
    
    // Alle Tab-Panes finden
    const tabPanes = document.querySelectorAll('.tab-pane');
    console.log('Gefundene Tab-Panes:', tabPanes.length);
    
    tabPanes.forEach((pane, index) => {
        console.log(`Pane ${index + 1}:`, {
            id: pane.id,
            display: window.getComputedStyle(pane).display,
            classList: [...pane.classList]
        });
    });
    
    // Event-Listener auf document prüfen
    console.log('Document Event-Listener:', getEventListeners ? getEventListeners(document) : 'nicht verfügbar');
    
    // activateTab Funktion prüfen
    if (typeof activateTab === 'function') {
        console.log('activateTab Funktion gefunden:', activateTab.toString());
    } else {
        console.log('❌ activateTab Funktion nicht gefunden!');
    }
    
    console.log('=== TAB DEBUG END ===');
}

// Funktion zum Überwachen von Tab-Events
function monitorTabEvents() {
    console.log('🔍 Überwache Tab-Events...');
    
    // Event-Listener für alle Klicks
    document.addEventListener('click', function(e) {
        console.log('CLICK EVENT:', {
            target: e.target,
            currentTarget: e.currentTarget,
            tagName: e.target.tagName,
            id: e.target.id,
            className: e.target.className,
            preventDefault: e.defaultPrevented,
            propagationStopped: e.cancelBubble
        });
        
        // Wenn es ein Tab ist
        if (e.target.classList.contains('tab')) {
            console.log('🎯 TAB CLICK DETECTED:', e.target.id);
        }
        
        // Wenn es ein Form-Submit ist
        if (e.target.type === 'submit' || e.target.closest('form')) {
            console.log('📝 FORM RELATED CLICK:', e.target);
        }
    }, true); // Capture-Phase
    
    // Event-Listener für Form-Submits
    document.addEventListener('submit', function(e) {
        console.log('FORM SUBMIT EVENT:', {
            form: e.target,
            formId: e.target.id,
            defaultPrevented: e.defaultPrevented
        });
    }, true);
    
    // activateTab Funktion überwachen (falls vorhanden)
    if (typeof activateTab === 'function') {
        const originalActivateTab = activateTab;
        window.activateTab = function(tabId) {
            console.log('🎯 activateTab aufgerufen mit:', tabId);
            console.trace('Call Stack:');
            return originalActivateTab.call(this, tabId);
        };
    }
}

// Funktion zum Testen der Instanz-Erstellung
function testInstanceCreation() {
    console.log('🧪 Teste Instanz-Erstellung...');
    
    const form = document.getElementById('createInstanceForm');
    if (!form) {
        console.log('❌ createInstanceForm nicht gefunden!');
        return;
    }
    
    console.log('✅ Form gefunden:', form);
    
    // Event-Listener des Forms prüfen
    console.log('Form Event-Listener:', getEventListeners ? getEventListeners(form) : 'nicht verfügbar');
    
    // Simuliere Form-Submit
    const instanceName = document.getElementById('instanceName');
    const adminCode = document.getElementById('adminAccessCode');
    
    if (instanceName) instanceName.value = 'test-debug-instance';
    if (adminCode) adminCode.value = 'debug123';
    
    console.log('Simuliere Form-Submit...');
    
    // Submit-Event manuell auslösen
    const submitEvent = new Event('submit', {
        bubbles: true,
        cancelable: true
    });
    
    console.log('Event erstellt:', submitEvent);
    form.dispatchEvent(submitEvent);
}

// Automatisch starten
debugTabBehavior();
monitorTabEvents();

console.log('💡 Verfügbare Debug-Funktionen:');
console.log('- debugTabBehavior() - Analysiert Tab-System');
console.log('- monitorTabEvents() - Überwacht alle Events');  
console.log('- testInstanceCreation() - Testet Instanz-Erstellung');

// Export für manuellen Aufruf
window.debugTools = {
    debugTabBehavior,
    monitorTabEvents,
    testInstanceCreation
};
