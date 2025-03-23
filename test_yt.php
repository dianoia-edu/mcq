<?php
// youtube_transcription.php
require_once __DIR__ . '/includes/config/openai_config.php';
require_once __DIR__ . '/includes/YoutubeTranscriptionService.php';

$result = null;
$videoUrl = '';
$errors = [];
$logs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $videoUrl = $_POST['video_url'] ?? '';
    
    if (empty($videoUrl)) {
        $errors[] = 'Bitte gib eine YouTube-URL ein.';
    } else {
        $service = new YoutubeTranscriptionService($openai_api_key);
        $result = $service->transcribeVideo($videoUrl);
        $logs = $result['logs'] ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube-Transkription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre { white-space: pre-wrap; }
        .logs { max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1>YouTube-Video-Transkription</h1>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="video_url" class="form-label">YouTube-Video-URL:</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" value="<?= htmlspecialchars($videoUrl) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Video transkribieren</button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($result): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Ergebnis</h5>
                </div>
                <div class="card-body">
                    <?php if ($result['success']): ?>
                        <h3>Transkription:</h3>
                        <div class="bg-light p-3 rounded mb-3">
                            <pre><?= htmlspecialchars($result['transcription']) ?></pre>
                        </div>
                        <div class="alert alert-success">
                            Transkription erfolgreich erstellt und gespeichert in: <?= htmlspecialchars($result['transcription_file']) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($result['error']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h3>Logs:</h3>
                    <div class="bg-dark text-light p-3 rounded logs">
                        <?php foreach ($logs as $log): ?>
                            <div><?= htmlspecialchars($log) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
