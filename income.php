<?php
require_once __DIR__ . '/includes/auth_check.php';
$currency = $user['currency'] ?: 'Rs';

// ---- Handle form submissions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $source = trim($_POST['source'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $entry_date = $_POST['entry_date'] ?? date('Y-m-d');
        $note = trim($_POST['note'] ?? '');

        if ($source === '' || $amount <= 0) {
            flash('error', 'Please enter a valid source and amount.');
        } elseif ($action === 'add') {
            $stmt = mysqli_prepare($conn, "INSERT INTO income (user_id, category_id, source, amount, entry_date, note) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iisdss', $user['id'], $category_id, $source, $amount, $entry_date, $note);
            mysqli_stmt_execute($stmt);
            flash('success', 'Income added.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($conn, "UPDATE income SET category_id=?, source=?, amount=?, entry_date=?, note=? WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, 'isdssii', $category_id, $source, $amount, $entry_date, $note, $id, $user['id']);
            mysqli_stmt_execute($stmt);
            flash('success', 'Income updated.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM income WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user['id']);
        mysqli_stmt_execute($stmt);
        flash('success', 'Income entry deleted.');
    }
    redirect('income.php' . (!empty($_POST['month_filter']) ? '?month=' . urlencode($_POST['month_filter']) : ''));
}

// ---- Filters ----
$month_filter = $_GET['month'] ?? '';
$sql = "SELECT i.*, c.name AS cat_name, c.icon AS cat_icon FROM income i LEFT JOIN categories c ON i.category_id = c.id WHERE i.user_id = ?";
$types = 'i'; $params = [$user['id']];
if ($month_filter) {
    $sql .= " AND DATE_FORMAT(i.entry_date, '%Y-%m') = ?";
    $types .= 's'; $params[] = $month_filter;
}
$sql .= " ORDER BY i.entry_date DESC, i.id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);

$total = get_total($conn, 'income', $user['id'], $month_filter ?: null);
$categories = get_categories($conn, $user['id'], 'income');
$cat_list = [];
while ($c = mysqli_fetch_assoc($categories)) $cat_list[] = $c;

$page_title = 'Income';
$page_sub = 'All the money coming in';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/topbar.php';
?>

<div class="toolbar">
  <div class="filters">
    <form method="GET" id="filterForm">
      <input type="month" name="month" value="<?= e($month_filter) ?>" onchange="document.getElementById('filterForm').submit()">
    </form>
    <?php if ($month_filter): ?><a href="income.php" class="btn btn-ghost btn-sm">Clear filter</a><?php endif; ?>
    <span class="pill">Total: <?= money($total, $currency) ?></span>
  </div>
  <button class="btn btn-primary" data-modal-open="addIncomeModal">+ Add Income</button>
</div>

<div class="card">
  <div class="card-body">
    <?php if (mysqli_num_rows($rows) === 0): ?>
      <div class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 50px 20px; width: 100%;">
        <div class="emoji" style="display: block; width: 100%; text-align: center; margin-bottom: 12px;">
          <img src="img/Savings.png" style="width: 48px; height: 48px; display: inline-block; margin: 0 auto; object-fit: contain;">
        </div>
        <h4>No income logged<?= $month_filter ? ' for this month' : '' ?></h4>
        <p>Add a source of income to start tracking your earnings.</p>
        <button class="btn btn-primary btn-sm" data-modal-open="addIncomeModal">+ Add Income</button>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Source</th><th>Category</th><th>Date</th><th>Note</th><th>Amount</th><th></th></tr></thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($rows)): ?>
          <tr>
            <td><strong><?= e($row['source']) ?></strong></td>
            <td><span class="pill"><?= e($row['cat_icon'] ?: '<img src="img/Income.png" alt="Savings">') ?> <?= e($row['cat_name'] ?: 'Uncategorized') ?></span></td>
            <td><?= date('M j, Y', strtotime($row['entry_date'])) ?></td>
            <td style="color:var(--ink-500);"><?= e($row['note'] ?: '—') ?></td>
            <td class="tx-amount income">+<?= money($row['amount'], $currency) ?></td>
            <td>
              <div class="row-actions">
                <a href="#" class="edit-btn" title="Edit"
                   onclick='openEditIncome(<?= json_encode($row) ?>); return false;'>✏️</a>
                <form method="POST" class="confirm-delete" data-label="this income entry">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <button type="submit" title="Delete">🗑️</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<!-- Add Income Modal -->
<div class="modal-backdrop" id="addIncomeModal">
  <div class="modal">
    <div class="modal-head"><h3>Add Income</h3><button class="modal-close" data-modal-close>✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="field"><label>Source</label><input type="text" name="source" placeholder="e.g. Freelance project" required></div>
        <div class="field-row">
          <div class="field"><label>Amount (<?= e($currency) ?>)</label><input type="number" step="0.01" min="0.01" name="amount" placeholder="0.00" required></div>
          <div class="field"><label>Date</label><input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" required></div>
        </div>
        <div class="field">
          <label>Category</label>
          <select name="category_id">
            <option value="">Uncategorized</option>
            <?php foreach ($cat_list as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Note (optional)</label><textarea name="note" rows="2" placeholder="Any extra detail"></textarea></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save Income</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Income Modal -->
<div class="modal-backdrop" id="editIncomeModal">
  <div class="modal">
    <div class="modal-head"><h3>Edit Income</h3><button class="modal-close" data-modal-close>✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="field"><label>Source</label><input type="text" name="source" id="edit_source" required></div>
        <div class="field-row">
          <div class="field"><label>Amount (<?= e($currency) ?>)</label><input type="number" step="0.01" min="0.01" name="amount" id="edit_amount" required></div>
          <div class="field"><label>Date</label><input type="date" name="entry_date" id="edit_date" required></div>
        </div>
        <div class="field">
          <label>Category</label>
          <select name="category_id" id="edit_category">
            <option value="">Uncategorized</option>
            <?php foreach ($cat_list as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Note (optional)</label><textarea name="note" id="edit_note" rows="2"></textarea></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Update Income</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditIncome(row) {
  document.getElementById('edit_id').value = row.id;
  document.getElementById('edit_source').value = row.source;
  document.getElementById('edit_amount').value = row.amount;
  document.getElementById('edit_date').value = row.entry_date;
  document.getElementById('edit_category').value = row.category_id || '';
  document.getElementById('edit_note').value = row.note || '';
  document.getElementById('editIncomeModal').classList.add('open');
}
</script>

<?php require __DIR__ . '/includes/footer_app.php'; ?>
