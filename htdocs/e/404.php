<?php
session_start();

$_SESSION['authenticated'] = true; 
$mainToken = generateAssetToken('main-css', 300);

?> <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><link rel="shortcut icon" href="/assets/image/logo.png" type="image/x-icon"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="https://theonemovies.com/e/<?php echo $mainToken['url']; ?>"><title>404 Not Found - TheOneMovies</title></head><body><div class="container"><a href="https://theonemovies.com/" class="logo">TheOneMovies</a><div class="error-code">404</div><div class="error-name">Page Not Found</div><p class="error-desc"><b>Oops,</b> 📂 Resource Unavailable: The requested file, page, or endpoint could not be located on this server. It may have been moved, deleted, or never existed. Verify the URL and try again.</p><div class="action-buttons"><a href="https://theonemovies.com/" class="btn btn-primary">Go to Homepage</a> <a href="https://theonemovies.com/movies" class="btn btn-secondary">Search Movies</a> <a href="https://theonemovies.com/series" class="btn btn-secondary">Search Series</a></div></div><footer><p>© 2026 TheOneMovies. All rights reserved.</p></footer><script>document.addEventListener("contextmenu",function(e){e.preventDefault()})</script></body></html> <?php
function generateAssetToken($file = 'main-css', $expiry = 300) {
    if (!isset($_SESSION['asset_tokens'])) {
        $_SESSION['asset_tokens'] = [];
    }
    
    $token = bin2hex(random_bytes(32));
    $expiryTime = time() + $expiry;
    
    $_SESSION['asset_tokens'][$token] = $expiryTime;
    
    return [
        'token' => $token,
        'expiry' => $expiryTime,
        'url' => "3iNd9Ex2Qw?file=$file&token=$token"
    ];
}
?>