<?php
/**
 * Hissab - Shared helper functions
 */

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function money($amount, $currency = 'Rs') {
    return $currency . ' ' . number_format((float)$amount, 2);
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function get_total($conn, $table, $user_id, $month = null) {
    $sql = "SELECT COALESCE(SUM(amount),0) AS total FROM $table WHERE user_id = ?";
    $params = [$user_id];
    $types = 'i';
    if ($month) {
        $sql .= " AND DATE_FORMAT(entry_date, '%Y-%m') = ?";
        $params[] = $month;
        $types .= 's';
    }
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    return (float)$row['total'];
}

/**
 * Categories a user actually sees when adding income/expenses:
 * their own custom categories, plus whichever shared default
 * categories they turned on during onboarding (or in Settings).
 */
function get_categories($conn, $user_id, $type) {
    $sql = "SELECT c.* FROM categories c
            LEFT JOIN user_categories uc ON uc.category_id = c.id AND uc.user_id = ?
            WHERE c.type = ? AND (c.user_id = ? OR (c.user_id IS NULL AND uc.is_active = 1))
            ORDER BY c.user_id IS NULL DESC, c.name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'isi', $user_id, $type, $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/** All shared/default categories of a type, regardless of any user's selection (for pickers). */
function get_default_categories($conn, $type) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE type = ? AND user_id IS NULL ORDER BY name ASC");
    mysqli_stmt_bind_param($stmt, 's', $type);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/** IDs of default categories a user currently has turned on, for a given type. */
function get_active_default_category_ids($conn, $user_id, $type) {
    $sql = "SELECT c.id FROM categories c
            JOIN user_categories uc ON uc.category_id = c.id
            WHERE c.type = ? AND c.user_id IS NULL AND uc.user_id = ? AND uc.is_active = 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $type, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($res)) $ids[] = (int)$row['id'];
    return $ids;
}

/**
 * Save which default categories (of one type: 'income' or 'expense') a user wants active.
 * $selected_ids = array of category_id ints to turn ON; every other default category of
 * that type is turned OFF for that user. Used by onboarding and by Settings > Categories.
 */
function save_user_category_selection($conn, $user_id, $type, $selected_ids) {
    $selected_ids = array_map('intval', $selected_ids);

    $defaults = get_default_categories($conn, $type);
    while ($cat = mysqli_fetch_assoc($defaults)) {
        $is_active = in_array((int)$cat['id'], $selected_ids, true) ? 1 : 0;
        $stmt = mysqli_prepare($conn, "INSERT INTO user_categories (user_id, category_id, is_active) VALUES (?,?,?)
                                        ON DUPLICATE KEY UPDATE is_active = VALUES(is_active)");
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $cat['id'], $is_active);
        mysqli_stmt_execute($stmt);
    }
}

/** Creates a personal category for one user (always visible to them, no picker row needed). */
function add_custom_category($conn, $user_id, $type, $name, $icon = '💰') {
    $name = trim($name);
    if ($name === '') return null;
    $icon = trim($icon) ?: '💰';
    $stmt = mysqli_prepare($conn, "INSERT INTO categories (user_id, name, type, icon) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'isss', $user_id, $name, $type, $icon);
    mysqli_stmt_execute($stmt);
    return mysqli_insert_id($conn);
}

/**
 * This month's income, expenses, and a lightweight savings analysis —
 * powers the dashboard's Savings Overview card.
 */
function get_savings_overview($conn, $user_id) {
    $this_month = date('Y-m');
    $month_income = get_total($conn, 'income', $user_id, $this_month);
    $month_expense = get_total($conn, 'expenses', $user_id, $this_month);
    $month_savings = $month_income - $month_expense;

    $all_income = get_total($conn, 'income', $user_id);
    $all_expense = get_total($conn, 'expenses', $user_id);
    $total_savings = $all_income - $all_expense;

    $savings_rate = $month_income > 0 ? ($month_savings / $month_income) * 100 : 0;

    if ($month_income <= 0) {
        $status = 'neutral';
        $message = 'Log some income this month to see your savings rate.';
    } elseif ($savings_rate < 0) {
        $status = 'bad';
        $message = "You're spending more than you're earning this month.";
    } elseif ($savings_rate < 10) {
        $status = 'warn';
        $message = 'Saving a little — try trimming a category to save more.';
    } elseif ($savings_rate < 25) {
        $status = 'good';
        $message = "You're saving at a healthy pace this month.";
    } else {
        $status = 'great';
        $message = "Excellent! You're saving a large share of your income.";
    }

    return [
        'month_income' => $month_income,
        'month_expense' => $month_expense,
        'month_savings' => $month_savings,
        'total_savings' => $total_savings,
        'savings_rate' => $savings_rate,
        'status' => $status,
        'message' => $message,
    ];
}

/**
 * If low-balance alerts are on and this month's balance has dropped below
 * the user's threshold, emails them — at most once every 24 hours so it
 * doesn't spam on every page load or every new expense.
 */
function maybe_send_low_balance_alert($conn, $user) {
    if (empty($user['notify_low_balance'])) return false;

    $this_month = date('Y-m');
    $income = get_total($conn, 'income', $user['id'], $this_month);
    $expense = get_total($conn, 'expenses', $user['id'], $this_month);
    $balance = $income - $expense;
    $threshold = (float)$user['low_balance_threshold'];

    if ($balance >= $threshold) return false;

    if (!empty($user['last_low_balance_alert_at'])) {
        $hoursSince = (time() - strtotime($user['last_low_balance_alert_at'])) / 3600;
        if ($hoursSince < 24) return false;
    }

    require_once __DIR__ . '/mailer.php';
    $sent = send_low_balance_email($user['email'], $user['full_name'], $balance, $threshold, $user['currency'] ?: 'Rs');

    $stmt = mysqli_prepare($conn, "UPDATE users SET last_low_balance_alert_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user['id']);
    mysqli_stmt_execute($stmt);

    return $sent;
}

function current_user($conn) {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res);
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function generate_otp() {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
