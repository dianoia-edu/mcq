# MCQ Test System - Server-Installation

Diese Anleitung beschreibt die Installation des MCQ Test Systems auf einem Webserver.

## 1. Systemvoraussetzungen

- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- Apache oder Nginx Webserver
- Erforderliche PHP-Erweiterungen: pdo, pdo_mysql, gd, fileinfo, curl, xml, mbstring

## 2. Externe Programme installieren

### Tesseract OCR (für PDF- und Bildverarbeitung)

#### Auf Debian/Ubuntu:
```bash
sudo apt update
sudo apt install tesseract-ocr tesseract-ocr-deu
```

#### Auf CentOS/RHEL:
```bash
sudo yum install tesseract tesseract-langpack-deu
```

### Ghostscript (für PDF-Verarbeitung)

#### Auf Debian/Ubuntu:
```bash
sudo apt update
sudo apt install ghostscript
```

#### Auf CentOS/RHEL:
```bash
sudo yum install ghostscript
```

## 3. Dateien hochladen

1. Laden Sie alle Projektdateien auf Ihren Webserver hoch
2. Stellen Sie sicher, dass die folgenden Verzeichnisse existieren und vom Webserver beschreibbar sind:
   - `logs/`
   - `results/`
   - `uploads/`

```bash
mkdir -p logs results uploads
chmod 755 logs results uploads
```

## 4. Datenbank einrichten

1. Erstellen Sie eine neue MySQL-Datenbank:
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
// Ändern Sie die Werte in der Konfiguration
private $config = [
    'host' => 'localhost',
    'dbname' => 'mcq_test_system',
    'username' => 'mcquser',
    'password' => 'IhrSicheresPasswort',
    'charset' => 'utf8mb4'
];
```

## 5. Überprüfen der Installation

1. Rufen Sie im Browser die Datei `server_check.php` auf:
```
https://ihre-domain.de/mcq-test-system/server_check.php
```

2. Überprüfen Sie, ob alle Voraussetzungen erfüllt sind:
   - PHP-Version und Erweiterungen
   - Externe Programme (Tesseract, Ghostscript)
   - Verzeichnisberechtigungen
   - Datenbankverbindung

## 6. Datenbank initialisieren

1. Rufen Sie im Browser die Datei `includes/init_database.php` auf:
```
https://ihre-domain.de/mcq-test-system/includes/init_database.php
```

2. Die Datenbanktabellen werden automatisch erstellt

## 7. Fehlerbehebung

### Tesseract oder Ghostscript nicht gefunden

Stellen Sie sicher, dass die Programme installiert sind und im Systempfad liegen:

```bash
which tesseract
which gs
```

Falls die Programme nicht im Systempfad sind, erstellen Sie symbolische Links:

```bash
sudo ln -s /pfad/zu/tesseract /usr/local/bin/tesseract
sudo ln -s /pfad/zu/ghostscript /usr/local/bin/gs
```

### Berechtigungsprobleme

Stellen Sie sicher, dass der Webserver-Benutzer (z.B. www-data) Schreibrechte für die erforderlichen Verzeichnisse hat:

```bash
sudo chown -R www-data:www-data logs results uploads
sudo chmod -R 755 logs results uploads
```

### Temporäres Verzeichnis nicht schreibbar

Wenn das temporäre Verzeichnis nicht schreibbar ist, können Sie ein eigenes temporäres Verzeichnis erstellen:

```bash
mkdir -p temp
chmod 777 temp
```

Und dann in der PHP-Konfiguration anpassen:

```php
// Am Anfang Ihrer PHP-Dateien
ini_set('upload_tmp_dir', __DIR__ . '/temp');
```

## 8. Sicherheitshinweise

1. Schützen Sie sensible Verzeichnisse mit .htaccess-Dateien
2. Verwenden Sie sichere Passwörter für die Datenbank
3. Halten Sie PHP und alle externen Programme aktuell
4. Sichern Sie regelmäßig die Datenbank und Ergebnisdateien 