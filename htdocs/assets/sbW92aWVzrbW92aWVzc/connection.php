<?php
// --- URL CLEANING ---
header_remove("X-Powered-By");

$requested_url = $_SERVER['REQUEST_URI'];
$url_path = parse_url($requested_url, PHP_URL_PATH);

$allowed_files = [
    '/assets/sbW92aWVzrbW92aWVzc/search.php',    
    '/assets/sbW92aWVzrbW92aWVzc/search-serie.php'  
];

if (str_ends_with($url_path, '.php') && !in_array($url_path, $allowed_files)) {
    
    $clean_path = substr($url_path, 0, -4);
    
    $query_string = parse_url($requested_url, PHP_URL_QUERY);
    $final_url = $clean_path . ($query_string ? '?' . $query_string : '');
    
    header("Location: " . $final_url, true, 301);
    exit();
}
// --- END OF CLEANING THE URL ---

    
// --- 1. DATABASE CREDENTIALS ---
$db_host = 'theonemovies-db.mysql.database.azure.com';  // host
$db_user = 'clovisadmin';  // user
$db_pass = 'Is@AzrTheOnedb';  // password
$db_name = 'real_theone_db';    // DB name

// --- 2. SAFE CONNECTION SETUP ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Attempt to connect
    $connect = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    // Fix special characters
    mysqli_set_charset($connect, "utf8mb4");

} catch (mysqli_sql_exception $e) {
    // --- 3. RESPONSIVE ERROR HANDLING ---
    
    // Set Timezone to Kigali (RW)
    date_default_timezone_set('Africa/Kigali');
    $currentTime = date("Y/m/d - H:i:s");

    die("
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { 
                background-color: #141414; 
                color: #ffffff; 
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
                margin: 0; 
                padding: 0;
                display: flex; 
                align-items: center; 
                justify-content: center; 
                height: 100vh; 
                text-align: center;
            }
            .container { 
                padding: 30px; 
                width: 90%; 
                max-width: 500px;
                box-sizing: border-box; 
            }
            h1 { 
                font-size: 32px; 
                font-weight: 500; 
                margin-bottom: 15px; 
            }
            p { 
                color: #cccccc; 
                font-size: 16px; 
                line-height: 1.5; 
                margin-bottom: 30px; 
            }
            .btn {
                background-color: #e50914;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
                font-size: 16px;
                display: inline-block;
                transition: background 0.2s;
            }
            .btn:hover { background-color: #b20710; }
            .footer { margin-top: 40px; color: #555; font-size: 12px; }

            /* Mobile Responsiveness */
            @media (max-width: 480px) {
                h1 { font-size: 24px; }
                p { font-size: 14px; }
                .btn { width: 100%; box-sizing: border-box; }
            }
        </style>
        
        <div class='container'>
            <h1>Whoops!</h1>
            <p>
                We are currently experiencing high traffic on our servers.<br>
                Please try again in a few moments.
            </p>
            
            <a href='javascript:location.reload()' class='btn'>Try Again</a>
            
            <div class='footer'>
                Ref: " . $currentTime . "
            </div>
        </div>
    ");
}

?>