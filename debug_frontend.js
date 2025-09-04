/**
 * Frontend Debug Tool - direkt in Browser-Konsole verwenden
 * Auf Teacher Dashboard Seite ausführen
 */

console.log('🔍 Frontend Debug Tool geladen');

// Test die Instanzen-API direkt
function testInstancesAPI() {
    console.log('=== FRONTEND API TEST ===');
    
    // Check if getIncludesUrl function exists
    if (typeof getIncludesUrl !== 'function') {
        console.error('❌ getIncludesUrl Funktion nicht gefunden!');
        return;
    }
    
    const apiUrl = getIncludesUrl('teacher_dashboard/get_instances.php');
    console.log('🔗 API URL:', apiUrl);
    
    // Test AJAX call exactly like the frontend does
    $.ajax({
        url: apiUrl,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('✅ AJAX Success:', response);
            
            if (response.success) {
                console.log(`📊 Gefundene Instanzen: ${response.instances.length}`);
                response.instances.forEach((inst, i) => {
                    console.log(`${i+1}. ${inst.name}: ${inst.admin_code} (${inst.status})`);
                });
                
                // Test displayInstanceList function
                if (typeof displayInstanceList === 'function') {
                    console.log('🎯 Teste displayInstanceList...');
                    const testDiv = $('#instanceList');
                    displayInstanceList(response.instances, testDiv);
                    console.log('✅ displayInstanceList ausgeführt');
                } else {
                    console.error('❌ displayInstanceList Funktion nicht gefunden!');
                }
            } else {
                console.error('❌ API Response success=false:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                responseText: xhr.responseText
            });
        }
    });
}

// Test loadInstanceList function
function testLoadInstanceList() {
    console.log('=== FRONTEND LOAD TEST ===');
    
    if (typeof loadInstanceList === 'function') {
        console.log('🎯 Rufe loadInstanceList() auf...');
        loadInstanceList();
    } else {
        console.error('❌ loadInstanceList Funktion nicht gefunden!');
    }
}

// Check if required functions exist
function checkFunctions() {
    console.log('=== FUNCTION CHECK ===');
    
    const requiredFunctions = [
        'getIncludesUrl',
        'loadInstanceList', 
        'displayInstanceList',
        'getStatusBadge',
        'getStatusIcon'
    ];
    
    requiredFunctions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ ${funcName} vorhanden`);
        } else {
            console.error(`❌ ${funcName} nicht gefunden!`);
        }
    });
}

// Check DOM elements
function checkDOM() {
    console.log('=== DOM CHECK ===');
    
    const instanceList = $('#instanceList');
    console.log('instanceList Element:', instanceList.length ? '✅ gefunden' : '❌ nicht gefunden');
    
    if (instanceList.length) {
        console.log('Current content:', instanceList.html().substring(0, 100) + '...');
    }
    
    const tabs = $('.tab');
    console.log('Tab-Elemente:', tabs.length);
    
    const activeTab = $('.tab.active');
    console.log('Aktiver Tab:', activeTab.length ? activeTab.attr('id') : 'keiner');
}

// Auto-run checks
checkFunctions();
checkDOM();

// Export functions
window.frontendDebug = {
    testInstancesAPI,
    testLoadInstanceList,
    checkFunctions,
    checkDOM
};

console.log('💡 Verfügbare Debug-Funktionen:');
console.log('- testInstancesAPI() - Testet API-Aufruf');
console.log('- testLoadInstanceList() - Testet loadInstanceList()');
console.log('- checkFunctions() - Prüft ob Funktionen vorhanden');
console.log('- checkDOM() - Prüft DOM-Elemente');
