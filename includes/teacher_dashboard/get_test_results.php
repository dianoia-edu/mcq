<?php
// Datei nur für AJAX-Anfragen
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben von Debug-Logs
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== Start get_test_results.php ===");

// Bei AJAX-Anfragen immer nur JSON zurückgeben
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Hole Filter-Parameter
$selectedTest = $_GET['test'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$searchTerm = $_GET['search'] ?? '';

writeLog("Filter-Parameter: Test=" . $selectedTest . ", Datum=" . $startDate . ", Suche=" . $searchTerm);

try {
    $db = DatabaseConfig::getInstance()->getConnection();
    writeLog("Datenbankverbindung hergestellt");
    
    // Test-Verbindung
    $testQuery = $db->query("SELECT 1");
    if ($testQuery === false) {
        throw new Exception("Datenbankverbindung fehlgeschlagen");
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
    writeLog("SQL-Abfrage: " . $sql);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    writeLog("Abfrage ausgeführt, " . count($results) . " Ergebnisse gefunden");
    
    // Formatiere die Ergebnisse für die Rückgabe
    $formattedResults = [];
    foreach ($results as $result) {
        $formattedResults[] = [
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
    }
    
    // Erstelle die Erfolgsantwort
    $response = [
        'success' => true,
        'results' => $formattedResults,
        'count' => count($formattedResults),
        'timestamp' => time()
    ];
    
    writeLog("Sende erfolgreiche Antwort mit " . count($formattedResults) . " Ergebnissen");
    echo json_encode($response);
    
} catch (Exception $e) {
    writeLog("Fehler: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ];
    
    echo json_encode($response);
}

writeLog("=== Ende get_test_results.php ===");
?> 