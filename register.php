<?php

// Include the Composer autoload file to load all dependencies, including PHPMailer and Dotenv
require_once __DIR__ . '/vendor/autoload.php';  

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

include 'config.php'; // Contains your $pdo connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grab the submitted values
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic password match validation
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Generate a random 4-digit OTP
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        // Set expiry time (e.g., 5 minutes from now)
        $otp_expiry = date("Y-m-d H:i:s", time() + 300);

        // Hash the password (always use a strong hash method)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the database with is_verified set to false (0)
        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, otp, otp_expiry, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$email, $username, $hashed_password, $otp, $otp_expiry, 0])) {
            // Prepare PHPMailer to send the OTP email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;

                // Fetch email credentials from the .env file
                $emailUser = $_ENV['EMAIL_USER'];  // Get the email from .env
                $emailPass = $_ENV['EMAIL_PASS'];  // Get the password from .env

                if (!$emailUser || !$emailPass) {
                    throw new Exception("Email credentials not found in the .env file.");
                }

                $mail->Username = $emailUser; // Use the environment variable for email
                $mail->Password = $emailPass;  // Use the environment variable for the app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Email settings
                $mail->setFrom($emailUser, 'Task Management System');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Verification Code';
                // Include the OTP in the email body
                $mail->Body = "<h3>Hello, " . htmlspecialchars($username) . "!</h3>
                            <p>Your OTP code is: <strong>{$otp}</strong></p>
                            <p>This code will expire in 5 minutes.</p>";

                $mail->send();
                // Redirect to the verification page with the email in the query string
                header("Location: verification.php?email=" . urlencode($email));
                exit;
            } catch (Exception $e) {
                $error_message = "Email could not be sent. Please try again later.";
            }
        } else {
            $error_message = "Registration failed. Please try again.";
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
        
        <!-- Display error message if exists -->
        <?php if (isset($error_message)): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
