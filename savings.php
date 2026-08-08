<?php
require_once __DIR__ . '/includes/auth_check.php';
$currency = $user['currency'] ?: 'Rs';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $goal_name = trim($_POST['goal_name'] ?? '');
        $target = (float)($_POST['target_amount'] ?? 0);
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        if ($goal_name === '' || $target <= 0) {
            flash('error', 'Please enter a valid goal name and target amount.');
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO savings (user_id, goal_name, target_amount, deadline) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isds', $user['id'], $goal_name, $target, $deadline);
            mysqli_stmt_execute($stmt);
            flash('success', 'Savings goal created.');
        }
    } elseif ($action === 'add_funds') {
        $id = (int)$_POST['id'];
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE savings SET saved_amount = saved_amount + ? WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, 'dii', $amount, $id, $user['id']);
            mysqli_stmt_execute($stmt);
            flash('success', 'Funds added to your goal.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM savings WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user['id']);
        mysqli_stmt_execute($stmt);
        flash('success', 'Savings goal removed.');
    }
    redirect('savings.php');
}

$stmt = mysqli_prepare($conn, "SELECT * FROM savings WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$goals = mysqli_stmt_get_result($stmt);
$goal_rows = [];
while ($g = mysqli_fetch_assoc($goals)) $goal_rows[] = $g;

$page_title = 'Savings';
$page_sub = 'Give every rupee a goal';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/topbar.php';
?>

<div class="toolbar">
  <div class="filters"><span class="pill">🎯 <?= count($goal_rows) ?> active goal<?= count($goal_rows) === 1 ? '' : 's' ?></span></div>
  <button class="btn btn-primary" data-modal-open="addGoalModal">+ New Savings Goal</button>
</div>

<?php if (empty($goal_rows)): ?>
  <div class="card">
    <div class="card-body">
      <div class="empty-state">
        <div class="emoji">🎯</div>
        <h4>No savings goals yet</h4>
        <p>Create a goal — an emergency fund, a trip, a laptop — and start chipping away at it.</p>
        <button class="btn btn-primary btn-sm" data-modal-open="addGoalModal">+ New Savings Goal</button>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="savings-grid">
  <?php foreach ($goal_rows as $g):
    $pct = $g['target_amount'] > 0 ? min(100, ($g['saved_amount'] / $g['target_amount']) * 100) : 0;
  ?>
    <div class="goal-card">
      <div class="goal-head">
        <div>
          <h4><?= e($g['goal_name']) ?></h4>
          <?php if ($g['deadline']): ?><div class="deadline">🗓 Target: <?= date('M j, Y', strtotime($g['deadline'])) ?></div><?php endif; ?>
        </div>
        <form method="POST" class="confirm-delete" data-label="this savings goal">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $g['id'] ?>">
          <button type="submit" title="Delete goal" style="background:none;border:none;color:var(--ink-500);font-size:15px;">🗑️</button>
        </form>
      </div>
      <div class="progress-track"><div class="progress-fill" id="preview-<?= $g['id'] ?>" style="width:<?= $pct ?>%;"></div></div>
      <div class="goal-figures">
        <span><strong><?= money($g['saved_amount'], $currency) ?></strong> saved</span>
        <span>of <?= money($g['target_amount'], $currency) ?></span>
      </div>
      <form method="POST" style="display:flex;gap:8px;margin-top:14px;">
        <input type="hidden" name="action" value="add_funds">
        <input type="hidden" name="id" value="<?= $g['id'] ?>">
        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Add funds"
               class="savings-add-input" data-goal-id="<?= $g['id'] ?>"
               data-target="<?= $g['target_amount'] ?>" data-current="<?= $g['saved_amount'] ?>"
               style="flex:1;padding:9px 12px;border-radius:8px;border:1.5px solid var(--ink-300);font-size:13.5px;">
        <button type="submit" class="btn btn-sm btn-outline">Add</button>
      </form>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Add Goal Modal -->
<div class="modal-backdrop" id="addGoalModal">
  <div class="modal">
    <div class="modal-head"><h3>New Savings Goal</h3><button class="modal-close" data-modal-close>✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="field"><label>Goal name</label><input type="text" name="goal_name" placeholder="e.g. Emergency fund" required></div>
        <div class="field"><label>Target amount (<?= e($currency) ?>)</label><input type="number" step="0.01" min="0.01" name="target_amount" placeholder="0.00" required></div>
        <div class="field"><label>Target date (optional)</label><input type="date" name="deadline"></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Create Goal</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer_app.php'; ?>
