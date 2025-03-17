<?php
/**
 * Gemeinsame Funktionen für das MCQ-Test-System
 */

/**
 * Extrahiert den Basis-Zugangscode
 * 
 * @param string $testCode Der Zugangscode des Tests
 * @return string Der Basis-Zugangscode ohne "-admin" und ohne Zahlen am Ende
 */
function getBaseCode($testCode) {
    // Entferne zuerst den Admin-Teil, falls vorhanden
    if (isAdminCode($testCode)) {
        $testCode = substr($testCode, 0, -6); // Entferne "-admin"
    }
    
    // Entferne dann alle Zahlen am Ende des Codes
    return preg_replace('/[0-9]+$/', '', $testCode);
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