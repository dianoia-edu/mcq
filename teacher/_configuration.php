<?php
if (!isset($_SESSION['teacher']) || $_SESSION['teacher'] !== true) {
    header('Location: index.php');
    exit();
}

// Direkte Konfiguration anstelle von config.php
$config = [
    // Hier können Standardwerte definiert werden
    'environment' => 'production'
];
?>

<div class="config-container">
    <h2>Konfiguration</h2>
    <form id="configForm" method="post">
        <div class="config-group">
            <label>
                <input type="checkbox" name="testMode" <?php echo $config['testMode'] ? 'checked' : ''; ?>>
                Testmodus aktivieren
            </label>
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="disableAttentionButton" <?php echo $config['disableAttentionButton'] ? 'checked' : ''; ?>>
                Aufmerksamkeitskontrolle deaktivieren
            </label>
        </div>
        
        <div class="config-group">
            <label>
                <input type="checkbox" name="allowTestRepetition" <?php echo $config['allowTestRepetition'] ? 'checked' : ''; ?>>
                Testwiederholung erlauben
            </label>
        </div>

        <button type="submit" class="btn primary-btn">Speichern</button>
    </form>
</div> 