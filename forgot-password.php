<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/mailer.php';

if (!empty($_SESSION['user_id'])) redirect('dashboard.php');

$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($found) {
            // Invalidate any earlier reset codes for this account
            $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ? AND purpose = 'reset'");
            mysqli_stmt_bind_param($del, 'i', $found['id']);
            mysqli_stmt_execute($del);

            $otp = generate_otp();
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $ins = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'reset', ?)");
            mysqli_stmt_bind_param($ins, 'iss', $found['id'], $otp, $expires);
            mysqli_stmt_execute($ins);

            send_otp_email($found['email'], $found['full_name'], $otp);

            $_SESSION['reset_user_id'] = $found['id'];
            $_SESSION['reset_email'] = $found['email'];
        }
        // Same message whether or not the account exists — avoids revealing which emails are registered
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password · Hisaab</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/addon.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-visual">
    <a href="index.php" class="brand" style="color:#fff;">
      <span class="brand-mark" style="background:rgba(255,255,255,.15);"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab
    </a>
    <div>
      <h2>Forgot your password? No worries.</h2>
      <p class="quote">We'll send a 6-digit verification code to your email so you can get back into your account safely.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Reset password</h1>
      <p class="subtitle">Enter the email linked to your account</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>

      <?php if ($sent): ?>
        <div class="alert alert-success">✓ If an account exists for that email, a 6-digit code has been sent. Check your inbox (and spam folder).</div>
        <a href="verify-otp.php" class="btn btn-primary btn-block">Enter verification code</a>
        <div class="form-foot">Didn't get an email? <a href="forgot-password.php">Try again</a></div>
      <?php else: ?>
        <form method="POST" novalidate>
          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send verification code</button>
        </form>
        <div class="form-foot">Remembered your password? <a href="login.php">Log in</a></div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
