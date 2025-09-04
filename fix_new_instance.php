<?php
/**
 * Sofort-Fix für die neu erstellte Instanz
 */

if (!isset($_GET['fix_instance']) || $_GET['fix_instance'] !== 'fix_now') {
    die('Instance-Fix verweigert.');
}

$instanceName = $_GET['instance'] ?? 'neutest'; // Standardmäßig neutest, kann angepasst werden
$instancesBasePath = '/var/www/dianoia-ai.de/lehrer_instanzen/';
$sourceBasePath = __DIR__;

$filesToUpdate = [
    'includes/teacher_dashboard/test_generator_view.php' => 'Test Generator View (getTeacherUrl fix)',
    'includes/teacher_dashboard/test_editor_view.php' => 'Test Editor View (usort fix)',
    'includes/teacher_dashboard/configuration_view.php' => 'Configuration View (getTeacherUrl fix)',
    'includes/teacher_dashboard/test_results_view.php' => 'Test Results View (DatabaseConfig fix)',
    'robust_instance_index.php' => 'Robuste Index-Datei'
];

$instanceBasePath = $instancesBasePath . $instanceName . '/mcq-test-system/';
$results = ['fixed' => 0, 'errors' => 0, 'details' => []];

if (!is_dir($instanceBasePath)) {
    die('Instanz nicht gefunden: ' . $instanceName);
}

foreach ($filesToUpdate as $file => $description) {
    $sourceFile = $sourceBasePath . '/' . $file;
    
    // Spezialbehandlung für robust_instance_index.php -> index.php
    if ($file === 'robust_instance_index.php') {
        $targetFile = $instanceBasePath . 'index.php';
    } else {
        $targetFile = $instanceBasePath . $file;
    }
    
    $targetDir = dirname($targetFile);
    
    try {
        if (!file_exists($sourceFile)) {
            $results['errors']++;
            $results['details'][] = "❌ $file: Quelldatei fehlt";
            continue;
        }
        
        // Erstelle Zielverzeichnis falls nötig
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                $results['errors']++;
                $results['details'][] = "❌ $file: Verzeichnis konnte nicht erstellt werden";
                continue;
            }
        }
        
        // Backup erstellen
        if (file_exists($targetFile)) {
            $backupFile = $targetFile . '.backup.' . date('Y-m-d_H-i-s');
            copy($targetFile, $backupFile);
        }
        
        // Datei kopieren
        if (copy($sourceFile, $targetFile)) {
            $results['fixed']++;
            $actualFileName = ($file === 'robust_instance_index.php') ? 'index.php' : $file;
            $results['details'][] = "✅ $actualFileName: Erfolgreich aktualisiert";
        } else {
            $results['errors']++;
            $results['details'][] = "❌ $file: Kopieren fehlgeschlagen";
        }
    } catch (Exception $e) {
        $results['errors']++;
        $results['details'][] = "❌ $file: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Instance Fix Resultat</title>
    <style>
        body { font-family: monospace; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .summary { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔧 Instance Fix - <?php echo htmlspecialchars($instanceName); ?></h1>
    
    <div class="summary">
        <h2>Zusammenfassung</h2>
        <p><strong>Repariert:</strong> <?php echo $results['fixed']; ?> Dateien</p>
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
            <p>Die Instanz <strong><?php echo htmlspecialchars($instanceName); ?></strong> wurde mit den korrigierten Dateien aktualisiert.</p>
            <p><strong>Testen Sie jetzt die Tabs im Admin-Dashboard der Instanz!</strong></p>
        </div>
    <?php endif; ?>
    
</body>
</html>
