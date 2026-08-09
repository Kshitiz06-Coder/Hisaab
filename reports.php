<?php
require_once __DIR__ . '/includes/auth_check.php';
$currency = $user['currency'] ?: 'Rs';

$month_filter = $_GET['month'] ?? date('Y-m');

// Expense breakdown by category
$sql = "SELECT COALESCE(c.name,'Uncategorized') AS name, COALESCE(c.icon,'🧾') AS icon, SUM(x.amount) AS total
        FROM expenses x LEFT JOIN categories c ON x.category_id = c.id
        WHERE x.user_id = ? AND DATE_FORMAT(x.entry_date,'%Y-%m') = ?
        GROUP BY name, icon ORDER BY total DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'is', $user['id'], $month_filter);
mysqli_stmt_execute($stmt);
$expense_by_cat = mysqli_stmt_get_result($stmt);
$exp_rows = [];
$max_exp = 0.01;
while ($r = mysqli_fetch_assoc($expense_by_cat)) { $exp_rows[] = $r; $max_exp = max($max_exp, $r['total']); }

// Income breakdown by category
$sql2 = "SELECT COALESCE(c.name,'Uncategorized') AS name, COALESCE(c.icon,'💰') AS icon, SUM(i.amount) AS total
        FROM income i LEFT JOIN categories c ON i.category_id = c.id
        WHERE i.user_id = ? AND DATE_FORMAT(i.entry_date,'%Y-%m') = ?
        GROUP BY name, icon ORDER BY total DESC";
$stmt2 = mysqli_prepare($conn, $sql2);
mysqli_stmt_bind_param($stmt2, 'is', $user['id'], $month_filter);
mysqli_stmt_execute($stmt2);
$income_by_cat = mysqli_stmt_get_result($stmt2);
$inc_rows = [];
$max_inc = 0.01;
while ($r = mysqli_fetch_assoc($income_by_cat)) { $inc_rows[] = $r; $max_inc = max($max_inc, $r['total']); }

$total_income = get_total($conn, 'income', $user['id'], $month_filter);
$total_expense = get_total($conn, 'expenses', $user['id'], $month_filter);
$savings_rate = $total_income > 0 ? round((($total_income - $total_expense) / $total_income) * 100, 1) : 0;

$page_title = 'Reports';
$page_sub = 'Understand your spending patterns';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/topbar.php';
?>

<div class="toolbar">
  <div class="filters">
    <form method="GET" id="filterForm">
      <input type="month" name="month" value="<?= e($month_filter) ?>" onchange="document.getElementById('filterForm').submit()">
    </form>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <span class="pill">💡 Savings rate: <?= $savings_rate ?>%</span>
    <button class="btn btn-primary" id="downloadReportBtn">⬇ Download Report (PDF)</button>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
  <div class="stat-card">
    <div class="stat-label">Total Income</div>
    <div class="stat-value" style="color:var(--green-700);"><?= money($total_income, $currency) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Expenses</div>
    <div class="stat-value" style="color:var(--red-600);"><?= money($total_expense, $currency) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Net Balance</div>
    <div class="stat-value"><?= money($total_income - $total_expense, $currency) ?></div>
  </div>
</div>

<div class="report-grid">
  <div class="card">
    <div class="card-head"><h3>Expenses by category</h3></div>
    <div class="card-body">
      <?php if (empty($exp_rows)): ?>
       <div class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 50px 20px; width: 100%;">
        <div class="emoji" style="display: block; width: 100%; text-align: center; margin-bottom: 12px;">
          <img src="img/Expense.png" alt="Expense" style="width: 48px; height: 48px; display: inline-block; margin: 0 auto; object-fit: contain;">
        </div><p>No expenses recorded for this month.</p></div>
      <?php else: foreach ($exp_rows as $r): ?>
        <div class="category-bar-row">
          <div class="cat-label"><?= e($r['icon']) ?> <?= e($r['name']) ?></div>
          <div class="category-bar-track"><div class="category-bar-fill" style="width:<?= ($r['total']/$max_exp)*100 ?>%; background:var(--red-600);"></div></div>
          <div class="category-bar-amt"><?= money($r['total'], $currency) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Income by category</h3></div>
    <div class="card-body">
      <?php if (empty($inc_rows)): ?>
         <div class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 50px 20px; width: 100%;">
        <div class="emoji" style="display: block; width: 100%; text-align: center; margin-bottom: 12px;">
          <img src="img/Income.png" alt="Income" style="width: 48px; height: 48px; display: inline-block; margin: 0 auto; object-fit: contain;">
          <p>No income recorded for this month.</p></div>
        </div>
      <?php else: foreach ($inc_rows as $r): ?>
        <div class="category-bar-row">
          <div class="cat-label"><?= e($r['icon']) ?> <?= e($r['name']) ?></div>
          <div class="category-bar-track"><div class="category-bar-fill" style="width:<?= ($r['total']/$max_inc)*100 ?>%;"></div></div>
          <div class="category-bar-amt"><?= money($r['total'], $currency) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="js/report-pdf.js"></script>
<script>
document.getElementById('downloadReportBtn').addEventListener('click', function () {
  var incomeRows = <?= json_encode(array_map(function ($r) {
      return ['name' => $r['icon'] . ' ' . $r['name'], 'total' => number_format($r['total'], 2)];
  }, $inc_rows)) ?>;
  var expenseRows = <?= json_encode(array_map(function ($r) {
      return ['name' => $r['icon'] . ' ' . $r['name'], 'total' => number_format($r['total'], 2)];
  }, $exp_rows)) ?>;

  var reportData = {
    userName: <?= json_encode($user['full_name']) ?>,
    userEmail: <?= json_encode($user['email']) ?>,
    currency: <?= json_encode($currency) ?>,
    monthValue: <?= json_encode($month_filter) ?>,
    monthLabel: <?= json_encode(date('F Y', strtotime($month_filter . '-01'))) ?>,
    generatedOn: <?= json_encode(date('M j, Y g:i A')) ?>,
    totalIncome: <?= json_encode(number_format($total_income, 2)) ?>,
    totalExpense: <?= json_encode(number_format($total_expense, 2)) ?>,
    netBalance: <?= json_encode(number_format($total_income - $total_expense, 2)) ?>,
    savingsRate: <?= json_encode($savings_rate) ?>,
    incomeRows: incomeRows,
    expenseRows: expenseRows
  };

  downloadHissabReportPDF(reportData);
});
</script>

<?php require __DIR__ . '/includes/footer_app.php'; ?>
