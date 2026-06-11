<?php 
session_start(); 
include 'asserts/src/connection.php';

// Handle redirects before any output
if (!isset($_SESSION['email']) || !isset($_SESSION['key'])) {
    header('location:way-to-go');
    exit();
}

// Get User Email
$email = $_SESSION['email'];

// Check if we need to redirect for invalid parameters
if ((!isset($_GET['upmid']) || empty($_GET['upmid'])) && 
    (!isset($_GET['upsid']) || empty($_GET['upsid'])) && 
    (!isset($_GET['upeid']) || empty($_GET['upeid']))) {
    header('location:index');
    exit();
}

// Generate part token
$random        = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $part_token    = '';
    for($i = 0; $i < 10; $i++){
        $part_token  .= $random[random_int(0, strlen($random) - 1)];
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../assets/image/logo.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheOneMovies - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="asserts/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="sidebar-logo">TheOne Admin</a>
                <button class="toggle-sidebar-close" id="toggleSidebarclose">
                    <i class="fas fa-x"></i>
                </button>
            </div>
            <div class="sidebar-menu">
                <a href="index" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="movies" class="menu-item">
                    <i class="fas fa-film"></i>
                    Movies
                    <span class="badge">12</span>
                </a>
                <a href="series" class="menu-item">
                    <i class="fas fa-tv"></i>
                    TV Series
                    <span class="badge">8</span>
                </a>
                <a href="episodes" class="menu-item">
                    <i class="fas fa-film"></i>
                    Episodes
                </a>
                <a href="comment" class="menu-item">
                    <i class="fas fa-users"></i>
                    Users
                    <span class="badge">24</span>
                </a>
                <a href="comment" class="menu-item">
                    <i class="fas fa-comments"></i>
                    Comments
                    <span class="badge">5</span>
                </a>
                <a href="analytics" class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
                <a href="upload" class="menu-item">
                    <i class="fas fa-upload"></i>
                    Upload
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4>Update</h4>
                </div>
                <div class="header-right">
                    <div class="user-menu" id="userMenu">
                        <?php
                        $stmt = $connect->prepare("SELECT `name` FROM `owners` WHERE email = ? LIMIT 1");
                        
                        if ($stmt) {
                            $stmt->bind_param("s", $email);
                            $stmt->execute();
                            $result = $stmt->get_result();
                        
                            if ($row = $result->fetch_assoc()) {
                                ?>
                                <span><?php echo htmlspecialchars($row['name']); ?></span>
                                <?php
                            } else { echo "<span>No owner found</span>"; }
                            $stmt->close();
                        } else { echo "Query failed: " . $connect->error; }
                        ?>
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="asserts/src/logout" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content">
                <!-- Dashboard Content -->
                <div class="content-header">
                    <h1 class="page-title">Update Movie, TV Serie Or Episode</h1>
                    <div>
                        <button class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <?php
                if (isset($_GET['upmid']) && !empty($_GET['upmid'])) {
                    $upmid = $_GET['upmid'];

                    $select_movie = "SELECT * FROM `movies` WHERE id = $upmid LIMIT 1";
                    $execut_movie = mysqli_query($connect, $select_movie);
                    
                    if(mysqli_num_rows($execut_movie) > 0) {
                ?> 
                <!-- Update Movie Form (Example) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Update Movie</h3>
                        <button class="btn btn-primary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back 
                        </button>
                    </div>
                    <?php
                        if(isset($_SESSION['start'])){
                        ?>
                        <div class="error_start" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['start']?></span>
                        </div>
                        <?php
                        unset($_SESSION['start']);
                        }
                        ?>

                        <?php
                        if(isset($_SESSION['end'])){
                        ?>
                        <div class="error_end" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['end']?></span>
                        </div>
                        <?php
                        unset($_SESSION['end']);
                        }
                    ?>
                    <div class="card-body">
                        <?php
                        while ($fetch_one = mysqli_fetch_assoc($execut_movie)) {
                        ?>
                        <form id="movieForm" action="asserts/src/update-movie-script.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $fetch_one['id']; ?>">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="name" value="<?php echo $fetch_one['name']; ?>" class="form-control" placeholder="Movie title">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="5" style="height: 5rem;" placeholder="Movie description"><?php echo $fetch_one['description']; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" name="category" id="category">
                                            <option value="<?php echo $fetch_one['category']; ?>"><?php echo $fetch_one['category']; ?></option>
                                            <option value="Action">Action</option>
                                            <option value="Animation">Animation</option>
                                            <option value="Adventure">Adventure</option>
                                            <option value="Comedy">Comedy</option>
                                            <option value="Crime">Crime</option>
                                            <option value="Documentary">Documentary</option>
                                            <option value="Drama">Drama</option>
                                            <option value="Horror">Horror</option>
                                            <option value="Sci-Fi">Sci-Fi</option>
                                            <option value="Romance">Romance</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Embend Link</label>
                                        <input type="text" name="link" value="<?php echo $fetch_one['link']; ?>" class="form-control" placeholder="Embed Link">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ratings</label>
                                        <input type="text" name="rate" value="<?php echo $fetch_one['ratings']; ?>" class="form-control" placeholder="Ratings... E.g: 5.1">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Movie Token Key</label>
                                        <input type="text" disabled name="token" value="<?php echo $fetch_one['token']; ?>" class="form-control" placeholder="Token Key">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Is Part</label>
                                        <select class="form-control" name="is_part" id="is_part" required>
                                            <option value="<?php echo $fetch_one['Is_Part']; ?>"><?php echo $fetch_one['Is_Part']; ?></option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Part Token</label>
                                        <select class="form-control" name="part_token" id="part_token" required>
                                            <option value="<?php echo $fetch_one['Part_token']; ?>" selected><?php echo $fetch_one['Part_token']; ?></option>
                                            <option value="<?php echo $part_token; ?>">New Token</option>
                                            <?php 
                                            $select_part_token = "SELECT `name`, `Part_token` FROM `movies` ORDER BY id DESC";
                                            $query_part_token  = mysqli_query($connect, $select_part_token);
                                            while($fetch_part_token = mysqli_fetch_assoc($query_part_token)){
                                            ?>
                                            <option value="<?php echo $fetch_part_token['Part_token']; ?>"><?php echo $fetch_part_token['name']; ?></option>
                                            <?php }?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <?php 
                                            $timezone = new DateTimeZone('Africa/Kigali');
                                            $now = new DateTime('now', $timezone);
                                            $created_at = $now->format('Y-m-d H:i:s');
                                        ?>
                                        <label class="form-label">Time</label>
                                        <select class="form-control" name="time" id="time">
                                            <option value="<?php echo $fetch_one['created_at']; ?>"><?php echo $fetch_one['created_at']; ?></option>
                                            <option value="<?php echo $created_at; ?>">New Time</option>
                                        </select>    
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Release Year</label>
                                        <input type="number" name="year" value="<?php echo $fetch_one['release_year']; ?>" class="form-control" placeholder="2023">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Duration (minutes)</label>
                                        <input type="number" name="duration" value="<?php echo $fetch_one['duration']; ?>" class="form-control" placeholder="120">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Download Link</label>
                                        <input type="text" name="download" value="<?php echo $fetch_one['download']; ?>" class="form-control" placeholder="Download Link">
                                        <small class="text-muted">⚠️ Leave empty If Embed Link can be used as Download link.</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Translator</label>
                                        <select class="form-control" name="translator" id="translator">
                                            <option value="<?php echo $fetch_one['translator']; ?>"><?php echo $fetch_one['translator']; ?></option>
                                            <option value="B The Great">B The Great</option>
                                            <option value="B.Man">B.Man</option>
                                            <option value="Buringanire">Buringanire</option>
                                            <option value="Cyber">Cyber</option>
                                            <option value="Dylan">Dylan</option>
                                            <option value="Fred">Fred</option>
                                            <option value="Gaheza">Gaheza</option>
                                            <option value="Hakim">Hakim</option>
                                            <option value="Jackson">Jackson</option>
                                            <option value="Junior Giti">Junior Giti</option>
                                            <option value="Kim">Kim</option>
                                            <option value="Kappo">Kappo</option>
                                            <option value="Master P">Master P</option>
                                            <option value="Mr Fire">Mr Fire</option>
                                            <option value="Mr Jingo">Mr Jingo</option>
                                            <option value="Mungeli">Mungeli</option>
                                            <option value="Perfect">Perfect</option>
                                            <option value="Paul">Paul</option>
                                            <option value="PK">PK</option>
                                            <option value="Rocky">Rocky</option>
                                            <option value="Rumuri">Rumuri</option>
                                            <option value="Sankara">Sankara</option>
                                            <option value="Savimbi">Savimbi</option>
                                            <option value="Saga Mwiza">Saga Mwiza</option>
                                            <option value="Senior">Senior</option>
                                            <option value="Sikov">Sikov</option>
                                            <option value="Vj Tcr">Vj Tcr</option>
                                            <option value="Vj Steppin">Vj Steppin</option>
                                            <option value="Vj Unique">Vj Unique</option>
                                            <option value="Yanga">Yanga</option>
                                            <option value="Zacky">Zacky</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Current Poster</label>
                                        <div class="media-upload">
                                            <?php 
                                                $db_image = $fetch_one['image'];
                                                $r2_domain = "https://media.theonemovies.com/"; 
                                                if (filter_var($db_image, FILTER_VALIDATE_URL)) {
                                                    $final_src = $db_image;
                                                } else {
                                                    $final_src = $r2_domain . $db_image;
                                                }
                                            ?>
                                            <img src="<?php echo $final_src; ?>" width="150" style="border-radius: 8px; margin-bottom: 10px;">
                                            
                                            <input type="hidden" name="old_image" value="<?php echo $fetch_one['image']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Update Poster (TMDB Link)</label>
                                        <input type="text" name="new_tmdb_url" class="form-control" placeholder="Paste NEW TMDB link here to change poster...">
                                        <small class="text-muted">⚠️ Leave empty to keep the current poster.</small>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Current Backdrop</label>
                                        <div class="media-upload">
                                            <?php 
                                                $db_backdrop = $fetch_one['backdrop'];
                                                $r2_domain = "https://media.theonemovies.com/"; 
                                                
                                                // Handle cases where older movies might not have a backdrop yet
                                                if (!empty($db_backdrop)) {
                                                    if (filter_var($db_backdrop, FILTER_VALIDATE_URL)) {
                                                        $final_bd_src = $db_backdrop;
                                                    } else {
                                                        $final_bd_src = $r2_domain . $db_backdrop;
                                                    }
                                                } else {
                                                    $final_bd_src = $r2_domain . "default-backdrop.jpg"; // Show default if empty
                                                }
                                            ?>
                                            <img src="<?php echo $final_bd_src; ?>" width="250" style="border-radius: 8px; margin-bottom: 10px; object-fit: cover; aspect-ratio: 16/9;">
                                            
                                            <input type="hidden" name="old_backdrop" value="<?php echo htmlspecialchars($db_backdrop); ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Update Backdrop (TMDB Link) - Wide</label>
                                        <input type="text" name="new_backdrop_url" class="form-control" placeholder="Paste NEW TMDB link here to change backdrop...">
                                        <small class="text-muted">⚠️ Leave empty to keep the current backdrop.</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="update-movie" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Movie
                            </button>
                        </form>
                        <?php }?>
                    </div>
                </div>
                <?php 
                    } else {
                        // If no movie found with that ID, redirect
                        header('location:index');
                        exit();
                    }
                }
                elseif (isset($_GET['upsid']) && !empty($_GET['upsid'])) {
                    $upsid = $_GET['upsid'];

                    $select_serie = "SELECT * FROM `series` WHERE id = $upsid LIMIT 1";
                    $execut_serie = mysqli_query($connect, $select_serie);
                    
                    if(mysqli_num_rows($execut_serie) > 0) {
                ?>
                <!-- Update Serie Form (Example) -->
                <div class="card" id="card-serie">
                    <div class="card-header">
                        <h3 class="card-title">Update TV Serie</h3>
                        <button class="btn btn-primary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back 
                        </button>
                    </div>
                     <?php
                        if(isset($_SESSION['starts'])){
                        ?>
                        <div class="error_start" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['starts']?></span>
                        </div>
                        <?php
                        unset($_SESSION['starts']);
                        }
                        ?>

                        <?php
                        if(isset($_SESSION['ends'])){
                        ?>
                        <div class="error_end" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['ends']?></span>
                        </div>
                        <?php
                        unset($_SESSION['ends']);
                        }
                    ?>
                    <div class="card-body">
                        <?php
                        while ($fetch_two = mysqli_fetch_assoc($execut_serie)) {
                        ?>
                        <form id="movieForm" action="asserts/src/update-movie-script.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $fetch_two['id']; ?>">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="name" value="<?php echo $fetch_two['name']; ?>" class="form-control" placeholder="Movie title">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="5" style="height: 5rem;" placeholder="Movie description"><?php echo $fetch_two['description']; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" name="category" id="category">
                                            <option value="<?php echo $fetch_two['category']; ?>"><?php echo $fetch_two['category']; ?></option>
                                            <option value="Action">Action</option>
                                            <option value="Animation">Animation</option>
                                            <option value="Adventure">Adventure</option>
                                            <option value="Comedy">Comedy</option>
                                            <option value="Crime">Crime</option>
                                            <option value="Documentary">Documentary</option>
                                            <option value="Drama">Drama</option>
                                            <option value="Horror">Horror</option>
                                            <option value="Sci-Fi">Sci-Fi</option>
                                            <option value="Romance">Romance</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Season</label>
                                        <input type="number" name="season" value="<?php echo $fetch_two['season']; ?>" class="form-control" placeholder="1">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Episodes</label>
                                        <input type="number" name="episodes" value="<?php echo $fetch_two['episodes']; ?>" class="form-control" placeholder="1">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ratings</label>
                                        <input type="text" name="rate" value="<?php echo $fetch_two['ratings']; ?>" class="form-control" placeholder="Ratings... E.g: 5.1">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Serie Token Key</label>
                                        <input type="text" disabled name="token" value="<?php echo $fetch_two['token']; ?>" class="form-control" placeholder="Token Key">
                                    </div>
                                    <div class="form-group">
                                        <?php 
                                            $timezone = new DateTimeZone('Africa/Kigali');
                                            $now = new DateTime('now', $timezone);
                                            $created_at = $now->format('Y-m-d H:i:s');
                                        ?>
                                        <label class="form-label">Time</label>
                                        <select class="form-control" name="time" id="time">
                                            <option value="<?php echo $fetch_two['created_at']; ?>"><?php echo $fetch_two['created_at']; ?></option>
                                            <option value="<?php echo $created_at; ?>">New Time</option>
                                        </select>    
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Release Year</label>
                                        <input type="number" name="year" value="<?php echo $fetch_two['release_year']; ?>" class="form-control" placeholder="2023">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Translator</label>
                                        <select class="form-control" name="translator" id="translator">
                                            <option value="<?php echo $fetch_two['translator']; ?>"><?php echo $fetch_two['translator']; ?></option>
                                            <option value="B The Great">B The Great</option>
                                            <option value="B.Man">B.Man</option>
                                            <option value="Buringanire">Buringanire</option>
                                            <option value="Cyber">Cyber</option>
                                            <option value="Dylan">Dylan</option>
                                            <option value="Fred">Fred</option>
                                            <option value="Gaheza">Gaheza</option>
                                            <option value="Hakim">Hakim</option>
                                            <option value="Jackson">Jackson</option>
                                            <option value="Junior Giti">Junior Giti</option>
                                            <option value="Kim">Kim</option>
                                            <option value="Kappo">Kappo</option>
                                            <option value="Master P">Master P</option>
                                            <option value="Mr Fire">Mr Fire</option>
                                            <option value="Mr Jingo">Mr Jingo</option>
                                            <option value="Mungeli">Mungeli</option>
                                            <option value="Perfect">Perfect</option>
                                            <option value="Paul">Paul</option>
                                            <option value="PK">PK</option>
                                            <option value="Rocky">Rocky</option>
                                            <option value="Rumuri">Rumuri</option>
                                            <option value="Sankara">Sankara</option>
                                            <option value="Savimbi">Savimbi</option>
                                            <option value="Saga Mwiza">Saga Mwiza</option>
                                            <option value="Senior">Senior</option>
                                            <option value="Sikov">Sikov</option>
                                            <option value="Vj Tcr">Vj Tcr</option>
                                            <option value="Vj Steppin">Vj Steppin</option>
                                            <option value="Vj Unique">Vj Unique</option>
                                            <option value="Yanga">Yanga</option>
                                            <option value="Zacky">Zacky</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Current Poster</label>
                                        <div class="media-upload">
                                            <?php 
                                                $db_image = $fetch_two['image'];
                                                $r2_domain = "https://media.theonemovies.com/"; 
                                                if (filter_var($db_image, FILTER_VALIDATE_URL)) {
                                                    $final_src = $db_image;
                                                } else {
                                                    $final_src = $r2_domain . $db_image;
                                                }
                                            ?>
                                            <img src="<?php echo $final_src; ?>" width="150" style="border-radius: 8px; margin-bottom: 10px;">
                                            
                                            <input type="hidden" name="old_image" value="<?php echo $fetch_two['image']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Update Poster (TMDB Link)</label>
                                        <input type="text" name="new_tmdb_url" class="form-control" placeholder="Paste NEW TMDB link here to change poster...">
                                        <small class="text-muted">⚠️ Leave empty to keep the current poster.</small>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Current Backdrop</label>
                                        <div class="media-upload">
                                            <?php 
                                                $db_backdrop = $fetch_two['backdrop']; // Assuming your array is $fetch_two
                                                $r2_domain = "https://media.theonemovies.com/"; 
                                                if (!empty($db_backdrop)) {
                                                    if (filter_var($db_backdrop, FILTER_VALIDATE_URL)) {
                                                        $final_bd_src = $db_backdrop;
                                                    } else {
                                                        $final_bd_src = $r2_domain . $db_backdrop;
                                                    }
                                                } else {
                                                    $final_bd_src = $r2_domain . "default-backdrop.jpg";
                                                }
                                            ?>
                                            <img src="<?php echo $final_bd_src; ?>" width="250" style="border-radius: 8px; margin-bottom: 10px; object-fit: cover; aspect-ratio: 16/9;">
                                            <input type="hidden" name="old_backdrop" value="<?php echo htmlspecialchars($db_backdrop); ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Update Backdrop (TMDB Link) - Wide</label>
                                        <input type="text" name="new_backdrop_url" class="form-control" placeholder="Paste NEW TMDB link here to change backdrop...">
                                        <small class="text-muted">⚠️ Leave empty to keep the current backdrop.</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="update-serie" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Serie
                            </button>
                        </form>
                        <?php }?>
                    </div>
                </div>
                <?php 
                    } else {
                        // If no serie found with that ID, redirect
                        header('location:index');
                        exit();
                    }
                }
                elseif (isset($_GET['upeid']) && !empty($_GET['upeid'])) {
                    $upeid = $_GET['upeid'];

                    $select_episode = "SELECT * FROM `episodes` WHERE id = $upeid LIMIT 1";
                    $execut_episode = mysqli_query($connect, $select_episode);
                    
                    if(mysqli_num_rows($execut_episode) > 0) {
                ?>
                <!-- Update Seris Episodes Form (Example) -->
                <div class="card" id="card-episode">
                    <div class="card-header">
                        <h3 class="card-title">Update Episode</h3>
                        <button class="btn btn-primary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back 
                        </button>
                    </div>
                    <?php
                        if(isset($_SESSION['starter'])){
                        ?>
                        <div class="error_start" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['starter']?></span>
                        </div>
                        <?php
                        unset($_SESSION['starter']);
                        }
                        ?>

                        <?php
                        if(isset($_SESSION['ender'])){
                        ?>
                        <div class="error_end" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['ender']?></span>
                        </div>
                        <?php
                        unset($_SESSION['ender']);
                        }
                    ?>
                    <div class="card-body">
                        <?php while ($fetch_three = mysqli_fetch_assoc($execut_episode)) { ?>
                        <form id="movieForm" action="asserts/src/update-movie-script.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $fetch_three['id']; ?>">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" value="<?php echo $fetch_three['name']; ?>" class="form-control" placeholder="Episode Name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="5" style="height: 5rem;" placeholder="Episode description"><?php echo $fetch_three['description']; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Embend Link</label>
                                        <input type="text" name="link" value="<?php echo $fetch_three['link']; ?>" class="form-control" placeholder="Embed Link">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Episode Token Key</label>
                                        <input type="text" disabled name="token" value="<?php echo $fetch_three['token']; ?>" class="form-control" placeholder="Token Key">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" value="<?php echo $fetch_three['title']; ?>" class="form-control" placeholder="Episode title">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Duration (minutes)</label>
                                        <input type="number" name="duration" value="<?php echo $fetch_three['duration']; ?>" class="form-control" placeholder="120">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Download Link</label>
                                        <input type="text" name="download" value="<?php echo $fetch_three['download']; ?>" class="form-control" placeholder="Download Link">
                                        <small class="text-muted">⚠️ Leave empty If Embed Link can be used as Download link.</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="update-episode" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Episode
                            </button>
                        </form>
                        <?php } ?>
                    </div>
                </div>
                <?php 
                    } else {
                        // If no episode found with that ID, redirect
                        header('location:index');
                        exit();
                    }
                }
                ?>

            </div>
            <footer class="admin-footer">
                <span>
                    &copy; 2026 TheOneMovies. 
                    <i class="fas fa-sync-alt"></i>
                    Last sync: 
                    <strong>
                        <?php 
                            date_default_timezone_set('Africa/Kigali'); 
                            echo date('M j, Y h:i A'); 
                        ?>
                    </strong>
                </span>
            </footer>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Close Toggle sidebar on mobile
        document.getElementById('toggleSidebarclose').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
        });

        // Toggle user dropdown menu
        document.getElementById('userMenu').addEventListener('click', function() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        });

        // Close dropdown when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#userMenu')) {
                document.getElementById('dropdownMenu').classList.remove('show');
            }
        });

        // Media upload preview for movie
        document.getElementById('mediaUpload').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });

        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('mediaPreview');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Media upload preview for serie
        document.getElementById('mediaUpload-serie').addEventListener('click', function() {
            document.getElementById('fileInput-serie').click();
        });

        document.getElementById('fileInput-serie').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('mediaPreview-serie');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Media upload preview for episode
        document.getElementById('mediaUpload-episode').addEventListener('click', function() {
            document.getElementById('fileInput-episode').click();
        });

        document.getElementById('fileInput-episode').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('mediaPreview-episode');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>