<?php
/**
 * Debug-Script für DB-Konstanten
 */

echo "<h1>🔍 Debug: Datenbank-Konstanten</h1>\n";

echo "<h2>1. Vor DatabaseConfig laden</h2>\n";
echo "DB_HOST defined: " . (defined('DB_HOST') ? 'YES' : 'NO') . "<br>\n";
echo "DB_USER defined: " . (defined('DB_USER') ? 'YES' : 'NO') . "<br>\n";
echo "DB_PASS defined: " . (defined('DB_PASS') ? 'YES' : 'NO') . "<br>\n";

echo "<h2>2. DatabaseConfig laden</h2>\n";
$configPath = __DIR__ . '/includes/database_config.php';
echo "Config-Pfad: <code>$configPath</code><br>\n";
echo "Existiert: " . (file_exists($configPath) ? 'YES' : 'NO') . "<br>\n";

if (file_exists($configPath)) {
    require_once $configPath;
    echo "✅ DatabaseConfig geladen<br>\n";
} else {
    echo "❌ DatabaseConfig nicht gefunden<br>\n";
    exit;
}

echo "<h2>3. Nach DatabaseConfig laden (vor getInstance)</h2>\n";
echo "DB_HOST defined: " . (defined('DB_HOST') ? 'YES' : 'NO') . "<br>\n";
echo "DB_USER defined: " . (defined('DB_USER') ? 'YES' : 'NO') . "<br>\n";
echo "DB_PASS defined: " . (defined('DB_PASS') ? 'YES' : 'NO') . "<br>\n";

echo "<h2>4. DatabaseConfig getInstance</h2>\n";
try {
    $dbConfig = DatabaseConfig::getInstance();
    echo "✅ getInstance erfolgreich<br>\n";
} catch (Exception $e) {
    echo "❌ getInstance Fehler: " . $e->getMessage() . "<br>\n";
    exit;
}

echo "<h2>5. Nach getInstance</h2>\n";
echo "DB_HOST defined: " . (defined('DB_HOST') ? 'YES (' . DB_HOST . ')' : 'NO') . "<br>\n";
echo "DB_USER defined: " . (defined('DB_USER') ? 'YES (' . DB_USER . ')' : 'NO') . "<br>\n";
echo "DB_PASS defined: " . (defined('DB_PASS') ? 'YES (' . (DB_PASS ? '[SET]' : '[EMPTY]') . ')' : 'NO') . "<br>\n";

echo "<h2>6. getConnection Test</h2>\n";
try {
    $connection = $dbConfig->getConnection();
    if ($connection) {
        echo "✅ Datenbankverbindung erfolgreich<br>\n";
    } else {
        echo "❌ Datenbankverbindung fehlgeschlagen<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Verbindungsfehler: " . $e->getMessage() . "<br>\n";
}

echo "<h2>7. Server-Info</h2>\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'nicht gesetzt') . "<br>\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'nicht gesetzt') . "<br>\n";
echo "Production Flag: " . (file_exists('/var/www/production_flag') ? 'YES' : 'NO') . "<br>\n";

?>
