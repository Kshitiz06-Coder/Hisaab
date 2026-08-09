<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

if (!empty($_SESSION['user_id'])) redirect('dashboard.php');
if (empty($_SESSION['reset_user_id'])) redirect('forgot-password.php');

$user_id = $_SESSION['reset_user_id'];

// Confirm there's a verified, unexpired OTP for this session before allowing a reset
$stmt = mysqli_prepare($conn, "SELECT * FROM password_resets WHERE user_id = ? AND is_verified = 1 AND purpose = 'reset' ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$verified = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$verified || strtotime($verified['expires_at']) < time()) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);
    flash('error', 'Your verification session expired. Please request a new code.');
    redirect('forgot-password.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'si', $hash, $user_id);
        mysqli_stmt_execute($upd);

        $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ? AND purpose = 'reset'");
        mysqli_stmt_bind_param($del, 'i', $user_id);
        mysqli_stmt_execute($del);

        unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);
        flash('success', 'Password reset successfully. Please log in with your new password.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password · Hisaab</title>
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
      <h2>Almost done.</h2>
      <p class="quote">Choose a new password to finish resetting your account.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Set a new password</h1>
      <p class="subtitle">Make it something you'll remember</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="password">New password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="At least 6 characters" minlength="6" required>
            <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁️</button>
          </div>
        </div>
        <div class="field">
          <label for="confirm_password">Confirm new password</label>
          <div class="password-wrap">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" minlength="6" required>
            <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">👁️</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Reset password</button>
      </form>
    </div>
  </div>
</div>
<script src="js/password-toggle.js"></script>
</body>
</html>
