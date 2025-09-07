<?php
// Starte Session nur, wenn noch keine aktivgg  ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// require_once 'config.php'; wurde entfernt
require_once 'includes/database_config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug-Funktion wurde entfernt

/**
 * Generiert einen eindeutigen Identifikator für einen Schüler
 * 
 * @param string $studentName Der Name des Schülers
 * @return string Ein eindeutiger Identifikator
 */
function getClientIdentifier() {
    // Kombiniere IP-Adresse und User-Agent für eindeutige Identifizierung
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $identifier = hash('sha256', $ip . $userAgent);
    error_log("Client Identifier generiert: " . substr($identifier, 0, 8) . "...");
    return $identifier;
}

/**
 * Generiert einen eindeutigen Identifikator für einen Schüler basierend auf seinem Namen
 * 
 * @param string $studentName Der Name des Schülers
 * @return string Ein eindeutiger Identifikator
 */
function getStudentIdentifier($studentName) {
    // Normalisiere den Namen (Kleinbuchstaben, Trim)
    $normalizedName = strtolower(trim($studentName));
    
    // Behandle deutsche Umlaute und ß
    $search = array('ä', 'ö', 'ü', 'ß');
    $replace = array('ae', 'oe', 'ue', 'ss');
    $normalizedName = str_replace($search, $replace, $normalizedName);
    
    // Entferne alle nicht-alphanumerischen Zeichen, um Konsistenz zu gewährleisten
    $normalizedName = preg_replace('/[^a-z0-9]/', '', $normalizedName);
    
    // Erstelle einen Hash
    $identifier = hash('sha256', $normalizedName);
    error_log("Student Identifier generiert für '$studentName': " . substr($identifier, 0, 8) . "...");
    return $identifier;
}

/**
 * Generiert einen eindeutigen Cookie-Namen für einen Test
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @return string Ein eindeutiger Cookie-Name
 */
function getTestCookieName($testCode) {
    return 'mcq_test_' . md5($testCode);
}

/**
 * Überprüft, ob ein Zugangscode im Admin-Modus ist
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @return bool True wenn es sich um einen Admin-Zugangscode handelt
 */
function isAdminCode($testCode) {
    return str_ends_with($testCode, '-admin');
}

/**
 * Extrahiert den Basis-Zugangscode aus einem Admin-Code
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @return string Der Basis-Zugangscode ohne "-admin"
 */
function getBaseCode($testCode) {
    if (isAdminCode($testCode)) {
        return substr($testCode, 0, -6); // Entferne "-admin"
    }
    return $testCode;
}

/**
 * Überprüft, ob ein Test heute bereits absolviert wurde
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @param string $studentName Der Name des Schülers (optional)
 * @return bool True wenn der Test heute bereits absolviert wurde, sonst False
 */
function hasCompletedTestToday($testCode, $studentName = null) {
    // DEBUG: Zeige was passiert
    error_log("=== hasCompletedTestToday aufgerufen ===");
    error_log("Test-Code: " . $testCode);
    error_log("Student: " . ($studentName ?? 'null'));
    
    // KOMPLETT DEAKTIVIERT: Erlaube immer Testdurchführung
    error_log("Test-Wiederholungsprüfung KOMPLETT DEAKTIVIERT - Erlaube IMMER Testdurchführung");
    return false;
    
    /* ALTE LOGIK AUSKOMMENTIERT - NUR FÜR REFERENZ
    // Überprüfe, ob die tägliche Test-Begrenzung deaktiviert wurde
    $configFile = dirname(__FILE__) . '/config/app_config.json';
    $dailyLimitEnabled = false; // STANDARD: Limit deaktiviert
    
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if (isset($config['disableDailyTestLimit']) && $config['disableDailyTestLimit'] === true) {
            error_log("Tägliche Test-Begrenzung deaktiviert (Konfiguration) - Erlaube Testdurchführung");
            return false;
        }
        // Wenn enableDailyTestLimit explizit auf true gesetzt ist, aktiviere das Limit
        if (isset($config['enableDailyTestLimit']) && $config['enableDailyTestLimit'] === true) {
            $dailyLimitEnabled = true;
            error_log("Tägliche Test-Begrenzung aktiviert (Konfiguration)");
        }
    }
    
    // STANDARD: Tägliche Test-Begrenzung ist deaktiviert
    if (!$dailyLimitEnabled) {
        error_log("Tägliche Test-Begrenzung standardmäßig deaktiviert - Erlaube Testdurchführung");
        return false;
    }
    */
}

/**
 * Markiert einen Test als absolviert
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @param string $studentName Der Name des Schülers
 * @return bool True wenn der Test erfolgreich als absolviert markiert wurde
 */
function markTestAsCompleted($testCode, $studentName) {
    // Zusätzliche Debug-Ausgaben
    error_log("markTestAsCompleted aufgerufen - Code: $testCode, Schüler: $studentName");
    
    // Bei Admin-Tests verwenden wir den Basis-Code für die Speicherung
    $baseCode = getBaseCode($testCode);
    
    // Generiere Identifikatoren
    $clientId = getClientIdentifier();
    $studentId = getStudentIdentifier($studentName);
    
    // 1. Speichere in JSON-Datei
    $attemptsFile = 'test_attempts.json';
    $attempts = [];
    
    if (file_exists($attemptsFile)) {
        $jsonContent = file_get_contents($attemptsFile);
        error_log("JSON Inhalt: " . substr($jsonContent, 0, 100) . "...");
        $attempts = json_decode($jsonContent, true) ?: [];
    }
    
    $today = date('Y-m-d');
    if (!isset($attempts[$baseCode])) {
        $attempts[$baseCode] = [];
    }
    
    // Neuer Eintrag für den aktuellen Versuch
    $newAttempt = [
        'date' => $today,
        'student' => $studentName,
        'student_id' => $studentId,
        'client_id' => $clientId,
        'is_admin' => isAdminCode($testCode)
    ];
    
    $attempts[$baseCode][] = $newAttempt;
    error_log("Neuer Eintrag: " . print_r($newAttempt, true));
    
    // Speichere die aktualisierte JSON-Datei
    $jsonSaved = file_put_contents($attemptsFile, json_encode($attempts, JSON_PRETTY_PRINT)) !== false;
    
    // 2. Setze Cookie
    $cookieName = getTestCookieName($baseCode);
    $cookieData = json_encode([
        'date' => $today,
        'student_id' => $studentId,
        'client_id' => $clientId
    ]);
    
    // Cookie für 1 Jahr setzen
    $cookieExpiry = time() + (86400 * 365);
    $cookiePath = '/';
    $cookieSecure = false; // Auf true setzen, wenn HTTPS verwendet wird
    $cookieHttpOnly = true;
    
    $cookieSet = setcookie($cookieName, $cookieData, [
        'expires' => $cookieExpiry,
        'path' => $cookiePath,
        'secure' => $cookieSecure,
        'httponly' => $cookieHttpOnly,
        'samesite' => 'Lax'
    ]);
    
    error_log("Test $testCode als absolviert markiert für Schüler $studentName");
    error_log("JSON gespeichert: " . ($jsonSaved ? "Ja" : "Nein"));
    error_log("Cookie gesetzt: " . ($cookieSet ? "Ja" : "Nein"));
    
    return $jsonSaved;
} 