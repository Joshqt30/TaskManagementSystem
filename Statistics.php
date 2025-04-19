<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['email'])) {
  $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
  $_SESSION['email'] = $user['email'];
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
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


$user_id = $_SESSION['user_id'];
$email   = $_SESSION['email'];

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
  <link rel="stylesheet" href="designs/transition.css" />
  <link rel="stylesheet" href="designs/Statistics.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />
  
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
    <div class="header-center">
      <ul class="nav header-nav">
        <li class="nav-item"><a class="nav-link" href="#">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
      </ul>
    </div>
    <div class="header-right">
      <div class="dropdown">
        <button class="btn rounded-circle user-btn text-dark" type="button" data-bs-toggle="dropdown">
          <i class="fa-solid fa-user" style="font-size:20px;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="settings.php">Account Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="login.php">Logout</a></li>
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
          <i class="fa-solid fa-user-circle"></i>
          <!-- Dynamic username placeholder -->
          <div class="user-name">nameforbackend@gmail.com</div>
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
        <div class="comp">
          <p>Completed Task</p>
      
        </div>

        <div class="comp">
          <p>In Progress</p>
           
        </div>

        <div class="comp">
          <p>Overdue Task</p>
           
        </div>

        <div class="comp">
          <p>Total Task</p>
           
        </div>

      </div>

   <div class="stats">  

    <div class="assign">
      <p>Total task assigned</p>
      <hr  />
    </div>

    <div class="status">
      <p>Tasks Statistics</p>
      <hr />
      <div class="card-body chart-body">
        <div class="chart-container">
          <canvas id="taskGraph"></canvas>
        </div>
        <div class="chart-legend">
          <div class="legend-item"><span class="legend-color todo"></span> To-Do</div>
          <div class="legend-item"><span class="legend-color in-progress"></span> In Progress</div>
          <div class="legend-item"><span class="legend-color completed"></span> Completed</div>
        </div>
      </div>
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
    const taskStats = <?= json_encode($task_stats) ?>;
  </script>

  <!-- Custom JS -->
  <script src="js/new.js"></script>
</body>
</html>