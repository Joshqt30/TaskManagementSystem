<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// update last_active
$admin_id = $_SESSION['user_id'];
$pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
    ->execute([$admin_id]);

$adminId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching admin data.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Settings</title>
  <link rel="stylesheet" href="designs/adminsettings.css">
  <link rel="stylesheet" href="designs/settings.css">
</head>
<body class="admin-settings">
  <div class="header">
    <button class="back-button">&larr; Back</button>
    <h1 class="page-title">Admin Profile Settings</h1>
  </div>

  <div class="container">
    
    <!-- General Info Section -->
    <div class="section-card">
      <h2 class="section-title">General Info</h2>

      <div class="field-group">
        <label>Username</label>
        <div id="name-display" class="field-value"><?= htmlspecialchars($admin['username']) ?></div>
      </div>

      <div id="general-edit-form" class="edit-form">
        <div class="form-group">
          <label for="name-input">Username</label>
          <input type="text" id="name-input" class="edit-input" value="<?= htmlspecialchars($admin['username']) ?>">
        </div>
        <div class="btn-container">
          <button id="general-cancel-btn" class="btn btn-sm btn-outline">Cancel</button>
          <button id="general-save-btn" class="btn btn-sm btn-primary">Save</button>
        </div>
      </div>
      <button id="general-update-btn" class="btn btn-primary">Edit Username</button>
    </div>

    <!-- Security Section -->
    <div class="section-card">
      <h2 class="section-title">Security</h2>

        <div class="field-group">
            <label>Email</label>
            <div id="email-display" class="field-value"><?= htmlspecialchars($admin['email']) ?></div>
        </div>

        <div class="field-group">
            <label>Password</label>
            <div class="password-display-wrapper">
            <span id="password-display">••••••••</span>
            </div>
        </div>
        <div id="security-edit-form" class="edit-form">

    <div class="form-group">
    <label for="email-input">Email</label>
    <input type="email" id="email-input" class="edit-input" value="<?= htmlspecialchars($admin['email']) ?>">
    </div>

    <div class="form-group">
    <label for="password-input">New Password</label>
    <div class="password-wrapper">
        <input type="password" id="password-input" class="edit-input">
        <span id="toggle-password" class="toggle-eye">👁️</span>
    </div>
    </div>

    <div class="form-group">
    <label for="confirm-password-input">Confirm Password</label>
    <div class="password-wrapper">
        <input type="password" id="confirm-password-input" class="edit-input">
        <span id="toggle-confirm-password" class="toggle-eye">👁️</span>
    </div>
    </div>

    <div class="btn-container">
    <button id="security-cancel-btn" class="btn btn-sm btn-outline">Cancel</button>
    <button id="security-save-btn" class="btn btn-sm btn-primary">Save</button>
    </div>
    </div>

      <button id="security-update-btn" class="btn btn-primary">Edit Security</button>
    </div>

  </div>

  <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <h3>Confirm Changes</h3>
        <p>Are you sure you want to save these security changes?</p>
        <div class="modal-actions">
        <button id="modal-cancel" class="btn btn-sm btn-outline">Cancel</button>
        <button id="modal-confirm" class="btn btn-sm btn-danger">Yes, Save</button>
        </div>
    </div>
    </div>


  <script src="js/adminsettings.js"></script>
</body>
</html>
