<?php
/**
 * Konvertiert einen Text mit nummerierten Fragen und Antworten in XML-Format
 * 
 * Erwartet Text im Format:
 * 1. Frage 1
 * A) Antwort 1 [correct]
 * B) Antwort 2
 * C) Antwort 3
 * D) Antwort 4
 * 
 * 2. Frage 2
 * ...
 * 
 * @param string $text Der zu konvertierende Text
 * @param string $access_code Der Zugangscode für den Test
 * @param int $question_count Die erwartete Anzahl der Fragen
 * @param int $answer_count Die erwartete Anzahl der Antworten pro Frage
 * @param string $answer_type Der Antworttyp (single, multiple, mixed)
 * @param string $title Der Titel des Tests
 * @return DOMDocument Das erzeugte XML-Dokument
 */
function convertTextToXML($text, $access_code, $question_count, $answer_count, $answer_type, $title = 'Generierter Test') {
    // Protokolliere den Eingabetext für Debugging
    error_log("Konvertiere Text zu XML. Textlänge: " . strlen($text));
    
    // Erstelle ein neues XML-Dokument
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    
    // Erstelle Root-Element
    $root = $dom->createElement('test');
    $dom->appendChild($root);
    
    // Füge Metadaten hinzu
    $access_code_node = $dom->createElement('access_code', $access_code);
    $root->appendChild($access_code_node);
    
    $title_node = $dom->createElement('title', $title);
    $root->appendChild($title_node);
    
    $question_count_node = $dom->createElement('question_count', $question_count);
    $root->appendChild($question_count_node);
    
    $answer_count_node = $dom->createElement('answer_count', $answer_count);
    $root->appendChild($answer_count_node);
    
    $answer_type_node = $dom->createElement('answer_type', $answer_type);
    $root->appendChild($answer_type_node);
    
    // Erstelle Fragen-Container
    $questions_node = $dom->createElement('questions');
    $root->appendChild($questions_node);
    
    // Extrahiere Fragen und Antworten aus dem Text
    $lines = explode("\n", $text);
    $questions = [];
    $current_question = null;
    $current_answers = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Ignoriere Zeilen, die mit "Titel:" beginnen
        if (preg_match('/^Titel:/i', $line)) continue;
        
        // Prüfe, ob die Zeile eine Frage ist (beginnt mit Zahl + Punkt/Klammer)
        if (preg_match('/^(\d+)[\.|\)](.+)$/', $line, $matches)) {
            // Wenn wir bereits eine Frage haben, füge sie zur Liste hinzu
            if ($current_question !== null) {
                $questions[] = [
                    'nr' => $current_question['nr'],
                    'text' => $current_question['text'],
                    'answers' => $current_answers
                ];
            }
            
            // Starte eine neue Frage
            $current_question = [
                'nr' => $matches[1],
                'text' => trim($matches[2])
            ];
            $current_answers = [];
        }
        // Prüfe, ob die Zeile eine Antwort ist (beginnt mit Buchstabe + Punkt/Klammer)
        else if ($current_question !== null && preg_match('/^([A-Z])[\.|\)](.+)$/', $line, $matches)) {
            $answer_text = trim($matches[2]);
            $is_correct = 0;
            
            // Prüfe, ob die Antwort als korrekt markiert ist
            if (preg_match('/\[correct\]$/i', $answer_text)) {
                $is_correct = 1;
                // Entferne die [correct] Markierung aus dem Text
                $answer_text = trim(preg_replace('/\[correct\]$/i', '', $answer_text));
            }
            
            $current_answers[] = [
                'text' => $answer_text,
                'correct' => $is_correct
            ];
        }
    }
    
    // Letzte Frage hinzufügen, falls vorhanden
    if ($current_question !== null) {
        $questions[] = [
            'nr' => $current_question['nr'],
            'text' => $current_question['text'],
            'answers' => $current_answers
        ];
    }
    
    error_log("Extrahierte Fragen: " . count($questions));
    
    // Erstelle XML-Struktur aus den extrahierten Fragen
    foreach ($questions as $q_index => $question) {
        // Erstelle Frage-Element
        $question_node = $dom->createElement('question');
        $question_node->setAttribute('nr', $question['nr']);
        $questions_node->appendChild($question_node);
        
        // Füge Fragentext hinzu
        $text_node = $dom->createElement('text', $question['text']);
        $question_node->appendChild($text_node);
        
        // Erstelle Antworten-Container
        $answers_node = $dom->createElement('answers');
        $question_node->appendChild($answers_node);
        
        // Zähle korrekte Antworten
        $correct_count = 0;
        foreach ($question['answers'] as $answer) {
            if ($answer['correct'] == 1) {
                $correct_count++;
            }
        }
        
        // Validiere die Anzahl der korrekten Antworten
        validateCorrectAnswerCount($correct_count, $answer_type, $question['nr']);
        
        // Füge Antworten hinzu
        foreach ($question['answers'] as $a_index => $answer) {
            $answer_node = $dom->createElement('answer');
            $answer_node->setAttribute('nr', $a_index + 1);
            $answers_node->appendChild($answer_node);
            
            $answer_text_node = $dom->createElement('text', $answer['text']);
            $answer_node->appendChild($answer_text_node);
            
            $correct_node = $dom->createElement('correct', $answer['correct']);
            $answer_node->appendChild($correct_node);
        }
        
        // Füge fehlende Antworten hinzu, falls nötig
        $found_answers = count($question['answers']);
        if ($found_answers < $answer_count) {
            error_log("Frage {$question['nr']}: Nur $found_answers von $answer_count Antworten gefunden. Füge fehlende hinzu.");
            
            for ($i = $found_answers + 1; $i <= $answer_count; $i++) {
                $answer_node = $dom->createElement('answer');
                $answer_node->setAttribute('nr', $i);
                $answers_node->appendChild($answer_node);
                
                $answer_text_node = $dom->createElement('text', "Antwort $i");
                $answer_node->appendChild($answer_text_node);
                
                $correct_node = $dom->createElement('correct', '0');
                $answer_node->appendChild($correct_node);
            }
        }
    }
    
    // Überprüfe, ob die erwartete Anzahl an Fragen erreicht wurde
    $found_questions = $questions_node->childNodes->length;
    if ($found_questions < $question_count) {
        error_log("Nur $found_questions von $question_count Fragen gefunden. Füge fehlende hinzu.");
        
        // Füge fehlende Fragen hinzu
        for ($i = $found_questions + 1; $i <= $question_count; $i++) {
            $question_node = $dom->createElement('question');
            $question_node->setAttribute('nr', $i);
            $questions_node->appendChild($question_node);
            
            $text_node = $dom->createElement('text', 'Frage ' . $i);
            $question_node->appendChild($text_node);
            
            $answers_node = $dom->createElement('answers');
            $question_node->appendChild($answers_node);
            
            for ($j = 1; $j <= $answer_count; $j++) {
                $answer_node = $dom->createElement('answer');
                $answer_node->setAttribute('nr', $j);
                $answers_node->appendChild($answer_node);
                
                $answer_text_node = $dom->createElement('text', 'Antwort ' . $j);
                $answer_node->appendChild($answer_text_node);
                
                $correct_node = $dom->createElement('correct', '0');
                $answer_node->appendChild($correct_node);
            }
        }
    }
    
    return $dom;
}

/**
 * Validiert die Anzahl der korrekten Antworten basierend auf dem Antworttyp
 * 
 * @param int $correct_count Die Anzahl der korrekten Antworten
 * @param string $answer_type Der Antworttyp (single, multiple, mixed)
 * @param int $question_nr Die Nummer der Frage (für Logging)
 * @throws Exception Wenn die Anzahl der korrekten Antworten nicht dem Antworttyp entspricht
 */
function validateCorrectAnswerCount($correct_count, $answer_type, $question_nr) {
    error_log("Frage $question_nr: $correct_count korrekte Antworten gefunden, Antworttyp: $answer_type");
    
    switch ($answer_type) {
        case 'single':
            if ($correct_count !== 1) {
                throw new Exception("Frage $question_nr: Bei Antworttyp 'single' muss genau eine Antwort korrekt sein, aber $correct_count Antworten sind als korrekt markiert.");
            }
            break;
            
        case 'multiple':
            if ($correct_count < 2) {
                throw new Exception("Frage $question_nr: Bei Antworttyp 'multiple' müssen mindestens zwei Antworten korrekt sein, aber nur $correct_count Antwort(en) sind als korrekt markiert.");
            }
            break;
            
        case 'mixed':
            if ($correct_count < 1) {
                throw new Exception("Frage $question_nr: Bei Antworttyp 'mixed' muss mindestens eine Antwort korrekt sein, aber keine Antwort ist als korrekt markiert.");
            }
            break;
            
        default:
            throw new Exception("Ungültiger Antworttyp: $answer_type");
    }
}

/**
 * Extrahiert den Titel aus dem Text
 * 
 * @param string $text Der Text, aus dem der Titel extrahiert werden soll
 * @return string|null Der extrahierte Titel oder null, wenn kein Titel gefunden wurde
 */
function extractTitleFromText($text) {
    if (preg_match('/^Titel:\s*(.+?)(?:\n|$)/m', $text, $title_match)) {
        return trim($title_match[1]);
    }
    
    // Versuche, einen Titel aus dem ersten Satz zu extrahieren
    if (preg_match('/^(.+?)(?:\.|$)/m', $text, $first_sentence)) {
        $title = trim($first_sentence[1]);
        if (strlen($title) > 50) {
            $title = substr($title, 0, 47) . '...';
        }
        return $title;
    }
    
    return null;
}

/**
 * Überprüft, ob der Text im erwarteten Format ist
 * 
 * @param string $text Der zu überprüfende Text
 * @return bool True, wenn der Text im erwarteten Format ist, sonst False
 */
function isTextInExpectedFormat($text) {
    // Überprüfe, ob der Text nummerierte Fragen enthält
    $has_numbered_questions = preg_match('/\d+[\.|\)]\s+.+/m', $text);
    
    // Überprüfe, ob der Text Antworten mit Buchstaben enthält
    $has_lettered_answers = preg_match('/[A-Z][\.|\)]\s+.+/m', $text);
    
    return $has_numbered_questions && $has_lettered_answers;
} 