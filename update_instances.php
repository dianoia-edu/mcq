<?php
/**
 * Update-Script für bestehende Lehrerinstanzen
 * Kopiert korrigierte Dateien in alle Instanzen
 */

// Sicherheitscheck
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'update_instances_2024') {
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Zugriff verweigert. Admin-Schlüssel erforderlich.']);
        exit;
    }
    die('Zugriff verweigert. Admin-Schlüssel erforderlich.');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// AJAX-Modus prüfen
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === 'true';

// Für AJAX-Anfragen: Springe direkt zur Verarbeitung, kein HTML-Output
if ($isAjax) {
    // Führe AJAX-Update durch und beende
    performAjaxUpdate();
    exit;
}

function performAjaxUpdate() {
    $instancesBasePath = dirname(__DIR__) . '/lehrer_instanzen/';
    $sourceBasePath = __DIR__;
    
    // Dateien die aktualisiert werden sollen
    $filesToUpdate = [
        'teacher/teacher_dashboard.php' => 'Teacher Dashboard (Instanzverwaltung mit Update/Delete-All + Tab ausgeblendet in Instanzen)',
        'teacher/delete_instance.php' => 'Instanz-Lösch-Script',
        'teacher/delete_all_instances.php' => 'Alle Instanzen löschen Script',
        'teacher/generate_test.php' => 'Test Generator (dynamische Modell-Auswahl)',
        'teacher/create_instance.php' => 'Instanz-Erstellung (VERHINDERT Testergebnisse-Kopieren komplett)',
        'js/main.js' => 'JavaScript Main (korrigierte AJAX-Pfade)',
        'includes/teacher_dashboard/test_generator_view.php' => 'Test Generator View (Modell-Auswahl + getTeacherUrl)',
        'includes/teacher_dashboard/test_editor_view.php' => 'Test Editor View (usort-Fix)',
        'includes/teacher_dashboard/configuration_view.php' => 'Configuration View (getTeacherUrl + Update-Button entfernt)',
        'includes/teacher_dashboard/test_results_view.php' => 'Test Results View (DatabaseConfig-Fix + Sortierung)',
        'includes/teacher_dashboard/config_view.php' => 'Config View',
        'includes/teacher_dashboard/get_openai_models.php' => 'OpenAI Models API',
        'includes/teacher_dashboard/get_instances.php' => 'Instanzen-Übersicht API (korrigierte Pfade + Admin-Codes)',
        'includes/openai_models.php' => 'OpenAI Models Management',
        'name_form.php' => 'Namenseingabe-Seite (SEB-Integration mit orangem Button)',
        'test.php' => 'Test-Durchführungsseite (SEB-Integration)',
        'result.php' => 'Testergebnis-Seite (SEB-Beenden-Button)',
        'index.php' => 'Startseite (SEB-Integration + Button-Fixes)',
        'setup_test_session.php' => 'Test-Session-Setup (SEB-Redirect-Support)',
        'process.php' => 'Test-Verarbeitung (SEB-Session-Tracking)',
        'includes/seb_detection.php' => 'SEB-Erkennungslogik (Warnung + Session-Management)',
        // SEB-Konfigurationsdateien
        'seb_config.php' => 'Standard SEB-Konfiguration',
        'seb_config_override_server.php' => 'SEB-Standalone-Konfiguration',
        // WARNUNG: database_config.php NICHT updaten - jede Instanz hat instanz-spezifische DB-Daten!
        'config/clean_schema.sql' => 'Sauberes DB-Schema ohne Testergebnisse'
    ];
    
    // Finde alle Instanzen
    $instances = [];
    if (is_dir($instancesBasePath)) {
        $dirs = scandir($instancesBasePath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $instancePath = $instancesBasePath . $dir;
            if (is_dir($instancePath) && is_dir($instancePath . '/mcq-test-system')) {
                $instances[] = $dir;
            }
        }
    }
    
    // Keine Instanzen gefunden
    if (empty($instances)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'statistics' => [
                'instances_processed' => 0,
                'files_updated' => 0,
                'errors' => 0
            ],
            'instances' => [],
            'message' => 'Keine Instanzen gefunden zum Aktualisieren. Das ist normal wenn noch keine Lehrerinstanzen erstellt wurden.',
            'info' => 'Erstellen Sie zuerst Lehrerinstanzen über die Instanzverwaltung.'
        ]);
        return;
    }
    
    // Update durchführen
    $totalUpdated = 0;
    $totalErrors = 0;
    $updateLog = [];
    
    foreach ($instances as $instance) {
        $instanceBasePath = $instancesBasePath . $instance . '/mcq-test-system/';
        $instanceErrors = 0;
        $instanceUpdated = 0;
        $instanceLog = [];
        
        foreach ($filesToUpdate as $file => $description) {
            $sourceFile = $sourceBasePath . '/' . $file;
            
            $targetFile = $instanceBasePath . $file;
            
            $targetDir = dirname($targetFile);
            
            if (!file_exists($sourceFile)) {
                $instanceLog[] = ['file' => $file, 'status' => 'error', 'message' => 'Quelldatei fehlt'];
                $instanceErrors++;
                continue;
            }
            
            // Erstelle Zielverzeichnis falls nötig
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    $instanceLog[] = ['file' => $file, 'status' => 'error', 'message' => 'Konnte Verzeichnis nicht erstellen'];
                    $instanceErrors++;
                    continue;
                }
            }
            
            // Backup der alten Datei erstellen
            if (file_exists($targetFile)) {
                $backupFile = $targetFile . '.backup.' . date('Y-m-d_H-i-s');
                copy($targetFile, $backupFile);
            }
            
            // Datei kopieren
            if (copy($sourceFile, $targetFile)) {
                $instanceLog[] = ['file' => $file, 'status' => 'success', 'message' => 'Aktualisiert'];
                $instanceUpdated++;
            } else {
                $instanceLog[] = ['file' => $file, 'status' => 'error', 'message' => 'Kopieren fehlgeschlagen'];
                $instanceErrors++;
            }
        }
        
        $totalUpdated += $instanceUpdated;
        $totalErrors += $instanceErrors;
        
        $updateLog[] = [
            'instance' => $instance,
            'files_updated' => $instanceUpdated,
            'errors' => $instanceErrors,
            'details' => $instanceLog
        ];
    }
    
    // JSON-Antwort senden
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $totalErrors === 0,
        'statistics' => [
            'instances_processed' => count($instances),
            'files_updated' => $totalUpdated,
            'errors' => $totalErrors
        ],
        'instances' => array_column($updateLog, 'instance'),
        'detailed_log' => $updateLog,
        'error' => $totalErrors > 0 ? 'Update mit ' . $totalErrors . ' Fehlern abgeschlossen' : null
    ]);
}

// Gemeinsame Konfiguration für beide Modi
$instancesBasePath = dirname(__DIR__) . '/lehrer_instanzen/';
$sourceBasePath = __DIR__;

// Dateien die aktualisiert werden sollen
$filesToUpdate = [
    'teacher/teacher_dashboard.php' => 'Teacher Dashboard (korrigierte Tab-Funktion)',
    'teacher/generate_test.php' => 'Test Generator (dynamische Modell-Auswahl)',
    'js/main.js' => 'JavaScript Main (korrigierte AJAX-Pfade)',
    'includes/teacher_dashboard/test_generator_view.php' => 'Test Generator View (Modell-Auswahl)',
    'includes/teacher_dashboard/test_editor_view.php' => 'Test Editor View',
    'includes/teacher_dashboard/configuration_view.php' => 'Configuration View',
    'includes/teacher_dashboard/test_results_view.php' => 'Test Results View',
    'includes/teacher_dashboard/config_view.php' => 'Config View',
    'includes/teacher_dashboard/get_openai_models.php' => 'OpenAI Models API',
    'includes/teacher_dashboard/get_instances.php' => 'Instanzen-Übersicht API',
    'includes/openai_models.php' => 'OpenAI Models Management'
    // WARNUNG: database_config.php NICHT updaten - jede Instanz hat instanz-spezifische DB-Daten!
];

// Finde alle Instanzen
$instances = [];
if (is_dir($instancesBasePath)) {
    $dirs = scandir($instancesBasePath);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $instancePath = $instancesBasePath . $dir;
        if (is_dir($instancePath) && is_dir($instancePath . '/mcq-test-system')) {
            $instances[] = $dir;
        }
    }
}

if (!$isAjax) {
    // HTML-Ausgabe für normale Ansicht
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instanzen-Update</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .file-list { background: #f8f9fa; padding: 10px; border-radius: 3px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔄 Instanzen-Update für MCQ Test System</h1>
    
    <?php
    
    echo '<div class="info">';
    echo '<h2>📋 Update-Informationen</h2>';
    echo '<p><strong>Quell-Verzeichnis:</strong> ' . $sourceBasePath . '</p>';
    echo '<p><strong>Instanzen-Verzeichnis:</strong> ' . $instancesBasePath . '</p>';
    echo '<p><strong>Zu aktualisierende Dateien:</strong></p>';
    echo '<div class="file-list">';
    foreach ($filesToUpdate as $file => $description) {
        $sourceFile = $sourceBasePath . '/' . $file;
        $exists = file_exists($sourceFile);
        echo '<p><strong>' . $file . ':</strong> ' . $description . ' ';
        echo '<span style="color: ' . ($exists ? 'green' : 'red') . '">';
        echo $exists ? '✅' : '❌ FEHLT';
        echo '</span></p>';
    }
    echo '</div>';
    echo '</div>';
    
    // Prüfe ob Instanzen-Verzeichnis existiert
    if (!is_dir($instancesBasePath)) {
        echo '<div class="error">❌ Instanzen-Verzeichnis nicht gefunden: ' . $instancesBasePath . '</div>';
        exit;
    }
    
    echo '<div class="info">';
    echo '<h2>🏢 Gefundene Instanzen</h2>';
    if (empty($instances)) {
        echo '<p>Keine Instanzen gefunden.</p>';
    } else {
        echo '<ul>';
        foreach ($instances as $instance) {
            echo '<li>' . htmlspecialchars($instance) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
    
    // Update durchführen
    if (!empty($instances)) {
        if (!$isAjax) {
            echo '<div class="status info">';
            echo '<h2>🚀 Starte Update...</h2>';
            echo '</div>';
        }
        
        $totalUpdated = 0;
        $totalErrors = 0;
        $updateLog = [];
        
        foreach ($instances as $instance) {
            if (!$isAjax) {
                echo '<h3>📁 Aktualisiere Instanz: ' . htmlspecialchars($instance) . '</h3>';
            }
            
            $instanceBasePath = $instancesBasePath . $instance . '/mcq-test-system/';
            $instanceErrors = 0;
            $instanceUpdated = 0;
            $instanceLog = [];
            
            foreach ($filesToUpdate as $file => $description) {
                $sourceFile = $sourceBasePath . '/' . $file;
                $targetFile = $instanceBasePath . $file;
                $targetDir = dirname($targetFile);
                
                if (!$isAjax) {
                    echo '<p><strong>' . $file . ':</strong> ';
                }
                
                if (!file_exists($sourceFile)) {
                    $error = 'Quelldatei fehlt';
                    if (!$isAjax) {
                        echo '<span style="color: red;">❌ ' . $error . '</span></p>';
                    }
                    $instanceLog[] = ['file' => $file, 'status' => 'error', 'message' => $error];
                    $instanceErrors++;
                    continue;
                }
                
                // Erstelle Zielverzeichnis falls nötig
                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0755, true)) {
                        $error = 'Konnte Verzeichnis nicht erstellen';
                        if (!$isAjax) {
                            echo '<span style="color: red;">❌ ' . $error . '</span></p>';
                        }
                        $instanceLog[] = ['file' => $file, 'status' => 'error', 'message' => $error];
                        $instanceErrors++;
                        continue;
                    }
                }
                
                // Backup der alten Datei erstellen
                if (file_exists($targetFile)) {
                    $backupFile = $targetFile . '.backup.' . date('Y-m-d_H-i-s');
                    copy($targetFile, $backupFile);
                }
                
                // Datei kopieren
                if (copy($sourceFile, $targetFile)) {
                    if (!$isAjax) {
                        echo '<span style="color: green;">✅ Aktualisiert</span>';
                    }
                    $instanceLog[] = ['file' => $file, 'status' => 'success', 'message' => 'Aktualisiert'];
                    $instanceUpdated++;
                } else {
                    $error = 'Kopieren fehlgeschlagen';
                    if (!$isAjax) {
                        echo '<span style="color: red;">❌ ' . $error . '</span>';
                    }
                    $instanceLog[] = ['file' => $file, 'status' => 'error', 'message' => $error];
                    $instanceErrors++;
                }
                if (!$isAjax) {
                    echo '</p>';
                }
            }
            
            $totalUpdated += $instanceUpdated;
            $totalErrors += $instanceErrors;
            
            $updateLog[] = [
                'instance' => $instance,
                'files_updated' => $instanceUpdated,
                'errors' => $instanceErrors,
                'details' => $instanceLog
            ];
            
            if (!$isAjax) {
                echo '<div class="status ' . ($instanceErrors === 0 ? 'success' : 'warning') . '">';
                echo '<strong>Instanz ' . htmlspecialchars($instance) . ':</strong> ';
                echo $instanceUpdated . ' Dateien aktualisiert, ' . $instanceErrors . ' Fehler';
                echo '</div>';
            }
        }
        
        // AJAX-Antwort
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $totalErrors === 0,
                'statistics' => [
                    'instances_processed' => count($instances),
                    'files_updated' => $totalUpdated,
                    'errors' => $totalErrors
                ],
                'instances' => array_column($updateLog, 'instance'),
                'detailed_log' => $updateLog,
                'error' => $totalErrors > 0 ? 'Update mit ' . $totalErrors . ' Fehlern abgeschlossen' : null
            ]);
            exit;
        }
        
        // HTML-Ausgabe für normale Ansicht
        echo '<div class="status ' . ($totalErrors === 0 ? 'success' : 'warning') . '">';
        echo '<h2>📊 Update-Zusammenfassung</h2>';
        echo '<p><strong>Instanzen verarbeitet:</strong> ' . count($instances) . '</p>';
        echo '<p><strong>Dateien aktualisiert:</strong> ' . $totalUpdated . '</p>';
        echo '<p><strong>Fehler:</strong> ' . $totalErrors . '</p>';
        
        if ($totalErrors === 0) {
            echo '<p style="color: green; font-weight: bold;">✅ Alle Instanzen erfolgreich aktualisiert!</p>';
        } else {
            echo '<p style="color: orange; font-weight: bold;">⚠️ Update mit Fehlern abgeschlossen.</p>';
        }
        echo '</div>';
        
        // Nächste Schritte
        echo '<div class="info">';
        echo '<h2>📋 Nächste Schritte</h2>';
        echo '<ol>';
        echo '<li>Testen Sie jede Instanz im Browser</li>';
        echo '<li>Prüfen Sie den Test-Generator in jeder Instanz</li>';
        echo '<li>Falls Probleme auftreten, prüfen Sie die Backup-Dateien (.backup.*)</li>';
        echo '<li>Löschen Sie alte Backup-Dateien nach erfolgreichem Test</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        // Keine Instanzen gefunden - AJAX-Antwort
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, // Ändere zu true für bessere UX
                'statistics' => [
                    'instances_processed' => 0,
                    'files_updated' => 0,
                    'errors' => 0
                ],
                'instances' => [],
                'message' => 'Keine Instanzen gefunden zum Aktualisieren. Das ist normal wenn noch keine Lehrerinstanzen erstellt wurden.',
                'info' => 'Erstellen Sie zuerst Lehrerinstanzen über die Instanzverwaltung.'
            ]);
            exit;
        }
    }
    
    echo '<div class="info">';
    echo '<h2>🔧 Manuelle Schritte</h2>';
    echo '<p>Falls das automatische Update nicht funktioniert, können Sie die Dateien manuell kopieren:</p>';
    echo '<pre>';
    echo 'Quellverzeichnis: ' . $sourceBasePath . "\n";
    echo 'Zielverzeichnis: ' . $instancesBasePath . '[INSTANZ-NAME]/mcq-test-system/';
    echo '</pre>';
    echo '</div>';
    
    ?>
    
    <div class="info">
        <h2>🔗 Test-Links</h2>
        <p>Testen Sie nach dem Update:</p>
        <?php foreach ($instances as $instance): ?>
        <p><a href="/lehrer_instanzen/<?php echo urlencode($instance); ?>/mcq-test-system/teacher/teacher_dashboard.php" target="_blank">
            🧪 Test Instanz: <?php echo htmlspecialchars($instance); ?>
        </a></p>
        <?php endforeach; ?>
    </div>
    
</body>
</html>
<?php } // Ende der HTML-Ausgabe ?>
