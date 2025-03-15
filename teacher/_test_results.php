<?php
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: index.php');
    exit();
}

// Lade Testergebnisse
$results = [];
$resultFiles = glob(__DIR__ . '/../results/*.txt');
foreach ($resultFiles as $file) {
    $filename = basename($file);
    $parts = explode('_', $filename);
    
    // Extrahiere Informationen aus dem Dateinamen
    $testName = $parts[0];
    $studentName = isset($parts[1]) ? $parts[1] : 'Unbekannt';
    $isAborted = strpos($filename, 'aborted') !== false;
    $date = str_replace('.txt', '', end($parts));
    
    $results[] = [
        'date' => $date,
        'testName' => $testName,
        'studentName' => $studentName,
        'status' => $isAborted ? 'Abgebrochen' : 'Abgeschlossen'
    ];
}
?>

<div class="results-container">
    <h2>Testergebnisse</h2>
    
    <?php if (empty($results)): ?>
        <p>Noch keine Testergebnisse vorhanden.</p>
    <?php else: ?>
        <table class="results-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Test</th>
                    <th>Schüler</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                <tr>
                    <td><?php echo date('d.m.Y H:i', strtotime(str_replace('-', ':', $result['date']))); ?></td>
                    <td><?php echo htmlspecialchars($result['testName']); ?></td>
                    <td><?php echo htmlspecialchars($result['studentName']); ?></td>
                    <td><?php echo $result['status']; ?></td>
                    <td>
                        <button class="btn" onclick="viewResult('<?php echo htmlspecialchars($result['testName']); ?>', '<?php echo htmlspecialchars($result['date']); ?>')">
                            Details
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function viewResult(testName, date) {
    // TODO: Implementiere Detailansicht
    alert('Details für Test: ' + testName + ' vom ' + date);
}
</script> 