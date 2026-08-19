<?php
require_once __DIR__ . '/includes/auth_check.php';

// If they've already finished onboarding, there's nothing to do here.
if (!empty($user['onboarded'])) {
    redirect('dashboard.php');
}

$income_defaults = [];
$res = get_default_categories($conn, 'income');
while ($c = mysqli_fetch_assoc($res)) $income_defaults[] = $c;

$expense_defaults = [];
$res = get_default_categories($conn, 'expense');
while ($c = mysqli_fetch_assoc($res)) $expense_defaults[] = $c;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'skip') {
        // Skip = keep every default category active for both types.
        save_user_category_selection($conn, $user['id'], 'income', array_column($income_defaults, 'id'));
        save_user_category_selection($conn, $user['id'], 'expense', array_column($expense_defaults, 'id'));
    } else {
        $income_selected = array_map('intval', $_POST['income_categories'] ?? []);
        $expense_selected = array_map('intval', $_POST['expense_categories'] ?? []);

        if (empty($income_selected) && empty($_POST['custom_income'])) {
            $errors[] = 'Pick at least one income category (or add your own).';
        }
        if (empty($expense_selected) && empty($_POST['custom_expense'])) {
            $errors[] = 'Pick at least one expense category (or add your own).';
        }

        if (!$errors) {
            save_user_category_selection($conn, $user['id'], 'income', $income_selected);
            save_user_category_selection($conn, $user['id'], 'expense', $expense_selected);

            foreach (($_POST['custom_income'] ?? []) as $name) {
                if (trim($name) !== '') add_custom_category($conn, $user['id'], 'income', $name, '💰');
            }
            foreach (($_POST['custom_expense'] ?? []) as $name) {
                if (trim($name) !== '') add_custom_category($conn, $user['id'], 'expense', $name, '🧾');
            }
        }
    }

    if (!$errors) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET onboarded = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user['id']);
        mysqli_stmt_execute($stmt);
        flash('success', 'Your categories are set up. Welcome to Hisaab!');
        redirect('dashboard.php');
    }
}

$page_title = 'Set up your categories';
$page_sub = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set up your categories · Hisaab</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/addon.css">
</head>
<body>
<div class="onboard-wrap">
  <div class="onboard-card">
    <div class="onboard-head">
      <span class="brand-mark"><img src="img/Logo.png" alt="Hisaab Logo"></span>
      <h2>Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?> 👋</h2>
      <p>Pick the categories you actually use. Income and expenses are different for everyone, so choose each separately — you can always change this later in Settings.</p>
    </div>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error">⚠ <?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" id="onboardForm">
      <input type="hidden" name="action" value="save">

      <div class="onboard-section">
        <div class="onboard-section-head">
          <h3>1. Your income categories</h3>
          <button type="button" class="btn-sm btn btn-ghost" data-select="income">Select all</button>
        </div>
        <div class="cat-picker-grid">
          <?php foreach ($income_defaults as $c): ?>
            <label class="cat-picker-item">
              <input type="checkbox" name="income_categories[]" value="<?= $c['id'] ?>" checked>
              <span><?= e($c['icon']) ?> <?= e($c['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="cat-custom-row" data-custom-for="income">
          <div class="cat-custom-list"></div>
          <div class="cat-custom-add">
            <input type="text" placeholder="Add your own income category (e.g. Rental income)" class="cat-custom-input">
            <button type="button" class="btn btn-outline btn-sm cat-custom-btn">+ Add</button>
          </div>
        </div>
      </div>

      <div class="onboard-section">
        <div class="onboard-section-head">
          <h3>2. Your expense categories</h3>
          <button type="button" class="btn-sm btn btn-ghost" data-select="expense">Select all</button>
        </div>
        <div class="cat-picker-grid">
          <?php foreach ($expense_defaults as $c): ?>
            <label class="cat-picker-item">
              <input type="checkbox" name="expense_categories[]" value="<?= $c['id'] ?>" checked>
              <span><?= e($c['icon']) ?> <?= e($c['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="cat-custom-row" data-custom-for="expense">
          <div class="cat-custom-list"></div>
          <div class="cat-custom-add">
            <input type="text" placeholder="Add your own expense category (e.g. Pet care)" class="cat-custom-input">
            <button type="button" class="btn btn-outline btn-sm cat-custom-btn">+ Add</button>
          </div>
        </div>
      </div>

      <div class="onboard-foot">
        <button type="submit" formnovalidate class="btn btn-ghost" name="action" value="skip">Skip, use all defaults</button>
        <button type="submit" class="btn btn-primary">Continue to Dashboard</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-select]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var type = btn.getAttribute('data-select');
      var boxes = document.querySelectorAll('input[name="' + type + '_categories[]"]');
      var allChecked = Array.from(boxes).every(function (b) { return b.checked; });
      boxes.forEach(function (b) { b.checked = !allChecked; });
      btn.textContent = allChecked ? 'Select all' : 'Clear all';
    });
  });

  document.querySelectorAll('.cat-custom-row').forEach(function (row) {
    var type = row.getAttribute('data-custom-for');
    var list = row.querySelector('.cat-custom-list');
    var input = row.querySelector('.cat-custom-input');
    var addBtn = row.querySelector('.cat-custom-btn');

    function addCustom() {
      var val = input.value.trim();
      if (!val) return;
      var item = document.createElement('div');
      item.className = 'cat-custom-chip';
      item.innerHTML = '<span></span><button type="button" aria-label="Remove">✕</button>';
      item.querySelector('span').textContent = val;
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'custom_' + type + '[]';
      hidden.value = val;
      item.appendChild(hidden);
      item.querySelector('button').addEventListener('click', function () { item.remove(); });
      list.appendChild(item);
      input.value = '';
      input.focus();
    }

    addBtn.addEventListener('click', addCustom);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); addCustom(); }
    });
  });
});
</script>
</body>
</html>
