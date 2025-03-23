<?php
// youtube_transcript_api.php
header('Content-Type: application/json');

// Lade den API-Schlüssel und den Service
require_once __DIR__ . '/includes/config/openai_config.php';
require_once __DIR__ . '/includes/YoutubeTranscriptionService.php';

// Überprüfe, ob eine Video-URL übergeben wurde
$videoUrl = $_GET['url'] ?? '';

if (empty($videoUrl)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Video-URL erforderlich'
    ]);
    exit;
}

// Erstelle den Service und transkribiere das Video
$service = new YoutubeTranscriptionService($openai_api_key);
$result = $service->transcribeVideo($videoUrl);

// Gib das Ergebnis zurück
echo json_encode($result);
