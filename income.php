<?php
$currency = $user['currency'] ?: 'Rs';

// ---- Handling form submissions ----
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
      <div class="empty-state">
        <div class="emoji"><img src="./Image/Income.png"></div>
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
            <td><span class="pill"><?= e($row['cat_icon'] ?: '💰') ?> <?= e($row['cat_name'] ?: 'Uncategorized') ?></span></td>
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

<?php?>
