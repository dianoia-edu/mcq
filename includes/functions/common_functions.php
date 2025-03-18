<?php
/**
 * Gemeinsame Funktionen für das MCQ-Test-System
 */

/**
 * Extrahiert den Basis-Zugangscode
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @return string Der Basis-Zugangscode ohne "-admin"
 */
function getBaseCode($testCode) {
    // Entferne zuerst den Admin-Teil, falls vorhanden
    if (isAdminCode($testCode)) {
        $testCode = substr($testCode, 0, -6); // Entferne "-admin"
    }
    
    // Keine Zahlen mehr entfernen - wir behalten den vollständigen Code
    return $testCode;
}

/**
 * Prüft, ob ein Zugangscode ein Admin-Code ist
 * 
 * @param string $testCode Der zu prüfende Zugangscode
 * @return bool True, wenn es ein Admin-Code ist, sonst False
 */
function isAdminCode($testCode) {
    return substr($testCode, -6) === "-admin";
} 