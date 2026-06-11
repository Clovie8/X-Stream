<?php
session_start();
include 'asserts/src/connection.php';
if (isset($_SESSION['email']) && isset($_SESSION['key'])) {

    
    // Get User Email
    $email = $_SESSION['email'];

    // Count all movies
    $result = mysqli_query($connect, "SELECT COUNT(*) AS total FROM movies WHERE Is_Part = 'No'");
    $row = mysqli_fetch_assoc($result);
    $total_movies = $row['total'];

    // Count all series
    $result_serie = mysqli_query($connect, "SELECT COUNT(*) AS total FROM series");
    $row_serie = mysqli_fetch_assoc($result_serie);
    $total_series = $row_serie['total'];

    // Count all episodes
    $result_episodes = mysqli_query($connect, "SELECT COUNT(*) AS total FROM episodes");
    $row_episodes = mysqli_fetch_assoc($result_episodes);
    $total_episodes = $row_episodes['total'];

    // Count all comments
    $result_comments = mysqli_query($connect, "SELECT COUNT(*) AS total FROM comments");
    $row_comments = mysqli_fetch_assoc($result_comments);
    $total_comments = $row_comments['total'];

    // Count all Movie Views
    $result_MViews = mysqli_query($connect, "SELECT SUM(views) AS total FROM movies");
    $row_MViews = mysqli_fetch_assoc($result_MViews);
    $total_MViews = $row_MViews['total'];

    // Count all Serie Views
    $result_SViews = mysqli_query($connect, "SELECT SUM(views) AS total FROM series");
    $row_SViews = mysqli_fetch_assoc($result_SViews);
    $total_SViews = $row_SViews['total'];

    // Count all Serie Views
    $result_EPViews = mysqli_query($connect, "SELECT SUM(views) AS total FROM episodes");
    $row_EPViews = mysqli_fetch_assoc($result_EPViews);
    $total_EPViews = $row_EPViews['total'];

    // Get all Users
    // Count Unique IPs (Total Visitors)
    $uniqueUsersQuery = $connect->query("SELECT COUNT(DISTINCT user_ip) FROM view_analytics");
    $uniqueUsers = $uniqueUsersQuery ? $uniqueUsersQuery->fetch_row()[0] : 0;
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
                <a href="#" class="menu-item active">
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
                    <h4>Dashboard</h4>
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
                    <h1 class="page-title">Dashboard OverView</h1>
                    <div>
                        <button class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e3f2fd; color: #1976d2;">
                            <i class="fas fa-film"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Total Movies</div>
                            <div class="stat-value"><?php echo $total_movies; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e8f5e9; color: #388e3c;">
                            <i class="fas fa-tv"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Total TV Series</div>
                            <div class="stat-value"><?php echo $total_series; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #fff3e0; color: #ffa000;">
                            <i class="fas fa-film"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Total Episodes</div>
                            <div class="stat-value"><?php echo $total_episodes; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f3e5f5; color: #8e24aa;">
                            <i class="fas fa-eye"></i> 
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Movie Views</div>
                            <div class="stat-value"><?php echo $total_MViews; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f3e5f5; color: #8e24aa;">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Serie Views</div>
                            <div class="stat-value"><?php echo $total_SViews; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f3e5f5; color: #8e24aa;">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Episode Views</div>
                            <div class="stat-value"><?php echo $total_EPViews; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f3e5f5; color: #8e24aa;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Active Users</div>
                            <div class="stat-value"><?php echo number_format($uniqueUsers); ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f3e5f5; color: #8e24aa;">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Total Comments</div>
                            <div class="stat-value"><?php echo $total_comments; ?></div>
                        </div>
                    </div>
                </div>
                <div class="tables-grid">
                    <!-- Recent Movies -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Movies</h3>
                            <button class="btn btn-primary">
                            <a href="upload" style="text-decoration-line: none; color: white;">
                                <i class="fas fa-plus"></i> Add New 
                            </a>
                            </button>
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
                            <div class="table-responsive">
                                <?php
                                $select = "SELECT * FROM `movies` ORDER BY id DESC LIMIT 5";
                                $execut = mysqli_query($connect, $select);
                                ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Year</th>
                                            <th>Duration</th>
                                            <th>Translator</th>
                                            <th>Link</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        function timeAgo($datetime){
                                            $now = new DateTime();
                                            $ago = new DateTime($datetime);
                                            $diff = $now->diff($ago);
    
                                            if($diff->y > 0) return $diff->y . "Y" . ($diff->y > 1 ? "s" : "") . " ago";
                                            if($diff->m > 0) return $diff->m . "M" . ($diff->m > 1 ? "s" : "") . " ago";
                                            if($diff->d > 0) return $diff->d . "D" . ($diff->d > 1 ? "s" : "") . " ago";
                                            if($diff->h > 0) return $diff->h . "H" . ($diff->h > 1 ? "s" : "") . " ago";
                                            if($diff->i > 0) return $diff->i . "Min" . ($diff->i > 1 ? "s" : "") . " ago";
                                            return "just now";
    
                                        }
            
                                        while ($fetch = mysqli_fetch_assoc($execut)) {
                                        ?>
                                        <tr>
                                            <td><?php echo $fetch['id']; ?></td>
                                            <td>
                                                <div class="scroll-text"><?php echo $fetch['name']; ?></div>
                                            </td>
                                            <td>
                                                <div class="scroll-text"><?php echo $fetch['description']; ?></div>
                                            </td>
                                            <td><?php echo $fetch['category']; ?></td>
                                            <td><?php echo $fetch['release_year']; ?></td>
                                            <td><?php echo $fetch['duration']; ?></td>
                                            <td><?php echo $fetch['translator']; ?></td>
                                            <td>
                                                <div class="scroll-text"><?php echo $fetch['link']; ?></div>
                                            </td>
                                            <td><?php echo timeAgo($fetch['created_at']); ?></td>
                                            <td>
                                                
                                                <button class="action-btn" title="Edit">
                                                    <a href="update-movie?upmid=<?php echo $fetch['id']; ?>" style="text-decoration-line: none; color: white;">
                                                        <i class="fas fa-edit" style="color: #808080;"></i>
                                                    </a>
                                                </button>
                                                <button class="action-btn" title="Delete">
                                                    <a href="movies">
                                                        <i class="fas fa-trash" style="color: #808080;"></i>
                                                    </a>
                                                </button>
                                                <!-- <button class="action-btn" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button> -->
                                            </td>
                                        </tr>
                                        <?php }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
    
                    <!-- Recent Series -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Series</h3>
                            <button class="btn btn-primary" href="upload">
                            <a href="upload" style="text-decoration-line: none; color: white;">
                                <i class="fas fa-plus"></i> Add New 
                            </a>
                            </button>
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
                            <div class="table-responsive">
                                <?php
                                $select = "SELECT * FROM `series` ORDER BY id DESC LIMIT 5";
                                $execut = mysqli_query($connect, $select);
                                ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Year</th>
                                            <th>Translator</th>
                                            <th>Ep</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while ($fetch = mysqli_fetch_assoc($execut)) {
                                        ?>
                                        <tr>
                                            <td><?php echo $fetch['id']; ?></td>
                                            <td><?php echo $fetch['name']; ?></td>
                                            <td>
                                                <div class="scroll-text"><?php echo $fetch['description']; ?></div>
                                            </td>
                                            <td><?php echo $fetch['category']; ?></td>
                                            <td><?php echo $fetch['release_year']; ?></td>
                                            <td><?php echo $fetch['translator']; ?></td>
                                            <td><?php echo $fetch['episodes']; ?></td>
                                            <td><?php echo timeAgo($fetch['created_at']); ?></td>
                                            <td>
                                                <button class="action-btn" title="Edit" id="toggleupdate">
                                                    <a href="update-movie?upsid=<?php echo $fetch['id']; ?>" style="text-decoration-line: none; color: white;">
                                                        <i class="fas fa-edit" style="color: #808080;"></i>
                                                    </a>
                                                </button>
                                                <button class="action-btn" title="Delete">
                                                    <a href="series">
                                                        <i class="fas fa-trash" style="color: #808080;"></i>
                                                    </a>
                                                </button>
                                                <button class="action-btn" title="Add Episode">
                                                    <a href="upload?id-to-add=<?php echo $fetch['id']; ?>" style="text-decoration-line: none; color: white;">
                                                        <i class="fas fa-plus" style="color: #808080;"></i>
                                                    </a>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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

        // Media upload preview
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

        // Form submission
        document.getElementById('movieForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Movie saved successfully!');
            // Here you would typically send the form data to your server
        });
    </script>
</body>
</html>
<?php }else{
    header('location:way-to-go');
    exit();
}?>