<?php
session_start();
include 'config.php';

// Check if the user is logged in and has the admin role
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

// Handle user removal
if (isset($_POST['remove_user'])) {
    $user_id = $_POST['user_id'];
    // Prevent admin from removing themselves
    if ($user_id != $admin_id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    // Redirect to avoid form resubmission
    header("Location: adminaccs.php");
    exit();
}

// Handle user update
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $organization = trim($_POST['organization']);

    // Validate role
    if (!in_array($role, ['admin', 'user'])) {
        $role = 'user'; // Default to user if invalid
    }

    // Prevent admin from demoting themselves
    if ($user_id == $admin_id && $role !== 'admin') {
        header("Location: adminaccs.php?error=Cannot demote yourself");
        exit();
    }

    // Basic validation for username, email, and organization
    if (empty($username) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: adminaccs.php?error=Invalid input");
        exit();
    }

    // Organization can be empty; if empty, set to NULL
    $organization = empty($organization) ? NULL : $organization;

    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, organization = ? WHERE id = ?");
    $stmt->execute([$username, $email, $role, $organization, $user_id]);
    
    // Redirect to avoid form resubmission
    header("Location: adminaccs.php");
    exit();
}

// Handle sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'All';
$where_clause = '';
if ($sort !== 'All') {
    $where_clause = " WHERE role = ?";
    $sort_param = $sort;
}

// Fetch users with organization
$query = "SELECT id, username, role, email, organization FROM users" . $where_clause;
$stmt = $pdo->prepare($query);
if ($sort !== 'All') {
    $stmt->execute([$sort_param]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORGanize+ Admin Dashboard - Accounts</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="designs/adminaccs.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
  :root { --transition-speed: .3s; }
  body { font-family:'Inter',sans-serif; background:#E3F2F1; }
  .header { background:#DBE8E7; color:#3D5654; height:55px; position:fixed; top:0; width:100%;
            display:flex; align-items:center; justify-content:space-between; padding:0 15px;
            box-shadow:0 2px 8px rgba(0,0,0,0.1); z-index:1000; }
  .header-left { display:flex; align-items:center; gap:12px; }
  .orglogo { width:30px; height:30px; }
  .header-title { font-size:20px; font-weight:650; }
  #toggleBtn { background:transparent; border:none; font-size:20px; cursor:pointer; }
  .sidebar { width:280px; background:#425C5A; position:fixed; top:55px; left:0;
             height:calc(100vh - 55px); border-top-right-radius:20px; border-bottom-right-radius:20px;
             transition:left var(--transition-speed); overflow:hidden; }
  .sidebar.sidebar-hidden { left:-280px; }
  .admin-profile-area { background:#425C5A; padding:30px 20px; text-align:center; }

  h3 {
    font-size: 22px;
  }
  .admin-avatar { width:80px; height:80px; border-radius:50%; background:#D9D9D9;
                   margin:0 auto 15px; border:2px double #ffd700; }
  .admin-profile-area h3 { color:#fff; margin:0; }
  .admin-email { color:#a0aec0; font-size:14px; margin-top:5px; }
  .nav-area { background:#3D5654; padding:20px; }
  .nav-menu { list-style:none; padding:0; margin:0; }
  .nav-item { margin:5px 0; }
  .nav-item a { display:flex; align-items:center; gap:10px; padding:12px 15px;
                 border-radius:50px; color:#fff; text-decoration:none;
                 transition:background .3s,color .3s; }
  .nav-item.active a, .nav-item:hover a { background:#E3F2F1; color:#3D5654; }
  .nav-icon { color:#FFD700; font-size:20px; width:24px; text-align:center; }
  .nav-item.active .nav-icon, .nav-item:hover .nav-icon { color:#3D5654; }
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
    <button class="btn rounded-circle user-btn text-dark" data-bs-toggle="dropdown">
      <i class="fa-solid fa-user"></i>
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
    <div class="admin-avatar"></div>
    <h3><?= $admin_username ?></h3>
    <p class="admin-email"><?= $admin_email ?></p>
  </div>
  <div class="nav-area">
    <ul class="nav-menu">
      <li class="nav-item"><a href="admin.php"><i class="fas fa-chart-bar nav-icon"></i>Dashboard</a></li>
      <li class="nav-item active"><a href="adminaccs.php"><i class="fas fa-users nav-icon"></i>Accounts</a></li>
      <li class="nav-item"><a href="admintasks.php"><i class="fas fa-flag nav-icon"></i>Tasks</a></li>
    </ul>
  </div>
</nav>

  <div class="main-content" id="mainContent">
    <div class="card bg-white p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Accounts</h2>
        <label for="sort">Sort By:</label>
        <select id="sort" onchange="window.location.href='adminaccs.php?sort=' + this.value">
            <option value="All" <?php echo $sort == 'All' ? 'selected' : ''; ?>>All</option>
            <option value="admin" <?php echo $sort == 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="user" <?php echo $sort == 'user' ? 'selected' : ''; ?>>User</option>
        </select>
        </div>
    
        
      <div class="table-responsive">
        <table class="account-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Organization</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="total-users">
                    <td colspan="5"><?php echo count($users); ?> Total Users</td>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr data-id="<?php echo $user['id']; ?>">
                        <td class="editable" data-field="name"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="editable" data-field="role">
                            <?php if (!empty($user['role'])): ?>
                                <span class="badge-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-user">user</span>
                            <?php endif; ?>
                        </td>
                        <td class="editable" data-field="email"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="editable" data-field="organization"><?php echo htmlspecialchars($user['organization'] ?? ''); ?></td>
                        <td class="action-buttons">
                        <button type="button" class="edit-btn btn btn-sm btn-outline-primary">Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="remove_user" class="remove-btn">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
 <script>
  document.addEventListener('DOMContentLoaded', function(){
    // Sidebar toggle (unchanged)…
    document.getElementById('toggleBtn').onclick = () => {
      document.getElementById('sidebar').classList.toggle('sidebar-hidden');
      document.getElementById('mainContent').classList.toggle('shifted');
    };

    // User-dropdown toggle (unchanged)…
    const ddToggle = document.querySelector('[data-bs-toggle="dropdown"]');
    const ddMenu   = document.querySelector('.dropdown-menu');
    ddToggle.addEventListener('click', e => {
      e.stopPropagation();
      ddMenu.classList.toggle('show');
    });
    document.addEventListener('click', () => ddMenu.classList.remove('show'));

    // ——— Edit/Save functionality ———
    document.querySelectorAll('.edit-btn').forEach(btn => {
      // ensure it never tries to submit any form:
      btn.type = 'button';

      btn.addEventListener('click', function(e) {
        e.preventDefault();

        const row = this.closest('tr');
        const editing = row.classList.contains('editing');

        if (editing) {
          saveChanges(row);
          row.classList.remove('editing');
          this.textContent = 'Edit';
        } else {
          enterEditMode(row);
          row.classList.add('editing');
          this.textContent = 'Save';
        }
      });
    });
  });

  function enterEditMode(row) {
    row.querySelectorAll('.editable').forEach(cell => {
      const field = cell.dataset.field;
      const val   = cell.textContent.trim();
      if (field === 'role') {
        cell.innerHTML = `
          <select class="edit-select form-select form-select-sm">
            <option value="admin" ${val==='admin'?'selected':''}>admin</option>
            <option value="user"  ${val==='user' ?'selected':''}>user</option>
          </select>`;
      } else {
        cell.innerHTML = `<input class="edit-input form-control form-control-sm" value="${val}">`;
      }
    });
  }

  function saveChanges(row) {
    let username, email, role, organization;
    row.querySelectorAll('.editable').forEach(cell => {
      const field = cell.dataset.field;
      if (field === 'role') {
        role = cell.querySelector('select').value;
        cell.innerHTML = `<span class="badge-${role}">${role}</span>`;
      } else {
        const input = cell.querySelector('input');
        const v = input.value;
        cell.textContent = v;
        if (field==='name')         username    = v;
        else if (field==='email')   email       = v;
        else if (field==='organization') organization = v;
      }
    });

    // build and submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'adminaccs.php';
    form.style.display = 'none';

    [['user_id', row.dataset.id],
     ['update_user', '1'],
     ['username',    username],
     ['email',       email],
     ['role',        role],
     ['organization',organization]
    ].forEach(([n,v])=>{
      const i = document.createElement('input');
      i.type = 'hidden'; i.name = n; i.value = v;
      form.appendChild(i);
    });

    document.body.appendChild(form);
    form.submit();
  }
</script>

</body>
</html>