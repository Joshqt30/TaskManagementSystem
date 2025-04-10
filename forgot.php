<?php
include 'config.php'; // DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = rand(100000, 999999); // generate 6-digit OTP

        // Store OTP in DB or session
        $stmt = $pdo->prepare("UPDATE users SET otp = ? WHERE email = ?");
        $stmt->execute([$otp, $email]);

        // Send OTP to email (use actual mail logic in production)
        // mail($email, "Password Reset OTP", "Your OTP is: $otp");

        session_start();
        $_SESSION['reset_email'] = $email;

        header("Location: verification.php?purpose=reset");
        exit;
    } else {
        $error = "Email not found!";
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

      <form id="forgot-form" onsubmit="handleSubmit(event)">
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
  <script>
    function handleSubmit(e) {
      e.preventDefault();
      const email = document.getElementById('forgot-form').email.value;
      alert('Verification email sent to ' + email);
    }
  </script>
</body>
</html>
