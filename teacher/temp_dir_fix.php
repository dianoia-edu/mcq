<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Temporäres Verzeichnis Fix ===\n\n";

// Aktuelles temporäres Verzeichnis
$currentTempDir = sys_get_temp_dir();
echo "Aktuelles temporäres Verzeichnis: $currentTempDir\n";
echo "Schreibbar: " . (is_writable($currentTempDir) ? "JA" : "NEIN") . "\n\n";

// Erstelle ein eigenes temporäres Verzeichnis
$customTempDir = __DIR__ . '/../temp';
if (!file_exists($customTempDir)) {
    if (mkdir($customTempDir, 0777, true)) {
        echo "Eigenes temporäres Verzeichnis erstellt: $customTempDir\n";
    } else {
        echo "FEHLER: Konnte eigenes temporäres Verzeichnis nicht erstellen\n";
        exit(1);
    }
} else {
    echo "Eigenes temporäres Verzeichnis existiert bereits: $customTempDir\n";
}

// Setze Berechtigungen
if (chmod($customTempDir, 0777)) {
    echo "Berechtigungen für temporäres Verzeichnis gesetzt (0777)\n";
} else {
    echo "WARNUNG: Konnte Berechtigungen für temporäres Verzeichnis nicht setzen\n";
}

// Teste Schreibzugriff
$testFile = $customTempDir . '/test_' . uniqid() . '.txt';
$testContent = "Test " . date('Y-m-d H:i:s');
$writeSuccess = @file_put_contents($testFile, $testContent);

if ($writeSuccess !== false) {
    echo "Schreibtest erfolgreich\n";
    $readContent = @file_get_contents($testFile);
    echo "Lesetest: " . ($readContent === $testContent ? "ERFOLGREICH" : "FEHLGESCHLAGEN") . "\n";
    @unlink($testFile);
} else {
    echo "FEHLER: Schreibtest fehlgeschlagen\n";
    exit(1);
}

// Erstelle eine .htaccess-Datei zum Schutz des Verzeichnisses
$htaccessContent = "# Verbiete Verzeichnisauflistung
Options -Indexes

# Verbiete Zugriff auf alle Dateien
<FilesMatch \".*\">
    Order Allow,Deny
    Deny from all
</FilesMatch>";

if (file_put_contents($customTempDir . '/.htaccess', $htaccessContent)) {
    echo ".htaccess-Datei zum Schutz des Verzeichnisses erstellt\n";
} else {
    echo "WARNUNG: Konnte .htaccess-Datei nicht erstellen\n";
}

echo "\nUm das eigene temporäre Verzeichnis zu verwenden, fügen Sie folgenden Code am Anfang von generate_test.php ein:\n\n";
echo "<?php\n";
echo "// Eigenes temporäres Verzeichnis verwenden\n";
echo "ini_set('upload_tmp_dir', __DIR__ . '/../temp');\n";
echo "?>\n\n";

echo "=== Fix abgeschlossen ===\n";
echo "Bitte testen Sie den Test-Generator erneut.\n"; 