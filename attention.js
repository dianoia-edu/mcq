// Hinweis: Aufmerksamkeitsbutton ist derzeit deaktiviert
console.log('Aufmerksamkeitsbutton ist deaktiviert');

/*
// Aufmerksamkeitsbutton Funktionalität
function createAttentionButton() {
    const button = document.createElement('button');
    button.id = 'attentionButton';
    button.innerHTML = 'Klicken Sie hier! <span class="countdown">4</span>';
    button.style.cssText = `
        position: fixed;
        padding: 10px 20px;
        background-color: #ff4444;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        z-index: 1000;
        display: none;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    `;
    document.body.appendChild(button);
    return button;
}

// Hauptfunktion
document.addEventListener('DOMContentLoaded', () => {
    console.log('Attention.js geladen - Initialisiere Aufmerksamkeitsbutton...');
    
    // Lese zuerst das data-Attribut vom body-Element
    const dataDisabled = document.body.getAttribute('data-disable-attention-button');
    console.log('data-disable-attention-button Attribut:', dataDisabled);
    
    // Prüfe, ob wir im Testmodus sind
    const isTestMode = document.body.getAttribute('data-test-mode') === 'true';
    console.log('Test-Modus:', isTestMode);
    
    // Überprüfe, ob der Button über das data-Attribut deaktiviert ist
    const isDisabledByAttribute = dataDisabled === 'true';
    console.log('Deaktiviert durch data-Attribut:', isDisabledByAttribute);
    
    // Wenn bereits durch das Attribut deaktiviert, müssen wir die Konfiguration nicht laden
    if (isTestMode || isDisabledByAttribute) {
        console.log('Aufmerksamkeitsbutton ist über HTML-Attribut deaktiviert');
        return;
    }
    
    // Als Fallback und zur Doppelprüfung: Hole Konfiguration vom Server
    console.log('Lade Konfiguration vom Server...');
    fetch('../config/app_config.json')
        .then(response => {
            console.log('Konfigurationsanfrage Status:', response.status);
            if (!response.ok) {
                console.log('Keine Konfigurationsdatei gefunden, verwende Standardwerte');
                // Versuche alternativen Pfad
                return fetch('./config/app_config.json')
                    .then(altResponse => {
                        if (!altResponse.ok) {
                            console.log('Auch alternativer Pfad fehlgeschlagen');
                            return { disableAttentionButton: false };
                        }
                        console.log('Alternativer Pfad erfolgreich');
                        return altResponse.json();
                    })
                    .catch(altError => {
                        console.error('Fehler beim Laden der Konfiguration (Alt):', altError);
                        return { disableAttentionButton: false };
                    });
            }
            return response.json();
        })
        .then(config => {
            console.log('Konfiguration geladen:', config);
            initializeAttentionButton(config);
        })
        .catch(error => {
            console.error('Fehler beim Laden der Konfiguration:', error);
            // Bei Fehler, Standardkonfiguration verwenden
            initializeAttentionButton({ disableAttentionButton: false });
        });

    function initializeAttentionButton(config) {
        // Prüfe nochmals alle Bedingungen
        const isAttentionButtonDisabled = document.body.getAttribute('data-disable-attention-button') === 'true' || 
                                          (config && config.disableAttentionButton === true);
        
        console.log('Aufmerksamkeitsbutton Deaktivierungsstatus:', isAttentionButtonDisabled);
        console.log('config.disableAttentionButton:', config.disableAttentionButton);
        
        // Wenn wir im Testmodus sind oder der Button deaktiviert ist, starte den Button nicht
        if (isTestMode || isAttentionButtonDisabled) {
            console.log('Aufmerksamkeitsbutton wird nicht initialisiert (deaktiviert)');
            return;
        }

        console.log('Aufmerksamkeitsbutton wird initialisiert...');
        const button = createAttentionButton();
        let timeoutId;
        let countdownInterval;
        let missedClicks = 0;
        let testActive = true;
        const testForm = document.getElementById('testForm');

        function updateCountdown(seconds) {
            const countdownElement = button.querySelector('.countdown');
            countdownElement.textContent = seconds;
        }

        function addHiddenInput(form, name, value) {
            let input = form.querySelector(`input[name="${name}"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                form.appendChild(input);
            }
            input.value = value;
        }

        async function endTest() {
            if (!testActive) return;
            testActive = false;

            try {
                if (testForm) {
                    addHiddenInput(testForm, 'aborted', 'true');
                    addHiddenInput(testForm, 'missedClicks', missedClicks);
                    testForm.submit();
                } else {
                    console.error('Testformular nicht gefunden');
                }
            } catch (error) {
                console.error('Fehler beim Speichern:', error);
                alert('Fehler beim Speichern des Tests. Bitte wenden Sie sich an die Aufsichtsperson.');
                testActive = true;
                missedClicks--;
            }
        }

        function showButton() {
            if (!testActive) return;
            
            button.style.display = 'block';
            
            let secondsLeft = 4;
            updateCountdown(secondsLeft);

            countdownInterval = setInterval(() => {
                if (!testActive) {
                    clearInterval(countdownInterval);
                    return;
                }

                secondsLeft--;
                updateCountdown(secondsLeft);
                
                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                    button.style.display = 'none';
                    missedClicks++;
                    
                    if (missedClicks >= 2) {
                        endTest();
                    }
                }
            }, 1000);

            timeoutId = setTimeout(() => {
                if (button.style.display === 'block') {
                    clearInterval(countdownInterval);
                    button.style.display = 'none';
                }
            }, 4000);
        }

        button.addEventListener('click', () => {
            if (!testActive) return;
            
            button.style.display = 'none';
            clearTimeout(timeoutId);
            clearInterval(countdownInterval);
        });

        function scheduleNextButton() {
            if (!testActive) return;
            
            const delay = Math.floor(Math.random() * (60000 - 40000) + 10000); //  Sekunden
            setTimeout(() => {
                if (testActive) {
                    showButton();
                    scheduleNextButton();
                }
            }, delay);
        }

        // Verhindere das normale Absenden des Formulars bei verpassten Klicks
        if (testForm) {
            testForm.addEventListener('submit', (e) => {
                if (missedClicks > 0) {
                    e.preventDefault();
                    endTest();
                }
            });
        }

        scheduleNextButton();
    }
});
*/ 