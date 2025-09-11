<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$videoId = $_POST['video_id'] ?? '';

if (empty($videoId)) {
    echo json_encode(['success' => false, 'error' => 'Video ID required']);
    exit;
}

// YouTube Data API v3 - kostenlose Methode ohne API-Key
// Verwende die YouTube oEmbed API als Fallback
$oembedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . urlencode($videoId) . "&format=json";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]
]);

$response = @file_get_contents($oembedUrl, false, $context);

if ($response === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch video data']);
    exit;
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON response']);
    exit;
}

if (isset($data['title'])) {
    echo json_encode([
        'success' => true,
        'title' => $data['title'],
        'author' => $data['author_name'] ?? '',
        'thumbnail' => $data['thumbnail_url'] ?? ''
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Title not found']);
}
?>
