<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Update last_active
$admin_id = $_SESSION['user_id'];
$pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
    ->execute([$admin_id]);

// Handle profile picture upload
$uploadSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = 'uploads/profile_pics/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid('profile_') . '_' . basename($_FILES['profile_pic']['name']);
    $targetPath = $uploadDir . $fileName;
    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) &&
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
        $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")
            ->execute([$fileName, $admin_id]);
        $_SESSION['profile_pic'] = $fileName;
    }

    header('Location: adminsettings.php?uploaded=1');
    exit;
}

if (!empty($_GET['uploaded']) && $_GET['uploaded'] === '1') {
    $uploadSuccess = true;
}

// Fetch admin data with profile_pic
try {
    $stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
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

    <!-- Add this after the opening <div class="container"> -->
  <div class="profile-picture-section">
      <div class="section-card">
          <h2 class="section-title">Profile Picture</h2>
          
          <?php if ($uploadSuccess): ?>
          <div class="success-message">Profile picture updated successfully!</div>
          <?php endif; ?>
          
          <div class="profile-preview-container">
              <div class="profile-preview" id="profile-preview">
                  <?php if (!empty($admin['profile_pic'])): ?>
                  <button class="remove-profile-btn" id="removeProfileBtn">×</button>
                  <div class="profile-image-wrapper">
                      <img src="uploads/profile_pics/<?= htmlspecialchars($admin['profile_pic']) ?>" 
                          alt="Profile Picture"
                          class="profile-preview-img">
                  </div>
                  <?php else: ?>
                  <i class="fa-solid fa-user-circle default-profile"></i>
                  <?php endif; ?>
              </div>
          </div>
          
          <form method="POST" enctype="multipart/form-data" id="profile-pic-form">
              <input type="file" 
                  name="profile_pic" 
                  id="profile-pic-input" 
                  accept="image/*"
                  class="visually-hidden">
              <label for="profile-pic-input" class="btn btn-primary">
                  <i class="fas fa-upload me-2"></i>Upload Photo
              </label>
          </form>
      </div>
  </div>
    
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
