<?php
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Lade die Ergebnisse aus der Datenbank
$allResults = [];
try {
    $db = DatabaseConfig::getInstance()->getConnection();
    
    // Debug: Überprüfe direkt die tests-Tabelle
    $testCheck = $db->query("SELECT * FROM tests");
    $allTests = $testCheck->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Alle Tests in der Datenbank:");
    foreach ($allTests as $test) {
        writeLog(sprintf("ID: %d, Code: %s, Titel: %s", 
            $test['test_id'], 
            $test['access_code'], 
            $test['title']
        ));
    }
    
    // Debug: Überprüfe direkt die test_attempts-Tabelle
    $attemptsCheck = $db->query("SELECT * FROM test_attempts WHERE test_id IN (SELECT test_id FROM tests WHERE access_code IN ('KKW', 'KK3'))");
    $attempts = $attemptsCheck->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Test-Versuche für KKW und KK3:");
    foreach ($attempts as $attempt) {
        writeLog(sprintf("ID: %d, Test-ID: %d, Student: %s, XML: %s",
            $attempt['attempt_id'],
            $attempt['test_id'],
            $attempt['student_name'],
            $attempt['xml_file_path']
        ));
    }
    
    $stmt = $db->query("
        SELECT 
            t.access_code,
            t.title as testTitle,
            ta.student_name as studentName,
            ta.completed_at as date,
            ta.points_achieved,
            ta.points_maximum,
            ta.percentage,
            ta.grade,
            ta.xml_file_path as fileName,
            t.created_at
        FROM test_attempts ta
        JOIN tests t ON ta.test_id = t.test_id
        ORDER BY t.created_at DESC, ta.completed_at DESC
    ");
    $dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug-Logging für alle Datenbankeinträge
    writeLog("Alle Datenbankeinträge:");
    foreach ($dbResults as $result) {
        writeLog(sprintf(
            "Test: %s, Code: %s, Student: %s, Datei: %s",
            $result['testTitle'],
            $result['access_code'],
            $result['studentName'],
            $result['fileName']
        ));
    }
    
    foreach ($dbResults as $result) {
        // Debug-Logging für XML-Pfad
        writeLog("XML-Pfad aus Datenbank: " . $result['fileName']);
        
        $allResults[] = [
            'testTitle' => $result['testTitle'],
            'studentName' => $result['studentName'],
            'date' => date('d.m.Y H:i', strtotime($result['date'])),
            'accessCode' => $result['access_code'],
            'fileName' => basename($result['fileName']),
            'testDate' => date('Y-m-d', strtotime($result['date'])),
            'points_achieved' => $result['points_achieved'],
            'points_maximum' => $result['points_maximum'],
            'percentage' => $result['percentage'],
            'grade' => $result['grade'],
            'created_at' => $result['created_at']
        ];
        
        // Debug-Logging für konstruierte Pfade
        writeLog("Konstruierte Pfade für " . $result['studentName'] . ":");
        writeLog(" - Ordner: " . $result['access_code'] . '_' . date('Y-m-d', strtotime($result['date'])));
        writeLog(" - Datei: " . basename($result['fileName']));
    }
    writeLog("Ergebnisse aus Datenbank geladen: " . count($allResults));

    // Lade alle Tests aus der Datenbank
    $allTestsQuery = $db->query("
        SELECT 
            t.test_id,
            t.access_code,
            t.title,
            COUNT(ta.attempt_id) as attempt_count
        FROM tests t
        LEFT JOIN test_attempts ta ON t.test_id = ta.test_id
        GROUP BY t.test_id, t.access_code, t.title
        ORDER BY t.access_code
    ");
    $allTests = $allTestsQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    writeLog("Fehler beim Laden aus der Datenbank: " . $e->getMessage());
    $allResults = []; // Leeres Array bei Datenbankfehler
}

// Sortiere Ergebnisse nach Test und Datum
usort($allResults, function($a, $b) {
    // Zuerst nach Testtitel sortieren
    $titleCompare = strcmp($a['testTitle'], $b['testTitle']);
    if ($titleCompare !== 0) {
        return $titleCompare;
    }
    // Bei gleichem Testtitel nach Datum sortieren (neueste zuerst)
    return strtotime($b['date']) - strtotime($a['date']);
});

// Gruppiere Ergebnisse nach Test
$groupedResults = [];
foreach ($allResults as $result) {
    $key = $result['testTitle'] . '_' . $result['accessCode'];
    if (!isset($groupedResults[$key])) {
        $groupedResults[$key] = [
            'testTitle' => $result['testTitle'],
            'accessCode' => $result['accessCode'],
            'testDate' => $result['testDate'],
            'results' => []
        ];
    }
    $groupedResults[$key]['results'][] = $result;
}

// Sammle alle einzigartigen Namen und Daten
$uniqueStudents = [];
$testDates = [];
$uniqueTests = [];

foreach ($allResults as $result) {
    $uniqueStudents[$result['studentName']] = true;
    $testDates[$result['testDate']] = true;
    
    // Speichere nur den ersten Eintrag für jeden Test (der neueste aufgrund der SQL-Sortierung)
    if (!isset($uniqueTests[$result['accessCode']])) {
        $uniqueTests[$result['accessCode']] = [
            'title' => $result['testTitle'],
            'created_at' => $result['created_at']
        ];
    }
}

$uniqueStudents = array_keys($uniqueStudents);
sort($uniqueStudents);
$testDates = array_keys($testDates);
sort($testDates);

// Sortiere Tests nach Erstellungsdatum absteigend
uasort($uniqueTests, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Konvertiere Daten in JSON für JavaScript
$studentListJson = json_encode($uniqueStudents);
$testDatesJson = json_encode($testDates);
$uniqueTestsJson = json_encode($uniqueTests);
?>

<!-- Flatpickr für verbesserte Datumsauswahl -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/de.js"></script>

<!-- Bootstrap CSS und JS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="container mt-4">
    <h2>Testergebnisse</h2>
    <?php if (empty($groupedResults)): ?>
        <div class="alert alert-info">
            Keine Testergebnisse verfügbar.
        </div>
    <?php else: ?>
        <!-- Filteroptionen -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="studentFilter" class="form-label">Schüler</label>
                        <input type="text" class="form-control" id="studentFilter" 
                               placeholder="Nach Schülername filtern..." 
                               list="studentsList"
                               autocomplete="off">
                        <datalist id="studentsList">
                            <?php foreach ($uniqueStudents as $student): ?>
                                <option value="<?php echo htmlspecialchars($student); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-4">
                        <label for="dateFilter" class="form-label">Datum</label>
                        <input type="date" class="form-control" id="dateFilter">
                    </div>
                    <div class="col-md-4">
                        <label for="testFilterBtn" class="form-label">Test</label>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="testFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                Alle Tests
                            </button>
                            <ul class="dropdown-menu w-100" aria-labelledby="testFilterBtn">
                                <li><a class="dropdown-item" href="#" data-value="" data-code="">Alle Tests</a></li>
                                <?php foreach ($allTests as $test): ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $test['attempt_count'] == 0 ? 'no-attempts' : ''; ?>" 
                                           href="#" 
                                           data-value="<?php echo htmlspecialchars($test['title']); ?>"
                                           data-code="<?php echo htmlspecialchars($test['access_code']); ?>">
                                            <div class="test-code"><?php echo htmlspecialchars($test['access_code']); ?></div>
                                            <div class="test-title"><?php echo htmlspecialchars($test['title']); ?></div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="filteredResults">
            <?php foreach ($groupedResults as $group): ?>
                <div class="card mb-4 result-group" 
                     data-test-title="<?php echo htmlspecialchars($group['testTitle']); ?>"
                     data-test-date="<?php echo htmlspecialchars($group['testDate']); ?>">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            [<?php echo htmlspecialchars($group['accessCode']); ?>] - 
                            <?php echo htmlspecialchars($group['testTitle']); ?> 
                            (<?php echo htmlspecialchars($group['testDate']); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Schüler</th>
                                        <th>Abgabezeitpunkt</th>
                                        <th>Punkte</th>
                                        <th>Prozent</th>
                                        <th>Note</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['results'] as $result): ?>
                                        <tr class="result-row" 
                                            data-student="<?php echo htmlspecialchars($result['studentName']); ?>"
                                            data-date="<?php echo htmlspecialchars($result['testDate']); ?>">
                                            <td><?php echo htmlspecialchars($result['studentName']); ?></td>
                                            <td><?php echo htmlspecialchars($result['date']); ?></td>
                                            <td><?php echo htmlspecialchars($result['points_achieved']); ?>/<?php echo htmlspecialchars($result['points_maximum']); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($result['percentage'], 1)); ?>%</td>
                                            <td><?php echo htmlspecialchars($result['grade']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-result" 
                                                        data-folder="<?php echo htmlspecialchars($result['accessCode'] . '_' . $result['testDate']); ?>"
                                                        data-file="<?php echo htmlspecialchars($result['fileName']); ?>">
                                                    Details
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal für Detailansicht -->
<link rel="stylesheet" href="../includes/teacher_dashboard/assets/css/modal-styles.css">
<div class="modal fade" id="resultDetailModal" tabindex="-1" aria-labelledby="resultDetailModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="resultDetailModalLabel">Testergebnis Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body" id="resultDetailContent">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Lade...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #testFilter option {
        white-space: pre;
        font-family: Arial, sans-serif;
    }
    .test-code {
        font-weight: bold;
        font-size: 1.1em;
    }
    .test-title {
        padding-left: 12px;
        color: #666;
        font-size: 0.9em;
    }
    .no-attempts {
        color: #999 !important;
    }
    .no-attempts .test-title {
        color: #999 !important;
    }
    .dropdown-item {
        padding: 0.5rem 1rem;
    }
    .dropdown-menu {
        max-height: 400px;
        overflow-y: auto;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Debug: test_results_view.php wird geladen - Version vom ' + new Date().toLocaleString('de-DE'));
    
    const resultDetailModal = document.getElementById('resultDetailModal');
    const modalInstance = new bootstrap.Modal(resultDetailModal);
    let lastFocusedElement = null;

    // Event-Listener für die Detail-Buttons
    document.querySelectorAll('.view-result').forEach(button => {
        button.addEventListener('click', async function() {
            const folder = this.dataset.folder;
            const file = this.dataset.file;
            lastFocusedElement = this; // Speichere den Button für späteren Fokus
            
            console.log('Debug: Versuche Datei zu laden:', {
                folder: folder,
                file: file,
                url: '../includes/teacher_dashboard/load_test_preview.php'
            });
            
            // Zeige Modal
            modalInstance.show();
            
            try {
                // Lade die XML-Datei und generiere die Vorschau
                const response = await fetch('../includes/teacher_dashboard/load_test_preview.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `folder=${encodeURIComponent(folder)}&file=${encodeURIComponent(file)}`
                });
                
                if (!response.ok) {
                    throw new Error('Netzwerkfehler beim Laden der Vorschau');
                }
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || 'Fehler beim Laden der Vorschau');
                }
                
                // Aktualisiere Modal-Inhalt
                const modalContent = document.getElementById('resultDetailContent');
                modalContent.innerHTML = data.html;
                
            } catch (error) {
                console.error('Fehler:', error);
                document.getElementById('resultDetailContent').innerHTML = `
                    <div class="alert alert-danger">
                        Fehler beim Laden der Vorschau: ${error.message}
                    </div>
                `;
            }
        });
    });

    // Event-Listener für Modal-Events
    resultDetailModal.addEventListener('hidden.bs.modal', function () {
        // Setze Fokus zurück auf den letzten Button
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
        // Leere den Modal-Inhalt
        document.getElementById('resultDetailContent').innerHTML = `
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Lade...</span>
                </div>
            </div>
        `;
    });

    // Verhindern, dass der Fokus im Modal bleibt, wenn es geschlossen wird
    resultDetailModal.addEventListener('hide.bs.modal', function () {
        const focusedElement = document.activeElement;
        if (this.contains(focusedElement)) {
            lastFocusedElement.focus();
        }
    });

    // Filter-Funktionalität
    const studentFilter = document.getElementById('studentFilter');
    const dateFilter = document.getElementById('dateFilter');
    const testFilter = document.getElementById('testFilterBtn');

    // Verfügbare Daten aus PHP
    const availableStudents = <?php echo !empty($studentListJson) ? $studentListJson : '[]'; ?>;
    const availableDates = <?php echo !empty($testDatesJson) ? $testDatesJson : '[]'; ?>;
    const availableTests = <?php echo !empty($uniqueTestsJson) ? $uniqueTestsJson : '{}'; ?>;

    // Initialisiere Flatpickr für das Datumsfeld
    if (dateFilter) {
        const datePicker = flatpickr(dateFilter, {
            dateFormat: "Y-m-d",
            enable: availableDates,
            inline: false,
            monthSelectorType: "static",
            locale: "de",
            onChange: function(selectedDates) {
                if (typeof applyFilters === 'function') {
                    applyFilters();
                }
            }
        });
    }

    // Autovervollständigung für Schülernamen
    studentFilter.addEventListener('input', function(e) {
        const value = e.target.value.toLowerCase();
        if (!value) return;

        const matchingStudents = availableStudents.filter(student => 
            student.toLowerCase().includes(value)
        );

        // Aktualisiere die Datalist
        const datalist = document.getElementById('studentsList');
        datalist.innerHTML = matchingStudents
            .map(student => `<option value="${student}">`)
            .join('');
    });

    function applyFilters() {
        const studentValue = studentFilter.value.toLowerCase();
        const dateValue = dateFilter.value;
        const selectedTest = testFilter.textContent.trim();
        
        document.querySelectorAll('.result-group').forEach(group => {
            const testTitle = group.dataset.testTitle;
            const testDate = group.dataset.testDate;
            let groupShouldShow = true;

            // Test-Filter
            if (selectedTest !== 'Alle Tests') {
                const [code, title] = selectedTest.split(' - ');
                if (title && testTitle !== title) {
                    groupShouldShow = false;
                }
            }

            // Zeilen in der Gruppe filtern
            const rows = group.querySelectorAll('.result-row');
            let visibleRows = 0;

            rows.forEach(row => {
                const student = row.dataset.student.toLowerCase();
                const date = row.dataset.date;
                let shouldShow = true;

                // Schüler-Filter
                if (studentValue && !student.includes(studentValue)) {
                    shouldShow = false;
                }

                // Datum-Filter
                if (dateValue && date !== dateValue) {
                    shouldShow = false;
                }

                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleRows++;
            });

            // Gruppe nur anzeigen, wenn sie sichtbare Zeilen hat und den Test-Filter erfüllt
            group.style.display = (visibleRows > 0 && groupShouldShow) ? '' : 'none';
        });
    }

    // Event-Listener für Filter
    studentFilter.addEventListener('input', applyFilters);
    testFilter.addEventListener('change', applyFilters);

    // Debounce-Funktion für die Suche
    let timeout = null;
    studentFilter.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(applyFilters, 300);
    });

    // Dropdown-Funktionalität
    const dropdownItems = document.querySelectorAll('.dropdown-menu .dropdown-item');
    
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.dataset.value;
            const code = this.dataset.code;
            
            // Update button text
            testFilter.textContent = value ? `${code} - ${value}` : 'Alle Tests';
            
            // Trigger filter update
            applyFilters();
        });
    });
});
</script> 