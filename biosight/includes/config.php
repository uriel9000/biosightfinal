<?php
/**
 * BioSight AI: Global Production Configuration
 */

// Security Constants
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/webp']);
define('RATE_LIMIT_SECONDS', 10); // 1 request per 10 seconds per session
define('PYTHON_SERVICE_URL', 'http://localhost:8000/analyze'); // FastAPI endpoint

// Prevent errors from breaking JSON output in API responses
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Session Security - Only set if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

/**
 * Middleware: Rate Limiter
 * Simple session-based tracker
 */
function checkRateLimit()
{
    if (!isset($_SESSION['last_request_time'])) {
        $_SESSION['last_request_time'] = time();
        return true;
    }

    $elapsed = time() - $_SESSION['last_request_time'];
    if ($elapsed < RATE_LIMIT_SECONDS) {
        return false;
    }

    $_SESSION['last_request_time'] = time();
    return true;
}

/**
 * Utility: Standard JSON Response
 */
function sendResponse($success, $message, $data = [], $code = 200)
{
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'timestamp' => time()
    ], $data));
    exit;
}
