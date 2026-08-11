<?php
require_once __DIR__ . '/config/admin_auth.php';

// ---- Stats ----
$totalUsers = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];
$verifiedUsers = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE is_verified = 1"))['c'];
$bannedUsers = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE is_banned = 1"))['c'];
$newThisWeek = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['c'];

// ---- Search + filter ----
$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // all | verified | unverified | banned | warned

$where = ['1=1'];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ?)';
    $like = "%$q%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($filter === 'verified') { $where[] = 'is_verified = 1'; }
if ($filter === 'unverified') { $where[] = 'is_verified = 0'; }
if ($filter === 'banned') { $where[] = 'is_banned = 1'; }
if ($filter === 'warned') { $where[] = 'warning_count > 0'; }

$sql = "SELECT id, full_name, email, is_verified, is_banned, warning_count, created_at
        FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);

function filter_url($filter, $q) {
    return '?' . http_build_query(array_filter(['filter' => $filter, 'q' => $q], fn($v) => $v !== ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard · Hisaab</title>
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
  <div class="admin-page-head">
    <div>
      <h1>User management</h1>
      <p>View every account, warn or ban problem users, and keep an eye on new signups.</p>
    </div>
  </div>

  <?php if ($ok = flash('success')): ?><div class="alert alert-success" style="margin-bottom:18px;">✓ <?= e($ok) ?></div><?php endif; ?>
  <?php if ($err = flash('error')): ?><div class="alert alert-error" style="margin-bottom:18px;">⚠ <?= e($err) ?></div><?php endif; ?>

  <div class="admin-stat-row">
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon" style="background:var(--green-50);">👥</span></div>
      <div class="stat-label">Total users</div>
      <div class="stat-value"><?= $totalUsers ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon" style="background:var(--green-50);">✅</span></div>
      <div class="stat-label">Verified</div>
      <div class="stat-value"><?= $verifiedUsers ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon" style="background:var(--red-50);">🚫</span></div>
      <div class="stat-label">Banned</div>
      <div class="stat-value"><?= $bannedUsers ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-top"><span class="stat-icon" style="background:var(--amber-50);">🆕</span></div>
      <div class="stat-label">New this week</div>
      <div class="stat-value"><?= $newThisWeek ?></div>
    </div>
  </div>

  <form method="GET" class="admin-toolbar">
    <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by name or email…">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
  </form>

  <div class="filter-pills" style="margin-bottom:18px;">
    <a href="<?= filter_url('all', $q) ?>" class="filter-pill <?= $filter === 'all' ? 'active' : '' ?>">All</a>
    <a href="<?= filter_url('verified', $q) ?>" class="filter-pill <?= $filter === 'verified' ? 'active' : '' ?>">Verified</a>
    <a href="<?= filter_url('unverified', $q) ?>" class="filter-pill <?= $filter === 'unverified' ? 'active' : '' ?>">Unverified</a>
    <a href="<?= filter_url('warned', $q) ?>" class="filter-pill <?= $filter === 'warned' ? 'active' : '' ?>">Warned</a>
    <a href="<?= filter_url('banned', $q) ?>" class="filter-pill <?= $filter === 'banned' ? 'active' : '' ?>">Banned</a>
  </div>

  <div class="card">
    <div class="card-body" style="padding:0;">
      <?php if (mysqli_num_rows($users) === 0): ?>
        <div class="admin-empty">
          <div class="emoji">🔍</div>
          No users match this search/filter.
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Status</th>
              <th>Warnings</th>
              <th>Joined</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <div class="avatar" style="background:var(--green-600);color:#fff;"><?= e(strtoupper(substr($u['full_name'], 0, 1))) ?></div>
                    <div>
                      <div class="u-name"><?= e($u['full_name']) ?></div>
                      <div class="u-email"><?= e($u['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($u['is_banned']): ?>
                    <span class="badge badge-danger">Banned</span>
                  <?php elseif (!$u['is_verified']): ?>
                    <span class="badge badge-muted">Unverified</span>
                  <?php else: ?>
                    <span class="badge badge-ok">Active</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($u['warning_count'] > 0): ?>
                    <span class="badge badge-warn">⚠ <?= (int)$u['warning_count'] ?></span>
                  <?php else: ?>
                    <span style="color:var(--ink-500);font-size:13px;">—</span>
                  <?php endif; ?>
                </td>
                <td style="color:var(--ink-500);font-size:13px;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td><a href="user-view.php?id=<?= (int)$u['id'] ?>" class="btn btn-outline btn-sm">View →</a></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</main>

</body>
</html>
