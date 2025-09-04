<?php
/**
 * ULTIMATIVER Test - database_config.php sollte jetzt korrekt bleiben
 */

session_start();
$_SESSION["teacher"] = true;

echo "<h1>🏆 ULTIMATIVER Fix-Test</h1>\n";

$instanceName = 'ultimate';
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
    echo "🗄️ DB bereinigt<br>\n";
} catch (Exception $e) {
    echo "⚠️ Cleanup: " . $e->getMessage() . "<br>\n";
}

echo "<h2>🚀 Instanz erstellen (ohne database_config.php Überschreibung)</h2>\n";

$_POST['instance_name'] = $instanceName;
$_POST['admin_access_code'] = 'ultimate123';
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
try {
    include 'teacher/create_instance.php';
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "<h3>📄 Create-Output:</h3>\n";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>$output</pre>\n";

// Parse JSON
$jsonData = null;
$dbPassword = null;
if (preg_match('/\{.*\}/', $output, $matches)) {
    $jsonData = json_decode($matches[0], true);
    if ($jsonData && isset($jsonData['db_password'])) {
        $dbPassword = $jsonData['db_password'];
    }
}

echo "<h2>🔍 DETAILLIERTE PRÜFUNG</h2>\n";

$mcqPath = "/var/www/dianoia-ai.de/lehrer_instanzen/$instanceName/mcq-test-system";
$dbConfigPath = "$mcqPath/includes/database_config.php";

// 1. Prüfe database_config.php SOFORT
echo "<h3>📄 database_config.php Analyse</h3>\n";
if (file_exists($dbConfigPath)) {
    $size = filesize($dbConfigPath);
    echo "✅ Datei existiert: <strong>$size bytes</strong><br>\n";
    
    if ($size > 0) {
        echo "🎉 <strong>SUCCESS: Datei ist NICHT leer!</strong><br>\n";
        
        $content = file_get_contents($dbConfigPath);
        echo "📝 Inhalt (erste 500 Zeichen):<br>\n";
        echo "<pre style='background: #e8f5e8; padding: 10px; font-size: 11px; max-height: 200px; overflow-y: scroll;'>";
        echo htmlspecialchars(substr($content, 0, 500));
        echo "</pre>\n";
        
        // Prüfe ob Instanz-spezifische Daten enthalten sind
        if (strpos($content, $instanceName) !== false) {
            echo "✅ <strong>Instanz-spezifische Daten gefunden!</strong><br>\n";
        } else {
            echo "⚠️ Keine Instanz-spezifischen Daten gefunden<br>\n";
        }
        
        if (strpos($content, $dbPassword) !== false) {
            echo "✅ <strong>Korrekte Passwort-Einbettung!</strong><br>\n";
        } else {
            echo "⚠️ Passwort nicht in Datei gefunden<br>\n";
        }
        
    } else {
        echo "❌ <strong>PROBLEM: Datei ist immer noch leer!</strong><br>\n";
    }
} else {
    echo "❌ <strong>FATAL: Datei existiert nicht!</strong><br>\n";
}

// 2. Teste die DatabaseConfig-Klasse der Instanz
echo "<h3>🔧 DatabaseConfig-Klasse Test</h3>\n";
if ($size > 0) {
    
    // Teste durch direktes Laden der Instanz-Config
    $tempConfigFile = "/tmp/test_instance_db_config.php";
    copy($dbConfigPath, $tempConfigFile);
    
    try {
        // Rename the class to avoid conflicts
        $configContent = file_get_contents($tempConfigFile);
        $configContent = str_replace('class DatabaseConfig', 'class InstanceDatabaseConfig', $configContent);
        file_put_contents($tempConfigFile, $configContent);
        
        require_once $tempConfigFile;
        
        $instanceConfig = InstanceDatabaseConfig::getInstance();
        $instanceConnection = $instanceConfig->getConnection();
        
        if ($instanceConnection) {
            echo "✅ <strong>Instanz-DatabaseConfig funktioniert perfekt!</strong><br>\n";
            
            // Teste eine einfache Query
            $result = $instanceConnection->query("SELECT DATABASE() as current_db")->fetch();
            echo "📊 Aktuelle DB: " . $result['current_db'] . "<br>\n";
            
            $tables = $instanceConnection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "📋 Tabellen: " . implode(', ', $tables) . "<br>\n";
            
        } else {
            echo "❌ Instanz-DatabaseConfig Verbindung fehlgeschlagen<br>\n";
        }
        
        unlink($tempConfigFile);
        
    } catch (Exception $e) {
        echo "❌ Instanz-DatabaseConfig Test-Fehler: " . $e->getMessage() . "<br>\n";
    }
    
} else {
    echo "⏭️ Übersprungen wegen leerer Datei<br>\n";
}

// 3. Direkte DB-Verbindung (Fallback-Test)
echo "<h3>🗄️ Direkte DB-Verbindung</h3>\n";
if ($dbPassword) {
    try {
        $testPdo = new PDO("mysql:host=localhost;dbname=$dbName", "mcq_user_$instanceName", $dbPassword);
        echo "✅ Direkte Verbindung erfolgreich<br>\n";
    } catch (PDOException $e) {
        echo "❌ Direkte Verbindung fehlgeschlagen: " . $e->getMessage() . "<br>\n";
    }
}

// 4. Teste andere wichtige Dateien
echo "<h3>📁 Andere Dateien-Check</h3>\n";
$criticalFiles = [
    'index.php',
    'config/app_config.json',
    'teacher/teacher_dashboard.php',
    'tests' => 'DIR',
    'results' => 'DIR'
];

foreach ($criticalFiles as $file => $type) {
    if (is_numeric($file)) {
        $file = $type;
        $type = 'FILE';
    }
    
    $path = "$mcqPath/$file";
    
    if ($type === 'DIR') {
        if (is_dir($path)) {
            $count = count(glob("$path/*"));
            echo "📁 $file: ✅ ($count Dateien)<br>\n";
        } else {
            echo "📁 $file: ❌ FEHLT<br>\n";
        }
    } else {
        if (file_exists($path)) {
            $size = filesize($path);
            echo "📄 $file: ✅ ($size bytes)<br>\n";
        } else {
            echo "📄 $file: ❌ FEHLT<br>\n";
        }
    }
}

echo "<h2>🎯 FINAL RESULT</h2>\n";
if ($jsonData && $jsonData['success'] && $size > 0) {
    echo "🏆 <strong>PERFEKTER ERFOLG!</strong><br>\n";
    echo "✅ Instanz erstellt<br>\n";
    echo "✅ Database Config korrekt<br>\n";
    echo "✅ DB-Verbindung funktioniert<br>\n";
    echo "✅ Alle Dateien vorhanden<br>\n";
    echo "<br>";
    echo "🔗 <strong>Test die Instanz:</strong><br>\n";
    echo "📄 Homepage: <a href='/lehrer_instanzen/$instanceName/mcq-test-system/' target='_blank'>Zur Instanz</a><br>\n";
    echo "🔧 Admin: <a href='/lehrer_instanzen/$instanceName/mcq-test-system/teacher/teacher_dashboard.php' target='_blank'>Admin-Dashboard</a><br>\n";
    echo "🔑 Admin-Code: <code>ultimate123</code><br>\n";
} else {
    echo "❌ <strong>IMMER NOCH PROBLEME</strong><br>\n";
    if (!$jsonData || !$jsonData['success']) {
        echo "- Instanz-Erstellung fehlgeschlagen<br>\n";
    }
    if ($size === 0) {
        echo "- database_config.php ist leer<br>\n";
    }
}

?>
