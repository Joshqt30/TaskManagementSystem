<?php
session_start();
include 'config.php';

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch admin details
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_username = $admin['username'];
$admin_email = $admin['email'];

// Fetch statistics
// 1. Number of Organizations (distinct organizations in users table)
$stmt = $pdo->query("SELECT COUNT(DISTINCT organization) as org_count FROM users");
$org_count = $stmt->fetch(PDO::FETCH_ASSOC)['org_count'];

// 2. Number of User Accounts
$stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
$user_count = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];

// 3. Number of Tasks Created (assuming there's a tasks table)
$task_count = 0; // Default to 0 if tasks table doesn't exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) as task_count FROM tasks");
    $task_count = $stmt->fetch(PDO::FETCH_ASSOC)['task_count'];
} catch (PDOException $e) {
    // Tasks table might not exist; we'll leave task_count as 0
}

// 4. Task Statistics (fetch all statuses dynamically)
$task_statuses = [];
$total_tasks = 0;
$status_colors = [
    'completed' => '#425C5A', // Dark teal
    'pending' => '#FFD700',   // Gold
    'in_progress' => '#FF6347', // Tomato red
    'cancelled' => '#808080',  // Gray
    'on_hold' => '#4682B4',   // Steel blue
];
$default_color = '#D9D9D9'; // Fallback color for unknown statuses

try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
    $task_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($task_stats as $stat) {
        $status = strtolower($stat['status']);
        $count = $stat['count'];
        $total_tasks += $count;
        
        $task_statuses[$status] = [
            'count' => $count,
            'percentage' => 0, // Will calculate below
            'color' => isset($status_colors[$status]) ? $status_colors[$status] : $default_color,
        ];
    }

    // Calculate percentages
    if ($total_tasks > 0) {
        foreach ($task_statuses as $status => &$data) {
            $data['percentage'] = round(($data['count'] / $total_tasks) * 100);
        }
    }

    // Generate conic-gradient segments
    $gradient_segments = [];
    $current_percentage = 0;
    foreach ($task_statuses as $status => $data) {
        $start = $current_percentage;
        $end = $current_percentage + $data['percentage'];
        $gradient_segments[] = "{$data['color']} {$start}% {$end}%";
        $current_percentage = $end;
    }
    $gradient_string = implode(', ', $gradient_segments);

} catch (PDOException $e) {
    // Tasks table might not exist; defaults will apply
    $gradient_string = $default_color;
}

// 5. Last 7 Days Registrations
$last_7_days = [];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = $date;
    $last_7_days[$date] = 0;
}

try {
    $stmt = $pdo->query("SELECT DATE(created_at) as reg_date, COUNT(*) as count 
                         FROM users 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                         GROUP BY DATE(created_at)");
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($registrations as $reg) {
        $reg_date = $reg['reg_date'];
        if (isset($last_7_days[$reg_date])) {
            $last_7_days[$reg_date] = $reg['count'];
        }
    }
} catch (PDOException $e) {
    // If the query fails (e.g., created_at column doesn't exist), keep $last_7_days as initialized
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORGanize+ Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #E3F2F1;
            display: flex;
            position: relative;
        }

        .header {
            background-color: #DBE8E7;
            color: #3D5654;
            padding: 0 15px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .header-center {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .orglogo {
            height: 30px;
            width: 30px;
            margin-top: 4px;
            margin-left: 1px;
            margin-right: -5px;
        }

        .header-title {
            font-family: "Inter Tight", sans-serif;
            font-size: 20px;
            font-weight: 650;
            line-height: 50px;
            color: var(--text-color);
        }

        #sidebarToggle {
            background: transparent;
            color: #3D5654;
            border: none;
            padding: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            font-size: 20px;
            transition: all 0.3s;
            margin-right: 0;
            margin-left: -50px;
        }

        #sidebarToggle i {
            font-weight: 5000;
        }

        #sidebarToggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        #toggleIcon {
            transition: transform 0.3s ease;
        }

        .sidebar {
            width: 280px;
            background-color: #425C5A;
            height: calc(100vh - 70px);
            padding: 0;
            color: white;
            position: fixed;
            left: 0;
            transition: left 0.3s ease-in-out;
            z-index: 999;
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
            top: 70px;
            display: flex;
            flex-direction: column;
            margin-top: -20px;
        }

        .sidebar.active {
            left: -280px;
        }

        .admin-profile-area {
            background-color: #425C5A;
            padding: 30px 20px;
            border-top-right-radius: 20px;
        }

        .nav-area {
            background-color: #3D5654;
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            border-bottom-right-radius: 20px;
        }

        .admin-profile {
            text-align: center;
            margin-bottom: 0;
        }

        .admin-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #D9D9D9;
            margin: 0 auto 15px;
            border: 2px double #ffd700;
            background-image: url('default-avatar.jpg');
            background-size: cover;
        }

        .admin-email {
            color: #a0aec0;
            font-size: 14px;
            margin-top: 5px;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .nav-item a {
            color: inherit;
            text-decoration: none;
            display: block;
            width: 100%;
        }

        .nav-icon {
            color: #FFD700;
            font-size: 20px;
            width: 24px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .nav-item:hover,
        .nav-item.active {
            background-color: #E3F2F1;
            color: #3D5654;
        }

        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon {
            color: #3D5654;
        }

        .nav-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
            padding: 30px;
            margin-top: 70px;
        }

        .main-content.shifted {
            margin-left: 0;
            width: 100%;
        }

        body {
            overflow-x: hidden;
        }

        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }

        .stat-icon {
            font-size: 20px;
            color: #3c4b5b;
            margin-bottom: 6px;
        }

        .stat-number {
            font-size: 26px;
            font-weight: 600;
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-label {
            color: #718096;
            font-size: 13px;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .dashboard-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .dashboard-card h2 {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .pie-chart-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            margin: 20px 0;
            flex-wrap: wrap;
            flex: 1;
        }

        .pie-chart {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            position: relative;
            background: <?php echo $total_tasks == 0 ? '#D9D9D9' : "conic-gradient($gradient_string)"; ?>;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .chart-legend {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 2px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th, .data-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table th {
            color: #718096;
            font-weight: 500;
        }

        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .pie-chart-container {
                flex-direction: column;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .main-content.shifted {
                margin-left: 0;
                width: 100%;
            }

            .stats-grid {
                flex-direction: column;
                align-items: center;
            }

            .stat-card {
                max-width: 100%;
            }

            .pie-chart {
                width: 160px;
                height: 160px;
            }

            .legend-item {
                font-size: 12px;
            }

            .legend-color {
                width: 8px;
                height: 8px;
            }
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
            padding: 0;
            margin: 0;
            list-style: none;
            z-index: 1000;
            white-space: nowrap;
            margin-top: 15px;
            margin-right: -40px;
            margin-top: 8px;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 10px 20px;
            color: #333;
            font-size: 14px;
            text-decoration: none;
            display: block;
            white-space: nowrap;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f1f1f1;
        }

        .dropdown-divider {
            margin: 5px 0;
            border-top: 1px solid #eee;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .user-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin-right: -50px;
        }

        .user-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-center" style="display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
            <div class="header-left" style="display: flex; gap: 12px; align-items: center;">
                <button id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo" />
                <span class="header-title">ORGanize+</span>
            </div>
            <div class="dropdown" id="userDropdown">
                <button class="btn rounded-circle user-btn text-dark" id="dropdownToggle" type="button">
                    <i class="fa-solid fa-user" style="font-size:20px;"></i>
                </button>
                <ul class="dropdown-menu" id="dropdownMenu">
                    <li><a class="dropdown-item" href="adminsettings.php">Account Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <aside class="sidebar">
        <div class="admin-profile-area">
            <div class="admin-profile">
                <div class="admin-avatar"></div>
                <h3><?php echo htmlspecialchars($admin_username); ?></h3>
                <p class="admin-email"><?php echo htmlspecialchars($admin_email); ?></p>
            </div>
        </div>
        <div class="nav-area">
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item active">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span><a href="admin.php">Dashboard</a></span>
                    </li>
                    <li class="nav-item">
                        <i class="fas fa-users nav-icon"></i>
                        <span><a href="adminaccs.php">Accounts</a></span>
                    </li>
                    <li class="nav-item">
                        <i class="fas fa-flag nav-icon"></i>
                        <span><a href="admintasks.php">Tasks</a></span>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="main-content">
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-user-group stat-icon"></i>
                <div class="stat-number"><?php echo $user_count; ?></div>
                <div class="stat-label">User Accounts</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-list-check stat-icon"></i>
                <div class="stat-number"><?php echo $task_count; ?></div>
                <div class="stat-label">Tasks Created</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-sitemap stat-icon"></i>
                <div class="stat-number"><?php echo $org_count; ?></div>
                <div class="stat-label">Organizations</div>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="dashboard-card">
                <h2>Total Tasks Statistics</h2>
                <div class="pie-chart-container">
                    <div class="pie-chart"></div>
                    <div class="chart-legend">
                        <?php foreach ($task_statuses as $status => $data): ?>
                            <div class="legend-item">
                                <span class="legend-color" style="background: <?php echo $data['color']; ?>;"></span>
                                <span><?php echo ucfirst($status); ?> (<?php echo $data['percentage']; ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>Last 7 Days Registrations</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Registrations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($last_7_days as $date => $count): ?>
                            <tr>
                                <td><?php echo date('M d', strtotime($date)); ?></td>
                                <td><?php echo $count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
            this.classList.toggle('active');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggle = document.getElementById('dropdownToggle');
            const dropdownMenu = document.getElementById('dropdownMenu');
            dropdownToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });
            document.addEventListener('click', function() {
                dropdownMenu.classList.remove('show');
            });
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>