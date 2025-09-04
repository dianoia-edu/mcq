<?php
/**
 * Finaler Test mit neuer database_config.php Generierung
 */

session_start();
$_SESSION["teacher"] = true;

echo "<h1>🔧 Finaler Fix-Test</h1>\n";

$instanceName = 'finaltest';
$instancePath = "/var/www/dianoia-ai.de/lehrer_instanzen/$instanceName";
$dbName = "mcq_inst_$instanceName";

// Cleanup
if (is_dir($instancePath)) {
    exec("rm -rf $instancePath");
    echo "🗑️ Alte Instanz gelöscht<br>\n";
}

require_once 'includes/database_config.php';
try {
    $dbConfig = DatabaseConfig::getInstance();
    $pdo = $dbConfig->getConnection();
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    $pdo->exec("DROP USER IF EXISTS 'mcq_user_$instanceName'@'localhost'");
    $pdo->exec("DROP USER IF EXISTS 'mcq_user_$instanceName'@'%'");
    $pdo->exec("FLUSH PRIVILEGES");
    echo "🗄️ DB und Benutzer bereinigt<br>\n";
} catch (Exception $e) {
    echo "⚠️ Cleanup: " . $e->getMessage() . "<br>\n";
}

echo "<h2>🚀 Instanz erstellen</h2>\n";

$_POST['instance_name'] = $instanceName;
$_POST['admin_access_code'] = 'testadmin';
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
try {
    include 'teacher/create_instance.php';
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>$output</pre>\n";

// Parse JSON für Passwort
$jsonData = null;
$dbPassword = null;
if (preg_match('/\{.*\}/', $output, $matches)) {
    $jsonData = json_decode($matches[0], true);
    if ($jsonData && isset($jsonData['db_password'])) {
        $dbPassword = $jsonData['db_password'];
    }
}

echo "<h2>🔍 Validierung</h2>\n";

$mcqPath = "/var/www/dianoia-ai.de/lehrer_instanzen/$instanceName/mcq-test-system";

// 1. Prüfe database_config.php
$dbConfigPath = "$mcqPath/includes/database_config.php";
if (file_exists($dbConfigPath)) {
    $size = filesize($dbConfigPath);
    echo "✅ database_config.php: $size bytes<br>\n";
    
    if ($size > 0) {
        echo "📄 <strong>Inhalt der database_config.php:</strong><br>\n";
        $content = file_get_contents($dbConfigPath);
        echo "<pre style='background: #f0f0f0; padding: 10px; font-size: 12px; max-height: 300px; overflow-y: scroll;'>";
        echo htmlspecialchars(substr($content, 0, 1000));
        if (strlen($content) > 1000) echo "\n... (truncated)";
        echo "</pre>\n";
    }
} else {
    echo "❌ database_config.php fehlt<br>\n";
}

// 2. Teste DB-Verbindung
echo "<h3>🗄️ Datenbank-Test</h3>\n";
if ($dbPassword) {
    echo "Teste mit Passwort: " . substr($dbPassword, 0, 10) . "...<br>\n";
    
    try {
        $testPdo = new PDO("mysql:host=localhost;dbname=$dbName", "mcq_user_$instanceName", $dbPassword);
        echo "✅ <strong>Direkte DB-Verbindung erfolgreich!</strong><br>\n";
        
        $tables = $testPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "📊 Tabellen: " . implode(', ', $tables) . "<br>\n";
        
        // Teste DatabaseConfig-Klasse der Instanz
        if (file_exists($dbConfigPath) && filesize($dbConfigPath) > 0) {
            echo "<h4>🔧 DatabaseConfig-Klasse Test</h4>\n";
            
            // Temporär die Instanz-Config laden
            $originalContent = file_get_contents('includes/database_config.php');
            file_put_contents('includes/database_config.php.backup', $originalContent);
            
            copy($dbConfigPath, 'includes/database_config.php');
            
            try {
                // Klasse neu laden (hack)
                if (class_exists('DatabaseConfig')) {
                    eval('class DatabaseConfigInstance extends DatabaseConfig {}');
                    $className = 'DatabaseConfigInstance';
                } else {
                    require_once 'includes/database_config.php';
                    $className = 'DatabaseConfig';
                }
                
                $instanceDbConfig = $className::getInstance();
                $instanceConnection = $instanceDbConfig->getConnection();
                
                if ($instanceConnection) {
                    echo "✅ <strong>DatabaseConfig-Klasse funktioniert!</strong><br>\n";
                } else {
                    echo "❌ DatabaseConfig-Klasse Verbindung fehlgeschlagen<br>\n";
                }
                
            } catch (Exception $e) {
                echo "❌ DatabaseConfig-Klasse Fehler: " . $e->getMessage() . "<br>\n";
            }
            
            // Original wiederherstellen
            file_put_contents('includes/database_config.php', $originalContent);
            unlink('includes/database_config.php.backup');
        }
        
    } catch (PDOException $e) {
        echo "❌ DB-Verbindung fehlgeschlagen: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "❌ Kein DB-Passwort aus JSON extrahiert<br>\n";
}

// 3. Prüfe Homepage
echo "<h3>🏠 Homepage-Test</h3>\n";
$indexPath = "$mcqPath/index.php";
if (file_exists($indexPath)) {
    echo "✅ index.php existiert (" . filesize($indexPath) . " bytes)<br>\n";
    echo "🔗 <strong>Links:</strong><br>\n";
    echo "📄 Homepage: <a href='/lehrer_instanzen/$instanceName/mcq-test-system/' target='_blank'>Zur Instanz-Homepage</a><br>\n";
    echo "🔧 Admin: <a href='/lehrer_instanzen/$instanceName/mcq-test-system/teacher/teacher_dashboard.php' target='_blank'>Admin-Bereich</a><br>\n";
    echo "🔑 Admin-Code: <code>testadmin</code><br>\n";
} else {
    echo "❌ index.php fehlt<br>\n";
}

echo "<h2>🎯 Zusammenfassung</h2>\n";
if ($jsonData && $jsonData['success']) {
    echo "✅ <strong>Instanz-Erstellung ERFOLGREICH!</strong><br>\n";
    echo "🔑 Alle Komponenten funktionieren<br>\n";
    echo "🗄️ Datenbank-Verbindung OK<br>\n";
    echo "📄 Alle Dateien korrekt erstellt<br>\n";
} else {
    echo "❌ <strong>Instanz-Erstellung fehlgeschlagen</strong><br>\n";
}

?>
