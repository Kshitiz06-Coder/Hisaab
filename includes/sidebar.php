<?php
$current = basename($_SERVER['PHP_SELF']);
function nav_active($page, $current) { return $page === $current ? 'active' : ''; }
?>
<aside class="sidebar" id="sidebar">
  <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">✕</button>
  <div class="sidebar-brand">
    <a href="dashboard.php" class="brand">
      <span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span> Hisaab
    </a>
  </div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="<?= nav_active('dashboard.php', $current) ?>"><span class="nav-ic"><img src="img/Dashboard.png" alt="Dashboard"></span> Dashboard</a>
    <a href="income.php" class="<?= nav_active('income.php', $current) ?>"><span class="nav-ic"><img src="img/Savings.png" alt="Income"></span> Income</a>
    <a href="expenses.php" class="<?= nav_active('expenses.php', $current) ?>"><span class="nav-ic"><img src="img/Expense.png" alt="Expense"></span> Expenses</a>
    <a href="savings.php" class="<?= nav_active('savings.php', $current) ?>"><span class="nav-ic"><img src="img/Income.png" alt="Savings"></span> Savings</a>
    <a href="reports.php" class="<?= nav_active('reports.php', $current) ?>"><span class="nav-ic"><img src="img/Reports.png" alt="Reports"></span> Reports</a>
    <div class="nav-section-label">Account</div>
    <a href="settings.php" class="<?= nav_active('settings.php', $current) ?>"><span class="nav-ic"><img src="img/Settings.png" alt="Settings"></span> Settings</a>
  </nav>
  <div class="sidebar-foot">
    <div class="sidebar-user">
      <div class="avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
      <div style="min-width:0;">
        <div class="name"><?= e($user['full_name']) ?></div>
        <div class="email"><?= e($user['email']) ?></div>
      </div>
    </div>
    <a href="logout.php" class="logout-link">↪ Log out</a>
  </div>
</aside>
<div class="overlay" id="overlay"></div>
