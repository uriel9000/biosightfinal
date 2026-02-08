<?php
require_once '../includes/security.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_hash = hash('sha256', session_id());
    $ip_masked = preg_replace('/\d+$/', 'xxx', $_SERVER['REMOTE_ADDR']);
    $version = '1.0.0';

    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO consent_logs (session_hash, disclaimer_version, ip_masked) VALUES (?, ?, ?)");
            $stmt->execute([$session_hash, $version, $ip_masked]);

            $_SESSION['consent_accepted'] = true;

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to log consent.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database unavailable.']);
    }
} else {
    // Check status
    echo json_encode(['accepted' => isset($_SESSION['consent_accepted'])]);
}