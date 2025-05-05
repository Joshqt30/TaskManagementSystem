<?php
session_start();
include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// update last_active
$admin_id = $_SESSION['user_id'];
$pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
    ->execute([$admin_id]);

// Fetch admin details
$stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_username = htmlspecialchars($admin['username']);
$admin_email    = htmlspecialchars($admin['email']);
$admin_profile_pic = $admin['profile_pic'] ?? null; // Add this line


// Status filter
$status = $_GET['status'] ?? '';

// Fetch tasks + collaborator emails
$sql = "
SELECT 
    t.id,
    t.title,
    t.status,
    t.due_date,
    GROUP_CONCAT(DISTINCT COALESCE(u2.email, c.email) SEPARATOR ', ') AS collaborator_emails
FROM tasks t
LEFT JOIN collaborators c ON t.id = c.task_id
LEFT JOIN users u2    ON c.user_id = u2.id
GROUP BY t.id
ORDER BY FIELD(t.status,'todo','in_progress','completed','expired'), t.due_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add CSS-friendly status class
foreach ($tasks as &$task) {
    $task['statusClass'] = str_replace('_','-',$task['status']);
}
unset($task);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ORGanize+ Admin – Tasks</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root { --transition-speed: 0.3s; }
    body { font-family:'Inter',sans-serif; background:#E3F2F1; }

    /* --------- HEADER --------- */
    .header {
      background:#DBE8E7; color:#3D5654;
      border-radius:12px 12px 0 0;
      box-shadow:0 2px 8px rgba(0,0,0,0.1);
      height:55px; position:fixed; top:0; width:100%; z-index:1000;
      display:flex; align-items:center; justify-content:space-between;
      padding:0 15px;
    }
    .header-left { display:flex; align-items:center; gap:12px; }
    .orglogo      { width:30px; height: 30px; margin: 0; }
    .header-title { font-size:20px; font-weight:650; }
    #toggleBtn    { background:transparent; border:none; font-size:20px; cursor:pointer; margin:0;  padding:0; }

    /* --------- SIDEBAR --------- */
    .sidebar {
      width:280px; background:#425C5A;
      position:fixed; top:55px; left:0;
      height:calc(100vh - 55px);
      border-top-right-radius:20px;
      border-bottom-right-radius:20px;
      overflow:hidden; transition:left var(--transition-speed);
      z-index:100;
    }
    .sidebar.sidebar-hidden { left:-280px; }

    h3 {
        font-size: 22px;
    }

    /* profile area (dark) */
    .admin-profile-area {
      background:#425C5A; padding:30px 20px; text-align:center;
    }
    .admin-avatar {
      width:80px; height:80px;
      border-radius:50%; background:#D9D9D9;
      margin:0 auto 15px;
      border:2px double #ffd700;
      /* background-image:url('default-avatar.jpg'); background-size:cover; */
    }
    .admin-profile-area h3 { color:#fff; margin:0; }
    .admin-email { color:#a0aec0; font-size:14px; margin-top:5px; }

    /* nav area (lighter) */
    .nav-area {
      background:#3D5654; padding:20px; flex-grow:1;
    }
    .nav-menu { list-style:none; padding:0; margin:0; }
    .nav-item { margin:5px 0; }
    .nav-item a {
      display:flex; align-items:center; gap:10px;
      padding:12px 15px; border-radius:50px;
      color:#fff; text-decoration:none;
      transition:background .3s,color .3s;
    }
    .nav-item.active a,
    .nav-item:hover a {
      background:#E3F2F1; color:#3D5654;
    }
    .nav-icon {
      color:#FFD700; font-size:20px; width:24px; text-align:center;
      transition:color .3s;
    }
    .nav-item.active .nav-icon,
    .nav-item:hover .nav-icon {
      color:#3D5654;
    }

    /* --------- MAIN CONTENT --------- */
    .main-content {
      margin-top:55px; margin-left:280px;
      padding:2rem; transition:margin-left var(--transition-speed);
    }
    .main-content.shifted { margin-left:0; }

    /* --------- TASK TABLE --------- */
    .minimal-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 12px;
  margin: 1.5rem 0;
}

.minimal-table thead th {
  background: #425C5A;
  color: #fff;
  padding: 16px 24px;
  font-weight: 600;
  font-size: 0.9rem;
  letter-spacing: 0.5px;
  border: none;
  position: sticky;
  top: 55px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.minimal-table tbody td {
  background: #fff;
  padding: 18px 24px;
  border: none;
  vertical-align: middle;
  transition: all 0.2s ease;
  position: relative;
}

.minimal-table tbody tr {
  cursor: pointer;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.2s ease;
}

.minimal-table tbody tr:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(61,86,84,0.1);
}

.minimal-table tbody tr:hover td {
  background: #F8FAFA;
}

/* Status badges */
.badge {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.badge.todo        { background: #FFEBEE; color: #D32F2F; }
.badge.in-progress { background: #E3F2FD; color: #1976D2; }
.badge.completed   { background: #E8F5E9; color: #2E7D32; }
.badge.expired     { background: #F5F5F5; color: #616161; }

/* Collaborator list */
.collaborator-list {
  max-width: 250px;
  line-height: 1.4;
  font-size: 0.95rem;
  color: #546E7A;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Due date styling */
.minimal-table td:nth-child(3) {
  font-weight: 500;
  color: #3D5654;
}

/* Header alignment */
.minimal-table th:nth-child(1) { width: 30%; }
.minimal-table th:nth-child(2) { width: 35%; }
.minimal-table th:nth-child(3) { width: 20%; }
.minimal-table th:nth-child(4) { width: 15%; text-align: right; }

/* Cell alignment */
.minimal-table td:last-child {
  text-align: right;
  padding-right: 32px;
}

/* Hover effect for collaborator list */
.collaborator-list:hover {
  white-space: normal;
  overflow: visible;
  position: relative;
  z-index: 2;
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  padding: 8px;
  border-radius: 6px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .minimal-table thead { display: none; }
  .minimal-table tbody td {
    display: block;
    text-align: right;
    padding: 12px 16px;
  }
  
  .minimal-table tbody td::before {
    content: attr(data-label);
    float: left;
    font-weight: 600;
    color: #3D5654;
    margin-right: auto;
  }
  
  .minimal-table tbody tr {
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  
  .minimal-table tbody td:last-child {
    border-radius: 0 0 12px 12px;
  }
}
  .user-profile-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-thumbnail {
  width: 33px;
  height: 33px;
  border-radius: 50%;
  object-fit: cover;
  transition: transform 0.2s;
}

/* Add to existing styles */
.bi-person-circle.profile-thumbnail {
  font-size: 33px !important; /* Match container size */
  display: flex !important;
  align-items: center;
  justify-content: center;
}


.dropdown-toggle::after {
  display: none !important;
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
  font-size: 14px !important;
}

/* Update admin avatar styling */
.admin-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px double #ffd700;
  margin: 0 auto 15px;
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
      <?php if(!empty($admin_profile_pic)): ?>
        <img src="uploads/profile_pics/<?= htmlspecialchars($admin_profile_pic) ?>" 
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
      <?php if(!empty($admin_profile_pic)): ?>
        <img src="uploads/profile_pics/<?= htmlspecialchars($admin_profile_pic) ?>" 
            class="admin-avatar-img"
            alt="Profile Picture">
      <?php else: ?>
        <i class="fa-solid fa-user-circle default-avatar"></i>
      <?php endif; ?>
    </div>
    <h3><?= $admin_username ?></h3>
    <p class="admin-email"><?= $admin_email ?></p>
  </div>
    <div class="nav-area">
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="admin.php"><i class="fas fa-chart-bar nav-icon"></i>Dashboard</a>
        </li>
        <li class="nav-item">
          <a href="adminaccs.php"><i class="fas fa-users nav-icon"></i>Accounts</a>
        </li>
        <li class="nav-item active">
          <a href="admintasks.php"><i class="fas fa-flag nav-icon"></i>Tasks</a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- MAIN CONTENT: TASKS -->
  <div class="main-content" id="mainContent">
    <div class="card bg-white p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">All User Tasks</h4>
        <select id="statusFilter" class="form-select w-auto">
          <option value=""            <?= $status===''           ? 'selected' : '' ?>>All</option>
          <option value="todo"        <?= $status==='todo'       ? 'selected' : '' ?>>To Do</option>
          <option value="in_progress" <?= $status==='in_progress'? 'selected' : '' ?>>In Progress</option>
          <option value="completed"   <?= $status==='completed'  ? 'selected' : '' ?>>Completed</option>
          <option value="expired"     <?= $status==='expired'    ? 'selected' : '' ?>>Expired</option>
        </select>
      </div>

      <div class="table-responsive">
        <table class="minimal-table">
          <thead>
            <tr>
              <th>Task Name</th>
              <th>Collaborators</th>
              <th>Due Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($tasks as $t): ?>
              <tr class="task-row" data-id="<?= $t['id'] ?>" data-status="<?= $t['status'] ?>">
              <td><?= htmlspecialchars($t['title']) ?></td>
              <td>
                <div class="collaborator-list">
                  <?php if($t['collaborator_emails']): 
                    $emails = explode(', ',$t['collaborator_emails']);
                    echo htmlspecialchars(
                      count($emails)>2
                        ? implode(', ',array_slice($emails,0,2)).', ...'
                        : $t['collaborator_emails']
                    );
                  else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </div>
              </td>
              <td><?= date('M j, Y',strtotime($t['due_date'])) ?></td>
              <td class="status-cell">
                <span class="badge <?= $t['statusClass'] ?>">
                  <?= ucfirst(str_replace('_',' ',$t['status'])) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TASK DETAIL MODAL -->
  <div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">
            <i class="fa fa-info-circle me-2"></i>Task Details
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
          <h6><i class="fa fa-users me-1 text-secondary"></i>Collaborators</h6>
          <ul id="detail-collaborators" class="list-group list-group-flush mt-2"></ul>
        </div>
        <div class="modal-footer border-0">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle
    document.getElementById('toggleBtn').onclick = () => {
      document.getElementById('sidebar').classList.toggle('sidebar-hidden');
      document.getElementById('mainContent').classList.toggle('shifted');
    };

    // Status filter
    document.getElementById('statusFilter').onchange = function() {
    const selected = this.value;
    document.querySelectorAll('.task-row').forEach(row => {
      const status = row.getAttribute('data-status');
      row.style.display = (!selected || status === selected) ? '' : 'none';
    });
  };


    document.addEventListener('DOMContentLoaded', function() {
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
        });

    // Task row click → modal
    document.querySelectorAll('.task-row').forEach(row => {
      row.addEventListener('click', async () => {
        const id = row.dataset.id;
        try {
          const res = await fetch(`api/get_task.php?id=${id}`);
          if (!res.ok) throw new Error();
          const task = await res.json();
          // populate...
          document.getElementById('detail-title').textContent = task.title;
          document.getElementById('detail-description').textContent = task.description || 'No description';
          const map = { todo:'bg-danger', in_progress:'bg-warning', completed:'bg-success', expired:'bg-secondary' };
          const st = document.getElementById('detail-status');
          st.textContent = task.status.replace('_',' ');
          st.className = `badge ${map[task.status]||'bg-secondary'}`;
          document.getElementById('detail-due-date').textContent =
            new Date(task.due_date).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
          const lst = document.getElementById('detail-collaborators');
          lst.innerHTML = task.collaborators?.length
            ? task.collaborators.map(c=>`
                <li class="list-group-item d-flex align-items-center">
                  <i class="fa fa-user-circle text-secondary me-2"></i>
                  <span class="flex-grow-1">${c.email}</span>
                  <span class="badge ${c.status==='accepted'?'bg-success':'bg-warning'}">${c.status}</span>
                </li>`).join('')
            : `<li class="list-group-item text-muted">
                 <i class="fa fa-user-slash me-2"></i>No collaborators
               </li>`;
          new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
        } catch {
          alert('Failed to load details');
        }
      });
    });
  </script>
</body>
</html>
