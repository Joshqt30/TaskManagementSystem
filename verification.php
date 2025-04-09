<?php
include 'config.php';

// Grab the email from POST (if the form is submitted) or GET (initial load)
$email = trim($_POST['email'] ?? $_GET['email'] ?? '');
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = isset($_POST['otp']) ? implode("", $_POST['otp']) : '';

    if (empty($email) || empty($otp)) {
        $error_message = "Email or OTP is missing!";
    } else {
        // Query the user record by email
        $stmt = $pdo->prepare("SELECT otp, otp_expiry, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['is_verified']) {
                $error_message = "This account is already verified. Please log in.";
            } elseif ($user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
                // Mark user as verified and clear the OTP fields
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE email = ?");
                $stmt->execute([$email]);
                header("Location: login.php");
                exit;
            } else {
                $error_message = "Invalid or expired OTP!";
            }
        } else {
            $error_message = "User not found!";
        }
    }
}

$emailValue = htmlspecialchars($email);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="designs/verification.css">
  <title>OTP Verification</title>
</head>
<body>
  <header>
      <img src="ORGanizepics/layers.png" class="ic" alt="Logo">
      <h2>ORGanize+</h2>
  </header>

  <div class="container">
      <div class="otp-verification">
          <div class="class-otp">OTP CODE VERIFICATION</div>
          <p>We have sent the OTP code to your email. Please enter the code below.</p>

          <?php if ($error_message): ?>
              <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
          <?php endif; ?>

          <form action="verification.php" method="POST">
              <!-- Hidden input to pass the email -->
              <input type="hidden" name="email" value="<?php echo $emailValue; ?>">
              <div class="otp-inputs">
                  <input type="text" maxlength="1" class="otp-box" name="otp[]">
                  <input type="text" maxlength="1" class="otp-box" name="otp[]">
                  <input type="text" maxlength="1" class="otp-box" name="otp[]">
                  <input type="text" maxlength="1" class="otp-box" name="otp[]">
              </div>
              <button type="submit" class="next-btn">Verify</button>
          </form>
      </div>
  </div>

  <script src="new.js"></script>
</body>
</html>
