# MCQ Test-System Dokumentation

## Systemübersicht
Das MCQ (Multiple Choice Question) Test-System ist eine webbasierte Anwendung zur Durchführung von Multiple-Choice-Tests mit Aufmerksamkeitskontrolle und Wiederholungsschutz.

## Hauptkomponenten

### 1. Login-System (index.php)
- **Zugangsmethoden:**
  - Schüler-Login über testspezifische Codes
  - Lehrer-Login (Zugangscode: admin123)
- **Session-Management** für sichere Zugangskontrolle
- **Anti-Caching-Maßnahmen** implementiert

### 2. Konfigurationssystem (config.php)
- **Speicherformat:** JSON
- **Haupteinstellungen:**
  ```json
  {
    "testMode": false,
    "disableAttentionButton": false,
    "allowTestRepetition": false
  }
  ```
- **Funktionen:**
  - `saveConfig()`: Speichert Konfigurationsänderungen
  - `loadConfig()`: Lädt aktuelle Konfiguration
  - `updateConfig()`: Aktualisiert einzelne Einstellungen
  - `getTestModeWarning()`: Zeigt Testmodus-Warnungen an

### 3. Test-Durchführung (test.php)
- **Funktionen:**
  - Dynamisches Laden von Testfragen
  - Zufällige Anordnung (Fragen & Antworten)
  - Unterstützung für Single- und Multiple-Choice
- **Sicherheitsfeatures:**
  - Session-Validierung
  - Zugriffskontrolle
  - Anti-Betrugs-Mechanismen

### 4. Aufmerksamkeitskontrolle (attention.js)
- **Funktionsweise:**
  - Zufälliges Erscheinen (10-15 Sekunden Intervall)
  - 4-Sekunden Countdown
  - Abbruch nach 2 verpassten Klicks
- **Konfigurierbar** über Testmodus-Einstellungen

### 5. Wiederholungsschutz (check_test_attempts.php)
- **Identifizierungsmethoden:**
  ```php
  // Client-Identifier
  hash('sha256', IP + UserAgent)
  ```
- **Speichermethoden:**
  - Session-basiert
  - Dateibasiert (results/...)
- **Ausnahmen:** Konfigurierbar im Testmodus

### 6. Ergebnisspeicherung (save_test_result.php)
- **Speicherformat:** JSON
- **Gespeicherte Daten:**
  - Testname
  - Schülername
  - Zeitstempel
  - Abbruchstatus
  - Verpasste Klicks
- **Speicherort:** results/-Verzeichnis

## Test-Management (Lehrer-Dashboard)

### Test-Editor Tab
- **Funktionen:**
  - Erstellung neuer Tests
  - Bearbeitung bestehender Tests
  - Vorschau der Tests
- **Test-Struktur:**
  - Zugangscode (automatisch generiert oder manuell)
  - Testtitel
  - Beliebige Anzahl von Fragen
  - Multiple-Choice und Single-Choice Antworten
- **Validierung:**
  - Mindestens eine richtige Antwort pro Frage
  - Eindeutige Zugangscodes
  - Pflichtfelder-Prüfung

## Testformat 