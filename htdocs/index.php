<?php 
session_start(); 

$_SESSION['authenticated'] = true; 
$mainToken = generateAssetToken('bootstrap', 300);
$preloadToken = generateAssetToken('=bWFpbmNzcw=', 300);
$mainJSToken = generateAssetToken('=bG9hZGpz=', 300);
$popupJSToken = generateAssetToken('=BwFpbmpz=', 300);
$NoteJSToken = generateAssetToken('=cG8wdXBqcw=', 300);
$appJSToken = generateAssetToken('=hdjJDuew9d=', 300);
$homeJSToken = generateAssetToken('QYuiMsdfgHkb', 300);

$dynamicAssets = [
    '{{CSS_MAIN}}'    => $mainToken['url'],
    '{{CSS_PRELOAD}}' => $preloadToken['url'],
    '{{JS_POPUP}}'    => $popupJSToken['url'],
    '{{JS_MAIN}}'     => $mainJSToken['url'],
    '{{JS_NOTE}}'     => $NoteJSToken['url'],
    '{{JS_APP}}'      => $appJSToken['url'], 
    '{{JS_HOME}}'     => $homeJSToken['url']
];

require_once 'cache.php';
$cache = new Cache(86400);
$cacheKey = md5($_SERVER['REQUEST_URI']);
$cachedContent = $cache->get($cacheKey);

if ($cachedContent) {
    $output = str_replace(array_keys($dynamicAssets), array_values($dynamicAssets), $cachedContent);
    echo $output;
    exit;
}

ob_start();

include 'assets/sbW92aWVzrbW92aWVzc/connection.php';
// Define Base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', '/cinehub/'); 

function timeAgo($datetime){
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if($diff->y > 0) return $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
    if($diff->m > 0) return $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
    if($diff->d > 0) return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    if($diff->h > 0) return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    if($diff->i > 0) return $diff->i . " min" . ($diff->i > 1 ? "s" : "") . " ago";
    return "just now";  
}

function formatMovieDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours == 0) return $mins . 'min';
    elseif ($mins == 0) return $hours . 'h';
    else return $hours . 'h ' . $mins . 'm';
}

// ----------------------------------------------------
// 1. DATA FETCHING (Moved to Top for React & SEO)
// ----------------------------------------------------

// Hero Slider Data 
$hero_mixed = [];
$query = "
    (SELECT 
        id, 
        name, 
        description, 
        category, 
        release_year, 
        translator, 
        image, 
        token, 
        ratings, 
        backdrop,
        duration,       -- Only in Movies
        NULL AS season, -- Not in Movies (set to NULL)
        NULL AS episodes, -- Not in Movies (set to NULL)
        'movie' AS type,  -- Identifier
        created_at        -- Assuming you have a timestamp column
    FROM movies 
    WHERE Is_Part = 'No')

    UNION ALL

    (SELECT 
        id, 
        name, 
        description, 
        category, 
        release_year, 
        translator, 
        image, 
        token, 
        ratings, 
        backdrop,
        NULL AS duration, -- Not in Series (set to NULL)
        season,           -- Only in Series
        episodes,         -- Only in Series
        'series' AS type, -- Identifier
        created_at        -- Assuming you have a timestamp column
    FROM series)

    ORDER BY created_at DESC  -- Sort the WHOLE combined list by time
    LIMIT 6                   -- Get the top 6 mixed items
";

$result = mysqli_query($connect, $query);
while($row = mysqli_fetch_assoc($result)) {
    $hero_mixed[] = $row;
}


// Recent Movies
$get_recent_movies = [];
$stmt = mysqli_prepare($connect, "SELECT `name`, `category`, `release_year`, `duration`, `translator`, `image`, `token`, `ratings` FROM `movies` WHERE Is_Part = ? ORDER BY created_at DESC LIMIT 12");
$no = "No"; mysqli_stmt_bind_param($stmt, "s", $no); mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while($row = mysqli_fetch_assoc($res)) $get_recent_movies[] = $row;

// Recent Series
$get_recent_serie = [];
$res = mysqli_query($connect, "SELECT `name`, `category`, `season`, `release_year`, `translator`, `episodes`, `image`, `token`, `ratings` FROM `series` ORDER BY created_at DESC LIMIT 12");
while($row = mysqli_fetch_assoc($res)) $get_recent_serie[] = $row;

// Popular Series
$get_pop_serie = [];
$res = mysqli_query($connect, "SELECT `name`, `category`, `season`, `release_year`, `translator`, `episodes`, `image`, `token`, `ratings` FROM `series` ORDER BY ratings DESC LIMIT 12");
while($row = mysqli_fetch_assoc($res)) $get_pop_serie[] = $row;

// Popular Movies
$get_pop_movies = [];
$stmt = mysqli_prepare($connect, "SELECT `name`, `category`, `release_year`, `duration`, `translator`, `image`, `token`, `ratings` FROM `movies` WHERE Is_Part = ? ORDER BY ratings DESC LIMIT 12");
mysqli_stmt_bind_param($stmt, "s", $no); mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while($row = mysqli_fetch_assoc($res)) $get_pop_movies[] = $row;





// ==========================================
// 1A. READ MOVIE COOKIES & BUILD CONDITIONS
// ==========================================
$pref_movie_cats = isset($_COOKIE['user_categories']) ? json_decode($_COOKIE['user_categories'], true) : [];
$pref_movie_trans = isset($_COOKIE['user_translators']) ? json_decode($_COOKIE['user_translators'], true) : [];

$m_category_list = "";
$m_translator_list = "";
$movie_dynamic_condition = "";

if (!empty($pref_movie_cats)) {
    $escaped_m_cats = array_map(function($cat) use ($connect) {
        return "'" . mysqli_real_escape_string($connect, $cat) . "'";
    }, $pref_movie_cats);
    $m_category_list = implode(',', $escaped_m_cats);
}

if (!empty($pref_movie_trans)) {
    $escaped_m_trans = array_map(function($trans) use ($connect) {
        return "'" . mysqli_real_escape_string($connect, $trans) . "'";
    }, $pref_movie_trans);
    $m_translator_list = implode(',', $escaped_m_trans);
}

if ($m_category_list !== "" && $m_translator_list !== "") {
    $movie_dynamic_condition = "(category IN ($m_category_list) OR translator IN ($m_translator_list))";
} elseif ($m_category_list !== "") {
    $movie_dynamic_condition = "category IN ($m_category_list)";
} elseif ($m_translator_list !== "") {
    $movie_dynamic_condition = "translator IN ($m_translator_list)";
}


// ==========================================
// 1B. READ SERIES COOKIES & BUILD CONDITIONS
// ==========================================
$pref_series_cats = isset($_COOKIE['series_categories']) ? json_decode($_COOKIE['series_categories'], true) : [];
$pref_series_trans = isset($_COOKIE['series_translators']) ? json_decode($_COOKIE['series_translators'], true) : [];

$s_category_list = "";
$s_translator_list = "";
$series_dynamic_condition = "";

if (!empty($pref_series_cats)) {
    $escaped_s_cats = array_map(function($cat) use ($connect) {
        return "'" . mysqli_real_escape_string($connect, $cat) . "'";
    }, $pref_series_cats);
    $s_category_list = implode(',', $escaped_s_cats);
}

if (!empty($pref_series_trans)) {
    $escaped_s_trans = array_map(function($trans) use ($connect) {
        return "'" . mysqli_real_escape_string($connect, $trans) . "'";
    }, $pref_series_trans);
    $s_translator_list = implode(',', $escaped_s_trans);
}

if ($s_category_list !== "" && $s_translator_list !== "") {
    $series_dynamic_condition = "(category IN ($s_category_list) OR translator IN ($s_translator_list))";
} elseif ($s_category_list !== "") {
    $series_dynamic_condition = "category IN ($s_category_list)";
} elseif ($s_translator_list !== "") {
    $series_dynamic_condition = "translator IN ($s_translator_list)";
}


// ==========================================
// 2. EXPLORE DATA: MOVIES
// ==========================================
$get_expor_movies = [];
$fetched_movie_ids = [];

// Apply base condition for movies, plus dynamic condition if it exists
$movie_where = "Is_Part = 'No'";
if (!empty($movie_dynamic_condition)) {
    $movie_where .= " AND $movie_dynamic_condition";
}

$query_movies = "SELECT `id`, `name`, `category`, `release_year`, `duration`, `translator`, `image`, `token`, `ratings` 
                 FROM `movies` 
                 WHERE $movie_where 
                 ORDER BY release_year DESC, id DESC LIMIT 100";

$res_movies = mysqli_query($connect, $query_movies);

// Safety Check: Only fetch if the query was successful
if ($res_movies) {
    while($row = mysqli_fetch_assoc($res_movies)) {
        $get_expor_movies[] = $row;
        $fetched_movie_ids[] = $row['id'];
    }
}

// Shuffle and slice to get random variety but limit to exactly 48
shuffle($get_expor_movies);
$get_expor_movies = array_slice($get_expor_movies, 0, 48);

// MOVIE FALLBACK: Fill to 48 if the dynamic condition restricted the results too much
$movie_count = count($get_expor_movies);
if ($movie_count < 48) {
    $movies_needed = 48 - $movie_count;
    // Ensure we don't fetch duplicates
    $exclude_sql = !empty($fetched_movie_ids) ? " AND id NOT IN (" . implode(',', $fetched_movie_ids) . ")" : "";
    
    $fill_query = "SELECT `id`, `name`, `category`, `release_year`, `duration`, `translator`, `image`, `token`, `ratings` 
                   FROM `movies` 
                   WHERE Is_Part = 'No' $exclude_sql 
                   ORDER BY release_year DESC, id DESC LIMIT $movies_needed";
                   
    $fill_res = mysqli_query($connect, $fill_query);
    if ($fill_res) {
        while($row = mysqli_fetch_assoc($fill_res)) {
            $get_expor_movies[] = $row; 
        }
    }
}


// ==========================================
// 3. EXPLORE DATA: SERIES
// ==========================================
$get_expor_series = [];
$fetched_series_ids = [];

// Apply dynamic condition for series if it exists
$series_where = !empty($series_dynamic_condition) ? "WHERE $series_dynamic_condition" : "";

$query_series = "SELECT `id`, `name`, `category`, `season`, `release_year`, `translator`, `episodes`, `image`, `token`, `ratings` 
                 FROM `series` 
                 $series_where 
                 ORDER BY release_year DESC, id DESC LIMIT 100";

$res_series = mysqli_query($connect, $query_series);
if ($res_series) {
    while($row = mysqli_fetch_assoc($res_series)) {
        $get_expor_series[] = $row;
        $fetched_series_ids[] = $row['id'];
    }
}

// Shuffle and slice to get random variety but limit to exactly 48
shuffle($get_expor_series);
$get_expor_series = array_slice($get_expor_series, 0, 48);

// SERIES FALLBACK: Fill to 48 if needed
$series_count = count($get_expor_series);
if ($series_count < 48) {
    $series_needed = 48 - $series_count;
    // Ensure we don't fetch duplicates
    $exclude_series_sql = !empty($fetched_series_ids) ? "WHERE id NOT IN (" . implode(',', $fetched_series_ids) . ")" : "";
    
    $fill_series_query = "SELECT `id`, `name`, `category`, `season`, `release_year`, `translator`, `episodes`, `image`, `token`, `ratings` 
                          FROM `series` 
                          $exclude_series_sql 
                          ORDER BY release_year DESC, id DESC LIMIT $series_needed";
                          
    $fill_series_res = mysqli_query($connect, $fill_series_query);
    if ($fill_series_res) {
        while($row = mysqli_fetch_assoc($fill_series_res)) {
            $get_expor_series[] = $row; 
        }
    }
}

// ==========================================
// 4. PREPARE FOR REACT
// ==========================================
// Encode safely so it can be injected directly into your React component script block
$react_movies_json = json_encode($get_expor_movies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$react_series_json = json_encode($get_expor_series, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);






// Footer Image
$footer_img = !empty($hero_mixed[0]['image']) ? $hero_mixed[0]['image'] : 'default-image.jpg';

// PREPARE REACT DATA
$reactData = [
    'hero' => $hero_mixed,
    'recentMovies' => $get_recent_movies,
    'recentSeries' => $get_recent_serie,
    'popularMovies' => $get_pop_movies,
    'popularSeries' => $get_pop_serie,
    'exploreMovies' => $get_expor_movies,
    'exploreSeries' => $get_expor_series,
    'footerImage' => $footer_img
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>The One Movies - Watch Free Agasobanuye Movies & Series</title>
    <meta name="description" content="Watch & download free Agasobanuye movies and TV series on The One Movies. Stream high-quality films translated by Rocky Kimomo, Junior Giti, and more.">
    <meta name="author" content="The One Movies">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://theonemovies.com/">
    
    <meta http-equiv="content-language" content="en, rw">
    <meta name="geo.region" content="RW">
    <meta name="geo.placename" content="Kigali">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="The One Movies">
    <meta property="og:title" content="The One Movies - Watch Free Agasobanuye Movies & Series">
    <meta property="og:description" content="Stream the latest Agasobanuye movies and series in HD. No subscription required. Fast and free.">
    <meta property="og:image" content="https://theonemovies.com/logo.png">
    <meta property="og:url" content="https://theonemovies.com/">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@theonemovies">
    <meta name="twitter:title" content="Watch Free Agasobanuye Movies & Series">
    <meta name="twitter:description" content="Stream the best translated movies (Agasobanuye) online for free.">
    <meta name="twitter:image" content="https://theonemovies.com/logo.png">

    <meta name="theme-color" content="#0a0a0a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="icon" href="https://theonemovies.com/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="https://theonemovies.com/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="https://theonemovies.com/assets/image/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://theonemovies.com/assets/image/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://theonemovies.com/assets/image/apple-touch-icon.png">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{CSS_MAIN}}">

    <style>#cinematic-preloader{position:fixed;top:0;left:0;width:100%;height:100%;background-color:#000;z-index:99999;display:flex;justify-content:center;align-items:center;transition:opacity .6s ease-out,visibility .6s}.progress-line-track{position:absolute;top:0;left:0;width:100%;height:4px;background:rgba(255,255,255,.05);overflow:hidden}.progress-line-bar{position:absolute;top:0;left:0;height:100%;width:0;background:#e50914;box-shadow:0 0 15px #e50914;animation:slide-top 2s cubic-bezier(.23,1,.32,1) forwards}.center-dot{width:15px;height:15px;background-color:#e50914;border-radius:50%;position:relative;animation:nuclear-pulse 1.2s infinite ease-in-out}.center-dot::after{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:100%;height:100%;border-radius:50%;border:2px solid #e50914;animation:ripple 1.2s infinite ease-out}@keyframes slide-top{0%{width:0}50%{width:60%}100%{width:100%}}@keyframes nuclear-pulse{0%{transform:scale(1);box-shadow:0 0 0 0 rgba(229,9,20,.7)}50%{transform:scale(1.5);box-shadow:0 0 20px 10px rgba(229,9,20,0);opacity:1}100%{transform:scale(1);box-shadow:0 0 0 0 rgba(229,9,20,0)}}@keyframes ripple{0%{width:100%;height:100%;opacity:1;border-width:2px}100%{width:400%;height:400%;opacity:0;border-width:0}}.media-poster{position:relative;background-color:#1a1a1a;aspect-ratio:2/3;overflow:hidden}.poster-loader,.poster-loader:before,.poster-loader:after{border-radius:50%;width:10px;height:10px;animation-fill-mode:both;animation:bounceloader 1.8s infinite ease-in-out}.poster-loader{color:#e50914;font-size:7px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) translateZ(0);text-indent:-9999em;animation-delay:-.16s;z-index:0}.poster-loader:before,.poster-loader:after{content:'';position:absolute;top:0}.poster-loader:before{left:-1.5em;animation-delay:-.32s}.poster-loader:after{left:1.5em}@keyframes bounceloader{0%,80%,100%{box-shadow:0 10px 0 -10px}40%{box-shadow:0 10px 0 0}}.poster-img{position:relative;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .5s ease-in-out;z-index:1;color:transparent;text-indent:-9999px}.media-type,.media-info,.media-info-conver,.glare{position:absolute;z-index:2}</style>

    <script> window.serverData = "<?php echo base64_encode(json_encode($reactData)); ?>"; </script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="{{JS_POPUP}}"></script>
    <script src="{{JS_APP}}"></script>
    <script src="{{JS_MAIN}}"></script>
    <script src="https://heavenlysuspicious.com/61/d3/71/61d3719ca5e74700c4f5ecb57e24c0d7.js"></script>
    <script src="https://analytics.ahrefs.com/analytics.js" data-key="FC4EHTR+AF8Tws+1ErsFyA" async></script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XK2S3QJH0W"></script>
    <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'G-XK2S3QJH0W');</script>
    <script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"WebSite","@id":"https://theonemovies.com/#website","url":"https://theonemovies.com/","name":"The One Movies","alternateName":"TheOneMovies","description":"Watch the best Agasobanuye movies & series online for free.","publisher":{"@id":"https://theonemovies.com/#organization"},"inLanguage":["en","rw"],"potentialAction":{"@type":"SearchAction","target":{"@type":"EntryPoint","urlTemplate":"https://theonemovies.com/movies?search={search_term_string}"},"query-input":"required name=search_term_string"}},{"@type":"Organization","@id":"https://theonemovies.com/#organization","name":"The One Movies","url":"https://theonemovies.com/","logo":{"@type":"ImageObject","@id":"https://theonemovies.com/#logo","url":"https://theonemovies.com/assets/image/logo-header.png","width":112,"height":112,"caption":"The One Movies Logo"},"image":{"@id":"https://theonemovies.com/#logo"},"sameAs":["https://www.facebook.com/profile.php?id=61582506213078","https://www.tiktok.com/@theonemovies11","https://www.instagram.com/_theonemovies.com_?igsh=MWs2d2lvcXdxa2lweA==","https://www.youtube.com/@TheOneMovies1","https://whatsapp.com/channel/0029VbBWSZu6rsQuR5Mrst33"],"contactPoint":{"@type":"ContactPoint","telephone":"+250-799-383-936","contactType":"Customer Service","areaServed":"RW","availableLanguage":["en","rw"]}},{"@type":"CollectionPage","@id":"https://theonemovies.com/#webpage","url":"https://theonemovies.com/","name":"The One Movies - Watch Free Agasobanuye Movies & Series","description":"Stream high-quality Agasobanuye movies & series online for free. Fast loading and free downloads.","isPartOf":{"@id":"https://theonemovies.com/#website"},"about":{"@id":"https://theonemovies.com/#organization"},"primaryImageOfPage":{"@id":"https://theonemovies.com/#logo"},"inLanguage":["en","rw"],"hasPart":[{"@type":"CollectionPage","name":"Agasobanuye Movies","url":"https://theonemovies.com/movies"},{"@type":"CollectionPage","name":"Agasobanuye TV Series","url":"https://theonemovies.com/series"}]}]}</script>
</head>
<body>
    <div id="cinematic-preloader"><div class="progress-line-track"><div class="progress-line-bar"></div></div><div class="center-dot"></div></div>
    <div id="vanilla-js-only"></div>
    <script defer src="{{JS_HOME}}"></script>
    <script src="{{JS_NOTE}}"></script>
</body>
</html>

<?php
function generateAssetToken($file = 'bootstrap', $expiry = 300) {
    if (!isset($_SESSION['asset_tokens'])) $_SESSION['asset_tokens'] = [];
    $token = bin2hex(random_bytes(32));
    $expiryTime = time() + $expiry;
    $_SESSION['asset_tokens'][$token] = $expiryTime;
    return ['token' => $token, 'expiry' => $expiryTime, 'url' => "3iNd9Ex2Qw?file=$file&token=$token"];
}

$content = ob_get_contents();
ob_end_clean();

if (strlen($content) > 100) $cache->save($cacheKey, $content);

$finalOutput = str_replace(array_keys($dynamicAssets), array_values($dynamicAssets), $content);
echo $finalOutput;
?>