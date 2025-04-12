<?php
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
    $organization = $_POST['organization']; // New field

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

    elseif (!in_array($organization, [
        'Organization Alpha',
        'Organization Beta',
        'Organization Gamma',
        'Organization Delta',
        'Organization Epsilon',
        'Organization Zeta',
        'Organization Eta',
        'Organization Theta',
        'Organization Iota',
        'Organization Kappa',
        'Organization Lambda',
        'Organization Mu',
        'Organization Nu',
        'Organization Xi',
        'Organization Omicron',
        'Organization Pi',
        'Organization Rho',
        'Organization Sigma',
        'Organization Tau',
        'Organization Upsilon',
    ])) {
        $error_message = "Please select a valid organization";
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
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, organization = ?, otp = ?, otp_expiry = ? WHERE email = ?");
                if (!$stmt->execute([$username, $hashed_password, $organization, $otp, $otp_expiry, $email])) {
                    $error_message = "Failed to update OTP. Please try again.";
                }
            } else {
                $error_message = "This email is already registered and verified!";
            }
        } else {
            // Insert a new user record
            $stmt = $pdo->prepare("INSERT INTO users (email, username, password, organization, otp, otp_expiry, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt->execute([$email, $username, $hashed_password, $organization, $otp, $otp_expiry, 0])) {
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

                $mail->setFrom($_ENV['EMAIL_USER'], 'Task Management System', false);
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
                        <p>Best regards,<br><strong>ORGanize+ Team</strong></p>
                    </div>
                ";
                $mail->AltBody = "Welcome, $username!\n\nYour OTP code is: $otp\nThis OTP is valid for 3 hours.\n\nIf you didn't request this, ignore this email.\n\nBest regards,\nORGanize+ Team";

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

        <div class="log-con">
        <select name="organization" class="log" required>
        <option value="Organization Alpha">Organization Alpha</option>
        <option value="Organization Beta">Organization Beta</option>
        <option value="Organization Gamma">Organization Gamma</option>
        <option value="Organization Delta">Organization Delta</option>
        <option value="Organization Epsilon">Organization Epsilon</option>
        <option value="Organization Zeta">Organization Zeta</option>
        <option value="Organization Eta">Organization Eta</option>
        <option value="Organization Theta">Organization Theta</option>
        <option value="Organization Iota">Organization Iota</option>
        <option value="Organization Kappa">Organization Kappa</option>
        <option value="Organization Lambda">Organization Lambda</option>
        <option value="Organization Mu">Organization Mu</option>
        <option value="Organization Nu">Organization Nu</option>
        <option value="Organization Xi">Organization Xi</option>
        <option value="Organization Omicron">Organization Omicron</option>
        <option value="Organization Pi">Organization Pi</option>
        <option value="Organization Rho">Organization Rho</option>
        <option value="Organization Sigma">Organization Sigma</option>
        <option value="Organization Tau">Organization Tau</option>
        <option value="Organization Upsilon">Organization Upsilon</option>
            <!-- Add more organizations as needed -->
        </select>
        </div>

          <button type="submit">Register</button>
      </form>
      
      <?php if (isset($error_message)): ?>
          <p style="color: red; text-align: center;"><?= htmlspecialchars($error_message) ?></p>
      <?php endif; ?>
      
      <p>Already have an account? <a href="login.php">Login</a></p>
  </div>
  <script src="js/new.js"></script>
</body>
</html>