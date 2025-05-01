<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// update last_active timestamp for this user
$pdo
  ->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
  ->execute([ $_SESSION['user_id'] ]);

// Get email early
if (!isset($_SESSION['email'])) {
  $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
  $_SESSION['email'] = $user['email'];
}
$email = $_SESSION['email'];
$user_id = $_SESSION['user_id'];


// Total assigned tasks (as owner or collaborator)
$stmt = $pdo->prepare("
  SELECT COUNT(DISTINCT t.id) AS total
  FROM tasks t
  LEFT JOIN collaborators c ON t.id = c.task_id
  WHERE (
    t.user_id = ?
    OR c.user_id = ?
    OR c.email   = ?
  )
");
$stmt->execute([
  $user_id,
  $user_id,
  $email
]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalTaskCount = $row['total'] ?? 0;


try {
    $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching user data");
}



// Default values (all 0)
$task_stats = [
  'todo'        => 0,
  'in_progress' => 0,
  'completed'   => 0,
  'expired'     => 0
];

// Query task counts per status
$stmt = $pdo->prepare("
  SELECT t.status, COUNT(DISTINCT t.id) AS cnt
  FROM tasks t
  LEFT JOIN collaborators c ON t.id = c.task_id
  WHERE (t.user_id = ? OR c.user_id = ? OR c.email = ?)
  GROUP BY t.status
");
$stmt->execute([$user_id, $user_id, $email]);

while ($row = $stmt->fetch()) {
  $task_stats[$row['status']] = (int)$row['cnt'];
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ORGanize+ | Statistics</title>
  
  <!-- Google Fonts (Inter & Inter Tight) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/Statistics.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />
  

  <script>
    // Pass PHP data to JavaScript
    window.USER_ID = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
    window.chartData = <?= json_encode([
        'labels' => ["To-Do", "In Progress", "Completed", "Expired"],
        'datasets' => [[
            'data' => [
                $task_stats['todo'],
                $task_stats['in_progress'],
                $task_stats['completed'],
                $task_stats['expired'] // Idagdag
            ],
            'backgroundColor' => ["#EA2E2E", "#5BA4E5", "#54D376", "#999999"]
        ]]
    ]) ?>;
  </script>


</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="header-left d-flex align-items-center">
      <button id="toggleBtn" class="btn" type="button">
        <i class="fa-solid fa-bars"></i>  
      </button>
      <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo" />
      <span class="header-title">ORGanize+</span>
    </div>

    <div class="header-right">
      <div class="dropdown">
        <button class="btn rounded-circle user-btn text-dark" type="button" data-bs-toggle="dropdown">
          <i class="fa-solid fa-user" style="font-size:20px;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="settings.php">Account Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a>

        </ul>
      </div>
    </div>
  </header>

  <!-- Wrapper: Sidebar + Main Content -->
  <div class="content-wrapper">
    <!-- Sidebar -->
    <nav class="sidebar sidebar-expanded" id="sidebar">
      <!-- Sidebar Middle: Profile & Navigation Menu -->
      <div class="sidebar-middle">
      <div class="sidebar-profile">
      <?php if (!empty($user['profile_pic'])) : ?>
       <img src="uploads/profile_pics/<?= htmlspecialchars($user['profile_pic']) ?>"
            class="sidebar-profile-pic" 
            alt="Profile Picture">
      <?php else : ?>
        <i class="fa-solid fa-user-circle"></i>
      <?php endif; ?>
      <div class="user-name">
        <?= htmlspecialchars($user['username']) ?>
      </div>
    </div>
        <ul class="nav flex-column sidebar-menu">
          <li class="nav-item">
            <a href="main.php" class="nav-link">
              <i class="fa-solid fa-house me-2"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a href="mytasks.php" class="nav-link">
              <i class="fa-solid fa-check-circle me-2"></i> My Tasks
            </a>
          </li>
          <li class="nav-item">
            <a href="inbox.php" class="nav-link">
              <i class="fa-solid fa-message me-2"></i> Inbox
            </a>
          </li>
          <li class="nav-item">
            <a href="calendar.php" class="nav-link">
              <i class="fa-solid fa-calendar me-2"></i> Calendar
            </a>
          </li>
          <li class="nav-item">
            <a href="Statistics.php" class="nav-link active">
              <i class="fa-solid fa-chart-pie me-2"></i> Tasks Statistics
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <div class="welcome-wrapper mb-4">
        <h4 class="welcome-text">Task Statistics </h4>
        <hr/>
      </div>

      <div class="Tasks">
      <div class="Tasks">
        <div class="comp">
          <p>To Do</p>
          <h4 id="todoCount"><?= $task_stats['todo'] ?></h4>
        </div>

        <div class="comp">
          <p>In Progress</p>
          <h4 id="inProgressCount"><?= $task_stats['in_progress'] ?></h4>
        </div>

        <div class="comp">
          <p>Completed Task</p>
          <h4 id="completedCount"><?= $task_stats['completed'] ?></h4>
        </div>

        <div class="comp">
          <p>Missed Task</p>
          <h4 id="expiredCount"><?= $task_stats['expired'] ?></h4>
        </div>
      </div>

      </div>

   <div class="stats">  

    <div class="custom-container mx-auto">
    <h2 class="custom-title">Total task assigned</h2>
    <div class="card-body-custom">
      <!-- dynamic count -->
      <div class="task-count"><?= $totalTaskCount ?></div>
      <!-- dynamic message -->
      <p class="task-message">
        You have <?= $totalTaskCount ?> tasks assigned. Keep up the momentum or start creating new tasks to stay organized and productive!
      </p>
      <!-- button -->
      <a href="mytasks.php" class="btn btn-custom">
        View your tasks <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>


    <div class="status">
      <p>Tasks Statistics</p>
      <hr />
      <div class="card-body chart-body">
        <div class="chart-container">
          <canvas id="taskGraph"></canvas>
        </div>
        <div class="chart-legend">
        <div class="legend-item">
          <div class="legend-color todo"></div>
          <span>To-Do: <strong><?= $task_stats['todo'] ?></strong></span>
        </div>
        <div class="legend-item">
          <div class="legend-color in-progress"></div>
          <span>In Progress: <strong><?= $task_stats['in_progress'] ?></strong></span>
        </div>
        <div class="legend-item">
          <div class="legend-color completed"></div>
          <span>Completed: <strong><?= $task_stats['completed'] ?></strong></span>
        </div>
        <div class="legend-item">
          <div class="legend-color missed"></div>
          <span>Missed: <strong><?= $task_stats['expired'] ?></strong></span>
        </div>
      </div>

      </div>
    </div>

  </div>  
  
            
       
    </div>
  </div>

            <!-- Logout Confirmation Modal -->
            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to logout?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <form action="logout.php" method="POST">
              <button type="submit" class="btn btn-danger">Yes, Logout</button>
            </form>
          </div>
        </div>
      </div>
    </div>


  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Inject PHP Task Stats into JS -->
  <script>
    window.taskStats = <?= json_encode($task_stats) ?>;
  </script>


  <!-- Custom JS -->
  <script src="js/new.js"></script>
  <script src="js/statistics.js"></script>
</body>
</html>