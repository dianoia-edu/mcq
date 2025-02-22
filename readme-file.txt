# MCQ-Testsystem Anleitung

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

## Installation und Einrichtung
1. Kopieren Sie alle Dateien auf Ihren Webserver
2. Stellen Sie sicher, dass PHP 7.4 oder höher installiert ist
3. Erstellen Sie die Verzeichnisse `tests/` und `results/` und setzen Sie die Schreibberechtigungen korrekt:
   ```
   mkdir tests results
   chmod 755 tests results
   ```
4. Öffnen Sie die Anwendung in einem Webbrowser

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

## Sicherheitsfunktionen
- Fragen und Antworten werden zufällig gemischt
- Anwesenheitsbestätigung durch zufällig erscheinende Dialogfenster
- Lehrer haben exklusiven Zugriff auf alle Testergebnisse

## Fehlerbehebung
Falls Probleme auftreten:
1. Stellen Sie sicher, dass die PHP-Version 7.4 oder höher ist
2. Überprüfen Sie die Schreibberechtigungen für die Verzeichnisse `tests/` und `results/`
3. Prüfen Sie das Format Ihrer Testdateien
