<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $otp = implode("", $_POST['otp']); // Combine the four input fields

    // Query the user record by email
    $stmt = $pdo->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if OTP matches and is not expired
        if ($user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
            // Mark user as verified and clear the OTP fields
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE email = ?");
            $stmt->execute([$email]);
            header("Location: login.php");
            exit;
        } else {
            $error_message = "Invalid or expired OTP!";
        }
    } else {
        $error_message = "User not found!";
    }
}

// Get the email for display (from GET parameter when the page loads)
$emailValue = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="designs/verification.css">
    <title>OTP Verification</title>
</head>
<body>
    <header>
        <img src="ORGanizepics/layers.png" class="ic" alt="Logo">
        <h2>ORGanize+</h2>
    </header>

    <div class="container">
        <div class="otp-verification">
            <div class="class-otp">OTP CODE VERIFICATION</div>
            <p>We have sent the OTP code to your email. Please enter the code below.</p>

            <?php if (isset($error_message)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <form action="verification.php" method="POST">
                <!-- Pass the email along in a hidden field -->
                <input type="hidden" name="email" value="<?php echo $emailValue; ?>">
                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="otp-box" id="otp1" name="otp[]">
                    <input type="text" maxlength="1" class="otp-box" id="otp2" name="otp[]">
                    <input type="text" maxlength="1" class="otp-box" id="otp3" name="otp[]">
                    <input type="text" maxlength="1" class="otp-box" id="otp4" name="otp[]">
                </div>
                <button type="submit" class="next-btn">Verify</button>
            </form>
        </div>
    </div>

    <script src="new.js"></script>
</body>
</html>
