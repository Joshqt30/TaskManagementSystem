<nav class="sidebar" id="sidebar">
  <div class="admin-profile-area">
    <div class="admin-avatar"></div>
    <h3 style="font-size: 24px;"><?= htmlspecialchars($admin_username) ?></h3>
    <p class="admin-email"><?= htmlspecialchars($admin_email) ?></p>
  </div>
  <div class="nav-area">
    <ul class="nav-menu">
      <li class="nav-item"><a href="admin.php"><i class="fas fa-chart-bar nav-icon"></i>Dashboard</a></li>
      <li class="nav-item"><a href="adminaccs.php"><i class="fas fa-users nav-icon"></i>Accounts</a></li>
      <li class="nav-item"><a href="admintasks.php"><i class="fas fa-flag nav-icon"></i>Tasks</a></li>
      <li class="nav-item active"><a href="reports.php"><i class="fas fa-file-alt nav-icon"></i>Reports</a></li>
    </ul>
  </div>
</nav>
