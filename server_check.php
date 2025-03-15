<?php
// Starte Session
session_start();

// Setze Fehlerberichterstattung
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Funktion zum Überprüfen der Voraussetzungen
function checkRequirements() {
    $requirements = [
        'php_version' => [
            'name' => 'PHP Version',
            'required' => '7.4.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'extensions' => [
            'name' => 'PHP Erweiterungen',
            'required' => ['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'curl', 'xml', 'mbstring'],
            'current' => array_filter(['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'curl', 'xml', 'mbstring'], 'extension_loaded'),
            'status' => count(array_filter(['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'curl', 'xml', 'mbstring'], 'extension_loaded')) === 7
        ],
        'tesseract' => [
            'name' => 'Tesseract OCR',
            'required' => 'Installiert',
            'current' => exec('which tesseract') ? 'Installiert' : 'Nicht gefunden',
            'status' => exec('which tesseract') ? true : false
        ],
        'ghostscript' => [
            'name' => 'Ghostscript',
            'required' => 'Installiert',
            'current' => exec('which gs') ? 'Installiert' : 'Nicht gefunden',
            'status' => exec('which gs') ? true : false
        ],
        'directories' => [
            'name' => 'Verzeichnisberechtigungen',
            'required' => 'Schreibbar',
            'current' => (is_writable('logs') && is_writable('results') && is_writable('uploads')) ? 'Schreibbar' : 'Nicht schreibbar',
            'status' => (is_writable('logs') && is_writable('results') && is_writable('uploads'))
        ]
    ];
    
    // Überprüfe Datenbankverbindung
    require_once 'includes/database_config.php';
    try {
        $db = DatabaseConfig::getInstance()->getConnection();
        $requirements['database'] = [
            'name' => 'Datenbankverbindung',
            'required' => 'Verbunden',
            'current' => 'Verbunden',
            'status' => true
        ];
    } catch (Exception $e) {
        $requirements['database'] = [
            'name' => 'Datenbankverbindung',
            'required' => 'Verbunden',
            'current' => 'Fehler: ' . $e->getMessage(),
            'status' => false
        ];
    }
    
    return $requirements;
}

// Führe die Überprüfung durch
$requirements = checkRequirements();
$allPassed = array_reduce($requirements, function($carry, $item) {
    return $carry && $item['status'];
}, true);

// HTML-Ausgabe
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server-Überprüfung</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .summary { margin-top: 20px; padding: 15px; border-radius: 5px; }
        .success-bg { background-color: #dff0d8; border: 1px solid #d6e9c6; }
        .error-bg { background-color: #f2dede; border: 1px solid #ebccd1; }
    </style>
</head>
<body>
    <h1>Server-Überprüfung</h1>
    
    <table>
        <tr>
            <th>Voraussetzung</th>
            <th>Erforderlich</th>
            <th>Aktuell</th>
            <th>Status</th>
        </tr>
        <?php foreach ($requirements as $req): ?>
        <tr>
            <td><?php echo $req['name']; ?></td>
            <td><?php echo is_array($req['required']) ? implode(', ', $req['required']) : $req['required']; ?></td>
            <td><?php echo is_array($req['current']) ? implode(', ', $req['current']) : $req['current']; ?></td>
            <td class="<?php echo $req['status'] ? 'success' : 'error'; ?>">
                <?php echo $req['status'] ? '✓' : '✗'; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div class="summary <?php echo $allPassed ? 'success-bg' : 'error-bg'; ?>">
        <?php if ($allPassed): ?>
            <p><strong>Alle Voraussetzungen erfüllt!</strong> Das System kann verwendet werden.</p>
        <?php else: ?>
            <p><strong>Es gibt Probleme mit den Voraussetzungen.</strong> Bitte beheben Sie die oben aufgeführten Fehler.</p>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 20px;">
        <p>Serverinformationen:</p>
        <ul>
            <li>Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt'; ?></li>
            <li>Hostname: <?php echo $_SERVER['SERVER_NAME'] ?? 'Unbekannt'; ?></li>
            <li>Dokumentenroot: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unbekannt'; ?></li>
            <li>PHP SAPI: <?php echo php_sapi_name(); ?></li>
        </ul>
    </div>
</body>
</html>