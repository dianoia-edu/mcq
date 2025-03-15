<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database_config.php';

// Funktion zum Schreiben in die Log-Datei
function writeLog($message) {
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Überprüfe die erforderlichen Parameter
if (!isset($_POST['folder']) || !isset($_POST['file'])) {
    writeLog("Fehlende Parameter: folder=" . ($_POST['folder'] ?? 'nicht gesetzt') . ", file=" . ($_POST['file'] ?? 'nicht gesetzt'));
    echo json_encode([
        'success' => false,
        'error' => 'Fehlende Parameter'
    ]);
    exit;
}

$folder = $_POST['folder'];
$file = $_POST['file'];

writeLog("Empfangene Parameter: folder=$folder, file=$file");

try {
    // Extrahiere das Datum aus dem Dateinamen (Format: 2U2_Marie_Meyer_2025-03-06_19-14-00.xml)
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $file, $matches)) {
        $datePart = $matches[1];
        
        // Prüfe, ob es sich um einen Admin-Pfad handelt
        $isAdminPath = strpos($file, '-admin_') !== false;
        $accessCode = substr($folder, 0, 3); // Extrahiere den Zugangscode (z.B. "2U2")
        
        // Konstruiere beide möglichen Ordnernamen
        $normalFolder = $accessCode . '_' . $datePart;
        $adminFolder = $accessCode . '-admin_' . $datePart;
        
        writeLog("Extrahiertes Datum aus Dateiname: " . $datePart);
        writeLog("Extrahierter Zugangscode: " . $accessCode);
        writeLog("Ist Admin-Pfad: " . ($isAdminPath ? 'ja' : 'nein'));
        
        // Konstruiere den Pfad vom Root-Verzeichnis aus
        $rootDir = dirname(dirname(dirname(__FILE__))); // Gehe drei Verzeichnisebenen nach oben zum Root
        
        // Versuche beide möglichen Pfade, aber priorisiere den passenden Typ
        $possiblePaths = $isAdminPath ? 
            [$adminFolder, $normalFolder] : 
            [$normalFolder, $adminFolder];
        
        $foundPath = false;
        foreach ($possiblePaths as $folderName) {
            $relativePath = 'results/' . $folderName . '/' . $file;
            $fullPath = $rootDir . '/' . str_replace('\\', '/', $relativePath);
            
            writeLog("Versuche Pfad: " . $fullPath);
            
            if (file_exists($fullPath)) {
                $xmlPath = $fullPath;
                $foundPath = true;
                writeLog("XML-Datei gefunden unter: " . $xmlPath);
                break;
            }
        }
        
        if (!$foundPath) {
            writeLog("XML-Datei nicht gefunden unter den folgenden Pfaden:");
            foreach ($possiblePaths as $folderName) {
                writeLog("- results/" . $folderName . "/" . $file);
            }
            echo json_encode([
                'success' => false,
                'error' => 'XML-Datei nicht gefunden'
            ]);
            exit;
        }
        
        // Lade die XML-Datei
        $xml = simplexml_load_file($xmlPath);
        if ($xml === false) {
            writeLog("Fehler beim Lesen der XML-Datei: " . $xmlPath);
            echo json_encode([
                'success' => false,
                'error' => 'Fehler beim Lesen der XML-Datei'
            ]);
            exit;
        }
        
        writeLog("XML-Datei erfolgreich geladen: " . $xmlPath);
        
        // Generiere HTML für die Vorschau
        $html = '<div class="test-preview">';

        // Titel und Metadaten
        $html .= '<div class="test-header mb-4">';
        $html .= '<h3>' . htmlspecialchars((string)$xml->title) . '</h3>';
        $html .= '<p><strong>Schüler:</strong> ' . htmlspecialchars((string)$xml->schuelername) . '</p>';
        $html .= '<p><strong>Abgabezeitpunkt:</strong> ' . htmlspecialchars((string)$xml->abgabezeit) . '</p>';
        $html .= '</div>';

        // Fragen
        $questionNumber = 1;
        foreach ($xml->questions->question as $question) {
            $html .= '<div class="question-block mb-4">';
            $html .= '<div class="question-text">';
            $html .= '<strong>' . $questionNumber . '. </strong>';
            $html .= htmlspecialchars((string)$question->text);
            $html .= '</div>';
            
            // Antworten
            $html .= '<div class="answers-list mt-2">';
            $answerLetter = 'A';
            foreach ($question->answers->answer as $answer) {
                $isCorrect = (int)$answer->correct === 1;
                $wasSelected = (int)$answer->schuelerantwort === 1;
                
                // Bestimme die CSS-Klasse basierend auf der Kombination von Korrektheit und Auswahl
                $answerClass = '';
                if ($wasSelected && $isCorrect) {
                    $answerClass = ' bg-success-light'; // Richtig und ausgewählt
                } elseif ($wasSelected && !$isCorrect) {
                    $answerClass = ' bg-danger-light'; // Falsch und ausgewählt
                } elseif (!$wasSelected && $isCorrect) {
                    $answerClass = ' bg-warning-light'; // Richtig aber nicht ausgewählt
                }
                
                $html .= '<div class="answer' . $answerClass . '">';
                $html .= '<i class="bi ' . ($wasSelected ? 'bi-record-circle-fill' : 'bi-circle') . '"></i> ';
                $html .= $answerLetter . '. ' . htmlspecialchars((string)$answer->text);
                if ($isCorrect) {
                    $html .= ' <i class="bi bi-check text-success"></i>';
                }
                $html .= '</div>';
                
                $answerLetter++;
            }
            $html .= '</div></div>';
            $questionNumber++;
        }
        
        $html .= '</div>';

        // Sende die generierte Vorschau zurück
        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
    }
} catch (Exception $e) {
    writeLog("Fehler: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 