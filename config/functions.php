<?php

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

function get_categories($conn, $user_id, $type) {
    $sql = "SELECT * FROM categories WHERE type = ? AND (user_id IS NULL OR user_id = ?) ORDER BY user_id IS NULL DESC, name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $type, $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
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

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
