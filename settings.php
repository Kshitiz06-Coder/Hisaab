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
    }
    redirect('settings.php');
}

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
  </div>
</div>

<?php require __DIR__ . '/includes/footer_app.php'; ?>
