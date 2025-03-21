<?php
// Ein Hilfsskript, um die 5S7-Testversuche zu reparieren

// Funktionen aus der sync_database.php laden
require_once __DIR__ . '/includes/teacher_dashboard/sync_database.php';

// Header für UTF-8
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Reparatur der 5S7-Testversuche</h1>";
echo "<pre>";

// Führe die Reparatur durch
if (fixTestAttemptsFor5S7()) {
    echo "Die 5S7-Testversuche wurden erfolgreich repariert.\n";
} else {
    echo "Es gab Probleme bei der Reparatur der 5S7-Testversuche. Überprüfen Sie die Log-Datei für Details.\n";
}

echo "</pre>";

echo "<p><a href='teacher/teacher_dashboard.php?tab=results'>Zurück zur Ergebnisübersicht</a></p>";
?> 