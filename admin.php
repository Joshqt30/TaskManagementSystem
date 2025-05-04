<?php
  session_start();
  include 'config.php';
  // — Auth & last_active update —
  if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin') {
    header("Location: login.php");
    exit;
  }
  $pdo->prepare("UPDATE users SET last_active=NOW() WHERE id=?")
      ->execute([$_SESSION['user_id']]);
  // — Active users —
  $active_now        = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE last_active>=NOW()-INTERVAL 5 MINUTE")->fetchColumn();
  $active_last_hour  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE last_active>=NOW()-INTERVAL 1 HOUR")->fetchColumn();
  // — Summary stats —
  $user_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  $task_count=0;
  try { $task_count = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(); } catch(PDOException $e){}
      
  // — Task status pie data —
  $status_colors = ['todo'=>'#EA2E2E','in_progress'=>'#5BA4E5','completed'=>'#54D376','expired'=>'#999999'];
  $rows = $pdo->query("SELECT status,COUNT(*) c FROM tasks GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
  $total= array_sum(array_column($rows,'c'));
  // -- Completed tasks count --
  $completed_tasks = 0;
  foreach($rows as $r) {
      if($r['status'] === 'completed') {
          $completed_tasks = $r['c'];
          break;
      }
  }
  $gradient_segments=[]; $task_statuses=[];
  $cur=0;
  foreach($rows as $r){
    $p= $total? round($r['c']/$total*100):0;
    $col= $status_colors[$r['status']]??'#D9D9D9';
    $gradient_segments[] = "$col $cur% ".($cur+$p)."%";
    $cur+= $p;
    $task_statuses[$r['status']]=['percentage'=>$p,'color'=>$col];
  }
  $gradient = $gradient_segments? implode(',',$gradient_segments): '#D9D9D9';
  // — Last 7 days regs —
  $last7=[]; for($i=6;$i>=0;$i--){ $d=date('Y-m-d',strtotime("-$i days")); $last7[$d]=0; }
  $regs = $pdo->query("
    SELECT DATE(created_at)d,COUNT(*)c 
    FROM users 
    WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) 
    GROUP BY DATE(created_at)
  ")->fetchAll(PDO::FETCH_ASSOC);
  foreach($regs as $r) $last7[$r['d']]=$r['c'];

  // — Fetch admin for sidebar —
  $stmt=$pdo->prepare("SELECT username, email, profile_pic FROM users WHERE id=?"); // ADD profile_pic
  $stmt->execute([$_SESSION['user_id']]);
  $admin=$stmt->fetch(PDO::FETCH_ASSOC);


  // Get recent activities (last 7 days)
  $activities = $pdo->query("
  SELECT 
      a.*,
      u.username,
      DATE(a.created_at) AS activity_date
  FROM activity_log a
  JOIN users u ON a.user_id = u.id
  WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  ORDER BY a.created_at DESC
  LIMIT 7
  ")->fetchAll(PDO::FETCH_ASSOC);
  // Group activities by date
  $grouped_activities = [];
  foreach($activities as $a) {
  $date = date('Y-m-d', strtotime($a['activity_date']));
  $grouped_activities[$date][] = $a;
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>ORGanize+ Admin Dashboard</title>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;650;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
      <style>
    :root { --transition-speed: .3s; }
    body { font-family:'Inter',sans-serif; background:#E3F2F1; }
    .header { background:#DBE8E7; color:#3D5654; height:55px; position:fixed; top:0; width:100%;
              display:flex; align-items:center; justify-content:space-between; padding:0 15px;
              box-shadow:0 2px 8px rgba(0,0,0,0.1); z-index:1000; }
    .header-left { display:flex; align-items:center; gap:12px; }
    .orglogo { width:30px; height:30px; margin:0; }
    .header-title { font-size:20px; font-weight:650; margin:0; }
    #toggleBtn { background:transparent; border:none; font-size:20px; cursor:pointer; padding:0; margin:0; }
    .sidebar { width:280px; background:#425C5A; position:fixed; top:55px; left:0;
              height:calc(100vh - 55px); border-top-right-radius:20px; border-bottom-right-radius:20px;
              transition:left var(--transition-speed); overflow:hidden; }
    .sidebar.sidebar-hidden { left:-280px; }
    h3 {
      font-size: 22px;
    }
    .admin-profile-area { background:#425C5A; padding:30px 20px; text-align:center; }
    .admin-avatar { width:80px; height:80px; border-radius:50%; background:#D9D9D9;
                    margin:0 auto 15px; border:2px double #ffd700; }
    .admin-profile-area h3 { color:#fff; margin:0; }
    .admin-email { color:#a0aec0; font-size:14px; margin-top:5px; }
    .nav-area { background:#3D5654; padding:20px; }
    .nav-menu { list-style:none; padding:0; margin:0; }
    .nav-item { margin:5px 0; }
    .nav-item a { display:flex; align-items:center; gap:10px; padding:12px 15px;
                  border-radius:50px; color:#fff; text-decoration:none;
                  transition:background .3s,color .3s; }
    .nav-item.active a, .nav-item:hover a { background:#E3F2F1; color:#3D5654; }
    .nav-icon { color:#FFD700; font-size:20px; width:24px; text-align:center; }
    .nav-item.active .nav-icon, .nav-item:hover .nav-icon { color:#3D5654; }
    .main-content { margin-left:280px; width:calc(100% - 280px); padding:30px; margin-top:55px;
                    transition:margin-left var(--transition-speed), width var(--transition-speed); }
    .main-content.shifted { margin-left:0; width:100%; }
    .stats-grid { display:flex; flex-wrap:wrap; gap:20px; justify-content:center; margin-bottom:20px; }
    .stat-card { background:white; padding:20px; border-radius:8px; border:1px solid #e2e8f0;
                flex:1; min-width:200px; max-width:300px; text-align:center; }
    .stat-icon { font-size:20px; color:#3c4b5b; margin-bottom:6px; }
    .stat-number { font-size:26px; font-weight:600; line-height:1; margin-bottom:6px; }
    .stat-label { color:#718096; font-size:13px; }
    /* Add to existing styles */
    .stat-icon-completed { color: #54D376; }  /* Green from pie chart */
    .stat-icon-task { color:#d2b827; }       /* Dark teal from header */
    .stat-icon-user { color: #5BA4E5; }       /* Blue from 'in progress' */
    .stat-icon-theme { color: #FFD700; }      /* Gold accent from nav */
    
    .dashboard-container { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
    .dashboard-card { background:white; padding:15px; border-radius:8px; border:1px solid #e2e8f0; }
    .dashboard-card h2 { font-size:16px; font-weight:600; color:#2d3748; margin-bottom:10px; }
    .pie-chart-container { display:flex; align-items:center; justify-content:center; gap:50px;
                          margin:65px 0; flex-wrap:wrap; flex:1; }
    .pie-chart { width:180px; height:180px; border-radius:50%; position:relative;
                background: <?php echo $total_tasks == 0 ? '#D9D9D9' : "conic-gradient($gradient_string)"; ?>;
                box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    .chart-legend { display:flex; flex-direction:column; gap:10px; justify-content:center; margin-top: -8px; }
    .legend-item { display:flex; align-items:center; gap:8px; font-size:13px; }
    .legend-color { width:10px; height:10px; border-radius:2px; }
    .data-table { width:100%; border-collapse:collapse; font-size:14px; }
    .data-table th, .data-table td { padding:8px; text-align:left; border-bottom:1px solid #e2e8f0; }
    .data-table th { color:#718096; font-weight:500; }
    .dropdown-menu { z-index:1050; }
    .activity-feed { padding:10px; }
.activity-date { 
    color: #4a5568; 
    font-weight: 600;
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 12px;
}
.activity-item {
    display: flex;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f7fafc;
}
.activity-icon {
    width: 32px;
    height: 32px;
    background: #f7fafc;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.activity-icon i { color: #48bb78; }
.activity-content { flex-grow: 1; }
.username { font-weight: 500; color: #2d3748; }
.activity-time { 
    font-size: 0.85rem;
    color: #718096;
    margin-top: 2px;

}.active-users-card {
  /* Para pantay sa kalahati ng Recent Activity height */
  height: 320px; /* adjust mo depende sa actual height ng Recent Activity */
  width: 420px;
  background: white;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.status-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  background: #4CAF50;
  border-radius: 50%;
  margin-right: 10px;
}

.card-title {
  display: flex;
  align-items: center;
  font-weight: 700; /* a bit bolder */
  font-size: 18px;
  color: #3D5654; /* subtle dark gray/green */
  border-bottom: 1px solid #CFD8D7;
  padding-bottom: 10px;
}


.active-users-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 15px;
  padding: 0 20px;
}
.active-user-stat {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
}
.stat-label {
  font-weight: 500;
  color: #3D5654;
  font-size: 14px;
}
.stat-value {
  font-weight: 600;
  color: #3D5654;
  font-size: 18px;
}

.admin-avatar {
  position: relative;
  overflow: hidden;
}

.admin-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.default-avatar {
  font-size: 80px;
  color: #D9D9D9;
}

/* Add these styles to the existing CSS */
.user-profile-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-thumbnail {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #3D5654;
  transition: transform 0.2s;
}

.dropdown-toggle::after {
  display: none; /* Hide default Bootstrap caret */
}

.caret-icon {
  color: #3D5654;
  transition: transform 0.2s;
}

.user-btn {
  background: transparent !important;
  border: none !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center;
  gap: 6px;
}

.user-btn:hover .profile-thumbnail {
  transform: scale(1.1);
}

.user-btn:hover .caret-icon {
  transform: translateY(1px);
}

.dropdown-menu {
  margin-top: 8px !important;
  border: 1px solid #DBE8E7 !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  font-size: 14px;
}
    @media (max-width: 1024px) {
      .dashboard-container { grid-template-columns:1fr; }
      .pie-chart-container { flex-direction:column; gap:20px; }
    }
    @media (max-width: 768px) {
      .main-content { margin-left:0; width:100%; }
      .main-content.shifted { margin-left:0; width:100%; }
      .stats-grid { flex-direction:column; align-items:center; }
      .stat-card { max-width:100%; }
      .pie-chart { width:160px; height:160px; }
      .legend-item { font-size:12px; }
      .legend-color { width:8px; height:8px; }
    }
  </style>
  </head>
  <body>
  <!-- HEADER -->
  <header class="header">
    <div class="header-left">
      <button id="toggleBtn"><i class="fa-solid fa-bars"></i></button>
      <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo"/>
      <span class="header-title">ORGanize+</span>
    </div>

    <div class="dropdown">
  <button class="btn user-btn text-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    <div class="user-profile-wrapper">
      <?php if(!empty($admin['profile_pic'])): ?>
        <img src="uploads/profile_pics/<?= htmlspecialchars($admin['profile_pic']) ?>" 
             class="profile-thumbnail"
             alt="Profile">
      <?php else: ?>
        <i class="bi bi-person-circle fs-5 profile-thumbnail"></i>
      <?php endif; ?>
      <i class="bi bi-chevron-down caret-icon fs-6"></i>
    </div>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><a class="dropdown-item" href="adminsettings.php">Account Settings</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
  </ul>
</div>

    
  </header>
  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">

    <div class="admin-profile-area">
      <div class="admin-avatar">
          <?php if(!empty($admin['profile_pic'])): ?>
          <img src="uploads/profile_pics/<?= htmlspecialchars($admin['profile_pic']) ?>" 
              alt="Profile Picture"
              class="admin-avatar-img">
        <?php else: ?>
          <i class="fa-solid fa-user-circle default-avatar"></i>
        <?php endif; ?>
      </div>
      <h3><?= htmlspecialchars($admin['username']) ?></h3>
      <p class="admin-email"><?= htmlspecialchars($admin['email']) ?></p>
    </div>


    <div class="nav-area">
      <ul class="nav-menu">
        <li class="nav-item active"><a href="admin.php"><i class="fas fa-chart-bar nav-icon"></i>Dashboard</a></li>
        <li class="nav-item"><a href="adminaccs.php"><i class="fas fa-users nav-icon"></i>Accounts</a></li>
        <li class="nav-item"><a href="admintasks.php"><i class="fas fa-flag nav-icon"></i>Tasks</a></li>
      </ul>
    </div>
  </nav>
  <main class="main-content" id="mainContent">
      <div class="stats-grid">
      <div class="stat-card">
        <i class="fas fa-user-group stat-icon stat-icon-user"></i>
        <div class="stat-number"><?php echo $user_count; ?></div>
        <div class="stat-label">User Accounts</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-list-check stat-icon stat-icon-task"></i>
        <div class="stat-number"><?php echo $task_count; ?></div>
        <div class="stat-label">Tasks Created</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-clipboard-check stat-icon stat-icon-completed"></i>
        <div class="stat-number"><?php echo $completed_tasks; ?></div>
        <div class="stat-label">Total Completed Tasks</div>
      </div>
    </div>
  <!-- row 1: Last 7 Days Registrations + Pie Chart -->
  <div class="dashboard-container row-1">
    <div class="dashboard-card">
      <h2>Last 7 Days Registrations</h2>
      <table class="data-table">
        <thead>
          <tr><th>Date</th><th>Registrations</th></tr>
        </thead>
        <tbody>
          <?php foreach($last7 as $d=>$c): ?>
            <tr>
              <td><?= date('M d',strtotime($d)) ?></td>
              <td><?= $c ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="dashboard-card">
      <h2>Total Tasks Statistics</h2>
      <div class="pie-chart-container">
        <div class="pie-chart" style="background:conic-gradient(<?= $gradient ?>)"></div>
        <div class="chart-legend">
          <?php foreach($task_statuses as $s=>$d): ?>
            <div class="legend-item">
              <span class="legend-color" style="background:<?=$d['color']?>"></span>
              <?= ucfirst($s) ?> (<?= $d['percentage'] ?>%)
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- row 2: Active Users + Recent Act -->
  <div class="dashboard-container row-2">
  <div class="dashboard-card">
      <h2>Recent Activity</h2>
      <div class="activity-feed">
        <?php foreach($grouped_activities as $date => $items): ?>
            <div class="activity-day">
                <div class="activity-date"><?= date('l, F j', strtotime($date)) ?></div>
                
                <?php foreach($items as $item): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $item['activity_type'] ?>">
                            <?php switch($item['activity_type']):
                                case 'registration': ?>
                                    <i class="fas fa-user-plus"></i>
                                    <?php break; ?>
                                <?php case 'task_create': ?>
                                    <i class="fas fa-clipboard-list"></i>
                                    <?php break; ?>
                                <?php case 'task_complete': ?>
                                    <i class="fas fa-check-circle"></i>
                                    <?php break; ?>
                            <?php endswitch; ?>
                        </div>
                        <div class="activity-content">
                            <span class="username"><?= $item['username'] ?></span>
                            <?= $item['description'] ?>
                            <div class="activity-time">
                                <?= date('h:i A', strtotime($item['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
      
      <div class="text-center mt-3">
          <a href="#viewAllActivities" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal">
              View All Activity
          </a>
      </div>
  </div>
      <div class="dashboard-card active-users-card">
      <h2 class="card-title">
        <span class="status-dot"></span>
        Active Users
      </h2>
      <div class="active-users-list">
        <div class="active-user-stat">
          <span class="stat-label">Active Now</span>
          <span class="stat-value"><?= $active_now ?></span>
        </div>
        <div class="active-user-stat">
          <span class="stat-label">Last Hour</span>
          <span class="stat-value"><?= $active_last_hour ?></span>
        </div>
      </div>
    </div>

  </div>
  </main>
  <!-- Modal -->
<div class="modal fade" id="viewAllActivities">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Full Activity Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="activity-feed" style="max-height: 70vh; overflow-y: auto;">
                    <?php 
                    // Get ALL activities (not just 7 days)
                    $all_activities = $pdo->query("
                        SELECT 
                            a.*,
                            u.username,
                            DATE(a.created_at) AS activity_date
                        FROM activity_log a
                        JOIN users u ON a.user_id = u.id
                        ORDER BY a.created_at DESC
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Group all activities by date
                    $grouped_all = [];
                    foreach($all_activities as $a) {
                        $date = date('Y-m-d', strtotime($a['activity_date']));
                        $grouped_all[$date][] = $a;
                    }
                    
                    foreach($grouped_all as $date => $items): 
                    ?>
                        <div class="activity-day">
                            <div class="activity-date"><?= date('l, F j', strtotime($date)) ?></div>
                            
                            <?php foreach($items as $item): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php switch($item['activity_type']):
                                        case 'registration': ?>
                                            <i class="fas fa-user-plus"></i>
                                        <?php break; ?>
                                        <?php case 'task_create': ?>
                                          <i class="fas fa-clipboard-list"></i>
                                        <?php break; ?>
                                        <?php case 'task_complete': ?>
                                            <i class="fas fa-check-circle"></i>
                                        <?php break; ?>
                                    <?php endswitch; ?>
                                </div>
                                <div class="activity-content">
                                    <span class="username"><?= htmlspecialchars($item['username']) ?></span>
                                    <?= htmlspecialchars($item['description']) ?>
                                    <div class="activity-time">
                                        <?= date('h:i A', strtotime($item['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    document.getElementById('toggleBtn').onclick = () => {
      document.getElementById('sidebar').classList.toggle('sidebar-hidden');
      document.getElementById('mainContent').classList.toggle('shifted');
    };
  });
  </script>
  </body>
  </html>