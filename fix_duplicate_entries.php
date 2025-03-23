<?php
require_once 'includes/database_config.php';

// Funktion zum Protokollieren
function log_message($message) {
    echo $message . "<br>";
    error_log($message);
}

try {
    // Verbindung zur Datenbank herstellen
    $db = DatabaseConfig::getInstance()->getConnection();
    log_message("Datenbankverbindung hergestellt");
    
    // 1. Holen aller test_attempts-Einträge
    $stmt = $db->query("
        SELECT attempt_id, test_id, student_name, completed_at, xml_file_path 
        FROM test_attempts
        ORDER BY completed_at DESC
    ");
    
    $allEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    log_message("Gesamt " . count($allEntries) . " Testversuche gefunden");
    
    // 2. Normalisiere die Pfade und finde doppelte Einträge
    $normalizedEntries = [];
    $duplicates = [];
    
    foreach ($allEntries as $entry) {
        // Normalisiere den Pfad
        $originalPath = $entry['xml_file_path'];
        $normalizedPath = str_replace('\\', '/', $originalPath);
        $normalizedPath = preg_replace('/^.*?\/results\//', 'results/', $normalizedPath);
        
        // Alternative Normalisierung: Nur Dateiname und übergeordneter Ordner
        $basePathKey = basename(dirname($normalizedPath)) . '/' . basename($normalizedPath);
        
        // Prüfen, ob ein Eintrag mit diesem normalisierten Pfad bereits existiert
        if (isset($normalizedEntries[$basePathKey])) {
            // Wir haben einen doppelten Eintrag gefunden
            $duplicates[] = [
                'original' => $normalizedEntries[$basePathKey],
                'duplicate' => $entry,
                'normalizedPath' => $normalizedPath,
                'basePathKey' => $basePathKey
            ];
        } else {
            // Speichere den Eintrag mit dem normalisierten Pfad
            $normalizedEntries[$basePathKey] = $entry;
        }
    }
    
    log_message("Gefunden: " . count($duplicates) . " doppelte Einträge");
    
    // 3. Zeige doppelte Einträge an
    if (!empty($duplicates)) {
        echo "<h2>Gefundene doppelte Einträge:</h2>";
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Typ</th>";
        echo "<th>Attempt ID</th>";
        echo "<th>Test ID</th>";
        echo "<th>Schüler</th>";
        echo "<th>Datum</th>";
        echo "<th>XML-Pfad</th>";
        echo "<th>Basis-Pfad</th>";
        echo "</tr>";
        
        foreach ($duplicates as $duplicate) {
            // Original-Eintrag
            echo "<tr style='background-color: #e0ffe0;'>";
            echo "<td>Original</td>";
            echo "<td>" . $duplicate['original']['attempt_id'] . "</td>";
            echo "<td>" . $duplicate['original']['test_id'] . "</td>";
            echo "<td>" . $duplicate['original']['student_name'] . "</td>";
            echo "<td>" . $duplicate['original']['completed_at'] . "</td>";
            echo "<td>" . $duplicate['original']['xml_file_path'] . "</td>";
            echo "<td>" . $duplicate['basePathKey'] . "</td>";
            echo "</tr>";
            
            // Duplikat-Eintrag
            echo "<tr style='background-color: #ffe0e0;'>";
            echo "<td>Duplikat</td>";
            echo "<td>" . $duplicate['duplicate']['attempt_id'] . "</td>";
            echo "<td>" . $duplicate['duplicate']['test_id'] . "</td>";
            echo "<td>" . $duplicate['duplicate']['student_name'] . "</td>";
            echo "<td>" . $duplicate['duplicate']['completed_at'] . "</td>";
            echo "<td>" . $duplicate['duplicate']['xml_file_path'] . "</td>";
            echo "<td>" . $duplicate['basePathKey'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // 4. Lösche doppelte Einträge (deaktiviert, bis der Benutzer bestätigt)
        if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
            $db->beginTransaction();
            
            try {
                $deletedCount = 0;
                $deleteStmt = $db->prepare("DELETE FROM test_attempts WHERE attempt_id = ?");
                
                foreach ($duplicates as $duplicate) {
                    // Behalte das Original, lösche das Duplikat
                    $deleteStmt->execute([$duplicate['duplicate']['attempt_id']]);
                    $deletedCount++;
                    
                    log_message("Gelöscht: Eintrag ID " . $duplicate['duplicate']['attempt_id'] . 
                                " (Duplikat von " . $duplicate['original']['attempt_id'] . ")");
                }
                
                $db->commit();
                log_message("Erfolgreich " . $deletedCount . " doppelte Einträge gelöscht.");
                
                // XML-Pfade in verbleibenden Einträgen korrigieren
                $updateCount = 0;
                foreach ($normalizedEntries as $basePath => $entry) {
                    if (strpos($entry['xml_file_path'], 'C:') !== false || 
                        strpos($entry['xml_file_path'], 'c:') !== false) {
                        
                        // Korrigiere absoluten Pfad in relativen Pfad
                        $correctPath = 'results/' . preg_replace('/^.*?\/results\//', '', str_replace('\\', '/', $entry['xml_file_path']));
                        
                        $updateStmt = $db->prepare("
                            UPDATE test_attempts 
                            SET xml_file_path = ? 
                            WHERE attempt_id = ?
                        ");
                        
                        $updateStmt->execute([$correctPath, $entry['attempt_id']]);
                        $updateCount++;
                        
                        log_message("Korrigiert: Pfad für Eintrag ID " . $entry['attempt_id'] . 
                                    " von '" . $entry['xml_file_path'] . "' zu '" . $correctPath . "'");
                    }
                }
                
                log_message("Pfade für " . $updateCount . " Einträge korrigiert.");
                
            } catch (Exception $e) {
                $db->rollBack();
                log_message("Fehler beim Löschen der Duplikate: " . $e->getMessage());
            }
        } else {
            // Link anzeigen, um doppelte Einträge zu löschen
            echo "<p><a href='?fix=true' class='btn btn-danger'>Doppelte Einträge löschen</a></p>";
            echo "<p style='color: red;'>Achtung: Dies wird " . count($duplicates) . " doppelte Einträge unwiderruflich aus der Datenbank entfernen.</p>";
        }
    } else {
        echo "<div style='color: green; font-weight: bold;'>Keine doppelten Einträge gefunden!</div>";
        
        // Trotzdem nach absoluten Pfaden suchen und korrigieren
        $absolutePathsQuery = $db->query("
            SELECT attempt_id, xml_file_path
            FROM test_attempts
            WHERE xml_file_path LIKE 'C:\\%' OR xml_file_path LIKE 'c:\\%' 
            OR xml_file_path LIKE 'C:/%' OR xml_file_path LIKE 'c:/%'
        ");
        
        $absolutePaths = $absolutePathsQuery->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($absolutePaths)) {
            log_message("Gefunden: " . count($absolutePaths) . " Einträge mit absoluten Pfaden");
            
            if (isset($_GET['fix_paths']) && $_GET['fix_paths'] === 'true') {
                $db->beginTransaction();
                
                try {
                    $updateCount = 0;
                    $updateStmt = $db->prepare("
                        UPDATE test_attempts 
                        SET xml_file_path = ? 
                        WHERE attempt_id = ?
                    ");
                    
                    foreach ($absolutePaths as $entry) {
                        // Korrigiere absoluten Pfad in relativen Pfad
                        $correctPath = 'results/' . preg_replace('/^.*?\/results\//', '', str_replace('\\', '/', $entry['xml_file_path']));
                        
                        $updateStmt->execute([$correctPath, $entry['attempt_id']]);
                        $updateCount++;
                        
                        log_message("Korrigiert: Pfad für Eintrag ID " . $entry['attempt_id'] . 
                                    " von '" . $entry['xml_file_path'] . "' zu '" . $correctPath . "'");
                    }
                    
                    $db->commit();
                    log_message("Pfade für " . $updateCount . " Einträge korrigiert.");
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    log_message("Fehler beim Korrigieren der Pfade: " . $e->getMessage());
                }
            } else {
                // Link anzeigen, um Pfade zu korrigieren
                echo "<h2>Gefundene absolute Pfade:</h2>";
                echo "<table border='1'>";
                echo "<tr>";
                echo "<th>Attempt ID</th>";
                echo "<th>XML-Pfad</th>";
                echo "<th>Korrigierter Pfad</th>";
                echo "</tr>";
                
                foreach ($absolutePaths as $entry) {
                    $correctPath = 'results/' . preg_replace('/^.*?\/results\//', '', str_replace('\\', '/', $entry['xml_file_path']));
                    
                    echo "<tr>";
                    echo "<td>" . $entry['attempt_id'] . "</td>";
                    echo "<td>" . $entry['xml_file_path'] . "</td>";
                    echo "<td>" . $correctPath . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                echo "<p><a href='?fix_paths=true' class='btn btn-warning'>Absolute Pfade korrigieren</a></p>";
            }
        } else {
            log_message("Keine Einträge mit absoluten Pfaden gefunden.");
        }
    }
    
} catch (Exception $e) {
    log_message("Fehler: " . $e->getMessage());
}
?> 