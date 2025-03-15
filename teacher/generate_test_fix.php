<?php
// Ausgabe als Text formatieren
header('Content-Type: text/plain');

echo "=== MCQ Test System - Test-Generator Fix ===\n\n";

// Prüfe, ob die Datei existiert
$targetFile = __DIR__ . '/generate_test.php';
if (!file_exists($targetFile)) {
    echo "FEHLER: Die Datei generate_test.php wurde nicht gefunden!\n";
    exit(1);
}

// Erstelle ein Backup der Originaldatei
$backupFile = $targetFile . '.bak';
if (!file_exists($backupFile)) {
    if (copy($targetFile, $backupFile)) {
        echo "Backup erstellt: $backupFile\n";
    } else {
        echo "WARNUNG: Konnte kein Backup erstellen\n";
    }
}

// Lese die Datei
$content = file_get_contents($targetFile);

// Füge Code am Anfang ein, um das temporäre Verzeichnis zu konfigurieren
$fixHeader = <<<'EOT'
<?php
// Fix für Upload-Probleme
$customTempDir = __DIR__ . '/../temp';
if (!file_exists($customTempDir)) {
    mkdir($customTempDir, 0777, true);
}
if (is_writable($customTempDir)) {
    ini_set('upload_tmp_dir', $customTempDir);
}

// Aktiviere detailliertes Logging für Uploads
function debug_upload($message) {
    $logFile = __DIR__ . '/../logs/upload.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
}

EOT;

// Ersetze den PHP-Öffnungs-Tag
$content = str_replace('<?php', $fixHeader, $content);

// Füge Code ein, um den Upload-Prozess zu verbessern
$uploadFix = <<<'EOT'
    // Verarbeite Datei-Upload, falls vorhanden
    if (isset($_FILES['source_file']) && $_FILES['source_file']['error'] === UPLOAD_ERR_OK) {
        // Überprüfe Dateigröße
        if ($_FILES['source_file']['size'] === 0) {
            debug_upload("Leere Datei hochgeladen");
            throw new Exception('Leere Datei hochgeladen');
        }

        // Überprüfe den Dateityp
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['source_file']['tmp_name']);
        finfo_close($finfo);

        debug_upload("Datei hochgeladen: " . $_FILES['source_file']['name'] . " (Typ: $mime_type, Größe: " . $_FILES['source_file']['size'] . " Bytes)");

        // Kopiere die Datei in ein sicheres Verzeichnis
        $uploadDir = __DIR__ . '/../uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $safeFilename = uniqid() . '_' . basename($_FILES['source_file']['name']);
        $targetPath = $uploadDir . '/' . $safeFilename;
        
        if (move_uploaded_file($_FILES['source_file']['tmp_name'], $targetPath)) {
            debug_upload("Datei erfolgreich verschoben nach: $targetPath");
            
            // Extrahiere Text aus der Datei
            if ($mime_type === 'application/pdf' || in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'])) {
                try {
                    $combinedContent .= performOCR($targetPath, $mime_type) . "\n\n";
                    debug_upload("OCR erfolgreich durchgeführt");
                } catch (Exception $e) {
                    debug_upload("OCR-Fehler: " . $e->getMessage());
                    throw new Exception('Fehler bei der Textextraktion: ' . $e->getMessage());
                }
            } 
            else if ($mime_type === 'text/plain') {
                $combinedContent .= file_get_contents($targetPath) . "\n\n";
                debug_upload("Text aus Datei gelesen");
            }
            else if (in_array($mime_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
                debug_upload("Word-Dokument erkannt, aber nicht unterstützt");
                throw new Exception('Word-Dokumente werden derzeit nicht unterstützt. Bitte konvertieren Sie das Dokument in PDF oder Text.');
            } else {
                debug_upload("Nicht unterstützter Dateityp: $mime_type");
                throw new Exception("Nicht unterstützter Dateityp: $mime_type");
            }
        } else {
            debug_upload("Fehler beim Verschieben der Datei");
            throw new Exception('Fehler beim Verarbeiten der hochgeladenen Datei');
        }
    }
EOT;

// Finde den Abschnitt, der den Datei-Upload verarbeitet
$uploadPattern = '/if\s*\(isset\s*\(\s*\$_FILES\s*\[\s*[\'"]source_file[\'"]\s*\]\s*\)\s*&&\s*\$_FILES\s*\[\s*[\'"]source_file[\'"]\s*\]\s*\[\s*[\'"]error[\'"]\s*\]\s*===\s*UPLOAD_ERR_OK\s*\)\s*\{.*?}/s';
if (preg_match($uploadPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    $position = $matches[0][1];
    $length = strlen($matches[0][0]);
    $content = substr($content, 0, $position) . $uploadFix . substr($content, $position + $length);
    echo "Upload-Prozess verbessert\n";
} else {
    echo "WARNUNG: Konnte den Upload-Abschnitt nicht finden\n";
}

// Erstelle das Logs-Verzeichnis, falls es nicht existiert
$logsDir = __DIR__ . '/../logs';
if (!file_exists($logsDir)) {
    if (mkdir($logsDir, 0755, true)) {
        echo "Logs-Verzeichnis erstellt: $logsDir\n";
    } else {
        echo "FEHLER: Konnte Logs-Verzeichnis nicht erstellen\n";
    }
}

// Erstelle das Uploads-Verzeichnis, falls es nicht existiert
$uploadsDir = __DIR__ . '/../uploads';
if (!file_exists($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "Uploads-Verzeichnis erstellt: $uploadsDir\n";
    } else {
        echo "FEHLER: Konnte Uploads-Verzeichnis nicht erstellen\n";
    }
}

// Erstelle das Temp-Verzeichnis, falls es nicht existiert
$tempDir = __DIR__ . '/../temp';
if (!file_exists($tempDir)) {
    if (mkdir($tempDir, 0777, true)) {
        echo "Temp-Verzeichnis erstellt: $tempDir\n";
    } else {
        echo "FEHLER: Konnte Temp-Verzeichnis nicht erstellen\n";
    }
}

// Setze Berechtigungen für die Verzeichnisse
$directories = [$logsDir, $uploadsDir, $tempDir];
foreach ($directories as $dir) {
    if (file_exists($dir)) {
        if (chmod($dir, 0777)) {
            echo "Berechtigungen für $dir gesetzt (0777)\n";
        } else {
            echo "WARNUNG: Konnte Berechtigungen für $dir nicht setzen\n";
        }
    }
}

// Speichere die modifizierte Datei
if (file_put_contents($targetFile, $content)) {
    echo "Datei erfolgreich aktualisiert: $targetFile\n";
} else {
    echo "FEHLER: Konnte Datei nicht aktualisieren\n";
}

echo "\n=== Fix abgeschlossen ===\n";
echo "Bitte testen Sie den Test-Generator erneut.\n";
echo "Wenn Probleme auftreten, können Sie die Originaldatei wiederherstellen mit:\n";
echo "copy('$backupFile', '$targetFile');\n"; 