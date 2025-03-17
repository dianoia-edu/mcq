<?php
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Prüfe ob es sich um einen AJAX-Request handelt
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");

    // Hole Filter-Parameter
    $selectedTest = $_GET['test'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $searchTerm = $_GET['search'] ?? '';

    // Baue die SQL-Query
    $sql = "
        SELECT 
            t.title as testTitle,
            ta.student_name as studentName,
            ta.completed_at as date,
            t.access_code,
            ta.xml_file_path as fileName,
            ta.points_achieved,
            ta.points_maximum,
            ta.percentage,
            ta.grade,
            ta.created_at
        FROM test_attempts ta
        JOIN tests t ON ta.test_id = t.test_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($selectedTest)) {
        $sql .= " AND t.access_code = ?";
        $params[] = $selectedTest;
    }
    
    if (!empty($startDate)) {
        $sql .= " AND DATE(ta.completed_at) >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $sql .= " AND DATE(ta.completed_at) <= ?";
        $params[] = $endDate;
    }
    
    if (!empty($searchTerm)) {
        $sql .= " AND (ta.student_name LIKE ? OR t.title LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }
    
    $sql .= " ORDER BY ta.completed_at DESC, t.access_code";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allResults = [];
    foreach ($dbResults as $result) {
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
        
        writeLog("Konstruierte Pfade für " . $result['studentName'] . ":");
        writeLog(" - Ordner: " . $result['access_code'] . '_' . date('Y-m-d', strtotime($result['date'])));
        writeLog(" - Datei: " . basename($result['fileName']));
    }
    writeLog("Ergebnisse aus Datenbank geladen: " . count($allResults));

    // Lade alle Tests für das Dropdown
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

    // Bei AJAX-Request nur die Daten zurückgeben
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'results' => $allResults
        ]);
        exit;
    }

} catch (Exception $e) {
    writeLog("Fehler beim Laden aus der Datenbank: " . $e->getMessage());
    $allResults = [];
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

if (!$isAjax):
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
    
    <!-- Filter-Formular -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="testSelect" class="form-label">Test auswählen</label>
                    <select class="form-select" id="testSelect" name="test">
                        <option value="">Alle Tests</option>
                        <?php foreach ($allTests as $test): ?>
                            <option value="<?php echo htmlspecialchars($test['access_code']); ?>"
                                    <?php echo ($selectedTest === $test['access_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($test['access_code'] . ' - ' . $test['title']); ?>
                                (<?php echo $test['attempt_count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="startDate" class="form-label">Von Datum</label>
                    <input type="date" class="form-control" id="startDate" name="start_date" 
                           value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-3">
                    <label for="endDate" class="form-label">Bis Datum</label>
                    <input type="date" class="form-control" id="endDate" name="end_date" 
                           value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-3">
                    <label for="searchInput" class="form-label">Suche</label>
                    <input type="text" class="form-control" id="searchInput" name="search" 
                           placeholder="Name oder Titel..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </form>
        </div>
    </div>

    <!-- Ergebnistabelle -->
    <div class="table-responsive">
        <table class="table table-striped" id="resultsTable">
            <thead>
                <tr>
                    <th>Test</th>
                    <th>Name</th>
                    <th>Datum</th>
                    <th>Punkte</th>
                    <th>Note</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody id="resultsBody">
                <!-- Wird durch JavaScript gefüllt -->
            </tbody>
        </table>
    </div>
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
    
    // Prüfe, ob die erforderlichen Elemente existieren
    const resultDetailModal = document.getElementById('resultDetailModal');
    const studentFilter = document.getElementById('studentFilter');
    const dateFilter = document.getElementById('dateFilter');
    const testFilter = document.getElementById('testFilterBtn');

    // Nur fortfahren, wenn wir uns im Testergebnisse-Tab befinden
    if (!resultDetailModal || !studentFilter || !dateFilter || !testFilter) {
        console.log('Debug: Nicht im Testergebnisse-Tab, überspringe Initialisierung');
        return;
    }

    const modalInstance = new bootstrap.Modal(resultDetailModal);
    let lastFocusedElement = null;

    // Event-Listener für die Detail-Buttons
    document.querySelectorAll('.view-result').forEach(button => {
        button.addEventListener('click', async function() {
            const folder = this.dataset.folder;
            const file = this.dataset.file;
            lastFocusedElement = this;
            
            console.log('Debug: Versuche Datei zu laden:', {
                folder: folder,
                file: file,
                url: '../includes/teacher_dashboard/load_test_preview.php'
            });
            
            modalInstance.show();
            
            try {
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
                
                const modalContent = document.getElementById('resultDetailContent');
                if (modalContent) {
                    modalContent.innerHTML = data.html;
                }
                
            } catch (error) {
                console.error('Fehler:', error);
                const modalContent = document.getElementById('resultDetailContent');
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="alert alert-danger">
                            Fehler beim Laden der Vorschau: ${error.message}
                        </div>
                    `;
                }
            }
        });
    });

    // Event-Listener für Modal-Events
    resultDetailModal.addEventListener('hidden.bs.modal', function () {
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
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

    // Verhindern, dass der Fokus im Modal bleibt
    resultDetailModal.addEventListener('hide.bs.modal', function () {
        const focusedElement = document.activeElement;
        if (this.contains(focusedElement) && lastFocusedElement) {
            lastFocusedElement.focus();
        }
    });

    // Filter-Funktionalität
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

    // Initial load
    loadResults();
    
    // Event-Listener für Filter-Änderungen
    document.getElementById('filterForm').addEventListener('change', function() {
        loadResults();
    });
    
    document.getElementById('searchInput').addEventListener('input', debounce(function() {
        loadResults();
    }, 300));
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function loadResults() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const queryString = new URLSearchParams(formData).toString();
    
    fetch(`?${queryString}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateResultsTable(data.results);
        } else {
            console.error('Fehler beim Laden der Ergebnisse:', data.error);
        }
    })
    .catch(error => {
        console.error('Fehler beim Laden der Ergebnisse:', error);
    });
}

function updateResultsTable(results) {
    const tbody = document.getElementById('resultsBody');
    tbody.innerHTML = '';
    
    results.forEach(result => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${result.testTitle} (${result.accessCode})</td>
            <td>${result.studentName}</td>
            <td>${result.date}</td>
            <td>${result.points_achieved}/${result.points_maximum} (${result.percentage}%)</td>
            <td>${result.grade}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="showResults('${result.fileName}')">
                    Anzeigen
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function showResults(filename) {
    window.location.href = `show_results.php?file=${encodeURIComponent(filename)}`;
}
</script>

<?php endif; ?> 