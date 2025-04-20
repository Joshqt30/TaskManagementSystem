<?php
session_start();
include 'config.php';
include 'partials/task_modal.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

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

$user_id = $_SESSION['user_id'];


// fetch username for sidebar
// now fetching both username and email
$stmtUser = $pdo->prepare("SELECT username, email
                           FROM users
                           WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

$status  = $_GET['status'] ?? '';

// 1) Base SELECT with only WHERE
$sql = "
  SELECT 
    t.id,
    t.user_id, 
    t.title,
    t.status,
    t.due_date,
    GROUP_CONCAT(DISTINCT COALESCE(u2.email, c.email) SEPARATOR ', ') AS collaborator_emails
  FROM tasks t
  LEFT JOIN collaborators c ON t.id = c.task_id
  LEFT JOIN users u2 ON c.user_id = u2.id
  WHERE t.id IN (
    SELECT id   FROM tasks         WHERE user_id = ?
    UNION
    SELECT task_id FROM collaborators WHERE user_id = ? OR email = ?
  )
";

// 2) Collect params for those three placeholders
$params = [
  $user_id,      // for tasks.user_id
  $user_id,      // for collaborators.user_id
  $user['email'] // for collaborators.email
];

if ($status) {
  $sql .= " AND t.status = ?";
  $params[] = $status;
}
$sql .= "
  GROUP BY t.id
  ORDER BY 
    FIELD(t.status,'todo','in_progress','completed','expired'),
    t.due_date ASC
";

// 5) Prepare & execute with the correct number of params
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$tasks = $stmt->fetchAll();

?>



<!-- mytasks.php -->

<!DOCTYPE html> 
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ORGanize+ | My Tasks</title>
  
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/mytasks.css" />
  <link rel="stylesheet" href="designs/main.css" />
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
        <i class="fa-solid fa-user-circle"></i>
        <div class="user-name">
          <?= htmlspecialchars($user['username']) ?> <!-- Only username -->
        </div>
      </div>
        <ul class="nav flex-column sidebar-menu">
          <li class="nav-item">
            <a href="main.php" class="nav-link">
              <i class="fa-solid fa-house me-2"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a href="mytasks.php" class="nav-link active">
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
    <main class="main-content flex-grow-1 p-4">
      <div class="container-fluid">
      
        <!-- Title & Subheading -->
        <div class="mb-3">
          <h1 class="page-heading mb-0">My Tasks</h1>
        </div>      

        <div class="tab-divider"></div>
      
        <!-- Buttons: New Task (left), Date View (right) -->
        <div class="d-flex justify-content-between align-items-center mb-4">
        <button class="btn btn-primary new-task-btn" data-bs-toggle="modal" data-bs-target="#createTaskModal">
          <i class="fa-solid fa-plus me-1"></i> New Task
        </button>
        <div class="mb-3">
        <label for="statusFilter" class="form-label">Filter Status</label>
        <select class="form-select" id="statusFilter" onchange="filterStatus()">
          <option value="">All</option>
          <option value="todo">To Do</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="expired">Expired</option>
        </select>
      </div>
        </div>
  
        <!-- Tasks Table -->
        <div class="table-responsive">
        <table class="minimal-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Collaborators</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($tasks as $task): ?>
          <?php 
            // Convert due_date to DateTime for comparison
            $today = (new DateTime())->setTime(0, 0); // current date at 00:00
            $dueDate = (new DateTime($task['due_date']))->setTime(0, 0); // due date at 00:00
            
            if ($task['status'] === 'todo' && $dueDate < $today) {
              $task['status'] = 'expired';
            }

            $statusClass = str_replace('_', '-', $task['status']);
          ?>
          <tr>
            <td><?= htmlspecialchars($task['title']) ?></td>
            <td>
              <?php if ($task['collaborator_emails']): ?>
                <?= nl2br(htmlspecialchars($task['collaborator_emails'])) ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
            </td>
            <td><?= htmlspecialchars($task['due_date']) ?></td>
            <td>
              <?php if ($task['status'] !== 'expired') : ?>
                <button class="btn btn-sm btn-warning edit-btn" data-id="<?= $task['id'] ?>">Edit</button>
              <?php endif; ?>

              <?php if ($task['user_id'] == $_SESSION['user_id']) : ?>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $task['id'] ?>">Delete</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>

        </table>

        </div>
      
      </div>
    </main>


    <!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
  <div class="modal-dialog">
      <form id="editTaskForm" action="api/update_task.php" method="POST">
      <input type="hidden" name="task_id" id="editTaskId" />

      <div class="mb-3">
        <label for="editTitle">Task Name</label>
        <!-- name must match what your PHP expects: “title” -->
        <input type="text" class="form-control" id="editTitle" name="title" required>
      </div>

      <div class="mb-3">
        <label for="editDescription">Description</label>
        <!-- you need a description field too -->
        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
      </div>

      <div class="mb-3">
        <label for="editDueDate">Due Date</label>
        <input type="date" class="form-control" id="editDueDate" name="due_date" required>
      </div>

      <div class="mb-3">
        <label for="editStatus">Status</label>
        <select class="form-select" id="editStatus" name="status" required>
          <option value="todo">To Do</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="expired">Expired</option>
        </select>
      </div>

      <!-- wrap collaborators in its own section -->
      <div id="collaboratorSection" class="mb-3" style="display:none">
        <label class="form-label">Collaborators</label>
        <div id="editCollaboratorContainer"></div>
        <button type="button" class="btn btn-sm btn-success" onclick="addCollaboratorField('edit')">
          <i class="fas fa-plus"></i> + Add Collaborator
        </button>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>

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
    <!-- Custom JS -->
    <script src="js/new.js"></script>
  </div>
</body>
</html>