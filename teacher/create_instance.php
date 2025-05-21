<?php
// teacher/create_instance.php
session_start();

// Sicherstellen, dass nur der Super-Admin darauf zugreifen kann
if (!isset($_SESSION["teacher"]) || $_SESSION["teacher"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert.']);
    exit;
}

header('Content-Type: application/json');

// Lade die Datenbankkonfiguration der Hauptinstanz
// Diese Datei sollte DB_HOST, DB_USER (mit Rechten zum Erstellen von DBs/Usern) und DB_PASS definieren.
$main_db_config_path = dirname(__DIR__) . '/config/database_config.php';

if (file_exists($main_db_config_path)) {
    require_once $main_db_config_path;
} else {
    echo json_encode(['success' => false, 'message' => 'Fehler: Haupt-Datenbankkonfigurationsdatei nicht gefunden.']);
    exit;
}

// Überprüfe, ob die notwendigen DB-Konstanten geladen wurden
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
    echo json_encode(['success' => false, 'message' => 'Fehler: DB_HOST, DB_USER oder DB_PASS nicht in der Haupt-Konfigurationsdatei definiert.']);
    exit;
}

// Pfad-Konfiguration
// Annahme: Das Verzeichnis 'lehrer_instanzen' soll auf derselben Ebene wie 'mcq-test-system' liegen.
// z.B. /xampp/htdocs/mcq-test-system UND /xampp/htdocs/lehrer_instanzen
$base_lehrer_instances_path = dirname(dirname(__DIR__)) . '/lehrer_instanzen/';
// Pfad zur aktuellen Super-Admin MCQ-System-Installation (die als Quelle dient)
$source_system_path = dirname(dirname(__DIR__)) . '/';

$config_create_instance = [
    'base_lehrer_instances_path' => $base_lehrer_instances_path,
    'source_system_path' => $source_system_path,
    'db_super_user' => DB_USER,         // Aus der Haupt-Konfig
    'db_super_password' => DB_PASS,     // Aus der Haupt-Konfig
    'db_host' => DB_HOST,               // Aus der Haupt-Konfig
    'default_new_db_user_prefix' => 'mcq_user_', // Präfix für neu erstellte DB-Benutzer
    'db_schema_file' => dirname(__DIR__) . '/config/schema.sql', // Pfad zur SQL-Datei mit dem DB-Schema
];

// Hilfsfunktion zum rekursiven Kopieren von Verzeichnissen
function recurse_copy($src, $dst) {
    $dir = opendir($src);
    if (!$dir) {
        return false;
    }
    if (!is_dir($dst)) {
        if (!mkdir($dst, 0777, true)) {
            closedir($dir);
            return false;
        }
    }
    while (($file = readdir($dir)) !== false) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                if (!recurse_copy($src . '/' . $file, $dst . '/' . $file)) {
                    closedir($dir);
                    return false;
                }
            } else {
                if (!copy($src . '/' . $file, $dst . '/' . $file)) {
                    closedir($dir);
                    return false;
                }
            }
        }
    }
    closedir($dir);
    return true;
}

// Hilfsfunktion zum Ausführen von SQL-Befehlen
function execute_sql($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Fehler: " . $e->getMessage() . " SQL: " . $sql);
        throw $e; // Erneut werfen, um in der Hauptfunktion gefangen zu werden
    }
}

if (!is_dir($config_create_instance['base_lehrer_instances_path'])) {
    if (!mkdir($config_create_instance['base_lehrer_instances_path'], 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Fehler: Basisverzeichnis für Instanzen konnte nicht erstellt werden.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instance_name_raw = $_POST['instance_name'] ?? '';
    $admin_access_code = $_POST['admin_access_code'] ?? 'admin123';

    $instance_name = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', $instance_name_raw));
    $instance_name = preg_replace('/-+/', '-', $instance_name); 
    $instance_name = preg_replace('/_+/', '_', $instance_name);   
    $instance_name = trim($instance_name, '_-');

    if (empty($instance_name)) {
        echo json_encode(['success' => false, 'message' => 'Instanzname darf nicht leer sein oder enthält ungültige Zeichen.']);
        exit;
    }

    if (empty($admin_access_code)) {
        echo json_encode(['success' => false, 'message' => 'Admin-Zugangscode darf nicht leer sein.']);
        exit;
    }

    $target_dir = rtrim($config_create_instance['base_lehrer_instances_path'], '/') . '/' . $instance_name;
    $db_name = 'mcq_inst_' . $instance_name; 
    $db_user_new_instance = $config_create_instance['default_new_db_user_prefix'] . $instance_name;
    $db_password_new_instance = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*', ceil(16/62))),1,16) . bin2hex(random_bytes(4));

    if (is_dir($target_dir)) {
        echo json_encode(['success' => false, 'message' => 'Fehler: Ein Verzeichnis mit diesem Instanznamen existiert bereits.']);
        exit;
    }

    // PDO-Verbindung mit Superuser-Rechten herstellen (ohne spezifische DB auszuwählen)
    $pdo_super = null;
    try {
        $dsn = "mysql:host=" . $config_create_instance['db_host'];
        $pdo_super = new PDO($dsn, $config_create_instance['db_super_user'], $config_create_instance['db_super_password']);
        $pdo_super->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log("DB Superuser Verbindungsfehler: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Datenbank-Superuser-Verbindung fehlgeschlagen: ' . $e->getMessage()]);
        exit;
    }

    // Prüfen, ob Zieldatenbank bereits existiert
    try {
        $stmt = execute_sql($pdo_super, "SHOW DATABASES LIKE ?", [$db_name]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Fehler: Datenbank ' . $db_name . ' existiert bereits.']);
            $pdo_super = null; // Verbindung schließen
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Fehler bei der Prüfung der Datenbankexistenz: ' . $e->getMessage()]);
        $pdo_super = null;
        exit;
    }

    // Schritt 1: Verzeichnisse und Dateien kopieren
    // Ausschlussliste: Welche Ordner/Dateien NICHT kopiert werden sollen
    $exclude_list = [
        rtrim($config_create_instance['source_system_path'], '/') . '/lehrer_instanzen', // Nicht sich selbst rekursiv kopieren
        // Füge hier weitere Pfade hinzu, die nicht mitkopiert werden sollen, z.B. .git, node_modules etc.
        // Wichtig: Pfade müssen absolut sein oder relativ zum $source_system_path
    ];
    
    // Funktion zum rekursiven Kopieren mit Ausschlussliste
    function smart_recurse_copy($src, $dst, $exclude = []) {
        $dir = opendir($src);
        if (!$dir) return false;
        if (!is_dir($dst)) {
            if (!mkdir($dst, 0777, true)) {
                 closedir($dir); return false;
            }
        }
        while (($file = readdir($dir)) !== false) {
            if (($file != '.') && ($file != '..')) {
                $src_path = rtrim($src, '/') . '/' . $file;
                $dst_path = rtrim($dst, '/') . '/' . $file;
                
                // Überprüfe, ob der aktuelle Pfad in der Ausschlussliste ist
                $skip = false;
                foreach ($exclude as $ex_item) {
                    if (rtrim($src_path, '/') == rtrim($ex_item, '/')) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                if (is_dir($src_path)) {
                    if (!smart_recurse_copy($src_path, $dst_path, $exclude)) {
                        closedir($dir); return false;
                    }
                } else {
                    if (!copy($src_path, $dst_path)) {
                        closedir($dir); return false;
                    }
                }
            }
        }
        closedir($dir);
        return true;
    }

    if (!smart_recurse_copy($config_create_instance['source_system_path'], $target_dir, $exclude_list)) {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Kopieren der Dateien.']);
        $pdo_super = null;
        exit;
    }

    try {
        // Schritt 2: Datenbank erstellen
        execute_sql($pdo_super, "CREATE DATABASE IF NOT EXISTS \`" . $db_name . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Schritt 3: Neuen Datenbankbenutzer erstellen und Rechte vergeben
        execute_sql($pdo_super, "CREATE USER IF NOT EXISTS ?@? IDENTIFIED BY ?", [$db_user_new_instance, $config_create_instance['db_host'], $db_password_new_instance]);
        execute_sql($pdo_super, "GRANT ALL PRIVILEGES ON \`" . $db_name . "`.* TO ?@?", [$db_user_new_instance, $config_create_instance['db_host']]);
        execute_sql($pdo_super, "FLUSH PRIVILEGES");

        // Schritt 4: Konfigurationsdatei der neuen Instanz anpassen (database_config.php)
        $new_instance_db_config_path = $target_dir . '/includes/database_config.php';
        if (file_exists($new_instance_db_config_path)) {
            $config_content = file_get_contents($new_instance_db_config_path);
            // Ersetze die DB-Zugangsdaten in der geklonten Datei
            // Dies ist ein einfacher Ansatz. Für komplexere Konfigs wären robustere Methoden nötig.
            $config_content = preg_replace("/'db_host'/s*=>/s*'[^']*'/", "'db_host' => '" . $config_create_instance['db_host'] . "'", $config_content);
            $config_content = preg_replace("/'db_user'/s*=>/s*'[^']*'/", "'db_user' => '" . $db_user_new_instance . "'", $config_content);
            $config_content = preg_replace("/'db_password'/s*=>/s*'[^']*'/", "'db_password' => '" . $db_password_new_instance . "'", $config_content);
            $config_content = preg_replace("/'db_name'/s*=>/s*'[^']*'/", "'db_name' => '" . $db_name . "'", $config_content);
            // Auch die Konstanten-Definitionen anpassen, falls vorhanden und hartcodiert
            $config_content = preg_replace("/define(/s*'DB_HOST'/s*,/s*'[^']*'/s*;/i/", "define('DB_HOST', '" . $config_create_instance['db_host'] . "');", $config_content);
            $config_content = preg_replace("/define(/s*'DB_USER'/s*,/s*'[^']*'/s*;/i/", "define('DB_USER', '" . $db_user_new_instance . "');", $config_content);
            $config_content = preg_replace("/define(/s*'DB_PASS'/s*,/s*'[^']*'/s*;/i/", "define('DB_PASS', '" . $db_password_new_instance . "');", $config_content);
            $config_content = preg_replace("/define(/s*'DB_NAME'/s*,/s*'[^']*'/s*;/i/", "define('DB_NAME', '" . $db_name . "');", $config_content);

            file_put_contents($new_instance_db_config_path, $config_content);
        } else {
            throw new Exception("database_config.php in der neuen Instanz nicht gefunden.");
        }

        // Schritt 5: Admin-Zugangscode in der neuen Instanz anpassen (index.php)
        $new_instance_index_path = $target_dir . '/index.php';
        if (file_exists($new_instance_index_path)) {
            $index_content = file_get_contents($new_instance_index_path);
            // Ersetze den Standard-Admin-Login-Code
            $index_content = preg_replace('/if ($accessCode === "admin123")/', 'if ($accessCode === "' . $admin_access_code . '")', $index_content);
            file_put_contents($new_instance_index_path, $index_content);
        } else {
             error_log("index.php in der neuen Instanz nicht gefunden unter: " . $new_instance_index_path);
            // Kein harter Fehler, aber Logging
        }

        // Schritt 6: Datenbankschema importieren
        $schema_file = $config_create_instance['db_schema_file'];
        if (file_exists($schema_file)) {
            $sql_schema = file_get_contents($schema_file);
            // Verbinde dich mit der neu erstellten DB der Instanz
            $pdo_instance = new PDO("mysql:host=" . $config_create_instance['db_host'] . ";dbname=" . $db_name, $db_user_new_instance, $db_password_new_instance);
            $pdo_instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo_instance->exec($sql_schema); // Führe das gesamte Schema-Skript aus
            $pdo_instance = null; // Verbindung schließen
        } else {
            throw new Exception("Datenbankschema-Datei nicht gefunden: " . $schema_file);
        }

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $web_path_to_instances = str_replace($_SERVER['DOCUMENT_ROOT'], '', $config_create_instance['base_lehrer_instances_path']);
        $web_path_to_instances = '/' . ltrim(str_replace('\\', '/', $web_path_to_instances), '/');
        $instance_url = $protocol . $host . rtrim($web_path_to_instances, '/') . '/' . $instance_name . '/';

        echo json_encode([
            'success' => true,
            'message' => 'Instanz erfolgreich erstellt!',
            'url' => $instance_url,
            'admin_code' => $admin_access_code,
            'db_name' => $db_name,
            'db_user' => $db_user_new_instance,
            'db_password' => $db_password_new_instance // Zeige das generierte Passwort dem Admin
        ]);

    } catch (PDOException $e) {
        // Aufräumen bei DB-Fehler (optional, aber gut für Konsistenz)
        // if (is_dir($target_dir)) { /* Lösche Verzeichnis */ }
        // execute_sql($pdo_super, "DROP DATABASE IF EXISTS \`" . $db_name . "`");
        // execute_sql($pdo_super, "DROP USER IF EXISTS ?@?", [$db_user_new_instance, $config_create_instance['db_host']]);
        error_log("PDO Fehler bei Instanzerstellung: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Datenbankfehler bei Instanzerstellung: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Allgemeiner Fehler bei Instanzerstellung: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Allgemeiner Fehler: ' . $e->getMessage()]);
    }

    $pdo_super = null; // Superuser-Verbindung schließen

} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage.']);
}

?> 