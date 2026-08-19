<?php
/**
 * Hissab — Report cron
 *
 * Command line (from the project's cron/ folder):
 *   php send_reports.php
 *
 * XAMPP on Windows — Task Scheduler, action:
 *   Program: C:\xampp\php\php.exe
 *   Arguments: "C:\xampp\htdocs\hissab\cron\send_reports.php"
 *
 * macOS/Linux — crontab entry (runs every day at 8:00 PM):
 *   0 20 * * * /Applications/XAMPP/bin/php /Applications/XAMPP/htdocs/hissab/cron/send_reports.php
 *
 * This file is CLI-only by design — it exits immediately if loaded over HTTP,
 * since report sending should not be triggerable by visiting a URL.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/report_dispatch.php';

$dailySent = dispatch_daily_reports($conn);
$weeklySent = dispatch_weekly_reports($conn);
$lowBalanceSent = dispatch_low_balance_alerts($conn);

echo "[" . date('Y-m-d H:i:s') . "] Daily reports sent: $dailySent, Weekly reports sent: $weeklySent, Low-balance alerts sent: $lowBalanceSent" . PHP_EOL;
