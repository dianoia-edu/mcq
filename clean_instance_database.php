<?php
/**
 * Bereinigt die Datenbank einer bestehenden Instanz von Testergebnissen
 */

if (!isset($_GET['clean_db']) || $_GET['clean_db'] !== 'clean_now') {
    die('DB-Bereinigung verweigert.');
}

$instanceName = $_GET['instance'] ?? '';
if (empty($instanceName)) {
    die('Instanzname fehlt. Verwendung: ?clean_db=clean_now&instance=INSTANZNAME');
}

// Lade die Instance-Konfiguration
$instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
$instancePath = $instancesBasePath . $instanceName . '/mcq-test-system/';
$configPath = $instancePath . 'includes/database_config.php';

if (!file_exists($configPath)) {
    die("Instanz-Konfiguration nicht gefunden: $configPath");
}

// Lade die DB-Konfiguration
require_once $configPath;

$result = ['cleaned' => 0, 'errors' => 0, 'details' => []];

try {
    // Hole DB-Konstanten aus der Instance-Konfiguration  
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
        throw new Exception('DB-Konstanten nicht gefunden in Instance-Konfiguration');
    }
    
    // Verbinde zur Instance-DB
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Bereinige alle Tabellen
    $tablesToClean = [
        'test_attempts' => 'Testversuche',
        'test_statistics' => 'Test-Statistiken', 
        'daily_attempts' => 'Tägliche Versuche',
        'tests' => 'Tests'
    ];
    
    foreach ($tablesToClean as $table => $description) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `$table` WHERE 1=1");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            
            $result['cleaned'] += $deleted;
            $result['details'][] = "✅ $description ($table): $deleted Einträge gelöscht";
        } catch (PDOException $e) {
            $result['errors']++;
            $result['details'][] = "❌ $description ($table): " . $e->getMessage();
        }
    }
    
    // Reset AUTO_INCREMENT
    try {
        $pdo->exec("ALTER TABLE test_attempts AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE daily_attempts AUTO_INCREMENT = 1");
        $result['details'][] = "✅ AUTO_INCREMENT zurückgesetzt";
    } catch (PDOException $e) {
        $result['details'][] = "⚠️ AUTO_INCREMENT Reset: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $result['errors']++;
    $result['details'][] = "❌ Verbindungsfehler: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DB-Bereinigung - <?php echo htmlspecialchars($instanceName); ?></title>
    <style>
        body { font-family: monospace; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .summary { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🧹 DB-Bereinigung - <?php echo htmlspecialchars($instanceName); ?></h1>
    
    <div class="summary">
        <h2>Zusammenfassung</h2>
        <p><strong>Gelöschte Einträge:</strong> <?php echo $result['cleaned']; ?></p>
        <p><strong>Fehler:</strong> <?php echo $result['errors']; ?></p>
    </div>
    
    <h2>Details</h2>
    <ul>
        <?php foreach ($result['details'] as $detail): ?>
            <li class="<?php 
                if (strpos($detail, '✅') !== false) echo 'success';
                elseif (strpos($detail, '❌') !== false) echo 'error';
                else echo 'warning';
            ?>">
                <?php echo htmlspecialchars($detail); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <?php if ($result['cleaned'] > 0): ?>
        <div class="success">
            <h3>✅ Bereinigung abgeschlossen!</h3>
            <p>Die Datenbank der Instanz <strong><?php echo htmlspecialchars($instanceName); ?></strong> wurde von Testergebnissen bereinigt.</p>
            <p><strong>Die Instanz startet jetzt mit einer leeren Datenbank!</strong></p>
        </div>
    <?php elseif ($result['errors'] === 0): ?>
        <div class="success">
            <h3>✅ Datenbank bereits sauber!</h3>
            <p>Die Instanz hatte bereits keine Testergebnisse.</p>
        </div>
    <?php endif; ?>
    
</body>
</html>
