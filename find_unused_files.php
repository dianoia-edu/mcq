<?php
/**
 * Script to find unused files in the project
 * 
 * This script analyzes your project files to identify which files 
 * might be obsolete because they're not included or referenced elsewhere.
 */

// Configuration
$projectRoot = __DIR__;  // Current directory is the project root
$excludeDirs = [
    '.git',
    'vendor',
    'node_modules',
    'tests/fixtures',
    '.github'
];

// File extensions to check
$fileExtensions = [
    'php',
    'js',
    'html',
    'htm'
];

// Files that are directly accessed (entry points)
$entryPoints = [
    'index.php',
    'teacher/index.php',
    'teacher/teacher_dashboard.php',
    'test.php',
    'process.php',
    'result.php',
    'auswertung.php',
    't_attempts.php',
    'student_name_form.php',
    'check_test_attempts.php',
    // Dateien, die direkt aufgerufen werden können
    'server_check.php',
    'save_test_result.php',
    'phpinfo.php',
    'info.php',
    'db_check.php',
    'check_environment.php',
    'setup_database.php',
    'deploy.php',
    'delete_test.php'
];

// Dateien, die bekanntermaßen benötigt werden (z.B. Konfigurationsdateien, Bibliotheken)
$knownRequiredFiles = [
    'includes/database_config.php',
    'includes/TestDatabase.php',
    'includes/init_database.php',
    'includes/config/openai_config.php',
    'includes/qr_generator.php',
    'includes/phpqrcode/qrlib.php',
    'includes/phpqrcode/phpqrcode.php',
    'includes/load_test_questions.php',
    'includes/teacher_dashboard/test_editor_view.php',
    'includes/teacher_dashboard/test_generator_view.php',
    'includes/teacher_dashboard/test_results_view.php',
    'includes/teacher_dashboard/configuration_view.php',
    'includes/teacher_dashboard/sync_database.php',
    'includes/teacher_dashboard/sync_database_helper.php',
    'js/main.js'
];

// Dateien, die durch JavaScript/AJAX aufgerufen werden könnten
$ajaxEndpoints = [
    'teacher/load_test.php',
    'teacher/load_test_results.php', 
    'teacher/load_test_list.php',
    'teacher/load_all_tests.php',
    'teacher/view_test_result.php',
    'teacher/save_test_xml.php',
    'teacher/preview_test.php',
    'teacher/delete_test.php',
    'teacher/delete_tests_batch.php',
    'teacher/generate_test.php',
    'teacher/generate_test_fix.php',
    'includes/teacher_dashboard/get_test_results.php',
    'includes/teacher_dashboard/get_test_results_data.php',
    'includes/teacher_dashboard/load_test_preview.php',
    'includes/teacher_dashboard/show_results.php',
    'includes/teacher_dashboard/delete_test_results.php',
    'includes/teacher_dashboard/sync_database.php'
];

// Hilfsfunktionen, die von anderen Dateien verwendet werden
$utilityFiles = [
    'includes/functions/text_to_xml_converter.php',
    'includes/functions/common_functions.php',
    'includes/functions/whisper_transcription.php',
    'includes/utils/ocr_utils.php',
    'includes/openai_api.php',
    'includes/config/youtube_config.php',
    'includes/config/app_config.php',
    'includes/config_loader.php'
];

// Initialize arrays
$allFiles = [];
$includedFiles = [];
$usedInAjax = [];
$usedInFormAction = [];
$usedByGrep = [];

echo "Scanning project files...\n";

// Find all files in the project
findAllFiles($projectRoot, $allFiles, $excludeDirs, $fileExtensions);
echo "Found " . count($allFiles) . " files to analyze.\n";

// Find file includes and requires
foreach ($allFiles as $file) {
    analyzeFileIncludes($file, $includedFiles);
    findAjaxUsage($file, $usedInAjax);
    findFormActions($file, $usedInFormAction);
}

// Add entry points to the list of included files
foreach ($entryPoints as $entryPoint) {
    $fullPath = $projectRoot . '/' . $entryPoint;
    if (file_exists($fullPath)) {
        $includedFiles[] = $fullPath;
    }
}

// Add known required files to the list of included files
foreach ($knownRequiredFiles as $requiredFile) {
    $fullPath = $projectRoot . '/' . $requiredFile;
    if (file_exists($fullPath)) {
        $includedFiles[] = $fullPath;
    }
}

// Add AJAX endpoints to the list of used files
foreach ($ajaxEndpoints as $endpoint) {
    $fullPath = $projectRoot . '/' . $endpoint;
    if (file_exists($fullPath)) {
        $usedInAjax[] = $fullPath;
    }
}

// Add utility files to the list of used files
foreach ($utilityFiles as $utility) {
    $fullPath = $projectRoot . '/' . $utility;
    if (file_exists($fullPath)) {
        $includedFiles[] = $fullPath;
    }
}

// Use recursive grep to find file references not caught by other methods
foreach ($allFiles as $file) {
    $relativePath = str_replace($projectRoot . '/', '', $file);
    $fileBasename = basename($file);
    
    // Skip the current script
    if ($fileBasename === 'find_unused_files.php') {
        continue;
    }
    
    // Use recursive grep to find references to this file
    foreach ($allFiles as $searchFile) {
        if ($searchFile === $file) {
            continue; // Skip self-references
        }
        
        $searchContent = file_get_contents($searchFile);
        
        // Check different ways a file might be referenced
        $searchTerms = [
            $fileBasename,
            $relativePath,
            str_replace('/', '\/', $relativePath)
        ];
        
        foreach ($searchTerms as $term) {
            if (stripos($searchContent, $term) !== false) {
                $usedByGrep[] = $file;
                break 2; // Break both loops if a reference is found
            }
        }
    }
}

// Combined array of all used files
$usedFiles = array_unique(array_merge($includedFiles, $usedInAjax, $usedInFormAction, $usedByGrep));

// Find unused files
$unusedFiles = array_diff($allFiles, $usedFiles);

// Exclude the current script from unused files
$unusedFiles = array_filter($unusedFiles, function($file) {
    return basename($file) !== 'find_unused_files.php';
});

// Print results
echo "\nFiles that may be obsolete:\n";
if (count($unusedFiles) > 0) {
    foreach ($unusedFiles as $file) {
        $relativePath = str_replace($projectRoot . '/', '', $file);
        echo "- $relativePath\n";
    }
} else {
    echo "No unused files found.\n";
}

echo "\nNote: This analysis might not be 100% accurate. Some files may be used in ways that this script cannot detect (e.g., dynamically included files, files referenced in configuration, etc.).\n";

echo "\nFiles that were found to be in use:\n";
$usedFilesRelative = array_map(function($file) use ($projectRoot) {
    return str_replace($projectRoot . '/', '', $file);
}, $usedFiles);
sort($usedFilesRelative);
foreach ($usedFilesRelative as $file) {
    echo "+ $file\n";
}

// Functions

/**
 * Recursively find all files in a directory
 */
function findAllFiles($dir, &$allFiles, $excludeDirs, $fileExtensions) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            // Skip excluded directories
            if (in_array($file, $excludeDirs)) {
                continue;
            }
            
            findAllFiles($path, $allFiles, $excludeDirs, $fileExtensions);
        } else {
            // Check if file has one of the specified extensions
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array(strtolower($extension), $fileExtensions)) {
                $allFiles[] = $path;
            }
        }
    }
}

/**
 * Analyze a file for includes and requires
 */
function analyzeFileIncludes($file, &$includedFiles) {
    $content = file_get_contents($file);
    $dirname = dirname($file);
    
    // Find all includes and requires
    $patterns = [
        '/include[\s_once]*\([\'"]([^\'"]+)[\'"]\)/',
        '/include[\s_once]*[\'"]([^\'"]+)[\'"]/',
        '/require[\s_once]*\([\'"]([^\'"]+)[\'"]\)/',
        '/require[\s_once]*[\'"]([^\'"]+)[\'"]/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $includedFile) {
                resolveIncludedFile($includedFile, $dirname, $includedFiles);
            }
        }
    }
}

/**
 * Find AJAX usage (files that might be called via AJAX)
 */
function findAjaxUsage($file, &$usedInAjax) {
    $content = file_get_contents($file);
    $projectRoot = __DIR__;
    
    // Common AJAX URL patterns
    $patterns = [
        '/url:\s*[\'"]([^\'"]+\.php)[\'"]/',
        '/\$\.ajax\(\s*[\'"]([^\'"]+\.php)[\'"]/',
        '/\$\.get\(\s*[\'"]([^\'"]+\.php)[\'"]/',
        '/\$\.post\(\s*[\'"]([^\'"]+\.php)[\'"]/',
        '/fetch\(\s*[\'"]([^\'"]+\.php)[\'"]/',
        '/axios\.(?:get|post|put|delete)\(\s*[\'"]([^\'"]+\.php)[\'"]/',
        '/location\.href\s*=\s*[\'"]([^\'"]+\.php)[\'"]/i',
        '/window\.location(\.\w+)*\s*=\s*[\'"]([^\'"]+\.php)[\'"]/i',
        '/action=[\'"]([\w-]+\.php)[\'"]/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $ajaxFile) {
                // Handle both absolute and relative paths
                if (strpos($ajaxFile, 'http') === 0) {
                    // Skip external URLs
                    continue;
                }
                
                // Remove URL parameters
                $ajaxFile = strtok($ajaxFile, '?');
                
                // Try to resolve the file path
                if (file_exists($projectRoot . '/' . $ajaxFile)) {
                    $usedInAjax[] = $projectRoot . '/' . $ajaxFile;
                } elseif (file_exists(dirname($file) . '/' . $ajaxFile)) {
                    $usedInAjax[] = dirname($file) . '/' . $ajaxFile;
                }
            }
        }
    }
}

/**
 * Resolve an included file path
 */
function resolveIncludedFile($includedFile, $baseDir, &$includedFiles) {
    $projectRoot = __DIR__;
    
    // Handle __DIR__ and dirname(__FILE__) expressions
    $includedFile = str_replace(['__DIR__', 'dirname(__FILE__)'], $baseDir, $includedFile);
    
    // Handle concatenated paths
    if (strpos($includedFile, ' . ') !== false) {
        // This is a complex path, try to resolve it
        $parts = explode(' . ', $includedFile);
        $resolvedParts = [];
        
        foreach ($parts as $part) {
            $part = trim($part, "'\" \t\n\r\0\x0B");
            if ($part === '$baseDir' || $part === '$basePath') {
                $resolvedParts[] = $projectRoot;
            } elseif (strpos($part, 'dirname(') === 0) {
                // Simplistic handling of dirname()
                $resolvedParts[] = $baseDir;
            } else {
                $resolvedParts[] = $part;
            }
        }
        
        $includedFile = implode('', $resolvedParts);
    }
    
    // Resolve paths
    if (strpos($includedFile, '/') === 0) {
        // Absolute path
        $fullPath = $includedFile;
    } else {
        // Relative path
        $fullPath = $baseDir . '/' . $includedFile;
    }
    
    // Normalize the path
    $fullPath = realpath($fullPath);
    
    if ($fullPath && file_exists($fullPath)) {
        $includedFiles[] = $fullPath;
    }
}

/**
 * Find form action usages
 */
function findFormActions($file, &$usedInFormAction) {
    $content = file_get_contents($file);
    $projectRoot = __DIR__;
    
    // Pattern to match form actions
    $patterns = [
        '/<form[^>]*action=[\'"]([^\'"]+\.php)[\'"]/i',
        '/\$_POST\[[\'"]action[\'"]\]\s*===?\s*[\'"]([^\'"]+)[\'"]/i',
        '/\$_GET\[[\'"]action[\'"]\]\s*===?\s*[\'"]([^\'"]+)[\'"]/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $actionFile) {
                // Handle both absolute and relative paths
                if (strpos($actionFile, 'http') === 0) {
                    // Skip external URLs
                    continue;
                }
                
                // Remove URL parameters
                $actionFile = strtok($actionFile, '?');
                
                // Try to resolve the file path
                if (file_exists($projectRoot . '/' . $actionFile)) {
                    $usedInFormAction[] = $projectRoot . '/' . $actionFile;
                } elseif (file_exists(dirname($file) . '/' . $actionFile)) {
                    $usedInFormAction[] = dirname($file) . '/' . $actionFile;
                }
            }
        }
    }
} 