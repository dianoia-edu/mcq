<?php

function performOCR($file) {
    // Überprüfe Upload-Fehler
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fehler beim Datei-Upload: ' . getUploadErrorMessage($file['error']));
    }

    // Überprüfe Dateityp
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Ungültiger Dateityp. Erlaubt sind nur JPG, PNG und PDF.');
    }

    // Erstelle Upload-Verzeichnis falls nicht vorhanden
    $uploadDir = __DIR__ . '/../uploads/ocr_results/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generiere eindeutigen Dateinamen
    $fileName = uniqid('ocr_') . '_' . basename($file['name']);
    $uploadFile = $uploadDir . $fileName;

    // Verschiebe die hochgeladene Datei
    if (!move_uploaded_file($file['tmp_name'], $uploadFile)) {
        throw new Exception('Fehler beim Speichern der Datei');
    }

    // Bei PDF: Konvertiere zu Bild mit ImageMagick
    if ($mimeType === 'application/pdf') {
        $imageFile = $uploadDir . pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
        // Korrigierte ImageMagick-Konvertierung
        $cmd = "convert -density 300x300 " . escapeshellarg($uploadFile) . "[0] " .
               "-quality 95 -background white -alpha remove " .
               escapeshellarg($imageFile) . " 2>&1";
        
        exec($cmd, $output, $returnVar);
        
        if ($returnVar !== 0) {
            error_log("ImageMagick PDF Konvertierung Fehler: " . implode("\n", $output));
            unlink($uploadFile);
            throw new Exception('Fehler bei der PDF-Konvertierung: ' . implode("\n", $output));
        }
        
        unlink($uploadFile); // Lösche PDF nach Konvertierung
        $uploadFile = $imageFile;
    }

    // Optimiere Bild für OCR
    $cmd = "convert " . escapeshellarg($uploadFile) . 
           " -density 300 -depth 8 -strip -background white -alpha off " . 
           escapeshellarg($uploadFile) . " 2>&1";
    
    exec($cmd, $convertOutput, $convertReturn);
    
    if ($convertReturn !== 0) {
        error_log("ImageMagick Optimierung Fehler: " . implode("\n", $convertOutput));
        unlink($uploadFile);
        throw new Exception('Fehler bei der Bildoptimierung');
    }

    // Führe OCR durch
    $output = [];
    $returnVar = 0;
    $cmd = "tesseract " . escapeshellarg($uploadFile) . " stdout -l deu 2>&1";
    exec($cmd, $output, $returnVar);
    
    // Lösche temporäre Datei
    unlink($uploadFile);
    
    if ($returnVar !== 0) {
        error_log("Tesseract Fehler: " . implode("\n", $output));
        throw new Exception('Fehler bei der OCR-Verarbeitung: ' . implode("\n", $output));
    }
    
    return implode("\n", $output);
}

function getUploadErrorMessage($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'Die hochgeladene Datei überschreitet die upload_max_filesize Direktive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'Die hochgeladene Datei überschreitet die MAX_FILE_SIZE Direktive im HTML Formular';
        case UPLOAD_ERR_PARTIAL:
            return 'Die Datei wurde nur teilweise hochgeladen';
        case UPLOAD_ERR_NO_FILE:
            return 'Es wurde keine Datei hochgeladen';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Temporäres Verzeichnis fehlt';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Fehler beim Schreiben der Datei';
        case UPLOAD_ERR_EXTENSION:
            return 'Eine PHP Erweiterung hat den Upload gestoppt';
        default:
            return 'Unbekannter Upload-Fehler';
    }
} 