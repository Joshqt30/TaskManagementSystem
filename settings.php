<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// update last_active timestamp for this user
$pdo
  ->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
  ->execute([ $_SESSION['user_id'] ]);
  
$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT username, organization, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching user data.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>User Profile Settings</title>
  <link rel="stylesheet" href="designs/settings.css">
</head>
<body>

  <div class="header">
    <button class="back-button">
      <span class="back-icon">←</span> Back
    </button>
    <h1 class="page-title">Profile Settings</h1>
  </div>
  
  <div class="container">
    <!-- General Information Section -->
    <div class="general-info">
      <div class="section-header">
        <h2>General information</h2>
      </div>
      
      <div id="general-success" class="success-message">
        Information updated successfully!
      </div>
      
      <div class="info-fields">
        <div class="field-group">
          <label>Username</label>
          <div id="name-display" class="field-value"><?= htmlspecialchars($user['username']) ?></div>
        </div>
        <div class="field-group">
          <label>Organization</label>
          <div id="org-display" class="organization-value"><?= htmlspecialchars($user['organization']) ?></div>
        </div>
      </div>
      
      <div id="general-edit-form" class="edit-form">
        <div class="edit-form-horizontal">
          <div class="form-group">
            <label for="name-input">Name</label>
            <input
              type="text"
              id="name-input"
              class="edit-input"
              value="<?= htmlspecialchars($user['username']) ?>"
              placeholder="Enter your name"
            >
          </div>
          <div class="form-group">
            <label>Organization</label>
            <div class="field-value"><?= htmlspecialchars($user['organization']) ?></div>
          </div>
        </div>
        <div class="btn-container">
          <button id="general-cancel-btn" class="btn btn-sm btn-cancel">Cancel</button>
          <button id="general-save-btn" class="btn btn-sm">Save</button>
        </div>
      </div>
      
      <button id="general-update-btn" class="btn">Update</button>
    </div>
    
    <!-- Security Section -->
    <div class="security-section">
      <div class="section-header">
        <h2>Security</h2>
      </div>
      
      <div id="security-success" class="success-message">
        Security information updated successfully!
      </div>
      
      <div class="security-fields">
        <div class="security-field">
          <label>Email</label>
          <div id="email-display" class="security-field-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>
        <div class="security-field">
          <label>Password</label>
          <div id="password-display" class="security-field-value">••••••••••</div>
        </div>
      </div>
      
      <div id="security-edit-form" class="edit-form">
        <!-- Full-width Email row -->
        <div class="edit-form-horizontal">
          <div class="form-group" style="flex:1; min-width:300px;">
            <label for="email-input">Email</label>
            <input
            type="email"
            id="email-input"
            class="edit-input"
            style="width:320px; max-width:100%; box-sizing:border-box;"
            value="<?= htmlspecialchars($user['email']) ?>"
            placeholder="Enter your email"
            />

          </div>
        </div>
        <!-- Two-column Password row -->
        <div class="edit-form-horizontal">
          <div class="form-group password-wrapper">
            <label for="password-input">New Password</label>
            <div class="password-field">
            <input type="password" id="password-input" class="edit-input"
            style="width:320px; max-width:100%; box-sizing:border-box;"
            placeholder="Enter new password">

              <span id="toggle-password" class="toggle-eye">👁️</span>
            </div>
          </div>
          <div class="form-group password-wrapper">
            <label for="confirm-password-input">Confirm Password</label>
            <div class="password-field">
            <input type="password" id="confirm-password-input" class="edit-input"
             style="width:320px; max-width:100%; box-sizing:border-box;"
            placeholder="Confirm new password">

              <span id="toggle-confirm-password" class="toggle-eye">👁️</span>
            </div>
          </div>
        </div>
        
        <div class="btn-container">
          <button id="security-cancel-btn" class="btn btn-sm btn-cancel">Cancel</button>
          <button id="security-save-btn" class="btn btn-sm">Save</button>
        </div>
      </div>
      
      <button id="security-update-btn" class="btn">Update Security Information</button>
    </div>
  </div>

  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="js/settings.js"></script>
</body>
</html>
