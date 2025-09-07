<?php
require_once(__DIR__ . '/phpqrcode/qrlib.php');

function generateTestQRCode($testName, $accessCode) {
    // Prüfe ob GD verfügbar ist
    if (!extension_loaded('gd')) {
        error_log('Warning: GD Library nicht verfügbar, verwende nur Text-Code');
        return $accessCode; // Gib nur den Code zurück wenn GD nicht verfügbar ist
    }

    // Erstelle QR-Code-Verzeichnis, falls es nicht existiert
    $qrDir = __DIR__ . '/../qrcodes';
    if (!is_dir($qrDir)) {
        if (!mkdir($qrDir, 0777, true)) {
            error_log('Fehler beim Erstellen des QR-Code-Verzeichnisses: ' . $qrDir);
            return $accessCode;
        }
    }

    // Prüfe ob das Verzeichnis schreibbar ist
    if (!is_writable($qrDir)) {
        error_log('QR-Code-Verzeichnis ist nicht schreibbar: ' . $qrDir);
        return $accessCode;
    }

    // Generiere den QR-Code für die direkte URL ohne SEB-Parameter
    $url = 'http://www.dianoia.de/test_app/index.php?code=' . urlencode($accessCode);
    $filename = $qrDir . '/' . $accessCode . '_qr.png';
    
    try {
        // Generiere QR-Code
        QRcode::png($url, $filename, QR_ECLEVEL_L, 10);
        
        // Prüfe ob die Datei erstellt wurde
        if (!file_exists($filename)) {
            error_log('QR-Code-Datei wurde nicht erstellt: ' . $filename);
            return $accessCode;
        }

        // Prüfe ob die Datei lesbar ist
        if (!is_readable($filename)) {
            error_log('QR-Code-Datei ist nicht lesbar: ' . $filename);
            return $accessCode;
        }

        return basename($filename);
    } catch (Exception $e) {
        error_log('QR Code generation failed: ' . $e->getMessage());
        return $accessCode; // Fallback: Gib nur den Code zurück
    }
} 