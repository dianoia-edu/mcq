<?php
/**
 * Dummy SEB Server API
 * Liefert minimale JSON-Response für SEB Server-Verbindung
 */

// JSON Content-Type setzen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Hole Request Method
$method = $_SERVER['REQUEST_METHOD'];

// Handle OPTIONS (CORS Preflight)
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Minimale SEB Server API Response
$response = [
    'api-version' => '1.0',
    'institution' => 'MCQ Test System',
    'name' => 'MCQ Test SEB Server',
    'exam-config' => [
        'url' => 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/name_form.php',
        'hash' => hash('sha256', 'mcq-test-' . date('Y-m-d'))
    ],
    'oauth' => [
        'authorization-endpoint' => null,
        'token-endpoint' => null,
        'client-name' => null,
        'client-secret' => null,
        'redirect-uri' => null
    ],
    'ping-interval' => 120000,
    'log-endpoint' => null,
    'session-endpoint' => null
];

// Request handling basierend auf Method
switch ($method) {
    case 'GET':
        // API Discovery - SEB fragt Server-Capabilities ab
        http_response_code(200);
        echo json_encode($response, JSON_PRETTY_PRINT);
        break;
        
    case 'POST':
        // Session erstellen (falls SEB das versucht)
        $sessionResponse = [
            'session-id' => 'mcq-session-' . uniqid(),
            'status' => 'active',
            'timestamp' => date('c')
        ];
        http_response_code(201);
        echo json_encode($sessionResponse, JSON_PRETTY_PRINT);
        break;
        
    case 'PUT':
        // Session update (falls SEB das versucht)
        http_response_code(200);
        echo json_encode(['status' => 'updated'], JSON_PRETTY_PRINT);
        break;
        
    case 'DELETE':
        // Session beenden (SEB versucht das beim Quit)
        http_response_code(200);
        echo json_encode(['status' => 'terminated'], JSON_PRETTY_PRINT);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed'], JSON_PRETTY_PRINT);
        break;
}

// Debug-Logging (optional)
error_log("SEB Server API: {$method} request from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
?>
