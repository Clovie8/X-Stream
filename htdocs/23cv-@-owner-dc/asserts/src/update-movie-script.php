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

$r2 = new SimpleR2($r2_access_key, $r2_secret_key, $r2_account_id, $r2_bucket, $r2_domain);


// 1. PHP Code to update Movie
if (isset($_POST['update-movie'])) {
    $idm         = mysqli_real_escape_string($connect, $_POST['id']);
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
    $new_time    = mysqli_real_escape_string($connect, $_POST['time']);
    
    // Hidden Old Images
    $old_image    = mysqli_real_escape_string($connect, $_POST['old_image']); 
    $old_backdrop = mysqli_real_escape_string($connect, $_POST['old_backdrop']); 

    // New TMDB Inputs
    $new_tmdb_url     = $_POST['new_tmdb_url'];
    $new_backdrop_url = $_POST['new_backdrop_url']; 

    // Check for empty fields
    if (empty($name) || empty($description) || empty($category) || empty($year)) {
        $_SESSION['start'] = "Fill all input to update movie!";
        header('location:../../update-movie?upmid=' . $idm);
        exit();
    }

    // Shared SSL Context for downloading both images
    $context = stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
    
    // Allowed image extensions
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

    // ==========================================
    // --- LOGIC 1: Handle Poster Update ---
    // ==========================================
    $final_image_url = $old_image; // Default: Keep the old one

    if (!empty($new_tmdb_url)) {
        $clean_name = strtolower($name); 
        $clean_name = preg_replace('/[^a-z0-9]+/', '-', $clean_name);
        $clean_name = trim($clean_name, '-');
        $unique_suffix = rand(1000, 9999);
        
        // --- REFINED EXTENSION LOGIC ---
        $path = parse_url($new_tmdb_url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // If it's empty or NOT in our allowed list, force it to 'jpg'
        if (empty($ext) || !in_array($ext, $allowed_extensions)) { 
            $ext = "jpg"; 
        }

        $new_file_name = $clean_name . '-poster-' . $unique_suffix . '.' . $ext;

        $image_content = file_get_contents($new_tmdb_url, false, $context);

        if ($image_content !== false) {
            $upload_success = $r2->upload($new_file_name, $image_content);

            if ($upload_success === true) {
                $final_image_url = $r2->getUrl($new_file_name);
                
                // DELETE OLD POSTER
                if (strpos($old_image, $r2Domain) !== false) {
                    $old_filename = basename($old_image);
                    $r2->delete($old_filename);
                } elseif (!filter_var($old_image, FILTER_VALIDATE_URL) && file_exists("../../../assets/poster/" . $old_image)) {
                    unlink("../../../assets/poster/" . $old_image);
                }
            } else {
                $_SESSION['start'] = "Cloud Poster Upload Failed: " . $upload_success;
                header('location:../../update-movie?upmid=' . $idm);
                exit();
            }
        } else {
            $_SESSION['start'] = "Could not download poster from TMDB.";
            header('location:../../update-movie?upmid=' . $idm);
            exit();
        }
    }

    // ==========================================
    // --- LOGIC 2: Handle Backdrop Update ---
    // ==========================================
    $final_backdrop_url = $old_backdrop; // Default: Keep the old one

    if (!empty($new_backdrop_url)) {
        $clean_name_bd = strtolower($name); 
        $clean_name_bd = preg_replace('/[^a-z0-9]+/', '-', $clean_name_bd);
        $clean_name_bd = trim($clean_name_bd, '-');
        $unique_suffix_bd = rand(1000, 9999);
        
        // --- REFINED EXTENSION LOGIC ---
        $path_bd = parse_url($new_backdrop_url, PHP_URL_PATH);
        $ext_bd = strtolower(pathinfo($path_bd, PATHINFO_EXTENSION));
        
        // If it's empty or NOT in our allowed list, force it to 'jpg'
        if (empty($ext_bd) || !in_array($ext_bd, $allowed_extensions)) { 
            $ext_bd = "jpg"; 
        }

        $new_bd_name = $clean_name_bd . '-backdrop-' . $unique_suffix_bd . '.' . $ext_bd;

        $bd_content = file_get_contents($new_backdrop_url, false, $context);

        if ($bd_content !== false) {
            $upload_bd_success = $r2->upload($new_bd_name, $bd_content);

            if ($upload_bd_success === true) {
                $final_backdrop_url = $r2->getUrl($new_bd_name);
                
                // DELETE OLD BACKDROP
                if (!empty($old_backdrop)) {
                    if (strpos($old_backdrop, $r2Domain) !== false) {
                        $old_bd_filename = basename($old_backdrop);
                        $r2->delete($old_bd_filename);
                    } elseif (!filter_var($old_backdrop, FILTER_VALIDATE_URL) && file_exists("../../../assets/backdrop/" . $old_backdrop)) {
                        unlink("../../../assets/backdrop/" . $old_backdrop);
                    }
                }
            } else {
                $_SESSION['start'] = "Cloud Backdrop Upload Failed: " . $upload_bd_success;
                header('location:../../update-movie?upmid=' . $idm);
                exit();
            }
        } else {
            $_SESSION['start'] = "Could not download backdrop from TMDB.";
            header('location:../../update-movie?upmid=' . $idm);
            exit();
        }
    }
    
    // ==========================================
    // --- 3. Update movie in database ---
    // ==========================================
    $update_movie = "UPDATE `movies` SET 
                    `name`='$name',
                    `description`='$description',
                    `category`='$category',
                    `release_year`='$year',
                    `duration`='$duration',
                    `translator`='$translator',
                    `link`='$link',
                    `download`='$download',
                    `ratings`='$rate',
                    `image`='$final_image_url', 
                    `Is_Part`='$is_part', 
                    `Part_token`='$part_token',
                    `created_at`='$new_time',
                    `backdrop`='$final_backdrop_url'
                    WHERE id = $idm";
                    
    $execte_movie = mysqli_query($connect, $update_movie);

    if ($execte_movie) {
        $_SESSION['end'] = "Movie Updated Successfully";
        header('location:../../update-movie?upmid=' . $idm);
        exit();
    } else {
        $_SESSION['start'] = "Movie Updating Failed!";
        header('location:../../update-movie?upmid=' . $idm);
        exit();
    }
}

// 2. PHP Code to update serie 
if (isset($_POST['update-serie'])) {
    $ids         = mysqli_real_escape_string($connect, $_POST['id']);
    $name        = mysqli_real_escape_string($connect, $_POST['name']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $category    = mysqli_real_escape_string($connect, $_POST['category']);
    $season      = mysqli_real_escape_string($connect, $_POST['season']);
    $year        = mysqli_real_escape_string($connect, $_POST['year']);
    $translator  = mysqli_real_escape_string($connect, $_POST['translator']);
    $episode     = mysqli_real_escape_string($connect, $_POST['episodes']);
    $rate        = mysqli_real_escape_string($connect, $_POST['rate']);
    $new_time    = mysqli_real_escape_string($connect, $_POST['time']);
    
    // Hidden Old Images
    $old_image    = mysqli_real_escape_string($connect, $_POST['old_image']);
    $old_backdrop = mysqli_real_escape_string($connect, $_POST['old_backdrop']);

    // New TMDB Inputs
    $new_tmdb_url     = $_POST['new_tmdb_url'];
    $new_backdrop_url = $_POST['new_backdrop_url'];

    if (empty($name) || empty($description) || empty($category) || empty($year) || empty($season) || empty($translator) || empty($episode)) {
        $_SESSION['starts'] = "Fill all input to update serie!";
        header('location:../../update-movie?upsid=' . $ids);
        exit();
    }

    $r2_domain = "https://media.theonemovies.com/"; 
    $context = stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

    $clean_name = strtolower($name); 
    $clean_name = preg_replace('/[^a-z0-9]+/', '-', $clean_name);
    $clean_name = trim($clean_name, '-');

    // ==========================================
    // --- LOGIC 1: Handle Poster Update ---
    // ==========================================
    $final_image_url = $old_image;

    if (!empty($new_tmdb_url)) {
        $unique_suffix = rand(1000, 9999);
        $path = parse_url($new_tmdb_url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if (empty($ext) || !in_array($ext, $allowed_extensions)) { $ext = "jpg"; }

        $new_file_name = $clean_name . '-poster-' . $unique_suffix . '.' . $ext;
        $image_content = file_get_contents($new_tmdb_url, false, $context);

        if ($image_content !== false) {
            $upload_success = $r2->upload($new_file_name, $image_content);

            if ($upload_success === true) {
                $final_image_url = $r2->getUrl($new_file_name);
                
                // DELETE OLD POSTER
                if (strpos($old_image, $r2_domain) !== false) {
                    $old_filename = basename($old_image);
                    $r2->delete($old_filename);
                } elseif (!filter_var($old_image, FILTER_VALIDATE_URL) && file_exists("../../../assets/poster/" . $old_image)) {
                    unlink("../../../assets/poster/" . $old_image);
                }
            } else {
                $_SESSION['starts'] = "Cloud Upload Failed: " . $upload_success;
                header('location:../../update-movie?upsid=' . $ids);
                exit();
            }
        } else {
            $_SESSION['starts'] = "Could not download image from TMDB.";
            header('location:../../update-movie?upsid=' . $ids);
            exit();
        }
    }

    // ==========================================
    // --- LOGIC 2: Handle Backdrop Update ---
    // ==========================================
    $final_backdrop_url = $old_backdrop;

    if (!empty($new_backdrop_url)) {
        $unique_suffix_bd = rand(1000, 9999);
        $path_bd = parse_url($new_backdrop_url, PHP_URL_PATH);
        $ext_bd = strtolower(pathinfo($path_bd, PATHINFO_EXTENSION));
        
        if (empty($ext_bd) || !in_array($ext_bd, $allowed_extensions)) { $ext_bd = "jpg"; }

        $new_bd_name = $clean_name . '-backdrop-' . $unique_suffix_bd . '.' . $ext_bd;
        $bd_content = file_get_contents($new_backdrop_url, false, $context);

        if ($bd_content !== false) {
            $upload_bd_success = $r2->upload($new_bd_name, $bd_content);

            if ($upload_bd_success === true) {
                $final_backdrop_url = $r2->getUrl($new_bd_name);
                
                // DELETE OLD BACKDROP
                if (!empty($old_backdrop)) {
                    if (strpos($old_backdrop, $r2_domain) !== false) {
                        $old_bd_filename = basename($old_backdrop);
                        $r2->delete($old_bd_filename);
                    } elseif (!filter_var($old_backdrop, FILTER_VALIDATE_URL) && file_exists("../../../assets/backdrop/" . $old_backdrop)) {
                        unlink("../../../assets/backdrop/" . $old_backdrop);
                    }
                }
            } else {
                $_SESSION['starts'] = "Cloud Backdrop Upload Failed: " . $upload_bd_success;
                header('location:../../update-movie?upsid=' . $ids);
                exit();
            }
        } else {
            $_SESSION['starts'] = "Could not download backdrop from TMDB.";
            header('location:../../update-movie?upsid=' . $ids);
            exit();
        }
    }
    
    // Update Series in database (Added `backdrop`='$final_backdrop_url')
    $update_serie = "UPDATE `series` SET 
                    `name`='$name',
                    `description`='$description',
                    `category`='$category',
                    `season`='$season',
                    `release_year`='$year',
                    `translator`='$translator',
                    `episodes`='$episode',
                    `image`='$final_image_url',
                    `created_at`='$new_time',
                    `ratings`='$rate',
                    `backdrop`='$final_backdrop_url'
                    WHERE id = $ids";
                    
    $execte_serie = mysqli_query($connect, $update_serie);

    if ($execte_serie) {
        $_SESSION['ends'] = "Serie Updated Successfully";
        header('location:../../update-movie?upsid=' . $ids);
        exit();
    } else {
        $_SESSION['starts'] = "Serie Updating Failed!";
        header('location:../../update-movie?upsid=' . $ids);
        exit();
    }
}

// 3. PHP Code to update episode of serie
if (isset($_POST['update-episode'])) {
    $ide         = mysqli_real_escape_string($connect, $_POST['id']);
    $name        = mysqli_real_escape_string($connect, $_POST['name']);
    $title       = mysqli_real_escape_string($connect, $_POST['title']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $duration    = mysqli_real_escape_string($connect, $_POST['duration']);
    $link        = mysqli_real_escape_string($connect, $_POST['link']);
    $download    = mysqli_real_escape_string($connect, $_POST['download']);

    if (empty($_POST['name']) or  empty($_POST['duration'])) {
        $_SESSION['starter'] = "Fill all input to update episode!";
        header('location:../../update-movie?upeid=' . $ide);
        exit();
    }

    $update_episode = "UPDATE `episodes` SET `name`='$name',`title`='$title',`description`='$description',`duration`='$duration',`link`='$link',`download`='$download'  WHERE id = $ide";
    $execte_episode = mysqli_query($connect,$update_episode);

    if ($execte_episode) {
        $_SESSION['ender'] = "Episode Updated Successfuly";
        header('location:../../update-movie?upeid=' . $ide);
        exit();
    }
    else{
        $_SESSION['starter'] = "Episode Updating Fail!";
        header('location:../../update-movie?upeid=' . $ide);
        exit();
    }
}
?>