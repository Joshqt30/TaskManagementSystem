<?php
session_start();
include 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ensure email is set in the session
if (!isset($_SESSION['email'])) {
  // Fetch email from the database if missing
  $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
  $_SESSION['email'] = $user['email']; 
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ORGanize+ | Calendar</title>
  
  <!-- Google Fonts (Inter & Inter Tight) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/calendar.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/main.css" />

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
          <!-- Dynamic username placeholder -->
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
            <a href="calendar.php" class="nav-link active">
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
      <div class="calendar-header mb-3">
        <h4 class="page-title">Calendar</h4>
      </div>
            
      <div class="row">
        <!-- Calendar Container -->
        <div class="col-12">
          <div class="card calendar-card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <i class="fa-solid fa-calendar me-2"></i> My Calendar
              </div>
            </div>
            <div class="card-body">
              <div class="calendar-container">
                <iframe src="https://calendar.google.com/calendar/embed?height=500&wkst=1&bgcolor=%23ffffff&ctz=Asia%2FManila&showTitle=0&showPrint=0&showCalendars=1" style="border:none;width:100%;height:500px;" frameborder="0"></iframe>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Custom JS -->
  <script src="js/new.js"></script>
</body>
</html>