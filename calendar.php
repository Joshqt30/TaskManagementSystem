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
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $_SESSION['email'] = $user['email'];
}

// Get user data
$user_id = $_SESSION['user_id'];
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
    die("Error fetching user data: " . $e->getMessage());
}

// Fetch tasks with due dates for the user, including status
try {
    $stmt = $pdo->prepare("SELECT id, title, description, due_date, status FROM tasks WHERE user_id = ? AND due_date IS NOT NULL");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching tasks: " . $e->getMessage());
}

// Prepare task deadlines for the calendar
$task_deadlines = [];
$current_date = new DateTime(); // Current date: 2025-04-24
foreach ($tasks as $task) {
    $due_date = new DateTime($task['due_date']);
    $is_expired = $due_date < $current_date;
    $task_deadlines[$task['due_date']][] = [
        'title' => htmlspecialchars($task['title']),
        'description' => htmlspecialchars($task['description']),
        'status' => htmlspecialchars($task['status']), // Include the status
        'is_expired' => $is_expired
    ];
}

// Get current date for display
$display_date = $current_date->format('j'); // 24
$display_day = strtoupper($current_date->format('l')); // THURSDAY

// Get selected month and year from query parameters, default to current month/year
$month = isset($_GET['month']) ? (int)$_GET['month'] : $current_date->format('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : $current_date->format('Y');
$month = max(1, min(12, $month)); // Ensure month is between 1 and 12
$display_month = strtoupper(date('M', mktime(0, 0, 0, $month, 1, $year))); // e.g., APR

// Generate calendar data for the selected month
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day = new DateTime("$year-$month-01");
$first_day_of_week = $first_day->format('w'); // 0 (Sunday) to 6 (Saturday)
$calendar_days = [];

// Add padding days from the previous month
$prev_month_days = $first_day_of_week;
$prev_month = $month == 1 ? 12 : $month - 1;
$prev_year = $month == 1 ? $year - 1 : $year;
$days_in_prev_month = cal_days_in_month(CAL_GREGORIAN, $prev_month, $prev_year);
for ($i = $prev_month_days - 1; $i >= 0; $i--) {
    $calendar_days[] = [
        'day' => $days_in_prev_month - $i,
        'is_current_month' => false,
        'date' => sprintf('%04d-%02d-%02d', $prev_year, $prev_month, $days_in_prev_month - $i)
    ];
}

// Add days of the current month
for ($day = 1; $day <= $days_in_month; $day++) {
    $calendar_days[] = [
        'day' => $day,
        'is_current_month' => true,
        'date' => sprintf('%04d-%02d-%02d', $year, $month, $day)
    ];
}

// Add padding days from the next month
$next_month_days = (7 - (count($calendar_days) % 7)) % 7;
$next_month = $month == 12 ? 1 : $month + 1;
$next_year = $month == 12 ? $year + 1 : $year;
for ($day = 1; $day <= $next_month_days; $day++) {
    $calendar_days[] = [
        'day' => $day,
        'is_current_month' => false,
        'date' => sprintf('%04d-%02d-%02d', $next_year, $next_month, $day)
    ];
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="designs/calendar.css" />
  <link rel="stylesheet" href="designs/header-sidebar.css" />
  <link rel="stylesheet" href="designs/mobile.css" />
  <link rel="stylesheet" href="designs/main.css" />

  <style>

    .sidebar-profile {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
      background-color: #425C5A;
      padding: 45px 10px;
      text-align: center;
      margin-bottom: 45px;
      white-space: nowrap;
      transition: all 0.3s ease-in-out;
      overflow: hidden;
    }
    .custom-calendar-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        background-color: #F5F6F5;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .calendar-left-panel {
        flex: 1;
        min-width: 200px;
        background-color: #28A745;
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 300px;
    }
    .calendar-left-panel .date {
        font-size: 4rem;
        font-weight: bold;
        line-height: 1;
    }
    .calendar-left-panel .day {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }
    .calendar-left-panel .time {
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    .calendar-right-panel {
        flex: 2;
        min-width: 300px;
    }
    .year-navigation {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-size: 1rem;
    }
    .year-navigation a {
        color: black;
        text-decoration: none;
        padding: 5px 10px;
        font-size: 1.2rem;
    }
    .year-navigation a:hover {
        color: #28A745;
    }
    .month-tabs {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 0.9rem;
    }
    .month-tabs a {
        text-decoration: none;
        color: black;
        padding: 5px 10px;
        border-radius: 5px;
        transition: background-color 0.3s;
    }
    .month-tabs a.active {
        background-color: #28A745;
        color: white;
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        background-color: white;
        padding: 10px;
        border-radius: 10px;
    }
    .calendar-grid .day-header {
        text-align: center;
        font-weight: bold;
        font-size: 0.9rem;
        padding: 5px;
    }
    .calendar-grid .day-cell {
        position: relative;
        text-align: center;
        padding: 10px;
        font-size: 0.9rem;
        border-radius: 0; /* Ensure no rounded corners for all cells */
        transition: background-color 0.3s;
        cursor: pointer;
    }
    .calendar-grid .day-cell:hover {
        background-color: #e9ecef;
    }
    .calendar-grid .day-cell.not-current-month {
        color: #A9A9A9;
    }
    .calendar-grid .day-cell.current-day {
        background-color: #28A745;
        color: white;
        border-radius: 5px; /* Softened corners for current day */
    }
    .calendar-grid .day-cell.current-day:hover {
        background-color: #218838;
    }
    .calendar-grid .day-cell .deadline-dot {
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .calendar-grid .day-cell .deadline-dot.expired {
        background-color: #6c757d;
    }
    .calendar-grid .day-cell .deadline-dot.upcoming {
        background-color: #dc3545;
    }
    @media (max-width: 576px) {
        .custom-calendar-container {
            flex-direction: column;
        }
        .calendar-left-panel, .calendar-right-panel {
            width: 100%;
        }
        .calendar-left-panel {
            min-height: 200px;
        }
        .calendar-left-panel .date {
            font-size: 3rem;
        }
        .calendar-left-panel .day {
            font-size: 1.2rem;
        }
        .calendar-left-panel .time {
            font-size: 1rem;
        }
    }
    /* Style for task status in the modal */
    .task-status {
        font-size: 0.9rem;
        color: #555;
        font-style: italic;
    }
  </style>
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
            <i class="bi bi-person-circle fs-5 profile-thumbnail"></i>
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
                <div class="custom-calendar-container">
                  <!-- Left Panel -->
                  <div class="calendar-left-panel">
                    <div>
                      <div class="date"><?php echo $display_date; ?></div>
                      <div class="day"><?php echo $display_day; ?></div>
                      <div class="time" id="currentTime"></div>
                    </div>
                  </div>
                  <!-- Right Panel -->
                  <div class="calendar-right-panel">
                    <!-- Year Navigation -->
                    <div class="year-navigation">
                      <a href="calendar.php?month=<?php echo $month; ?>&year=<?php echo $year - 1; ?>" title="Previous Year"><</a>
                      <span><?php echo $year; ?></span>
                      <a href="calendar.php?month=<?php echo $month; ?>&year=<?php echo $year + 1; ?>" title="Next Year">></a>
                    </div>
                    <!-- Month Tabs -->
                    <div class="month-tabs">
                      <?php
                      $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                      foreach ($months as $index => $m) {
                          $month_num = $index + 1;
                          $active = $month_num === $month ? 'active' : '';
                          $href = "calendar.php?month=$month_num&year=$year";
                          echo "<a href='$href' class='$active'>$m</a>";
                      }
                      ?>
                    </div>
                    <div class="calendar-grid">
                      <!-- Days of the Week -->
                      <?php
                      $days_of_week = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
                      foreach ($days_of_week as $day) {
                          echo "<div class='day-header'>$day</div>";
                      }
                      ?>
                      <!-- Calendar Days -->
                      <?php
                      foreach ($calendar_days as $day_data) {
                          $day = $day_data['day'];
                          $is_current_month = $day_data['is_current_month'];
                          $date = $day_data['date'];
                          $is_current_day = $is_current_month && $day == $display_date && $month == $current_date->format('n') && $year == $current_date->format('Y');
                          $class = $is_current_month ? '' : 'not-current-month';
                          if ($is_current_day) {
                              $class .= ' current-day';
                          }
                          echo "<div class='day-cell $class' data-date='$date' data-bs-toggle='modal' data-bs-target='#dateTasksModal'>";
                          echo $day;
                          if (isset($task_deadlines[$date])) {
                              foreach ($task_deadlines[$date] as $task) {
                                  $dot_class = $task['is_expired'] ? 'expired' : 'upcoming';
                                  echo "<div class='deadline-dot $dot_class' title='{$task['title']}'></div>";
                              }
                          }
                          echo "</div>";
                      }
                      ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Date Tasks Modal -->
  <div class="modal fade" id="dateTasksModal" tabindex="-1" aria-labelledby="dateTasksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dateTasksModalLabel">Tasks for <span id="modalDate"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="tasksList"></div>
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
  <!-- Custom JS -->
  <script src="js/new.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateCells = document.querySelectorAll('.day-cell');
        const modalDate = document.getElementById('modalDate');
        const tasksList = document.getElementById('tasksList');
        const currentTimeDisplay = document.getElementById('currentTime');

        // Store tasks data in JavaScript for client-side access
        const tasksData = <?php echo json_encode($task_deadlines); ?>;

        // Function to update the current time in 12-hour format
        function updateTime() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12; // Convert to 12-hour format
            hours = String(hours).padStart(2, '0');
            currentTimeDisplay.textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Handle date cell clicks
        dateCells.forEach(cell => {
            cell.addEventListener('click', function() {
                const selectedDate = this.getAttribute('data-date');
                modalDate.textContent = new Date(selectedDate).toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });

                // Populate tasks for the selected date
                tasksList.innerHTML = '';
                if (tasksData[selectedDate]) {
                    tasksData[selectedDate].forEach(task => {
                        const taskDiv = document.createElement('div');
                        taskDiv.className = 'mb-3';
                        taskDiv.innerHTML = `
                            <strong>${task.title}</strong><br>
                            ${task.description || 'No description'}<br>
                            <span class="task-status">Status: ${task.status}</span>
                        `;
                        tasksList.appendChild(taskDiv);
                    });
                } else {
                    tasksList.innerHTML = '<p>No tasks scheduled for this date.</p>';
                }
            });
        });
    });
  </script>
</body>
</html>