# MCQ Test System

Ein System zur Erstellung und Verwaltung von Multiple-Choice-Tests.

## Systemanforderungen

- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- Webserver (Apache, Nginx)
- PHP-Erweiterungen: PDO, PDO_MySQL, GD, FileInfo, cURL, XML, mbstring

### Optionale Abhängigkeiten (für erweiterte Funktionen)

- Tesseract OCR (für Texterkennung in Bildern und PDFs)
- Ghostscript (für PDF-Verarbeitung)
- pdftotext (für PDF-Textextraktion)

## Installation auf dem Webserver

### 1. Vorbereitung

1. Laden Sie alle Dateien auf Ihren Webserver hoch
2. Stellen Sie sicher, dass der Webserver-Benutzer Schreibrechte für folgende Verzeichnisse hat:
   - `logs/`
   - `results/`
   - `uploads/`
   - `config/`

### 2. Server-Setup ausführen

1. Rufen Sie im Browser die Datei `server_setup.php` auf:
   ```
   https://ihre-domain.de/mcq-test-system/server_setup.php
   ```

2. Das Skript wird:
   - Erforderliche Verzeichnisse erstellen
   - Sicherheits-Konfigurationen hinzufügen
   - Die Datenbank initialisieren
   - Die Serverumgebung prüfen

### 3. Konfiguration anpassen

1. Öffnen Sie die Datei `config/config.php` und passen Sie die Produktionseinstellungen an:
   ```php
   'production' => [
       'db_host' => 'localhost',     // Datenbankserver
       'db_name' => 'mcq_test_system', // Datenbankname
       'db_user' => 'db_username',   // Datenbankbenutzer
       'db_password' => 'db_password', // Datenbankpasswort
       'base_url' => 'https://ihre-domain.de/mcq-test-system' // Basis-URL
   ]
   ```

2. Wenn Sie die OpenAI-API verwenden möchten, öffnen Sie die Datei `config/api_config.json` und fügen Sie Ihren API-Schlüssel ein:
   ```json
   {
       "api_key": "Ihr-OpenAI-API-Schlüssel"
   }
   ```

### 4. Deployment ausführen

1. Rufen Sie im Browser die Datei `deploy.php` auf:
   ```
   https://ihre-domain.de/mcq-test-system/deploy.php
   ```

2. Das Skript wird:
   - Die Datenbank synchronisieren
   - Tests aus XML-Dateien importieren
   - Statistiken aktualisieren

### 5. Testen

1. Rufen Sie die Hauptseite der Anwendung auf:
   ```
   https://ihre-domain.de/mcq-test-system/
   ```

2. Überprüfen Sie, ob alle Funktionen korrekt arbeiten

## Aktualisierung des Systems

Wenn Sie das System aktualisieren möchten:

1. Sichern Sie Ihre Konfigurationsdateien:
   - `config/config.php`
   - `config/api_config.json`

2. Laden Sie die neuen Dateien auf den Server hoch (außer die gesicherten Konfigurationsdateien)

3. Führen Sie das Deployment-Skript aus:
   ```
   https://ihre-domain.de/mcq-test-system/deploy.php
   ```

## Fehlerbehebung

### PDF-Upload funktioniert nicht

Wenn der PDF-Upload nicht funktioniert, kann das folgende Ursachen haben:

1. **Fehlende externe Programme**: Tesseract OCR und Ghostscript sind nicht installiert
   - Lösung 1: Installieren Sie die Programme auf dem Server
   - Lösung 2: Verwenden Sie den verbesserten Test-Generator (`teacher/generate_test_improved.php`), der auch ohne externe Programme funktioniert

2. **Berechtigungsprobleme**: Das temporäre Verzeichnis ist nicht schreibbar
   - Lösung: Stellen Sie sicher, dass PHP Schreibrechte für das temporäre Verzeichnis hat

3. **Konfigurationsprobleme**: Die PHP-Konfiguration erlaubt keine großen Uploads
   - Lösung: Erhöhen Sie die Werte für `upload_max_filesize` und `post_max_size` in der PHP-Konfiguration

### Datenbank-Fehler

Bei Datenbank-Fehlern:

1. Überprüfen Sie die Datenbankverbindungsdaten in `config/config.php`
2. Stellen Sie sicher, dass der Datenbankbenutzer ausreichende Rechte hat
3. Prüfen Sie die Logs unter `logs/debug.log` und `logs/deploy.log`

## Kontakt

Bei Fragen oder Problemen wenden Sie sich bitte an den Entwickler.

## Funktionen
- Multiple-Choice-Tests mit automatischer Auswertung
- Aufmerksamkeitsbutton zur Konzentrationskontrolle
- Lehrerbereich zur Verwaltung und Auswertung
- Testmodus für Systemtests
- Datenbankgestützte Speicherung aller Testergebnisse
- Admin-Modus für unbegrenzte Testwiederholungen
- XML-basierte Testdateien mit Backup-System

## Installation

### 1. Systemvoraussetzungen
- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- Apache Webserver (z.B. XAMPP)
- Aktivierte PHP-Erweiterungen: mysqli, xml

### 2. Datenbank-Setup
1. Erstellen Sie eine neue MySQL-Datenbank:
```sql
CREATE DATABASE mcq_test_system;
```

2. Importieren Sie das Datenbankschema:
```sql
USE mcq_test_system;

CREATE TABLE tests (
    test_id VARCHAR(50) PRIMARY KEY,
    access_code VARCHAR(10) NOT NULL,
    title VARCHAR(255) NOT NULL,
    question_count INT NOT NULL,
    answer_count INT NOT NULL,
    answer_type ENUM('single', 'multiple', 'mixed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE test_attempts (
    attempt_id VARCHAR(50) PRIMARY KEY,
    test_id VARCHAR(50),
    student_name VARCHAR(100) NOT NULL,
    points_achieved INT NOT NULL,
    points_maximum INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    grade INT NOT NULL,
    xml_file_path VARCHAR(255) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(test_id)
);

CREATE TABLE test_statistics (
    test_id VARCHAR(50) PRIMARY KEY,
    total_attempts INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0.00,
    last_attempt_at TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(test_id)
);

CREATE TABLE daily_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_code VARCHAR(10) NOT NULL,
    student_identifier VARCHAR(64) NOT NULL,
    attempt_date DATE NOT NULL,
    is_admin_attempt BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_attempt (test_code, student_identifier, attempt_date)
);
```

3. Konfigurieren Sie die Datenbankverbindung in `config/database_config.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'ihr_username');
define('DB_PASS', 'ihr_passwort');
define('DB_NAME', 'mcq_test_system');
```

### 3. Verzeichnisstruktur einrichten
```
mcq-test-system/
│
├── config/
│   ├── database_config.php    # Datenbank-Konfiguration
│   └── config.json           # Allgemeine Konfiguration
│
├── includes/
│   ├── TestDatabase.php      # Datenbank-Klasse
│   └── database_functions.php # Hilfsfunktionen
│
├── tests/                    # XML-Testdateien
│   └── beispieltest.txt   # Beispiel-Testdatei
│
└── results/                  # Verzeichnis für Testergebnisse
```

## Zugangscodes und Berechtigungen

### Normale Tests
- Reguläre Zugangscodes (z.B. "2U2")
- Ein Versuch pro Tag pro Schüler
- Ergebnisse werden in der Datenbank und als XML gespeichert

### Admin-Tests
- Zugangscode mit "-admin" Suffix (z.B. "2U2-admin")
- Unbegrenzte Versuche möglich
- Ergebnisse werden als Admin-Versuche markiert
- Ideal für Testzwecke und Überprüfungen

### Lehrerbereich
- Zugang mit "admin123"
- Vollständige Test- und Ergebnisverwaltung
- Einsicht in alle Statistiken

## Datenspeicherung

### Datenbank
- Zentrale Speicherung aller Testinformationen
- Detaillierte Statistiken und Auswertungen
- Schneller Zugriff auf Testergebnisse

### XML-Dateien
- Backup aller Testversuche
- Detaillierte Antwortprotokolle
- Langzeitarchivierung

## Ablauf der Testdurchführung und Auswertung

### 1. Einstieg über index.php
- Der Benutzer gibt einen Zugangscode ein
- Das System prüft, ob der Code gültig ist und ob der Test bereits heute absolviert wurde
- Bei gültigem Code wird der Benutzer zur Namenseingabe weitergeleitet
- Nach Eingabe des Namens wird der Test geladen

### 2. Testdurchführung in test.php
- Die Testdatei wird aus dem XML-Format geladen
- Fragen und Antworten werden gemischt, um Betrug zu verhindern
- Der Test wird dem Benutzer angezeigt, mit Single-Choice (Radio-Buttons) oder Multiple-Choice (Checkboxen) Fragen
- Der Benutzer beantwortet die Fragen und sendet das Formular ab

### 3. Testauswertung in process.php
- Die Antworten des Benutzers werden erfasst und in eine neue XML-Datei geschrieben
- Für jede Antwort wird gespeichert, ob sie vom Benutzer ausgewählt wurde (schuelerantwort = 1 oder 0)
- Die XML-Datei wird im Verzeichnis "results" gespeichert
- Der Test wird als absolviert markiert (in test_attempts.json)
- Die Auswertung wird mit der Funktion evaluateTest() durchgeführt

### 4. Auswertungslogik in auswertung.php
- Für jede Frage werden die richtigen und falschen Antworten gezählt
- Bei Single-Choice-Fragen gibt es einen Punkt für die richtige Antwort
- Bei Multiple-Choice-Fragen gibt es einen Punkt für jede richtige Antwort, aber Abzug für falsche Antworten
- Die Gesamtpunktzahl und der Prozentsatz werden berechnet
- Die Note wird anhand des Notenschemas (notenschema.txt) berechnet

### 5. Ergebnisanzeige in result.php
- Die Ergebnisse werden dem Benutzer angezeigt (Punkte, Prozentsatz, Note)
- Der Benutzer kann zur Startseite zurückkehren

### 6. Datenbankintegration
- Die Testergebnisse werden in der Datenbank gespeichert (TestDatabase.php)
- Die Teststatistiken werden aktualisiert (Durchschnittsergebnisse, Anzahl der Versuche)

### 7. Besonderheiten
- Admin-Modus: Zugangscodes mit "-admin" am Ende erlauben unbegrenzte Testversuche
- Testversuche werden pro Tag und Benutzer begrenzt (ein Versuch pro Tag)
- Die Ergebnisse werden sowohl in XML-Dateien als auch in der Datenbank gespeichert

### Datenstrom
1. **Eingabe des Zugangscodes** (index.php)
   - Überprüfung des Codes
   - Speicherung in $_SESSION['test_code']

2. **Eingabe des Namens** (index.php)
   - Speicherung in $_SESSION['student_name']
   - Laden der Testdatei in $_SESSION['test_file']

3. **Anzeige des Tests** (test.php)
   - Laden der XML-Datei
   - Mischen der Fragen und Antworten
   - Speicherung der originalen Fragen in $_SESSION['original_questions']
   - Speicherung der gemischten Fragen in $_SESSION['questions']

4. **Abgabe des Tests** (process.php)
   - Erfassung der Antworten aus $_POST
   - Erstellung einer neuen XML-Datei mit den Antworten
   - Speicherung der XML-Datei im Verzeichnis "results"
   - Markierung des Tests als absolviert
   - Auswertung des Tests
   - Speicherung der Ergebnisse in $_SESSION['test_results']
   - Speicherung der Ergebnisse in der Datenbank

5. **Anzeige der Ergebnisse** (result.php)
   - Anzeige der Ergebnisse aus $_SESSION['test_results']

## Sicherheitsfunktionen
- Verschlüsselte Datenbankverbindung
- Sichere Behandlung von Benutzereingaben
- Schutz vor SQL-Injection
- Session-basierte Zugriffskontrolle

## Fehlerbehebung

### Datenbank-Probleme
1. Überprüfen Sie die Datenbankverbindung
2. Stellen Sie sicher, dass alle Tabellen korrekt erstellt wurden
3. Prüfen Sie die Berechtigungen des Datenbankbenutzers

### XML-Fehler
1. Überprüfen Sie die Schreibrechte in den Verzeichnissen
2. Validieren Sie das XML-Format der Testdateien
3. Prüfen Sie die PHP XML-Erweiterung

### Admin-Modus Probleme
1. Überprüfen Sie das korrekte Format des Admin-Codes
2. Prüfen Sie die Session-Einstellungen
3. Kontrollieren Sie die Datenbankeinträge

## Entwicklung und Updates

### Versionierung
- Git für Quellcode-Verwaltung
- Semantische Versionierung für Releases

### Datenbank-Migration
Bei Updates der Datenbankstruktur:
1. Backup der bestehenden Daten
2. Ausführen der Migrations-Skripte
3. Überprüfen der Datenintegrität

## Support und Wartung
- Regelmäßige Backups der Datenbank
- Überprüfung der Protokolldateien
- Aktualisierung der PHP-Komponenten

## Lizenz
Dieses Projekt ist unter der MIT-Lizenz lizenziert.

## Übersicht
Dieses webbasierte Multiple-Choice-Testsystem ermöglicht es Lehrern, Tests zu erstellen, zu verwalten und auszuwerten. Schüler können Tests mit entsprechenden Zugangscodes absolvieren.

## Dateistruktur
```
mcq-test-system/
│
├── index.php              # Startseite mit Zugangscode-Abfrage
├── test.php               # Testdurchführung
├── process.php            # Testauswertung und Speicherung
├── teacher_dashboard.php  # Lehrerbereich
├── auswertung.php         # Testauswertung für Lehrer
├── preview_test.php       # Testvorschau
├── student_name_form.php  # Formular für Schülernamen
├── styles.css             # CSS-Stylesheets
│
├── tests/                 # Verzeichnis für Testdateien
│   └── beispieltest.txt   # Beispiel-Testdatei
│
└── results/               # Verzeichnis für Testergebnisse
```

## Testformat
Jede Testdatei im `tests/`-Ordner sollte folgendes Format haben:
```
zugangscode
Testname
Frage 1
Antwort A1
Antwort A2
*[richtig] Antwort A3
...
```

- Die erste Zeile enthält den Zugangscode
- Die zweite Zeile enthält den Testnamen
- Jede Frage beginnt am Zeilenanfang (ohne Leerzeichen)
- Antworten beginnen mit Leerzeichen oder *
- Richtige Antworten sind mit `[richtig]` markiert

## Lehrerbereich
- Zugang mit dem festen Zugangscode: `Amandus!123`
- Im Lehrerbereich können Sie:
  - Neue Tests hochladen
  - Verfügbare Tests ansehen
  - Testvorschauen anzeigen
  - Testergebnisse auswerten

## Punktesystem
- Für jede richtige Antwort gibt es 1 Punkt
- Für jede falsche Antwort wird 1 Punkt abgezogen
- Die Note wird wie folgt berechnet:
  - \> 90% richtig: Note 15
  - \> 80% richtig: Note 12
  - \> 70% richtig: Note 9
  - \> 60% richtig: Note 6
  - \> 50% richtig: Note 3
  - < 50% richtig: Note 0

## Versionskontrolle
Das Projekt wird mit Git versioniert. Wichtige Befehle:
- `git add .` - Alle Änderungen für Commit vormerken
- `git commit -m "Nachricht"` - Änderungen committen
- `git status` - Aktuellen Status anzeigen

# MCQ Test System - Installationsanleitung

## Systemanforderungen
- PHP 7.4 oder höher
- XAMPP oder ähnlicher Webserver
- Windows 10 oder höher

## Benötigte Abhängigkeiten

### 1. PHP Erweiterungen
Stellen Sie sicher, dass folgende PHP-Erweiterungen aktiviert sind:
- curl
- mbstring
- xml
- zip

Um diese zu aktivieren:
1. Öffnen Sie die `php.ini` in Ihrem XAMPP-Verzeichnis
2. Entfernen Sie das Semikolon (;) vor den entsprechenden Zeilen:
```ini
extension=curl
extension=mbstring
extension=xml
extension=zip
```
3. Speichern Sie die Datei und starten Sie Apache neu

### 2. yt-dlp für YouTube-Untertitel
1. Installieren Sie yt-dlp:
   ```powershell
   # Mit winget
   winget install yt-dlp

   # Oder mit chocolatey
   choco install yt-dlp
   ```
2. Verifizieren Sie die Installation:
   ```powershell
   yt-dlp --version
   ```

### 3. Tesseract OCR für Texterkennung
1. Laden Sie den Tesseract-Installer herunter:
   - Besuchen Sie [UB-Mannheim/tesseract](https://github.com/UB-Mannheim/tesseract/wiki)
   - Laden Sie den aktuellsten 64-bit Installer herunter
2. Führen Sie die Installation aus:
   - Wählen Sie während der Installation "Deutsch" als zusätzliche Sprache
   - Aktivieren Sie die Option "Add to system PATH"
3. Verifizieren Sie die Installation:
   ```powershell
   tesseract --version
   ```

### 4. Ghostscript für PDF-Verarbeitung
1. Laden Sie Ghostscript herunter:
   - Besuchen Sie [Ghostscript Downloads](https://www.ghostscript.com/releases/gsdnld.html)
   - Laden Sie die aktuelle Version für Windows (64-bit) herunter
2. Führen Sie die Installation aus:
   - Wählen Sie die Option "Add to system PATH"
3. Verifizieren Sie die Installation:
   ```powershell
   gswin64c --version
   ```

## Überprüfung der Installation

Führen Sie folgendes PHP-Skript aus, um alle Abhängigkeiten zu überprüfen:

```php
<?php
// Überprüfe PHP-Erweiterungen
$required_extensions = ['curl', 'mbstring', 'xml', 'zip'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "Fehler: PHP-Erweiterung '$ext' ist nicht installiert.\n";
    }
}

// Überprüfe externe Programme
$commands = [
    'yt-dlp --version',
    'tesseract --version',
    'gswin64c --version'
];

foreach ($commands as $cmd) {
    exec($cmd, $output, $return_var);
    if ($return_var !== 0) {
        echo "Fehler: '$cmd' ist nicht verfügbar.\n";
    }
}

echo "Überprüfung abgeschlossen.\n";
?>
```

## Fehlerbehebung

### yt-dlp Fehler
- Wenn yt-dlp nicht gefunden wird, stellen Sie sicher, dass es im System-PATH ist
- Führen Sie `refreshenv` in PowerShell aus, um den PATH zu aktualisieren
- Starten Sie den Computer neu, falls der PATH nicht aktualisiert wird

### Tesseract Fehler
- Überprüfen Sie, ob Tesseract im System-PATH ist
- Standard-Installationspfad: `C:\Program Files\Tesseract-OCR`
- Stellen Sie sicher, dass die deutschen Sprachdaten installiert sind

### Ghostscript Fehler
- Überprüfen Sie, ob Ghostscript im System-PATH ist
- Standard-Installationspfad: `C:\Program Files\gs\gs[version]`
- Bei PATH-Problemen: Fügen Sie den Pfad manuell zur Systemumgebung hinzu
