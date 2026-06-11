<?php
session_start();

// Verify user is authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Check token validation
if (!isset($_GET['token']) || !validateToken($_GET['token'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or expired token');
}

// Define allowed files with ABSOLUTE PATHS - UPDATE THESE PATHS!
$allowedFiles = [
    // CSS Files
    'bootstrap' => 'assets/cb25saW5lss/movie-style.css',
    
    // JS Files
    '=bG9hZGpz=' => 'assets/jf245bd2d5s/serie-script.js',
    '=BwFpbmpz=' => 'assets/jf245bd2d5s/popup-wait.js',
    '=kjdfUIsj34=' => 'assets/jf245bd2d5s/cmVhY3Q=/Main-YXBw/series-app.js'
];

// Get requested file from query parameter
$requestedFile = isset($_GET['file']) ? $_GET['file'] : 'bootstrap';

// Check if requested file is allowed
if (!isset($allowedFiles[$requestedFile])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid file request');
}

$assetFile = $allowedFiles[$requestedFile];

// Security: Check file exists
if (!file_exists($assetFile)) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found at: ' . $assetFile);
}

// Set proper headers based on file type
$fileExtension = pathinfo($assetFile, PATHINFO_EXTENSION);
switch ($fileExtension) {
    case 'css':
        header('Content-Type: text/css');
        break;
    case 'js':
        header('Content-Type: application/javascript');
        break;
    default:
        header('Content-Type: application/octet-stream');
}

header('Cache-Control: max-age=3600');
header('X-Content-Type-Options: nosniff');

// Read and output the file
readfile($assetFile);

// Invalidate token after successful use
invalidateToken($_GET['token']);
exit;

/**
 * Token validation function
 */
function validateToken($token) {
    if (!isset($_SESSION['asset_tokens'])) {
        return false;
    }
    
    $currentTime = time();
    foreach ($_SESSION['asset_tokens'] as $storedToken => $expiryTime) {
        if ($token === $storedToken && $currentTime < $expiryTime) {
            return true;
        }
        
        // Clean up expired tokens
        if ($currentTime >= $expiryTime) {
            unset($_SESSION['asset_tokens'][$storedToken]);
        }
    }
    return false;
}

/**
 * Token invalidation function
 */
function invalidateToken($token) {
    if (isset($_SESSION['asset_tokens'][$token])) {
        unset($_SESSION['asset_tokens'][$token]);
    }
}

/**
 * Generate asset token function
 */
function generateAssetToken($file = 'bootstrap', $expiry = 300) {
    if (!isset($_SESSION['asset_tokens'])) {
        $_SESSION['asset_tokens'] = [];
    }
    
    $token = bin2hex(random_bytes(32));
    $expiryTime = time() + $expiry;
    
    $_SESSION['asset_tokens'][$token] = $expiryTime;
    
    return [
        'token' => $token,
        'expiry' => $expiryTime,
        'url' => "In9Dx27eXq?file=$file&token=$token"
    ];
}
?>