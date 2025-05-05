<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

include 'config.php';

// ——— AUTH & CONTEXT ———
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['user_id'];   // ← Make sure this is here, before you use $userId

// Verify user is admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    header("Location: login.php");
    exit();
}

// Update last_active
$pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")
    ->execute([$userId]);

// ——— POST-Redirect-GET for profile_pic upload ———
$uploadSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = 'uploads/profile_pics/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName   = uniqid('profile_') . '_' . basename($_FILES['profile_pic']['name']);
    $targetPath = $uploadDir . $fileName;
    $allowed    = ['jpg','jpeg','png','gif', 'jfif'];
    $ext        = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) &&
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
        // Save only the filename in DB and session
        $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")
            ->execute([$fileName, $userId]);
        $_SESSION['profile_pic'] = $fileName;
    }

    // Redirect away from POST to clear it from history
    echo json_encode(['success' => true, 'filename' => $fileName]);
    exit;
    
}

// After redirect (on GET), look for our flag
if (!empty($_GET['uploaded']) && $_GET['uploaded'] === '1') {
    $uploadSuccess = true;
}


// ——— NOW fetch user data with the defined $userId ———
try {
    $stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("Admin not found.");
    }
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// … the rest of your HTML below …

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="designs/adminsettings.css">
</head>
<body>

  <div class="header">
    <button class="back-button" onclick="window.href='admin.php'">
      <span class="back-icon">←</span> Back
    </button>
    <h1 class="page-title">Profile Settings</h1>
  </div>
  
  <div class="container">
    <!-- Profile and General Info Container -->
    <div class="profile-general-container">
        <!-- Profile Picture Section -->
            <div class="profile-picture-section">
        <div class="section-header">
            <h2>Profile Picture</h2>
        </div>
        <?php if ($uploadSuccess): ?>
            <div class="success-message">Profile picture updated successfully!</div>
        <?php endif; ?>
        
        <div class="profile-preview-container">
        <?php if (!empty($user['profile_pic'])): ?>
            <!-- Move remove button HERE (outside profile-preview) -->
            <button class="remove-profile-btn" id="removeProfileBtn">×</button>
        <?php endif; ?>

        <div class="profile-preview" id="profile-preview">
            <?php if (!empty($user['profile_pic'])): ?>
                <div class="profile-image-wrapper">
                    <img src="uploads/profile_pics/<?= htmlspecialchars($user['profile_pic']) ?>" 
                        alt="Profile Picture"
                        class="profile-preview-img">
                </div>
            <?php else: ?>
                <div class="profile-image-wrapper">
                    <i class="fa-solid fa-user-circle default-profile"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>
        
        <form method="POST" enctype="multipart/form-data" id="profile-pic-form">
            <input type="file" 
                  name="profile_pic" 
                  id="profile-pic-input" 
                  accept="image/*"
                  class="visually-hidden">
            <label for="profile-pic-input" class="btn upload-btn">
                <i class="fas fa-upload me-2"></i>Upload Photo
            </label>
        </form>
    </div>

        <!-- General Information Section -->
        <div class="general-info">
            <div class="section-header">
                <h2>General Information</h2>
            </div>
            
            <div id="general-success" class="success-message">
                Information updated successfully!
            </div>
            
            <div class="info-fields">
                <div class="field-group">
                    <label>Username</label>
                    <div id="name-display" class="field-value"><?= htmlspecialchars($user['username']) ?></div>
                </div>
            </div>
            
            <div id="general-edit-form" class="edit-form">
                <div class="edit-form-horizontal">
                    <div class="form-group">
                        <label for="name-input">Name</label>
                        <input type="text"
                             id="name-input"
                             class="edit-input"
                             value="<?= htmlspecialchars($user['username']) ?>"
                             placeholder="Enter your name">
                    </div>
                </div>
                <div class="btn-container">
                    <button id="general-cancel-btn" class="btn btn-sm btn-cancel">Cancel</button>
                    <button id="general-save-btn" class="btn btn-sm">Save</button>
                </div>
            </div>
            
            <button id="general-update-btn" class="btn">Update</button>
        </div>
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
        <div class="edit-form-horizontal">
          <div class="form-group" style="flex:1; min-width:300px;">
            <label for="email-input">Email</label>
            <input type="email"
                   id="email-input"
                   class="edit-input"
                   value="<?= htmlspecialchars($user['email']) ?>"
                   placeholder="Enter your email">
          </div>
        </div>
        <div class="edit-form-horizontal">
          <div class="form-group password-wrapper">
            <label for="password-input">New Password</label>
            <div class="password-field">
              <input type="password" 
                     id="password-input" 
                     class="edit-input"
                     placeholder="Enter new password">
              <span id="toggle-password" class="toggle-eye">👁️</span>
            </div>
          </div>
          <div class="form-group password-wrapper">
            <label for="confirm-password-input">Confirm Password</label>
            <div class="password-field">
              <input type="password" 
                     id="confirm-password-input" 
                     class="edit-input"
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

  <!-- Confirmation Modal -->
<div class="confirmation-modal" id="confirmationModal">
    <div class="modal-content">
        <div class="modal-message" id="modalMessage">Are you sure you want to save changes?</div>
        <div class="modal-buttons">
            <button class="btn btn-cancel" id="modalCancel">Cancel</button>
            <button class="btn" id="modalConfirm">Confirm</button>
        </div>
    </div>
</div>

<script>
  if (window.location.search.includes('uploaded=1')) {
    window.history.replaceState({}, '', window.location.pathname);
  }
</script>

  <script src="js/adminsettings.js"></script>
</body>
</html>