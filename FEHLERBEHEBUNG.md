# Fehlerbehebung für den Test-Generator

Diese Anleitung hilft Ihnen, Probleme mit dem Test-Generator auf dem Live-Server zu beheben.

## Problem: "Keine Inhaltsquelle gefunden"

Wenn Sie beim Hochladen einer Datei die Fehlermeldung "Keine Inhaltsquelle gefunden. Bitte laden Sie eine Datei hoch oder geben Sie eine URL ein." erhalten, liegt das Problem wahrscheinlich am Upload-Prozess oder am temporären Verzeichnis.

### Lösung 1: Fix-Skript ausführen

1. Rufen Sie im Browser die Datei `teacher/generate_test_fix.php` auf:
   ```
   https://ihre-domain.de/mcq-test-system/teacher/generate_test_fix.php
   ```

2. Das Skript wird:
   - Ein Backup der Originaldatei erstellen
   - Ein eigenes temporäres Verzeichnis konfigurieren
   - Den Upload-Prozess verbessern
   - Erforderliche Verzeichnisse erstellen und Berechtigungen setzen

3. Testen Sie den Test-Generator erneut

### Lösung 2: Manuelle Fehlerbehebung

Wenn das Fix-Skript nicht funktioniert, können Sie folgende Schritte manuell durchführen:

1. **Verzeichnisse erstellen und Berechtigungen setzen**:
   ```bash
   mkdir -p /var/www/html/mcq-test-system/temp
   mkdir -p /var/www/html/mcq-test-system/uploads
   mkdir -p /var/www/html/mcq-test-system/logs
   chmod -R 777 /var/www/html/mcq-test-system/temp
   chmod -R 777 /var/www/html/mcq-test-system/uploads
   chmod -R 777 /var/www/html/mcq-test-system/logs
   ```

2. **Temporäres Verzeichnis in PHP-Konfiguration anpassen**:
   Fügen Sie am Anfang der Datei `teacher/generate_test.php` folgenden Code ein:
   ```php
   <?php
   // Eigenes temporäres Verzeichnis verwenden
   $customTempDir = __DIR__ . '/../temp';
   if (!file_exists($customTempDir)) {
       mkdir($customTempDir, 0777, true);
   }
   if (is_writable($customTempDir)) {
       ini_set('upload_tmp_dir', $customTempDir);
   }
   ```

3. **Upload-Prozess debuggen**:
   Rufen Sie im Browser die Datei `teacher/upload_debug.php` auf:
   ```
   https://ihre-domain.de/mcq-test-system/teacher/upload_debug.php
   ```
   
   Folgen Sie den Anweisungen und prüfen Sie die Ergebnisse.

### Lösung 3: Detaillierte Fehleranalyse

Wenn die obigen Lösungen nicht funktionieren, können Sie eine detaillierte Fehleranalyse durchführen:

1. Rufen Sie im Browser die Datei `teacher/debug_upload.php` auf:
   ```
   https://ihre-domain.de/mcq-test-system/teacher/debug_upload.php
   ```

2. Öffnen Sie das generierte Test-Formular und führen Sie einen Test durch:
   ```
   https://ihre-domain.de/mcq-test-system/teacher/test_form.php
   ```

3. Prüfen Sie die Datei `logs/upload_debug.log` für detaillierte Informationen:
   ```bash
   cat /var/www/html/mcq-test-system/logs/upload_debug.log
   ```

## Weitere mögliche Probleme

### Tesseract oder Ghostscript nicht gefunden

Wenn die Fehlermeldung "Tesseract ist nicht verfügbar" oder "Ghostscript ist nicht verfügbar" erscheint:

1. **Installieren Sie die Programme**:
   ```bash
   # Auf Debian/Ubuntu
   sudo apt update
   sudo apt install tesseract-ocr tesseract-ocr-deu ghostscript
   
   # Auf CentOS/RHEL
   sudo yum install tesseract tesseract-langpack-deu ghostscript
   ```

2. **Prüfen Sie, ob die Programme im Systempfad sind**:
   ```bash
   which tesseract
   which gs
   ```

3. **Erstellen Sie symbolische Links, falls nötig**:
   ```bash
   sudo ln -s /pfad/zu/tesseract /usr/local/bin/tesseract
   sudo ln -s /pfad/zu/ghostscript /usr/local/bin/gs
   ```

### Berechtigungsprobleme

Wenn Berechtigungsprobleme auftreten:

1. **Prüfen Sie den Webserver-Benutzer**:
   ```bash
   ps aux | grep apache
   # oder
   ps aux | grep nginx
   ```

2. **Setzen Sie die Berechtigungen entsprechend**:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/mcq-test-system/temp
   sudo chown -R www-data:www-data /var/www/html/mcq-test-system/uploads
   sudo chown -R www-data:www-data /var/www/html/mcq-test-system/logs
   sudo chown -R www-data:www-data /var/www/html/mcq-test-system/results
   ```

## Wiederherstellung der Originaldatei

Wenn Sie die Originaldatei wiederherstellen möchten:

```php
<?php
copy('/var/www/html/mcq-test-system/teacher/generate_test.php.bak', 
     '/var/www/html/mcq-test-system/teacher/generate_test.php');
echo "Originaldatei wiederhergestellt";
?>
``` 