<?php
// Registration logic (if needed)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'config.php';  // Include the database connection

    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);  // Encrypt the password
        $otp = rand(1000, 9999);  // Generate OTP
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));  // OTP expiry time (5 minutes from now)

        // Insert user data into the database
        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, otp, otp_expiry) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $username, $password, $otp, $otp_expiry]);

        // Send OTP email
        mail($email, "OTP for Registration", "Your OTP is: " . $otp);

        // Redirect to verification page
        header("Location: verification.php?email=" . $email);
        exit();
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
