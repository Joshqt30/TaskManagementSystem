<?php
session_start(); // Start session first
if (empty($_SESSION['reset_email'])) {
    header("Location: forgot.php");
    exit;
}

include 'config.php'; // DB connection

$email = $_SESSION['reset_email'] ?? '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = isset($_POST['otp']) ? implode("", array_map('intval', $_POST['otp'])) : '';

    if (empty($otp)) {
        $error_message = "OTP is missing!";
    } else {
        $stmt = $pdo->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
                // OTP is valid, redirect to new password page
                header("Location: newpass.php");
                exit;
            } else {
                $error_message = "Invalid or expired OTP!";
            }
        } else {
            $error_message = "User not found!";
        }
    }
}
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
                <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>

            <!-- HTML for OTP verification -->
            <form action="verify-forgot.php" method="POST" class="otp-form">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
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

  <script src="js/new.js"></script>
</body>
</html>
