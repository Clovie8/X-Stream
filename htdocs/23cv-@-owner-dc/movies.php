<?php
session_start();
include 'asserts/src/connection.php';

// --- 1. DEFINE HELPER FUNCTIONS ---
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

// --- 2. AJAX LIVE SEARCH HANDLER (Keep existing logic) ---
if(isset($_POST['live_search'])) {
    $search_term = mysqli_real_escape_string($connect, $_POST['live_search']);
    $query = "SELECT * FROM `movies` WHERE name LIKE '%{$search_term}%' OR category LIKE '%{$search_term}%' ORDER BY id DESC LIMIT 50";
    $execut = mysqli_query($connect, $query);
    
    if(mysqli_num_rows($execut) > 0){
        while ($fetch = mysqli_fetch_assoc($execut)) {
            ?>
            <tr>
                <td><?php echo $fetch['id']; ?></td>
                <td><?php echo $fetch['name']; ?></td>
                <td><div class="scroll-text"><?php echo $fetch['description']; ?></div></td>
                <td><?php echo $fetch['category']; ?></td>
                <td><?php echo $fetch['release_year']; ?></td>
                <td><?php echo $fetch['duration']; ?></td>
                <td><?php echo $fetch['translator']; ?></td>
                <td><div class="scroll-text"><?php echo $fetch['link']; ?></div></td>
                <td><?php echo timeAgo($fetch['created_at']); ?></td>
                <td><?php echo $fetch['views']; ?></td>
                <td>
                    <button class="action-btn" title="Edit">
                        <a href="update-movie?upmid=<?php echo $fetch['id']; ?>" style="text-decoration-line: none; color: white;">
                            <i class="fas fa-edit" style="color: #808080;"></i>
                        </a>
                    </button>
                    <button class="action-btn toggle-delete-btn" title="Delete" data-movie-id="<?php echo $fetch['id']; ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='11' style='text-align:center;'>No movies found</td></tr>";
    }
    exit(); 
}

// --- 3. MAIN PAGE LOGIC (Pagination + Real Search) ---
if (isset($_SESSION['email']) && isset($_SESSION['key'])) {

    // Get User Email
    $email = $_SESSION['email'];
    
    // Check if "Real Search" is active via URL
    $whereClause = "";
    $url_search_param = "";
    $search_val = ""; // For input field value

    if(isset($_GET['search']) && !empty($_GET['search'])){
        $search_val = mysqli_real_escape_string($connect, $_GET['search']);
        $whereClause = "WHERE name LIKE '%$search_val%' OR category LIKE '%$search_val%'";
        $url_search_param = "&search=" . urlencode($search_val); // Append to pagination links
    }

    // Pagination Variables
    $limit = 50; 
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $start_from = ($page - 1) * $limit;
    
    // Get Total Records (Respecting the Search Filter)
    $sql_count = "SELECT COUNT(id) FROM movies $whereClause";
    $rs_result = mysqli_query($connect, $sql_count);
    $row_count = mysqli_fetch_row($rs_result);
    $total_records = $row_count[0];
    $total_pages = ceil($total_records / $limit);
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
                <a href="#" class="menu-item">
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

        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4>Movies</h4>
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

            <div class="admin-content">
                <div class="content-header">
                    <h1 class="page-title">All Movies</h1>
                    <div>
                        <button class="btn btn-secondary" onclick="window.location.href='movies'">
                            <i class="fas fa-sync-alt"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <form action="" method="GET" class="search-form">
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" name="search" id="liveSearchInput" 
                                           placeholder="Search movies..." 
                                           value="<?php echo htmlspecialchars($search_val); ?>">
                                </div>
                                <button type="submit" class="btn-search-submit">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                        </form>
                        <button class="btn btn-primary">
                            <a href="upload" style="text-decoration-line: none; color: white;">
                                <i class="fas fa-plus"></i> Add New 
                            </a>
                        </button>
                    </div>

                    <?php if(isset($_SESSION['start'])): ?>
                        <div class="error_start">
                            <span><?php echo $_SESSION['start']; unset($_SESSION['start']); ?></span>
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['end'])): ?>
                        <div class="error_end">
                            <span><?php echo $_SESSION['end']; unset($_SESSION['end']); ?></span>
                            <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                            // MAIN QUERY: Uses $whereClause for real search, plus pagination
                            $select = "SELECT * FROM `movies` $whereClause ORDER BY id DESC LIMIT $start_from, $limit";
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
                                        <th>Views</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="movieTableBody">
                                    <?php
                                    if(mysqli_num_rows($execut) > 0) {
                                        while ($fetch = mysqli_fetch_assoc($execut)) {
                                        ?>
                                        <tr>
                                            <td><?php echo $fetch['id']; ?></td>
                                            <td><?php echo $fetch['name']; ?></td>
                                            <td><div class="scroll-text"><?php echo $fetch['description']; ?></div></td>
                                            <td><?php echo $fetch['category']; ?></td>
                                            <td><?php echo $fetch['release_year']; ?></td>
                                            <td><?php echo $fetch['duration']; ?></td>
                                            <td><?php echo $fetch['translator']; ?></td>
                                            <td><div class="scroll-text"><?php echo $fetch['link']; ?></div></td>
                                            <td><?php echo timeAgo($fetch['created_at']); ?></td>
                                            <td><?php echo $fetch['views']; ?></td>
                                            <td>
                                                <button class="action-btn" title="Edit">
                                                    <a href="update-movie?upmid=<?php echo $fetch['id']; ?>" style="text-decoration-line: none; color: white;">
                                                        <i class="fas fa-edit" style="color: #808080;"></i>
                                                    </a>
                                                </button>
                                                <button class="action-btn toggle-delete-btn" 
                                                        title="Delete" 
                                                        data-movie-id="<?php echo $fetch['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($fetch['name']); ?>"> <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php } 
                                    } else {
                                        echo "<tr><td colspan='11' style='text-align:center; padding:20px;'>No results found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                            
                        </div>
                        <div class="pagination-container">
                            <div class="pagination" id="paginationControls">
                                <?php 
                                if($page > 1){
                                    echo '<a href="?page='.($page-1).$url_search_param.'"><i class="fas fa-chevron-left"></i> Prev</a>';
                                }
                                
                                for($i=1; $i<=$total_pages; $i++) {
                                    $active = ($i == $page) ? "active" : "";
                                    echo '<a href="?page='.$i.$url_search_param.'" class="'.$active.'">'.$i.'</a>';
                                }
                                
                                if($page < $total_pages){
                                    echo '<a href="?page='.($page+1).$url_search_param.'">Next <i class="fas fa-chevron-right"></i></a>';
                                }
                                ?>
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

        <div class="container-delete" id="contdelete">
            <div class="main-delete" id="maindelete">
                <p>Are you sure you want to delete <b id="deleteItemName">this item</b>?</p>
                
                <div class="delete-buttons">
                    <button class="one" id="confirmDeleteBtn">Delete</button>
                    <button class="two">Cancel</button> </div>
            </div>
        </div>

    <script>
        // --- 1. LIVE SEARCH SCRIPT ---
        document.getElementById('liveSearchInput').addEventListener('keyup', function() {
            let searchTerm = this.value;
            let pagination = document.getElementById('paginationControls');
            
            // If typing triggers live search, hide pagination to avoid confusion
            if(searchTerm.length > 0) {
                pagination.style.display = 'none';
            } else {
                // If cleared, we reload to return to the correct "GET" state or clean state
                location.reload(); 
                return;
            }

            let formData = new FormData();
            formData.append('live_search', searchTerm);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('movieTableBody').innerHTML = data;
                attachDeleteListeners();
            });
        });
        
    </script>

    <script src="asserts/js/admin.js"></script>
</body>
</html>
<?php }else{
    header('location:way-to-go');
    exit();
}?>