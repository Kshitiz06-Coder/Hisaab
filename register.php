<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

if (!empty($_SESSION['user_id'])) redirect('dashboard.php');

$errors = [];
$full_name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // 1. Local basic validations
    if ($full_name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    // 2. Check if email exists in Database
    if (!$errors) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    // 3. Mailboxlayer Real-World API Validation
    if (!$errors) {
        $apiKey = 'de7d69d7c6bde8d05f10a87fff0c177e'; 
        $apiUrl = "https://apilayer.net/api/check?access_key={$apiKey}&email=" . urlencode($email);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        // Process API response if request was successful
        if ($data && !isset($data['error'])) {
            if (empty($data['format_valid'])) {
                $errors[] = 'Invalid email syntax.';
            } elseif (!empty($data['did_you_mean'])) {
                $errors[] = 'Did you mean ' . htmlspecialchars($data['did_you_mean']) . '?';
            } elseif (empty($data['mx_found'])) {
                $errors[] = 'The email domain cannot receive messages (Invalid MX record).';
            } elseif (!empty($data['disposable'])) {
                $errors[] = 'Disposable or temporary email addresses are not allowed.';
            } elseif (isset($data['score']) && $data['score'] < 0.4) {
                $errors[] = 'This email address appears to be inactive or risky.';
            }
        }
    }

    // 4. Save to Database
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $full_name, $email, $hash);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['user_id'] = mysqli_insert_id($conn);
            flash('success', 'Welcome to Hissab, ' . $full_name . '! Your account is ready.');
            redirect('dashboard.php');
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hisaab</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-visual">
    <a href="index.php" class="brand" style="color:#fff;">
      <span class="brand-mark" style="background:rgba(255,255,255,.15);"><img src="Image/Logo.png" alt="Logo"></span> Hissab
    </a>
    <div>
      <h2>Join the sustainable finance movement.</h2>
      <p class="quote">"Simplified accounting for smarter tracking" — take control of your income, expenses, and savings goals in one clean dashboard.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="color:var(--green-700);"><span class="brand-mark"><img src="Image/Logo.png" alt="Logo"></span> Hisaab</div>
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