<?php
header('Content-Type: application/json');

// Bestimme den korrekten Basispfad
$currentDir = dirname(__FILE__);
$isInTeacherDir = strpos($currentDir, '/teacher/') !== false;

if ($isInTeacherDir) {
    // Wir sind im teacher-Unterordner
    $baseDir = dirname(dirname($currentDir));
} else {
    // Wir sind im Hauptverzeichnis
    $baseDir = dirname(dirname($currentDir));
}

try {
    // Lade vorhandene Tests
    $tests = [];
    $testFiles = glob($baseDir . '/tests/*.xml');
    
    foreach ($testFiles as $testFile) {
        try {
            $xml = simplexml_load_file($testFile);
            if ($xml === false) {
                continue;
            }
            
            $testName = pathinfo($testFile, PATHINFO_FILENAME);
            $accessCode = (string)$xml->access_code ?: substr($testName, 0, 3);
            $tests[$testName] = [
                'name' => $testName,
                'title' => (string)$xml->title ?: $testName,
                'accessCode' => $accessCode,
                'questions' => count($xml->questions->question),
                'file' => $testFile
            ];
        } catch (Exception $e) {
            error_log("Error loading test file {$testFile}: " . $e->getMessage());
        }
    }
    
    // Sortiere Tests nach Erstellungszeit (neueste zuerst)
    if (!empty($tests)) {
        uasort($tests, function($a, $b) {
            $timeA = filemtime($a['file']);
            $timeB = filemtime($b['file']);
            return $timeB - $timeA; // Neueste zuerst
        });
    }
    
    // Konvertiere zu nummeriertem Array für JSON
    $testsArray = array_values($tests);
    
    echo json_encode([
        'success' => true,
        'tests' => $testsArray,
        'count' => count($testsArray)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
