<?php
/**
 * Include at the top of every protected page.
 * Starts the session, connects to DB, loads helpers, enforces login.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect('login.php');
}

$user = current_user($conn);
if (!$user) {
    // stale session pointing at a deleted user
    session_destroy();
    redirect('login.php');
}
