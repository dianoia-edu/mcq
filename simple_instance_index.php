<?php
// Vereinfachte index.php für Instanzen
session_start();

// Lade Instanz-spezifische Konfiguration
function loadInstanceConfig() {
    $configPath = __DIR__ . '/config/app_config.json';
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        return $config ?: [];
    }
    return [];
}

// Fehler-Variable
$error = '';

// POST-Verarbeitung
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accessCode"])) {
    $accessCode = trim($_POST["accessCode"]);
    
    if (!empty($accessCode)) {
        // Lade Instanz-Konfiguration
        $config = loadInstanceConfig();
        $adminCode = $config['admin_access_code'] ?? '';
        
        // Prüfe auf Admin-Login
        if (!empty($adminCode) && $accessCode === $adminCode) {
            $_SESSION["teacher"] = true;
            header("Location: teacher/teacher_dashboard.php");
            exit();
        }
        
        // Fallback auf Standard-Admin-Code
        if ($accessCode === "admin123") {
            $_SESSION["teacher"] = true;
            header("Location: teacher/teacher_dashboard.php");
            exit();
        }
        
        // Prüfe auf Test-Code (vereinfacht)
        $testPattern = "tests/" . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $accessCode)) . "*.xml";
        $testFiles = glob($testPattern);
        
        if (!empty($testFiles)) {
            $_SESSION["accessCode"] = $accessCode;
            $_SESSION["testFile"] = $testFiles[0];
            header("Location: name_form.php");
            exit();
        }
        
        $error = "Der eingegebene Zugangscode ist ungültig. Bitte überprüfen Sie Ihre Eingabe.";
    } else {
        $error = "Bitte geben Sie einen Zugangscode ein.";
    }
}

// Konfiguration für Anzeige
$config = loadInstanceConfig();
$schoolName = $config['schoolName'] ?? 'Online-Test-System';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($schoolName); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 90%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-control {
            border-radius: 15px;
            padding: 15px 20px;
            font-size: 1.1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 15px;
            padding: 15px 30px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.3);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
        }
        
        .access-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            border-left: 5px solid #4facfe;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo htmlspecialchars($schoolName); ?></h1>
            <p class="mb-0">Geben Sie den Zugangscode ein, um einen Test zu starten</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="access-info">
                <h5 class="text-primary mb-3">
                    <i class="bi bi-key-fill me-2"></i>Zugangscode eingeben
                </h5>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <input type="text" 
                               class="form-control" 
                               name="accessCode" 
                               placeholder="z.B. ABC123"
                               required 
                               autocomplete="off"
                               autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-play-circle-fill me-2"></i>Test starten
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
