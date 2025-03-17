<?php
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Prüfe ob es sich um einen AJAX-Request handelt
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Lade die Ergebnisse aus der Datenbank
$allResults = [];
try {
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");

    // Hole Filter-Parameter
    $selectedTest = $_GET['test'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $searchTerm = $_GET['search'] ?? '';

    // Debug: Überprüfe direkt die tests-Tabelle
    writeLog("Führe Test-Tabellen-Abfrage aus...");
    $testCheck = $db->query("SELECT * FROM tests");
    if ($testCheck === false) {
        writeLog("Fehler bei der Test-Tabellen-Abfrage: " . print_r($db->errorInfo(), true));
    }
    $allTests = $testCheck->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Anzahl gefundener Tests: " . count($allTests));
    writeLog("Alle Tests in der Datenbank:");
    foreach ($allTests as $test) {
        writeLog(sprintf("ID: %d, Code: %s, Titel: %s", 
            $test['test_id'], 
            $test['access_code'], 
            $test['title']
        ));
    }

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
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
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

if (!$isAjax):
?>

<!-- HTML und JavaScript Code -->
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
                        <input type="text" class="form-control" id="dateFilter">
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
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisiere Flatpickr
    flatpickr("#dateFilter", {
        dateFormat: "Y-m-d",
        locale: "de",
        allowInput: true
    });

    // Event-Listener für Filter
    document.getElementById('studentFilter').addEventListener('input', debounce(updateResults, 300));
    document.getElementById('dateFilter').addEventListener('change', updateResults);
    
    // Event-Listener für Test-Dropdown
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('testFilterBtn').textContent = this.querySelector('.test-title')?.textContent || 'Alle Tests';
            updateResults();
        });
    });

    // Initial load
    loadResults();
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
    const studentFilter = document.getElementById('studentFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    const selectedTest = document.querySelector('.dropdown-item.active')?.dataset?.code || '';
    
    const queryParams = new URLSearchParams({
        search: studentFilter,
        start_date: dateFilter,
        test: selectedTest
    });
    
    fetch(`?${queryParams.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateResultsDisplay(data.results);
        } else {
            console.error('Fehler beim Laden der Ergebnisse:', data.error);
        }
    })
    .catch(error => {
        console.error('Fehler beim Laden der Ergebnisse:', error);
    });
}

function updateResultsDisplay(results) {
    const container = document.getElementById('filteredResults');
    container.innerHTML = '';

    // Gruppiere Ergebnisse nach Test
    const groupedResults = {};
    results.forEach(result => {
        const key = `${result.testTitle}_${result.accessCode}`;
        if (!groupedResults[key]) {
            groupedResults[key] = {
                testTitle: result.testTitle,
                accessCode: result.accessCode,
                testDate: result.testDate,
                results: []
            };
        }
        groupedResults[key].results.push(result);
    });

    // Erstelle HTML für jede Gruppe
    Object.values(groupedResults).forEach(group => {
        const card = document.createElement('div');
        card.className = 'card mb-4 result-group';
        card.innerHTML = `
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    [${group.accessCode}] - 
                    ${group.testTitle}
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
                            ${group.results.map(result => `
                                <tr>
                                    <td>${result.studentName}</td>
                                    <td>${result.date}</td>
                                    <td>${result.points_achieved}/${result.points_maximum}</td>
                                    <td>${result.percentage}%</td>
                                    <td>${result.grade}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showResults('${result.fileName}')">
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function showResults(filename) {
    window.location.href = `show_results.php?file=${encodeURIComponent(filename)}`;
}
</script>

<?php endif; ?> 