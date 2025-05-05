<?php
session_start();
include 'config.php';

// 🚨 Problem: Checking for logged-in user (user_id) instead of reset session
// ✅ Fix: Check if the user is in the password reset flow via $_SESSION['reset_email']
if (!isset($_SESSION['reset_email'])) { // 👈 Changed from $_SESSION['user_id']
  header("Location: login.php");
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new-password'];
    $confirm_password = $_POST['confirm-password'];
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,20}$/', $new_password)) {
        $error = "Password must meet complexity requirements!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $_SESSION['reset_email']]);
        
        // 🚨 Security: Clear reset session data after password change
        unset($_SESSION['reset_email']); // 👈 Prevent session reuse
        
        echo "<script>
            alert('Password updated successfully!');
            window.location.href = 'login.php';
            </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password</title>

  <!-- Bootstrap CSS (for the form styling below) -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />

  <!-- Font Awesome (for the envelope icon) -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
  />

  <!-- Your custom CSS -->
  <link href="designs/newpass.css" rel="stylesheet" />
</head>
<body>

  <!-- ===== START HEADER (site logo + title) ===== -->
  <header>
    <!-- logo -->
    <img src="ORGanizepics/layers.png" class="ic" alt="Logo">
    <!-- site title -->
    <h2>ORGanize+</h2>
  </header>
  <!-- ===== END HEADER ===== -->

  <!-- main area: centers the white box under the header -->
  <main class="main-content">
    <div class="forgot-container">
      <h1>New Password</h1>

      <?php if (!empty($error)): ?>
       <div class="alert alert-danger" role="alert">
         <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

      <!-- HTML for entering the new password -->
      <form action="newpass.php" method="POST">
          <!-- In your form, replace the lock icons with eye icons -->
          <div class="input-group mb-4">
              <input type="password" name="new-password" class="form-control password-toggleable" 
                    placeholder="Enter new password" required />
              <span class="input-group-text">
                  <img src="ORGanizepics/eye-closed.png" class="toggle-password" 
                      alt="Toggle Password" style="cursor: pointer;">
              </span>
          </div>
          <div class="input-group mb-4">
              <input type="password" name="confirm-password" class="form-control password-toggleable" 
                    placeholder="Confirm new password" required />
              <span class="input-group-text">
                  <img src="ORGanizepics/eye-closed.png" class="toggle-password" 
                      alt="Toggle Password" style="cursor: pointer;">
              </span>
          </div>
          <button type="submit" class="next-button">Save</button>
      </form>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>

            // Toggle password visibility for both pages
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
          const input = this.closest('.input-group').querySelector('input'); // ✅ Correct parent
            if (input.type === 'password') {
                input.type = 'text';
                this.src = 'ORGanizepics/eye-open.png';
            } else {
                input.type = 'password';
                this.src = 'ORGanizepics/eye-closed.png';
            }
        });
    });

  </script>
  <script src="js/new.js"></script>
</body>
</html>
