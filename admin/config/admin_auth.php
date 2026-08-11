<?php
/**
 * Include at the top of every protected admin page.
 * Starts the session, connects to DB, loads helpers, enforces admin login.
 * Uses a separate session key ($_SESSION['admin_id']) so admin login is
 * completely independent from a regular user's login session.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

if (empty($_SESSION['admin_id'])) {
    redirect('login.php');
}

$stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['admin_id']);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$admin) {
    // stale session pointing at a deleted admin
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    redirect('login.php');
}
