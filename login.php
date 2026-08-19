<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/mailer.php';

if (!empty($_SESSION['user_id'])) redirect('dashboard.php');

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $found = mysqli_fetch_assoc($result);

    if ($found && password_verify($password, $found['password'])) {
        if ((int)$found['is_banned'] === 1) {
            $reason = $found['ban_reason'] ? ' Reason: ' . $found['ban_reason'] : '';
            $errors[] = 'Your account has been suspended by an administrator.' . $reason;
        } elseif ((int)$found['is_verified'] === 0) {
            // Reuse an existing unexpired code if there is one, otherwise send a fresh one
            $stmt2 = mysqli_prepare($conn, "SELECT * FROM password_resets WHERE user_id = ? AND purpose = 'register' ORDER BY id DESC LIMIT 1");
            mysqli_stmt_bind_param($stmt2, 'i', $found['id']);
            mysqli_stmt_execute($stmt2);
            $existing_otp = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

            if (!$existing_otp || strtotime($existing_otp['expires_at']) < time()) {
                issue_register_otp($conn, $found['id'], $found['email'], $found['full_name']);
            }

            $_SESSION['pending_verify_user_id'] = $found['id'];
            $_SESSION['pending_verify_email'] = $found['email'];
            flash('error', 'Please verify your email before logging in. We sent a code to ' . $found['email'] . '.');
            redirect('verify-email.php');
        } else {
            $_SESSION['user_id'] = $found['id'];
            redirect('dashboard.php');
        }
    } else {
        $errors[] = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In · Hisaab</title>
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
      <h2>Welcome back to your financial dashboard.</h2>
      <p class="quote">Track income, control expenses, and hit your savings goals — all in one calm, clear place.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Log in</h1>
      <p class="subtitle">Enter your details to continue</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>
      <?php if ($ok = flash('success')): ?>
        <div class="alert alert-success">✓ <?= e($ok) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= e($email) ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="Your password" required>
            <button type="button" class="toggle-password" data-target="password" aria-label="Show password"><img src="img/show.png" alt="show" width="25" height="25"></button>
          </div>
          <div style="text-align:right;margin-top:6px;"><a href="forgot-password.php" style="font-size:12.5px;color:var(--green-700);font-weight:600;">Forgot password?</a></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log in</button>
      </form>
      <div class="form-foot">Don't have an account? <a href="register.php">Create one</a></div>
    </div>
  </div>
</div>
<script src="js/password-toggle.js?v=2"></script>
</body>
</html>
