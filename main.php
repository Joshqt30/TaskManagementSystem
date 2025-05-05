<?php
session_start();
include 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo
  ->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
  ->execute([ $_SESSION['user_id'] ]);

// Ensure email is set in the session
if (!isset($_SESSION['email'])) {
  // Fetch email from the database if missing
  $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
  $_SESSION['email'] = $user['email']; 
}


include 'partials/task_modal.php';

// Get user data
  $user_id = $_SESSION['user_id'];
      try {
        $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
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
  // New task statistics query (add this)
  $task_stats = ['todo' => 0, 'in_progress' => 0, 'completed' => 0, 'expired' => 0];
  $stmt = $pdo->prepare("
      SELECT t.status, COUNT(DISTINCT t.id) as count 
      FROM tasks t
      LEFT JOIN collaborators c ON t.id = c.task_id
      WHERE 
          (t.user_id = ? OR c.user_id = ? OR c.email = ?)
      GROUP BY t.status
  ");
  $stmt->execute([$user_id, $user_id, $_SESSION['email']]);
  while ($row = $stmt->fetch()) {
      $task_stats[$row['status']] = $row['count'];
  }

// I-update ang status ng overdue tasks
$update = $pdo->prepare("
    UPDATE tasks 
    SET status = 'expired' 
    WHERE user_id = :uid 
    AND (status = 'todo' OR status = 'in_progress') 
    AND DATE(due_date) < CURDATE()
");
$update->execute(['uid' => $_SESSION['user_id']]);


// ====== NEW CODE STARTS HERE ======
// Fetch tasks (owned by user OR where user is collaborator)
$sql = "
    SELECT DISTINCT t.* 
    FROM tasks t
    LEFT JOIN collaborators c ON t.id = c.task_id
    WHERE 
        (t.user_id = ? OR c.user_id = ? OR c.email = ?)
        AND t.status IN ('todo', 'in_progress', 'completed', 'expired') 
    ORDER BY 
        CASE t.status
            WHEN 'todo' THEN 1
            WHEN 'in_progress' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'expired' THEN 4
        END, 
        t.due_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id, $_SESSION['email']]);
$tasks = $stmt->fetchAll();
// ====== NEW CODE ENDS HERE ======
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/main.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />

  <script>
    // Pass PHP data to JavaScript
    window.USER_ID = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
    window.chartData = <?= json_encode([
        'labels' => ["To-Do", "In Progress", "Completed", "Missed"],
        'datasets' => [[
            'data' => [
                $task_stats['todo'],
                $task_stats['in_progress'],
                $task_stats['completed'],
                $task_stats['expired'] 
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
      <button class="btn user-btn text-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-profile-wrapper">
          <?php if(!empty($user['profile_pic'])): ?>
            <img src="uploads/profile_pics/<?= htmlspecialchars($user['profile_pic']) ?>" 
                class="profile-thumbnail"
                alt="Profile">
          <?php else: ?>
            <i class="fa-solid fa-user-circle profile-thumbnail"></i>
          <?php endif; ?>
          <i class="bi bi-chevron-down caret-icon fs-6"></i>
        </div>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="settings.php">Account Settings</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
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
        <i class="fa-solid fa-user-circle sidebar-profile-pic"></i>
      <?php endif; ?>
      <div class="user-name">
        <?= htmlspecialchars($user['username']) ?>
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
           <!-- In the My Tasks section -->
          <div class="mytasks-section mb-4">
              <div class="d-flex align-items-center justify-content-between mb-4">
                  <h5 class="section-title">My Tasks</h5>
                  <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                      + Create Task
                  </button>
              </div>

              <!-- Tabs -->
              <ul class="nav nav-tabs task-tabs mb-4">
                  <li class="nav-item">
                      <a class="nav-link active" href="#" data-status="todo">To-Do</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="#" data-status="in_progress">In Progress</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link" href="#" data-status="completed">Completed</a>
                  </li>
                  
                  <li class="nav-item">
                      <a class="nav-link" href="#" data-status="expired">Expired</a>
                 </li>
              </ul>

              <!-- Task Lists -->
              <div class="task-lists">
                  <!-- To-Do Tasks -->
                  <div class="task-group todo-group show">
                      <?php foreach ($tasks as $task): ?>
                          <?php if ($task['status'] === 'todo'): ?>
                        <div class="task-item" data-task-id="<?= $task['id'] ?>">
                        <div class="task-bullet todo"></div>
                        <div class="task-content">
                          <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                          <div class="task-meta">
                            <span class="task-date">
                              <i class="fas fa-calendar-day"></i>
                              <?= date('M j, Y', strtotime($task['due_date'])) ?>
                            </span>
                          </div>
                        </div>
                      </div>
                          <?php endif; ?>
                      <?php endforeach; ?>
                  </div>

                <!-- In Progress Tasks -->
                <div class="task-group in_progress-group">
                    <?php foreach ($tasks as $task): ?>
                        <?php if ($task['status'] === 'in_progress'): ?>
                            <div class="task-item" data-task-id="<?= $task['id'] ?>">
                                <div class="task-bullet in_progress"></div>
                                <div class="task-content">
                                    <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                    <div class="task-meta">
                                        <span class="task-date">
                                            <i class="fas fa-calendar-day"></i>
                                            <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

              <!-- Completed Tasks -->
              <div class="task-group completed-group">
                  <?php foreach ($tasks as $task): ?>
                      <?php if ($task['status'] === 'completed'): ?>
                          <div class="task-item" data-task-id="<?= $task['id'] ?>">
                              <div class="task-bullet completed"></div>
                              <div class="task-content">
                                  <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                  <div class="task-meta">
                                      <span class="task-date">
                                          <i class="fas fa-calendar-day"></i>
                                          <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                      </span>
                                  </div>
                              </div>
                          </div>
                      <?php endif; ?>
                  <?php endforeach; ?>
              </div>

              <div class="task-group expired-group">
            <?php foreach ($tasks as $task): ?>
                <?php if ($task['status'] === 'expired'): ?>
                    <div class="task-item" data-task-id="<?= $task['id'] ?>">
                        <div class="task-bullet expired"></div>
                        <div class="task-content">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="task-meta">
                                <span class="task-date text-danger">
                                    <i class="fas fa-calendar-day"></i>
                                    <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>


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
              <div class="chart-legend"></div>
            </div>
          </div>
        </div>
      </div>
      <!-- end row -->
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
  <!-- Custom JS -->
  <script src="js/new.js"></script>
</body>
</html>