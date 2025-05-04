<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/vendor/autoload.php';  

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

include 'config.php'; // Contains your $pdo connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
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
    // Password strength check
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,20}$/', $password)) {
        $error_message = "Password must be 8-20 chars with uppercase, lowercase, and number!";
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
                $error_message = "This email is already registered!";
            }
        } else {
            // Insert a new user record with role 'user'
            $stmt = $pdo->prepare("
                INSERT INTO users (email, username, password, otp, otp_expiry, is_verified, created_at, role)
                VALUES (?, ?, ?, ?, ?, ?, NOW(),'user')
            ");
            if (!$stmt->execute([$email, $username, $hashed_password, $otp, $otp_expiry, 0])) {
                $error_message = "Registration failed. Please try again.";
            } else
            // After successful user insertion
            $new_user_id = $pdo->lastInsertId();  // Get the newly created user's ID

            // Log the activity
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO activity_log 
                    (user_id, activity_type, description)
                    VALUES (?, 'registration', 'New user registered')
                ");
                $stmt->execute([$new_user_id]);
            } catch(PDOException $e) {
                // Optional: Log error but don't break registration
                error_log("Activity log failed: " . $e->getMessage());
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

                $mail->setFrom($_ENV['EMAIL_USER'], 'ORGanizePLUS', false);
                $mail->addReplyTo($_ENV['EMAIL_USER'], 'Task Management Support');
                $mail->addAddress($email);
                $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
                $mail->addCustomHeader('X-Priority', '3');
                $mail->addCustomHeader('Return-Path', $_ENV['EMAIL_USER']);

                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code for ORGanize+';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; color: #333;'>
                        <h2>Hello, " . htmlspecialchars($username) . "!</h2>
                        <p>We're excited to have you. Your one-time passcode (OTP) is:</p>
                        <h1 style='color: #014BFE;'>$otp</h1>
                        <p><strong>This OTP is valid for 3 hours.</strong></p>
                        <p>If you did not request this, please ignore this email.</p>
                        <p>For any issues, contact our support team at <a href='mailto:organizeplusmail@gmail.com'>organizeplusmail@gmail.com</a>.</p>
                        <hr>
                        <p>Best regards,<br><strong>ORGanizePLUS Team</strong></p>
                    </div>
                ";
                $mail->AltBody = "Welcome, $username!\n\nYour OTP code is: $otp\nThis OTP is valid for 3 hours.\n\nIf you didn't request this, ignore this email.\n\nBest regards,\nORGanize+ Team";

                $mail->send();
                header("Location: verification.php?email=" . urlencode($email));
                exit;
            } catch (Exception $e) {
                error_log("Mailer Error: " . $e->getMessage());
                error_log("Mailer Debug: " . $mail->ErrorInfo);
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

      <?php if (isset($error_message)): ?>
          <div class="alert alert-danger" role="alert">
             <?= htmlspecialchars($error_message) ?>
          </div>
      <?php endif; ?>

      <form method="POST">
          <div class="log-con">
              <input type="email" name="email" class="log" placeholder="Email" 
                  value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
              <img src="ORGanizepics/email.png" class="ics" alt="Email Icon">
          </div>
          <div class="log-con">
              <input type="text" name="username" class="log" placeholder="Username" 
                  value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
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
      <p>Already have an account? <a href="login.php">Login</a></p>
  </div>
  <script src="js/new.js"></script>
</body>
</html>