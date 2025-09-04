<?php
/**
 * Webserver-Bereitschaftscheck für MCQ Test System
 * Überprüft alle Voraussetzungen für den Mehrbenutzerbetrieb
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ System - Webserver Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; }
        .check { padding: 10px; margin: 10px 0; border-radius: 5px; border-left: 5px solid; }
        .pass { background: #d4edda; border-color: #28a745; color: #155724; }
        .fail { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .warn { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🚀 MCQ Test System - Webserver Bereitschaftscheck</h1>
    
    <?php
    $checks = [];
    $criticalErrors = 0;
    $warnings = 0;
    
    // Hilfsfunktionen
    function addCheck($title, $status, $message, $critical = false) {
        global $checks, $criticalErrors, $warnings;
        $checks[] = [
            'title' => $title,
            'status' => $status,
            'message' => $message,
            'critical' => $critical
        ];
        
        if ($status === 'fail' && $critical) $criticalErrors++;
        if ($status === 'warn') $warnings++;
    }
    
    function formatBytes($size, $precision = 2) {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
    
    // 1. PHP Version und Erweiterungen
    echo "<h2>📋 PHP Konfiguration</h2>";
    
    $phpVersion = phpversion();
    if (version_compare($phpVersion, '7.4.0', '>=')) {
        addCheck("PHP Version", "pass", "PHP $phpVersion ist kompatibel");
    } else {
        addCheck("PHP Version", "fail", "PHP $phpVersion ist zu alt. Mindestens PHP 7.4 erforderlich.", true);
    }
    
    $requiredExtensions = ['pdo', 'pdo_mysql', 'xml', 'mbstring', 'curl'];
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            addCheck("PHP Erweiterung: $ext", "pass", "Installiert");
        } else {
            addCheck("PHP Erweiterung: $ext", "fail", "Nicht installiert", true);
        }
    }
    
    // Memory Limit
    $memoryLimit = ini_get('memory_limit');
    $memoryBytes = (int)$memoryLimit * 1024 * 1024;
    if ($memoryBytes >= 128 * 1024 * 1024) {
        addCheck("Memory Limit", "pass", "Memory Limit: $memoryLimit");
    } else {
        addCheck("Memory Limit", "warn", "Memory Limit: $memoryLimit (empfohlen: 128M+)");
    }
    
    // Upload Limits
    $maxFilesize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    addCheck("Upload Limits", "info", "Max File: $maxFilesize, Post Max: $postMaxSize");
    
    // 2. Datenbankverbindung
    echo "<h2>🗄️ Datenbankverbindung</h2>";
    
    try {
        require_once __DIR__ . '/includes/database_config.php';
        $dbConfig = DatabaseConfig::getInstance();
        $db = $dbConfig->getConnection();
        
        addCheck("Datenbankverbindung", "pass", "Verbindung erfolgreich hergestellt");
        
        // Prüfe MySQL Version
        $stmt = $db->query("SELECT VERSION() as version");
        $mysqlVersion = $stmt->fetch()['version'];
        if (version_compare($mysqlVersion, '5.7.0', '>=')) {
            addCheck("MySQL Version", "pass", "MySQL $mysqlVersion ist kompatibel");
        } else {
            addCheck("MySQL Version", "warn", "MySQL $mysqlVersion (empfohlen: 5.7+)");
        }
        
        // Prüfe Tabellen
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $expectedTables = ['tests', 'test_attempts', 'test_statistics', 'daily_attempts'];
        
        foreach ($expectedTables as $table) {
            if (in_array($table, $tables)) {
                addCheck("Tabelle: $table", "pass", "Existiert");
            } else {
                addCheck("Tabelle: $table", "warn", "Nicht gefunden (wird bei Migration erstellt)");
            }
        }
        
    } catch (Exception $e) {
        addCheck("Datenbankverbindung", "fail", "Fehler: " . $e->getMessage(), true);
    }
    
    // 3. Verzeichnisberechtigungen
    echo "<h2>📁 Verzeichnisse und Berechtigungen</h2>";
    
    $directories = [
        'logs' => 'Für Protokolldateien',
        'results' => 'Für Testergebnisse', 
        'uploads' => 'Für Datei-Uploads',
        'config' => 'Für Konfigurationsdateien',
        'qrcodes' => 'Für QR-Code Generierung',
        'temp_qrcodes' => 'Für temporäre QR-Codes'
    ];
    
    foreach ($directories as $dir => $description) {
        $path = __DIR__ . '/' . $dir;
        
        if (!file_exists($path)) {
            if (mkdir($path, 0755, true)) {
                addCheck("Verzeichnis: $dir", "pass", "Erstellt ($description)");
            } else {
                addCheck("Verzeichnis: $dir", "fail", "Konnte nicht erstellt werden", true);
            }
        } else {
            if (is_writable($path)) {
                addCheck("Verzeichnis: $dir", "pass", "Beschreibbar ($description)");
            } else {
                addCheck("Verzeichnis: $dir", "fail", "Nicht beschreibbar", true);
            }
        }
    }
    
    // Prüfe Hauptverzeichnis für Instanzerstellung
    $parentDir = dirname(__DIR__);
    if (is_writable($parentDir)) {
        addCheck("Instanzenverzeichnis", "pass", "Instanzen können erstellt werden");
    } else {
        addCheck("Instanzenverzeichnis", "warn", "Instanzerstellung möglicherweise nicht möglich");
    }
    
    // 4. Externe Programme (optional)
    echo "<h2>🔧 Externe Programme (optional)</h2>";
    
    // Tesseract OCR
    exec('tesseract --version 2>&1', $tesseractOutput, $tesseractReturn);
    if ($tesseractReturn === 0) {
        addCheck("Tesseract OCR", "pass", "Installiert: " . trim($tesseractOutput[0]));
    } else {
        addCheck("Tesseract OCR", "warn", "Nicht installiert (PDF/Bild-OCR nicht verfügbar)");
    }
    
    // Ghostscript
    $gsCommands = ['gs', 'gswin64c', 'gswin32c'];
    $gsFound = false;
    foreach ($gsCommands as $cmd) {
        exec("$cmd --version 2>&1", $gsOutput, $gsReturn);
        if ($gsReturn === 0) {
            addCheck("Ghostscript", "pass", "Installiert: " . trim($gsOutput[0]));
            $gsFound = true;
            break;
        }
    }
    if (!$gsFound) {
        addCheck("Ghostscript", "warn", "Nicht installiert (PDF-Verarbeitung nicht verfügbar)");
    }
    
    // 5. System-Information
    echo "<h2>ℹ️ System-Information</h2>";
    ?>
    
    <table>
        <tr><th>Parameter</th><th>Wert</th></tr>
        <tr><td>Betriebssystem</td><td><?php echo php_uname('s r'); ?></td></tr>
        <tr><td>Webserver</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt'; ?></td></tr>
        <tr><td>PHP SAPI</td><td><?php echo php_sapi_name(); ?></td></tr>
        <tr><td>Server Name</td><td><?php echo $_SERVER['SERVER_NAME'] ?? 'Nicht gesetzt'; ?></td></tr>
        <tr><td>Document Root</td><td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Nicht gesetzt'; ?></td></tr>
        <tr><td>Aktuelles Verzeichnis</td><td><?php echo __DIR__; ?></td></tr>
        <tr><td>Freier Speicher</td><td><?php echo disk_free_space('.') ? formatBytes(disk_free_space('.')) : 'Unbekannt'; ?></td></tr>
    </table>
    
    <?php
    // Zeige alle Checks an
    echo "<h2>📊 Testergebnisse</h2>";
    
    foreach ($checks as $check) {
        $class = $check['status'];
        if ($class === 'fail') $class = 'fail';
        elseif ($class === 'warn') $class = 'warn';
        elseif ($class === 'info') $class = 'info';
        else $class = 'pass';
        
        echo "<div class='check $class'>";
        echo "<strong>" . htmlspecialchars($check['title']) . ":</strong> ";
        echo htmlspecialchars($check['message']);
        echo "</div>";
    }
    
    // Zusammenfassung
    echo "<h2>🎯 Zusammenfassung</h2>";
    
    if ($criticalErrors > 0) {
        echo "<div class='check fail'>";
        echo "<strong>❌ System nicht bereit:</strong> $criticalErrors kritische Fehler müssen behoben werden.";
        echo "</div>";
    } else {
        echo "<div class='check pass'>";
        echo "<strong>✅ System bereit:</strong> Alle kritischen Anforderungen erfüllt.";
        echo "</div>";
        
        if ($warnings > 0) {
            echo "<div class='check warn'>";
            echo "<strong>⚠️ Hinweise:</strong> $warnings Warnungen (System funktioniert, aber einige Features sind möglicherweise eingeschränkt).";
            echo "</div>";
        }
    }
    
    // Nächste Schritte
    echo "<h2>🚀 Nächste Schritte</h2>";
    
    if ($criticalErrors === 0) {
        echo "<div class='check info'>";
        echo "<strong>Deployment starten:</strong><br>";
        echo "1. <a href='deploy.php'>Standard-Deployment</a> für neue Installation<br>";
        echo "2. <a href='migrate_database.php?admin_key=migrate_db_2024'>Daten-Migration</a> wenn bereits Daten vorhanden<br>";
        echo "3. <a href='teacher/teacher_dashboard.php'>Lehrerbereich</a> nach erfolgreicher Installation";
        echo "</div>";
    } else {
        echo "<div class='check fail'>";
        echo "<strong>Kritische Fehler beheben:</strong><br>";
        echo "Bitte beheben Sie zunächst alle als 'kritisch' markierten Probleme, bevor Sie das Deployment starten.";
        echo "</div>";
    }
    ?>
    
    <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
        <small>
            <strong>Hinweis:</strong> Diese Datei kann nach erfolgreichem Deployment gelöscht werden.<br>
            <strong>Dokumentation:</strong> Siehe WEBSERVER_DEPLOYMENT.md für detaillierte Anweisungen.
        </small>
    </div>
    
</body>
</html>
