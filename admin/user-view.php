<?php
require_once __DIR__ . '/config/admin_auth.php';
require_once __DIR__ . '/../config/mailer.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$target) {
    flash('error', 'That user no longer exists.');
    redirect('dashboard.php');
}

// ---- Handle actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'warn') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash('error', 'Please enter a reason for the warning.');
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO user_warnings (user_id, admin_id, reason) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, 'iis', $id, $admin['id'], $reason);
            mysqli_stmt_execute($ins);

            $upd = mysqli_prepare($conn, "UPDATE users SET warning_count = warning_count + 1 WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'i', $id);
            mysqli_stmt_execute($upd);

            send_warning_email($target['email'], $target['full_name'], $reason);
            flash('success', 'Warning sent to ' . $target['full_name'] . '.');
        }
        redirect('user-view.php?id=' . $id);
    }

    if ($action === 'ban') {
        $reason = trim($_POST['ban_reason'] ?? '');
        if ($reason === '') {
            flash('error', 'Please enter a reason for the ban.');
        } else {
            $upd = mysqli_prepare($conn, "UPDATE users SET is_banned = 1, ban_reason = ?, banned_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'si', $reason, $id);
            mysqli_stmt_execute($upd);

            send_ban_email($target['email'], $target['full_name'], $reason);
            flash('success', $target['full_name'] . ' has been banned.');
        }
        redirect('user-view.php?id=' . $id);
    }

    if ($action === 'unban') {
        $upd = mysqli_prepare($conn, "UPDATE users SET is_banned = 0, ban_reason = NULL, banned_at = NULL WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'i', $id);
        mysqli_stmt_execute($upd);

        send_unban_email($target['email'], $target['full_name']);
        flash('success', $target['full_name'] . ' has been reinstated.');
        redirect('user-view.php?id=' . $id);
    }
}

// ---- Refetch (in case an action above just changed it) ----
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ---- Financial summary ----
$totalIncome = get_total($conn, 'income', $id);
$totalExpense = get_total($conn, 'expenses', $id);
$balance = $totalIncome - $totalExpense;

$savingsRes = mysqli_prepare($conn, "SELECT COUNT(*) c, COALESCE(SUM(saved_amount),0) s FROM savings WHERE user_id = ?");
mysqli_stmt_bind_param($savingsRes, 'i', $id);
mysqli_stmt_execute($savingsRes);
$savingsRow = mysqli_fetch_assoc(mysqli_stmt_get_result($savingsRes));

// ---- Recent transactions (income + expenses merged) ----
$txStmt = mysqli_prepare($conn, "
    (SELECT 'income' AS kind, source AS label, amount, entry_date FROM income WHERE user_id = ?)
    UNION ALL
    (SELECT 'expense' AS kind, title AS label, amount, entry_date FROM expenses WHERE user_id = ?)
    ORDER BY entry_date DESC LIMIT 8
");
mysqli_stmt_bind_param($txStmt, 'ii', $id, $id);
mysqli_stmt_execute($txStmt);
$transactions = mysqli_stmt_get_result($txStmt);

// ---- Warning history ----
$wStmt = mysqli_prepare($conn, "
    SELECT w.*, a.full_name AS admin_name
    FROM user_warnings w LEFT JOIN admins a ON a.id = w.admin_id
    WHERE w.user_id = ? ORDER BY w.created_at DESC
");
mysqli_stmt_bind_param($wStmt, 'i', $id);
mysqli_stmt_execute($wStmt);
$warnings = mysqli_stmt_get_result($wStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($target['full_name']) ?> · Admin · Hisaab</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/addon.css">
<link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">

<header class="admin-topbar">
  <a href="dashboard.php" class="brand">
    <span class="brand-mark"><img src="../img/Logo.png" alt="Hisaab Logo"></span> Hisaab <span class="admin-tag">Admin</span>
  </a>
  <div class="admin-topbar-right">
    <span class="admin-whoami">Logged in as <strong><?= e($admin['full_name']) ?></strong></span>
    <a href="logout.php" class="btn btn-ghost btn-sm">↪ Log out</a>
  </div>
</header>

<main class="admin-main">
  <a href="dashboard.php" style="font-size:13.5px;color:var(--ink-500);font-weight:600;display:inline-block;margin-bottom:14px;">← Back to all users</a>

  <?php if ($ok = flash('success')): ?><div class="alert alert-success" style="margin-bottom:18px;">✓ <?= e($ok) ?></div><?php endif; ?>
  <?php if ($err = flash('error')): ?><div class="alert alert-error" style="margin-bottom:18px;">⚠ <?= e($err) ?></div><?php endif; ?>

  <div class="detail-grid">
    <!-- Left column: identity + moderation -->
    <div>
      <div class="card detail-hero">
        <div class="avatar"><?= e(strtoupper(substr($target['full_name'], 0, 1))) ?></div>
        <h2><?= e($target['full_name']) ?></h2>
        <div class="email"><?= e($target['email']) ?></div>
        <div style="margin-top:12px;">
          <?php if ($target['is_banned']): ?>
            <span class="badge badge-danger">Banned</span>
          <?php elseif (!$target['is_verified']): ?>
            <span class="badge badge-muted">Unverified</span>
          <?php else: ?>
            <span class="badge badge-ok">Active</span>
          <?php endif; ?>
          <?php if ($target['warning_count'] > 0): ?>
            <span class="badge badge-warn">⚠ <?= (int)$target['warning_count'] ?> warning<?= $target['warning_count'] == 1 ? '' : 's' ?></span>
          <?php endif; ?>
        </div>
        <div class="meta-row"><span>Member since</span><span><?= date('M j, Y', strtotime($target['created_at'])) ?></span></div>
        <div class="meta-row"><span>Currency</span><span><?= e($target['currency']) ?></span></div>
        <div class="meta-row"><span>Savings goals</span><span><?= (int)$savingsRow['c'] ?></span></div>
      </div>

      <?php if ($target['is_banned']): ?>
        <div class="danger-panel">
          <h4>🚫 This account is banned</h4>
          <p><strong>Reason:</strong> <?= e($target['ban_reason']) ?><br>
          Since <?= date('M j, Y g:ia', strtotime($target['banned_at'])) ?></p>
          <form method="POST">
            <input type="hidden" name="action" value="unban">
            <button type="submit" class="btn btn-primary btn-sm btn-block" onclick="return confirm('Reinstate this account and let them log in again?');">Reinstate account</button>
          </form>
        </div>
      <?php else: ?>
        <div class="warn-panel">
          <h4>⚠ Send a warning</h4>
          <form method="POST">
            <input type="hidden" name="action" value="warn">
            <div class="field" style="margin-bottom:10px;">
              <textarea name="reason" rows="3" placeholder="Reason for this warning (emailed to the user)…" required style="width:100%;padding:10px 12px;border:1.5px solid var(--ink-100);border-radius:var(--radius-sm);font-size:13.5px;resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn btn-outline btn-sm btn-block">Send warning email</button>
          </form>
        </div>

        <div class="danger-panel">
          <h4>🚫 Ban this account</h4>
          <p>They'll be immediately blocked from logging in and notified by email.</p>
          <form method="POST" onsubmit="return confirm('Ban ' + <?= json_encode($target['full_name']) ?> + '? They will be unable to log in.');">
            <input type="hidden" name="action" value="ban">
            <div class="field" style="margin-bottom:10px;">
              <textarea name="ban_reason" rows="3" placeholder="Reason for the ban (emailed to the user)…" required style="width:100%;padding:10px 12px;border:1.5px solid var(--red-50);border-radius:var(--radius-sm);font-size:13.5px;resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn btn-danger btn-sm btn-block">Ban account</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right column: financial data + history -->
    <div>
      <div class="mini-stat-row">
        <div class="mini-stat income">
          <div class="lbl">Total income</div>
          <div class="val"><?= money($totalIncome, $target['currency']) ?></div>
        </div>
        <div class="mini-stat expense">
          <div class="lbl">Total expenses</div>
          <div class="val"><?= money($totalExpense, $target['currency']) ?></div>
        </div>
        <div class="mini-stat">
          <div class="lbl">Net balance</div>
          <div class="val"><?= money($balance, $target['currency']) ?></div>
        </div>
      </div>

      <div class="card" style="margin-bottom:20px;">
        <div class="card-head"><h3>Recent transactions</h3></div>
        <div class="card-body" style="padding:0;">
          <?php if (mysqli_num_rows($transactions) === 0): ?>
            <div class="admin-empty"><div class="emoji">📭</div>No income or expenses recorded yet.</div>
          <?php else: ?>
            <table class="data-table tx-mini-table">
              <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>Date</th></tr></thead>
              <tbody>
                <?php while ($tx = mysqli_fetch_assoc($transactions)): ?>
                  <tr>
                    <td><span class="badge <?= $tx['kind'] === 'income' ? 'badge-ok' : 'badge-danger' ?>"><?= $tx['kind'] === 'income' ? 'Income' : 'Expense' ?></span></td>
                    <td><?= e($tx['label']) ?></td>
                    <td><?= money($tx['amount'], $target['currency']) ?></td>
                    <td style="color:var(--ink-500);"><?= date('M j, Y', strtotime($tx['entry_date'])) ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h3>Warning history</h3></div>
        <div class="card-body">
          <?php if (mysqli_num_rows($warnings) === 0): ?>
            <p style="color:var(--ink-500);font-size:13.5px;">No warnings issued to this user.</p>
          <?php else: ?>
            <?php while ($w = mysqli_fetch_assoc($warnings)): ?>
              <div class="warning-log-item">
                <div class="wl-reason">⚠ <?= e($w['reason']) ?></div>
                <div class="wl-meta">By <?= e($w['admin_name'] ?? 'a former admin') ?> · <?= date('M j, Y g:ia', strtotime($w['created_at'])) ?></div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

</body>
</html>
