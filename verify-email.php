<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/mailer.php';

if (!empty($_SESSION['user_id'])) redirect('dashboard.php');
if (empty($_SESSION['pending_verify_user_id'])) redirect('register.php');

$user_id = $_SESSION['pending_verify_user_id'];
$email = $_SESSION['pending_verify_email'];
$errors = [];
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($u && issue_register_otp($conn, $user_id, $u['email'], $u['full_name'])) {
            $notice = 'A new code has been sent to ' . $u['email'] . '.';
        } else {
            $errors[] = "We couldn't resend the code right now. Please try again shortly.";
        }
    } else {
        $entered = trim($_POST['otp'] ?? '');
        $stmt = mysqli_prepare($conn, "SELECT * FROM password_resets WHERE user_id = ? AND purpose = 'register' ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $otp_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$otp_row) {
            $errors[] = 'No active code found. Please request a new one.';
        } elseif (strtotime($otp_row['expires_at']) < time()) {
            $errors[] = 'This code has expired. Please request a new one.';
        } elseif (!hash_equals($otp_row['otp_code'], $entered)) {
            $errors[] = 'Incorrect code. Double check your email and try again.';
        } else {
            $upd = mysqli_prepare($conn, "UPDATE users SET is_verified = 1 WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'i', $user_id);
            mysqli_stmt_execute($upd);

            $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ? AND purpose = 'register'");
            mysqli_stmt_bind_param($del, 'i', $user_id);
            mysqli_stmt_execute($del);

            unset($_SESSION['pending_verify_user_id'], $_SESSION['pending_verify_email']);
            // Session assignment removed so they have to log in manually
            flash('success', 'Email verified! Please log in to access your dashboard.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email · Hisaab</title>
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
      <h2>One last step.</h2>
      <p class="quote">We sent a 6-digit code to <?= e($email) ?> to confirm it's really you. It's valid for 10 minutes.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Verify your email</h1>
      <p class="subtitle">Sent to <?= e($email) ?></p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>
      <?php if ($flashErr = flash('error')): ?>
        <div class="alert alert-error">⚠ <?= e($flashErr) ?></div>
      <?php endif; ?>
      <?php if ($notice): ?>
        <div class="alert alert-success">✓ <?= e($notice) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="otp">6-digit code</label>
          <input type="text" id="otp" name="otp" class="otp-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Verify &amp; continue</button>
      </form>
      <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="resend" value="1">
        <button type="submit" class="btn btn-ghost btn-block">Resend code</button>
      </form>
      <div class="form-foot"><a href="register.php">Used the wrong email? Start over</a></div>
    </div>
  </div>
</div>
</body>
</html>
