<?php
require_once '../includes/security.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
header('Content-Type: application/json');
session_start();

// 1. Authentication & Integrity Check
if (!isset($_SESSION['consent_accepted'])) {
    sendResponse(false, 'Unauthorized: Legal consent required.', [], 403);
}

// 2. Rate Limiting
if (!checkRateLimit()) {
    sendResponse(false, 'Too many requests. Please wait 10 seconds.', [], 429);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed.', [], 405);
}

// 3. File Validation
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, 'No image uploaded or upload error occurred.');
}

$file = $_FILES['image'];

// Validation: Size
if ($file['size'] > MAX_FILE_SIZE) {
    sendResponse(false, 'File too large. Maximum size is 10MB.');
}

// Validation: MIME Type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, ALLOWED_MIMES)) {
    sendResponse(false, 'Invalid file type. Only JPG, PNG, and WebP allowed.');
}

// 4. Secure File Handling
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate a random, high-entropy filename to prevent enumeration
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeFileName = bin2hex(random_bytes(16)) . '.' . $extension;
$targetPath = $uploadDir . $safeFileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {

    // 5. Communication with Python AI Service (FastAPI)
// We use cURL for a production-grade microservice connection
    $ch = curl_init(PYTHON_SERVICE_URL);
    $cfile = new CURLFile($targetPath, $mime, $safeFileName);
    $data = ['file' => $cfile];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30s timeout for AI analysis

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $result) {
        $aiData = json_decode($result, true);
        $interpretation = $aiData['interpretation'] ?? $result;

        // 6. DB Persistence & Encryption
        if (isset($pdo)) {
            try {
                $session_hash = hash('sha256', session_id());
                $encrypted_content = encryptData($interpretation);

                $stmt = $pdo->prepare("INSERT INTO analysis_logs (session_hash, image_ref, interpretation_blob) VALUES (?, ?, ?)");
                $stmt->execute([$session_hash, $safeFileName, $encrypted_content]);

                // Audit Success
                $stmt = $pdo->prepare("INSERT INTO system_audit (event_type, details) VALUES (?, ?)");
                $stmt->execute(['UPLOAD_SUCCESS', "Session: $session_hash, File: $safeFileName"]);

            } catch (Exception $e) {
                error_log("Production DB Error: " . $e->getMessage());
            }
        }

        sendResponse(true, 'Analysis complete.', [
            'interpretation' => $interpretation,
            'image_id' => $safeFileName
        ]);
    } else {
        // Fallback or Error
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        error_log("AI Service Failure. HTTP: $httpCode, cURL Error ($curlErrno): $curlError, Result: $result");
        sendResponse(false, 'AI analysis service is currently unavailable.', ['code' => $httpCode], 502);
    }
} else {
    sendResponse(false, 'Failed to store uploaded image.', [], 500);
}