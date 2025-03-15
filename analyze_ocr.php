<?php
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: monospace; padding: 20px; }
        .word { margin: 5px; padding: 3px; }
        .umlaut { background: #ffeb3b; }
        .stats { margin: 20px 0; padding: 10px; background: #f5f5f5; }
        .file { margin-bottom: 30px; border-bottom: 1px solid #ccc; }
    </style>
</head>
<body>';

$resultDir = __DIR__ . '/ocr_results';
$files = glob($resultDir . '/*.txt');

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    echo "<div class='file'>";
    echo "<h2>Analyse: $filename</h2>";
    
    // Statistiken
    $stats = [
        'total_words' => 0,
        'words_with_umlauts' => 0,
        'umlaut_a' => 0,
        'umlaut_o' => 0,
        'umlaut_u' => 0,
        'sharp_s' => 0
    ];
    
    // Wörter mit Umlauten finden
    $words = str_word_count($content, 1, 'äöüÄÖÜß');
    $stats['total_words'] = count($words);
    
    echo "<div class='stats'>";
    echo "Gesamtwörter: " . $stats['total_words'] . "<br>";
    
    // Suche nach Wörtern mit Umlauten
    echo "<h3>Wörter mit Umlauten:</h3>";
    foreach ($words as $word) {
        if (preg_match('/[äöüÄÖÜß]/', $word)) {
            echo "<span class='word umlaut'>$word</span>";
            $stats['words_with_umlauts']++;
            
            // Zähle einzelne Umlaute
            $stats['umlaut_a'] += substr_count($word, 'ä') + substr_count($word, 'Ä');
            $stats['umlaut_o'] += substr_count($word, 'ö') + substr_count($word, 'Ö');
            $stats['umlaut_u'] += substr_count($word, 'ü') + substr_count($word, 'Ü');
            $stats['sharp_s'] += substr_count($word, 'ß');
        }
    }
    
    // Zeige Statistiken
    echo "<h3>Umlaut-Statistiken:</h3>";
    echo "Wörter mit Umlauten: " . $stats['words_with_umlauts'] . "<br>";
    echo "ä/Ä: " . $stats['umlaut_a'] . "<br>";
    echo "ö/Ö: " . $stats['umlaut_o'] . "<br>";
    echo "ü/Ü: " . $stats['umlaut_u'] . "<br>";
    echo "ß: " . $stats['sharp_s'] . "<br>";
    echo "</div>";
    
    echo "</div>";
}

echo '</body></html>'; 