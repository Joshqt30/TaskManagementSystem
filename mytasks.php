<!-- mytasks.html -->
<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';
include 'partials/task_modal.php';
  
?>


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
  <link rel="stylesheet" href="designs/transition.css" />
  <link rel="stylesheet" href="designs/mytasks.css" />
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
    <main class="main-content flex-grow-1 p-4">
      <div class="container-fluid">
      
        <!-- Title & Subheading -->
        <div class="mb-3">
          <h1 class="page-heading mb-0">My Tasks</h1>
        </div>      

        <div class="tab-divider"></div>
      
        <!-- Buttons: New Task (left), Date View (right) -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <button class="btn btn-primary new-task-btn">
            <i class="fa-solid fa-plus me-1"></i> New Task
          </button>
          <button class="btn btn-secondary date-view-btn">
            <i class="fa-solid fa-calendar-days me-1"></i> Date View
          </button>
        </div>
  
        <!-- Tasks Table -->
        <div class="table-responsive">
          <table class="minimal-table">
            <thead>
              <tr>
                <th style="width: 40%;">Name</th>
                <th style="width: 30%;">Date <i class="fa-solid fa-sort ms-1"></i></th>
                <th style="width: 30%;">Status</th>
              </tr>
            </thead>
            <tbody>
              <!-- "Past Dates" Subheader Row with chevron -->
              <tr class="table-light">
                <td colspan="3">
                  <i class="fa-solid fa-chevron-down me-2"></i>Past Dates (1 item)
                </td>
              </tr>
  
              <!-- Example Task Row / Replace once backend logic is ready -->
              <tr>
                <td>Example Task</td>
                <td>
                  <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                  <span class="text-danger">April 1, 2025</span>
                </td>
                <td>
                  <span class="badge in-progress">In progress</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      
      </div>
    </main>

          <!-- Logout Confirmation Modal -->
          <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
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