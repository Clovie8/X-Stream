<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. HELPER FUNCTIONS ---
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
        'url' => "../../../fi8L3e9NzQ?file=$file&token=$token"
    ];
}

function formatMovieDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours == 0) return $mins . 'min';
    elseif ($mins == 0) return $hours . 'h';
    else return $hours . 'h ' . $mins . 'm';
}

// --- 2. AUTH & TOKENS ---
$_SESSION['authenticated'] = true; 
$mainToken = generateAssetToken('bootstrap', 300);
$mainJSToken = generateAssetToken('=bG9hZGpz=', 300);
$popupJSToken = generateAssetToken('=BwFpbmpz=', 300);
$loadJSToken = generateAssetToken('=cG8wdXBqcw=', 300);
$appReactToken = generateAssetToken('==TS89nssk==', 300);

$dynamicAssets = [
    '{{CSS_MAIN}}'    => $mainToken['url'],
    '{{JS_POPUP}}'    => $popupJSToken['url'],
    '{{JS_MAIN}}'     => $mainJSToken['url'],
    '{{JS_LOAD}}'     => $loadJSToken['url'],
    '{{JS_APP}}'      => $appReactToken['url']
];

require_once 'cache.php';
$cache = new Cache(600);
$cacheKey = md5($_SERVER['REQUEST_URI']);

// --- 3. CACHE CHECK ---
$cachedContent = $cache->get($cacheKey);
if ($cachedContent) {
    $output = str_replace(array_keys($dynamicAssets), array_values($dynamicAssets), $cachedContent);
    echo $output;
    // Note: We append a small comment so you know it's cached, purely for debugging
    echo "";
    exit;
}

ob_start();

include 'assets/sbW92aWVzrbW92aWVzc/connection.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// --- 4. DATA FETCHING ---
// We define a default empty structure for React to avoid crashes if DB fails
$reactData = [
    'serie' => null,
    'episodes' => [],
    'urls' => [
        'base' => '../../../',
        'poster_path' => 'https://media.theonemovies.com/'
    ]
];

if(isset($_GET['playseriekey']) && isset($_GET['withpscat']) && !empty($_GET['playseriekey']) && !empty($_GET['withpscat'])){
    
    $seriekey = htmlspecialchars($_GET['playseriekey']);
    $category = htmlspecialchars($_GET['withpscat']);
    
    // Fetch Series Info
    $select_movie = "SELECT `id`, `name`, `description`, `category`, `release_year`, `translator`, `season`, `episodes`, `image`, `ratings`, `backdrop` FROM `series` WHERE token = ? AND category = ? LIMIT 1";
    $stmt = mysqli_prepare($connect, $select_movie);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $seriekey, $category);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $serie = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if(!$serie){
            header('Location: ../../../series');
            exit();
        }
        $reactData['serie'] = $serie;
    } else {
        error_log("Database error: " . mysqli_error($connect));
        header('Location: ../../../series');
        exit();
    }
    
    
    
    //=== VIEWS COUNTING: SERIES ====
    $serieid = (int)$serie['id']; 
    $cookieName = "viewed_serie_" . $serieid;

    
    // 1. Get the REAL User IP through Cloudflare and Azure Load Balancers
    $user_ip = $_SERVER['REMOTE_ADDR']; // Fallback

    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $user_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $user_ip = trim($ip_list[0]);
    }
    
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "Unknown";

    
    // 0. CHECK IF VISITOR IS A BOT
    if (preg_match('/bot|crawl|curl|dataprovider|search|get|spider|find|java|majesticsEO|google|yahoo|teoma|contxeble|yandex|libwww-perl|facebook/i', $userAgent)) {
        // Bot detected. Do nothing.
    } else {
    
        $timezone = new DateTimeZone('Africa/Kigali');
        $now = new DateTime('now', $timezone);
        $created_at = $now->format('Y-m-d H:i:s');
    
        // 1. CHECK DATABASE SPEED LIMIT (With 'type' check)
        $checkStmt = $connect->prepare("SELECT id FROM view_analytics 
                                        WHERE video_id = ? 
                                        AND user_ip = ? 
                                        AND type = 'serie' 
                                        AND viewed_at > (? - INTERVAL 1 MINUTE)");
        $checkStmt->execute([$serieid, $user_ip, $created_at]);
        $recentView = $checkStmt->fetch();
    
        // 2. ONLY COUNT IF NO RECENT VIEW
        if (!$recentView) {
            if (!isset($_COOKIE[$cookieName])) {
    
                // === GATHER USER DATA ===
                $os = "Unknown OS";
                if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
                elseif (strpos($userAgent, 'Android') !== false) $os = 'Android';
                elseif (strpos($userAgent, 'iPhone') !== false) $os = 'iPhone';
                elseif (strpos($userAgent, 'iPad') !== false) $os = 'iPad';
                elseif (strpos($userAgent, 'Mac') !== false) $os = 'Mac OS';
                elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
                
                $browser = "Unknown Browser";
                if (strpos($userAgent, 'OPR') !== false || strpos($userAgent, 'Opera') !== false) {
                    $browser = 'Opera';
                } elseif (strpos($userAgent, 'Edg') !== false) { // Modern Edge uses 'Edg'
                    $browser = 'Edge';
                } elseif (strpos($userAgent, 'Chrome') !== false) {
                    $browser = 'Chrome';
                } elseif (strpos($userAgent, 'Firefox') !== false) {
                    $browser = 'Firefox';
                } elseif (strpos($userAgent, 'Safari') !== false) {
                    $browser = 'Safari';
                }
    
                $device = "Desktop";
                if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
                    $device = "Mobile";
                }


    
                $country = "Unknown";
                $city = "Unknown";
                
                if (isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
                    $country = $_SERVER["HTTP_CF_IPCOUNTRY"];
                }
                
                $apiUrl = "http://ip-api.com/json/{$user_ip}?fields=status,country,city";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
                $response = curl_exec($ch);
                curl_close($ch);
                
                if ($response) {
                    $locationData = json_decode($response);
                    if ($locationData && $locationData->status == 'success') {
                        $country = $locationData->country;
                        $city = $locationData->city;
                    }
                }
                // === END GATHER DATA ===
    
                // 3. UPDATE TOTAL COUNT
                $updateStmt = $connect->prepare("UPDATE series SET views = views + 1 WHERE id = ?");
                $updateStmt->execute([$serieid]);
    
                // 4. INSERT HISTORY (New Columns + 'serie' type)
                $historySql = "INSERT INTO view_analytics 
                               (video_id, viewed_at, user_ip, type, os, device, browser, country, city) 
                               VALUES (?, ?, ?, 'serie', ?, ?, ?, ?, ?)";
                $historyStmt = $connect->prepare($historySql);
                $historyStmt->execute([$serieid, $created_at, $user_ip, $os, $device, $browser, $country, $city]);
    
                // 5. SET COOKIE
                setcookie($cookieName, "1", time() + 86400, "/"); 
            }
        }
    }
    
    
    
    
    // Fetch Episodes
    $select_more = "SELECT `name`, `duration`, `token` FROM `episodes` WHERE serie_token = ? ORDER BY id ASC";
    $stmt = mysqli_prepare($connect, $select_more);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "s", $seriekey);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            // We pre-calculate duration here so React doesn't have to do logic
            $row['formatted_duration'] = formatMovieDuration($row['duration']);
            
            // We extract episode number from name here (logic from your original code)
            $last_four = substr($row['name'], -4);
            $numeric_value = preg_replace('/[^0-9]/', '', $last_four); 
            $row['ep_number'] = $numeric_value;
            
            // Build the watch link URL here for easier usage in React
            $link = "../../../play-serie/" . urlencode($row['token']) . "/" . urlencode($serie['category']) . "/" . urlencode(str_replace(' ', '-', $row['name']));
            $row['watch_link'] = $link;

            $reactData['episodes'][] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    header('Location: ../../../series');
    exit();
}

// --- GLOBAL R2 CONFIGURATION ---
$r2Domain = "https://media.theonemovies.com/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?php echo htmlspecialchars($serie['name']); ?> | Season <?php echo htmlspecialchars($serie['season']); ?></title>
    <meta name="description" content="Discover episodes, translator info, and ratings for <?php echo htmlspecialchars($serie['name']); ?>. Stream full Agasobanuye seasons in HD and track new releases on The One Movies.">
    <meta name="author" content="The One Movies">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://theonemovies.com/serie-details/<?php echo $seriekey;?>/<?php echo urlencode($serie['category']);?>/<?php echo urlencode(str_replace(' ', '-', $serie['name']));?>">
    
    <meta property="og:type" content="video.tv_show">
    <meta property="og:site_name" content="The One Movies">
    <meta property="og:title" content="<?php echo htmlspecialchars($serie['name']); ?> - Season <?php echo htmlspecialchars($serie['season']); ?>">
    <meta property="og:description" content="Stream full episodes of <?php echo htmlspecialchars($serie['name']); ?> (<?php echo htmlspecialchars($serie['release_year']); ?>). Translated by <?php echo htmlspecialchars($serie['translator']); ?>.">
    <meta property="og:image" content="<?php echo !empty($serie['image']) ? (strpos($serie['image'], 'http') === 0 ? htmlspecialchars($serie['image']) : $r2Domain . htmlspecialchars($serie['image'])) : $r2Domain . 'default-image.jpg'; ?>">
    <meta property="og:url" content="https://theonemovies.com/serie-details/<?php echo $seriekey;?>/<?php echo urlencode($serie['category']);?>/<?php echo urlencode(str_replace(' ', '-', $serie['name']));?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@theonemovies">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($serie['name']); ?> - S<?php echo htmlspecialchars($serie['season']); ?>">
    <meta name="twitter:description" content="Watch <?php echo htmlspecialchars($serie['name']); ?> translated by <?php echo htmlspecialchars($serie['translator']); ?> on The One Movies.">
    <meta name="twitter:image" content="<?php echo !empty($serie['image']) ? (strpos($serie['image'], 'http') === 0 ? htmlspecialchars($serie['image']) : $r2Domain . htmlspecialchars($serie['image'])) : $r2Domain . 'default-image.jpg'; ?>">

    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">

    <link rel="icon" href="https://theonemovies.com/../../../favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="https://theonemovies.com/../../../favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="https://theonemovies.com/../../../assets/image/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://theonemovies.com/../../../assets/image/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://theonemovies.com/../../../assets/image/apple-touch-icon.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{CSS_MAIN}}">
    
    <style>#cinematic-preloader{position:fixed;top:0;left:0;width:100%;height:100%;background-color:#000;z-index:99999;display:flex;justify-content:center;align-items:center;transition:opacity .6s ease-out,visibility .6s}.progress-line-track{position:absolute;top:0;left:0;width:100%;height:4px;background:rgba(255,255,255,.05);overflow:hidden}.progress-line-bar{position:absolute;top:0;left:0;height:100%;width:0;background:#e50914;box-shadow:0 0 15px #e50914;animation:slide-top 2s cubic-bezier(.23,1,.32,1) forwards}.center-dot{width:15px;height:15px;background-color:#e50914;border-radius:50%;position:relative;animation:nuclear-pulse 1.2s infinite ease-in-out}.center-dot::after{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:100%;height:100%;border-radius:50%;border:2px solid #e50914;animation:ripple 1.2s infinite ease-out}@keyframes slide-top{0%{width:0}50%{width:60%}100%{width:100%}}@keyframes nuclear-pulse{0%{transform:scale(1);box-shadow:0 0 0 0 rgba(229,9,20,.7)}50%{transform:scale(1.5);box-shadow:0 0 20px 10px rgba(229,9,20,0);opacity:1}100%{transform:scale(1);box-shadow:0 0 0 0 rgba(229,9,20,0)}}@keyframes ripple{0%{width:100%;height:100%;opacity:1;border-width:2px}100%{width:400%;height:400%;opacity:0;border-width:0}}.media-poster{position:relative;background-color:#1a1a1a;aspect-ratio:2/3;overflow:hidden}.poster-loader,.poster-loader:before,.poster-loader:after{border-radius:50%;width:10px;height:10px;animation-fill-mode:both;animation:bounceloader 1.8s infinite ease-in-out}.poster-loader{color:#e50914;font-size:7px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) translateZ(0);text-indent:-9999em;animation-delay:-.16s;z-index:0}.poster-loader:before,.poster-loader:after{content:'';position:absolute;top:0}.poster-loader:before{left:-1.5em;animation-delay:-.32s}.poster-loader:after{left:1.5em}@keyframes bounceloader{0%,80%,100%{box-shadow:0 10px 0 -10px}40%{box-shadow:0 10px 0 0}}.poster-img{position:relative;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .5s ease-in-out;z-index:1;color:transparent;text-indent:-9999px}.media-type,.media-info,.media-info-conver,.bookmark-btn{position:absolute;z-index:2}</style>
    
    <script> window.serverData = "<?php echo base64_encode(json_encode($reactData)); ?>"; </script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="{{JS_MAIN}}"></script>
    <script src="{{JS_POPUP}}"></script>
    <script src="https://heavenlysuspicious.com/61/d3/71/61d3719ca5e74700c4f5ecb57e24c0d7.js"></script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XK2S3QJH0W"></script>
    <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'G-XK2S3QJH0W'); </script>
</head>
<body>
    <div id="cinematic-preloader"><div class="progress-line-track"><div class="progress-line-bar"></div></div><div class="center-dot"></div></div>
    <div id="jquery-powered" class="container"></div>
    <script defer src="{{JS_APP}}"></script>
</body>
</html>
<?php
// --- 5. FINALIZE & CACHE ---
$content = ob_get_contents();
ob_end_clean();

if (strlen($content) > 100) {
    $cache->save($cacheKey, $content);
}

$finalOutput = str_replace(
    array_keys($dynamicAssets), 
    array_values($dynamicAssets), 
    $content
);

echo $finalOutput;
?>