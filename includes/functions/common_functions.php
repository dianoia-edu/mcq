<?php
/**
 * Gemeinsame Funktionen für das MCQ-Test-System
 */

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
 * Prüft, ob ein Zugangscode ein Admin-Code ist
 * 
 * @param string $testCode Der zu prüfende Zugangscode
 * @return bool True, wenn es ein Admin-Code ist, sonst False
 */
function isAdminCode($testCode) {
    return substr($testCode, -6) === "-admin";
} 