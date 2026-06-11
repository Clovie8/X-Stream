<?php 
session_start(); 
include 'asserts/src/connection.php';
if (isset($_SESSION['email']) && isset($_SESSION['key'])) {

    // Get User Email
    $email = $_SESSION['email'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/image/logo.png" type="image/x-icon">
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
                    <h4>Upload</h4>
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
                    <h1 class="page-title">Add New Movie Or TV Serie</h1>
                    <div>
                        <button class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <?php if (isset($_GET['id-to-add']) && !empty($_GET['id-to-add'])) {
                    $epid = $_GET['id-to-add'];

                    $select_id = "SELECT * FROM `series` WHERE id = $epid LIMIT 1";
                    $execut_id = mysqli_query($connect, $select_id);
                    $fetch     = mysqli_fetch_array($execut_id, MYSQLI_ASSOC);
                    $rows      = mysqli_num_rows($execut_id);

                    if ($rows > 0) {
                ?>
    
                <!-- Add Seris Episodes Form (Example) -->
                <div class="card" id="card-episode">
                    <div class="card-header">
                        <h3 class="card-title">Add New Episode</h3>
                        <button class="btn btn-primary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back 
                        </button>
                    </div>
                    <?php
                        if(isset($_SESSION['starter'])){
                        ?>
                        <div class="error_start" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['starter']?></span>
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
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
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
                        </div>
                        <?php
                        unset($_SESSION['ender']);
                        }
                    ?>
                    <div class="card-body">
                        <?php
                        $select_details = "SELECT * FROM `series` WHERE id = $epid LIMIT 1";
                        $execut_details = mysqli_query($connect, $select_details);
                        while ($fetch_details = mysqli_fetch_assoc($execut_details)) {
                        ?>
                        <form id="movieForm" action="asserts/src/add-movie.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $fetch_details['id']; ?>">
                            <input type="hidden" name="serie_token" value="<?php echo $fetch_details['token']; ?>">
                            <input type="hidden" name="category" value="<?php echo $fetch_details['category']; ?>">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" value="<?php echo $fetch_details['name']; ?> Ep" class="form-control" placeholder="Episode Name" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="5" style="height: 5rem;" placeholder="Episode description"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Embend Link</label>
                                        <input type="text" name="link" class="form-control" placeholder="Embed Link">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-control" placeholder="Episode title">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Duration (minutes)</label>
                                        <input type="number" name="duration" class="form-control" placeholder="120" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Download Link</label>
                                        <input type="text" name="download" class="form-control" placeholder="Download Link">
                                        <small class="text-muted">⚠️ Leave empty If Embed Link can be used as Download link.</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="submit-episode" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Episode
                            </button>
                        </form>
                        <?php }?>
                    </div>
                </div>

                <?php }else{
                    $_SESSION['start'] = "Invalid ID!";
                    header('location:series');
                    exit();
                }?>


                <?php } else{?>
                <!-- Add Movie Form (Example) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Add New Movie</h3>
                    </div>
                    <?php
                        if(isset($_SESSION['start'])){
                        ?>
                        <div class="error_start" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['start']?></span>
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
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
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
                        </div>
                        <?php
                        unset($_SESSION['end']);
                        }
                    ?>
                    <div class="card-body">
                        <form id="movieForm" action="asserts/src/add-movie.php" method="POST" enctype="multipart/form-data">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="name" class="form-control" placeholder="Movie title" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="5" style="height: 5rem;" placeholder="Movie description" required><b>| .</b><br></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" name="category" id="category" required>
                                            <option value="" selected>Category</option>
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
                                            <option value="Indian">Indian</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Embend Link</label>
                                        <input type="text" name="link" class="form-control" placeholder="Embed Link">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ratings</label>
                                        <input type="text" name="rate" class="form-control" placeholder="Rate... E.g: 5.1">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Is Part</label>
                                        <select class="form-control" name="is_part" id="is_part" required onchange="togglePartToken()">
                                            <option value="" disabled selected>Yes or No</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Part Token</label>
                                        <select class="form-control" name="part_token" id="part_token" required>
                                            <option value="" disabled selected>Token</option>
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

                                    <script>
                                        // 1. Store all original options in a variable immediately
                                        const partTokenSelect = document.getElementById('part_token');
                                        const allTokenOptions = Array.from(partTokenSelect.options);
                                    
                                        function togglePartToken() {
                                            const isPartValue = document.getElementById('is_part').value;
                                            
                                            // Clear current options
                                            partTokenSelect.innerHTML = '';
                                    
                                            if (isPartValue === 'No') {
                                                // Filter to find ONLY the "New Token" option
                                                // We assume "New Token" is the text inside the option
                                                const newTokenOption = allTokenOptions.find(opt => opt.text.trim() === 'New Token');
                                                
                                                if (newTokenOption) {
                                                    partTokenSelect.add(newTokenOption);
                                                    newTokenOption.selected = true; // Auto-select it
                                                }
                                            } else {
                                                // If "Yes", restore ALL original options (including database results)
                                                allTokenOptions.forEach(option => {
                                                    partTokenSelect.add(option);
                                                });
                                                // Reset to the first option (placeholder) or keep user selection
                                                partTokenSelect.value = ""; 
                                            }
                                        }
                                    </script>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Release Year</label>
                                        <input type="number" name="year" class="form-control" placeholder="2023" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Duration (minutes)</label>
                                        <input type="number" name="duration" class="form-control" placeholder="120" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Download Link</label>
                                        <input type="text" name="download" class="form-control" placeholder="Download Link">
                                        <small class="text-muted">⚠️ Leave empty If Embed Link can be used as Download link.</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Translator</label>
                                        <select class="form-control" name="translator" id="translator" required>
                                            <option value="" disabled selected>Translator</option>
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
                                            <option value="Ssenior">Senior</option>
                                            <option value="Sikov">Sikov</option>
                                            <option value="Vj Tcr">Vj Tcr</option>
                                            <option value="Vj Steppin">Vj Steppin</option>
                                            <option value="Vj Unique">Vj Unique</option>
                                            <option value="Yanga">Yanga</option>
                                            <option value="Zacky">Zacky</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Poster Image (TMDB Link)</label>
                                        
                                        <input type="text" 
                                               name="tmdb_url" 
                                               class="form-control" 
                                               placeholder="Paste link: https://image.tmdb.org/..." 
                                               required 
                                               oninput="showPreview(this)">
                                               
                                        <div class="preview_box" style="margin-top: 15px; display: none; text-align: center; border: 2px dashed #ccc; padding: 10px; border-radius: 8px;">
                                            <p style="margin-bottom: 5px; color: #666; font-size: 0.9rem;">Poster Preview:</p>
                                            
                                            <img class="tmdb_preview_img" 
                                                 src="" 
                                                 alt="Poster Preview" 
                                                 style="max-width: 200px; max-height: 300px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                 onload="this.style.display='inline-block'; this.nextElementSibling.style.display='none';">
                                            
                                            <p class="preview_error" style="color: red; display: none; font-size: 0.8rem;">Invalid Image Link</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Backdrop Image (TMDB Link) - Wide</label>
                                        
                                        <input type="text" 
                                               name="backdrop_url" 
                                               class="form-control" 
                                               placeholder="Paste link: https://image.tmdb.org/..." 
                                               required 
                                               oninput="showPreview(this)">
                                               
                                        <div class="preview_box" style="margin-top: 15px; display: none; text-align: center; border: 2px dashed #ccc; padding: 10px; border-radius: 8px;">
                                            <p style="margin-bottom: 5px; color: #666; font-size: 0.9rem;">Backdrop Preview:</p>
                                            
                                            <img class="tmdb_preview_img" 
                                                 src="" 
                                                 alt="Backdrop Preview" 
                                                 style="max-width: 100%; max-height: 200px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); object-fit: cover;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                 onload="this.style.display='inline-block'; this.nextElementSibling.style.display='none';">
                                            
                                            <p class="preview_error" style="color: red; display: none; font-size: 0.8rem;">Invalid Image Link</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="submit-movie" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Movie
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Add Serie Form (Example) -->
                <div class="card" id="card-serie">
                    <div class="card-header">
                        <h3 class="card-title">Add New TV Serie</h3>
                    </div>
                     <?php
                        if(isset($_SESSION['starts'])){
                        ?>
                        <div class="error_start" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['starts']?></span>
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
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
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
                        </div>
                        <?php
                        unset($_SESSION['ends']);
                        }
                    ?>
                    <div class="card-body">
                        <form id="movieForm" action="asserts/src/add-movie.php" method="POST" enctype="multipart/form-data">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="name" class="form-control" placeholder="Movie title" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="5" style="height: 5rem;" placeholder="Serie description" required><b>| .</b><br></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" name="category" id="category" required>
                                            <option value="" disabled selected>Category</option>
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
                                        <input type="number" name="season" class="form-control" placeholder="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Episodes</label>
                                        <input type="number" name="episodes" class="form-control" placeholder="1" required>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label">Release Year</label>
                                        <input type="number" name="year" class="form-control" placeholder="2023" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ratings</label>
                                        <input type="text" name="rate" class="form-control" placeholder="Rate... E.g: 5.1">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Translator</label>
                                        <select class="form-control" name="translator" id="translator" required>
                                            <option value="" disabled selected>Translator</option>
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
                                        <label class="form-label">Poster Image (TMDB Link)</label>
                                        
                                        <input type="text" 
                                               name="tmdb_url" 
                                               class="form-control" 
                                               placeholder="Paste link: https://image.tmdb.org/..." 
                                               required 
                                               oninput="showPreview(this)">
                                               
                                        <div class="preview_box" style="margin-top: 15px; display: none; text-align: center; border: 2px dashed #ccc; padding: 10px; border-radius: 8px;">
                                            <p style="margin-bottom: 5px; color: #666; font-size: 0.9rem;">Poster Preview:</p>
                                            
                                            <img class="tmdb_preview_img" 
                                                 src="" 
                                                 alt="Poster Preview" 
                                                 style="max-width: 200px; max-height: 300px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                 onload="this.style.display='inline-block'; this.nextElementSibling.style.display='none';">
                                            
                                            <p class="preview_error" style="color: red; display: none; font-size: 0.8rem;">Invalid Image Link</p>
                                        </div>
                                    </div> 
                                    
                                    <div class="form-group">
                                        <label class="form-label">Backdrop Image (TMDB Link) - Wide</label>
                                        <input type="text" 
                                               name="backdrop_url" 
                                               class="form-control" 
                                               placeholder="Paste link: https://image.tmdb.org/..." 
                                               required 
                                               oninput="showPreview(this)">
                                               
                                        <div class="preview_box" style="margin-top: 15px; display: none; text-align: center; border: 2px dashed #ccc; padding: 10px; border-radius: 8px;">
                                            <p style="margin-bottom: 5px; color: #666; font-size: 0.9rem;">Backdrop Preview:</p>
                                            <img class="tmdb_preview_img" 
                                                 src="" 
                                                 alt="Backdrop Preview" 
                                                 style="max-width: 100%; max-height: 200px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); object-fit: cover; aspect-ratio: 16/9;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                 onload="this.style.display='inline-block'; this.nextElementSibling.style.display='none';">
                                            <p class="preview_error" style="color: red; display: none; font-size: 0.8rem;">Invalid Image Link</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="submit-serie" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Serie
                            </button>
                        </form>
                    </div>
                </div>
                 <?php }?>

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




        //POSTER IMAGE PERVIEW
        function showPreview(inputElement) {
            const url = inputElement.value;
            
            // 1. Find the parent container (form-group) of THIS input
            const container = inputElement.closest('.form-group');
            
            // 2. Find the elements ONLY inside this container
            const previewBox = container.querySelector('.preview_box');
            const previewImg = container.querySelector('.tmdb_preview_img');
            const errorMsg   = container.querySelector('.preview_error');
        
            // If empty, hide
            if (!url || url.trim() === "") {
                previewBox.style.display = 'none';
                return;
            }
        
            // Show box
            previewBox.style.display = 'block';
            
            // Reset error state
            errorMsg.style.display = 'none';
            previewImg.style.display = 'inline-block';
            
            // Set src
            previewImg.src = url;
        }


    </script>
</body>
</html>
<?php }else{
    header('location:way-to-go');
    exit();
}?>