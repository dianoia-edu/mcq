<?php
/**
 * Sofort-Fix für Tab-Verstecken in Instanzen
 */

if (!isset($_GET['fix_tabs']) || $_GET['fix_tabs'] !== 'hide_now') {
    die('Tab-Fix verweigert.');
}

$instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
$sourceFile = __DIR__ . '/teacher/teacher_dashboard.php';

$results = ['fixed' => 0, 'errors' => 0, 'details' => []];

if (!file_exists($sourceFile)) {
    die('Quell-Dashboard nicht gefunden: ' . $sourceFile);
}

$sourceContent = file_get_contents($sourceFile);

if (is_dir($instancesBasePath)) {
    $dirs = scandir($instancesBasePath);
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $instancePath = $instancesBasePath . $dir;
        $mcqPath = $instancePath . '/mcq-test-system';
        $dashboardPath = $mcqPath . '/teacher/teacher_dashboard.php';
        
        if (!is_dir($mcqPath)) continue;
        
        try {
            if (file_exists($dashboardPath)) {
                // Backup erstellen
                $backupPath = $dashboardPath . '.backup.' . date('Y-m-d_H-i-s');
                copy($dashboardPath, $backupPath);
                
                // Neue Datei kopieren
                if (file_put_contents($dashboardPath, $sourceContent)) {
                    $results['fixed']++;
                    $results['details'][] = "✅ $dir: Dashboard aktualisiert";
                } else {
                    $results['errors']++;
                    $results['details'][] = "❌ $dir: Schreibfehler";
                }
            } else {
                $results['errors']++;
                $results['details'][] = "❌ $dir: Dashboard nicht gefunden";
            }
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = "❌ $dir: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tab-Fix Resultat</title>
    <style>
        body { font-family: monospace; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .summary { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔧 Tab-Hiding Fix</h1>
    
    <div class="summary">
        <h2>Zusammenfassung</h2>
        <p><strong>Repariert:</strong> <?php echo $results['fixed']; ?> Instanzen</p>
        <p><strong>Fehler:</strong> <?php echo $results['errors']; ?></p>
    </div>
    
    <h2>Details</h2>
    <ul>
        <?php foreach ($results['details'] as $detail): ?>
            <li class="<?php echo strpos($detail, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($detail); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <?php if ($results['fixed'] > 0): ?>
        <div class="success">
            <h3>✅ Fix abgeschlossen!</h3>
            <p>Die Instanzen haben jetzt die korrigierte Dashboard-Version.</p>
            <p><strong>Der Instanzverwaltung-Tab sollte jetzt in Instanzen ausgeblendet sein!</strong></p>
            
            <h4>Test-URLs:</h4>
            <?php
            if (is_dir($instancesBasePath)) {
                $dirs = scandir($instancesBasePath);
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..') continue;
                    if (is_dir($instancesBasePath . $dir . '/mcq-test-system')) {
                        echo '<p><a href="/lehrer_instanzen/' . $dir . '/mcq-test-system/teacher/teacher_dashboard.php" target="_blank">🔗 ' . $dir . ' Dashboard testen</a></p>';
                    }
                }
            }
            ?>
        </div>
    <?php endif; ?>
    
</body>
</html>
