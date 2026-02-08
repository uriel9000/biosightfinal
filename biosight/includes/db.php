<?php

function loadEnv($path)
{
    if (!file_exists($path))
        return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (strpos($line, '=') === false)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/../.env');

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db = $_ENV['DB_NAME'] ?? 'biodb';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log($e->getMessage());
}

/**
 * Encrypts data using AES-256-CTR
 */
function encryptData($data)
{
    $key = $_ENV['APP_ENCRYPTION_KEY'] ?? 'default_key_ensure_32_chars_long!!';
    $nonce = random_bytes(openssl_cipher_iv_length('aes-256-ctr'));
    $ciphertext = openssl_encrypt($data, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $nonce);
    return $nonce . $ciphertext;
}

/**
 * Decrypts data using AES-256-CTR
 */
function decryptData($data)
{
    $key = $_ENV['APP_ENCRYPTION_KEY'] ?? 'default_key_ensure_32_chars_long!!';
    $iv_length = openssl_cipher_iv_length('aes-256-ctr');
    $nonce = substr($data, 0, $iv_length);
    $ciphertext = substr($data, $iv_length);
    return openssl_decrypt($ciphertext, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $nonce);
}
