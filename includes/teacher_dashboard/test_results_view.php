<?php
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Überprüfe, ob es sich um eine AJAX-Anfrage handelt
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

writeLog("Request-Typ: " . ($isAjax ? "AJAX" : "Normal HTML"));
writeLog("GET-Parameter: " . print_r($_GET, true));

// Bei AJAX-Anfragen keine HTML-Ausgabe erzeugen
if ($isAjax) {
    // Vermeide PHP-Warnungen und Fehler in der Ausgabe
    ob_start(); 
}

// Lade die Ergebnisse aus der Datenbank
$allResults = [];
try {
    writeLog("Stelle Verbindung zur Datenbank her");
    $db = DatabaseConfig::getInstance()->getConnection();
    
    // Prüfe die Verbindung
    try {
        $testQuery = $db->query("SELECT 1");
        if ($testQuery) {
            writeLog("Datenbankverbindung erfolgreich getestet");
        } else {
            writeLog("Datenbankverbindungstest fehlgeschlagen");
        }
    } catch (Exception $e) {
        writeLog("Fehler beim Testen der Datenbankverbindung: " . $e->getMessage());
    }
    
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
    writeLog("Erstelle SQL-Abfrage mit Filtern");

    if (!empty($selectedTest)) {
        $sql .= " AND t.access_code = ?";
        $params[] = $selectedTest;
        writeLog("Filter: Test = " . $selectedTest);
    }
    
    if (!empty($startDate)) {
        $sql .= " AND DATE(ta.completed_at) >= ?";
        $params[] = $startDate;
        writeLog("Filter: Startdatum = " . $startDate);
    }
    
    if (!empty($endDate)) {
        $sql .= " AND DATE(ta.completed_at) <= ?";
        $params[] = $endDate;
        writeLog("Filter: Enddatum = " . $endDate);
    }
    
    if (!empty($searchTerm)) {
        $sql .= " AND (ta.student_name LIKE ? OR t.title LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        writeLog("Filter: Suchbegriff = " . $searchTerm);
    }
    
    $sql .= " ORDER BY ta.completed_at DESC, t.access_code";
    writeLog("Vollständige SQL-Abfrage: " . $sql);
    writeLog("Parameter: " . print_r($params, true));
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Abfrage ausgeführt, " . count($dbResults) . " Ergebnisse gefunden");
    
    foreach ($dbResults as $result) {
        writeLog("XML-Pfad aus Datenbank: " . $result['fileName']);
        
        $allResults[] = [
            'testTitle' => $result['testTitle'],
            'studentName' => $result['studentName'],
            'date' => date('d.m.Y H:i', strtotime($result['date'])),
            'accessCode' => $result['access_code'],
            'fileName' => $result['fileName'],
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

    if ($isAjax) {
        // Verwerfe alle bisherigen Ausgaben
        ob_end_clean();
        
        writeLog("Sende AJAX-Antwort mit " . count($allResults) . " Ergebnissen");
        
        // Ausführlichere Debug-Ausgabe
        if (count($allResults) > 0) {
            writeLog("Beispielergebnis: " . print_r($allResults[0], true));
        }
        
        // Cache-Header deaktivieren
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'results' => $allResults,
            'count' => count($allResults),
            'timestamp' => time()
        ];
        
        echo json_encode($response);
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

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testergebnisse</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <style>
        .test-code {
            font-weight: bold;
            color: #0d6efd;
            display: inline-block;
        }
        .test-title {
            margin-left: 5px;
            display: inline-block;
        }
        .no-attempts {
            color: #6c757d;
            font-style: italic;
        }
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
            white-space: normal;
        }
        .result-group .card-header {
            background-color: #0d6efd;
            color: white;
        }
        .btn-info {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: #000;
        }
        .btn-info:hover {
            background-color: #31d2f2;
            border-color: #25cff2;
            color: #000;
        }
    </style>
</head>

<!-- HTML und JavaScript Code -->
<div class="container mt-4">
    <h2>Testergebnisse</h2>
    
    <?php if (empty($allTests)): ?>
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
                                <li><a class="dropdown-item active" href="#" data-value="" data-code="">Alle Tests</a></li>
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
            <!-- Hier werden die gefilterten Ergebnisse angezeigt -->
            <?php if (!empty($groupedResults)): ?>
                <?php foreach ($groupedResults as $group): ?>
                    <div class="card mb-4 result-group">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                [<?php echo htmlspecialchars($group['accessCode']); ?>] - 
                                <?php echo htmlspecialchars($group['testTitle']); ?>
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
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['studentName']); ?></td>
                                                <td><?php echo htmlspecialchars($result['date']); ?></td>
                                                <td><?php echo htmlspecialchars($result['points_achieved']); ?>/<?php echo htmlspecialchars($result['points_maximum']); ?></td>
                                                <td><?php echo htmlspecialchars($result['percentage']); ?>%</td>
                                                <td><?php echo htmlspecialchars($result['grade']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="showResults('<?php echo htmlspecialchars($result['fileName']); ?>')">
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
            <?php else: ?>
                <div class="alert alert-info mt-3">
                    <p>Keine Ergebnisse gefunden. Wenn Sie gerade einen Test abgeschlossen haben, wählen Sie bitte den Filter "Alle Tests".</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/de.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM geladen, initialisiere Filterkomponenten');
    
    // Prüfe, ob die erforderlichen Elemente existieren
    const studentFilter = document.getElementById('studentFilter');
    const dateFilter = document.getElementById('dateFilter');
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    
    if (!studentFilter || !dateFilter) {
        console.error('Filter-Elemente nicht gefunden!');
        return;
    }
    
    console.log('Filter-Elemente gefunden, initialisiere...');
    
    // Initialisiere Flatpickr
    if (typeof flatpickr === 'function') {
        flatpickr("#dateFilter", {
            dateFormat: "Y-m-d",
            locale: "de",
            allowInput: true,
            onChange: function(selectedDates) {
                console.log('Datum geändert:', selectedDates);
                updateResults();
            }
        });
        console.log('Flatpickr initialisiert');
    } else {
        console.error('Flatpickr nicht gefunden!');
    }

    // Event-Listener für Filter
    studentFilter.addEventListener('input', debounce(function() {
        console.log('Schülerfilter geändert:', this.value);
        updateResults();
    }, 300));
    
    // Event-Listener für Test-Dropdown
    if (dropdownItems.length > 0) {
        console.log('Dropdown-Items gefunden:', dropdownItems.length);
        
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Test ausgewählt:', this.dataset.code);
                
                // Entferne active-Klasse von allen Items
                document.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                
                // Füge active-Klasse zum geklickten Item hinzu
                this.classList.add('active');
                
                const testFilterBtn = document.getElementById('testFilterBtn');
                if (testFilterBtn) {
                    testFilterBtn.textContent = this.querySelector('.test-title')?.textContent || 'Alle Tests';
                }
                
                updateResults();
            });
        });
    } else {
        console.error('Keine Dropdown-Items gefunden!');
    }

    // Initial load
    console.log('Lade initiale Ergebnisse');
    updateResults();
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

function updateResults() {
    const studentFilter = document.getElementById('studentFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    const activeTest = document.querySelector('.dropdown-item.active');
    const selectedTest = activeTest ? activeTest.dataset.code : '';
    
    console.log('Update Ergebnisse mit Filtern:', {
        student: studentFilter,
        date: dateFilter,
        test: selectedTest
    });
    
    const queryParams = new URLSearchParams({
        search: studentFilter || '',
        start_date: dateFilter || '',
        test: selectedTest || ''
    });
    
    // Aktuelle URL und Pfad
    const currentUrl = window.location.href.split('?')[0];
    console.log('Basis-URL:', currentUrl);
    console.log('Sende AJAX-Anfrage:', currentUrl + '?' + queryParams.toString());
    
    // Zeige Ladestatus an
    const container = document.getElementById('filteredResults');
    container.innerHTML = '<div class="alert alert-info">Ergebnisse werden geladen...</div>';
    
    // Zufälliger Cache-Buster
    queryParams.append('_', new Date().getTime());
    
    fetch(currentUrl + '?' + queryParams.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        console.log('Antwort erhalten, Status:', response.status);
        if (!response.ok) {
            throw new Error('Netzwerkantwort nicht ok');
        }
        
        // Versuche den Response-Text zu loggen
        return response.text().then(text => {
            console.log('Antwort-Text (erste 100 Zeichen):', text.substring(0, 100));
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON-Parse-Fehler:', e);
                console.error('Erhaltener Text statt JSON:', text.substring(0, 300));
                throw new Error('Ungültiges JSON in der Antwort: ' + e.message);
            }
        });
    })
    .then(data => {
        console.log('Daten erhalten:', data);
        if (data.success) {
            updateResultsDisplay(data.results);
        } else {
            console.error('Fehler beim Laden der Ergebnisse:', data.error);
            document.getElementById('filteredResults').innerHTML = 
                '<div class="alert alert-danger">Fehler beim Laden der Ergebnisse: ' + data.error + '</div>';
        }
    })
    .catch(error => {
        console.error('Fehler beim Laden der Ergebnisse:', error);
        document.getElementById('filteredResults').innerHTML = 
            '<div class="alert alert-danger">Fehler beim Laden der Ergebnisse: ' + error.message + '</div>';
    });
}

function updateResultsDisplay(results) {
    console.log('Aktualisiere Ergebnisanzeige mit', results.length, 'Ergebnissen');
    
    const container = document.getElementById('filteredResults');
    container.innerHTML = '';

    if (!results || results.length === 0) {
        console.log('Keine Ergebnisse gefunden');
        container.innerHTML = '<div class="alert alert-info">Keine Ergebnisse gefunden.</div>';
        return;
    }

    // Debug-Ausgabe aller Ergebnisse
    console.log('Erhaltene Ergebnisse:', results);

    // Gruppiere Ergebnisse nach Test
    const groupedResults = {};
    results.forEach(result => {
        // Überprüfe, ob alle erforderlichen Eigenschaften vorhanden sind
        if (!result.testTitle || !result.accessCode) {
            console.error('Ungültiges Ergebnisobjekt:', result);
            return;
        }

        const key = `${result.testTitle}_${result.accessCode}`;
        if (!groupedResults[key]) {
            groupedResults[key] = {
                testTitle: result.testTitle,
                accessCode: result.accessCode,
                testDate: result.testDate || '',
                results: []
            };
        }
        groupedResults[key].results.push(result);
    });
    
    console.log('Gruppierte Ergebnisse:', Object.keys(groupedResults).length, 'Gruppen');

    // Erstelle HTML für jede Gruppe
    Object.values(groupedResults).forEach(group => {
        console.log('Erstelle Gruppe für:', group.testTitle, 'mit', group.results.length, 'Ergebnissen');
        
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
                                    <td>${result.studentName || ''}</td>
                                    <td>${result.date || ''}</td>
                                    <td>${result.points_achieved || 0}/${result.points_maximum || 0}</td>
                                    <td>${result.percentage || 0}%</td>
                                    <td>${result.grade || ''}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showResults('${result.fileName || ''}')">
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
    
    console.log('Ergebnisanzeige aktualisiert');
}

function showResults(filename) {
    console.log('Zeige Details für:', filename);
    window.location.href = `show_results.php?file=${encodeURIComponent(filename)}`;
}
</script>

<?php endif; ?> 