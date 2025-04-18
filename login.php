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
        $stmt = $pdo->prepare("SELECT id, username, password, is_verified, email FROM users WHERE email = ?");
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, is_verified, email FROM users WHERE username = ?");
    }

    $stmt->execute([$username_or_email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($_POST['password'], $user['password'])) {
            if ($user['is_verified'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email']; // 👈 ADD THIS LINE (to store email in session)

                    // 👇 Add this block to update ALL pending collaborators for this user
                        $updateStmt = $pdo->prepare("
                        UPDATE collaborators 
                        SET 
                            status = 'accepted',
                            user_id = ?  
                            WHERE (email = ? OR user_id = ?)
                            AND status = 'pending'
                    ");
                    $updateStmt->execute([$_SESSION['user_id'], $_SESSION['email'], $_SESSION['user_id']]);
                    // ======== END COLLABORATOR UPDATE ========

                // ======== COLLABORATOR STATUS UPDATE ========
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

                // Default redirect for regular login
                header("Location: main.php");
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

        <!-- 👇 Add error display -->
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
                 
            <div class="log-con"> 
                <input type="password" name="password" class="log" placeholder="Password" required>
                <img src="ORGanizepics/padlock.png" class="ics" alt="Padlock Icon">
            </div>
            
            <!-- Forgot password positioned under the password field, right aligned -->
            <div class="forgot-container">
                <a href="forgot.php" class="forgot">Forgot password?</a> 
            </div>
            
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
    <script src="js/new.js"></script>
</body>
</html>
