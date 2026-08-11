<?php
require_once __DIR__ . '/includes/auth_check.php';

$currency = $user['currency'] ?: 'Rs';
$this_month = date('Y-m');

$total_income = get_total($conn, 'income', $user['id'], $this_month);
$total_expense = get_total($conn, 'expenses', $user['id'], $this_month);
$balance = $total_income - $total_expense;

$all_income_ever = get_total($conn, 'income', $user['id']);
$all_expense_ever = get_total($conn, 'expenses', $user['id']);

// Last 6 months cash flow for chart
$months = [];
$income_series = [];
$expense_series = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[] = date('M', strtotime($m . '-01'));
    $income_series[] = get_total($conn, 'income', $user['id'], $m);
    $expense_series[] = get_total($conn, 'expenses', $user['id'], $m);
}

// Recent transactions (union of income + expenses)
$stmt = mysqli_prepare($conn, "
    (SELECT 'income' AS kind, source AS title, amount, entry_date, created_at FROM income WHERE user_id = ?)
    UNION ALL
    (SELECT 'expense' AS kind, title, amount, entry_date, created_at FROM expenses WHERE user_id = ?)
    ORDER BY created_at DESC LIMIT 6
");
mysqli_stmt_bind_param($stmt, 'ii', $user['id'], $user['id']);
mysqli_stmt_execute($stmt);
$recent = mysqli_stmt_get_result($stmt);

$page_title = 'Dashboard';
$page_sub = 'Welcome back, ' . explode(' ', $user['full_name'])[0] . ' 👋';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/topbar.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-label">Income this month</div><div class="stat-value"><?= money($total_income, $currency) ?></div></div>
      <div class="stat-icon income"><img src="img/Income.png" alt="Income"></div>
    </div>
    <span class="stat-trend up">↑ All-time: <?= money($all_income_ever, $currency) ?></span>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-label">Expenses this month</div><div class="stat-value"><?= money($total_expense, $currency) ?></div></div>
      <div class="stat-icon expense"><img src="img/Expense.png" alt="Expense"></div>
    </div>
    <span class="stat-trend down">↓ All-time: <?= money($all_expense_ever, $currency) ?></span>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div><div class="stat-label">Net balance</div><div class="stat-value" style="color:<?= $balance >= 0 ? 'var(--green-700)' : 'var(--red-600)' ?>;"><?= money($balance, $currency) ?></div></div>
      <div class="stat-icon balance"><img src="img/Savings.png" alt="Savings"></div>
    </div>
    <span class="stat-trend <?= $balance >= 0 ? 'up' : 'down' ?>"><?= $balance >= 0 ? '✓ Healthy' : '⚠ Overspending' ?></span>
  </div>
</div>

<div class="dash-grid">
  <div class="card">
    <div class="card-head">
      <h3>Cash flow — last 6 months</h3>
    </div>
    <div class="card-body">
      <div class="chart-wrap"><canvas id="cashFlowChart"></canvas></div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>Recent transactions</h3>
      <a href="reports.php" class="btn-sm btn btn-ghost">View all</a>
    </div>
    <div class="card-body">
      <?php if (mysqli_num_rows($recent) === 0): ?>
        <div class="empty-state">
          <div class="emoji">🌱</div>
          <h4>No transactions yet</h4>
          <p>Add your first income or expense to see it here.</p>
          <a href="income.php" class="btn btn-primary btn-sm">Add income</a>
        </div>
      <?php else: ?>
        <div class="tx-list">
        <?php while ($tx = mysqli_fetch_assoc($recent)): ?>
          <div class="tx-row">
            <div class="tx-ic <?= $tx['kind'] ?>"><?= $tx['kind'] === 'income' ? '<img src="img/Income.png" alt="Income">' : '<img src="img/Expense.png" alt="Expense">' ?></div>
            <div class="tx-info">
              <div class="tx-title"><?= e($tx['title']) ?></div>
              <div class="tx-meta"><?= date('M j, Y', strtotime($tx['entry_date'])) ?></div>
            </div>
            <div class="tx-amount <?= $tx['kind'] ?>"><?= $tx['kind'] === 'income' ? '+' : '-' ?><?= money($tx['amount'], $currency) ?></div>
          </div>
        <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('cashFlowChart');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [
      {
        label: 'Income',
        data: <?= json_encode($income_series) ?>,
        borderColor: '#16a34a',
        backgroundColor: 'rgba(22,163,74,0.10)',
        tension: 0.35, fill: true, pointRadius: 3
      },
      {
        label: 'Expenses',
        data: <?= json_encode($expense_series) ?>,
        borderColor: '#dc2626',
        backgroundColor: 'rgba(220,38,38,0.06)',
        tension: 0.35, fill: true, pointRadius: 3
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 12 } } } },
    scales: { y: { beginAtZero: true, grid: { color: '#eef1f4' } }, x: { grid: { display: false } } }
  }
});
</script>

<?php require __DIR__ . '/includes/footer_app.php'; ?>
