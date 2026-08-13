<?php
/**
 * Hissab — Report dispatch logic
 * Shared by the CLI cron script (cron/send_reports.php) so it can be
 * scheduled with XAMPP's/your OS's task scheduler.
 *
 * A user is "due" for a report if their preference is on AND they haven't
 * already received one in the current period (today / this week) — this
 * makes it safe to run the cron job as often as you like (e.g. every hour)
 * without double-sending.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

/** Build the income/expense/top-categories summary for a user over N days. */
function build_report_summary($conn, $user, $days) {
    $currency = $user['currency'] ?: 'Rs';

    if ($days === 1) {
        $dateCond = "entry_date = CURDATE()";
        $periodLabel = date('M j, Y');
    } else {
        $dateCond = "entry_date >= DATE_SUB(CURDATE(), INTERVAL " . ((int)$days - 1) . " DAY)";
        $periodLabel = date('M j', strtotime('-' . ((int)$days - 1) . ' days')) . ' – ' . date('M j, Y');
    }

    $incStmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(amount),0) t FROM income WHERE user_id = ? AND $dateCond");
    mysqli_stmt_bind_param($incStmt, 'i', $user['id']);
    mysqli_stmt_execute($incStmt);
    $income = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($incStmt))['t'];

    $expStmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE user_id = ? AND $dateCond");
    mysqli_stmt_bind_param($expStmt, 'i', $user['id']);
    mysqli_stmt_execute($expStmt);
    $expense = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($expStmt))['t'];

    $catStmt = mysqli_prepare($conn, "
        SELECT COALESCE(c.name,'Uncategorized') name, COALESCE(c.icon,'🧾') icon, SUM(x.amount) total
        FROM expenses x LEFT JOIN categories c ON x.category_id = c.id
        WHERE x.user_id = ? AND $dateCond
        GROUP BY name, icon ORDER BY total DESC LIMIT 5
    ");
    mysqli_stmt_bind_param($catStmt, 'i', $user['id']);
    mysqli_stmt_execute($catStmt);
    $catRes = mysqli_stmt_get_result($catStmt);
    $topCategories = [];
    while ($row = mysqli_fetch_assoc($catRes)) $topCategories[] = $row;

    return [
        'income' => $income,
        'expense' => $expense,
        'currency' => $currency,
        'periodLabel' => $periodLabel,
        'topCategories' => $topCategories,
    ];
}

/** Send daily reports to every eligible, opted-in, not-yet-sent-today user. Returns count sent. */
function dispatch_daily_reports($conn) {
    $sql = "SELECT * FROM users
            WHERE notify_daily = 1 AND is_banned = 0 AND is_verified = 1
              AND (last_daily_report_at IS NULL OR DATE(last_daily_report_at) < CURDATE())";
    $res = mysqli_query($conn, $sql);
    $sent = 0;
    while ($user = mysqli_fetch_assoc($res)) {
        $summary = build_report_summary($conn, $user, 1);
        if (send_report_email($user['email'], $user['full_name'], 'daily', $summary)) {
            $upd = mysqli_prepare($conn, "UPDATE users SET last_daily_report_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'i', $user['id']);
            mysqli_stmt_execute($upd);
            $sent++;
        }
    }
    return $sent;
}

/** Send weekly reports to every eligible, opted-in user whose last report was 7+ days ago. Returns count sent. */
function dispatch_weekly_reports($conn) {
    $sql = "SELECT * FROM users
            WHERE notify_weekly = 1 AND is_banned = 0 AND is_verified = 1
              AND (last_weekly_report_at IS NULL OR last_weekly_report_at <= DATE_SUB(NOW(), INTERVAL 7 DAY))";
    $res = mysqli_query($conn, $sql);
    $sent = 0;
    while ($user = mysqli_fetch_assoc($res)) {
        $summary = build_report_summary($conn, $user, 7);
        if (send_report_email($user['email'], $user['full_name'], 'weekly', $summary)) {
            $upd = mysqli_prepare($conn, "UPDATE users SET last_weekly_report_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'i', $user['id']);
            mysqli_stmt_execute($upd);
            $sent++;
        }
    }
    return $sent;
}
