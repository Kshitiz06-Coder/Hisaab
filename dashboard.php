<?php
require_once __DIR__ . '/includes/auth_check.php';

$currency = $user['currency'] ?: 'Rs';
$this_month = date('Y-m');

function get_total_range($conn, $table, $user_id, $start_date, $end_date) {
  $sql = "SELECT COALESCE(SUM(amount),0) AS total FROM $table WHERE user_id = ? AND entry_date BETWEEN ? AND ?";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, 'iss', $user_id, $start_date, $end_date);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($res);
  return (float)$row['total'];
}

// Last 6 weeks cash flow, Mon–Sun weeks, oldest to newest, current week last
$weeks = [];
$income_series = [];
$expense_series = [];
$savings_series = [];
$this_monday = date('Y-m-d', strtotime('monday this week'));
for ($i = 5; $i >= 0; $i--) {
  $week_start = date('Y-m-d', strtotime("$this_monday -$i weeks"));
  $week_end = date('Y-m-d', strtotime("$week_start +6 days"));
  $weeks[] = date('M j', strtotime($week_start)) . '–' . date('j', strtotime($week_end));
  $week_income = get_total_range($conn, 'income', $user['id'], $week_start, $week_end);
  $week_expense = get_total_range($conn, 'expenses', $user['id'], $week_start, $week_end);
  $income_series[] = $week_income;
  $expense_series[] = $week_expense;
  $savings_series[] = $week_income - $week_expense;
}

// Stat cards now mirror the same 6-week window as the chart
$total_income = array_sum($income_series);
$total_expense = array_sum($expense_series);
$balance = $total_income - $total_expense;

$all_income_ever = get_total($conn, 'income', $user['id']);
$all_expense_ever = get_total($conn, 'expenses', $user['id']);

$savings = get_savings_overview($conn, $user['id']);
maybe_send_low_balance_alert($conn, $user);


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
      <div>
        <div class="stat-label">Income — last 6 weeks</div>
        <div class="stat-value"><?= money($total_income, $currency) ?></div>
      </div>
      <div class="stat-icon income"><img src="img/Income.png" alt="Income"></div>
    </div>
    <span class="stat-trend up">↑ All-time: <?= money($all_income_ever, $currency) ?></span>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div>
        <div class="stat-label">Expenses — last 6 weeks</div>
        <div class="stat-value"><?= money($total_expense, $currency) ?></div>
      </div>
      <div class="stat-icon expense"><img src="img/Expense.png" alt="Expense"></div>
    </div>
    <span class="stat-trend down">↓ All-time: <?= money($all_expense_ever, $currency) ?></span>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div>
        <div class="stat-label">Net balance</div>
        <div class="stat-value" style="color:<?= $balance >= 0 ? 'var(--green-700)' : 'var(--red-600)' ?>;"><?= money($balance, $currency) ?></div>
      </div>
      <div class="stat-icon balance"><img src="img/Savings.png" alt="Savings"></div>
    </div>
    <span class="stat-trend <?= $balance >= 0 ? 'up' : 'down' ?>"><?= $balance >= 0 ? '✓ Healthy' : '⚠ Overspending' ?></span>
  </div>
</div>

<div class="dash-grid">
  <div class="card">
    <div class="card-head">
      <h3>Cash flow — last 6 weeks</h3>
    </div>
    <div class="card-body">
      <?php if (array_sum($income_series) == 0 && array_sum($expense_series) == 0): ?>
        <div class="empty-state">
          <div class="emoji">📊</div>
          <h4>No activity in the last 6 weeks</h4>
          <p>Log some income or expenses and this chart will fill in.</p>
          <a href="income.php" class="btn btn-primary btn-sm">Add income</a>
        </div>
      <?php else: ?>
        <div class="chart-wrap"><canvas id="cashFlowChart"></canvas></div>
      <?php endif; ?>
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

  <div class="card savings-overview">
    <div class="card-head">
      <h3>💰 Savings overview</h3>
      <a href="savings.php" class="btn-sm btn btn-ghost">Savings goals</a>
    </div>
    <div class="card-body">
      <div class="sv-figure">
        <div class="sv-label">Income this month</div>
        <div class="sv-value" style="color:var(--green-700);"><?= money($savings['month_income'], $currency) ?></div>
      </div>
      <div class="sv-figure">
        <div class="sv-label">Expenses this month</div>
        <div class="sv-value" style="color:var(--red-600);"><?= money($savings['month_expense'], $currency) ?></div>
      </div>
      <div class="sv-figure">
        <div class="sv-label">Total savings (all-time)</div>
        <div class="sv-value" style="color:<?= $savings['total_savings'] >= 0 ? 'var(--green-700)' : 'var(--red-600)' ?>;"><?= money($savings['total_savings'], $currency) ?></div>
      </div>
      <div class="sv-analysis">
        <div class="sv-rate-row">
          <span class="sv-rate-num"><?= number_format($savings['savings_rate'], 1) ?>%</span>
          <span class="sv-badge <?= $savings['status'] ?>"><?= ucfirst($savings['status'] === 'great' ? 'excellent' : $savings['status']) ?></span>
        </div>
        <div class="progress-track">
          <div class="progress-fill" style="width:<?= max(0, min(100, $savings['savings_rate'])) ?>%;"></div>
        </div>
        <div class="sv-message" style="margin-top:8px;"><?= e($savings['message']) ?></div>
      </div>
    </div>
  </div>
</div>

<script src="js/chart.umd.min.js"></script>
<script>
  const ctx = document.getElementById('cashFlowChart');
  if (typeof Chart === 'undefined') {
    console.error('Chart.js failed to load — check that js/chart.umd.min.js exists and the path is correct.');
  } else if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($weeks) ?>,
      datasets: [{
          label: 'Income',
          data: <?= json_encode($income_series) ?>,
          backgroundColor: '#16a34a',
          borderRadius: 4,
          maxBarThickness: 28
        },
        {
          label: 'Expenses',
          data: <?= json_encode($expense_series) ?>,
          backgroundColor: '#dc2626',
          borderRadius: 4,
          maxBarThickness: 28
        },
        {
          label: 'Total Savings',
          data: <?= json_encode($savings_series) ?>,
          backgroundColor: '#2563eb',
          borderRadius: 4,
          maxBarThickness: 28
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 10,
            font: {
              size: 12
            }
          }
        },
        tooltip: {
          callbacks: {
            label: function(item) {
              return item.dataset.label + ': <?= $currency ?> ' + item.formattedValue;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: '#eef1f4'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });
  }
</script>

<?php require __DIR__ . '/includes/footer_app.php'; ?>