# MCQ Test System - Portierungsanleitung

Diese Anleitung beschreibt, wie Sie das MCQ Test System von Ihrer lokalen Entwicklungsumgebung auf einen Webserver portieren können.

## Voraussetzungen

- Ein Webserver mit PHP 7.4+ und MySQL 5.7+
- FTP-Zugang oder SSH-Zugang zum Server
- Administratorrechte für die Datenbank

## 1. Vorbereitung der lokalen Dateien

1. Sichern Sie Ihre lokale Datenbank:
   ```bash
   mysqldump -u root -p mcq_test_system > mcq_backup.sql
   ```

2. Erstellen Sie ein Archiv aller Projektdateien:
   ```bash
   zip -r mcq-test-system.zip . -x "*.git*" "*.zip" "*.log"
   ```

## 2. Installation auf dem Server

### 2.1 Dateien hochladen

1. Laden Sie das Archiv auf den Server hoch (per FTP oder SCP)
2. Entpacken Sie das Archiv im Webserver-Verzeichnis:
   ```bash
   unzip mcq-test-system.zip -d /var/www/html/mcq-test-system
   ```

### 2.2 Verzeichnisberechtigungen anpassen

Stellen Sie sicher, dass der Webserver-Benutzer Schreibrechte für die folgenden Verzeichnisse hat:

```bash
cd /var/www/html/mcq-test-system
mkdir -p logs results uploads
chown -R www-data:www-data logs results uploads
chmod -R 755 logs results uploads
```

### 2.3 Datenbank einrichten

1. Erstellen Sie eine neue Datenbank auf dem Server:
   ```sql
   CREATE DATABASE mcq_test_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Erstellen Sie einen Datenbankbenutzer:
   ```sql
   CREATE USER 'mcquser'@'localhost' IDENTIFIED BY 'IhrSicheresPasswort';
   GRANT ALL PRIVILEGES ON mcq_test_system.* TO 'mcquser'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. Passen Sie die Datenbankverbindungsdaten in `includes/database_config.php` an:
   ```php
   private $config = [
       'host' => 'localhost',
       'dbname' => 'mcq_test_system',
       'username' => 'mcquser',
       'password' => 'IhrSicheresPasswort',
       'charset' => 'utf8mb4'
   ];
   ```

### 2.4 Externe Programme installieren

Installieren Sie die erforderlichen externen Programme auf dem Server:

#### Tesseract OCR (für PDF- und Bildverarbeitung)

```bash
# Auf Debian/Ubuntu
sudo apt update
sudo apt install tesseract-ocr tesseract-ocr-deu

# Auf CentOS/RHEL
sudo yum install tesseract tesseract-langpack-deu
```

#### Ghostscript (für PDF-Verarbeitung)

```bash
# Auf Debian/Ubuntu
sudo apt update
sudo apt install ghostscript

# Auf CentOS/RHEL
sudo yum install ghostscript
```

## 3. Überprüfung und Initialisierung

1. Rufen Sie im Browser die Datei `server_check.php` auf:
   ```
   https://ihre-domain.de/mcq-test-system/server_check.php
   ```

2. Überprüfen Sie, ob alle Voraussetzungen erfüllt sind

3. Führen Sie das Deployment-Skript aus:
   ```
   https://ihre-domain.de/mcq-test-system/deploy.php
   ```

4. Überprüfen Sie, ob die Datenbank korrekt initialisiert wurde

## 4. Testen der Anwendung

1. Rufen Sie die Hauptseite der Anwendung auf:
   ```
   https://ihre-domain.de/mcq-test-system/
   ```

2. Testen Sie die folgenden Funktionen:
   - Anmeldung im Lehrerbereich
   - Hochladen und Generieren von Tests
   - Durchführen eines Tests
   - Anzeigen von Testergebnissen

## 5. Regelmäßige Updates

Wenn Sie Änderungen an Ihrem lokalen System vornehmen und diese auf den Server übertragen möchten:

1. Sichern Sie die Konfigurationsdateien auf dem Server:
   - `includes/database_config.php`
   - Andere angepasste Konfigurationsdateien

2. Laden Sie die aktualisierten Dateien auf den Server hoch

3. Stellen Sie die gesicherten Konfigurationsdateien wieder her

4. Führen Sie das Deployment-Skript aus:
   ```
   https://ihre-domain.de/mcq-test-system/deploy.php
   ```

## 6. Fehlerbehebung

### PDF-Upload funktioniert nicht

1. Überprüfen Sie, ob Tesseract und Ghostscript installiert sind:
   ```bash
   which tesseract
   which gs
   ```

2. Stellen Sie sicher, dass die Programme im Systempfad liegen

3. Überprüfen Sie die Berechtigungen für das temporäre Verzeichnis

### Datenbank-Fehler

1. Überprüfen Sie die Datenbankverbindungsdaten
2. Stellen Sie sicher, dass der Datenbankbenutzer ausreichende Rechte hat
3. Prüfen Sie die Logs unter `logs/deploy.log`

### Berechtigungsprobleme

1. Überprüfen Sie die Berechtigungen für die Verzeichnisse:
   ```bash
   ls -la logs results uploads
   ```

2. Passen Sie die Berechtigungen bei Bedarf an:
   ```bash
   chown -R www-data:www-data logs results uploads
   chmod -R 755 logs results uploads
   ``` 