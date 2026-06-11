<?php
session_start();
include 'asserts/src/connection.php';

// --- 1. AJAX LIVE SEARCH HANDLER (Added) ---
if(isset($_POST['live_search'])) {
    $search_term = mysqli_real_escape_string($connect, $_POST['live_search']);
    
    // Construct the same complex query for live search
    // We search IP, Country, City, Device, OS, or the Video Title (via Joins)
    $query = "
        SELECT a.*, 
               COALESCE(m.name, s.name, e.name) as video_title
        FROM view_analytics a
        LEFT JOIN movies m ON (a.video_id = m.id AND a.type = 'movie')
        LEFT JOIN series s ON (a.video_id = s.id AND a.type = 'serie')
        LEFT JOIN episodes e ON (a.video_id = e.id AND a.type = 'episode')
        WHERE 
            a.user_ip LIKE '%{$search_term}%' OR 
            a.country LIKE '%{$search_term}%' OR 
            a.city LIKE '%{$search_term}%' OR 
            a.device LIKE '%{$search_term}%' OR 
            COALESCE(m.name, s.name, e.name) LIKE '%{$search_term}%'
        ORDER BY viewed_at DESC
        LIMIT 50
    ";
    
    $execut = mysqli_query($connect, $query);
    
    if(mysqli_num_rows($execut) > 0){
        $i = 1; // Row counter
        while ($row = mysqli_fetch_assoc($execut)) {
            ?>
            <tr>
                <td style="font-weight: bold; color: #777;"><?php echo $i++; ?></td>
                <td>
                    <span class="badge badge-<?php echo ($row['type']=='movie'?'primary':($row['type']=='serie'?'warning':'success')); ?>">
                        <?php echo ucfirst($row['type']); ?>
                    </span>
                </td>
                <td style="font-weight: 500;">
                    <?php echo htmlspecialchars($row['video_title'] ?? 'Unknown Title'); ?>
                </td>
                <td style="font-family: monospace; color: #666;"><?php echo htmlspecialchars($row['user_ip']); ?></td>
                <td>
                    <?php if($row['country'] != 'Unknown'): ?>
                        <i class="fas fa-map-marker-alt" style="color: #e91e63; margin-right: 5px;"></i>
                        <?php echo htmlspecialchars($row['city'] . ', ' . $row['country']); ?>
                    <?php else: ?>
                        <span style="color: #ccc;">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <i class="fas fa-<?php echo ($row['device']=='Mobile'?'mobile-alt':'desktop'); ?>" style="margin-right: 5px;"></i>
                    <?php echo htmlspecialchars($row['os'] . ' / ' . $row['browser']); ?>
                </td>
                <td style="font-size: 0.9em; color: #555;">
                    <?php echo date('M j, Y h:i A', strtotime($row['viewed_at'])); ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>No records match your search.</td></tr>";
    }
    exit(); // Stop here for AJAX requests
}


// --- 2. EXISTING DATA FETCHING (Keep as is) ---
if (isset($_SESSION['email']) && isset($_SESSION['key'])) {

    // Get User Email
    $email = $_SESSION['email'];

    $totalViewsQuery = $connect->query("SELECT COUNT(*) FROM view_analytics");
    $totalViews = $totalViewsQuery ? $totalViewsQuery->fetch_row()[0] : 0;

    
    $kigali_timezone = new DateTimeZone('Africa/Kigali');
    $date = new DateTime('now', $kigali_timezone);
    $today_in_kigali = $date->format('Y-m-d');

    $todayViewsQuery = $connect->query("SELECT COUNT(*) FROM view_analytics WHERE DATE(viewed_at) = '$today_in_kigali'");
    $todayViews = $todayViewsQuery ? $todayViewsQuery->fetch_row()[0] : 0;
    
    $topDeviceQuery = $connect->query("SELECT device, COUNT(*) as count FROM view_analytics GROUP BY device ORDER BY count DESC LIMIT 1");
    $topDevice = $topDeviceQuery ? $topDeviceQuery->fetch_assoc() : null;
    $topDeviceName = $topDevice ? $topDevice['device'] : 'N/A';

    $topMoviesQuery = $connect->query("SELECT m.name as title, COUNT(a.id) as views FROM view_analytics a JOIN movies m ON a.video_id = m.id WHERE a.type = 'movie' GROUP BY a.video_id ORDER BY views DESC LIMIT 5");
    $topMovies = $topMoviesQuery ? $topMoviesQuery->fetch_all(MYSQLI_ASSOC) : [];

    $trendingSeriesQuery = $connect->query("SELECT s.name as title, COUNT(a.id) as views FROM view_analytics a JOIN series s ON a.video_id = s.id WHERE a.type = 'serie' AND a.viewed_at >= DATE(NOW()) - INTERVAL 7 DAY GROUP BY a.video_id ORDER BY views DESC LIMIT 5");
    $trendingSeries = $trendingSeriesQuery ? $trendingSeriesQuery->fetch_all(MYSQLI_ASSOC) : [];

    $topCountriesQuery = $connect->query("SELECT country, COUNT(*) as count FROM view_analytics WHERE country IS NOT NULL AND country != 'Unknown' GROUP BY country ORDER BY count DESC LIMIT 5");
    $topCountries = $topCountriesQuery ? $topCountriesQuery->fetch_all(MYSQLI_ASSOC) : [];

    $topEpisodesQuery = $connect->query("SELECT e.name as title, COUNT(a.id) as views FROM view_analytics a JOIN episodes e ON a.video_id = e.id WHERE a.type = 'episode' GROUP BY a.video_id ORDER BY views DESC LIMIT 5");
    $topEpisodes = $topEpisodesQuery ? $topEpisodesQuery->fetch_all(MYSQLI_ASSOC) : [];


    // === MASTER TABLE LOGIC ===
    
    // Default Parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 50; 
    $offset = ($page - 1) * $limit;
    
    // Capture URL params
    $search = isset($_GET['search']) ? $connect->real_escape_string($_GET['search']) : '';
    $fromDate = isset($_GET['from']) ? $connect->real_escape_string($_GET['from']) : '';
    $toDate = isset($_GET['to']) ? $connect->real_escape_string($_GET['to']) : '';
    $sort = isset($_GET['sort']) ? $connect->real_escape_string($_GET['sort']) : 'viewed_at';
    $order = isset($_GET['order']) ? $connect->real_escape_string($_GET['order']) : 'DESC';

    // Base WHERE
    $whereSQL = "WHERE 1=1";

    // Search Filter (Real Search via URL)
    if (!empty($search)) {
        // We need to handle this carefully with the joins later, 
        // but for counting rows, we need simple conditions first.
        // NOTE: Searching on 'video_title' requires HAVING or complex WHERE, 
        // simplified here to standard fields.
        $whereSQL .= " AND (
            a.user_ip LIKE '%$search%' OR 
            a.country LIKE '%$search%' OR 
            a.city LIKE '%$search%' OR 
            a.device LIKE '%$search%' OR 
            a.os LIKE '%$search%'
        )";
    }

    if (!empty($fromDate) && !empty($toDate)) {
        $whereSQL .= " AND DATE(a.viewed_at) BETWEEN '$fromDate' AND '$toDate'";
    }

    // Dynamic SQL
    $sql = "
        SELECT a.*, 
               COALESCE(m.name, s.name, e.name) as video_title
        FROM view_analytics a
        LEFT JOIN movies m ON (a.video_id = m.id AND a.type = 'movie')
        LEFT JOIN series s ON (a.video_id = s.id AND a.type = 'serie')
        LEFT JOIN episodes e ON (a.video_id = e.id AND a.type = 'episode')
        $whereSQL
        ORDER BY $sort $order
        LIMIT $limit OFFSET $offset
    ";

    $masterTableQuery = $connect->query($sql);
    $analyticsData = $masterTableQuery ? $masterTableQuery->fetch_all(MYSQLI_ASSOC) : [];

    // D. Get Total Rows (Existing)
    $countSql = "SELECT COUNT(*) FROM view_analytics a $whereSQL";
    $totalRows = $connect->query($countSql)->fetch_row()[0];
    
    // E. NEW: Get Unique Users (Matching the same search!)
    // We use the same $whereSQL so it respects your Date & Search filters
    $countUsersSql = "SELECT COUNT(DISTINCT user_ip) FROM view_analytics a $whereSQL";
    $totalUniqueUsers = $connect->query($countUsersSql)->fetch_row()[0];

    $totalPages = ceil($totalRows / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../assets/image/logo.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheOneMovies - Analytics</title>
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
                <a href="index" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="movies" class="menu-item">
                    <i class="fas fa-film"></i> Movies <span class="badge">12</span>
                </a>
                <a href="series" class="menu-item">
                    <i class="fas fa-tv"></i> TV Series <span class="badge">8</span>
                </a>
                <a href="episodes" class="menu-item">
                    <i class="fas fa-film"></i> Episodes
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-users"></i> Users <span class="badge">24</span>
                </a>
                <a href="comment" class="menu-item">
                    <i class="fas fa-comments"></i> Comments <span class="badge">5</span>
                </a>
                <a href="analytics" class="menu-item active">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
                <a href="upload" class="menu-item">
                    <i class="fas fa-upload"></i> Upload
                </a>
            </div>
        </div>

        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4>Analytics</h4>
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
                            <a href="#" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
                            <a href="#" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                            <a href="asserts/src/logout" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-content">
                <div class="content-header">
                    <h1 class="page-title">Traffic & Performance</h1>
                    <div>
                        <button class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh Data
                        </button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e3f2fd; color: #0d47a1;">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Total Views (All Time)</div>
                            <div class="stat-value"><?php echo number_format($totalViews); ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e8f5e9; color: #1b5e20;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Views Today</div>
                            <div class="stat-value"><?php echo number_format($todayViews); ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #fff3e0; color: #e65100;">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Top Device</div>
                            <div class="stat-value"><?php echo htmlspecialchars($topDeviceName); ?></div>
                        </div>
                    </div>
                </div>

                <div class="analytics-grid-row">
                    <div class="card analytics-card">
                        <div class="card-header">
                            <span class="card-title"><i class="fas fa-film" style="color: var(--primary); margin-right:8px;"></i> Top 5 Movies</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th style="width: 50px;">#</th><th>Title</th><th class="text-right">Views</th></tr></thead>
                                    <tbody>
                                        <?php if(count($topMovies) > 0): $i=1; foreach($topMovies as $movie): ?>
                                            <tr><td style="color: #888;"><?php echo $i++; ?></td><td><?php echo htmlspecialchars($movie['title']); ?></td><td class="text-right font-weight-bold"><?php echo number_format($movie['views']); ?></td></tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="3" class="text-center">No data available</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card analytics-card">
                        <div class="card-header">
                            <span class="card-title"><i class="fas fa-fire" style="color: #ff9800; margin-right:8px;"></i> Trending Series</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th style="width: 50px;">#</th><th>Title</th><th class="text-right">Views</th></tr></thead>
                                    <tbody>
                                        <?php if(count($trendingSeries) > 0): $i=1; foreach($trendingSeries as $serie): ?>
                                            <tr><td style="color: #888;"><?php echo $i++; ?></td><td><?php echo htmlspecialchars($serie['title']); ?></td><td class="text-right font-weight-bold"><?php echo number_format($serie['views']); ?></td></tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="3" class="text-center">No data available</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card analytics-card">
                        <div class="card-header">
                            <span class="card-title"><i class="fas fa-play-circle" style="color: #9c27b0; margin-right:8px;"></i> Top Episodes</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th style="width: 50px;">#</th><th>Title</th><th class="text-right">Views</th></tr></thead>
                                    <tbody>
                                        <?php if(count($topEpisodes) > 0): $i=1; foreach($topEpisodes as $ep): ?>
                                            <tr><td style="color: #888;"><?php echo $i++; ?></td><td><?php echo htmlspecialchars($ep['title']); ?></td><td class="text-right font-weight-bold"><?php echo number_format($ep['views']); ?></td></tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="3" class="text-center">No data available</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card analytics-card">
                        <div class="card-header">
                             <span class="card-title"><i class="fas fa-globe-africa" style="color: #2196f3; margin-right:8px;"></i> Top Locations</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th> <th>Country</th>
                                            <th class="text-right">Visitors</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($topCountries) > 0): ?>
                                        <?php $i=1; foreach($topCountries as $loc): ?>
                                            <tr>
                                                <td style="color: #888;"><?php echo $i++; ?></td> <td><?php echo htmlspecialchars($loc['country']); ?></td>
                                                <td class="text-right font-weight-bold"><?php echo number_format($loc['count']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="2" class="text-center">No data available</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header modern-header">
                        <div class="modern-title">
                            <i class="fas fa-list-alt"></i> 
                            <span>Detailed View Logs</span>
                        </div>
                    
                        <div class="header-stats">
                            <div class="stat-item">
                                <span class="stats-label">Total Views:</span>
                                <span class="stats-value"><?php echo number_format($totalRows); ?></span>
                            </div>
                    
                            <div class="stat-divider"></div>
                    
                            <div class="stat-item">
                                <span class="stats-label">Unique Users:</span>
                                <span class="stats-value"><?php echo number_format($totalUniqueUsers); ?></span>
                            </div>
                        </div>
                        
                        <form id="filterForm" class="filter-controls" method="GET" action="">
                            <input type="date" name="from" class="filter-input" value="<?php echo htmlspecialchars($fromDate); ?>" title="From Date">
                            <span class="filter-separator">to</span>
                            <input type="date" name="to" class="filter-input" value="<?php echo htmlspecialchars($toDate); ?>" title="To Date">
                            
                            <div class="search-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" id="liveSearchInput" class="filter-input" 
                                       placeholder="Search IP, Country..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                            <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            
                            <a href="analytics.php" class="btn btn-reset">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">#</th> 
                                        <th><a href="#" onclick="sortTable('type')">Type <?php echo ($sort=='type')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                                        <th>Video Title</th>
                                        <th><a href="#" onclick="sortTable('user_ip')">IP Address <?php echo ($sort=='user_ip')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                                        <th><a href="#" onclick="sortTable('country')">Location <?php echo ($sort=='country')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                                        <th>Device / OS</th>
                                        <th><a href="#" onclick="sortTable('viewed_at')">Date & Time <?php echo ($sort=='viewed_at')?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                                    </tr>
                                </thead>
                                <tbody id="analyticsTableBody">
                                    <?php if(count($analyticsData) > 0): ?>
                                        <?php 
                                            // Calculate starting number
                                            $rowNumber = ($page * $limit) - $limit + 1; 
                                        ?>
                                        <?php foreach($analyticsData as $row): ?>
                                        <tr>
                                            <td style="font-weight: bold; color: #777;"><?php echo $rowNumber++; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo ($row['type']=='movie'?'primary':($row['type']=='serie'?'warning':'success')); ?>">
                                                    <?php echo ucfirst($row['type']); ?>
                                                </span>
                                            </td>
                                            <td style="font-weight: 500;">
                                                <?php echo htmlspecialchars($row['video_title'] ?? 'Unknown Title'); ?>
                                            </td>
                                            <td style="font-family: monospace; color: #666;"><?php echo htmlspecialchars($row['user_ip']); ?></td>
                                            <td>
                                                <?php if($row['country'] != 'Unknown'): ?>
                                                    <i class="fas fa-map-marker-alt" style="color: #e91e63; margin-right: 5px;"></i>
                                                    <?php echo htmlspecialchars($row['city'] . ', ' . $row['country']); ?>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-<?php echo ($row['device']=='Mobile'?'mobile-alt':'desktop'); ?>" style="margin-right: 5px;"></i>
                                                <?php echo htmlspecialchars($row['os'] . ' / ' . $row['browser']); ?>
                                            </td>
                                            <td style="font-size: 0.9em; color: #555;">
                                                <?php echo date('M j, Y h:i A', strtotime($row['viewed_at'])); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center" style="padding: 30px;">No records found matching your filters.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                        <div class="pagination-container" id="paginationControls" style="padding: 15px; display: flex; justify-content: flex-end; border-top: 1px solid #eee;">
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo $search; ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-link">&laquo; Prev</a>
                                <?php endif; ?>

                                <?php for($i=1; $i<=$totalPages; $i++): ?>
                                    <?php if ($i == $page || $i == 1 || $i == $totalPages || ($i >= $page-2 && $i <= $page+2)): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-link <?php if($i == $page) echo 'active'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php elseif ($i == $page-3 || $i == $page+3): ?>
                                        <span class="page-dots">...</span>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo $search; ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-link">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
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
        // --- LIVE SEARCH LOGIC ---
        document.getElementById('liveSearchInput').addEventListener('keyup', function() {
            let searchTerm = this.value;
            let pagination = document.getElementById('paginationControls');
            
            // If typing, hide existing pagination to avoid confusion
            // If empty, reload to show original state (with correct sort/filters)
            if(searchTerm.length > 0) {
                if(pagination) pagination.style.display = 'none';
            } else {
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
                document.getElementById('analyticsTableBody').innerHTML = data;
            });
        });

        // Toggle sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        document.getElementById('toggleSidebarclose').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
        });

        document.getElementById('userMenu').addEventListener('click', function() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#userMenu')) {
                document.getElementById('dropdownMenu').classList.remove('show');
            }
        });

        // Sort Table Function (Maintains filters)
        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            let currentOrder = urlParams.get('order') || 'DESC';
            let currentSort = urlParams.get('sort') || 'viewed_at';
            
            let newOrder = (currentSort === column && currentOrder === 'DESC') ? 'ASC' : 'DESC';
            
            urlParams.set('sort', column);
            urlParams.set('order', newOrder);
            
            window.location.search = urlParams.toString();
        }
    </script>
</body>
</html>
<?php }else{
    header('location:way-to-go');
    exit();
}?>