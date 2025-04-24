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
    <link rel="stylesheet" href="designs/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
<style>

:root { --transition-speed: 0.3s; }
body { font-family:'Inter',sans-serif; background:#E3F2F1; }

/* HEADER */
.header {
  background:#DBE8E7; color:#3D5654;
  border-radius:12px 12px 0 0;
  box-shadow:0 2px 8px rgba(0,0,0,0.1);
  height:55px !important; position:fixed; top:0; width:100%; z-index:1000;
  display:flex; align-items:center; justify-content:space-between;
  padding:0 15px;
}
.header-left { display:flex; align-items:center; gap:12px; }
.orglogo      { width:30px; height:30px; }
.header-title { font-size:20px; font-weight:650; }
#toggleBtn    { background:transparent; border:none; font-size:20px; cursor:pointer; }

/* SIDEBAR */
.sidebar {
  width:280px; background:#425C5A;
  position:fixed; top:55px !important; left:0;
  height:calc(100vh - 55px);
  border-top-right-radius:20px;
  border-bottom-right-radius:20px;
  overflow:hidden; transition:left var(--transition-speed);
  z-index:100;
}
.sidebar.sidebar-hidden { left:-280px; }
.admin-profile-area {
  background:#425C5A; padding:30px 20px; text-align:center;
}
.admin-avatar {
  width:80px; height:80px;
  border-radius:50%; background:#D9D9D9;
  margin:0 auto 15px;
  border:2px double #ffd700;
}
.admin-profile-area h3 { color:#fff; margin:0; }
.admin-email { color:#a0aec0; font-size:14px; margin-top:5px; }
.nav-area { background:#3D5654; padding:20px; flex-grow:1; }
.nav-menu { list-style:none; padding:0; margin:0; }
.nav-item { margin:5px 0; }
.nav-item a {
  display:flex; align-items:center; gap:10px;
  padding:12px 15px; border-radius:50px;
  color:#fff; text-decoration:none;
  transition:background .3s,color .3s;
}
.nav-item.active a,
.nav-item:hover a {
  background:#E3F2F1; color:#3D5654;
}
.nav-icon {
  color:#FFD700; font-size:20px; width:24px; text-align:center;
  transition:color .3s;
}
.nav-item.active .nav-icon,
.nav-item:hover .nav-icon {
  color:#3D5654;
}

/* ADJUST MAIN CONTENT */
.main-content {
  margin-top:55px; margin-left:280px;
  padding:2rem; transition:margin-left var(--transition-speed);
}
.main-content.shifted { margin-left:0; }

        .pie-chart {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            position: relative;
            background: <?php echo $total_tasks == 0 ? '#D9D9D9' : "conic-gradient($gradient_string)"; ?>;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }


     
    </style>
</head>
<body>
<header class="header">
  <div class="header-left">
    <button id="toggleBtn" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
    <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo"/>
    <span class="header-title">ORGanize+</span>
  </div>
  <div class="dropdown">
    <button class="btn rounded-circle user-btn text-dark" data-bs-toggle="dropdown">
      <i class="fa-solid fa-user"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="adminsettings.php">Account Settings</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="logout.php">Logout</a></li>
    </ul>
  </div>
</header>

<nav class="sidebar" id="sidebar">
  <div class="admin-profile-area">
    <div class="admin-avatar"></div>
    <h3><?= htmlspecialchars($admin_username) ?></h3>
    <p class="admin-email"><?= htmlspecialchars($admin_email) ?></p>
  </div>
  <div class="nav-area">
    <ul class="nav-menu">
      <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='admin.php'?'active':'' ?>">
        <a href="admin.php"><i class="fas fa-chart-bar nav-icon"></i>Dashboard</a>
      </li>
      <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='adminaccs.php'?'active':'' ?>">
        <a href="adminaccs.php"><i class="fas fa-users nav-icon"></i>Accounts</a>
      </li>
      <li class="nav-item <?= basename($_SERVER['PHP_SELF'])=='admintasks.php'?'active':'' ?>">
        <a href="admintasks.php"><i class="fas fa-flag nav-icon"></i>Tasks</a>
      </li>
    </ul>
  </div>
</nav>

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
        document.getElementById('toggleBtn').onclick = () => {
        document.getElementById('sidebar').classList.toggle('sidebar-hidden');
        document.querySelector('.main-content').classList.toggle('shifted');
        };


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