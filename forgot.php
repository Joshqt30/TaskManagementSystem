<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

include 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_verified = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
      $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE email = ?");
        if ($stmt->execute([$otp, $email])) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['EMAIL_USER'];
                $mail->Password = $_ENV['EMAIL_PASS'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom($_ENV['EMAIL_USER'], 'ORGanize+');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP';
                $mail->Body = "<h1>Your OTP: $otp</h1><p>Valid for 10 minutes.</p>";
                $mail->send();

                $_SESSION['reset_email'] = $email;
                echo 
                "<script>
                alert('Verification email sent to $email');
                window.location.href = 'verify-forgot.php';
                 </script>";

                header("Location: verify-forgot.php");
                exit;
            } catch (Exception $e) {
                $error = "Failed to send OTP. Try again.";
            }
        } else {
            $error = "Database error. Try again.";
        }
    } else {
        $error = "Email not found or not verified!";
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
  <link href="designs/forgot.css" rel="stylesheet" />
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
      <h1>Forgot Password</h1>
      <p>Please enter your registered Email account.</p>

      <form method="POST">
        <div class="input-group mb-4">
          <input
            type="email"
            name="email"
            class="form-control"
            placeholder="Enter your email"
            required
          />
          <span class="input-group-text">
            <i class="fa-solid fa-envelope"></i>
          </span>
        </div>
        <button type="submit" class="next-button">Next</button>
      </form>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/new.js"></script>
</body>
</html>
