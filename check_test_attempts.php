<?php
// Starte Session, falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gemeinsame Funktionen einbinden
require_once 'includes/functions/common_functions.php';

/**
 * Gibt eine eindeutige Client-ID zurück, basierend auf IP und User-Agent
 * 
 * @return string Die eindeutige Client-ID
 */
function getClientIdentifier() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return md5($ip . $userAgent);
}

/**
 * Gibt eine eindeutige Studenten-ID zurück, basierend auf dem Namen
 * 
 * @param string $studentName Der Name des Studenten
 * @return string Die eindeutige Studenten-ID
 */
function getStudentIdentifier($studentName) {
    if (empty($studentName)) {
        return getClientIdentifier();
    }
    return md5(strtolower(trim($studentName)));
}

/**
 * Generiert einen eindeutigen Cookie-Namen für einen Test
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @return string Der Cookie-Name
 */
function getTestCookieName($testCode) {
    $baseCode = getBaseCode($testCode);
    return 'test_completed_' . md5($baseCode);
}

/**
 * Überprüft, ob ein Test heute bereits absolviert wurde
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @param string $studentName Der Name des Schülers (optional)
 * @return bool True wenn der Test heute bereits absolviert wurde, sonst False
 */
function hasCompletedTestToday($testCode, $studentName = null) {
    // Admin-Modus: Erlaube unbegrenzte Versuche
    if (isAdminCode($testCode)) {
        error_log("Admin-Modus erkannt für Code: $testCode - Erlaube Testdurchführung");
        return false;
    }

    // 1. Überprüfe Cookie
    $cookieName = getTestCookieName($testCode);
    $today = date('Y-m-d');
    
    if (isset($_COOKIE[$cookieName])) {
        $cookieData = json_decode($_COOKIE[$cookieName], true);
        
        // Überprüfe, ob das Cookie für heute gilt und die Daten stimmen
        if (isset($cookieData['date']) && $cookieData['date'] === $today) {
            error_log("Cookie gefunden für Test $testCode: Test wurde heute bereits absolviert");
            return true;
        }
    }
    
    // 2. Überprüfe JSON-Datei als Backup
    $attemptsFile = 'test_attempts.json';
    
    // Wenn die Datei nicht existiert, wurde der Test noch nicht absolviert
    if (!file_exists($attemptsFile)) {
        return false;
    }
    
    // Lade die Versuche
    $attempts = json_decode(file_get_contents($attemptsFile), true);
    if (!$attempts || !isset($attempts[$testCode])) {
        return false;
    }
    
    // Erstelle Identifikatoren für die Überprüfung
    $clientId = getClientIdentifier();
    $studentId = $studentName ? getStudentIdentifier($studentName) : null;
    
    // Prüfe, ob der Test heute bereits absolviert wurde
    foreach ($attempts[$testCode] as $attempt) {
        if ($attempt['date'] === $today) {
            // Überprüfe anhand des Client-Identifikators
            if (isset($attempt['client_id']) && $attempt['client_id'] === $clientId) {
                error_log("Client-ID Match gefunden für Test $testCode: Test wurde heute bereits absolviert");
                return true;
            }
            
            // Wenn ein Schülername angegeben wurde, überprüfe auch diesen
            if ($studentId && isset($attempt['student_id']) && $attempt['student_id'] === $studentId) {
                error_log("Student-ID Match gefunden für Test $testCode: Test wurde heute bereits absolviert");
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Markiert einen Test als absolviert
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @param string $studentName Der Name des Schülers
 * @return bool True wenn der Test erfolgreich als absolviert markiert wurde
 */
function markTestAsCompleted($testCode, $studentName) {
    // Bei Admin-Tests verwenden wir den Basis-Code für die Speicherung
    $baseCode = getBaseCode($testCode);
    
    // Generiere Identifikatoren
    $clientId = getClientIdentifier();
    $studentId = getStudentIdentifier($studentName);
    
    // 1. Speichere in JSON-Datei
    $attemptsFile = 'test_attempts.json';
    $attempts = [];
    
    if (file_exists($attemptsFile)) {
        $attempts = json_decode(file_get_contents($attemptsFile), true) ?: [];
    }
    
    $today = date('Y-m-d');
    if (!isset($attempts[$baseCode])) {
        $attempts[$baseCode] = [];
    }
    
    $attempts[$baseCode][] = [
        'date' => $today,
        'student' => $studentName,
        'student_id' => $studentId,
        'client_id' => $clientId,
        'is_admin' => isAdminCode($testCode)
    ];
    
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