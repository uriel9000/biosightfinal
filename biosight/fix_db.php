<?php
require_once 'includes/db.php';

try {
    // Check if interpretation_blob exists, if not add it
    $q = $pdo->query("SHOW COLUMNS FROM analysis_logs LIKE 'interpretation_blob'");
    if (!$q->fetch()) {
        echo "Adding 'interpretation_blob' column to 'analysis_logs'...\n";
        $pdo->exec("ALTER TABLE analysis_logs ADD COLUMN interpretation_blob VARBINARY(16000) AFTER image_ref");
        echo "Successfully updated schema.\n";
    } else {
        echo "'interpretation_blob' already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
