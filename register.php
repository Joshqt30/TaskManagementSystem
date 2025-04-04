<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password Validation
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Send Email Confirmation
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL_USER'); // Use environment variable
            $mail->Password = getenv('EMAIL_PASS'); // Use environment variable
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email Settings
            $mail->setFrom(getenv('EMAIL_USER'), 'Task Management System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Registration Confirmation';
            $mail->Body = '<h3>Welcome, ' . htmlspecialchars($username) . '!</h3><p>You have successfully registered.</p>';

            $mail->send();
            echo "✅ Confirmation email sent!";
        } catch (Exception $e) {
            echo "❌ Email Error: {$mail->ErrorInfo}";
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
                <img src="ORGanizepics/email.png" class="ics">
            </div>
            <div class="log-con">
                <input type="text" name="username" class="log" placeholder="Username" required>
                <img src="ORGanizepics/user.png" class="ics">
            </div>
            <div class="log-con">
                <input type="password" name="password" class="log" placeholder="Password" required>
                <img src="ORGanizepics/padlock.png" class="ics">
            </div>
            <div class="log-con">
                <input type="password" name="confirm_password" class="log" placeholder="Confirm Password" required>
                <img src="ORGanizepics/padlock.png" class="ics">
            </div>
            <button type="submit">Register</button>
        </form>
        
        <!-- Error message for mismatched passwords -->
        <?php if (isset($error_message)): ?>
            <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
