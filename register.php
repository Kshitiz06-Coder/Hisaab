<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/mailer.php';

if (!empty($_SESSION['user_id'])) redirect('login.php');

$errors = [];
$full_name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    // Domain-level reality check: does this email's domain even accept mail?
    // Catches typos and made-up domains (e.g. "test@doesnotexist123.com").
    if (!$errors && function_exists('checkdnsrr')) {
        $domain = substr(strrchr($email, '@'), 1);
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            $errors[] = "The domain \"$domain\" doesn't appear to accept email. Check for typos.";
        }
    }

    $existing = null;
    if (!$errors) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($existing && (int)$existing['is_verified'] === 1) {
            $errors[] = 'An account with this email already exists. Please log in instead.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($existing) {
            // Unverified account from a previous, abandoned signup attempt — reuse it.
            $user_id = $existing['id'];
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ?, password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ssi', $full_name, $hash, $user_id);
            mysqli_stmt_execute($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, is_verified) VALUES (?, ?, ?, 0)");
            mysqli_stmt_bind_param($stmt, 'sss', $full_name, $email, $hash);
            mysqli_stmt_execute($stmt);
            $user_id = mysqli_insert_id($conn);
        }

        if (issue_register_otp($conn, $user_id, $email, $full_name)) {
            $_SESSION['pending_verify_user_id'] = $user_id;
            $_SESSION['pending_verify_email'] = $email;
            redirect('verify-email.php');
        } else {
            $errors[] = "We couldn't send a verification email right now. Please try again in a moment.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account · Hisaab</title>
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
      <h2>Join the sustainable finance movement.</h2>
      <p class="quote">"Simplified accounting for smarter tracking" — take control of your income, expenses, and savings goals in one clean dashboard.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Create account</h1>
      <p class="subtitle">Start your financial journey today</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" placeholder="Your Name" value="<?= e($full_name) ?>" required>
        </div>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= e($email) ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="At least 6 characters" required minlength="6">
        </div>
        <div class="field">
          <label for="confirm_password">Confirm password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create account</button>
      </form>
      <div class="form-foot">Already have an account? <a href="login.php">Log in</a></div>
    </div>
  </div>
</div>
</body>
</html>
