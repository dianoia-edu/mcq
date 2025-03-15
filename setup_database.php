<?php
require_once 'includes/init_database.php';

try {
    echo "Starte Datenbankinitialisierung...\n";
    
    $initializer = new DatabaseInitializer();
    $initializer->initializeTables();
    
    echo "Datenbank und Tabellen wurden erfolgreich erstellt.\n";
    echo "Das System ist nun bereit für die Verwendung.\n";
} catch (Exception $e) {
    echo "Fehler bei der Datenbankinitialisierung:\n";
    echo $e->getMessage() . "\n";
    echo "Bitte überprüfen Sie die Datenbankeinstellungen in includes/database_config.php\n";
    exit(1);
} 