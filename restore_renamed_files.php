<?php
/**
 * Skript zur Wiederherstellung der umbenannten Dateien
 * Entfernt das Präfix "_old_" von jeder Datei
 */

$renamedFiles = [
    './_old_analyze_ocr.php',
    './_old_check_dependencies.php',
    './_old_check_test_data.php',
    './_old_evaluate_api.php',
    'includes/phpqrcode/tools/_old_merge.php',
    'includes/teacher_dashboard/_old_config_view.php',
    './_old_install_webmin.php',
    './_old_save_generated_test.php',
    './_old_server_setup.php',
    'teacher/_old_debug_upload.php',
    'teacher/_old_fix_upload_limit.php',
    'teacher/_old_temp_dir_fix.php',
    'teacher/_old_upload_debug.php',
];

$restored = 0;
$errors = [];

// Wiederherstellung jeder Datei
foreach ($renamedFiles as $file) {
    $renamedPath = __DIR__ . '/' . $file;
    
    // Überprüfe, ob der Dateiname mit _old_ beginnt
    $baseName = basename($file);
    if (strpos($baseName, '_old_') !== 0) {
        continue;
    }
    
    // Erstelle den ursprünglichen Dateinamen ohne das Präfix "_old_"
    $directory = dirname($file);
    $originalName = $directory . '/' . (($directory === '.') ? '' : '') . substr(basename($file), 5);
    $originalPath = __DIR__ . '/' . $originalName;
    
    // Prüfe, ob die umbenannte Datei existiert
    if (!file_exists($renamedPath)) {
        $errors[] = "Umbenannte Datei nicht gefunden: $renamedPath";
        continue;
    }
    
    // Prüfe, ob die Originaldatei bereits wiederhergestellt wurde
    if (file_exists($originalPath)) {
        $errors[] = "Originaldatei existiert bereits: $originalPath";
        continue;
    }
    
    // Versuche, die Datei zurückzubenennen
    if (rename($renamedPath, $originalPath)) {
        echo "Wiederhergestellt: $file → $originalName\n";
        $restored++;
    } else {
        $errors[] = "Fehler beim Wiederherstellen von $file zu $originalName";
    }
}

// Ausgabe der Ergebnisse
echo "\nErgebnis:\n";
echo "- $restored von " . count($renamedFiles) . " Dateien wurden wiederhergestellt.\n";

if (!empty($errors)) {
    echo "\nFehler:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}
