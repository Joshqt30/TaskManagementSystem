<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

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
  <link rel="stylesheet" href="designs/settings.css">
</head>
<body>
  <div class="header">
    <button class="back-button"><span class="back-icon">←</span> Back</button>
    <h1 class="page-title">Admin Profile Settings</h1>
  </div>

  <div class="container">
    <div class="general-info">
      <h2>General Info</h2>
      <div class="field-group">
        <label>Username</label>
        <div id="name-display"><?= htmlspecialchars($admin['username']) ?></div>
      </div>

      <div id="general-edit-form" class="edit-form">
        <div class="form-group">
          <label for="name-input">Username</label>
          <input type="text" id="name-input" class="edit-input" value="<?= htmlspecialchars($admin['username']) ?>">
        </div>
        <div class="btn-container">
          <button id="general-cancel-btn" class="btn btn-sm btn-cancel">Cancel</button>
          <button id="general-save-btn" class="btn btn-sm">Save</button>
        </div>
      </div>
      <button id="general-update-btn" class="btn">Update</button>
    </div>

    <div class="security-section">
      <h2>Security</h2>
      <div class="field-group">
        <label>Email</label>
        <div id="email-display"><?= htmlspecialchars($admin['email']) ?></div>
      </div>
      <div class="field-group">
        <label>Password</label>
        <div id="password-display">••••••••</div>
      </div>

      <div id="security-edit-form" class="edit-form">
        <div class="form-group">
          <label for="email-input">Email</label>
          <input type="email" id="email-input" class="edit-input" value="<?= htmlspecialchars($admin['email']) ?>">
        </div>
        <div class="form-group">
          <label for="password-input">New Password</label>
          <input type="password" id="password-input" class="edit-input">
        </div>
        <div class="form-group">
          <label for="confirm-password-input">Confirm Password</label>
          <input type="password" id="confirm-password-input" class="edit-input">
        </div>
        <div class="btn-container">
          <button id="security-cancel-btn" class="btn btn-sm btn-cancel">Cancel</button>
          <button id="security-save-btn" class="btn btn-sm">Save</button>
        </div>
      </div>
      <button id="security-update-btn" class="btn">Update Security</button>
    </div>
  </div>

  <script src="js/settings.js"></script>
</body>
</html>
