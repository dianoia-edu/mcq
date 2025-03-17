<?php
session_start();

// Überprüfe, ob der Benutzer als Lehrer angemeldet ist
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Zugriff verweigert. Bitte melden Sie sich als Lehrer an.</div>';
    exit;
}

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = dirname(__DIR__) . '/logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("load_test_results.php aufgerufen");

// Lade die Datenbankkonfiguration
require_once dirname(__DIR__) . '/includes/database_config.php';

// Lade die Ergebnisse aus der Datenbank
$allResults = [];
try {
    $db = DatabaseConfig::getInstance()->getConnection();
    
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
            t.created_at,
            DATE(t.created_at) as testDate,
            ts.attempts_count,
            ts.average_percentage
        FROM test_attempts ta
        JOIN tests t ON ta.test_id = t.test_id
        LEFT JOIN test_statistics ts ON t.test_id = ts.test_id
        ORDER BY t.title ASC, ta.completed_at DESC
    ");
    
    $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Anzahl der geladenen Testergebnisse: " . count($allResults));
    
    // Lade alle Tests für den Filter
    $testStmt = $db->query("
        SELECT 
            t.test_id,
            t.access_code,
            t.title,
            t.created_at,
            COALESCE(ts.attempts_count, 0) as attempt_count
        FROM tests t
        LEFT JOIN test_statistics ts ON t.test_id = ts.test_id
        ORDER BY t.created_at DESC
    ");
    
    $allTests = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Anzahl der geladenen Tests: " . count($allTests));
    
} catch (Exception $e) {
    writeLog("Fehler beim Laden aus der Datenbank: " . $e->getMessage());
    $allResults = []; // Leeres Array bei Datenbankfehler
    $allTests = [];
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
    $key = $result['testTitle'] . '_' . $result['access_code'];
    if (!isset($groupedResults[$key])) {
        $groupedResults[$key] = [
            'testTitle' => $result['testTitle'],
            'accessCode' => $result['access_code'],
            'testDate' => $result['testDate'],
            'results' => []
        ];
    }
    $groupedResults[$key]['results'][] = $result;
}

// Sammle alle einzigartigen Namen und Daten
$uniqueStudents = [];
$testDates = [];

foreach ($allResults as $result) {
    $uniqueStudents[$result['studentName']] = true;
    $testDates[$result['testDate']] = true;
}

$uniqueStudents = array_keys($uniqueStudents);
sort($uniqueStudents);
$testDates = array_keys($testDates);
sort($testDates);

// Konvertiere Daten in JSON für JavaScript
$studentListJson = json_encode($uniqueStudents);
$testDatesJson = json_encode($testDates);
$uniqueTestsJson = json_encode($allTests);

// Beginne mit der HTML-Ausgabe
?>

<div class="container-fluid">
    <?php if (empty($allResults)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Keine Testergebnisse gefunden.
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filter</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="studentFilter" class="form-label">Schüler</label>
                        <input type="text" class="form-control" id="studentFilter" placeholder="Schülername eingeben...">
                    </div>
                    <div class="col-md-4">
                        <label for="dateFilter" class="form-label">Datum</label>
                        <input type="text" class="form-control" id="dateFilter" placeholder="Datum auswählen">
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
                                        <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($result['date']))); ?></td>
                                        <td><?php echo htmlspecialchars($result['points_achieved']); ?>/<?php echo htmlspecialchars($result['points_maximum']); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($result['percentage'], 1)); ?>%</td>
                                        <td><?php echo htmlspecialchars($result['grade']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-result" 
                                                    data-folder="<?php echo htmlspecialchars($result['access_code'] . '_' . $result['testDate']); ?>"
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
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal für Detailansicht -->
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
    const studentFilter = document.getElementById('studentFilter');
    const dateFilter = document.getElementById('dateFilter');
    const testFilter = document.getElementById('testFilterBtn');
    
    // Prüfe, ob die Elemente existieren, bevor wir mit ihnen arbeiten
    if (!resultDetailModal || !studentFilter || !dateFilter || !testFilter) {
        console.log('Debug: Einige Elemente wurden nicht gefunden, möglicherweise ist der Tab nicht aktiv');
        return; // Beende die Ausführung, wenn Elemente fehlen
    }
    
    const modalInstance = new bootstrap.Modal(resultDetailModal);
    let lastFocusedElement = null;

    // Event-Listener für die Detail-Buttons
    document.querySelectorAll('.view-result').forEach(button => {
        button.addEventListener('click', function() {
            lastFocusedElement = this;
            const folder = this.dataset.folder;
            const file = this.dataset.file;
            
            // Zeige das Modal
            modalInstance.show();
            
            // Lade die Detailansicht
            fetch(`view_test_result.php?file=${encodeURIComponent(file)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Netzwerkantwort war nicht ok');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('resultDetailContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Fehler:', error);
                    const modalContent = document.getElementById('resultDetailContent');
                    if (modalContent) {
                        modalContent.innerHTML = `
                            <div class="alert alert-danger">
                                Fehler beim Laden der Vorschau: ${error.message}
                            </div>
                        `;
                    }
                });
        });
    });

    // Event-Listener für Modal-Events
    resultDetailModal.addEventListener('hidden.bs.modal', function () {
        // Setze Fokus zurück auf den letzten Button
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
        // Leere den Modal-Inhalt
        const modalContent = document.getElementById('resultDetailContent');
        if (modalContent) {
            modalContent.innerHTML = `
                <div class="d-flex justify-content-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Lade...</span>
                    </div>
                </div>
            `;
        }
    });

    // Verhindern, dass der Fokus im Modal bleibt, wenn es geschlossen wird
    resultDetailModal.addEventListener('hide.bs.modal', function () {
        const focusedElement = document.activeElement;
        if (this.contains(focusedElement) && lastFocusedElement) {
            lastFocusedElement.focus();
        }
    });

    // Event-Listener für Test-Filter-Dropdown
    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.dataset.value;
            const code = this.dataset.code;
            testFilter.textContent = value ? `${code} - ${value}` : 'Alle Tests';
            applyFilters();
        });
    });

    // Funktion zum Anwenden der Filter
    function applyFilters() {
        if (!studentFilter || !dateFilter || !testFilter) return;
        
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
    if (studentFilter) {
        studentFilter.addEventListener('input', applyFilters);
    }
    
    if (dateFilter) {
        dateFilter.addEventListener('change', applyFilters);
    }
});
</script> 