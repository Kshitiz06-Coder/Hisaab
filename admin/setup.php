<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// Lock this page the moment at least one admin exists.
$countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM admins");
$adminCount = (int) mysqli_fetch_assoc($countRes)['c'];

if ($adminCount > 0) {
    // Already set up — send anyone who lands here to the real login.
    redirect('login.php');
}

$errors = [];
$full_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($full_name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        // Re-check right before inserting to close the race window.
        $recheck = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM admins"));
        if ((int)$recheck['c'] > 0) {
            redirect('login.php');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "INSERT INTO admins (full_name, email, password) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $full_name, $email, $hash);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['admin_id'] = mysqli_insert_id($conn);
            $_SESSION['admin_name'] = $full_name;
            redirect('dashboard.php');
        } else {
            $errors[] = 'Could not create the admin account. Try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Setup · Hisaab</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/addon.css">
<link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-visual admin-auth-visual">
    <a href="../index.php" class="brand" style="color:#fff;">
      <span class="brand-mark" style="background:rgba(255,255,255,.15);"><img src="../img/Logo.png" alt="Hisaab Logo"></span> Hisaab <span class="admin-tag">Admin</span>
    </a>
    <div>
      <h2>One-time admin setup.</h2>
      <p class="quote">This page creates the first administrator account for Hisaab. It locks itself automatically once an admin exists — for security, delete or restrict access to this file after you're done.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="../img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Create admin account</h1>
      <p class="subtitle">No administrators exist yet — set up the first one</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" value="<?= e($full_name) ?>" required>
        </div>
        <div class="field">
          <label for="email">Admin email</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
            <button type="button" class="toggle-password" data-target="password" aria-label="Show password"><img src="../img/show.png" alt="show" width="25" height="25"></button>
          </div>
        </div>
        <div class="field">
          <label for="confirm">Confirm password</label>
          <div class="password-wrap">
            <input type="password" id="confirm" name="confirm" required>
            <button type="button" class="toggle-password" data-target="confirm" aria-label="Show password"><img src="../img/show.png" alt="show" width="25" height="25"></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create admin account</button>
      </form>
      <div class="form-foot">Already set up? <a href="login.php">Go to admin login</a></div>
    </div>
  </div>
</div>
<script src="../js/password-toggle.js?v=2"></script>
</body>
</html>
