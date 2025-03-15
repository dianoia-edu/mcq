<?php
// Aktiviere Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Funktion zum Formatieren der Ausgabe
function printResult($test, $result, $details = '') {
    $status = $result ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m";
    echo str_pad($test, 40) . " $status";
    if (!$result && $details) {
        echo " \033[33m($details)\033[0m";
    }
    echo "\n";
}

echo "\nÜberprüfe Systemanforderungen...\n";
echo "================================\n\n";

// PHP Version
$php_version = phpversion();
$php_version_ok = version_compare($php_version, '7.4.0', '>=');
printResult(
    "PHP Version (>= 7.4)", 
    $php_version_ok,
    $php_version_ok ? '' : "PHP $php_version gefunden"
);

// PHP Erweiterungen
$required_extensions = [
    'curl' => 'URL-Verarbeitung',
    'mbstring' => 'Multibyte-Strings',
    'xml' => 'XML-Verarbeitung',
    'zip' => 'ZIP-Verarbeitung',
    'fileinfo' => 'MIME-Typ-Erkennung'
];

echo "\nÜberprüfe PHP-Erweiterungen...\n";
echo "============================\n\n";

foreach ($required_extensions as $ext => $purpose) {
    $loaded = extension_loaded($ext);
    printResult(
        "$ext ($purpose)", 
        $loaded,
        $loaded ? '' : 'Nicht geladen'
    );
}

// Externe Programme
echo "\nÜberprüfe externe Programme...\n";
echo "===========================\n\n";

// yt-dlp
exec('yt-dlp --version 2>&1', $output_ytdl, $return_var_ytdl);
printResult(
    "yt-dlp (YouTube Untertitel)", 
    $return_var_ytdl === 0,
    $return_var_ytdl === 0 ? '' : 'Nicht gefunden'
);

// Tesseract
exec('tesseract --version 2>&1', $output_tess, $return_var_tess);
printResult(
    "Tesseract OCR (Texterkennung)", 
    $return_var_tess === 0,
    $return_var_tess === 0 ? '' : 'Nicht gefunden'
);

// Ghostscript
exec('gswin64c --version 2>&1', $output_gs, $return_var_gs);
printResult(
    "Ghostscript (PDF-Verarbeitung)", 
    $return_var_gs === 0,
    $return_var_gs === 0 ? '' : 'Nicht gefunden'
);

// Überprüfe Tesseract Sprachdaten
if ($return_var_tess === 0) {
    exec('tesseract --list-langs 2>&1', $output_langs, $return_var_langs);
    $has_german = false;
    if ($return_var_langs === 0) {
        $has_german = in_array('deu', $output_langs);
    }
    printResult(
        "Tesseract Deutsche Sprachdaten", 
        $has_german,
        $has_german ? '' : 'Nicht installiert'
    );
}

// Überprüfe Schreibrechte
echo "\nÜberprüfe Verzeichnisrechte...\n";
echo "============================\n\n";

$test_dirs = [
    'tests' => __DIR__ . '/tests',
    'logs' => __DIR__ . '/logs',
    'temp' => sys_get_temp_dir()
];

foreach ($test_dirs as $name => $dir) {
    $exists = file_exists($dir);
    if (!$exists) {
        @mkdir($dir, 0777, true);
        $exists = file_exists($dir);
    }
    
    $writable = is_writable($dir);
    printResult(
        "$name Verzeichnis ($dir)", 
        $exists && $writable,
        !$exists ? 'Nicht gefunden' : (!$writable ? 'Keine Schreibrechte' : '')
    );
}

echo "\nÜberprüfung abgeschlossen.\n";

// Zeige Hinweise für fehlende Komponenten
$missing_components = [];

if (!$php_version_ok) {
    $missing_components[] = "- Aktualisieren Sie PHP auf Version 7.4 oder höher";
}

foreach ($required_extensions as $ext => $purpose) {
    if (!extension_loaded($ext)) {
        $missing_components[] = "- Aktivieren Sie die PHP-Erweiterung '$ext' in der php.ini";
    }
}

if ($return_var_ytdl !== 0) {
    $missing_components[] = "- Installieren Sie yt-dlp mit 'winget install yt-dlp' oder 'choco install yt-dlp'";
}

if ($return_var_tess !== 0) {
    $missing_components[] = "- Installieren Sie Tesseract OCR von https://github.com/UB-Mannheim/tesseract/wiki";
}

if ($return_var_gs !== 0) {
    $missing_components[] = "- Installieren Sie Ghostscript von https://www.ghostscript.com/releases/gsdnld.html";
}

if (!empty($missing_components)) {
    echo "\nFolgende Komponenten müssen noch installiert/konfiguriert werden:\n";
    echo implode("\n", $missing_components) . "\n";
    echo "\nBefolgen Sie die Anweisungen in der README.md für detaillierte Installationsschritte.\n";
}
?> 