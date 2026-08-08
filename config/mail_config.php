<?php
/**
 * Hissab — Gmail SMTP credentials for sending OTP emails.
 *
 * SETUP:
 * 1. Turn on 2-Step Verification on the Gmail account you'll send from:
 *      https://myaccount.google.com/security
 * 2. Generate an "App Password" (NOT your normal Gmail password):
 *      https://myaccount.google.com/apppasswords
 *    Choose app "Mail", device "Other", name it "Hissab" — Google gives you
 *    a 16-character code like: abcd efgh ijkl mnop
 * 3. Paste your Gmail address and that App Password below.
 *
 * Never commit real credentials to a public repo (e.g. GitHub).
 */

define('GMAIL_ADDRESS', 'kshitizkhatiwada787@gmail.com');
define('GMAIL_APP_PASSWORD', 'zkqj prhh mbsc kyng');
define('MAIL_FROM_NAME', 'Hisaab');
