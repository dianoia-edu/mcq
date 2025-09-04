<?php
/**
 * Debug-Script um die Pfade in der Ausschlussliste zu testen
 */

// Simuliere die gleichen Pfade wie in create_instance.php
$source_system_path = dirname(dirname(__DIR__)) . '/';
$mcq_system_path = $source_system_path . 'mcq-test-system';

echo "<h1>🔍 Debug: Ausschlussliste-Pfade</h1>\n";

echo "<h2>1. Basis-Pfade</h2>\n";
echo "Source System Path: <code>" . htmlspecialchars($source_system_path) . "</code><br>\n";
echo "MCQ System Path: <code>" . htmlspecialchars($mcq_system_path) . "</code><br>\n";

echo "<h2>2. Geplante Ausschlussliste</h2>\n";
$exclude_list = [
    rtrim($source_system_path, '/') . '/lehrer_instanzen',
    rtrim($source_system_path, '/') . '/mcq-test-system/tests',
    rtrim($source_system_path, '/') . '/mcq-test-system/results',
];

foreach ($exclude_list as $i => $path) {
    $exists = is_dir($path) ? '✅ EXISTS' : '❌ NOT FOUND';
    echo "[$i] <code>" . htmlspecialchars($path) . "</code> → $exists<br>\n";
}

echo "<h2>3. Tatsächliche Verzeichnisstruktur</h2>\n";
echo "Source System: <code>" . htmlspecialchars($source_system_path) . "</code><br>\n";
if (is_dir($source_system_path)) {
    $dirs = scandir($source_system_path);
    foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir($source_system_path . $dir)) {
            echo "  📁 $dir<br>\n";
            
            // Für mcq-test-system schauen wir tiefer
            if ($dir === 'mcq-test-system') {
                $mcq_path = $source_system_path . $dir;
                $mcq_dirs = scandir($mcq_path);
                foreach ($mcq_dirs as $subdir) {
                    if ($subdir !== '.' && $subdir !== '..' && is_dir($mcq_path . '/' . $subdir)) {
                        $fullPath = $mcq_path . '/' . $subdir;
                        $fileCount = 0;
                        if (in_array($subdir, ['tests', 'results'])) {
                            $files = glob($fullPath . '/*');
                            $fileCount = count($files);
                        }
                        echo "    📁 $subdir" . ($fileCount > 0 ? " ($fileCount Dateien)" : "") . "<br>\n";
                    }
                }
            }
        }
    }
} else {
    echo "❌ Source System Pfad existiert nicht!<br>\n";
}

echo "<h2>4. Korrigierte Pfade (absolut)</h2>\n";
// Versuche verschiedene Pfad-Varianten
$possible_source_paths = [
    dirname(__DIR__) . '/',
    __DIR__ . '/',
    '/var/www/dianoia-ai.de/',
    getcwd() . '/',
];

foreach ($possible_source_paths as $test_path) {
    echo "<h3>Test-Pfad: <code>" . htmlspecialchars($test_path) . "</code></h3>\n";
    
    $test_exclude = [
        rtrim($test_path, '/') . '/lehrer_instanzen',
        rtrim($test_path, '/') . '/mcq-test-system/tests',
        rtrim($test_path, '/') . '/mcq-test-system/results',
    ];
    
    foreach ($test_exclude as $path) {
        $exists = is_dir($path) ? '✅ EXISTS' : '❌ NOT FOUND';
        $fileCount = '';
        if ($exists === '✅ EXISTS' && (strpos($path, '/tests') !== false || strpos($path, '/results') !== false)) {
            $files = glob($path . '/*');
            $fileCount = ' (' . count($files) . ' Dateien)';
        }
        echo "  <code>" . htmlspecialchars($path) . "</code> → $exists$fileCount<br>\n";
    }
}

echo "<h2>5. Live-Server Pfad-Test</h2>\n";
// Für Live-Server
$live_paths = [
    '/var/www/dianoia-ai.de/',
    '/var/www/dianoia-ai.de/mcq-test-system/',
];

foreach ($live_paths as $live_path) {
    echo "<h3>Live-Pfad: <code>" . htmlspecialchars($live_path) . "</code></h3>\n";
    
    if (is_dir($live_path)) {
        echo "✅ Existiert<br>\n";
        
        $tests_path = $live_path . 'mcq-test-system/tests';
        $results_path = $live_path . 'mcq-test-system/results';
        
        if (is_dir($tests_path)) {
            $test_files = glob($tests_path . '/*');
            echo "  📁 tests: " . count($test_files) . " Dateien<br>\n";
        }
        
        if (is_dir($results_path)) {
            $result_files = glob($results_path . '/*');
            echo "  📁 results: " . count($result_files) . " Dateien<br>\n";
        }
    } else {
        echo "❌ Existiert nicht<br>\n";
    }
}

?>
