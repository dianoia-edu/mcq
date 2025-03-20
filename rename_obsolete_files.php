<?php
/**
 * Skript zum Umbenennen der als obsolet identifizierten Dateien
 * Fügt das Präfix "_old_" zu jeder Datei hinzu
 */

// Liste der als potenziell obsolet identifizierten Dateien
$obsoleteFiles = [
    '_old_test-ended.html',
    'analyze_ocr.php',
    'check_dependencies.php',
    'check_test_data.php',
    'evaluate_api.php',
    'includes/phpqrcode/tools/merge.php',
    'includes/teacher_dashboard/config_view.php',
    'install_webmin.php',
    'save_generated_test.php',
    'server_setup.php',
    'teacher/debug_upload.php',
    'teacher/fix_upload_limit.php',
    'teacher/temp_dir_fix.php',
    'teacher/upload_debug.php'
];

$renamed = 0;
$errors = [];

// Umbenennen jeder Datei
foreach ($obsoleteFiles as $file) {
    $originalPath = __DIR__ . '/' . $file;
    
    // Wenn die Datei bereits mit _old_ beginnt, überspringe sie
    $baseName = basename($file);
    if (strpos($baseName, '_old_') === 0) {
        echo "Überspringe bereits markierte Datei: $file\n";
        continue;
    }
    
    // Erstelle den neuen Dateinamen mit dem Präfix "_old_"
    $directory = dirname($file);
    $newName = $directory . '/' . (($directory === '.') ? '' : '') . '_old_' . basename($file);
    $newPath = __DIR__ . '/' . $newName;
    
    // Prüfe, ob die Originaldatei existiert
    if (!file_exists($originalPath)) {
        $errors[] = "Datei nicht gefunden: $originalPath";
        continue;
    }
    
    // Prüfe, ob die Zieldatei bereits existiert
    if (file_exists($newPath)) {
        $errors[] = "Zieldatei existiert bereits: $newPath";
        continue;
    }
    
    // Versuche, die Datei umzubenennen
    if (rename($originalPath, $newPath)) {
        echo "Umbenannt: $file → $newName\n";
        $renamed++;
    } else {
        $errors[] = "Fehler beim Umbenennen von $file zu $newName";
    }
}

// Ausgabe der Ergebnisse
echo "\nErgebnis:\n";
echo "- $renamed von " . count($obsoleteFiles) . " Dateien wurden umbenannt.\n";

if (!empty($errors)) {
    echo "\nFehler:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

echo "\nDu kannst jetzt das System testen. Um die Änderungen rückgängig zu machen, führe das Skript 'restore_renamed_files.php' aus.\n";

// Erstelle ein Wiederherstellungsskript
$restoreScript = __DIR__ . '/restore_renamed_files.php';
$restoreContent = '<?php
/**
 * Skript zur Wiederherstellung der umbenannten Dateien
 * Entfernt das Präfix "_old_" von jeder Datei
 */

$renamedFiles = [
';

foreach ($obsoleteFiles as $file) {
    $directory = dirname($file);
    $newName = $directory . '/' . (($directory === '.') ? '' : '') . '_old_' . basename($file);
    
    // Nur Dateien hinzufügen, die erfolgreich umbenannt wurden
    if (file_exists(__DIR__ . '/' . $newName)) {
        $restoreContent .= "    '$newName',\n";
    }
}

$restoreContent .= '];

$restored = 0;
$errors = [];

// Wiederherstellung jeder Datei
foreach ($renamedFiles as $file) {
    $renamedPath = __DIR__ . \'/\' . $file;
    
    // Überprüfe, ob der Dateiname mit _old_ beginnt
    $baseName = basename($file);
    if (strpos($baseName, \'_old_\') !== 0) {
        continue;
    }
    
    // Erstelle den ursprünglichen Dateinamen ohne das Präfix "_old_"
    $directory = dirname($file);
    $originalName = $directory . \'/\' . (($directory === \'.\') ? \'\' : \'\') . substr(basename($file), 5);
    $originalPath = __DIR__ . \'/\' . $originalName;
    
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
        echo "Wiederhergestellt: $file → $originalName\\n";
        $restored++;
    } else {
        $errors[] = "Fehler beim Wiederherstellen von $file zu $originalName";
    }
}

// Ausgabe der Ergebnisse
echo "\\nErgebnis:\\n";
echo "- $restored von " . count($renamedFiles) . " Dateien wurden wiederhergestellt.\\n";

if (!empty($errors)) {
    echo "\\nFehler:\\n";
    foreach ($errors as $error) {
        echo "- $error\\n";
    }
}
';

// Speichere das Wiederherstellungsskript
file_put_contents($restoreScript, $restoreContent);
echo "Wiederherstellungsskript erstellt: restore_renamed_files.php\n"; 