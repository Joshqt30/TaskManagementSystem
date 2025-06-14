<?php
session_start();

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

include 'config.php';

// <-- ADD CSRF PROTECTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['csrf_token'])) {
  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
      die("CSRF token validation failed");
  }
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate new token


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

if (isset($_POST['update_user'])) {
  $user_id = $_POST['user_id'];
  $new_role = $_POST['role'];

  // Prevent admin from changing their own role
  if ($user_id == $admin_id) {
      header("Location: adminaccs.php?error=Cannot+edit+your+own+role");
      exit();
  }

  // Fetch old data for audit
  $stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $old = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$old) {
      header("Location: adminaccs.php?error=User+not+found");
      exit();
  }

  // Persist the new role
  $upd = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
  $upd->execute([$new_role, $user_id]);

  // Audit log
  $log = sprintf(
    "Admin %d changed user %d role: %s → %s",
    $admin_id,
    $user_id,
    $old['role'],
    $new_role
  );
  file_put_contents('admin_audit.log', date('[Y-m-d H:i:s] ') . $log . PHP_EOL, FILE_APPEND);

  // Redirect with success
  header("Location: adminaccs.php?success=Role+updated");
  exit();
}

// Handle sorting/filtering
$sort = $_GET['sort'] ?? 'All';

$query  = "SELECT id, username, role, email
           FROM users
           WHERE is_verified = 1";
$params = [];

if ($sort !== 'All') {
    // Add an AND, not a second WHERE
    $query .= " AND role = ?";
    $params[] = $sort;
}

// Prepare and execute
$stmt = $pdo->prepare($query);
$stmt->execute($params);
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

      <!-- ←← 4) EXPOSE PHP TOKEN TO JS -->
  <script>
    const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';
  </script>

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

<?php if ($error): ?>
    <div class="alert alert-danger">Error: <?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

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
                    <?php
                    $role    = $user['role'] ?? 'user';
                    $bgColor = ($role === 'admin' ? 'danger' : 'secondary');
                  ?>
                  <span class="badge bg-<?= $bgColor ?>">
                    <?= ucfirst(htmlspecialchars($role)) ?>
                  </span>

                  <?php else: ?>
                    <span class="badge badge-user">user</span>
                  <?php endif; ?>
                </td>
                <td class="editable" data-field="email"><?php echo htmlspecialchars($user['email']); ?></td>
                <td class="action-column">
                <div class="action-buttons">
                  <button type="button" class="edit-btn btn btn-sm btn-outline-primary">Edit</button>

                  <form method="POST" class="d-inline delete-form">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
              <input type="hidden" name="remove_user" value="1">
              <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
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

<!-- Save Confirmation Modal -->
<div class="modal fade" id="saveConfirmModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Confirm Changes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to save these changes?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmSaveBtn" class="btn btn-primary">Save Changes</button>
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




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        let formToDelete = null;
        let rowToActOn   = null;

        // ─── Sidebar toggle ───
        document.getElementById('toggleBtn').addEventListener('click', () => {
          document.getElementById('sidebar').classList.toggle('sidebar-hidden');
          document.getElementById('mainContent').classList.toggle('shifted');
        });

        // ─── DELETE FLOW ───
        document.querySelectorAll('.remove-btn').forEach(btn => {
          btn.addEventListener('click', e => {
            e.preventDefault();
            formToDelete = btn.closest('td').querySelector('form.delete-form');
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
          });
        });
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
          if (formToDelete) formToDelete.submit();
        });

        // ─── EDIT / SAVE FLOW ───
        document.querySelectorAll('.edit-btn').forEach(btn => {
          btn.addEventListener('click', e => {
            e.preventDefault();
            rowToActOn = btn.closest('tr');
            const whichModal = rowToActOn.classList.contains('editing')
                             ? 'saveConfirmModal'
                             : 'editConfirmModal';
            new bootstrap.Modal(document.getElementById(whichModal)).show();
          });
        });

        document.getElementById('confirmEditBtn').addEventListener('click', () => {
          if (!rowToActOn) return;
          enterEditMode(rowToActOn);
          rowToActOn.classList.add('editing');
          rowToActOn.querySelector('.edit-btn').textContent = 'Save';
           // 👇 hide the edit-confirmation modal
            bootstrap.Modal.getInstance(
              document.getElementById('editConfirmModal')
            ).hide();
        });

        // In the save event listener - MODIFY THIS SECTION
        document.getElementById('confirmSaveBtn').addEventListener('click', () => {
          if (!rowToActOn) return;
          
          // Get values directly from inputs
          const userId = rowToActOn.dataset.id;
          const role = rowToActOn.querySelector('[data-field="role"] select').value;

          // Create hidden form
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'adminaccs.php';
          form.style.display = 'none';

          // Add ALL required fields
          const fields = [
            ['csrf_token', CSRF_TOKEN],
            ['user_id', userId],
            ['update_user', '1'],
            ['role', role]
          ];

          fields.forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
          });

          document.body.appendChild(form);
          form.submit();

          // Close modal
          bootstrap.Modal.getInstance(document.getElementById('saveConfirmModal')).hide();
        });

        // helper for edit mode
        function enterEditMode(row) {
          row.querySelectorAll('.editable').forEach(cell => {
            const field = cell.dataset.field;
            // Only the role cell becomes a <select>; leave others untouched
            if (field === 'role') {
              const cur = cell.querySelector('span').textContent.trim().toLowerCase();
              cell.innerHTML = `
                <select class="form-select form-select-sm">
                  <option value="admin" ${cur === 'admin' ? 'selected' : ''}>Admin</option>
                  <option value="user"  ${cur === 'user'  ? 'selected' : ''}>User</option>
                </select>`;
            }
            // if it's username or email, we do nothing (they stay as text)
          });
        }

      });
    </script>
  </body>
</html>
