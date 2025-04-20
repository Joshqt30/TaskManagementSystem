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

.user-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    margin-right: 10px;
}

.user-btn:hover {
    opacity: 0.9;
}

.content {
    margin: 70px auto 0 auto;
    padding: 30px;
    width: 80%;
    max-width: 1000px;
    transition: margin 0.4s ease, width 0.4s ease;
    color: #3D5654;
}

.content.shifted {
    margin-left: 270px;
}

h2 {
    margin-bottom: 10px;
    color: #2c3e50;
    font-size: 1.8rem;
}

select {
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ddd;
    margin-bottom: 15px;
    background-color: white;
    font-size: 0.9rem;
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

th, td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;
}

thead {
    background-color: white;
    border-bottom: 2px solid #ddd;
}

thead th {
    font-weight: 600;
    color: #2c3e50;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

tr:not(:last-child) {
    border-bottom: 1px solid #eee;
}

tr:hover:not(.total-users) {
    background-color: #f9f9f9;
}

.total-users {
    background-color: #f4f4f4;
    color: #7D7D7D;
    font-weight: 500;
}

.total-users td {
    border-top: 2px solid #ddd;
    text-align: left;
    padding-left: 20px;
}

.badge-admin, 
.badge-user {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-admin {
    background-color: #2c3e50;
    color: white;
}

.badge-user {
    background-color: #d4a017;
    color: white;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.edit-btn, 
.remove-btn,
.save-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s ease;
    min-width: 80px;
}

.edit-btn {
    background-color: #3498db;
    color: white;
}

.edit-btn:hover {
    background-color: #2980b9;
    transform: translateY(-1px);
}

.remove-btn {
    background-color: #e74c3c;
    color: white;
}

.remove-btn:hover {
    background-color: #c0392b;
    transform: translateY(-1px);
}

.save-btn {
    background-color: #2ecc71 !important;
    margin-right: 5px;
}

.save-btn:hover {
    background-color: #27ae60 !important;
    transform: translateY(-1px);
}

.edit-input, .edit-select {
    padding: 8px 12px;
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    font-family: inherit;
    transition: border-color 0.3s;
}

.edit-input:focus, .edit-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

@media (max-width: 768px) {
    .content {
        width: 95%;
        padding: 20px;
        margin: 60px auto 0 auto;
    }
    
    th, td {
        padding: 8px 6px;
        font-size: 0.8rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .edit-btn, 
    .remove-btn,
    .save-btn {
        width: 100%;
        padding: 6px;
        font-size: 0.8rem;
    }

    .account-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    .content.shifted {
        margin-left: 0;
    }
}
    </style>

</head>
<body>
    <header class="header">
        <div class="header-center">
            <div class="header-left">
                <button id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <img src="ORGanizepics/layers.png" class="orglogo" alt="Logo" />
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

    <aside class="sidebar"> <!-- Removed 'active' class to start open -->
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
                    <li class="nav-item active">
                        <i class="fas fa-users nav-icon"></i>
                        <span><a href="adminaccs.php">Accounts</a></span>
                    </li>
                    <li class="nav-item">
                        <i class="fas fa-flag nav-icon"></i>
                        <span><a href="admintasks.php">Tasks</a></span>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content shifted" id="mainContent">
        <h2>Accounts</h2>
        <label for="sort">Sort By:</label>
        <select id="sort" onchange="window.location.href='adminaccs.php?sort=' + this.value">
            <option value="All" <?php echo $sort == 'All' ? 'selected' : ''; ?>>All</option>
            <option value="admin" <?php echo $sort == 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="user" <?php echo $sort == 'user' ? 'selected' : ''; ?>>User</option>
        </select>
    
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
                            <button class="edit-btn">Edit</button>
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
    
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.stopPropagation();
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });

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

            // Ensure sidebar links are clickable
            document.querySelectorAll('.nav-item a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.stopPropagation();
                    console.log('Nav link clicked:', this.href);
                    window.location.href = this.href;
                });
            });

            // Edit functionality
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const isEditing = row.classList.contains('editing');
                    
                    if (isEditing) {
                        saveChanges(row);
                        row.classList.remove('editing');
                        this.textContent = 'Edit';
                        this.classList.remove('save-btn');
                    } else {
                        enterEditMode(row);
                        row.classList.add('editing');
                        this.textContent = 'Save';
                        this.classList.add('save-btn');
                    }
                });
            });

            function enterEditMode(row) {
                row.querySelectorAll('.editable').forEach(cell => {
                    const field = cell.getAttribute('data-field');
                    if (field === 'role') {
                        const currentRole = cell.textContent.trim().toLowerCase();
                        const normalizedRole = currentRole === 'admin' ? 'admin' : 'user';
                        cell.innerHTML = `
                            <select class="edit-select" data-field="role">
                                <option value="admin" ${normalizedRole === 'admin' ? 'selected' : ''}>admin</option>
                                <option value="user" ${normalizedRole === 'user' ? 'selected' : ''}>user</option>
                            </select>
                        `;
                    } else {
                        const value = cell.textContent;
                        cell.innerHTML = `<input class="edit-input" type="text" value="${value}" data-field="${field}">`;
                    }
                });
            }

            function saveChanges(row) {
                const userId = row.getAttribute('data-id');
                let username = '';
                let email = '';
                let role = '';
                let organization = '';
                
                row.querySelectorAll('.editable').forEach(cell => {
                    const field = cell.getAttribute('data-field');
                    if (field === 'role') {
                        const select = cell.querySelector('.edit-select');
                        role = select.value;
                        const displayRole = role || 'user';
                        cell.innerHTML = `<span class="badge-${displayRole.toLowerCase()}">${displayRole}</span>`;
                    } else {
                        const input = cell.querySelector('.edit-input');
                        const newValue = input.value;
                        cell.textContent = newValue;
                        if (field === 'name') username = newValue;
                        if (field === 'email') email = newValue;
                        if (field === 'organization') organization = newValue;
                    }
                });

                // Submit the update to the backend
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'adminaccounts.php';
                form.style.display = 'none';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                const usernameInput = document.createElement('input');
                usernameInput.type = 'hidden';
                usernameInput.name = 'username';
                usernameInput.value = username;
                
                const emailInput = document.createElement('input');
                emailInput.type = 'hidden';
                emailInput.name = 'email';
                emailInput.value = email;
                
                const roleInput = document.createElement('input');
                roleInput.type = 'hidden';
                roleInput.name = 'role';
                roleInput.value = role;
                
                const organizationInput = document.createElement('input');
                organizationInput.type = 'hidden';
                organizationInput.name = 'organization';
                organizationInput.value = organization;
                
                const updateInput = document.createElement('input');
                updateInput.type = 'hidden';
                updateInput.name = 'update_user';
                updateInput.value = '1';
                
                form.appendChild(userIdInput);
                form.appendChild(usernameInput);
                form.appendChild(emailInput);
                form.appendChild(roleInput);
                form.appendChild(organizationInput);
                form.appendChild(updateInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>
</body>
</html>