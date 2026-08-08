<?php
/**
 * Hissab — Mailer
 * Sends OTP emails through Gmail's SMTP server using PHPMailer.
 *
 * PHPMailer is NOT bundled here — install it one of two ways:
 *
 *   A) Composer (recommended):
 *      composer require phpmailer/phpmailer
 *      -> creates vendor/autoload.php, which this file auto-detects.
 *
 *   B) Manual (no Composer):
 *      Download https://github.com/PHPMailer/PHPMailer (Code -> Download ZIP)
 *      Copy these 3 files from its `src/` folder into:
 *          hissab/libs/PHPMailer/src/Exception.php
 *          hissab/libs/PHPMailer/src/PHPMailer.php
 *          hissab/libs/PHPMailer/src/SMTP.php
 *      -> this file auto-detects that folder too.
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
