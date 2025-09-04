# MCQ Test System - Webserver-Deployment Anleitung

## Vorbereitung für Mehrbenutzerbetrieb

Diese Anleitung führt Sie durch die Übertragung des MCQ Test Systems auf Ihren Webserver mit vollständiger Mehrbenutzerfunktionalität.

## 📋 Voraussetzungen

- **Webserver** mit PHP 7.4+ und MySQL 5.7+
- **Administratorrechte** für Datenbank und Dateisystem
- **FTP/SSH-Zugang** zum Server

## 🚀 Deployment-Schritte

### 1. Dateien übertragen

1. **Alle Projektdateien** auf den Webserver hochladen
2. **Verzeichnisberechtigungen** setzen:
   ```bash
   chmod 755 logs/ results/ uploads/ config/
   chown www-data:www-data logs/ results/ uploads/
   ```

### 2. Datenbankverbindung konfigurieren

Die Datei `includes/database_config.php` enthält bereits die automatische Umgebungserkennung:

- **Entwicklung**: Verwendet `root` ohne Passwort
- **Produktion**: Verwendet `mcqadmin` mit Passwort `Ib1973g!np`

**Für andere Produktionsserver:** Passen Sie in `includes/database_config.php` die Erkennungslogik an:

```php
$isProduction = ($_SERVER['SERVER_NAME'] ?? '') === 'IHRE-DOMAIN.de' || 
               ($_SERVER['HTTP_HOST'] ?? '') === 'IHRE-DOMAIN.de' ||
               file_exists('/var/www/production_flag');
```

### 3. Datenbank initialisieren

**Option A: Automatisches Deployment**
```
https://ihre-domain.de/mcq-test-system/deploy.php
```

**Option B: Mit Migration bestehender Daten**
```
https://ihre-domain.de/mcq-test-system/migrate_database.php?admin_key=migrate_db_2024
```

### 4. Mehrbenutzerfunktionen aktivieren

Nach erfolgreicher Initialisierung:

1. **Lehrerbereich aufrufen:**
   ```
   https://ihre-domain.de/mcq-test-system/teacher/teacher_dashboard.php
   ```

2. **Mit Admin-Code anmelden:** `admin123`

3. **Neue Instanz erstellen:**
   - Im Dashboard: "Neue Instanz erstellen"
   - Instanzname eingeben (z.B. "schule_meyer")
   - System erstellt automatisch:
     - Separate Datenbank: `mcq_inst_schule_meyer`
     - Benutzer: `mcq_user_schule_meyer`
     - Verzeichnis: `/lehrer_instanzen/schule_meyer/`

## 🔧 Konfiguration der Instanzen

### Verzeichnisstruktur nach Instanzerstellung:
```
/var/www/html/
├── mcq-test-system/          # Hauptinstanz
└── lehrer_instanzen/         # Separate Instanzen
    ├── schule_meyer/
    │   └── mcq-test-system/  # Vollständige Kopie
    └── schule_mueller/
        └── mcq-test-system/
```

### Jede Instanz erhält:
- **Eigene Datenbank** mit eigenen Zugangsdaten
- **Isolierte Dateien** und Konfiguration
- **Separaten Admin-Zugang**
- **Unabhängige Test- und Ergebnisverwaltung**

## 📊 Verwaltung und Überwachung

### Hauptinstanz (Super-Admin):
- **URL:** `https://ihre-domain.de/mcq-test-system/`
- **Admin-Code:** `admin123`
- **Funktionen:**
  - Instanzen erstellen/verwalten
  - Systemweite Überwachung
  - Backup-Verwaltung

### Lehrer-Instanzen:
- **URL:** `https://ihre-domain.de/lehrer_instanzen/[name]/`
- **Admin-Code:** Wird bei Erstellung angezeigt
- **Funktionen:**
  - Eigene Tests erstellen
  - Schülerergebnisse verwalten
  - Statistiken einsehen

## 🔒 Sicherheitseinstellungen

### 1. Datenbankbenutzer
Jede Instanz hat einen eigenen Datenbankbenutzer mit minimalen Rechten:
```sql
GRANT ALL PRIVILEGES ON mcq_inst_[name].* TO 'mcq_user_[name]'@'localhost';
```

### 2. Dateisystem-Isolation
- Jede Instanz arbeitet in ihrem eigenen Verzeichnis
- Keine Zugriffe zwischen Instanzen möglich
- Admin-Dateien geschützt

### 3. Session-Isolation
- Separate Sessions für jede Instanz
- Keine Überschneidungen bei gleichzeitiger Nutzung

## 🧪 Tests und Überprüfung

### Nach dem Deployment prüfen:

1. **Datenbankverbindung:**
   ```
   https://ihre-domain.de/mcq-test-system/check_db.php
   ```

2. **Systemstatus:**
   ```
   https://ihre-domain.de/mcq-test-system/server_check.php
   ```

3. **Instanzerstellung testen:**
   - Im Lehrerbereich neue Testinstanz erstellen
   - Login in neuer Instanz testen
   - Test erstellen und durchführen

## 🚨 Fehlerbehebung

### Häufige Probleme:

#### 1. Datenbankverbindung fehlschlägt
- Prüfen Sie die Zugangsdaten in `includes/database_config.php`
- Stellen Sie sicher, dass MySQL läuft
- Überprüfen Sie die Firewall-Einstellungen

#### 2. Berechtigungsfehler
```bash
# Berechtigungen korrigieren:
chown -R www-data:www-data /var/www/html/mcq-test-system/
chmod -R 755 /var/www/html/mcq-test-system/
```

#### 3. Instanz kann nicht erstellt werden
- Prüfen Sie die Schreibrechte in `/var/www/html/`
- Überprüfen Sie die MySQL-Benutzerrechte
- Kontrollieren Sie die Logs: `logs/debug.log`

### Log-Dateien:
- **Deployment:** `logs/deploy.log`
- **Debug:** `logs/debug.log`
- **PHP-Fehler:** `logs/php_errors.log`

## 📞 Support

Bei Problemen:
1. Prüfen Sie die Log-Dateien
2. Überprüfen Sie die Systemvoraussetzungen
3. Testen Sie die Grundfunktionen einzeln

## 🗑️ Cleanup nach erfolgreichem Deployment

Nach erfolgreichem Test und Betrieb löschen:
```bash
rm migrate_database.php
rm WEBSERVER_DEPLOYMENT.md
```

## ✅ Erfolgreiche Implementierung

Das System unterstützt jetzt:
- ✅ **Mehrbenutzerbetrieb** mit isolierten Instanzen
- ✅ **Automatische Datenbankinitialisierung**
- ✅ **Sichere Instanzverwaltung**
- ✅ **Skalierbare Architektur**
- ✅ **Vollständige Datenmigration**
