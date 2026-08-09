<?php
/**
 * Hissab — Real-world email verification (AbstractAPI)
 *
 * DNS checks (checkdnsrr) only prove a DOMAIN can receive mail.
 * This goes further and checks whether the SPECIFIC MAILBOX is real,
 * by asking AbstractAPI to perform an SMTP handshake against it.
 *
**/

define('EMAIL_VERIFY_API_KEY', '5977002763c84fbb82f00b20118ddd10');

/**
 * Checks whether $email is a real, deliverable mailbox.
 *
 * @return bool|null  true = deliverable, false = confirmed bad/disposable,
 *                     null = inconclusive or API unavailable (caller should
 *                     fall back to the OTP step as the real proof).
 */
function verify_email_real($email) {
    if (EMAIL_VERIFY_API_KEY === '' || EMAIL_VERIFY_API_KEY === '5977002763c84fbb82f00b20118ddd10') {
        return null; // API key not configured yet — skip silently, rely on OTP only
    }

    $url = 'https://emailvalidation.abstractapi.com/v1/'
         . '?api_key=' . urlencode(EMAIL_VERIFY_API_KEY)
         . '&email=' . urlencode($email);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr || !$response) {
        error_log('Email verify API error: ' . $curlErr);
        return null; // network/API hiccup — don't block signup over it
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return null;
    }

    // AbstractAPI fields: deliverability = DELIVERABLE | UNDELIVERABLE | UNKNOWN | RISKY
    $deliverability = $data['deliverability'] ?? 'UNKNOWN';
    $isDisposable = $data['is_disposable_email']['value'] ?? false;

    if ($isDisposable) {
        return false; // block temp-mail / throwaway addresses
    }
    if ($deliverability === 'UNDELIVERABLE') {
        return false;
    }
    if ($deliverability === 'DELIVERABLE') {
        return true;
    }

    return null; // RISKY / UNKNOWN — inconclusive, let the OTP step decide
}
