<?php
/**
 * Migration Script: Fix analysis_logs Schema
 * 
 * This script makes the 'interpretation' column nullable to allow
 * the current INSERT logic to work while maintaining encrypted-only storage.
 */

require_once 'includes/db.php';

header('Content-Type: text/plain');
echo "=== Analysis Logs Schema Migration ===\n\n";

if (!isset($pdo)) {
    echo "❌ Database connection failed.\n";
    exit(1);
}

try {
    // 1. Check current schema
    echo "Step 1: Checking current schema...\n";
    $stmt = $pdo->query("DESCRIBE analysis_logs");
    $columns = $stmt->fetchAll();

    $hasInterpretation = false;
    $interpretationNullable = false;

    foreach ($columns as $col) {
        if ($col['Field'] === 'interpretation') {
            $hasInterpretation = true;
            $interpretationNullable = ($col['Null'] === 'YES');
            echo "  Found 'interpretation' column: Type={$col['Type']}, Nullable={$col['Null']}\n";
        }
    }

    if (!$hasInterpretation) {
        echo "  ℹ️  'interpretation' column doesn't exist. No migration needed.\n\n";
        exit(0);
    }

    if ($interpretationNullable) {
        echo "  ✅ 'interpretation' column is already nullable. No migration needed.\n\n";
        exit(0);
    }

    // 2. Make interpretation column nullable
    echo "\nStep 2: Making 'interpretation' column nullable...\n";
    $pdo->exec("ALTER TABLE analysis_logs MODIFY COLUMN interpretation TEXT NULL");
    echo "  ✅ Successfully modified 'interpretation' column to be nullable.\n";

    // 3. Verify the change
    echo "\nStep 3: Verifying the change...\n";
    $stmt = $pdo->query("DESCRIBE analysis_logs");
    $columns = $stmt->fetchAll();

    foreach ($columns as $col) {
        if ($col['Field'] === 'interpretation') {
            if ($col['Null'] === 'YES') {
                echo "  ✅ Verification successful: 'interpretation' is now nullable.\n";
            } else {
                echo "  ❌ Verification failed: 'interpretation' is still NOT NULL.\n";
                exit(1);
            }
        }
    }

    // 4. Test INSERT query
    echo "\nStep 4: Testing INSERT query...\n";
    $session_hash = hash('sha256', 'migration_test_' . time());
    $image_ref = 'migration_test_' . bin2hex(random_bytes(8)) . '.jpg';
    $test_data = json_encode(['migration' => 'test', 'timestamp' => time()]);
    $encrypted_content = encryptData($test_data);

    $stmt = $pdo->prepare("INSERT INTO analysis_logs (session_hash, image_ref, interpretation_blob) VALUES (?, ?, ?)");
    $result = $stmt->execute([$session_hash, $image_ref, $encrypted_content]);

    if ($result) {
        $insertId = $pdo->lastInsertId();
        echo "  ✅ INSERT test successful! ID: $insertId\n";

        // Clean up test data
        $pdo->prepare("DELETE FROM analysis_logs WHERE id = ?")->execute([$insertId]);
        echo "  ✅ Test data cleaned up.\n";
    } else {
        echo "  ❌ INSERT test failed!\n";
        print_r($stmt->errorInfo());
        exit(1);
    }

    // 5. Log the migration
    echo "\nStep 5: Logging migration...\n";
    $stmt = $pdo->prepare("INSERT INTO system_audit (event_type, details) VALUES (?, ?)");
    $stmt->execute(['SECURITY_ALERT', 'Schema migration: Made analysis_logs.interpretation column nullable']);
    echo "  ✅ Migration logged to system_audit.\n";

    echo "\n=== Migration Complete ===\n";
    echo "The analysis_logs table is now ready to accept INSERT queries.\n";
    echo "You can now upload images and they will be stored in the database.\n\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}
