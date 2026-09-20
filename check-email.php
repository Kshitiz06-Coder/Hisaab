<?php
/**
 * AJAX endpoint used by js/form-validate.js to check, in real time,
 * whether an email is already registered while the user is typing
 * on the Register page.
 *
 * GET /check-email.php?email=someone@example.com
 * Response: {"exists": true|false}
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT is_verified FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Mirrors register.php's own duplicate rule: only a VERIFIED account counts
// as "already taken" — an abandoned, never-verified signup can be reused,
// so we don't want to scare that person off with a false "taken" message.
$exists = $row && (int)$row['is_verified'] === 1;

echo json_encode(['exists' => $exists]);
