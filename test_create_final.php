<?php
/**
 * Finaler Test für Instanz-Erstellung nach DB-Fix
 */

session_start();
$_SESSION["teacher"] = true;

echo "<h1>🎯 Finaler Test: Instanz testfinal erstellen</h1>\n";

// Cleanup: Vorherige Testinstanz löschen
$instancePath = '/var/www/dianoia-ai.de/lehrer_instanzen/testfinal';
if (is_dir($instancePath)) {
    echo "🗑️ Lösche existierende Instanz...<br>\n";
    exec("rm -rf $instancePath 2>&1", $output, $return);
    if ($return === 0) {
        echo "✅ Alte Instanz gelöscht<br>\n";
    } else {
        echo "⚠️ Löschung: " . implode('<br>', $output) . "<br>\n";
    }
}

// Test auch DB löschen
$dbName = 'mcq_inst_testfinal';
require_once 'includes/database_config.php';
try {
    $dbConfig = DatabaseConfig::getInstance();
    $pdo = $dbConfig->getConnection();
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    echo "🗄️ Alte Datenbank gelöscht<br>\n";
} catch (Exception $e) {
    echo "⚠️ DB-Löschung: " . $e->getMessage() . "<br>\n";
}

echo "<h2>📋 Test-Parameter</h2>\n";
echo "Instanz-Name: testfinal<br>\n";
echo "Admin-Code: admin123<br>\n";

// POST-Parameter für create_instance.php
$_POST['instance_name'] = 'testfinal';
$_POST['admin_access_code'] = 'admin123';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h2>🚀 Instanz erstellen...</h2>\n";

// Capture Output
ob_start();
$success = false;
try {
    include 'teacher/create_instance.php';
    $success = true;
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
$createOutput = ob_get_clean();

echo "<h3>📄 Create-Output:</h3>\n";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>$createOutput</pre>\n";

// Parse JSON Output
$jsonData = null;
if (preg_match('/\{.*\}/', $createOutput, $matches)) {
    $jsonData = json_decode($matches[0], true);
}

echo "<h2>📊 Ergebnis-Analyse</h2>\n";
if ($jsonData) {
    echo "JSON Response gefunden:<br>\n";
    echo "Success: " . ($jsonData['success'] ? '✅ YES' : '❌ NO') . "<br>\n";
    echo "Message: " . htmlspecialchars($jsonData['message']) . "<br>\n";
    if (isset($jsonData['update_status'])) {
        echo "Update Status: " . ($jsonData['update_status'] ? '✅' : '❌') . "<br>\n";
    }
} else {
    echo "❌ Keine gültige JSON-Response gefunden<br>\n";
}

echo "<h2>🔍 Dateisystem-Prüfung</h2>\n";
$mcqPath = '/var/www/dianoia-ai.de/lehrer_instanzen/testfinal/mcq-test-system';
if (is_dir($mcqPath)) {
    echo "✅ Instanz-Verzeichnis erstellt: <code>$mcqPath</code><br>\n";
    
    // Wichtige Verzeichnisse prüfen
    $checks = [
        'tests' => $mcqPath . '/tests',
        'results' => $mcqPath . '/results',
        'config' => $mcqPath . '/config',
        'includes' => $mcqPath . '/includes',
        'teacher' => $mcqPath . '/teacher'
    ];
    
    foreach ($checks as $name => $path) {
        if (is_dir($path)) {
            $files = glob($path . '/*');
            $fileCount = count($files);
            
            if ($name === 'tests' || $name === 'results') {
                // Diese sollten leer sein
                $status = $fileCount === 0 ? '✅ LEER' : '❌ ' . $fileCount . ' DATEIEN';
                echo "📁 $name: $status<br>\n";
                if ($fileCount > 0 && $fileCount < 5) {
                    // Zeige erste paar Dateien
                    foreach (array_slice($files, 0, 3) as $file) {
                        echo "  - " . basename($file) . "<br>\n";
                    }
                }
            } else {
                // Diese sollten Dateien enthalten
                $status = $fileCount > 0 ? '✅ ' . $fileCount . ' DATEIEN' : '⚠️ LEER';
                echo "📁 $name: $status<br>\n";
            }
        } else {
            echo "📁 $name: ❌ FEHLT<br>\n";
        }
    }
    
    // Spezielle Dateien prüfen
    $keyFiles = [
        'index.php' => $mcqPath . '/index.php',
        'app_config.json' => $mcqPath . '/config/app_config.json',
        'database_config.php' => $mcqPath . '/includes/database_config.php'
    ];
    
    echo "<h3>🔑 Wichtige Dateien:</h3>\n";
    foreach ($keyFiles as $name => $path) {
        if (file_exists($path)) {
            $size = filesize($path);
            echo "📄 $name: ✅ ($size bytes)<br>\n";
            
            if ($name === 'app_config.json') {
                $config = json_decode(file_get_contents($path), true);
                if ($config && isset($config['admin_access_code'])) {
                    echo "  🔑 Admin-Code: " . htmlspecialchars($config['admin_access_code']) . "<br>\n";
                }
            }
        } else {
            echo "📄 $name: ❌ FEHLT<br>\n";
        }
    }
    
} else {
    echo "❌ Instanz-Verzeichnis nicht erstellt<br>\n";
}

echo "<h2>🗄️ Datenbank-Prüfung</h2>\n";
try {
    $dbConfig = DatabaseConfig::getInstance();
    $pdo = $dbConfig->getConnection();
    
    // Prüfe ob Instanz-DB existiert
    $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
    $stmt->execute(['mcq_inst_testfinal']);
    if ($stmt->fetch()) {
        echo "✅ Instanz-Datenbank erstellt: mcq_inst_testfinal<br>\n";
        
        // Verbinde zur Instanz-DB und prüfe Tabellen
        $instancePdo = new PDO("mysql:host=localhost;dbname=mcq_inst_testfinal", "mcq_user_testfinal", "defaultpassword");
        $tables = $instancePdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "📊 Tabellen: " . implode(', ', $tables) . "<br>\n";
        
        // Prüfe ob Tabellen leer sind
        foreach (['tests', 'test_attempts', 'test_statistics', 'daily_attempts'] as $table) {
            if (in_array($table, $tables)) {
                $count = $instancePdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                $status = $count == 0 ? '✅ LEER' : '⚠️ ' . $count . ' EINTRÄGE';
                echo "  📋 $table: $status<br>\n";
            }
        }
        
    } else {
        echo "❌ Instanz-Datenbank nicht erstellt<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ DB-Prüfung Fehler: " . $e->getMessage() . "<br>\n";
}

echo "<br><h2>🎯 Zusammenfassung:</h2>\n";
if ($jsonData && $jsonData['success']) {
    echo "✅ <strong>Instanz-Erstellung erfolgreich!</strong><br>\n";
    echo "🔗 Homepage: <a href='/lehrer_instanzen/testfinal/mcq-test-system/index.php' target='_blank'>/lehrer_instanzen/testfinal/mcq-test-system/index.php</a><br>\n";
    echo "🔧 Admin: <a href='/lehrer_instanzen/testfinal/mcq-test-system/teacher/teacher_dashboard.php' target='_blank'>/lehrer_instanzen/testfinal/mcq-test-system/teacher/teacher_dashboard.php</a><br>\n";
} else {
    echo "❌ <strong>Instanz-Erstellung fehlgeschlagen</strong><br>\n";
}

?>
