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
    // Prüfe, ob wir im Testmodus sind
    const isTestMode = document.body.getAttribute('data-test-mode') === 'true';
    const isAttentionButtonDisabled = document.body.getAttribute('data-disable-attention-button') === 'true';
    
    // Wenn wir im Testmodus sind oder der Button deaktiviert ist, starte den Button nicht
    if (isTestMode || isAttentionButtonDisabled) {
        console.log('Aufmerksamkeitsbutton ist deaktiviert');
        return;
    }

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
}); 