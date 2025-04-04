<?php
// OTP verification logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'config.php';  // Include the database connection

    $email = $_POST['email'];
    $otp = $_POST['otp'];

    // Prepare and execute a query to get the user data
    $stmt = $pdo->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if OTP is valid and not expired
        if ($user['otp'] == $otp && strtotime($user['otp_expiry']) > time()) {
            // Mark user as verified and clear OTP fields
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE email = ?");
            $stmt->execute([$email]);
            // Redirect to login page or dashboard
            header("Location: login.php");
            exit;
        } else {
            $error_message = "Invalid or expired OTP!";
        }
    } else {
        $error_message = "User not found!";
    }
} else {
    $error_message = "Invalid request.";
}
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
            <p>We have sent the OTP code to your email, please enter the code in the column below.</p>

            <?php if (isset($error_message)): ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form action="verification.php" method="POST">
                <input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">

                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="otp-box" id="otp1" name="otp[]">
                    <input type="text" maxlength="1" class="otp-box" id="otp2" name="otp[]">
                    <input type="text" maxlength="1" class="otp-box" id="otp3" name="otp[]">
                    <input type="text" maxlength="1" class="otp-box" id="otp4" name="otp[]">
                </div>

                <button type="submit" class="next-btn">Next</button>
            </form>
        </div>
    </div>

    <script src="new.js"></script>
</body>
</html>
