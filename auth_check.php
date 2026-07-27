<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

if (empty($_SESSION['user_id'])) {
    redirect('login.php');
}

$user = current_user($conn);
if (!$user) {
    session_destroy();
    redirect('login.php');
}
