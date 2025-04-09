<?php

require_once __DIR__ . '/vendor/autoload.php';  

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

include 'config.php'; // Contains your $pdo connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    }
    else {
        // Check if the email is already in the database
        $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();

        // Generate OTP and set expiry time (3 hours)
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $otp_expiry = date("Y-m-d H:i:s", time() + (3 * 60 * 60));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($existingUser) {
            // If user exists but is NOT verified, update OTP info and resend OTP email.
            if ($existingUser['is_verified'] == 0) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, otp = ?, otp_expiry = ? WHERE email = ?");
                if (!$stmt->execute([$username, $hashed_password, $otp, $otp_expiry, $email])) {
                    $error_message = "Failed to update OTP. Please try again.";
                }
            } else {
                $error_message = "This email is already registered and verified!";
            }
        } else {
            // Insert a new user record
            $stmt = $pdo->prepare("INSERT INTO users (email, username, password, otp, otp_expiry, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt->execute([$email, $username, $hashed_password, $otp, $otp_expiry, 0])) {
                $error_message = "Registration failed. Please try again.";
            }
        }

        // If no error, send the OTP email
        if (!isset($error_message)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['EMAIL_USER'];
                $mail->Password = $_ENV['EMAIL_PASS'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Set consistent sender information
                $mail->setFrom($_ENV['EMAIL_USER'], 'Task Management System');
                $mail->addAddress($email);

                // Use HTML email and plain text alternative
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code for Task Management System';

                // HTML version of the email
                $mail->Body = "<h3>Hello, " . htmlspecialchars($username) . "!</h3>
                               <p>Your one-time passcode (OTP) is: <strong>{$otp}</strong></p>
                               <p>This OTP is valid for 3 hours.</p>
                               <hr>
                               <p>Please do not reply to this email. If you did not request an OTP, simply ignore this message.</p>";

                // Plain text alternative (non-HTML)
                $mail->AltBody = "Hello $username,\n\nYour one-time passcode (OTP) is: $otp\nThis OTP is valid for 3 hours.\n\nPlease do not reply to this email. If you did not request an OTP, ignore this message.";

                $mail->send();
                header("Location: verification.php?email=" . urlencode($email));
                exit;
            } catch (Exception $e) {
                $error_message = "Email could not be sent. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="designs/register.css">
  <title>Register</title>
</head>
<body>
  <header>
      <img src="ORGanizepics/layers.png" class="ic" alt="Logo">
      <h2>ORGanize+</h2>
  </header>

  <div class="container">
      <h1>Register</h1>
      <form method="POST">
          <div class="log-con">
              <input type="email" name="email" class="log" placeholder="Email" required>
              <img src="ORGanizepics/email.png" class="ics" alt="Email Icon">
          </div>
          <div class="log-con">
              <input type="text" name="username" class="log" placeholder="Username" required>
              <img src="ORGanizepics/user.png" class="ics" alt="User Icon">
          </div>
          <div class="log-con">
              <input type="password" name="password" class="log" placeholder="Password" required>
              <img src="ORGanizepics/padlock.png" class="ics" alt="Padlock Icon">
          </div>
          <div class="log-con">
              <input type="password" name="confirm_password" class="log" placeholder="Confirm Password" required>
              <img src="ORGanizepics/padlock.png" class="ics" alt="Padlock Icon">
          </div>
          <button type="submit">Register</button>
      </form>
      
      <?php if (isset($error_message)): ?>
          <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error_message); ?></p>
      <?php endif; ?>
      
      <p>Already have an account? <a href="login.php">Login</a></p>
  </div>
</body>
</html>
