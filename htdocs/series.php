<?php
session_start();

$current_uri = $_SERVER['REQUEST_URI'];
if (preg_match('#/series/$#', $current_uri)) {
    $new_uri = preg_replace('#/$#', '', $current_uri);
    header('Location: ' . $new_uri);
    exit();
}

$_SESSION['authenticated'] = true; 
$mainToken = generateAssetToken('bootstrap', 300);
$mainJSToken = generateAssetToken('=bG9hZGpz=', 300);
$popupJSToken = generateAssetToken('=BwFpbmpz=', 300);
$loadJSToken = generateAssetToken('=cG8wdXBqcw=', 300);
$appJSToken = generateAssetToken('=kjdfUIsj34=', 300);

$dynamicAssets = [
    '{{CSS_MAIN}}'    => $mainToken['url'],
    '{{JS_POPUP}}'    => $popupJSToken['url'],
    '{{JS_MAIN}}'     => $mainJSToken['url'],
    '{{JS_LOAD}}'     => $loadJSToken['url'],
    '{{JS_APP}}'      => $appJSToken['url']
];

require_once 'cache.php';
$cache = new Cache(600);
$cacheKey = md5($_SERVER['REQUEST_URI']);
$cachedContent = $cache->get($cacheKey);

if ($cachedContent) {
    $output = str_replace(array_keys($dynamicAssets), array_values($dynamicAssets), $cachedContent);
    echo $output;
    // echo "✅ Loaded from cache";
    exit;
}

ob_start();

require 'assets/sbW92aWVzrbW92aWVzc/connection.php';

// Dynamically determine the base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', '/');
define('ITEMS_PER_PAGE', 24); // Number of series per page

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if($diff->y > 0) return $diff->y . "year ago";
    if($diff->m > 0) return $diff->m . "month ago";
    if($diff->d > 0) return $diff->d . "day ago";
    if($diff->h > 0) return $diff->h . "hour ago";
    return "Just now";  
}

// 1. GET FILTER OPTIONS
$translators = [];
$t_res = $connect->query("SELECT DISTINCT translator FROM series WHERE translator IS NOT NULL AND translator != '' ORDER BY translator ASC");
while($row = $t_res->fetch_assoc()) $translators[] = $row['translator'];

$years = [];
$current_year = date('Y');
for ($y = $current_year; $y >= $current_year - 10; $y--) $years[] = $y;

// 2. GET SERIES (Filters)
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * ITEMS_PER_PAGE;

$cat = isset($_GET['category']) ? $_GET['category'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : null;
$trans = isset($_GET['translator']) ? $_GET['translator'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$where = [];
$params = [];
$types = '';

if ($cat) { $where[] = "category = ?"; $params[] = $cat; $types .= 's'; }
if ($year) { $where[] = "release_year = ?"; $params[] = $year; $types .= 's'; }
if ($trans) { $where[] = "translator = ?"; $params[] = $trans; $types .= 's'; }
if ($search) { $where[] = "(name LIKE ?)"; $params[] = "%$search%"; $types .= 's'; }

$order = 'release_year DESC, id DESC';
if($sort == 'oldest') $order = 'release_year ASC, id ASC';
if($sort == 'recent') $order = 'id DESC';
if($sort == 'name_asc') $order = 'name ASC';
if($sort == 'name_desc') $order = 'name DESC';
if($sort == 'rating') $order = 'ratings DESC';

$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM series";
if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY $order LIMIT ? OFFSET ?";
$params[] = ITEMS_PER_PAGE; $params[] = $offset; $types .= 'ii';

$stmt = $connect->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$series = [];
while($row = $res->fetch_assoc()) {
    $row['time_ago'] = timeAgo($row['created_at']);
    $series[] = $row;
}

$total_res = $connect->query("SELECT FOUND_ROWS()");
$total_series = $total_res->fetch_row()[0];
$total_pages = ceil($total_series / ITEMS_PER_PAGE);

// 3. PREPARE REACT DATA
$reactData = [
    'series' => $series,
    'pagination' => [
        'current' => $current_page,
        'total' => $total_pages,
        'total_items' => $total_series
    ],
    'filters' => [
        'translators' => $translators,
        'years' => $years
    ],
    'currentFilters' => [
        'category' => $cat,
        'year' => $year,
        'translator' => $trans,
        'search' => $search,
        'sort' => $sort
    ]
];

// --- NEW CODE STARTS HERE ---
// If the frontend explicitly asks for AJAX data, return JSON and stop.
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode($reactData);
    exit; // Stop script here so no HTML is rendered
}
// --- NEW CODE ENDS HERE ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>Agasobanuye TV Series & Shows | The One Movies</title>
    <meta name="description" content="Discover the best Agasobanuye TV series and shows on The One Movies. Stream full seasons and your favorite episodes in HD for free.">
    <meta name="author" content="The One Movies">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://theonemovies.com/series">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="The One Movies">
    <meta property="og:title" content="Browse Agasobanuye TV Series - The One Movies">
    <meta property="og:description" content="Watch Agasobanuye Series online. Full seasons, high quality.">
    <meta property="og:image" content="https://theonemovies.com/logo.png">
    <meta property="og:url" content="https://theonemovies.com/series">
    <meta name="twitter:card" content="summary_large_image">
    
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">

    <link rel="icon" href="https://theonemovies.com/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="https://theonemovies.com/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="https://theonemovies.com/assets/image/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://theonemovies.com/assets/image/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://theonemovies.com/assets/image/apple-touch-icon.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{CSS_MAIN}}">

    <style>#cinematic-preloader{position:fixed;top:0;left:0;width:100%;height:100%;background-color:#000;z-index:99999;display:flex;justify-content:center;align-items:center;transition:opacity .6s ease-out,visibility .6s}.progress-line-track{position:absolute;top:0;left:0;width:100%;height:4px;background:rgba(255,255,255,.05);overflow:hidden}.progress-line-bar{position:absolute;top:0;left:0;height:100%;width:0;background:#e50914;box-shadow:0 0 15px #e50914;animation:slide-top 2s cubic-bezier(.23,1,.32,1) forwards}.center-dot{width:15px;height:15px;background-color:#e50914;border-radius:50%;position:relative;animation:nuclear-pulse 1.2s infinite ease-in-out}.center-dot::after{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:100%;height:100%;border-radius:50%;border:2px solid #e50914;animation:ripple 1.2s infinite ease-out}@keyframes slide-top{0%{width:0}50%{width:60%}100%{width:100%}}@keyframes nuclear-pulse{0%{transform:scale(1);box-shadow:0 0 0 0 rgba(229,9,20,.7)}50%{transform:scale(1.5);box-shadow:0 0 20px 10px rgba(229,9,20,0);opacity:1}100%{transform:scale(1);box-shadow:0 0 0 0 rgba(229,9,20,0)}}@keyframes ripple{0%{width:100%;height:100%;opacity:1;border-width:2px}100%{width:400%;height:400%;opacity:0;border-width:0}}.media-poster{position:relative;background-color:#1a1a1a;aspect-ratio:2/3;overflow:hidden}.poster-loader,.poster-loader:before,.poster-loader:after{border-radius:50%;width:10px;height:10px;animation-fill-mode:both;animation:bounceloader 1.8s infinite ease-in-out}.poster-loader{color:#e50914;font-size:7px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) translateZ(0);text-indent:-9999em;animation-delay:-.16s;z-index:0}.poster-loader:before,.poster-loader:after{content:'';position:absolute;top:0}.poster-loader:before{left:-1.5em;animation-delay:-.32s}.poster-loader:after{left:1.5em}@keyframes bounceloader{0%,80%,100%{box-shadow:0 10px 0 -10px}40%{box-shadow:0 10px 0 0}}.poster-img{position:relative;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .5s ease-in-out;z-index:1;color:transparent;text-indent:-9999px}.media-type,.media-info,.media-info-conver{position:absolute;z-index:2}</style>

    <script> window.serverData = "<?php echo base64_encode(json_encode($reactData)); ?>"; </script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="{{JS_MAIN}}"></script>
    <script src="{{JS_POPUP}}"></script>
    <script src="https://heavenlysuspicious.com/61/d3/71/61d3719ca5e74700c4f5ecb57e24c0d7.js"></script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XK2S3QJH0W"></script>
    <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'G-XK2S3QJH0W'); </script>
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"CollectionPage","@id":"https://theonemovies.com/series/#webpage","url":"https://theonemovies.com/series","name":"Agasobanuye TV Series","description":"Discover the best Agasobanuye TV series and shows.","isPartOf":{"@type":"WebSite","@id":"https://theonemovies.com/#website"}}</script>
</head>
<body>
    <div id="cinematic-preloader"><div class="progress-line-track"><div class="progress-line-bar"></div></div><div class="center-dot"></div></div>
    <div id="it-just-works"></div>
    <script defer src="{{JS_APP}}"></script>
</body>
</html>

<?php
function generateAssetToken($file = 'bootstrap', $expiry = 300) {
    if (!isset($_SESSION['asset_tokens'])) $_SESSION['asset_tokens'] = [];
    $token = bin2hex(random_bytes(32));
    $expiryTime = time() + $expiry;
    $_SESSION['asset_tokens'][$token] = $expiryTime;
    return ['token' => $token, 'expiry' => $expiryTime, 'url' => "In9Dx27eXq?file=$file&token=$token"];
}

$content = ob_get_contents();
ob_end_clean();

if (strlen($content) > 100) $cache->save($cacheKey, $content);

$finalOutput = str_replace(array_keys($dynamicAssets), array_values($dynamicAssets), $content);
echo $finalOutput;
?>