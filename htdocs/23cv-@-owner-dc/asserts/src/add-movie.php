<?php
session_start();
include 'connection.php';
require_once "SimpleR2.php"; 

// --- R2 CONFIGURATION ---
$r2_account_id = "b3cc99db2fef9f54d9490e3e860d22b2";
$r2_access_key = "fa979a29eda3f50411252ea0b6ed374d";
$r2_secret_key = "788b1d45387d212439e333df3ef2d11559388fe9e54bae16f02e849581d1ee14";
$r2_bucket     = "movie-posters";
$r2_domain     = "media.theonemovies.com";

// Initialize R2
$r2 = new SimpleR2($r2_access_key, $r2_secret_key, $r2_account_id, $r2_bucket, $r2_domain);


// 1. PHP Code to upload movie
if (isset($_POST['submit-movie'])) {
    // 1. Sanitize standard inputs
    $name        = mysqli_real_escape_string($connect, $_POST['name']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $category    = mysqli_real_escape_string($connect, $_POST['category']);
    $year        = mysqli_real_escape_string($connect, $_POST['year']);
    $duration    = mysqli_real_escape_string($connect, $_POST['duration']);
    $translator  = mysqli_real_escape_string($connect, $_POST['translator']);
    $link        = mysqli_real_escape_string($connect, $_POST['link']);
    $download    = mysqli_real_escape_string($connect, $_POST['download']);
    $rate        = mysqli_real_escape_string($connect, $_POST['rate']);
    $is_part     = mysqli_real_escape_string($connect, $_POST['is_part']);
    $part_token  = mysqli_real_escape_string($connect, $_POST['part_token']);
    
    // TMDB URL Inputs
    $tmdb_url     = $_POST['tmdb_url']; 
    $backdrop_url = $_POST['backdrop_url']; // <-- NEW: Grab backdrop link

    // Generate Token & Dates
    $randomId = uniqid('', true); 
    $token    = str_replace('.', '', substr($randomId, 0, 20));

    $timezone = new DateTimeZone('Africa/Kigali');
    $now = new DateTime('now', $timezone);
    $created_at = $now->format('Y-m-d H:i:s');

    // 2. Validate Empty Inputs (Added backdrop to validation)
    if (empty($name) || empty($description) || empty($category) || empty($tmdb_url) || empty($backdrop_url)) {
        $_SESSION['start'] = "Fill all inputs to upload movie!";
        header('location:../../upload');
        exit();
    }

    // Fix for SSL issues on Localhost (XAMPP) - Moved up so both images can use it
    $context = stream_context_create([
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ]);

    // 1. Clean the name (Slugify) for file names
    $clean_name = strtolower($name); 
    $clean_name = preg_replace('/[^a-z0-9]+/', '-', $clean_name);
    $clean_name = trim($clean_name, '-');

    // ==========================================
    // --- UPLOAD LOGIC 1: POSTER IMAGE ---
    // ==========================================
    $unique_suffix = rand(1000, 9999);
    $path = parse_url($tmdb_url, PHP_URL_PATH);
    $ext = pathinfo($path, PATHINFO_EXTENSION);

    if (empty($ext) || strlen($ext) > 4) { $ext = "jpg"; }

    $new_file_name = $clean_name . '-poster-' . $unique_suffix . '.' . $ext; // Added '-poster-'
    $final_image_url = "";

    $image_content = file_get_contents($tmdb_url, false, $context);
    if ($image_content === false) {
        $_SESSION['start'] = "Error: Could not download POSTER from TMDB.";
        header('location:../../upload');
        exit();
    }

    $upload_success = $r2->upload($new_file_name, $image_content);
    if ($upload_success === true) {
        $final_image_url = $r2->getUrl($new_file_name);
    } else {
        $_SESSION['start'] = "Cloud Poster Upload Failed: " . $upload_success;
        header('location:../../upload');
        exit();
    }

    // ==========================================
    // --- UPLOAD LOGIC 2: BACKDROP IMAGE ---
    // ==========================================
    $unique_suffix_bd = rand(1000, 9999);
    $path_bd = parse_url($backdrop_url, PHP_URL_PATH);
    $ext_bd = pathinfo($path_bd, PATHINFO_EXTENSION);

    if (empty($ext_bd) || strlen($ext_bd) > 4) { $ext_bd = "jpg"; }

    $new_bd_name = $clean_name . '-backdrop-' . $unique_suffix_bd . '.' . $ext_bd; // Added '-backdrop-'
    $final_backdrop_url = "";

    $bd_content = file_get_contents($backdrop_url, false, $context);
    if ($bd_content === false) {
        $_SESSION['start'] = "Error: Could not download BACKDROP from TMDB.";
        header('location:../../upload');
        exit();
    }

    $upload_bd_success = $r2->upload($new_bd_name, $bd_content);
    if ($upload_bd_success === true) {
        $final_backdrop_url = $r2->getUrl($new_bd_name);
    } else {
        $_SESSION['start'] = "Cloud Backdrop Upload Failed: " . $upload_bd_success;
        header('location:../../upload');
        exit();
    }


    // ==========================================
    // --- 3. INSERT INTO DATABASE ---
    // ==========================================
    // Notice `backdrop` is added to the columns, and `$final_backdrop_url` is added to the values.
    $insert_movie = "INSERT INTO `movies`(`id`, `name`, `description`, `category`, `release_year`, `duration`, `translator`, `link`, `download`, `image`, `Is_Part`, `Part_token`, `created_at`, `token`, `ratings`, `backdrop`) 
                     VALUES (NULL,'$name','$description','$category','$year','$duration','$translator','$link','$download','$final_image_url','$is_part','$part_token','$created_at','$token','$rate','$final_backdrop_url')";
    
    $execte_movie = mysqli_query($connect, $insert_movie);

    if ($execte_movie) {
        $_SESSION['end'] = "Movie Uploaded Successfully!";
        header('location:../../upload');
        exit();
    } else {
        $_SESSION['start'] = "Database Error: " . mysqli_error($connect);
        header('location:../../upload');
        exit();
    }
}

// 2. PHP Code to upload serie 
if (isset($_POST['submit-serie'])) {
    $name        = mysqli_real_escape_string($connect, $_POST['name']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $category    = mysqli_real_escape_string($connect, $_POST['category']);
    $season      = mysqli_real_escape_string($connect, $_POST['season']);
    $year        = mysqli_real_escape_string($connect, $_POST['year']);
    $translator  = mysqli_real_escape_string($connect, $_POST['translator']);
    $episode     = mysqli_real_escape_string($connect, $_POST['episodes']);
    $rate        = mysqli_real_escape_string($connect, $_POST['rate']);
    
    // TMDB URL Inputs
    $tmdb_url     = $_POST['tmdb_url'];
    $backdrop_url = $_POST['backdrop_url']; // NEW

    // Generate Tokens & Dates
    $randomId = uniqid('', true); 
    $token    = str_replace('.', '', substr($randomId, 0, 20));

    $timezone = new DateTimeZone('Africa/Kigali');
    $now = new DateTime('now', $timezone);
    $created_at = $now->format('Y-m-d H:i:s');

    // Validate Empty Inputs
    if (empty($name) || empty($description) || empty($category) || empty($season) || empty($year) || empty($translator) || empty($episode) || empty($tmdb_url) || empty($backdrop_url)) {
        $_SESSION['starts'] = "Fill all input to upload serie!";
        header('location:../../upload#card-serie');
        exit();
    }

    // Shared setup for images
    $clean_name = strtolower($name); 
    $clean_name = preg_replace('/[^a-z0-9]+/', '-', $clean_name);
    $clean_name = trim($clean_name, '-');
    $context = stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

    // ==========================================
    // --- UPLOAD LOGIC 1: POSTER ---
    // ==========================================
    $unique_suffix = rand(1000, 9999);
    $path = parse_url($tmdb_url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    if (empty($ext) || !in_array($ext, $allowed_extensions)) { $ext = "jpg"; }

    $new_file_name = $clean_name . '-poster-' . $unique_suffix . '.' . $ext;
    $final_image_url = "";

    $image_content = file_get_contents($tmdb_url, false, $context);
    if ($image_content === false) {
        $_SESSION['starts'] = "Error: Could not download POSTER from TMDB.";
        header('location:../../upload#card-serie');
        exit();
    }

    $upload_success = $r2->upload($new_file_name, $image_content);
    if ($upload_success === true) {
        $final_image_url = $r2->getUrl($new_file_name);
    } else {
        $_SESSION['starts'] = "Cloud Poster Upload Failed: " . $upload_success;
        header('location:../../upload#card-serie');
        exit();
    }

    // ==========================================
    // --- UPLOAD LOGIC 2: BACKDROP ---
    // ==========================================
    $unique_suffix_bd = rand(1000, 9999);
    $path_bd = parse_url($backdrop_url, PHP_URL_PATH);
    $ext_bd = strtolower(pathinfo($path_bd, PATHINFO_EXTENSION));
    
    if (empty($ext_bd) || !in_array($ext_bd, $allowed_extensions)) { $ext_bd = "jpg"; }

    $new_bd_name = $clean_name . '-backdrop-' . $unique_suffix_bd . '.' . $ext_bd;
    $final_backdrop_url = "";

    $bd_content = file_get_contents($backdrop_url, false, $context);
    if ($bd_content === false) {
        $_SESSION['starts'] = "Error: Could not download BACKDROP from TMDB.";
        header('location:../../upload#card-serie');
        exit();
    }

    $upload_bd_success = $r2->upload($new_bd_name, $bd_content);
    if ($upload_bd_success === true) {
        $final_backdrop_url = $r2->getUrl($new_bd_name);
    } else {
        $_SESSION['starts'] = "Cloud Backdrop Upload Failed: " . $upload_bd_success;
        header('location:../../upload#card-serie');
        exit();
    }

    // Insert into Database (Added `backdrop` and `$final_backdrop_url`)
    $insert_serie = "INSERT INTO `series`(`id`, `name`, `description`, `category`, `season`, `release_year`, `translator`, `episodes`, `image`, `backdrop`, `created_at`, `token`, `ratings`) 
                     VALUES (NULL,'$name','$description','$category','$season','$year','$translator','$episode','$final_image_url','$final_backdrop_url','$created_at','$token','$rate')";
    
    $execte_serie = mysqli_query($connect, $insert_serie);

    if ($execte_serie) {
        $_SESSION['ends'] = "Serie Uploaded Successfuly";
        header('location:../../upload#card-serie');
        exit();
    } else {
        $_SESSION['starts'] = "Serie Uploading Fail!";
        header('location:../../upload#card-serie');
        exit();
    }
}

// 3. PHP Code to add episode of serie
if (isset($_POST['submit-episode'])) {
    $epid        = mysqli_real_escape_string($connect, $_POST['id']);
    $name        = mysqli_real_escape_string($connect, $_POST['name']);
    $title       = mysqli_real_escape_string($connect, $_POST['title']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $duration    = mysqli_real_escape_string($connect, $_POST['duration']);
    $link        = mysqli_real_escape_string($connect, $_POST['link']);
    $download    = mysqli_real_escape_string($connect, $_POST['download']);
    $category    = mysqli_real_escape_string($connect, $_POST['category']);
    $serie_token = mysqli_real_escape_string($connect, $_POST['serie_token']);
    $randomId = uniqid('', true); // generate random token 
    $token    = str_replace('.', '', substr($randomId, 0, 20));


    $timezone = new DateTimeZone('Africa/Kigali');
    $now = new DateTime('now', $timezone);
    $created_at = $now->format('Y-m-d H:i:s');

    if (empty($_POST['name']) or empty($_POST['duration'])) {
        $_SESSION['starter'] = "Fill all input to upload episode!";
        header('location:../../upload?id-to-add=' . $epid);
        exit();
    }

    $insert_episode = "INSERT INTO `episodes`(`id`, `serie_id`,`serie_token`, `name`, `title`, `description`, `category`, `duration`, `link`, `download`, `created_at`, `token`) 
                        VALUES (NULL,'$epid','$serie_token','$name','$title','$description','$category','$duration','$link','$download','$created_at','$token')";
    $execte_episode = mysqli_query($connect,$insert_episode);
        
    if ($execte_episode) {
        $_SESSION['ender'] = "Episode Uploaded Successfuly";
        header('location:../../upload?id-to-add=' . $epid);
        exit();
    }
    else{
        $_SESSION['starter'] = "Episode Uploading Fail!";
        header('location:../../upload?id-to-add=' . $epid);
        exit();
    }  
        
}   
?>