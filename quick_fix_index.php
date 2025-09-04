<?php
/**
 * Schnelle Reparatur für 500-Fehler in index.php
 */

if (!isset($_GET['quick_fix']) || $_GET['quick_fix'] !== 'fix_500_now') {
    die('Quick-Fix verweigert.');
}

$instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
$templatePath = __DIR__ . '/simple_instance_index.php';

$results = ['fixed' => 0, 'errors' => 0, 'details' => []];

if (!file_exists($templatePath)) {
    die('Template nicht gefunden: ' . $templatePath);
}

$templateContent = file_get_contents($templatePath);

if (is_dir($instancesBasePath)) {
    $dirs = scandir($instancesBasePath);
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $instancePath = $instancesBasePath . $dir;
        $mcqPath = $instancePath . '/mcq-test-system';
        
        if (!is_dir($mcqPath)) continue;
        
        $indexPath = $mcqPath . '/index.php';
        
        try {
            if (file_put_contents($indexPath, $templateContent)) {
                $results['fixed']++;
                $results['details'][] = "✅ $dir: index.php repariert";
            } else {
                $results['errors']++;
                $results['details'][] = "❌ $dir: Schreibfehler";
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
    <title>Quick Fix Resultat</title>
    <style>
        body { font-family: monospace; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .summary { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔧 Quick Fix - 500 Fehler</h1>
    
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
            <h3>✅ Reparatur abgeschlossen!</h3>
            <p>Die index.php Dateien wurden durch eine vereinfachte, fehlerfreie Version ersetzt.</p>
            <p><strong>Nächster Schritt:</strong> Testen Sie die Homepage-Links in der Instanzverwaltung.</p>
        </div>
    <?php endif; ?>
    
</body>
</html>
