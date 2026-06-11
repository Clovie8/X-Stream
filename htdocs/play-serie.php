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
$appJSToken = generateAssetToken('=hSE44tksns=', 300);


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
if(isset($_GET['playepisodekey']) && isset($_GET['withpecat']) &&
    !empty($_GET['playepisodekey']) && !empty($_GET['withpecat'])){
    
    // Sanitize inputs
    // $moviekey = filter_var($_GET['playepisodekey'], FILTER_SANITIZE_STRING);
    // $category = filter_var($_GET['withpecat'], FILTER_SANITIZE_STRING);
    
    $moviekey = htmlspecialchars($_GET['playepisodekey']);
    $category = htmlspecialchars($_GET['withpecat']);
    
    // Prepare and execute query securely
    $select_movie = "SELECT 
    `id`, `serie_id`, `serie_token`, `name`, `title`, `description`, `category`, `duration`, `link`, `download`, `token`
    FROM `episodes` WHERE token = ? AND category = ? LIMIT 1";
    $stmt = mysqli_prepare($connect, $select_movie);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $moviekey, $category);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $movie = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // If movie not found, redirect
        if(!$movie){
            header('Location: ../../../series');
            exit();
        }
    } else {
        // Handle database error
        error_log("Database error: " . mysqli_error($connect));
        header('Location: ../../../series');
        exit();
    }
    
   
    //=== VIEWS COUNTING: EPISODES ====
    // Note: Assuming $movie['id'] holds the episode ID as per your snippet
    $episodeid = (int)$movie['id']; 
    $cookieName = "viewed_episode_" . $episodeid;
    

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
                                        AND type = 'episode' 
                                        AND viewed_at > (? - INTERVAL 1 MINUTE)");
        $checkStmt->execute([$episodeid, $user_ip, $created_at]);
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
                $updateStmt = $connect->prepare("UPDATE episodes SET views = views + 1 WHERE id = ?");
                $updateStmt->execute([$episodeid]);
    
                // 4. INSERT HISTORY (New Columns + 'episode' type)
                $historySql = "INSERT INTO view_analytics 
                               (video_id, viewed_at, user_ip, type, os, device, browser, country, city) 
                               VALUES (?, ?, ?, 'episode', ?, ?, ?, ?, ?)";
                $historyStmt = $connect->prepare($historySql);
                $historyStmt->execute([$episodeid, $created_at, $user_ip, $os, $device, $browser, $country, $city]);
    
                // 5. SET COOKIE
                setcookie($cookieName, "1", time() + 86400, "/"); 
            }
        }
    }
    
    

    // Geting series id and token
    $serie_id     = htmlspecialchars($movie['serie_id']);
    $serie_token = htmlspecialchars($movie['serie_token']);
    
    // Selecting some details from sereis
    $select_serie = "SELECT
    `description`, `release_year`, `translator`, `season`, `episodes`, `image`, `ratings`
    FROM `series` WHERE id = ? AND token = ? LIMIT 1";
    $stmt = mysqli_prepare($connect, $select_serie);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $serie_id, $serie_token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $serie = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    // Get similar movies (More Episodes)
    $similar_movies = [];
    $select_more = "SELECT  
    `name`, `category`, `duration`, `token`
    FROM `episodes` WHERE serie_id = ? AND token != ? ORDER BY id ASC";
    $stmt = mysqli_prepare($connect, $select_more);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $serie_id, $moviekey);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            // Logic to create the "S1 : Ep4" label logic in PHP for React
            $last_four = substr($row['name'], -4); 
            $numeric_value = preg_replace('/[^0-9]/', '', $last_four); 
            $row['episode_label'] = "S" . htmlspecialchars($serie['season']) . " : Ep" . htmlspecialchars($numeric_value);
            $similar_movies[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    
    

    
    
    
    // SET DAYNMIC CONTENT COOKIE 
    if ($movie) {
        // 2. Safely grab the category and translator
        $clicked_category = !empty($row['category']) ? $row['category'] : null;
        $clicked_translator = !empty($row['translator']) ? $row['translator'] : null;
        
        // ---------------------------------------------------------
        // 3. PROCESS THE SERIES CATEGORY COOKIE
        // ---------------------------------------------------------
        if ($clicked_category) {
            // Look specifically for the SERIES cookie
            $saved_categories = isset($_COOKIE['series_categories']) ? json_decode($_COOKIE['series_categories'], true) : [];
            if (!is_array($saved_categories)) $saved_categories = []; 
            
            array_unshift($saved_categories, $clicked_category);
            $saved_categories = array_unique($saved_categories);
            $saved_categories = array_slice($saved_categories, 0, 3);
            
            // Save as 'series_categories'
            setcookie('series_categories', json_encode($saved_categories), time() + (86400 * 30), "/");
        }
    
        // ---------------------------------------------------------
        // 4. PROCESS THE SERIES TRANSLATOR COOKIE
        // ---------------------------------------------------------
        if ($clicked_translator) {
            // Look specifically for the SERIES cookie
            $saved_translators = isset($_COOKIE['series_translators']) ? json_decode($_COOKIE['series_translators'], true) : [];
            if (!is_array($saved_translators)) $saved_translators = []; 
            
            array_unshift($saved_translators, $clicked_translator);
            $saved_translators = array_unique($saved_translators);
            $saved_translators = array_slice($saved_translators, 0, 3);
            
            // Save as 'series_translators'
            setcookie('series_translators', json_encode($saved_translators), time() + (86400 * 30), "/");
        }
    
    } 



    // Get Explore more series
    // 1. READ SERIES-SPECIFIC COOKIES AND BUILD CONDITIONS
    $pref_series_cats = isset($_COOKIE['series_categories']) ? json_decode($_COOKIE['series_categories'], true) : [];
    $pref_series_trans = isset($_COOKIE['series_translators']) ? json_decode($_COOKIE['series_translators'], true) : [];
    
    $s_category_list = "";
    $s_translator_list = "";
    $series_where_sql = "";
    
    // Safely escape the cookie data
    if (!empty($pref_series_cats)) {
        $escaped_cats = array_map(function($cat) use ($connect) {
            return "'" . mysqli_real_escape_string($connect, $cat) . "'";
        }, $pref_series_cats);
        $s_category_list = implode(',', $escaped_cats);
    }
    
    if (!empty($pref_series_trans)) {
        $escaped_trans = array_map(function($trans) use ($connect) {
            return "'" . mysqli_real_escape_string($connect, $trans) . "'";
        }, $pref_series_trans);
        $s_translator_list = implode(',', $escaped_trans);
    }
    
    // Build the dynamic WHERE clause
    if ($s_category_list !== "" && $s_translator_list !== "") {
        $series_where_sql = "WHERE category IN ($s_category_list) OR translator IN ($s_translator_list)";
    } elseif ($s_category_list !== "") {
        $series_where_sql = "WHERE category IN ($s_category_list)";
    } elseif ($s_translator_list !== "") {
        $series_where_sql = "WHERE translator IN ($s_translator_list)";
    }
    
    // 2. FETCH THE PERSONALIZED POOL
    $more_series = [];
    $fetched_series_ids = [];
    
    // Notice we added `id` to the SELECT and increased LIMIT to 40 for a good shuffle pool
    $select_more_series = "SELECT `id`, `name`, `category`, `release_year`, `translator`, `season`, `episodes`, `image`, `token`, `ratings` 
                           FROM `series` 
                           $series_where_sql 
                           ORDER BY id DESC LIMIT 40";
    
    $stmt_series = mysqli_prepare($connect, $select_more_series);
    
    if($stmt_series){
        mysqli_stmt_execute($stmt_series);
        $result_series = mysqli_stmt_get_result($stmt_series);
        
        while($row_series = mysqli_fetch_assoc($result_series)){
            $more_series[] = $row_series;
            $fetched_series_ids[] = $row_series['id']; // Save ID to prevent duplicates
        }
        mysqli_stmt_close($stmt_series);
    }
    
    // 3. SHUFFLE AND KEEP 12
    shuffle($more_series);
    $more_series = array_slice($more_series, 0, 12);
    
    // 4. THE FALLBACK: Fill missing spots if the user's cookies didn't find enough series
    $series_count = count($more_series);
    
    if ($series_count < 12) {
        $series_needed = 12 - $series_count;
        
        // Exclude the ones we already found. 
        // If $fetched_series_ids has data, we use WHERE. Otherwise, no WHERE clause is needed.
        $exclude_sql = !empty($fetched_series_ids) ? "WHERE id NOT IN (" . implode(',', $fetched_series_ids) . ")" : "";
        
        $fill_query_series = "SELECT `id`, `name`, `category`, `release_year`, `translator`, `season`, `episodes`, `image`, `token`, `ratings` 
                              FROM `series` 
                              $exclude_sql 
                              ORDER BY id DESC LIMIT ?"; 
                              
        $stmt_fill_series = mysqli_prepare($connect, $fill_query_series);
        
        if($stmt_fill_series){
            // Bind "i" for $series_needed (integer)
            mysqli_stmt_bind_param($stmt_fill_series, "i", $series_needed);
            mysqli_stmt_execute($stmt_fill_series);
            $fill_res_series = mysqli_stmt_get_result($stmt_fill_series);
            
            while($row_fill_series = mysqli_fetch_assoc($fill_res_series)){
                $more_series[] = $row_fill_series; 
            }
            mysqli_stmt_close($stmt_fill_series);
        }
    }

    
    
    
    

    
    
    // IF CONDITION TO CHECK IF LINK IS EMPTY OR NOT START WITH https://
    //$videoUrl = htmlspecialchars($movie['link']);
    
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

    // Prepare React Data
    $reactData = [
        'current_token' => $moviekey,
        'movie' => $movie,
        'serie' => $serie,
        'embedUrl' => $embedUrl,
        'downloadLink' => $downloadLink,
        'similar_episodes' => $similar_movies, // Contains the new 'episode_label'
        'more_series' => $more_series
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
    
    <title><?php echo htmlspecialchars($movie['name']); ?> By <?php echo htmlspecialchars($serie['translator']); ?></title>
    <meta name="description" content="Watch the latest Agasobanuye <?php echo htmlspecialchars($movie['name']); ?> in HD. Stream this Agasobanuye episode instantly with smooth playback and free downloads on The One Movies.">
    <meta name="author" content="The One Movies">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://theonemovies.com/play-serie/<?php echo urlencode($movie['token']);?>/<?php echo urlencode($movie['category']);?>/<?php echo urlencode(str_replace(' ', '-', $movie['name']));?>">
    
    <meta property="og:type" content="video.episode">
    <meta property="og:site_name" content="The One Movies">
    <meta property="og:title" content="<?php echo htmlspecialchars($movie['name']); ?> (Agasobanuye)">
    <meta property="og:description" content="Watch Season <?php echo htmlspecialchars($serie['season']); ?> Episode translated by <?php echo htmlspecialchars($serie['translator']); ?>. Stream instantly on The One Movies.">
    <meta property="og:image" content="<?php echo !empty($serie['image']) ? (strpos($serie['image'], 'http') === 0 ? htmlspecialchars($serie['image']) : $r2Domain . htmlspecialchars($serie['image'])) : $r2Domain . 'default-image.jpg'; ?>">
    <meta property="og:url" content="https://theonemovies.com/play-serie/<?php echo urlencode($movie['token']);?>/<?php echo urlencode($movie['category']);?>/<?php echo urlencode(str_replace(' ', '-', $movie['name']));?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@theonemovies">
    <meta name="twitter:title" content="Watch <?php echo htmlspecialchars($movie['name']); ?>">
    <meta name="twitter:description" content="New Episode out now! Translated by <?php echo htmlspecialchars($serie['translator']); ?>. Watch for free.">
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
    <div id="ignore-this-div"></div>
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
    
} else {
    header('Location: ../../../series');
    exit();
} ?>