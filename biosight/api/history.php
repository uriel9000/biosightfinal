<?php
require_once '../includes/security.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
header('Content-Type: application/json');
session_start();

$session_hash = hash('sha256', session_id());
$history = [];

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT id, image_ref as image_path, interpretation_blob, created_at FROM analysis_logs WHERE
session_hash = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$session_hash]);
        $results = $stmt->fetchAll();

        $history = [];
        foreach ($results as $row) {
            // Assuming decryptData function is defined elsewhere and available
            $row['interpretation'] = decryptData($row['interpretation_blob']);
            unset($row['interpretation_blob']);
            $history[] = $row;
        }

        echo json_encode(['success' => true, 'history' => $history]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch history.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
}