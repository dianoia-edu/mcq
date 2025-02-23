<?php
require_once(__DIR__ . '/phpqrcode/qrlib.php');

function generateTestQRCode($testName, $accessCode) {
    // Erstelle QR-Code-Verzeichnis, falls es nicht existiert
    $qrDir = __DIR__ . '/../qrcodes';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0777, true);
    }

    // Generiere den QR-Code für die direkte URL
    $url = 'http://www.dianoia.de/test_app/student_name_form.php?code=' . urlencode($accessCode);
    $filename = $qrDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $testName) . '_qr.png';
    
    // Generiere QR-Code
    QRcode::png($url, $filename, QR_ECLEVEL_L, 10);
    
    return basename($filename);
} 