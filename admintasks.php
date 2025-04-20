<?php
session_start();
include 'config.php';

// Check if user is logged in as admin
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


$status  = $_GET['status'] ?? '';

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
  GROUP BY t.id
  ORDER BY 
    FIELD(t.status,'todo','in_progress','completed','expired'),
    t.due_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$tasks = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORGanize+ Admin - Tasks</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="designs/mytasks.css" />
    <link rel="stylesheet" href="designs/main.css" />

    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    background-color: #E3F2F1;
    display: flex;
    position: relative;
}

.content {
  margin-left: 280px;
  margin-top: 70px;      /* match your fixed header height */
  padding: 30px;
  width: calc(100% - 280px);
  transition: margin-left .3s ease, width .3s ease;
}
.content.shifted {
  margin-left: 0;
  width: 100%;
}


.header {
    background-color: #DBE8E7;
    color: #3D5654;
    padding: 0 15px;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
}

.header-center {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.orglogo {
    height: 30px;
    width: 30px;
    margin-top: 4px;
    margin-left: 1px;
    margin-right: -5px;
}

.header-title {
    font-family: "Inter Tight", sans-serif;
    font-size: 20px;
    font-weight: 650;
    line-height: 50px;
    color: var(--text-color);
}

#sidebarToggle {
    background: transparent;
    color: #3D5654;
    border: none;
    padding: 5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    font-size: 20px;
    transition: all 0.3s;
    margin-right: 0;
    margin-left: -40px;
}

#sidebarToggle i {
    font-weight: 5000;
}

#sidebarToggle:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.sidebar {
    width: 280px;
    background-color: #425C5A;
    height: calc(100vh - 70px);
    padding: 0;
    color: white;
    position: fixed;
    left: 0;
    transition: left 0.3s ease-in-out;
    z-index: 999;
    border-top-right-radius: 20px;
    border-bottom-right-radius: 20px;
    top: 70px;
    display: flex;
    flex-direction: column;
    margin-top: -20px;
}

.sidebar.active {
    left: -280px;
}

.admin-profile-area {
    background-color: #425C5A;
    padding: 30px 20px;
    border-top-right-radius: 20px;
}

.nav-area {
    background-color: #3D5654;
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    border-bottom-right-radius: 20px;
}

.admin-profile {
    text-align: center;
    margin-bottom: 0;
}

.admin-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #D9D9D9;
    margin: 0 auto 15px;
    border: 2px double #ffd700;
    background-image: url('default-avatar.jpg');
    background-size: cover;
}

.admin-email {
    color: #a0aec0;
    font-size: 14px;
    margin-top: 5px;
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    padding: 12px 15px;
    margin: 5px 0;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
    position: relative;
}

.nav-item a {
    color: inherit;
    text-decoration: none;
    display: block;
    width: 100%;
    z-index: 1001;
    pointer-events: auto;
}

.nav-icon {
    color: #FFD700;
    font-size: 20px;
    width: 24px;
    text-align: center;
    transition: all 0.3s ease;
}

.nav-item:hover,
.nav-item.active {
    background-color: #E3F2F1;
    color: #3D5654;
}

.nav-item:hover .nav-icon,
.nav-item.active .nav-icon {
    color: #3D5654;
}

.nav-item.active .nav-icon {
    color: #FFD700;
}

.nav-item i {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    background-color: white;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    overflow: hidden;
    padding: 0;
    margin: 0;
    list-style: none;
    z-index: 1000;
    white-space: nowrap;
    margin-top: 15px;
    margin-right: -25px;
    margin-top: 8px;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    padding: 10px 20px;
    color: #333;
    font-size: 14px;
    text-decoration: none;
    display: block;
    white-space: nowrap;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #f1f1f1;
}

.dropdown-divider {
    margin: 5px 0;
    border-top: 1px solid #eee;
}

.dropdown {
    position: relative;
    display: inline-block;
}

    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="header-center">
            <div class="header-left">
                <button id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo">
                <span class="header-title">ORGanize+</span>
            </div>
            <div class="dropdown" id="userDropdown">
            <button class="btn rounded-circle user-btn text-dark" id="dropdownToggle" type="button">
                    <i class="fa-solid fa-user" style="font-size:20px;"></i>
                </button>
                <ul class="dropdown-menu" id="dropdownMenu">
                    <li><a class="dropdown-item" href="adminsettings.php">Account Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="admin-profile-area">
            <div class="admin-profile">
                <div class="admin-avatar"></div>
                <h3><?php echo htmlspecialchars($admin_username); ?></h3>
                <p class="admin-email"><?php echo htmlspecialchars($admin_email); ?></p>
            </div>
        </div>
        <div class="nav-area">
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span><a href="admin.php">Dashboard</a></span>
                    </li>
                    <li class="nav-item">
                        <i class="fas fa-users nav-icon"></i>
                        <span><a href="adminaccs.php">Accounts</a></span>
                    </li>
                    <li class="nav-item active">
                        <i class="fas fa-flag nav-icon"></i>
                        <span><a href="admintasks.php">Tasks</a></span>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
 <div class="content shifted" id="mainContent">
    <div class="card task-card p-4">
        <!-- Task Filter -->
     <div class="mb-3 d-flex align-items-center justify-content-between">
        <h4 class="mb-0">All User Tasks</h4>
        <select id="statusFilter" class="form-select w-auto" onchange="filterStatus()">
            <option value="">All</option>
            <option value="todo">To Do</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="expired">Expired</option>
        </select>
        </div>

    <!-- Task Table -->
    <div class="table-responsive">
    <table class="minimal-table">
        <thead>
        <tr>
            <th>Task Name</th>
            <th>Collaborators</th>
            <th>Status</th>
            <th>Due Date</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($tasks as $task): ?>
        <?php
          $today = (new DateTime())->setTime(0, 0);
          $dueDate = (new DateTime($task['due_date']))->setTime(0, 0);
          if ($task['status'] === 'todo' && $dueDate < $today) {
            $task['status'] = 'expired';
          }
          $statusClass = str_replace('_', '-', $task['status']);
        ?>
        <tr class="task-row" data-id="<?= $task['id'] ?>" style="cursor: pointer;">
          <td><?= htmlspecialchars($task['title']) ?></td>
          <td>
            <?= $task['collaborator_emails'] ? nl2br(htmlspecialchars($task['collaborator_emails'])) : '<span class="text-muted">—</span>' ?>
          </td>
          <td><span class="badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></td>
          <td><?= htmlspecialchars($task['due_date']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
        </table>
    </div>
    </div>

    </div>
</div>

        <!-- Task Details Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="taskDetailModalLabel">
          <i class="fa fa-info-circle me-2"></i> Task Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="task-meta mb-3">
          <p><strong>Status:</strong> <span id="detail-status" class="badge"></span></p>
          <p><strong>Due Date:</strong> <span id="detail-due-date"></span></p>
        </div>
        <h4 id="detail-title" class="mb-2"></h4>
        <p id="detail-description" class="text-muted"></p>
        <hr/>
        <h6><i class="fa fa-users me-1 text-secondary"></i> Collaborators</h6>
        <ul id="detail-collaborators" class="list-group list-group-flush mt-2"></ul>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

    <script>
    // Sidebar Toggle (outside DOMContentLoaded since it's at bottom of body)
    document.getElementById('sidebarToggle').addEventListener('click', function(e) {
        e.stopPropagation();
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.getElementById('mainContent');
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('shifted');
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Dropdown functionality
        const dropdownToggle = document.getElementById('dropdownToggle');
        const dropdownMenu = document.getElementById('dropdownMenu');

        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!dropdownMenu.contains(e.target) && !dropdownToggle.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });

        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Navigation links handling
        document.querySelectorAll('.nav-item a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('Nav link clicked:', this.href);
                window.location.href = this.href;
            });
        });
    }); // Added missing closing parenthesis and curly brace
</script>

<script>
document.querySelectorAll('.task-row').forEach(row => {
  row.addEventListener('click', async () => {
    const taskId = row.dataset.id;

    try {
      const res = await fetch(`api/get_task.php?id=${taskId}`);
      const task = await res.json();

      // Populate modal content
      document.getElementById('detail-title').textContent = task.title;
      document.getElementById('detail-description').textContent = task.description;

      const statusEl = document.getElementById('detail-status');
      statusEl.textContent = task.status.replace('_',' ');
      statusEl.className = 'badge ' + ({
        todo: 'bg-danger',
        in_progress: 'bg-warning',
        completed: 'bg-success',
        expired: 'bg-secondary'
      })[task.status];

      const date = new Date(task.due_date);
      document.getElementById('detail-due-date').textContent =
        date.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });

      const collabList = document.getElementById('detail-collaborators');
      collabList.innerHTML = '';
      if (task.collaborators.length) {
        task.collaborators.forEach(c => {
          const li = document.createElement('li');
          li.className = 'list-group-item';
          li.innerHTML = `
            <i class="fa fa-user-circle text-secondary"></i>
            <span class="flex-grow-1">${c.email}</span>
            <span class="badge ${c.status==='accepted'?'bg-success':'bg-warning'}">${c.status}</span>`;
          collabList.appendChild(li);
        });
      } else {
        collabList.innerHTML = `<li class="list-group-item text-muted">
          <i class="fa fa-user-slash me-2"></i>No collaborators</li>`;
      }

      new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
    } catch (err) {
      console.error('Error loading task details', err);
    }
  });
});

// Filter by status
document.getElementById('statusFilter').value = "<?= $status ?>";
function filterStatus() {
  const selected = document.getElementById('statusFilter').value;
  window.location.href = "admintasks.php?status=" + selected;
}
</script>



<script src="js/new.js"></script>
</body>
</html>