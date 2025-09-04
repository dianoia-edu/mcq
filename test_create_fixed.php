<?php
/**
 * Test nach allen Pfad- und DB-Korrekturen
 */

session_start();
$_SESSION["teacher"] = true;

echo "<h1>🔧 Test: Alle Korrekturen angewendet</h1>\n";

// Cleanup
$instanceName = 'testfixed';
$instancePath = "/var/www/dianoia-ai.de/lehrer_instanzen/$instanceName";
$dbName = "mcq_inst_$instanceName";

// Lösche alte Instanz
if (is_dir($instancePath)) {
    exec("rm -rf $instancePath");
    echo "🗑️ Alte Instanz gelöscht<br>\n";
}

// Lösche alte DB
require_once 'includes/database_config.php';
try {
    $dbConfig = DatabaseConfig::getInstance();
    $pdo = $dbConfig->getConnection();
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    $pdo->exec("DROP USER IF EXISTS 'mcq_user_$instanceName'@'localhost'");
    $pdo->exec("DROP USER IF EXISTS 'mcq_user_$instanceName'@'%'");
    $pdo->exec("FLUSH PRIVILEGES");
    echo "🗄️ Alte DB und Benutzer gelöscht<br>\n";
} catch (Exception $e) {
    echo "⚠️ DB-Cleanup: " . $e->getMessage() . "<br>\n";
}

echo "<h2>🚀 Neue Instanz erstellen</h2>\n";

// Setze Parameter
$_POST['instance_name'] = $instanceName;
$_POST['admin_access_code'] = 'admin123';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Führe create_instance.php aus
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
if (preg_match('/\{.*\}/', $output, $matches)) {
    $jsonData = json_decode($matches[0], true);
    echo "<h2>📊 JSON-Analyse</h2>\n";
    echo "Success: " . ($jsonData['success'] ? '✅ YES' : '❌ NO') . "<br>\n";
    echo "Message: " . htmlspecialchars($jsonData['message']) . "<br>\n";
}

echo "<h2>🔍 Detaillierte Prüfung</h2>\n";

// Dateisystem
$mcqPath = "/var/www/dianoia-ai.de/lehrer_instanzen/$instanceName/mcq-test-system";
echo "<h3>📁 Dateisystem</h3>\n";
if (is_dir($mcqPath)) {
    echo "✅ MCQ-System-Verzeichnis: <code>$mcqPath</code><br>\n";
    
    // Prüfe kritische Dateien
    $files = [
        'includes/database_config.php',
        'config/app_config.json',
        'index.php'
    ];
    
    foreach ($files as $file) {
        $path = "$mcqPath/$file";
        if (file_exists($path)) {
            echo "✅ $file (" . filesize($path) . " bytes)<br>\n";
        } else {
            echo "❌ $file FEHLT<br>\n";
        }
    }
    
    // Prüfe tests/results
    $testsPath = "$mcqPath/tests";
    $resultsPath = "$mcqPath/results";
    
    if (is_dir($testsPath)) {
        $testFiles = glob("$testsPath/*");
        echo "📁 tests: " . count($testFiles) . " Dateien ";
        echo (count($testFiles) === 0 ? '✅' : '❌') . "<br>\n";
    }
    
    if (is_dir($resultsPath)) {
        $resultFiles = glob("$resultsPath/*");
        echo "📁 results: " . count($resultFiles) . " Dateien ";
        echo (count($resultFiles) === 0 ? '✅' : '❌') . "<br>\n";
    }
    
} else {
    echo "❌ MCQ-System-Verzeichnis nicht erstellt<br>\n";
}

// Datenbank
echo "<h3>🗄️ Datenbank</h3>\n";
try {
    $dbConfig = DatabaseConfig::getInstance();
    $pdo = $dbConfig->getConnection();
    
    // Prüfe DB existiert
    $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
    $stmt->execute([$dbName]);
    if ($stmt->fetch()) {
        echo "✅ Datenbank $dbName erstellt<br>\n";
        
        // Teste Verbindung mit Instanz-Benutzer
        $dbUser = "mcq_user_$instanceName";
        
        // Finde das Passwort (grob - normalerweise würde man es speichern)
        // Für Test verwenden wir ein bekanntes Muster
        $testPasswords = [];
        
        // Generiere mögliche Passwörter basierend auf dem Algorithmus
        for ($i = 0; $i < 5; $i++) {
            $password = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*'), 0, 16) . bin2hex(random_bytes(4));
            $testPasswords[] = $password;
        }
        
        // Oder versuche Standard-Passwörter
        $testPasswords[] = 'defaultpassword';
        $testPasswords[] = 'admin123';
        
        $connected = false;
        foreach ($testPasswords as $testPwd) {
            try {
                $testPdo = new PDO("mysql:host=localhost;dbname=$dbName", $dbUser, $testPwd);
                echo "✅ Benutzer-Verbindung erfolgreich mit Passwort: " . substr($testPwd, 0, 8) . "...<br>\n";
                
                // Teste Tabellen
                $tables = $testPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "📊 Tabellen (" . count($tables) . "): " . implode(', ', $tables) . "<br>\n";
                
                $connected = true;
                break;
            } catch (PDOException $e) {
                // Ignore, try next password
            }
        }
        
        if (!$connected) {
            echo "❌ Keine erfolgreiche Benutzer-Verbindung möglich<br>\n";
            
            // Zeige bestehende Benutzer
            $users = $pdo->query("SELECT User, Host FROM mysql.user WHERE User LIKE 'mcq_user_%'")->fetchAll();
            echo "Bestehende MCQ-Benutzer:<br>\n";
            foreach ($users as $user) {
                echo "  - {$user['User']}@{$user['Host']}<br>\n";
            }
        }
        
    } else {
        echo "❌ Datenbank $dbName nicht erstellt<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ DB-Test Fehler: " . $e->getMessage() . "<br>\n";
}

echo "<h2>🎯 Endergebnis</h2>\n";
if ($jsonData && $jsonData['success']) {
    echo "✅ <strong>Instanz-Erstellung erfolgreich!</strong><br>\n";
    echo "🔗 Homepage: <a href='/lehrer_instanzen/$instanceName/mcq-test-system/' target='_blank'>Zur Instanz</a><br>\n";
} else {
    echo "❌ <strong>Instanz-Erstellung fehlgeschlagen</strong><br>\n";
}

?>
