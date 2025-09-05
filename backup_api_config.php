<?php
/**
 * Backup und Restore für API-Konfiguration
 */

echo "<h1>💾 API-Config Backup & Restore</h1>\n";

$apiConfigPath = __DIR__ . '/config/api_config.json';
$backupPath = __DIR__ . '/config/api_config.backup.json';

echo "<h2>📋 Status</h2>\n";

// Prüfe aktuelle Config
if (file_exists($apiConfigPath)) {
    $config = json_decode(file_get_contents($apiConfigPath), true);
    $size = filesize($apiConfigPath);
    echo "📄 Aktuelle Config: ✅ ($size bytes)<br>\n";
    
    if ($config && isset($config['api_key'])) {
        $keyLength = strlen($config['api_key']);
        $keyPreview = substr($config['api_key'], 0, 12);
        echo "🔑 API-Key: $keyPreview... ($keyLength Zeichen)<br>\n";
        
        if ($config['api_key'] === 'YOUR_OPENAI_API_KEY_HERE') {
            echo "⚠️ Noch Standard-Platzhalter<br>\n";
        } else {
            echo "✅ Echter API-Key gesetzt<br>\n";
        }
    } else {
        echo "❌ Kein API-Key gefunden<br>\n";
    }
} else {
    echo "❌ Keine aktuelle Config gefunden<br>\n";
}

// Prüfe Backup
if (file_exists($backupPath)) {
    $backupConfig = json_decode(file_get_contents($backupPath), true);
    $backupSize = filesize($backupPath);
    $backupTime = date('Y-m-d H:i:s', filemtime($backupPath));
    echo "💾 Backup: ✅ ($backupSize bytes, erstellt: $backupTime)<br>\n";
    
    if ($backupConfig && isset($backupConfig['api_key'])) {
        $backupKeyLength = strlen($backupConfig['api_key']);
        $backupKeyPreview = substr($backupConfig['api_key'], 0, 12);
        echo "🔑 Backup API-Key: $backupKeyPreview... ($backupKeyLength Zeichen)<br>\n";
    }
} else {
    echo "💾 Backup: ❌ Nicht vorhanden<br>\n";
}

echo "<h2>🛠️ Aktionen</h2>\n";

// Handle Actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'backup':
            if (file_exists($apiConfigPath)) {
                if (copy($apiConfigPath, $backupPath)) {
                    echo "✅ <strong>Backup erstellt!</strong><br>\n";
                } else {
                    echo "❌ Backup-Fehler<br>\n";
                }
            } else {
                echo "❌ Keine Config zum Backup vorhanden<br>\n";
            }
            break;
            
        case 'restore':
            if (file_exists($backupPath)) {
                if (copy($backupPath, $apiConfigPath)) {
                    echo "✅ <strong>Config wiederhergestellt!</strong><br>\n";
                    // Auch includes/config aktualisieren
                    $includesPath = __DIR__ . '/includes/config/api_config.json';
                    if (is_dir(dirname($includesPath))) {
                        copy($backupPath, $includesPath);
                        echo "✅ includes/config ebenfalls aktualisiert<br>\n";
                    }
                } else {
                    echo "❌ Restore-Fehler<br>\n";
                }
            } else {
                echo "❌ Kein Backup zum Wiederherstellen vorhanden<br>\n";
            }
            break;
            
        case 'delete_backup':
            if (file_exists($backupPath)) {
                if (unlink($backupPath)) {
                    echo "✅ <strong>Backup gelöscht!</strong><br>\n";
                } else {
                    echo "❌ Backup-Lösch-Fehler<br>\n";
                }
            }
            break;
    }
    
    echo "<hr><br>\n";
    echo "🔄 <a href='" . $_SERVER['PHP_SELF'] . "'>Seite neu laden</a><br>\n";
    echo "<br>\n";
}

?>

<form method="post" style="margin: 10px 0;">
    <button type="submit" name="action" value="backup" style="padding: 8px 16px; background: #007cba; color: white; border: none; border-radius: 4px;">
        💾 Backup erstellen
    </button>
    <small style="display: block; margin-top: 5px; color: #666;">
        Erstellt eine Sicherungskopie der aktuellen API-Konfiguration
    </small>
</form>

<?php if (file_exists($backupPath)): ?>
<form method="post" style="margin: 10px 0;">
    <button type="submit" name="action" value="restore" 
            style="padding: 8px 16px; background: #d63384; color: white; border: none; border-radius: 4px;"
            onclick="return confirm('API-Konfiguration vom Backup wiederherstellen?')">
        🔄 Backup wiederherstellen
    </button>
    <small style="display: block; margin-top: 5px; color: #666;">
        Stellt die API-Konfiguration vom Backup wieder her
    </small>
</form>

<form method="post" style="margin: 10px 0;">
    <button type="submit" name="action" value="delete_backup" 
            style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px;"
            onclick="return confirm('Backup löschen?')">
        🗑️ Backup löschen
    </button>
</form>
<?php endif; ?>

<h2>📝 Manuelle Bearbeitung</h2>
<p>Du kannst die API-Konfiguration auch direkt bearbeiten:</p>
<code><?php echo $apiConfigPath; ?></code>

<?php if (file_exists($apiConfigPath)): ?>
<details style="margin: 10px 0;">
    <summary>📄 Aktuelle Konfiguration anzeigen</summary>
    <pre style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">
<?php echo htmlspecialchars(file_get_contents($apiConfigPath)); ?>
    </pre>
</details>
<?php endif; ?>

<h2>💡 Tipps</h2>
<ul>
    <li><strong>Vor Setup:</strong> Backup erstellen</li>
    <li><strong>Nach API-Key setzen:</strong> Backup erstellen</li>
    <li><strong>Bei Problemen:</strong> Backup wiederherstellen</li>
</ul>

<?php
