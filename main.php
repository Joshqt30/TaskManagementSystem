<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';
include 'partials/task_modal.php';

// Get user data
  $user_id = $_SESSION['user_id'];
      try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) {
          // Handle case where user doesn't exist
          session_destroy();
          header("Location: login.php");
          exit;
      }
      } catch (PDOException $e) {
      // Log error and handle appropriately
      die("Error fetching user data");
      }

// Get task statistics for chart
    $task_stats = ['todo' => 0, 'in_progress' => 0, 'completed' => 0];
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch()) {
        $task_stats[$row['status']] = $row['count'];
    }

    // Get latest 5 tasks
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC, due_date ASC LIMIT 5");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll();
    ?>


<!-- main.html -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ORGanize+ | Home</title>
  
  <!-- Google Fonts (Inter & Inter Tight) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/main.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />

  <script>
    // Pass PHP data to JavaScript
    window.USER_ID = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
    window.chartData = <?= json_encode([
        'labels' => ["To-Do", "In Progress", "Completed"],
        'datasets' => [[
            'data' => [
                $task_stats['todo'],
                $task_stats['in_progress'],
                $task_stats['completed']
            ],
            'backgroundColor' => ["#EA2E2E", "#5BA4E5", "#54D376"]
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
    <div class="header-center">
      <ul class="nav header-nav">
        <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>
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
        <div class="user-name">
          <?= htmlspecialchars($user['username']) ?> <!-- Only username -->
        </div>
      </div>
        <ul class="nav flex-column sidebar-menu">
          <li class="nav-item">
            <a href="main.php" class="nav-link active">
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
            <a href="Statistics.php" class="nav-link">
              <i class="fa-solid fa-chart-pie me-2"></i> Tasks Statistics
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <div class="welcome-wrapper mb-4">
      <h4 class="welcome-text">Welcome, <?= htmlspecialchars($user['username']) ?>!</h4>
      </div>
            
      <div class="row g-3">
        <!-- Left Column: My Tasks -->
        <div class="col-lg-7">
          <div class="mytasks-section mb-4">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="section-title">My Tasks</h5>
              <!-- In every page -->
              <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                + Create Task
              </button>
            </div>

            <ul class="nav nav-tabs task-tabs mb-0">
              <li class="nav-item">
                <a class="nav-link active" href="#">To-Do</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">In Progress</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">Completed</a>
              </li>
            </ul>
            <!-- Divider line -->
            <div class="tab-divider"></div>
            <div class="task-body">
            <?php if (!empty($tasks)): ?>
              <?php foreach ($tasks as $task): ?>
                <div class="task-item">
                  <h6><?= htmlspecialchars($task['title']) ?></h6>
                  <p><?= htmlspecialchars($task['description']) ?></p>
                  <div class="task-meta">
                    <span class="badge <?= $task['status'] ?>">
                      <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                    </span>
                    <?php if ($task['due_date']): ?>
                      <span class="due-date">
                        <i class="fas fa-calendar-day"></i>
                        <?= date('M j, Y', strtotime($task['due_date'])) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="no-tasks">
                <i class="fas fa-clipboard-list"></i>
                <p>No tasks found. Start by creating one!</p>
              </div>
            <?php endif; ?>
          </div>
          </div>
        </div>
      
        <!-- Right Column: Tasks Statistics (Chart) -->
        <div class="col-lg-5">
          <div class="card task-card chart-card">
            <div class="card-header">
              <i class="fa-solid fa-chart-pie me-2"></i> Tasks Statistics
            </div>
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
      <!-- end row -->
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Custom JS -->
  <script src="js/new.js"></script>
</body>
</html>