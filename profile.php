<?php
require_once __DIR__ . '/includes/auth_check.php';
$currency = $user['currency'] ?: 'Rs';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_account') {
    $confirm_password = $_POST['confirm_password'] ?? '';
    $confirm_text = trim($_POST['confirm_text'] ?? '');

    if ($confirm_text !== 'DELETE') {
        flash('error', 'Please type DELETE exactly to confirm.');
        redirect('profile.php');
    } elseif (!password_verify($confirm_password, $user['password'])) {
        flash('error', 'Incorrect password. Account was not deleted.');
        redirect('profile.php');
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user['id']);
        mysqli_stmt_execute($stmt);
        // categories/income/expenses/savings cascade-delete via FK ON DELETE CASCADE
        session_unset();
        session_destroy();
        header('Location: index.php?account_deleted=1');
        exit;
    }
}

$total_income_all = get_total($conn, 'income', $user['id']);
$total_expense_all = get_total($conn, 'expenses', $user['id']);
$net_balance_all = $total_income_all - $total_expense_all;

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt, COALESCE(SUM(saved_amount),0) AS saved FROM savings WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$goal_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$page_title = 'Profile';
$page_sub = 'Your account at a glance';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/topbar.php';
?>

<div class="profile-hero">
  <div class="avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
  <div>
    <h2><?= e($user['full_name']) ?></h2>
    <p><?= e($user['email']) ?></p>
    <p>🗓 Member since <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
  </div>
</div>

<div class="profile-stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Income (all-time)</div>
    <div class="stat-value" style="color:var(--green-700);"><?= money($total_income_all, $currency) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Expenses (all-time)</div>
    <div class="stat-value" style="color:var(--red-600);"><?= money($total_expense_all, $currency) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Net Balance</div>
    <div class="stat-value"><?= money($net_balance_all, $currency) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Savings Goals</div>
    <div class="stat-value"><?= (int)$goal_info['cnt'] ?></div>
    <span class="stat-trend up"><?= money($goal_info['saved'], $currency) ?> saved total</span>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h3>Account details</h3>
    <a href="settings.php" class="btn btn-outline btn-sm">✏️ Edit in Settings</a>
  </div>
  <div class="card-body">
    <table class="data-table">
      <tbody>
        <tr><td style="color:var(--ink-500);width:180px;">Full name</td><td><strong><?= e($user['full_name']) ?></strong></td></tr>
        <tr><td style="color:var(--ink-500);">Email address</td><td><?= e($user['email']) ?></td></tr>
        <tr><td style="color:var(--ink-500);">Preferred currency</td><td><span class="pill"><?= e($currency) ?></span></td></tr>
        <tr><td style="color:var(--ink-500);">Account created</td><td><?= date('F j, Y', strtotime($user['created_at'])) ?></td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card danger-zone">
  <div class="card-head">
    <h3 style="color:var(--red-600);">⚠ Danger Zone</h3>
  </div>
  <div class="card-body">
    <div class="danger-row">
      <div>
        <div class="danger-title">Delete this account</div>
        <p class="danger-desc">This permanently deletes your profile and all income, expense, and savings data. This cannot be undone.</p>
      </div>
      <button class="btn btn-danger" data-modal-open="deleteAccountModal">Delete Account</button>
    </div>
  </div>
</div>

<!-- Delete Account Modal -->
<div class="modal-backdrop" id="deleteAccountModal">
  <div class="modal">
    <div class="modal-head"><h3 style="color:var(--red-600);">Delete your account?</h3><button class="modal-close" data-modal-close>✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="delete_account">
        <div class="alert alert-error" style="margin-bottom:18px;">
          ⚠ This will permanently erase your profile, all income, expenses, and savings goals. There is no way to undo this.
        </div>
        <div class="field">
          <label>Type <strong>DELETE</strong> to confirm</label>
          <input type="text" name="confirm_text" placeholder="DELETE" required autocomplete="off">
        </div>
        <div class="field">
          <label>Enter your password</label>
          <input type="password" name="confirm_password" placeholder="Your password" required>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-danger">Yes, permanently delete my account</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer_app.php'; ?>
