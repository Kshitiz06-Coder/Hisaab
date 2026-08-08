<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/mailer.php';

if (!empty($_SESSION['user_id'])) redirect('dashboard.php');
if (empty($_SESSION['reset_user_id'])) redirect('forgot-password.php');

$user_id = $_SESSION['reset_user_id'];
$email = $_SESSION['reset_email'];
$errors = [];
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($u) {
            $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ? AND purpose = 'reset'");
            mysqli_stmt_bind_param($del, 'i', $user_id);
            mysqli_stmt_execute($del);

            $otp = generate_otp();
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $ins = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'reset', ?)");
            mysqli_stmt_bind_param($ins, 'iss', $user_id, $otp, $expires);
            mysqli_stmt_execute($ins);

            send_otp_email($u['email'], $u['full_name'], $otp);
            $notice = 'A new code has been sent to ' . $u['email'] . '.';
        }
    } else {
        $entered = trim($_POST['otp'] ?? '');
        $stmt = mysqli_prepare($conn, "SELECT * FROM password_resets WHERE user_id = ? AND purpose = 'reset' ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $reset = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$reset) {
            $errors[] = 'No active code found for this account. Please request a new one.';
        } elseif (strtotime($reset['expires_at']) < time()) {
            $errors[] = 'This code has expired. Please request a new one.';
        } elseif (!hash_equals($reset['otp_code'], $entered)) {
            $errors[] = 'Incorrect code. Double check your email and try again.';
        } else {
            $upd = mysqli_prepare($conn, "UPDATE password_resets SET is_verified = 1 WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'i', $reset['id']);
            mysqli_stmt_execute($upd);
            redirect('reset-password.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Code · Hisaab</title>
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
      <h2>Check your inbox.</h2>
      <p class="quote">We sent a 6-digit code to <?= e($email) ?>. It's valid for 10 minutes.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Enter verification code</h1>
      <p class="subtitle">Sent to <?= e($email) ?></p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>
      <?php if ($notice): ?>
        <div class="alert alert-success">✓ <?= e($notice) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="otp">6-digit code</label>
          <input type="text" id="otp" name="otp" class="otp-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Verify code</button>
      </form>
      <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="resend" value="1">
        <button type="submit" class="btn btn-ghost btn-block">Resend code</button>
      </form>
      <div class="form-foot"><a href="forgot-password.php">Use a different email</a></div>
    </div>
  </div>
</div>
</body>
</html>
