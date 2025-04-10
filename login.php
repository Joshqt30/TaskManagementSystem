<?php
// Enable error reporting for debugging (remove these in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    // Determine if the input is an email or username
    if (filter_var($username_or_email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT id, username, password, is_verified FROM users WHERE email = ?");
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, is_verified FROM users WHERE username = ?");
    }

    $stmt->execute([$username_or_email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 1) {
                // Login successful, redirect to main.php
                header("Location: main.php");
                exit;
            } else {
                $error_message = "Your account is not verified yet. Please verify your email first.";
            }
        } else {
            $error_message = "Incorrect password!";
        }
    } else {
        $error_message = "Username or email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="designs/login.css">
    <title>Login</title>
</head>
<body>

    <header>
        <img src="ORGanizepics/layers.png" class="ic" alt="Logo">
        <h2>ORGanize+</h2>
    </header>

    <div class="container">
        <h1>Login</h1>
        
        <form method="POST">
            <div class="log-con"> 
                <input type="text" name="username_or_email" class="log" placeholder="Username or Email" required>
                <img src="ORGanizepics/user.png" class="ics" alt="User Icon">
            </div>
                 
            <div class="log-con"> 
                <input type="password" name="password" class="log" placeholder="Password" required>
                <img src="ORGanizepics/padlock.png" class="ics" alt="Padlock Icon">
            </div>
            
            <!-- Forgot password positioned under the password field, right aligned -->
            <div class="forgot-container">
                <a href="#" class="forgot">Forgot password?</a> 
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <?php if ($error_message): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        
        <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
    <script src="js/new.js"></script>
</body>
</html>
