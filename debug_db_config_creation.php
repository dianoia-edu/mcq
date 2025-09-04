<?php
/**
 * Debug der database_config.php Erstellung
 */

echo "<h1>🔍 Debug: database_config.php Erstellung</h1>\n";

// Simuliere die Parameter aus create_instance.php
$instance_name = 'debugtest';
$target_dir = "/var/www/dianoia-ai.de/lehrer_instanzen/$instance_name/mcq-test-system";
$db_user_new_instance = "mcq_user_$instance_name";
$db_password_new_instance = "TestPassw0rd123!@#";
$db_name = "mcq_inst_$instance_name";
$config_create_instance = ['db_host' => 'localhost'];

echo "<h2>📋 Parameter</h2>\n";
echo "Instance Name: <code>$instance_name</code><br>\n";
echo "Target Dir: <code>$target_dir</code><br>\n";
echo "DB User: <code>$db_user_new_instance</code><br>\n";
echo "DB Password: <code>" . substr($db_password_new_instance, 0, 8) . "...</code><br>\n";

// Erstelle Verzeichnis falls nötig
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
    echo "📁 Zielverzeichnis erstellt<br>\n";
} else {
    echo "📁 Zielverzeichnis existiert bereits<br>\n";
}

if (!is_dir("$target_dir/includes")) {
    mkdir("$target_dir/includes", 0777, true);
    echo "📁 Includes-Verzeichnis erstellt<br>\n";
}

$new_instance_db_config_path = $target_dir . '/includes/database_config.php';
echo "<h2>📄 Datei-Erstellung</h2>\n";
echo "Ziel-Pfad: <code>$new_instance_db_config_path</code><br>\n";

// Erstelle den exakten Code aus create_instance.php
$instance_db_config = '<?php
// Automatisch generierte Datenbankkonfiguration für Instanz: ' . $instance_name . '
// Datenbankverbindungskonfiguration
class DatabaseConfig {
    private static $instance = null;
    private $connection = null;
    private $config;
    
    private function __construct() {
        // Instanz-spezifische Konfiguration
        $this->config = [
            \'db_host\' => \'' . addslashes($config_create_instance['db_host']) . '\',
            \'db_user\' => \'' . addslashes($db_user_new_instance) . '\',
            \'db_password\' => \'' . addslashes($db_password_new_instance) . '\',
            \'db_name\' => \'' . addslashes($db_name) . '\'
        ];

        // Definiere Konstanten für globalen Zugriff
        if (!defined(\'DB_HOST\')) define(\'DB_HOST\', $this->config[\'db_host\']);
        if (!defined(\'DB_USER\')) define(\'DB_USER\', $this->config[\'db_user\']);
        if (!defined(\'DB_PASS\')) define(\'DB_PASS\', $this->config[\'db_password\']);
        if (!defined(\'DB_NAME\')) define(\'DB_NAME\', $this->config[\'db_name\']);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host=" . $this->config[\'db_host\'] . ";dbname=" . $this->config[\'db_name\'] . ";charset=utf8mb4";
                $this->connection = new PDO($dsn, $this->config[\'db_user\'], $this->config[\'db_password\']);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
                throw new Exception("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
            }
        }
        return $this->connection;
    }
    
    public function createDatabase() {
        // Für Instanzen nicht nötig - DB existiert bereits
        return true;
    }
    
    public function initializeTables() {
        // Für Instanzen nicht nötig - Tabellen existieren bereits
        return true;
    }
}
?>';

echo "Inhalt-Länge: " . strlen($instance_db_config) . " Zeichen<br>\n";

echo "<h3>📄 Inhalt-Vorschau (erste 500 Zeichen):</h3>\n";
echo "<pre style='background: #f0f0f0; padding: 10px; font-size: 11px;'>";
echo htmlspecialchars(substr($instance_db_config, 0, 500));
echo "</pre>\n";

// Teste file_put_contents
echo "<h2>💾 file_put_contents Test</h2>\n";
$result = file_put_contents($new_instance_db_config_path, $instance_db_config);

if ($result !== false) {
    echo "✅ file_put_contents erfolgreich: $result bytes geschrieben<br>\n";
} else {
    echo "❌ file_put_contents fehlgeschlagen<br>\n";
}

// Prüfe Datei
echo "<h2>🔍 Datei-Prüfung</h2>\n";
if (file_exists($new_instance_db_config_path)) {
    $size = filesize($new_instance_db_config_path);
    echo "✅ Datei existiert: $size bytes<br>\n";
    
    if ($size > 0) {
        $content = file_get_contents($new_instance_db_config_path);
        echo "📄 Inhalt gelesen: " . strlen($content) . " Zeichen<br>\n";
        
        echo "<h3>📄 Tatsächlicher Inhalt (erste 300 Zeichen):</h3>\n";
        echo "<pre style='background: #f0f0f0; padding: 10px; font-size: 11px;'>";
        echo htmlspecialchars(substr($content, 0, 300));
        echo "</pre>\n";
    } else {
        echo "⚠️ Datei ist leer<br>\n";
    }
} else {
    echo "❌ Datei existiert nicht<br>\n";
}

// Teste Verzeichnis-Berechtigungen
echo "<h2>🔒 Berechtigungs-Test</h2>\n";
$includesDir = "$target_dir/includes";
echo "Includes-Verzeichnis: <code>$includesDir</code><br>\n";
echo "Lesbar: " . (is_readable($includesDir) ? '✅' : '❌') . "<br>\n";
echo "Schreibbar: " . (is_writable($includesDir) ? '✅' : '❌') . "<br>\n";

$permissions = substr(sprintf('%o', fileperms($includesDir)), -4);
echo "Berechtigungen: $permissions<br>\n";

// Cleanup
echo "<h2>🗑️ Cleanup</h2>\n";
if (file_exists($new_instance_db_config_path)) {
    unlink($new_instance_db_config_path);
    echo "🗑️ Test-Datei gelöscht<br>\n";
}
if (is_dir($target_dir)) {
    exec("rm -rf $target_dir");
    echo "🗑️ Test-Verzeichnis gelöscht<br>\n";
}

?>
