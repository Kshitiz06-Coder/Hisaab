<div class="toolbar">
  <div class="filters">
    <form method="GET" id="filterForm">
      <input type="month" name="month" value="<?= e($month_filter) ?>" onchange="document.getElementById('filterForm').submit()">
    </form>
    <?php if ($month_filter): ?><a href="expenses.php" class="btn btn-ghost btn-sm">Clear filter</a><?php endif; ?>
    <span class="pill">Total: <?= money($total, $currency) ?></span>
  </div>
  <button class="btn btn-primary" data-modal-open="addExpenseModal">+ Add Expense</button>
</div>

<div class="card">
  <div class="card-body">
    <?php if (mysqli_num_rows($rows) === 0): ?>
      <div class="empty-state">
        <div class="emoji">🧾</div>
        <h4>No expenses logged<?= $month_filter ? ' for this month' : '' ?></h4>
        <p>Add an expense to see where your money is going.</p>
        <button class="btn btn-primary btn-sm" data-modal-open="addExpenseModal">+ Add Expense</button>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Title</th><th>Category</th><th>Date</th><th>Note</th><th>Amount</th><th></th></tr></thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($rows)): ?>
          <tr>
            <td><strong><?= e($row['title']) ?></strong></td>
            <td><span class="pill"><?= e($row['cat_icon'] ?: '🧾') ?> <?= e($row['cat_name'] ?: 'Uncategorized') ?></span></td>
            <td><?= date('M j, Y', strtotime($row['entry_date'])) ?></td>
            <td style="color:var(--ink-500);"><?= e($row['note'] ?: '—') ?></td>
            <td class="tx-amount expense">-<?= money($row['amount'], $currency) ?></td>
            <td>
              <div class="row-actions">
                <a href="#" class="edit-btn" title="Edit"
                   onclick='openEditExpense(<?= json_encode($row) ?>); return false;'>✏️</a>
                <form method="POST" class="confirm-delete" data-label="this expense">
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

<div class="modal-backdrop" id="addExpenseModal">
  <div class="modal">
    <div class="modal-head"><h3>Add Expense</h3><button class="modal-close" data-modal-close>✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="field"><label>Title</label><input type="text" name="title" placeholder="e.g. Grocery shopping" required></div>
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
        <button type="submit" class="btn btn-primary">Save Expense</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="editExpenseModal">
  <div class="modal">
    <div class="modal-head"><h3>Edit Expense</h3><button class="modal-close" data-modal-close>✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="field"><label>Title</label><input type="text" name="title" id="edit_title" required></div>
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
        <button type="submit" class="btn btn-primary">Update Expense</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditExpense(row) {
  document.getElementById('edit_id').value = row.id;
  document.getElementById('edit_title').value = row.title;
  document.getElementById('edit_amount').value = row.amount;
  document.getElementById('edit_date').value = row.entry_date;
  document.getElementById('edit_category').value = row.category_id || '';
  document.getElementById('edit_note').value = row.note || '';
  document.getElementById('editExpenseModal').classList.add('open');
}
</script>