<?php
// Überprüfen Sie, ob dies eine AJAX-Anfrage ist und geben Sie nur JSON zurück
// Dies sollte vor allen anderen Anweisungen stehen
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // Alle bisherigen Ausgabe-Puffer entfernen
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Starte einen neuen sauberen Puffer
    ob_start();
    
    // Setze Header für reines JSON
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // AJAX-Verarbeitung später im Code
    $isAjax = true;
} else {
    $isAjax = false;
}

require_once __DIR__ . '/../../includes/database_config.php';

// Synchronisiere zuerst die Datenbank, bevor Ergebnisse geladen werden
if (!$isAjax) {
    writeLog("Automatische Synchronisation der Datenbank wird durchgeführt");
    try {
        require_once __DIR__ . '/sync_database_helper.php';
        syncDatabase();
        writeLog("Automatische Synchronisation erfolgreich abgeschlossen");
    } catch (Exception $e) {
        writeLog("Fehler bei der automatischen Synchronisation: " . $e->getMessage());
        // Fahre trotz Fehler fort
    }
}

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("Request-Typ: " . ($isAjax ? "AJAX" : "Normal HTML"));
writeLog("GET-Parameter: " . print_r($_GET, true));

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

    // Prüfe, ob die created_at-Spalte in der tests-Tabelle existiert
    try {
        $tableCheckQuery = $db->query("SHOW COLUMNS FROM tests LIKE 'created_at'");
        $hasCreatedAtColumn = $tableCheckQuery->rowCount() > 0;
        writeLog("created_at-Spalte in der tests-Tabelle " . ($hasCreatedAtColumn ? "gefunden" : "NICHT GEFUNDEN"));
        
        if (!$hasCreatedAtColumn) {
            writeLog("WICHTIG: Die created_at-Spalte fehlt in der tests-Tabelle. Es wird ein Standardwert verwendet.");
        }
    } catch (Exception $e) {
        writeLog("Fehler beim Prüfen der Tabellenspalten: " . $e->getMessage());
        $hasCreatedAtColumn = false;
    }
    
    // Hole Filter-Parameter
    $selectedTest = $_GET['test'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $searchTerm = $_GET['search'] ?? '';
    
    // Debug: Überprüfe direkt die tests-Tabelle
    writeLog("Führe Test-Tabellen-Abfrage aus...");
    $testCheck = $db->query("SELECT test_id, access_code, title, created_at FROM tests");
    if ($testCheck === false) {
        writeLog("Fehler bei der Test-Tabellen-Abfrage: " . print_r($db->errorInfo(), true));
    }
    $allTests = $testCheck->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Anzahl gefundener Tests: " . count($allTests));
    writeLog("Alle Tests in der Datenbank:");
    foreach ($allTests as $test) {
        writeLog(sprintf("ID: %d, Code: %s, Titel: %s, Created At: %s", 
            $test['test_id'], 
            $test['access_code'], 
            $test['title'],
            isset($test['created_at']) ? $test['created_at'] : 'NICHT VORHANDEN'
        ));
        
        // Wenn created_at nicht vorhanden ist, setze einen Standardwert
        if (!isset($test['created_at'])) {
            $test['created_at'] = date('Y-m-d H:i:s'); // Aktuelles Datum als Fallback
        }
    }

    // Baue die SQL-Query
    $sql = "
        SELECT 
            t.title as testTitle,
            ta.student_name as studentName,
            ta.completed_at as date,
            t.access_code,
            " . ($hasCreatedAtColumn ? "t.created_at," : "") . "
            ta.xml_file_path as fileName,
            ta.points_achieved,
            ta.points_maximum,
            ta.percentage,
            ta.grade
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
    
    // Hilfsfunktion zum sicheren Abrufen des created_at-Felds
    function getCreatedAt($result) {
        if (isset($result['created_at']) && !empty($result['created_at'])) {
            return $result['created_at'];
        } else {
            return date('Y-m-d H:i:s'); // Aktuelles Datum als Fallback
        }
    }
    
    foreach ($dbResults as $result) {
        writeLog("XML-Pfad aus Datenbank: " . $result['fileName']);
        
        $allResults[] = [
            'testTitle' => $result['testTitle'],
            'studentName' => $result['studentName'],
            'date' => date('d.m.Y H:i', strtotime($result['date'])),
            'accessCode' => $result['access_code'],
            'fileName' => $result['fileName'],
            'created_at' => getCreatedAt($result),
            'testDate' => date('Y-m-d', strtotime($result['date'])),
            'points_achieved' => $result['points_achieved'],
            'points_maximum' => $result['points_maximum'],
            'percentage' => $result['percentage'],
            'grade' => $result['grade']
        ];
        
        writeLog("Konstruierte Pfade für " . $result['studentName'] . ":");
        writeLog(" - Ordner: " . $result['access_code'] . '_' . date('Y-m-d', strtotime($result['date'])));
        writeLog(" - Datei: " . basename($result['fileName']));
    }
    writeLog("Ergebnisse aus Datenbank geladen: " . count($allResults));

    // Lade alle Tests aus der Datenbank
    $sql = "
        SELECT 
            t.test_id,
            t.access_code,
            t.title,
            " . ($hasCreatedAtColumn ? "t.created_at," : "") . "
            COUNT(ta.attempt_id) as attempt_count
        FROM tests t
        LEFT JOIN test_attempts ta ON t.test_id = ta.test_id
        GROUP BY t.test_id, t.access_code, t.title" . ($hasCreatedAtColumn ? ", t.created_at" : "") . "
        ORDER BY t.access_code
    ";
    writeLog("SQL für Testliste: " . $sql);
    
    $allTestsQuery = $db->query($sql);
    $allTests = $allTestsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Standardwert für created_at setzen, falls nicht vorhanden
    foreach ($allTests as &$test) {
        if (!isset($test['created_at']) || empty($test['created_at'])) {
            $test['created_at'] = date('Y-m-d H:i:s'); // Aktuelles Datum als Fallback
            $testId = array_key_exists('test_id', $test) ? $test['test_id'] : 'unbekannt';
            writeLog("Test ohne created_at gefunden (ID: {$testId}), Standardwert gesetzt: " . $test['created_at']);
        } else {
            $testId = array_key_exists('test_id', $test) ? $test['test_id'] : 'unbekannt';
            writeLog("Test mit created_at gefunden (ID: {$testId}): " . $test['created_at']);
        }
    }

    if ($isAjax) {
        // Verwerfe alle bisherigen Ausgaben
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        writeLog("Sende AJAX-Antwort mit " . count($allResults) . " Ergebnissen");
        
        // Ausführlichere Debug-Ausgabe
        if (count($allResults) > 0) {
            writeLog("Beispielergebnis: " . print_r($allResults[0], true));
        }
        
        // Cache-Header deaktivieren
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        $response = [
            'success' => true,
            'results' => $allResults,
            'count' => count($allResults),
            'timestamp' => time()
        ];
        
        echo json_encode($response);
        
        // Beende die Ausführung vollständig
        exit();
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
            'lastTestDate' => $result['date'],
            'results' => []
        ];
    } else {
        // Aktualisiere das Datum des letzten Tests, wenn das aktuelle Ergebnis neuer ist
        $currentDate = strtotime($result['date']);
        $lastDate = strtotime($groupedResults[$key]['lastTestDate']);
        if ($currentDate > $lastDate) {
            $groupedResults[$key]['lastTestDate'] = $result['date'];
        }
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
            'created_at' => $result['date']
        ];
    }
}

$uniqueStudents = array_keys($uniqueStudents);
sort($uniqueStudents);
$testDates = array_keys($testDates);
sort($testDates);

// Sortiere Tests nach Erstellungsdatum absteigend
uasort($uniqueTests, function($a, $b) {
    // Sichere Abfrage des created_at-Felds
    $dateA = array_key_exists('created_at', $a) && !empty($a['created_at']) ? $a['created_at'] : '1970-01-01';
    $dateB = array_key_exists('created_at', $b) && !empty($b['created_at']) ? $b['created_at'] : '1970-01-01';
    
    // Debug-Ausgabe
    writeLog("Test A (ID: " . (array_key_exists('test_id', $a) ? $a['test_id'] : 'unbekannt') . 
           ", Name: " . (array_key_exists('title', $a) ? $a['title'] : 'unbekannt') . 
           "): created_at = $dateA");
    writeLog("Test B (ID: " . (array_key_exists('test_id', $b) ? $b['test_id'] : 'unbekannt') . 
           ", Name: " . (array_key_exists('title', $b) ? $b['title'] : 'unbekannt') . 
           "): created_at = $dateB");
    
    return strtotime($dateB) - strtotime($dateA);
});

// Konvertiere Daten in JSON für JavaScript
$studentListJson = json_encode($uniqueStudents);
$testDatesJson = json_encode($testDates);
$uniqueTestsJson = json_encode($uniqueTests);

// JSON-Liste der verfügbaren Daten für Flatpickr
$availableDatesJson = json_encode($testDates);

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Entferne alle Tab-spezifischen Styles */
        /* Behalte nur die notwendigen Styles für Filter und Ergebnisse */
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
            color: #adb5bd;
            font-style: italic;
            opacity: 0.7;
            cursor: not-allowed;
            position: relative;
        }
        .no-attempts .test-code,
        .no-attempts .test-title,
        .no-attempts .text-muted {
            color: #adb5bd !important;
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
        /* Einheitliche Tabellenformatierung für alle Ergebnisgruppen */
        .result-group table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .result-group th, .result-group td {
            vertical-align: middle !important;
            padding: 0.75rem !important;
            box-sizing: border-box;
        }
        .result-group th:nth-child(1), .result-group td:nth-child(1) { width: 20%; } /* Schüler */
        .result-group th:nth-child(2), .result-group td:nth-child(2) { width: 20%; } /* Abgabezeitpunkt */
        .result-group th:nth-child(3), .result-group td:nth-child(3) { width: 12%; text-align: center; } /* Punkte */
        .result-group th:nth-child(4), .result-group td:nth-child(4) { width: 12%; text-align: center; } /* Prozent */
        .result-group th:nth-child(5), .result-group td:nth-child(5) { width: 12%; text-align: center; } /* Note */
        .result-group th:nth-child(6), .result-group td:nth-child(6) { width: 24%; text-align: left; } /* Aktionen */
        /* Ende der Tabellenformatierung */
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
        /* Tooltip für Tests ohne Ergebnisse */
        .no-attempts:hover::after {
            content: "Keine Ergebnisse verfügbar";
            position: absolute;
            background-color: #343a40;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
            right: 20px;
            white-space: nowrap;
        }
        /* Max-Höhe für Dropdown-Menü mit Scrollbar */
        .dropdown-menu.test-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }
        /* Stil für das Datum in der Testliste */
        .test-date {
            color: #6c757d;
            font-size: 0.9em;
            margin-left: 5px;
        }
        /* Einheitliche Ausrichtung und Breite für Details-Buttons */
        .btn-action {
            width: 100px;
            text-align: center !important;
            display: inline-block;
            margin: 0 !important;
        }
        /* Formatierung der Noten */
        .grade-good {
            font-weight: bold;
            color: #28a745;
            font-size: 1.2em;
        }
        .grade-medium {
            color: #fd7e14;
            font-weight: bold;
            font-size: 1.2em;
        }
        .grade-bad {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.2em;
        }
        /* Sortierungsstile */
        .sort-header {
            cursor: pointer;
            position: relative;
        }
        .sort-header::after {
            content: "⇅";
            display: inline-block;
            margin-left: 5px;
            opacity: 0.5;
            font-size: 0.8em;
        }
        .sort-header.sort-asc::after {
            content: "↑";
            opacity: 1;
        }
        .sort-header.sort-desc::after {
            content: "↓";
            opacity: 1;
        }
        .group-sort-container {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        .group-sort-label {
            margin-right: 10px;
            font-weight: bold;
        }
        .btn-group-sort .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        /* Deaktivierte Tage im Datepicker anpassen */
        .flatpickr-day.flatpickr-disabled, 
        .flatpickr-day.flatpickr-disabled:hover {
            color: #ccc;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .flatpickr-day.flatpickr-disabled:hover::after {
            content: "Keine Tests an diesem Tag";
            position: absolute;
            background-color: #343a40;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            z-index: 100;
        }
    </style>
</head>

<!-- HTML-Struktur ohne eigene Tab-Navigation -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Testauswertung</h3>
        </div>
            <div class="card-body">
                    <!-- Filter-Bereich -->
                    <div class="filter-section mb-4">
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
                                <input type="text" class="form-control" id="dateFilter" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="testFilterBtn" class="form-label">Test</label>
                                <div class="dropdown mb-3">
                                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="testFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                Alle Tests
                            </button>
                                    <ul class="dropdown-menu test-dropdown" aria-labelledby="testFilterBtn">
                                        <li><a class="dropdown-item active" href="#" data-code="">Alle Tests</a></li>
                                        <?php 
                                        // Sortiere Tests nach Erstellungsdatum absteigend
                                        writeLog("Sortiere Tests für Dropdown-Liste...");
                                        usort($allTests, function($a, $b) {
                                            // Sichere Abfrage des created_at-Felds
                                            $dateA = array_key_exists('created_at', $a) && !empty($a['created_at']) ? $a['created_at'] : '1970-01-01';
                                            $dateB = array_key_exists('created_at', $b) && !empty($b['created_at']) ? $b['created_at'] : '1970-01-01';
                                            
                                            // Debug-Ausgabe
                                            writeLog("Test A (ID: " . (array_key_exists('test_id', $a) ? $a['test_id'] : 'unbekannt') . 
                                                   ", Name: " . (array_key_exists('title', $a) ? $a['title'] : 'unbekannt') . 
                                                   "): created_at = $dateA");
                                            writeLog("Test B (ID: " . (array_key_exists('test_id', $b) ? $b['test_id'] : 'unbekannt') . 
                                                   ", Name: " . (array_key_exists('title', $b) ? $b['title'] : 'unbekannt') . 
                                                   "): created_at = $dateB");
                                            
                                            return strtotime($dateB) - strtotime($dateA);
                                        });
                                        writeLog("Tests sortiert, Anzahl: " . count($allTests));
                                        
                                        foreach ($allTests as $test): 
                                            $hasResults = array_key_exists('attempt_count', $test) ? $test['attempt_count'] > 0 : false;
                                            $itemClass = $hasResults ? 'dropdown-item' : 'dropdown-item no-attempts';
                                            
                                            // Sicherstellen, dass alle benötigten Schlüssel existieren
                                            $title = array_key_exists('title', $test) ? $test['title'] : 'Unbekannter Test';
                                            $accessCode = array_key_exists('access_code', $test) ? $test['access_code'] : 'Unbekannt';
                                            
                                            // Datum formatieren aus created_at
                                            $testDate = '';
                                            if (array_key_exists('created_at', $test) && !empty($test['created_at'])) {
                                                $testDate = date('d.m.y', strtotime($test['created_at']));
                                            }
                                        ?>
                                            <li>
                                                <a class="<?php echo $itemClass; ?>" 
                                           href="#" 
                                                   <?php if ($hasResults): ?>
                                                   data-value="<?php echo htmlspecialchars($title); ?>"
                                                   data-code="<?php echo htmlspecialchars($accessCode); ?>"
                                                   <?php else: ?>
                                                   style="pointer-events: none; cursor: default;"
                                                   <?php endif; ?>>
                                                    <div class="test-code"><?php echo htmlspecialchars($accessCode); ?></div>
                                                    <div class="test-title">
                                                        <?php echo htmlspecialchars($title); ?>
                                                        <?php if (!empty($testDate)): ?>
                                                        <span class="test-date">(<?php echo $testDate; ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!$hasResults): ?>
                                                    <div class="text-muted small">(Keine Ergebnisse)</div>
                                                    <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
        </div>

                    <!-- Gruppensortierung -->
                    <div class="group-sort-container">
                        <span class="group-sort-label">Gruppensortierung:</span>
                        <div class="btn-group btn-group-sort me-2" role="group">
                            <button type="button" class="btn btn-outline-secondary" data-sort-groups="accessCode" data-sort-dir="asc">Code ↑</button>
                            <button type="button" class="btn btn-outline-secondary" data-sort-groups="accessCode" data-sort-dir="desc">↓</button>
                        </div>
                        <div class="btn-group btn-group-sort me-2" role="group">
                            <button type="button" class="btn btn-outline-secondary" data-sort-groups="testTitle" data-sort-dir="asc">Titel ↑</button>
                            <button type="button" class="btn btn-outline-secondary" data-sort-groups="testTitle" data-sort-dir="desc">↓</button>
                        </div>
                        <div class="btn-group btn-group-sort" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-sort-groups="testDate" data-sort-dir="desc">Neueste Tests ↓</button>
                            <button type="button" class="btn btn-outline-secondary" data-sort-groups="testDate" data-sort-dir="asc">Älteste Tests ↑</button>
            </div>
        </div>

                    <!-- Einfache Ergebnisse-Anzeige ohne eigene Tab-Navigation -->
        <div id="filteredResults">
                        <!-- Hier werden die gefilterten Ergebnisse angezeigt -->
                        <?php if (!empty($groupedResults)): ?>
            <?php foreach ($groupedResults as $group): ?>
                                <div class="card mb-4 result-group">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            [<?php echo htmlspecialchars($group['accessCode']); ?>] - 
                            <?php echo htmlspecialchars($group['testTitle']); ?> 
                                            <?php if (!empty($group['testDate'])): ?>
                                                (<?php echo date('d.m.y', strtotime($group['testDate'])); ?>)
                                            <?php endif; ?>
                                            <?php if (!empty($group['lastTestDate'])): ?>
                                                <?php 
                                                    $lastTestTimestamp = strtotime($group['lastTestDate']);
                                                    if ($lastTestTimestamp !== false): 
                                                ?>
                                                | Letzter Test: <?php echo date('d.m.y H:i', $lastTestTimestamp); ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="sort-header" data-sort="studentName">Schüler</th>
                                        <th class="sort-header" data-sort="date">Abgabezeitpunkt</th>
                                        <th class="sort-header" data-sort="points">Punkte</th>
                                        <th class="sort-header" data-sort="percentage">Prozent</th>
                                        <th class="sort-header" data-sort="grade">Note</th>
                                        <th class="text-start">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['results'] as $result): ?>
                                                        <tr>
                                            <td><?php echo htmlspecialchars($result['studentName']); ?></td>
                                            <td><?php echo htmlspecialchars($result['date']); ?></td>
                                            <td><?php echo htmlspecialchars($result['points_achieved']); ?>/<?php echo htmlspecialchars($result['points_maximum']); ?></td>
                                                            <td><?php echo htmlspecialchars($result['percentage']); ?>%</td>
                                            <td>
                                                <?php 
                                                $grade = $result['grade'];
                                                $gradeClass = '';
                                                if ($grade == '1' || $grade == '2') {
                                                    $gradeClass = 'grade-good';
                                                } else if ($grade == '3' || $grade == '4') {
                                                    $gradeClass = 'grade-medium';
                                                } else if ($grade == '5' || $grade == '6') {
                                                    $gradeClass = 'grade-bad';
                                                }
                                                ?>
                                                <span class="<?php echo $gradeClass; ?>"><?php echo htmlspecialchars($grade); ?></span>
                                            </td>
                                            <td class="text-start">
                                                <button class="btn btn-sm btn-outline-success btn-action" onclick="showResults('<?php echo htmlspecialchars(str_replace('\\', '/', $result['fileName'])); ?>')">
                                                    Details
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger ms-2" onclick="deleteTestResult('<?php echo htmlspecialchars(str_replace('\\', '/', $result['fileName'])); ?>', '<?php echo htmlspecialchars($result['studentName'] ?? 'Unbekannt'); ?>')">
                                                    <i class="bi bi-trash"></i>
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
                </div>
            </div>
        </div>
    </div>
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

<!-- Modal für Erfolgsmeldung -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Erfolg</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 2rem;"></i>
                    <p class="mb-0" id="successMessage">Das Testergebnis wurde erfolgreich gelöscht.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Fehlermeldung -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">Fehler</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-3" style="font-size: 2rem;"></i>
                    <p class="mb-0" id="errorMessage">Es ist ein Fehler aufgetreten.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Löschbestätigung -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Löschen bestätigen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-3" style="font-size: 2rem;"></i>
                    <p class="mb-0" id="confirmDeleteMessage">Sind Sie sicher, dass Sie dieses Testergebnis löschen möchten?</p>
                </div>
                <p class="small text-muted">Diese Aktion kann nicht rückgängig gemacht werden.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Ja, löschen</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/de.js"></script>
<script>
// Debug-Ausgaben für Tab-Navigation
console.log('==== TEST_RESULTS_VIEW DEBUG ====');
console.log('DOM Content loaded in test_results_view.php');
console.log('Parent Tab-Container:', document.querySelector('.tab-content'));
console.log('Current Tab-Pane:', document.querySelector('#testResults'));
console.log('Tab-Pane visible?', document.querySelector('#testResults')?.style.display);
console.log('Tab is active?', document.querySelector('.tab[data-target="#testResults"]')?.classList.contains('active'));
console.log('================================');

document.addEventListener('DOMContentLoaded', function() {
    // Entfernung der eigenen Tab-Navigation
    // Behalte nur Filter-Funktionalität
    
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
        // Verfügbare Daten aus dem PHP-Backend
        const availableDates = <?php echo $availableDatesJson; ?>;
        console.log('Verfügbare Daten für Datepicker:', availableDates);
        
        flatpickr("#dateFilter", {
            dateFormat: "Y-m-d",
            locale: "de",
            allowInput: true,
            disableMobile: true,
            // Aktiviere nur die Tage, an denen Tests vorhanden sind
            enable: availableDates,
            // Zeige Info, wenn ein deaktivierter Tag gewählt wird
            onValueUpdate: function(selectedDates, dateStr) {
                console.log('Datum geändert:', selectedDates);
                if (selectedDates.length > 0) {
                    updateResults();
                }
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
            // Überspringe Tests ohne Ergebnisse (sie haben kein data-code Attribut)
            if (!item.dataset.code && item.classList.contains('no-attempts')) {
                console.log('Überspringe Test ohne Ergebnisse:', item.textContent.trim());
                return; // Überspringe diesen Test
            }
            
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Wenn es kein data-code Attribut gibt, handelt es sich um einen Test ohne Ergebnisse
                if (!this.dataset.code && this.classList.contains('no-attempts')) {
                    console.log('Test ohne Ergebnisse, ignoriere Klick');
                    return;
                }
                
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

    // Initialisiere Gruppensortierung
    initGroupSorting();
    
    // Event-Delegation für Tabellensortierung
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('sort-header') || e.target.closest('.sort-header')) {
            const header = e.target.classList.contains('sort-header') ? e.target : e.target.closest('.sort-header');
            const table = header.closest('table');
            const sortField = header.dataset.sort;
            
            // Entferne vorherige Sortierklassen
            table.querySelectorAll('.sort-header').forEach(h => {
                if (h !== header) {
                    h.classList.remove('sort-asc', 'sort-desc');
                }
            });
            
            // Bestimme Sortierrichtung
            let sortDirection = 'asc';
            if (header.classList.contains('sort-asc')) {
                sortDirection = 'desc';
                header.classList.remove('sort-asc');
                header.classList.add('sort-desc');
            } else {
                header.classList.remove('sort-desc');
                header.classList.add('sort-asc');
            }
            
            // Sortiere Tabelle
            sortTable(table, sortField, sortDirection);
        }
    });
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
    
    // Verwende die dedizierte AJAX-Endpunkt-Datei mit absolutem Pfad
    const ajaxUrl = '/mcq-test-system/teacher/load_test_results.php';
    console.log('Sende AJAX-Anfrage an:', ajaxUrl);
    
    // Zeige Ladestatus an
    const container = document.getElementById('filteredResults');
    container.innerHTML = '<div class="alert alert-info">Ergebnisse werden geladen...</div>';
    
    // Zufälliger Cache-Buster
    queryParams.append('_', new Date().getTime());
    
    fetch(ajaxUrl + '?' + queryParams.toString(), {
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
        return response.json();
    })
    .then(data => {
        console.log('Vollständige AJAX-Antwort erhalten:', data);
        
        // Detaillierte Debug-Ausgabe der Datenstruktur
        if (data.data && data.data.length > 0) {
            console.log('Beispiel-Datensatz:', JSON.stringify(data.data[0], null, 2));
            console.log('Verfügbare Felder:', Object.keys(data.data[0]));
        }
        
                if (!data.success) {
            throw new Error(data.error || 'Unbekannter Fehler beim Laden der Daten');
        }
        
        // Überprüfe, ob Ergebnisse vorhanden sind
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<div class="alert alert-info">Keine Ergebnisse gefunden.</div>';
            return;
        }
        
        updateResultsDisplay(data.data);
    })
    .catch(error => {
        console.error('Fehler beim Laden der Ergebnisse:', error);
        container.innerHTML = `
                    <div class="alert alert-danger">
                <strong>Fehler beim Laden der Ergebnisse:</strong> ${error.message}
                <br><small>Bitte prüfen Sie die Konsole für weitere Details.</small>
                    </div>
                `;
    });
}

// Hilfsfunktion zum Aktualisieren der Ergebnisanzeige
function updateResultsDisplay(results) {
    console.log('Aktualisiere Ergebnisanzeige mit', results.length, 'Ergebnissen');
    
    // Feldnamen-Mapping zwischen Backend und Frontend
    const fieldMapping = {
        // Backend-Name -> Frontend-Name
        'test_title': 'testTitle',
        'title': 'testTitle',
        'test_code': 'accessCode',
        'access_code': 'accessCode',
        'student_name': 'studentName',
        'completed_at': 'date',
        'xml_file_path': 'fileName',
        'points_achieved': 'points_achieved',
        'points_maximum': 'points_maximum'
    };
    
    // Daten transformieren, um einheitliche Feldnamen zu verwenden
    const transformedResults = results.map(item => {
        const transformed = {};
        Object.keys(item).forEach(key => {
            const mappedKey = fieldMapping[key] || key;
            
            // Datumsformatierung für date-Feld
            if (key === 'completed_at' || key === 'date') {
                if (item[key]) {
                    // Formatiere Datum als dd.mm.yy
                    const date = new Date(item[key]);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = String(date.getFullYear()).slice(2);
                    transformed[mappedKey] = `${day}.${month}.${year} ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
                } else {
                    transformed[mappedKey] = item[key];
                }
            } else {
                transformed[mappedKey] = item[key];
            }
        });
        return transformed;
    });
    
    console.log('Transformierte Ergebnisse:', transformedResults.length);
    if (transformedResults.length > 0) {
        console.log('Beispiel transformiertes Ergebnis:', JSON.stringify(transformedResults[0], null, 2));
    }
    
    // Gruppiere Ergebnisse nach Test
    const groupedResults = {};
    transformedResults.forEach(result => {
        const key = result.testTitle + '_' + result.accessCode;
        if (!groupedResults[key]) {
            groupedResults[key] = {
                testTitle: result.testTitle,
                accessCode: result.accessCode,
                testDate: result.testDate || result.created_at || '',
                lastTestDate: result.date,
                results: []
            };
        } else {
            // Aktualisiere das Datum des letzten Tests, wenn das aktuelle Ergebnis neuer ist
            const currentDate = new Date(result.date);
            const lastDate = new Date(groupedResults[key].lastTestDate);
            if (currentDate > lastDate) {
                groupedResults[key].lastTestDate = result.date;
            }
        }
        groupedResults[key].results.push(result);
    });
    
    console.log('Gruppierte Ergebnisse:', Object.keys(groupedResults).length, 'Gruppen');
    
    // Ergebnisse anzeigen
    const container = document.getElementById('filteredResults');
    
    if (Object.keys(groupedResults).length === 0) {
        container.innerHTML = '<div class="alert alert-info">Keine Ergebnisse gefunden.</div>';
        return;
    }
    
    let html = '';
    
    // Zeige Ergebnisse gruppiert nach Test an
    Object.values(groupedResults).forEach(group => {
        html += `
            <div class="card mb-4 result-group">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        [${group.accessCode}] - 
                        ${group.testTitle}
                        ${group.testDate ? ' (' + formatDate(group.testDate) + ')' : ''}
                        ${group.lastTestDate ? (() => {
                            const formattedDate = formatDateTime(group.lastTestDate);
                            return formattedDate ? ' | Letzter Test: ' + formattedDate : '';
                        })() : ''}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="sort-header" data-sort="studentName">Schüler</th>
                                    <th class="sort-header" data-sort="date">Abgabezeitpunkt</th>
                                    <th class="sort-header" data-sort="points">Punkte</th>
                                    <th class="sort-header" data-sort="percentage">Prozent</th>
                                    <th class="sort-header" data-sort="grade">Note</th>
                                    <th class="text-start">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>`;
        
        group.results.forEach(result => {
            html += `
                <tr>
                    <td>${result.studentName || 'Unbekannt'}</td>
                    <td>${result.date || 'Unbekannt'}</td>
                    <td>${result.points_achieved || 0}/${result.points_maximum || 0}</td>
                    <td>${result.percentage || 0}%</td>
                    <td>
                        ${formatGrade(result.grade || '-')}
                    </td>
                    <td class="text-start">
                        <button class="btn btn-sm btn-outline-success btn-action" onclick="showResults('${(result.fileName || '').replace(/\\/g, '/')}')">
                            Details
                        </button>
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="deleteTestResult('${(result.fileName || '').replace(/\\/g, '/')}', '${result.studentName || 'Unbekannt'}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
    });
    
    container.innerHTML = html;
    console.log('Ergebnisanzeige aktualisiert');
}

function showResults(filename) {
    console.log('Zeige Details für:', filename);
    
    // Normalisiere den Pfad für Windows (ersetze Backslashes durch Forward Slashes)
    filename = filename.replace(/\\/g, '/');
    
    // Entferne absoluten Pfadteil, falls vorhanden
    // Falls der Pfad mit dem Projektverzeichnis beginnt, entferne diesen Teil
    const basePath = '/xampp/htdocs/mcq-test-system/';
    if (filename.includes(basePath)) {
        filename = filename.substring(filename.indexOf(basePath) + basePath.length);
    } else if (filename.includes('C:/xampp/htdocs/mcq-test-system/')) {
        filename = filename.replace('C:/xampp/htdocs/mcq-test-system/', '');
    }
    
    console.log('Normalisierter Pfad:', filename);
    
    // Speichere das aktuelle Element für späteren Fokus
    let lastFocusedElement = document.activeElement;
    
    // Hole Modal und stelle Bootstrap-Modal-Instanz her
    const resultDetailModal = document.getElementById('resultDetailModal');
    let modalInstance;
    
    if (resultDetailModal) {
        modalInstance = new bootstrap.Modal(resultDetailModal);
        
        // Zeige das Modal
        modalInstance.show();
        
        // Lade die Detailansicht mit korrektem Pfad
        fetch(`../includes/teacher_dashboard/show_results.php?file=${encodeURIComponent(filename)}&format=ajax`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Netzwerkantwort war nicht ok');
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('resultDetailContent').innerHTML = html;
                
                // Nach dem Laden der Inhalte die Punkte und Header analysieren
                setTimeout(() => {
                    const questionCards = document.querySelectorAll('#resultDetailContent .card');
                    console.log('Gefundene Fragen-Karten:', questionCards.length);
                    
                    questionCards.forEach((card, index) => {
                        // Finde den Header und die Punkte-Anzeige
                        const header = card.querySelector('.card-header');
                        const pointsDisplay = card.querySelector('.card-header h5');
                        
                        if (!header || !pointsDisplay) {
                            console.log(`Frage ${index+1}: Header oder Punktanzeige nicht gefunden`);
                            return;
                        }
                        
                        // Extrahiere den Text aus der Überschrift
                        const headerText = pointsDisplay.textContent;
                        
                        // Extrahiere die Fragennummer und Punkte
                        const questionMatch = headerText.match(/Frage (\d+):/);
                        const pointsMatch = headerText.match(/(\d+)\/(\d+)/);
                        
                        let questionNumber = index + 1;
                        let achievedPoints = 0;
                        let maxPoints = 0;
                        
                        if (questionMatch && questionMatch.length >= 2) {
                            questionNumber = parseInt(questionMatch[1], 10);
                        }
                        
                        if (pointsMatch && pointsMatch.length >= 3) {
                            achievedPoints = parseInt(pointsMatch[1], 10);
                            maxPoints = parseInt(pointsMatch[2], 10);
                        }
                        
                        // Extrahiere den Stil
                        const headerStyle = header.getAttribute('style') || '';
                        const borderStyle = card.getAttribute('style') || '';
                        
                        // Bestimme die Farbe basierend auf dem Stil
                        let color = 'unbekannt';
                        if (headerStyle.includes('#28a745')) {
                            color = 'grün (alle Punkte)';
                        } else if (headerStyle.includes('#dc3545')) {
                            color = 'rot (keine Punkte)';
                        } else if (headerStyle.includes('#fd7e14')) {
                            color = 'orange (teilweise Punkte)';
                        }
                        
                        // Gib detaillierte Informationen aus
                        console.log(`Frage ${index+1}:`, {
                            'Text': headerText,
                            'Extrahierte Fragennummer': questionNumber,
                            'Position in DOM': index+1,
                            'Erreichte Punkte': achievedPoints,
                            'Maximale Punkte': maxPoints,
                            'Farbe': color,
                            'Header-Stil': headerStyle,
                            'Border-Stil': borderStyle
                        });
                    });
                }, 100);
            })
            .catch(error => {
                console.error('Fehler beim Laden der Details:', error);
                const modalContent = document.getElementById('resultDetailContent');
                if (modalContent) {
                    modalContent.innerHTML = `
                        <div class="alert alert-danger">
                            Fehler beim Laden der Testergebnis-Details: ${error.message}
                        </div>
                    `;
                }
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
    } else {
        // Fallback, wenn Modal nicht verfügbar ist
        window.location.href = `../../includes/teacher_dashboard/show_results.php?file=${encodeURIComponent(filename)}`;
    }
}

// Debug-Event-Listener für Tab-Änderungen
$(document).on('tabChanged', function(e, target) {
    console.log('Tab changed event received in test_results_view.php');
    console.log('Target:', target);
    console.log('Is testResults visible?', $('#testResults').is(':visible'));
    console.log('testResults display style:', $('#testResults').css('display'));
    
    // Prüfe, ob wir neu laden müssen
    if (target === '#testResults' && $('#testResults').is(':visible')) {
        console.log('testResults Tab ist aktiv, aktualisiere Ergebnisse');
        updateResults();
    }
});

// Hilfsfunktion zur Datumsformatierung
function formatDate(dateString) {
    try {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        // Prüfe, ob das Datum gültig ist
        if (isNaN(date.getTime())) return '';
        
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear()).slice(2);
        return `${day}.${month}.${year}`;
    } catch (e) {
        console.error('Fehler bei der Datumsformatierung:', e, dateString);
        return '';
    }
}

// Hilfsfunktion zur Datums- und Uhrzeitformatierung
function formatDateTime(dateTimeString) {
    try {
        // Wenn kein Datum übergeben wurde oder es ein ungültiger String ist
        if (!dateTimeString || typeof dateTimeString !== 'string') {
            return '';
        }
        
        // Wenn das Datum bereits im Format "DD.MM.YY HH:MM" ist
        if (dateTimeString.match(/^\d{2}\.\d{2}\.\d{2} \d{2}:\d{2}$/)) {
            return dateTimeString;
        }
        
        // Versuche Standardkonvertierung
        const date = new Date(dateTimeString);
        
        // Prüfe, ob das Datum gültig ist
        if (isNaN(date.getTime())) {
            console.warn('Ungültiges Datum:', dateTimeString);
            return '';
        }
        
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear()).slice(2);
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${day}.${month}.${year} ${hours}:${minutes}`;
    } catch (e) {
        console.error('Fehler bei der Datums- und Uhrzeitformatierung:', e, dateTimeString);
        return '';
    }
}

// Hilfsfunktion zur Notenformatierung
function formatGrade(grade) {
    if (grade === '1' || grade === '2') {
        return `<span class="grade-good">${grade}</span>`;
    } else if (grade === '3' || grade === '4') {
        return `<span class="grade-medium">${grade}</span>`;
    } else if (grade === '5' || grade === '6') {
        return `<span class="grade-bad">${grade}</span>`;
    } else {
        return grade;
    }
}

// Hilfsfunktion zur Tabellensortierung
function sortTable(table, field, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let valA, valB;
        
        if (field === 'studentName' || field === 'date') {
            valA = a.cells[field === 'studentName' ? 0 : 1].textContent.trim();
            valB = b.cells[field === 'studentName' ? 0 : 1].textContent.trim();
        } else if (field === 'points') {
            // Extrahiere den ersten Teil (erreichte Punkte)
            valA = parseInt(a.cells[2].textContent.split('/')[0]);
            valB = parseInt(b.cells[2].textContent.split('/')[0]);
        } else if (field === 'percentage') {
            valA = parseFloat(a.cells[3].textContent);
            valB = parseFloat(b.cells[3].textContent);
        } else if (field === 'grade') {
            valA = a.cells[4].textContent.trim();
            valB = b.cells[4].textContent.trim();
            
            // Berücksichtige, dass Note 1 besser ist als Note 6
            if (!isNaN(valA) && !isNaN(valB)) {
                valA = parseInt(valA);
                valB = parseInt(valB);
                return direction === 'asc' ? valA - valB : valB - valA;
            }
        }
        
        // Standardsortierung
        if (direction === 'asc') {
            return valA > valB ? 1 : -1;
        } else {
            return valA < valB ? 1 : -1;
        }
    });
    
    // Aktualisiere DOM
    rows.forEach(row => tbody.appendChild(row));
}

// Initialisiere Gruppensortierung
function initGroupSorting() {
    const sortButtons = document.querySelectorAll('.btn-group-sort .btn');
    if (sortButtons.length === 0) return;
    
    sortButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Entferne active-Klasse von allen Buttons
            sortButtons.forEach(btn => btn.classList.remove('active'));
            
            // Setze active-Klasse für geklickten Button
            this.classList.add('active');
            
            // Sortiere Gruppen
            const field = this.dataset.sortGroups;
            const direction = this.dataset.sortDir;
            sortGroups(field, direction);
        });
    });
    
    // Standardmäßig nach dem letzten Testdatum absteigend sortieren
    // Suche den Button für "Neueste Tests" und setze ihn als aktiv
    const newestTestsButton = document.querySelector('[data-sort-groups="testDate"][data-sort-dir="desc"]');
    if (newestTestsButton) {
        // Entferne active-Klasse von allen Buttons
        sortButtons.forEach(btn => btn.classList.remove('active'));
        // Setze active-Klasse für den Button "Neueste Tests"
        newestTestsButton.classList.add('active');
        // Führe die Sortierung durch
        sortGroups('testDate', 'desc');
    } else {
        // Fallback zur vorherigen Standardsortierung, falls der Button nicht gefunden wird
        sortGroups('accessCode', 'asc');
    }
}

// Sortiere Gruppen
function sortGroups(field, direction) {
    const container = document.getElementById('filteredResults');
    const groups = Array.from(container.querySelectorAll('.result-group'));
    
    groups.sort((a, b) => {
        let valA, valB;
        
        if (field === 'accessCode') {
            // Extrahiere den Zugangscode aus dem Header
            const headerA = a.querySelector('.card-header h5').textContent;
            const headerB = b.querySelector('.card-header h5').textContent;
            const matchA = headerA.match(/\[(.*?)\]/);
            const matchB = headerB.match(/\[(.*?)\]/);
            
            valA = matchA && matchA.length > 1 ? matchA[1].trim() : '';
            valB = matchB && matchB.length > 1 ? matchB[1].trim() : '';
        } else if (field === 'testTitle') {
            // Extrahiere den Testtitel aus dem Header
            const headerA = a.querySelector('.card-header h5').textContent;
            const headerB = b.querySelector('.card-header h5').textContent;
            
            const partsA = headerA.split('-');
            const partsB = headerB.split('-');
            
            valA = partsA.length > 1 ? partsA[1].split('(')[0].trim() : '';
            valB = partsB.length > 1 ? partsB[1].split('(')[0].trim() : '';
        } else if (field === 'testDate') {
            // Suche nach dem letzten Testdatum in der Überschrift
            const headerA = a.querySelector('.card-header h5').textContent;
            const headerB = b.querySelector('.card-header h5').textContent;
            
            // Suche nach "Letzter Test: 01.02.23 12:34" im Format
            const dateMatchA = headerA.match(/Letzter Test: (\d{2}\.\d{2}\.\d{2} \d{2}:\d{2})/);
            const dateMatchB = headerB.match(/Letzter Test: (\d{2}\.\d{2}\.\d{2} \d{2}:\d{2})/);
            
            if (dateMatchA && dateMatchB) {
                try {
                    // Wenn beide Daten gefunden wurden, konvertiere sie für den Vergleich
                    const [dayA, monthA, yearA] = dateMatchA[1].substring(0, 8).split('.');
                    const [hoursA, minutesA] = dateMatchA[1].substring(9).split(':');
                    
                    const [dayB, monthB, yearB] = dateMatchB[1].substring(0, 8).split('.');
                    const [hoursB, minutesB] = dateMatchB[1].substring(9).split(':');
                    
                    // Prüfe, ob alle Datumskomponenten gültige Zahlen sind
                    if (dayA && monthA && yearA && hoursA && minutesA && 
                        dayB && monthB && yearB && hoursB && minutesB) {
                        
                        const dateA = new Date(`20${yearA}`, parseInt(monthA) - 1, parseInt(dayA), parseInt(hoursA), parseInt(minutesA));
                        const dateB = new Date(`20${yearB}`, parseInt(monthB) - 1, parseInt(dayB), parseInt(hoursB), parseInt(minutesB));
                        
                        // Zusätzliche Prüfung, ob die Datumskonvertierung gültige Objekte erzeugt hat
                        if (!isNaN(dateA.getTime()) && !isNaN(dateB.getTime())) {
                            return direction === 'asc' ? dateA - dateB : dateB - dateA;
                        }
                    }
                } catch (e) {
                    console.error('Fehler beim Sortieren nach Testdatum:', e);
                }
            } else if (dateMatchA && !dateMatchB) {
                // Wenn nur in der ersten Gruppe ein Datum gefunden wurde
                return direction === 'asc' ? 1 : -1;
            } else if (!dateMatchA && dateMatchB) {
                // Wenn nur in der zweiten Gruppe ein Datum gefunden wurde
                return direction === 'asc' ? -1 : 1;
            }
            
            // Wenn keine speziellen Daten gefunden wurden, fallback auf Erstellungsdatum
            const creationDateMatchA = headerA.match(/\((\d{2}\.\d{2}\.\d{2})\)/);
            const creationDateMatchB = headerB.match(/\((\d{2}\.\d{2}\.\d{2})\)/);
            
            valA = creationDateMatchA && creationDateMatchA.length > 1 ? creationDateMatchA[1] : '01.01.00';
            valB = creationDateMatchB && creationDateMatchB.length > 1 ? creationDateMatchB[1] : '01.01.00';
            
            try {
                // Konvertiere zu Datum für besseren Vergleich
                const [dayA, monthA, yearA] = valA.split('.');
                const [dayB, monthB, yearB] = valB.split('.');
                
                if (dayA && monthA && yearA && dayB && monthB && yearB) {
                    const dateA = new Date(`20${yearA}`, parseInt(monthA) - 1, parseInt(dayA));
                    const dateB = new Date(`20${yearB}`, parseInt(monthB) - 1, parseInt(dayB));
                    
                    if (!isNaN(dateA.getTime()) && !isNaN(dateB.getTime())) {
                        return direction === 'asc' ? dateA - dateB : dateB - dateA;
                    }
                }
            } catch (e) {
                console.error('Fehler beim Sortieren nach Erstellungsdatum:', e);
            }
            
            // Fallback, wenn die Datumskonvertierung fehlschlägt
            return direction === 'asc' ? (valA > valB ? 1 : -1) : (valA < valB ? 1 : -1);
        }
        
        // Standardsortierung
        if (direction === 'asc') {
            return valA > valB ? 1 : -1;
        } else {
            return valA < valB ? 1 : -1;
        }
    });
    
    // Aktualisiere DOM
    groups.forEach(group => container.appendChild(group));
}

// Funktion zum Löschen eines Testergebnisses
function deleteTestResult(filename, studentName) {
    console.log('Löschversuch für:', filename, 'Schüler:', studentName);
    
    // Speichere die Dateiinformationen, um sie später zu verwenden
    let fileToDelete = filename;
    
    // Löschbestätigung als Modal anzeigen statt confirm()
    const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    document.getElementById('confirmDeleteMessage').textContent = `Sind Sie sicher, dass Sie das Testergebnis von "${studentName}" löschen möchten?`;
    
    // Event-Handler für den Bestätigungsbutton
    const confirmDeleteButton = document.getElementById('confirmDeleteButton');
    
    // Alten Event-Listener entfernen, um doppelte Registrierung zu vermeiden
    const newConfirmDeleteButton = confirmDeleteButton.cloneNode(true);
    confirmDeleteButton.parentNode.replaceChild(newConfirmDeleteButton, confirmDeleteButton);
    
    // Neuen Event-Listener hinzufügen
    newConfirmDeleteButton.addEventListener('click', function() {
        // Modal schließen
        confirmDeleteModal.hide();
        
        // Normalisiere den Pfad für Windows (ersetze Backslashes durch Forward Slashes)
        fileToDelete = fileToDelete.replace(/\\/g, '/');
        
        // Entferne absoluten Pfadteil, falls vorhanden
        const basePath = '/xampp/htdocs/mcq-test-system/';
        if (fileToDelete.includes(basePath)) {
            fileToDelete = fileToDelete.substring(fileToDelete.indexOf(basePath) + basePath.length);
        } else if (fileToDelete.includes('C:/xampp/htdocs/mcq-test-system/')) {
            fileToDelete = fileToDelete.replace('C:/xampp/htdocs/mcq-test-system/', '');
        }
        
        console.log('Lösche Testergebnis:', fileToDelete);
        
        // AJAX-Anfrage zum Löschen des Testergebnisses
        fetch('../includes/teacher_dashboard/delete_test_result.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `file=${encodeURIComponent(fileToDelete)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Netzwerkantwort war nicht ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Erfolgreiche Löschung - zeige Modal statt alert
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                document.getElementById('successMessage').textContent = 'Das Testergebnis wurde erfolgreich gelöscht.';
                successModal.show();
                
                // Automatisches Ausblenden nach 3 Sekunden
                setTimeout(() => {
                    successModal.hide();
                }, 3000);
                
                // Aktualisiere die Ergebnisanzeige
                updateResults();
            } else {
                // Fehler bei der Löschung - zeige Modal statt alert
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                document.getElementById('errorMessage').textContent = data.error || 'Unbekannter Fehler beim Löschen';
                errorModal.show();
            }
        })
        .catch(error => {
            console.error('Fehler beim Löschen des Testergebnisses:', error);
            // Fehler-Modal anzeigen statt alert
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            document.getElementById('errorMessage').textContent = 'Fehler beim Löschen des Testergebnisses: ' + error.message;
            errorModal.show();
        });
    });
    
    // Modal anzeigen
    confirmDeleteModal.show();
}
</script> 

</body>
</html>
<?php endif; ?> 