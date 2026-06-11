<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>The One Movies System Cache Manager</title><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"><style>:root{--bg-body:#000000;--bg-card:#111111;--text-main:#ffffff;--text-muted:#888888;--border:#333333;--accent:#ffffff;--code-bg:#050505;--success:#4ade80;--error:#ef4444;--font:'Inter',-apple-system,BlinkMacSystemFont,sans-serif}body{font-family:var(--font);background-color:var(--bg-body);color:var(--text-main);display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;padding:20px}.container{background:var(--bg-card);padding:2.5rem;border-radius:16px;border:1px solid var(--border);max-width:420px;width:100%;text-align:center;box-shadow:0 20px 25px -5px rgba(0,0,0,.5)}.icon{font-size:3.5rem;margin-bottom:1rem;display:block}h3{margin:0 0 .5rem 0;font-size:1.5rem;font-weight:700;letter-spacing:-.5px}p{margin:0;color:var(--text-muted);line-height:1.5}.path-box{background:var(--code-bg);border:1px solid var(--border);color:var(--text-muted);padding:12px;border-radius:8px;font-family:'Courier New',monospace;font-size:.8rem;margin:1.5rem 0;word-break:break-all}.btn{display:inline-block;background-color:var(--accent);color:var(--bg-body);padding:12px 24px;text-decoration:none;border-radius:50px;font-weight:700;transition:all .2s ease;margin-top:10px}.btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(255,255,255,.2)}.error-title{color:var(--error)}.error-box{background:#220505;border:1px solid #450a0a;color:#fca5a5;padding:1rem;border-radius:8px;text-align:left;overflow-x:auto;margin:1rem 0}</style></head><body><div class="container"> <?php
// clean.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Adjust path to point to your actual cache class
require_once __DIR__ . '/cache.php'; 

// SECURITY: Your Token
$secretToken = 'RealDc';

// Check token
if (!isset($_GET['token']) || $_GET['token'] !== $secretToken) {
    http_response_code(403);
    // Dark Mode Access Denied
    echo '<span class="icon">⛔</span>';
    echo '<h3 class="error-title">Access Denied</h3>';
    echo '<p>Invalid Security Token provided.</p>';
    echo '</div></body></html>'; 
    die();
}

try {
    // 2. FORCE the correct directory path
    $correctCachePath = __DIR__ . '/cache/';

    // Pass the explicit path to the constructor
    $cache = new Cache(86400, $correctCachePath);

    // 3. Run the clear function
    if (method_exists($cache, 'clearAll')) {
        $cache->clearAll();
        
        // Dark Mode Success Output
        echo '<span class="icon">✅</span>';
        echo '<h3 style="color:var(--success);">Cache Cleared</h3>';
        echo '<div class="path-box">Target: ' . htmlspecialchars($correctCachePath) . '</div>';
    } else {
        throw new Exception("The 'clearAll' method is missing from cache.php.");
    }

    echo '<a href="/" class="btn">Go Back Home</a>';

} catch (Throwable $e) {
    // Dark Mode Error Output
    echo '<span class="icon">⚠️</span>';
    echo '<h3 class="error-title">System Error</h3>';
    echo '<div class="error-box"><pre style="margin:0;">' . $e->getMessage() . '</pre></div>';
    echo '<a href="index.php" class="btn" style="background:#333; color:#fff;">Go Back</a>';
}
?> </div></body></html>