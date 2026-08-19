<?php
/**
 * Hissab — Mailer
 * Sends OTP emails through Gmail's SMTP server using PHPMailer.
 *
 * PHPMailer is NOT bundled here — install it one of two ways:
 *
 */

require_once __DIR__ . '/mail_config.php';

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
$manualBase = __DIR__ . '/../libs/PHPMailer/src/';

if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} elseif (file_exists($manualBase . 'PHPMailer.php')) {
    require_once $manualBase . 'Exception.php';
    require_once $manualBase . 'PHPMailer.php';
    require_once $manualBase . 'SMTP.php';
}

/**
 * Send a 6-digit OTP code to $toEmail.
 * Returns true on success, false on failure (check error_log for details).
 */
function send_otp_email($toEmail, $toName, $otp) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('Hissab mailer: PHPMailer is not installed. See config/mailer.php for setup instructions.');
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = GMAIL_ADDRESS;
        $mail->Password = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom(GMAIL_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(GMAIL_ADDRESS, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = 'Your Hisaab verification code';
        $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;padding:28px;border:1px solid #eee;border-radius:14px;">
                <h2 style="color:#16a34a;margin:0 0 8px;">Hisaab</h2>
                <p style="color:#333;">Hi ' . htmlspecialchars($toName) . ',</p>
                <p style="color:#333;">Use the code below to verify it\'s you and reset your password. It expires in <strong>10 minutes</strong>.</p>
                <div style="font-size:32px;font-weight:700;letter-spacing:10px;background:#f0fdf4;color:#157347;padding:18px;text-align:center;border-radius:10px;margin:22px 0;">' . htmlspecialchars($otp) . '</div>
                <p style="color:#888;font-size:13px;">If you didn\'t request this, you can safely ignore this email — your password will not be changed.</p>
            </div>';
        $mail->AltBody = "Your Hisaab verification code is: $otp. It expires in 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Hisaab mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Generate, store, and email a fresh registration-verification OTP for a user.
 * Invalidates any earlier unused registration OTPs for that user first.
 * Returns true if the email was sent successfully.
 */
function issue_register_otp($conn, $user_id, $email, $name) {
    $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ? AND purpose = 'register'");
    mysqli_stmt_bind_param($del, 'i', $user_id);
    mysqli_stmt_execute($del);

    $otp = generate_otp();
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $ins = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'register', ?)");
    mysqli_stmt_bind_param($ins, 'iss', $user_id, $otp, $expires);
    mysqli_stmt_execute($ins);

    return send_otp_email($email, $name, $otp);
}

/**
 * Shared wrapper for the admin-panel notification emails below.
 * Keeps a consistent look without repeating the HTML shell each time.
 */
function send_admin_notice_email($toEmail, $toName, $subject, $accentColor, $heading, $bodyHtml, $altText) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('Hissab mailer: PHPMailer is not installed. See config/mailer.php for setup instructions.');
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = GMAIL_ADDRESS;
        $mail->Password = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom(GMAIL_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(GMAIL_ADDRESS, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:460px;margin:auto;padding:28px;border:1px solid #eee;border-radius:14px;">
                <h2 style="color:' . $accentColor . ';margin:0 0 8px;">Hisaab</h2>
                <p style="color:#333;">Hi ' . htmlspecialchars($toName) . ',</p>
                <h3 style="color:#222;margin:14px 0 6px;">' . htmlspecialchars($heading) . '</h3>
                ' . $bodyHtml . '
                <p style="color:#888;font-size:12.5px;margin-top:22px;">This is an automated notice from the Hisaab administration team.</p>
            </div>';
        $mail->AltBody = $altText;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Hisaab mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/** Sent when an admin issues a warning to a user's account. */
function send_warning_email($toEmail, $toName, $reason) {
    $body = '<p style="color:#333;">Your Hisaab account has received a warning:</p>
        <div style="background:#fffbeb;color:#92400e;padding:14px 16px;border-radius:10px;margin:14px 0;font-size:14.5px;">' . nl2br(htmlspecialchars($reason)) . '</div>
        <p style="color:#333;">Please review our usage guidelines. Repeated or serious violations may result in your account being suspended.</p>';
    return send_admin_notice_email(
        $toEmail, $toName,
        'Warning issued to your Hisaab account',
        '#d97706',
        '⚠ Account warning',
        $body,
        "Your Hisaab account has received a warning: $reason. Repeated or serious violations may result in suspension."
    );
}

/** Sent when an admin bans a user's account. */
function send_ban_email($toEmail, $toName, $reason) {
    $body = '<p style="color:#333;">Your Hisaab account has been <strong>suspended</strong> by an administrator.</p>
        <div style="background:#fef2f2;color:#991b1b;padding:14px 16px;border-radius:10px;margin:14px 0;font-size:14.5px;"><strong>Reason:</strong> ' . nl2br(htmlspecialchars($reason)) . '</div>
        <p style="color:#333;">You will not be able to log in while your account is suspended. If you believe this was a mistake, please contact the administrator.</p>';
    return send_admin_notice_email(
        $toEmail, $toName,
        'Your Hisaab account has been suspended',
        '#dc2626',
        '🚫 Account suspended',
        $body,
        "Your Hisaab account has been suspended. Reason: $reason. You will not be able to log in until it is reinstated."
    );
}

/** Sent when an admin lifts a ban and restores account access. */
function send_unban_email($toEmail, $toName) {
    $body = '<p style="color:#333;">Good news — your Hisaab account access has been <strong>restored</strong>. You can log in normally again.</p>';
    return send_admin_notice_email(
        $toEmail, $toName,
        'Your Hisaab account has been reinstated',
        '#16a34a',
        '✅ Account reinstated',
        $body,
        'Your Hisaab account access has been restored and you can log in normally again.'
    );
}

/** Sent automatically when a user's monthly balance drops below their alert threshold. */
function send_low_balance_email($toEmail, $toName, $balance, $threshold, $currency) {
    $body = '<p style="color:#333;">Just a heads up — your balance for this month has dropped below the alert level you set.</p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr>
                <td style="padding:10px;background:#fef2f2;border-radius:8px 0 0 8px;text-align:center;">
                    <div style="font-size:11px;color:#991b1b;text-transform:uppercase;">Current balance</div>
                    <div style="font-size:18px;font-weight:700;color:#991b1b;">' . htmlspecialchars($currency) . ' ' . number_format($balance, 2) . '</div>
                </td>
                <td style="padding:10px;background:#f5f7f6;border-radius:0 8px 8px 0;text-align:center;">
                    <div style="font-size:11px;color:#667085;text-transform:uppercase;">Your alert threshold</div>
                    <div style="font-size:18px;font-weight:700;color:#333;">' . htmlspecialchars($currency) . ' ' . number_format($threshold, 2) . '</div>
                </td>
            </tr>
        </table>
        <p style="color:#333;">It might be a good time to review your expenses or hold off on non-essential spending.</p>
        <p style="color:#888;font-size:12px;margin-top:18px;">You\'re receiving this because low-balance alerts are turned on in your Hisaab settings. You can adjust the threshold or turn this off anytime under Settings → Notifications.</p>';

    return send_admin_notice_email(
        $toEmail, $toName,
        'Hisaab — your balance is running low',
        '#dc2626',
        '⚠ Low balance alert',
        $body,
        "Your Hisaab balance this month is $currency " . number_format($balance, 2) . ", below your alert threshold of $currency " . number_format($threshold, 2) . '.'
    );
}

/**
 * Renders and sends a daily or weekly income/expense summary email.
 * $summary = ['income' => float, 'expense' => float, 'currency' => string,
 *             'periodLabel' => string, 'topCategories' => [['name'=>..,'icon'=>..,'total'=>..], ...]]
 */
function send_report_email($toEmail, $toName, $kind, $summary) {
    $isDaily = $kind === 'daily';
    $net = $summary['income'] - $summary['expense'];
    $netColor = $net >= 0 ? '#16a34a' : '#dc2626';
    $cur = $summary['currency'];

    $catRows = '';
    foreach ($summary['topCategories'] as $c) {
        $catRows .= '<tr>
            <td style="padding:6px 0;color:#333;font-size:13.5px;">' . htmlspecialchars($c['icon'] . ' ' . $c['name']) . '</td>
            <td style="padding:6px 0;text-align:right;color:#333;font-size:13.5px;">' . htmlspecialchars($cur) . ' ' . number_format($c['total'], 2) . '</td>
        </tr>';
    }
    if ($catRows === '') {
        $catRows = '<tr><td colspan="2" style="padding:6px 0;color:#999;font-size:13px;">No expenses recorded in this period.</td></tr>';
    }

    $heading = $isDaily ? '📅 Your daily report' : '🗓️ Your weekly report';
    $subject = $isDaily ? 'Hisaab — your daily report' : 'Hisaab — your weekly report';

    $body = '
        <p style="color:#333;">Here\'s your ' . ($isDaily ? 'summary for today' : 'summary for the past 7 days') . ' (' . htmlspecialchars($summary['periodLabel']) . '):</p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr>
                <td style="padding:10px;background:#f0fdf4;border-radius:8px 0 0 8px;text-align:center;">
                    <div style="font-size:11px;color:#157347;text-transform:uppercase;">Income</div>
                    <div style="font-size:16px;font-weight:700;color:#157347;">' . htmlspecialchars($cur) . ' ' . number_format($summary['income'], 2) . '</div>
                </td>
                <td style="padding:10px;background:#fef2f2;text-align:center;">
                    <div style="font-size:11px;color:#991b1b;text-transform:uppercase;">Expenses</div>
                    <div style="font-size:16px;font-weight:700;color:#991b1b;">' . htmlspecialchars($cur) . ' ' . number_format($summary['expense'], 2) . '</div>
                </td>
                <td style="padding:10px;background:#f5f7f6;border-radius:0 8px 8px 0;text-align:center;">
                    <div style="font-size:11px;color:#667085;text-transform:uppercase;">Net</div>
                    <div style="font-size:16px;font-weight:700;color:' . $netColor . ';">' . htmlspecialchars($cur) . ' ' . number_format($net, 2) . '</div>
                </td>
            </tr>
        </table>
        <div style="font-size:13px;font-weight:700;color:#333;margin:16px 0 6px;text-transform:uppercase;letter-spacing:.03em;">Top expense categories</div>
        <table style="width:100%;border-collapse:collapse;">' . $catRows . '</table>
        <p style="color:#888;font-size:12px;margin-top:18px;">You\'re receiving this because ' . ($isDaily ? 'daily' : 'weekly') . ' reports are turned on in your Hisaab settings. You can turn them off anytime under Settings → Notifications.</p>
    ';

    return send_admin_notice_email(
        $toEmail, $toName, $subject, '#16a34a', $heading, $body,
        "Your $kind Hisaab report: Income " . $cur . ' ' . number_format($summary['income'], 2) .
        ", Expenses " . $cur . ' ' . number_format($summary['expense'], 2) .
        ", Net " . $cur . ' ' . number_format($net, 2) . '.'
    );
}
