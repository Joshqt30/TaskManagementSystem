<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    
    // Convert email to lowercase if it's an email
    if (filter_var($username_or_email, FILTER_VALIDATE_EMAIL)) {
        $username_or_email = strtolower($username_or_email);
        $stmt = $pdo->prepare("SELECT id, username, password, is_verified, email, role FROM users WHERE email = ?");
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, is_verified, email, role FROM users WHERE username = ?");
    }

    $stmt->execute([$username_or_email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($_POST['password'], $user['password'])) {
            if ($user['is_verified'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role']; // Store the role in session

                // Update pending collaborators
                $updateStmt = $pdo->prepare("
                    UPDATE collaborators 
                    SET 
                    status = 'accepted',
                    user_id = ?  
                    WHERE (email = ? OR user_id = ?)
                    AND status = 'pending'
                ");
                $updateStmt->execute([$_SESSION['user_id'], $_SESSION['email'], $_SESSION['user_id']]);

                // Collaborator status update for specific task
                if (isset($_GET['task_id'])) {
                    $taskId = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);
                    
                    if ($taskId) {
                        // Verify collaborator relationship
                        $stmtCheck = $pdo->prepare("
                            SELECT 1 
                            FROM collaborators 
                            WHERE user_id = ? AND task_id = ?
                        ");
                        $stmtCheck->execute([$_SESSION['user_id'], $taskId]);
                        
                        if ($stmtCheck->fetch()) {
                            // Update status to 'accepted'
                            $stmtUpdate = $pdo->prepare("
                                UPDATE collaborators 
                                SET status = 'accepted' 
                                WHERE user_id = ? AND task_id = ?
                            ");
                            $stmtUpdate->execute([$_SESSION['user_id'], $taskId]);
                            
                            // Redirect to task details
                            header("Location: main.php?id=$taskId");
                            exit();
                        }
                    }
                }

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: main.php");
                }
                exit();

            } else {
                $error_message = "Account not verified. Verify your email first.";
            }
        } else {
            $error_message = "Incorrect password!";
        }
    } else {
        $error_message = "Username/Email not found!";
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

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="log-con"> 
                <input type="text" name="username_or_email" class="log" placeholder="Username or Email" required>
                <img src="ORGanizepics/user.png" class="ics" alt="User Icon">
            </div>
                <!-- In your login.php form -->
                <div class="log-con"> 
                    <input type="password" name="password" class="log" placeholder="Password" required>
                    <img src="ORGanizepics/eye-closed.png" class="toggle-password" alt="Toggle Password">
                </div>
            <div class="forgot-container">
                <a href="forgot.php" class="forgot">Forgot password?</a> 
            </div>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </div>

    <script>
            // Toggle password visibility for both pages
                document.querySelectorAll('.toggle-password').forEach(icon => {
                    icon.addEventListener('click', function() {
                        const input = this.closest('.log-con').querySelector('input');
                        if (input.type === 'password') {
                            input.type = 'text';
                            this.src = 'ORGanizepics/eye-open.png';
                        } else {
                            input.type = 'password';
                            this.src = 'ORGanizepics/eye-closed.png';
                        }
                    });
                });
    </script>


    <script src="js/new.js"></script>
</body>
</html>