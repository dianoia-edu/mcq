<?php
// Setze Ausführungszeitlimit hoch
set_time_limit(300);

// Funktion zum Ausführen von Shell-Befehlen
function execCommand($command) {
    if (function_exists('shell_exec')) {
        return shell_exec('sudo ' . $command . ' 2>&1');
    }
    return "Error: shell_exec is not available";
}

// Ausgabe aktivieren
header('Content-Type: text/plain');

// Download Webmin
echo "Downloading Webmin...\n";
execCommand('wget http://prdownloads.sourceforge.net/webadmin/webmin_2.025_all.deb');

// Installation durchführen
echo "Installing Webmin...\n";
execCommand('dpkg -i webmin_2.025_all.deb');
execCommand('apt-get -f install -y');

// Aufräumen
execCommand('rm webmin_2.025_all.deb');

// Ausgabe der Ergebnisse
echo "Installation completed. Please check if Webmin is running at https://your-server:10000\n";
echo "Installation log:\n";
echo execCommand('systemctl status webmin');
?> 