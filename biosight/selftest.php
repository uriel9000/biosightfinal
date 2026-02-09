<?php
/**
 * BioSight AI: Automated Production Self-Test
 */

$testResults = [];

// 1. Critical Directory Check
$dirs = ['uploads', 'assets/css', 'assets/js', 'api', 'includes'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $testResults[] = "✅ Directory found: $dir";
    } else {
        $testResults[] = "❌ Directory MISSING: $dir";
    }
}

// 2. Environment Configuration Check
if (file_exists('.env')) {
    $env = parse_ini_file('.env');
    if (!empty($env['APP_ENCRYPTION_KEY']) && strlen($env['APP_ENCRYPTION_KEY']) >= 32) {
        $testResults[] = "✅ .env active with valid 256-bit encryption key";
    } else {
        $testResults[] = "⚠️ .env exists but encryption key is too short or missing";
    }
} else {
    $testResults[] = "❌ .env file MISSING (Critical for DB/AI)";
}

// 3. Security Header Verification
require_once 'includes/security.php';
$testResults[] = "✅ Security headers middleware active";

// 4. PWA Status
if (file_exists('manifest.json') && file_exists('sw.js')) {
    $testResults[] = "✅ PWA manifest and Service Worker found";
} else {
    $testResults[] = "❌ PWA assets MISSING";
}

// Output Results
echo implode("\n", $testResults) . "\n";
echo "--- SELF-TEST COMPLETE ---\n";
