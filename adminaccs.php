<?php
session_start();
include 'config.php';

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// update last_active
$admin_id = $_SESSION['user_id'];
$pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
    ->execute([$admin_id]);

// Fetch admin details
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE id = ?"); // Add profile_pic
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_username = $admin['username'];
$admin_email = $admin['email'];
$admin_profile_pic = $admin['profile_pic'] ?? null; // Get profile pic

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

    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->execute([$username, $email, $role, $user_id]);
    
    
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
$query = "SELECT id, username, role, email FROM users" . $where_clause;
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

  .admin-avatar {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
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

.user-profile-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-thumbnail {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #3D5654;
  transition: transform 0.2s;
}

.dropdown-toggle::after {
  display: none; /* Hide default Bootstrap caret */
}

.caret-icon {
  color: #3D5654;
  transition: transform 0.2s;
}

/* Remove button background and padding */
.user-btn {
  background: transparent !important;
  border: none !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center;
  gap: 6px;
}

/* Hover effects */
.user-btn:hover .profile-thumbnail {
  transform: scale(1.1);
}

.user-btn:hover .caret-icon {
  transform: translateY(1px);
}

/* Fix dropdown menu positioning */
.dropdown-menu {
  margin-top: 8px !important;
  border: 1px solid #DBE8E7 !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}


/* Add to existing CSS */

.account-table {
  width: 100%;
  border-collapse: collapse;
}

.action-column {
  width: 150px; /* Fixed width for action column */
  text-align: right;
  padding-right: 25px !important;
}


.account-table th,
.account-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-end; /* Align buttons to the right */
  white-space: nowrap; /* Prevent text wrapping */
}

/* Ensure table uses Bootstrap's default styling */
.table-responsive {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.account-table thead th {
  background: #425C5A;
  color: white;
  font-weight: 600;
}

/* Add Bootstrap table class */
table.table {
  margin-bottom: 0;
}

td.vertical-align-middle {
  vertical-align: middle !important;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}

.action-buttons form {
  margin: 0;
  display: flex;
}

td {
  vertical-align: middle !important;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
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
  <button class="btn rounded-circle user-btn text-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
             alt="Profile Picture"
             class="admin-avatar-img">
      <?php else: ?>
        <i class="fa-solid fa-user-circle default-avatar"></i>
      <?php endif; ?>
    </div>
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
      <h2 class="mb-0">Accounts</h2>
      <select id="sort" class="form-select w-auto" 
              onchange="window.location.href='adminaccs.php?sort='+this.value">
        <option value="All"   <?= $sort=='All'   ? 'selected':'' ?>>All</option>
        <option value="admin" <?= $sort=='admin' ? 'selected':'' ?>>Admin</option>
        <option value="user"  <?= $sort=='user'  ? 'selected':'' ?>>User</option>
      </select>
    </div>

    
<!-- Updated Table Structure -->
      <div class="table-responsive">
        <table class="table table-hover account-table">
          <thead class="align-middle">
            <tr>
              <th>Username</th>
              <th>Role</th>
              <th>Email</th>
              <th class="action-column">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr class="total-users">
              <td colspan="4"><?php echo count($users); ?> Total Users</td>
            </tr>
            <?php foreach ($users as $user): ?>
              <tr data-id="<?php echo $user['id']; ?>">
                <td class="editable" data-field="username"><?php echo htmlspecialchars($user['username']); ?></td>
                <td class="editable" data-field="role">
                  <?php if (!empty($user['role'])): ?>
                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'admin' : 'user'; ?>">
                      <?php echo ucfirst($user['role']); ?>
                    </span>
                  <?php else: ?>
                    <span class="badge badge-user">user</span>
                  <?php endif; ?>
                </td>
                <td class="editable" data-field="email"><?php echo htmlspecialchars($user['email']); ?></td>
                <td class="action-column">
                  <div class="action-buttons">
                    <button type="button" class="edit-btn btn btn-sm btn-outline-primary">Edit</button>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                      <input type="hidden" name="remove_user" value="1">
                      <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                  </div>
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
    let currentForm = null;
    let currentRow = null;


  document.addEventListener('DOMContentLoaded', function(){

// Handle Edit Button Click
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.type = 'button';
  btn.addEventListener('click', function (e) {
    e.preventDefault();

    const row = this.closest('tr');
    const isEditing = row.classList.contains('editing');

    if (isEditing) {
      // Save directly if already in edit mode
      saveChanges(row);
      row.classList.remove('editing');
      this.textContent = 'Edit';
    } else {
      // Only show modal here, don't edit yet
      currentRow = row;
      const editModal = new bootstrap.Modal(document.getElementById('editConfirmModal'));
      editModal.show();
    }
  });
});

// Confirm Edit Button in Modal
document.getElementById('confirmEditBtn').addEventListener('click', function () {
  const editModal = bootstrap.Modal.getInstance(document.getElementById('editConfirmModal'));
  editModal.hide();

  if (currentRow) {
    enterEditMode(currentRow);
    currentRow.classList.add('editing');
    currentRow.querySelector('.edit-btn').textContent = 'Save';
  }
});

document.querySelectorAll('.remove-btn').forEach(btn => {
  btn.type = 'button';
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    currentRow = this.closest('tr');
    currentForm = this.closest('form'); // ✅ This is the correct way!

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();
  });
});

document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
  const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
  deleteModal.hide();

  if (currentForm) {
    currentForm.submit(); // ✅ Form found, now this will work.
  } else {
    console.error("No form found for deletion.");
  }
});


});


    // Sidebar toggle (unchanged)…
    document.getElementById('toggleBtn').onclick = () => {
      document.getElementById('sidebar').classList.toggle('sidebar-hidden');
      document.getElementById('mainContent').classList.toggle('shifted');
    };

    // User-dropdown toggle (unchanged)…
    // const ddToggle = document.querySelector('[data-bs-toggle="dropdown"]');
    // const ddMenu   = document.querySelector('.dropdown-menu');
    // ddToggle.addEventListener('click', e => {
    //   e.stopPropagation();
    //   ddMenu.classList.toggle('show');
    // });
    // document.addEventListener('click', () => ddMenu.classList.remove('show'));

    // ——— Edit/Save functionality ———
    // document.querySelectorAll('.edit-btn').forEach(btn => {
    //   // ensure it never tries to submit any form:
    //   btn.type = 'button';

    //   btn.addEventListener('click', function(e) {
    //     e.preventDefault();

    //     const row = this.closest('tr');
    //     const editing = row.classList.contains('editing');

    //     if (editing) {
    //       saveChanges(row);
    //       row.classList.remove('editing');
    //       this.textContent = 'Edit';
    //     } else {
    //       enterEditMode(row);
    //       row.classList.add('editing');
    //       this.textContent = 'Save';
    //     }
    //   });
    // });

  function enterEditMode(row) {
  row.querySelectorAll('.editable').forEach(cell => {
    const field = cell.dataset.field;
    let val;
    if (field === 'role') {
      val = cell.querySelector('span').textContent.trim().toLowerCase();
      cell.innerHTML = `
        <select class="edit-select form-select form-select-sm">
          <option value="admin" ${val === 'admin' ? 'selected' : ''}>Admin</option>
          <option value="user" ${val === 'user' ? 'selected' : ''}>User</option>
        </select>`;
    } else {
      val = cell.textContent.trim();

    }
  });
}


function saveChanges(row) {
  const userId = row.dataset.id;

  const getValue = (selector, fallbackSelector) => {
    const inputEl = row.querySelector(selector);
    if (inputEl) return inputEl.value.trim();

    const fallbackEl = row.querySelector(fallbackSelector);
    return fallbackEl ? fallbackEl.textContent.trim() : '';
  };

  const username = getValue('[data-field="username"] input', '[data-field="username"]');
  const email = getValue('[data-field="email"] input', '[data-field="email"]');
  const roleSelect = row.querySelector('[data-field="role"] select');
  const role = roleSelect ? roleSelect.value : getValue('[data-field="role"]', '[data-field="role"]');

  // Revert cells to read mode
  row.querySelector('[data-field="username"]').innerHTML = `<span>${username}</span>`;
  row.querySelector('[data-field="email"]').innerHTML = `<span>${email}</span>`;
  row.querySelector('[data-field="role"]').innerHTML = `<span class="badge-${role}">${role.charAt(0).toUpperCase() + role.slice(1)}</span>`;

  const editBtn = row.querySelector('.edit-btn');
  const saveBtn = row.querySelector('.save-btn');
  if (editBtn) editBtn.style.display = '';
  if (saveBtn) saveBtn.style.display = 'none';

  // Submit the hidden form
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'adminaccs.php';
  form.style.display = 'none';

  [['user_id', userId],
   ['update_user', '1'],
   ['username', username],
   ['email', email],
   ['role', role]
  ].forEach(([name, value]) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
  });

  document.body.appendChild(form);
  form.submit();
}


</script>


<!-- Edit Confirmation Modal -->
<div class="modal fade" id="editConfirmModal" tabindex="-1" aria-labelledby="editConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="editConfirmLabel">Confirm Edit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to edit this user's info?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmEditBtn" class="btn btn-warning">Yes, Edit</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteConfirmLabel">Confirm Removal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to remove this user? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes, Remove</button>
      </div>
    </div>
  </div>
</div>


</body>
</html>