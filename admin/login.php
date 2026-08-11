<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// If no admin exists yet, route to first-run setup instead.
$countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM admins");
if ((int) mysqli_fetch_assoc($countRes)['c'] === 0) {
    redirect('setup.php');
}

if (!empty($_SESSION['admin_id'])) redirect('dashboard.php');

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($found && password_verify($password, $found['password'])) {
        $_SESSION['admin_id'] = $found['id'];
        $_SESSION['admin_name'] = $found['full_name'];
        redirect('dashboard.php');
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
<title>Admin Log In · Hisaab</title>
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
      <h2>Administrator access.</h2>
      <p class="quote">Manage every account on Hisaab — review activity, send warnings, and ban accounts that break the rules.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="../img/Logo.png" alt="Hisaab Logo"></span> Hisaab</div>
      <h1>Admin log in</h1>
      <p class="subtitle">This is a separate login from regular user accounts</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">⚠ <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="email">Admin email</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" required>
            <button type="button" class="toggle-password" data-target="password" aria-label="Show password"><img src="../img/show.png" alt="show" width="25" height="25"></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log in</button>
      </form>
      <div class="form-foot"><a href="../index.php">← Back to Hisaab</a></div>
    </div>
  </div>
</div>
<script src="../js/password-toggle.js?v=2"></script>
</body>
</html>
