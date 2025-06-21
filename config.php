<?php
require_once 'security.php';

// Environment detection
$isProduction = !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']);

// Error reporting based on environment
if ($isProduction) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'data/.error_log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Database configuration
if ($isProduction) {
    // InfinityFree production settings - UPDATED WITH YOUR ACTUAL CREDENTIALS
    $servername = "sql305.infinityfree.com";
    $username = "if0_39260841";
    $password = "SCOfwiT35hpA1f";
    $dbname = "if0_39260841_movienight";
} else {
    // Local development settings
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "movie_night_db";
}

// Create secure PDO connection
try {
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Prevent SQL injection
        PDO::MYSQL_ATTR_FOUND_ROWS => true
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
} catch(PDOException $e) {
    SecurityManager::logSecurityEvent('DATABASE_CONNECTION_FAILED', $e->getMessage());
    
    if ($isProduction) {
        die("Service temporarily unavailable. Please try again later.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

// Enhanced input sanitization function
function sanitize_input($data, $type = 'string') {
    return SecurityManager::sanitizeInput($data, $type);
}

// Check for suspicious activity
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (SecurityManager::checkSuspiciousActivity($clientIP)) {
    http_response_code(403);
    die("Access denied.");
}
?>
