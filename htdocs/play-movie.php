<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 

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
        'url' => "../../../iN8dE64XyZ?file=$file&token=$token"
    ];
}

$_SESSION['authenticated'] = true; 
$mainToken = generateAssetToken('bootstrap', 300);
$mainJSToken = generateAssetToken('=bG9hZGpz=', 300);
$popupJSToken = generateAssetToken('=BwFpbmpz=', 300);
$appJSToken = generateAssetToken('=38CnaWSydj=', 300);

$dynamicAssets = [
    '{{CSS_MAIN}}'    => $mainToken['url'],
    '{{JS_POPUP}}'    => $popupJSToken['url'],
    '{{JS_MAIN}}'     => $mainJSToken['url'],
    '{{JS_APP}}'      => $appJSToken['url']
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

// Validate and sanitize input
if(isset($_GET['playmoviekey']) && isset($_GET['withpmcat']) &&
    !empty($_GET['playmoviekey']) && !empty($_GET['withpmcat'])){
    
    // Sanitize inputs
    //$moviekey = filter_var($_GET['playmoviekey'], FILTER_SANITIZE_STRING);
    //$category = filter_var($_GET['withpmcat'], FILTER_SANITIZE_STRING);
    
    $moviekey = htmlspecialchars($_GET['playmoviekey']);
    $category = htmlspecialchars($_GET['withpmcat']);
    
    // Prepare and execute query securely
    $select_movie = "SELECT  
    `id`, `name`, `description`, `category`, `release_year`, `duration`, `translator`, `link`, `download`, `image`, `Part_token`, `token`, `ratings`
    FROM `movies` WHERE token = ? AND category = ? LIMIT 1";
    $stmt = mysqli_prepare($connect, $select_movie);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $moviekey, $category);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $movie = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if(!$movie){
            header('Location: ../../../movies');
            exit();
        }
    } else {
        error_log("Database error: " . mysqli_error($connect));
        header('Location: ../../../movies');
        exit();
    }
    
    
    
    
    //=== VIEWS COUNTING: MOVIES ====
    $movieid = (int)$movie['id']; 
    $cookieName = "viewed_movie_" . $movieid;
    

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
                                        AND type = 'movie' 
                                        AND viewed_at > (? - INTERVAL 1 MINUTE)");
        $checkStmt->execute([$movieid, $user_ip, $created_at]);
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
    

                // Location API (Suppressed errors with @ to prevent crashing)
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
                $updateStmt = $connect->prepare("UPDATE movies SET views = views + 1 WHERE id = ?");
                $updateStmt->execute([$movieid]);
    
                // 4. INSERT HISTORY (New Columns + 'movie' type)
                $historySql = "INSERT INTO view_analytics 
                               (video_id, viewed_at, user_ip, type, os, device, browser, country, city) 
                               VALUES (?, ?, ?, 'movie', ?, ?, ?, ?, ?)";
                $historyStmt = $connect->prepare($historySql);
                $historyStmt->execute([$movieid, $created_at, $user_ip, $os, $device, $browser, $country, $city]);
    
                // 5. SET COOKIE
                setcookie($cookieName, "1", time() + 86400, "/"); 
            }
        }
    }
    
    
    
    

    // Part token of playing movie
    $part_token = htmlspecialchars($movie['Part_token']);
    
    // Get similar movies
    $ispart="No";
    $similar_movies = [];
    $select_more = "SELECT 
    `name`, `category`, `duration`, `release_year`, `translator`, `image`, `token`, `ratings`
    FROM `movies` WHERE category = ? AND Is_Part = ? AND token != ? AND Part_token != ? ORDER BY id DESC LIMIT 10";
    $stmt = mysqli_prepare($connect, $select_more);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ssss", $category, $ispart, $moviekey, $part_token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $similar_movies[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    // Get part of movie
    $movie_part = [];
    $select_part = "SELECT  
    `name`, `description`, `category`, `release_year`, `duration`, `translator`, `image`, `Part_token`, `token`
    FROM `movies` WHERE Part_token = ? AND token != ? ORDER BY id ASC";
    $stmt_part = mysqli_prepare($connect, $select_part);
    
    if($stmt_part){
        mysqli_stmt_bind_param($stmt_part, "ss", $part_token, $moviekey);
        mysqli_stmt_execute($stmt_part);
        $result_part = mysqli_stmt_get_result($stmt_part);
        while($row_part = mysqli_fetch_assoc($result_part)){
            $movie_part[] = $row_part;
        }
        mysqli_stmt_close($stmt_part);
    }

    
    
    
    // SET DAYNMIC CONTENT COOKIE 
    if ($movie) {
        // 2. Safely grab the category and translator (in case one is blank)
        $clicked_category = !empty($row['category']) ? $row['category'] : null;
        $clicked_translator = !empty($row['translator']) ? $row['translator'] : null;
        
        // ---------------------------------------------------------
        // 3. PROCESS THE CATEGORY COOKIE
        // ---------------------------------------------------------
        if ($clicked_category) {
            // Read existing, or start fresh
            $saved_categories = isset($_COOKIE['user_categories']) ? json_decode($_COOKIE['user_categories'], true) : [];
            if (!is_array($saved_categories)) $saved_categories = []; // Safety fallback
            
            // Add new, remove duplicates, keep top 3
            array_unshift($saved_categories, $clicked_category);
            $saved_categories = array_unique($saved_categories);
            $saved_categories = array_slice($saved_categories, 0, 3);
            
            // Save the cookie for 30 days site-wide
            setcookie('user_categories', json_encode($saved_categories), time() + (86400 * 30), "/");
        }
    
        // ---------------------------------------------------------
        // 4. PROCESS THE TRANSLATOR COOKIE
        // ---------------------------------------------------------
        if ($clicked_translator) {
            // Read existing, or start fresh
            $saved_translators = isset($_COOKIE['user_translators']) ? json_decode($_COOKIE['user_translators'], true) : [];
            if (!is_array($saved_translators)) $saved_translators = []; // Safety fallback
            
            // Add new, remove duplicates, keep top 3
            array_unshift($saved_translators, $clicked_translator);
            $saved_translators = array_unique($saved_translators);
            $saved_translators = array_slice($saved_translators, 0, 3);
            
            // Save the cookie for 30 days site-wide
            setcookie('user_translators', json_encode($saved_translators), time() + (86400 * 30), "/");
        }
    
    }
    
    // Get Explore more movies
    // 1. READ COOKIES AND BUILD DYNAMIC CONDITIONS
    $preferred_categories = isset($_COOKIE['user_categories']) ? json_decode($_COOKIE['user_categories'], true) : [];
    $preferred_translators = isset($_COOKIE['user_translators']) ? json_decode($_COOKIE['user_translators'], true) : [];
    
    $category_sql_list = "";
    $translator_sql_list = "";
    $dynamic_condition = "";
    
    // Safely escape the cookie data
    if (!empty($preferred_categories)) {
        $escaped_categories = array_map(function($cat) use ($connect) {
            return "'" . mysqli_real_escape_string($connect, $cat) . "'";
        }, $preferred_categories);
        $category_sql_list = implode(',', $escaped_categories);
    }
    
    if (!empty($preferred_translators)) {
        $escaped_translators = array_map(function($trans) use ($connect) {
            return "'" . mysqli_real_escape_string($connect, $trans) . "'";
        }, $preferred_translators);
        $translator_sql_list = implode(',', $escaped_translators);
    }
    
    // Build the AND (...) string to append to your WHERE clause
    if ($category_sql_list !== "" && $translator_sql_list !== "") {
        $dynamic_condition = " AND (category IN ($category_sql_list) OR translator IN ($translator_sql_list))";
    } elseif ($category_sql_list !== "") {
        $dynamic_condition = " AND category IN ($category_sql_list)";
    } elseif ($translator_sql_list !== "") {
        $dynamic_condition = " AND translator IN ($translator_sql_list)";
    }
    
    // 2. FETCH THE PERSONALIZED POOL (using your prepared statement structure)
    $more_expo_movies = [];
    $fetched_movie_ids = [];
    
    // Notice we pull LIMIT 40 here to give us a good pool to shuffle
    $select_more_expo_movies = "SELECT `id`, `name`, `category`, `duration`, `release_year`, `translator`, `image`, `token`, `ratings`
                                FROM `movies` 
                                WHERE Is_Part = ? $dynamic_condition 
                                ORDER BY id DESC LIMIT 40";
    
    $stmt_expo_movies = mysqli_prepare($connect, $select_more_expo_movies);
    
    if($stmt_expo_movies){
        mysqli_stmt_bind_param($stmt_expo_movies, "s", $ispart);
        mysqli_stmt_execute($stmt_expo_movies);
        $result_expo_movies = mysqli_stmt_get_result($stmt_expo_movies);
        
        while($row_expo_movies = mysqli_fetch_assoc($result_expo_movies)){
            $more_expo_movies[] = $row_expo_movies;
            $fetched_movie_ids[] = $row_expo_movies['id']; // Save ID to prevent duplicates later
        }
        mysqli_stmt_close($stmt_expo_movies);
    }
    
    // 3. SHUFFLE AND KEEP 12
    shuffle($more_expo_movies);
    $more_expo_movies = array_slice($more_expo_movies, 0, 12);
    
    // 4. THE FALLBACK: Fill missing spots if the user's cookies didn't find enough movies
    $movie_count = count($more_expo_movies);
    
    if ($movie_count < 12) {
        $movies_needed = 12 - $movie_count;
        
        // Exclude the ones we already found so we don't show duplicates
        $exclude_sql = !empty($fetched_movie_ids) ? " AND id NOT IN (" . implode(',', $fetched_movie_ids) . ")" : "";
        
        $fill_query = "SELECT `id`, `name`, `category`, `duration`, `release_year`, `translator`, `image`, `token`, `ratings` 
                       FROM `movies` 
                       WHERE Is_Part = ? $exclude_sql 
                       ORDER BY id DESC LIMIT ?"; // Using ? for the limit as well
                       
        $stmt_fill = mysqli_prepare($connect, $fill_query);
        
        if($stmt_fill){
            // Bind "s" for $ispart (string) and "i" for $movies_needed (integer)
            mysqli_stmt_bind_param($stmt_fill, "si", $ispart, $movies_needed);
            mysqli_stmt_execute($stmt_fill);
            $fill_res = mysqli_stmt_get_result($stmt_fill);
            
            while($row_fill = mysqli_fetch_assoc($fill_res)){
                $more_expo_movies[] = $row_fill; 
            }
            mysqli_stmt_close($stmt_fill);
        }
    }
    
    
    


    // $videoUrl = htmlspecialchars($movie['link']);

    // IF CONDITION TO CHECK IF LINK IS EMPTY OR NOT START WITH https://
    // $videoUrl = htmlspecialchars($movie['link']);
    
    $rawLink = $movie['link'];

    if (empty($rawLink) || strpos($rawLink, 'https://') !== 0) {
         $videoUrl = "https://drive.google.com/file/d/1zHP5kWvunILnVS5TZFps5XdU00f7cST8/preview";
     } else {
         $videoUrl = htmlspecialchars($rawLink);
     }
    
    // 1. Define Helper Functions first
    // FUNCTION TO GET CLEAN EMBED LINK
    function getDrivePreviewLink($url) {
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $fileId = $matches[1];
            return "https://drive.google.com/file/d/" . $fileId . "/preview";
        }
        return false; 
    }
    
    // FUNCTION TO GET GOOGLE DOWNLOAD LINK
    function getDriveDownloadLink($url) {
        $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
        if (preg_match($pattern, $url, $matches)) {
            $fileId = $matches[1];
            return $fileId;
        }
        return false;
    }
    
    // 2. Main Logic
    // Check if the link starts with https://drive.google.com
    if (strpos($videoUrl, 'https://drive.google.com') === 0) {
        $embedUrl = getDrivePreviewLink($videoUrl);
        $downloadLink = getDriveDownloadLink($videoUrl);
    
    } else {
        $embedUrl = $videoUrl;
        $DownLink = isset($movie['download']) ? $movie['download'] : ''; 
        $downloadLink = getDriveDownloadLink($DownLink);
    }

    // --- PREPARE DATA FOR REACT ---
    $reactData = [
        'movie' => $movie,
        'parts' => $movie_part,
        'similar' => $similar_movies,
        'explore' => $more_expo_movies,
        'embedUrl' => $embedUrl,
        'downloadLink' => $downloadLink
    ];
    
    
    // --- GLOBAL R2 CONFIGURATION ---
    $r2Domain = "https://media.theonemovies.com/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?php echo htmlspecialchars($movie['name']); ?> By <?php echo htmlspecialchars($movie['translator']); ?></title>
    <meta name="description" content="Stream <?php echo htmlspecialchars($movie['name']); ?> in HD on The One Movies. Enjoy this Agasobanuye movie translated by <?php echo htmlspecialchars($movie['translator']); ?> with fast loading and a free download option.">
    <meta name="author" content="The One Movies">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://theonemovies.com/play-movie/<?php echo urlencode($movie['token']);?>/<?php echo urlencode($movie['category']);?>/<?php echo urlencode(str_replace(' ', '-', $movie['name']));?>">
    
    <meta property="og:type" content="video.movie">
    <meta property="og:site_name" content="The One Movies">
    <meta property="og:title" content="Watch Agasobanuye <?php echo htmlspecialchars($movie['name']); ?>">
    <meta property="og:description" content="Stream <?php echo htmlspecialchars($movie['name']); ?> now in HD. Translated by <?php echo htmlspecialchars($movie['translator']); ?>. Free download available.">
    <meta property="og:image" content="<?php echo !empty($movie['image']) ? (strpos($movie['image'], 'http') === 0 ? htmlspecialchars($movie['image']) : $r2Domain . htmlspecialchars($movie['image'])) : $r2Domain . 'default-image.jpg'; ?>">
    <meta property="og:url" content="https://theonemovies.com/play-movie/<?php echo urlencode($movie['token']);?>/<?php echo urlencode($movie['category']);?>/<?php echo urlencode(str_replace(' ', '-', $movie['name']));?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@theonemovies">
    <meta name="twitter:title" content="Watch <?php echo htmlspecialchars($movie['name']); ?>">
    <meta name="twitter:description" content="Stream this Agasobanuye movie now in HD. Fast loading & free download.">
    <meta name="twitter:image" content="<?php echo !empty($movie['image']) ? (strpos($movie['image'], 'http') === 0 ? htmlspecialchars($movie['image']) : $r2Domain . htmlspecialchars($movie['image'])) : $r2Domain . 'default-image.jpg'; ?>">

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
    
    <style>#static-preloader{position:fixed;top:0;left:0;width:100%;height:100%;background-color:#000;z-index:99999;display:flex;flex-direction:column;justify-content:center;align-items:center;transition:opacity .5s ease-out,visibility .5s}.static-spinner{width:50px;height:50px;border:3px solid rgba(255,255,255,.1);border-radius:50%;border-top-color:#e50914;animation:static-spin .8s linear infinite;margin-bottom:15px}.static-text{color:#e50914;font-family:sans-serif;font-size:14px;font-weight:600;letter-spacing:1px;text-transform:uppercase;display:flex}.static-text::after{content:'';animation:loading-dots 1.5s infinite;width:20px;text-align:left;display:inline-block}@keyframes static-spin{to{transform:rotate(360deg)}}@keyframes loading-dots{0%{content:''}25%{content:'.'}50%{content:'..'}75%{content:'...'}100%{content:''}}.media-poster{position:relative;background-color:#1a1a1a;aspect-ratio:2/3;overflow:hidden}.poster-loader,.poster-loader:before,.poster-loader:after{border-radius:50%;width:10px;height:10px;animation-fill-mode:both;animation:bounceloader 1.8s infinite ease-in-out}.poster-loader{color:#e50914;font-size:7px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) translateZ(0);text-indent:-9999em;animation-delay:-.16s;z-index:0}.poster-loader:before,.poster-loader:after{content:'';position:absolute;top:0}.poster-loader:before{left:-1.5em;animation-delay:-.32s}.poster-loader:after{left:1.5em}@keyframes bounceloader{0%,80%,100%{box-shadow:0 10px 0 -10px}40%{box-shadow:0 10px 0 0}}.poster-img{position:relative;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .5s ease-in-out;z-index:1;color:transparent;text-indent:-9999px}.media-type,.media-info,.media-info-conver{position:absolute;z-index:2}</style>
    
    <script> window.serverData = "<?php echo base64_encode(json_encode($reactData)); ?>"; </script>
    <script defer src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script defer src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="{{JS_MAIN}}"></script>
    <script src="{{JS_POPUP}}"></script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XK2S3QJH0W"></script>
    <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'G-XK2S3QJH0W');</script>
</head>
<body>
    <div id="static-preloader"><div class="static-spinner"></div><div class="static-text">Loading</div></div>
    <div id="works-dont-touch"></div>
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
    
}else{
    header('Location: ../../../movies');
    exit();
}?>