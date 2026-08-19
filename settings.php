<?php
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid name and email.');
        } else {
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($check, 'si', $email, $user['id']);
            mysqli_stmt_execute($check);
            if (mysqli_stmt_get_result($check)->num_rows > 0) {
                flash('error', 'That email is already in use.');
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, email=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'ssi', $full_name, $email, $user['id']);
                mysqli_stmt_execute($stmt);
                flash('success', 'Profile updated.');
            }
        }
    } elseif ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 6) {
            flash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            flash('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'si', $hash, $user['id']);
            mysqli_stmt_execute($stmt);
            flash('success', 'Password changed successfully.');
        }
    } elseif ($action === 'update_prefs') {
        $currency = trim($_POST['currency'] ?? 'Rs');
        $stmt = mysqli_prepare($conn, "UPDATE users SET currency=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'si', $currency, $user['id']);
        mysqli_stmt_execute($stmt);
        flash('success', 'Preferences saved.');
    } elseif ($action === 'update_notifications') {
        $notify_daily = isset($_POST['notify_daily']) ? 1 : 0;
        $notify_weekly = isset($_POST['notify_weekly']) ? 1 : 0;
        $notify_low_balance = isset($_POST['notify_low_balance']) ? 1 : 0;
        $threshold = (float)($_POST['low_balance_threshold'] ?? $user['low_balance_threshold']);
        if ($threshold < 0) $threshold = 0;
        $stmt = mysqli_prepare($conn, "UPDATE users SET notify_daily=?, notify_weekly=?, notify_low_balance=?, low_balance_threshold=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'iiidi', $notify_daily, $notify_weekly, $notify_low_balance, $threshold, $user['id']);
        mysqli_stmt_execute($stmt);
        flash('success', 'Notification preferences saved.');
    } elseif ($action === 'update_categories') {
        $income_selected = array_map('intval', $_POST['income_categories'] ?? []);
        $expense_selected = array_map('intval', $_POST['expense_categories'] ?? []);
        save_user_category_selection($conn, $user['id'], 'income', $income_selected);
        save_user_category_selection($conn, $user['id'], 'expense', $expense_selected);
        flash('success', 'Categories updated.');
    } elseif ($action === 'add_category') {
        $type = ($_POST['cat_type'] ?? '') === 'expense' ? 'expense' : 'income';
        $name = trim($_POST['cat_name'] ?? '');
        $icon = trim($_POST['cat_icon'] ?? '') ?: ($type === 'income' ? '💰' : '🧾');
        if ($name === '') {
            flash('error', 'Enter a category name.');
        } else {
            add_custom_category($conn, $user['id'], $type, $name, $icon);
            flash('success', 'Category added.');
        }
    } elseif ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        // Only allow deleting categories this user created themselves.
        $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user['id']);
        mysqli_stmt_execute($stmt);
        flash('success', 'Category removed.');
    }
    redirect('settings.php' . (isset($_POST['tab']) ? '#' . $_POST['tab'] : ''));
}

$income_defaults = [];
$res = get_default_categories($conn, 'income');
while ($c = mysqli_fetch_assoc($res)) $income_defaults[] = $c;

$expense_defaults = [];
$res = get_default_categories($conn, 'expense');
while ($c = mysqli_fetch_assoc($res)) $expense_defaults[] = $c;

$active_income_ids = get_active_default_category_ids($conn, $user['id'], 'income');
$active_expense_ids = get_active_default_category_ids($conn, $user['id'], 'expense');

$custom_income = [];
$res = get_categories($conn, $user['id'], 'income');
while ($c = mysqli_fetch_assoc($res)) if ($c['user_id']) $custom_income[] = $c;

$custom_expense = [];
$res = get_categories($conn, $user['id'], 'expense');
while ($c = mysqli_fetch_assoc($res)) if ($c['user_id']) $custom_expense[] = $c;

$page_title = 'Settings';
$page_sub = 'Manage your account';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/topbar.php';
?>

<div class="settings-grid">
  <div class="settings-tabs">
    <a href="#" data-tab-target="tab-profile" class="active">👤 Profile</a>
    <a href="#" data-tab-target="tab-security">🔒 Security</a>
    <a href="#" data-tab-target="tab-preferences">⚙️ Preferences</a>
    <a href="#" data-tab-target="tab-notifications">🔔 Notifications</a>
    <a href="#" data-tab-target="tab-categories">🏷️ Categories</a>
  </div>

  <div>
    <div class="settings-panel active" id="tab-profile">
      <div class="card">
        <div class="card-head"><h3>Profile information</h3></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="field-row">
              <div class="field"><label>Full name</label><input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required></div>
              <div class="field"><label>Email address</label><input type="email" name="email" value="<?= e($user['email']) ?>" required></div>
            </div>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </form>
        </div>
      </div>
    </div>

    <div class="settings-panel" id="tab-security">
      <div class="card">
        <div class="card-head"><h3>Change password</h3></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="update_password">
            <div class="field"><label>Current password</label><input type="password" name="current_password" required></div>
            <div class="field-row">
              <div class="field"><label>New password</label><input type="password" name="new_password" minlength="6" required></div>
              <div class="field"><label>Confirm new password</label><input type="password" name="confirm_password" minlength="6" required></div>
            </div>
            <button type="submit" class="btn btn-primary">Update password</button>
          </form>
        </div>
      </div>
    </div>

    <div class="settings-panel" id="tab-preferences">
      <div class="card">
        <div class="card-head"><h3>Preferences</h3></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="update_prefs">
            <div class="field">
              <label>Currency symbol</label>
              <select name="currency">
                <?php foreach (['Rs' => 'Rs (NPR/INR)', '$' => '$ (USD)', '€' => '€ (EUR)', '£' => '£ (GBP)'] as $val => $label): ?>
                  <option value="<?= e($val) ?>" <?= $user['currency'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary">Save preferences</button>
          </form>
        </div>
      </div>
    </div>

    <div class="settings-panel" id="tab-notifications">
      <div class="card">
        <div class="card-head"><h3>Email reports</h3></div>
        <div class="card-body">
          <p style="color:var(--ink-500);font-size:13.5px;margin-bottom:18px;">Get your income/expense summary sent to <strong><?= e($user['email']) ?></strong> automatically. Toggle either one on — you can turn them off anytime.</p>
          <form method="POST">
            <input type="hidden" name="action" value="update_notifications">

            <label class="notify-row">
              <div>
                <div class="notify-row-title">📅 Daily report</div>
                <div class="notify-row-desc">A short summary of that day's income and expenses, sent every evening.</div>
              </div>
              <span class="switch">
                <input type="checkbox" name="notify_daily" <?= $user['notify_daily'] ? 'checked' : '' ?>>
                <span class="switch-track"><span class="switch-thumb"></span></span>
              </span>
            </label>

            <label class="notify-row">
              <div>
                <div class="notify-row-title">🗓️ Weekly report</div>
                <div class="notify-row-desc">A rollup of the past 7 days — total income, expenses, net balance, and top categories.</div>
              </div>
              <span class="switch">
                <input type="checkbox" name="notify_weekly" <?= $user['notify_weekly'] ? 'checked' : '' ?>>
                <span class="switch-track"><span class="switch-thumb"></span></span>
              </span>
            </label>

            <div class="threshold-row">
              <div>
                <div class="notify-row-title">⚠ Low balance alert</div>
                <div class="notify-row-desc">Email me if my balance for the current month drops below the threshold below (checked at most once every 24 hours).</div>
              </div>
              <span class="switch">
                <input type="checkbox" name="notify_low_balance" <?= $user['notify_low_balance'] ? 'checked' : '' ?>>
                <span class="switch-track"><span class="switch-thumb"></span></span>
              </span>
            </div>
            <div class="field" style="max-width:220px;margin-top:10px;">
              <label>Alert threshold (<?= e($currency) ?>)</label>
              <input type="number" step="0.01" min="0" name="low_balance_threshold" value="<?= e($user['low_balance_threshold']) ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;">Save notification preferences</button>
          </form>
        </div>
      </div>
    </div>

    <div class="settings-panel" id="tab-categories">
      <div class="card">
        <div class="card-head"><h3>Manage categories</h3></div>
        <div class="card-body">
          <p style="color:var(--ink-500);font-size:13.5px;margin-bottom:18px;">Turn default categories on or off, and add your own. Income and expense categories are managed separately since they usually differ.</p>

          <form method="POST">
            <input type="hidden" name="action" value="update_categories">
            <input type="hidden" name="tab" value="tab-categories">

            <div class="cat-manage-group">
              <h4>Income categories</h4>
              <div class="cat-manage-list">
                <?php foreach ($income_defaults as $c): ?>
                  <label class="cat-manage-chip">
                    <input type="checkbox" name="income_categories[]" value="<?= $c['id'] ?>" <?= in_array((int)$c['id'], $active_income_ids, true) ? 'checked' : '' ?>>
                    <span><?= e($c['icon']) ?> <?= e($c['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="cat-manage-group">
              <h4>Expense categories</h4>
              <div class="cat-manage-list">
                <?php foreach ($expense_defaults as $c): ?>
                  <label class="cat-manage-chip">
                    <input type="checkbox" name="expense_categories[]" value="<?= $c['id'] ?>" <?= in_array((int)$c['id'], $active_expense_ids, true) ? 'checked' : '' ?>>
                    <span><?= e($c['icon']) ?> <?= e($c['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Save category selection</button>
          </form>

          <div style="height:1px;background:var(--ink-100);margin:24px 0;"></div>

          <div class="cat-manage-group">
            <h4>Your custom income categories</h4>
            <div class="cat-manage-custom" style="margin-bottom:14px;">
              <?php if (empty($custom_income)): ?><span style="color:var(--ink-500);font-size:13px;">None yet.</span><?php endif; ?>
              <?php foreach ($custom_income as $c): ?>
                <span class="cat-manage-chip"><?= e($c['icon']) ?> <?= e($c['name']) ?>
                  <form method="POST" style="display:inline;" class="confirm-delete" data-label="this category">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="tab" value="tab-categories">
                    <button type="submit">✕</button>
                  </form>
                </span>
              <?php endforeach; ?>
            </div>
            <form method="POST" class="field-inline-add">
              <input type="hidden" name="action" value="add_category">
              <input type="hidden" name="cat_type" value="income">
              <input type="hidden" name="tab" value="tab-categories">
              <input type="text" name="cat_icon" value="💰" style="max-width:56px;text-align:center;">
              <input type="text" name="cat_name" placeholder="New income category name" required>
              <button type="submit" class="btn btn-outline btn-sm">+ Add</button>
            </form>
          </div>

          <div class="cat-manage-group" style="margin-top:22px;">
            <h4>Your custom expense categories</h4>
            <div class="cat-manage-custom" style="margin-bottom:14px;">
              <?php if (empty($custom_expense)): ?><span style="color:var(--ink-500);font-size:13px;">None yet.</span><?php endif; ?>
              <?php foreach ($custom_expense as $c): ?>
                <span class="cat-manage-chip"><?= e($c['icon']) ?> <?= e($c['name']) ?>
                  <form method="POST" style="display:inline;" class="confirm-delete" data-label="this category">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="tab" value="tab-categories">
                    <button type="submit">✕</button>
                  </form>
                </span>
              <?php endforeach; ?>
            </div>
            <form method="POST" class="field-inline-add">
              <input type="hidden" name="action" value="add_category">
              <input type="hidden" name="cat_type" value="expense">
              <input type="hidden" name="tab" value="tab-categories">
              <input type="text" name="cat_icon" value="🧾" style="max-width:56px;text-align:center;">
              <input type="text" name="cat_name" placeholder="New expense category name" required>
              <button type="submit" class="btn btn-outline btn-sm">+ Add</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer_app.php'; ?>
