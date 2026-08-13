<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php session_start(); if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hisaab · Sustainable Finance System</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
  <nav class="landing-nav">
    <a href="index.php" class="brand"><span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab</a>
    <div class="nav-actions">
      <a href="login.php" class="btn btn-ghost">Log in</a>
      <a href="register.php" class="btn btn-primary">Get Started</a>
    </div>
  </nav>

  <?php if (!empty($_GET['account_deleted'])): ?>
    <div class="alert alert-success" style="margin-top:10px;">✓ Your account and all associated data have been permanently deleted.</div>
  <?php endif; ?>

  <section class="hero">
    <div>
      <span class="hero-eyebrow">🌱 Sustainable Finance System</span>
      <h1>Made simple <span>for everyday</span> users</h1>
      <p class="lead">Hissab helps you track income, control expenses, and grow your savings — one clean dashboard, zero spreadsheet headaches.</p>
      <div class="hero-cta">
        <a href="register.php" class="btn btn-primary">Start Free Today</a>
        <a href="login.php" class="btn btn-outline">I already have an account</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="mock-browser-bar"><span></span><span></span><span></span></div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <strong style="font-family:var(--font-display);font-size:15px;">This Month</strong>
        <span class="pill">📈 On track</span>
      </div>
      <div style="font-family:var(--font-display);font-size:30px;font-weight:700;color:var(--green-700);">Rs 6,250.00</div>
      <div style="font-size:13px;color:var(--ink-500);margin-bottom:16px;">Net balance</div>
      <div class="progress-track"><div class="progress-fill" style="width:68%;"></div></div>
      <div class="hero-stat-card floating-1">
        <div class="label">Income</div>
        <div class="value">Rs 8,250</div>
      </div>
      <div class="hero-stat-card floating-2">
        <div class="label">Expenses</div>
        <div class="value" style="color:var(--red-600);">Rs 1,450</div>
      </div>
    </div>
  </section>
</div>

<section class="features">
  <div class="container">
    <div class="section-head">
      <h2>Everything you need to stay on top of money</h2>
      <p>Three simple pillars: track it, manage it, grow it.</p>
    </div>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="feature-icon"><img src="img/Income.png" alt="Income"></div>
        <h3>Track Income</h3>
        <p>Log every source of income — salary, freelance, business — and see it roll up automatically.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><img src="img/Expense.png" alt="Expense"></div>
        <h3>Manage Expenses</h3>
        <p>Categorize spending, spot patterns, and catch overspending before it becomes a habit.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><img src="img/Savings.png" alt="Savings"></div>
        <h3>Set Savings Goals</h3>
        <p>Give every rupee a job. Set targets, track progress, and celebrate milestones.</p>
      </div>
    </div>
  </div>
</section>

<div class="container">
  <div class="cta-band">
    <h2>Start your financial journey today</h2>
    <p>Free to use. No spreadsheets. No stress.</p>
    <a href="register.php" class="btn btn-primary">Create your account</a>
  </div>
</div>

<footer class="site-footer">
  © <?= date('Y') ?> Hissab — Sustainable Finance System. Built as a college project.
</footer>

</body>
</html>
