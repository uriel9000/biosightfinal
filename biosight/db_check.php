<?php
require_once 'includes/db.php';

header('Content-Type: text/plain');
echo "Checking Database Connection...\n";

if (!isset($pdo)) {
    echo "❌ PDO Object is NULL. Check your .env credentials and ensure the database exists.\n";
    exit;
}

echo "✅ Database Connected.\n";

try {
    $result = $pdo->query("SHOW TABLES LIKE 'consent_logs'");
    if ($result->rowCount() > 0) {
        echo "✅ Table 'consent_logs' exists.\n";
    } else {
        echo "❌ Table 'consent_logs' is MISSING. Please run the schema.sql in phpMyAdmin.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
