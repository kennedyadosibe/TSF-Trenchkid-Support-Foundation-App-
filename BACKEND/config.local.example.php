<?php
// Copy this file to BACKEND/config.local.php on hosting or local XAMPP.
// Do not commit config.local.php because it may contain real secrets.

return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'tsf',
    'DB_USER' => 'root',
    'DB_PASS' => '',

    'SITE_URL' => 'https://www.tsfghana.org',
    'ADMIN_EMAIL' => 'admin@tsfghana.org',

    'PAYSTACK_SECRET_KEY' => '',
    'PAYSTACK_CALLBACK_URL' => 'https://www.tsfghana.org/donate.html',

    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_USER' => '',
    'SMTP_PASS' => '',
    'FROM_EMAIL' => 'noreply@tsfghana.org',

    'SMS_API_URL' => '',
    'SMS_API_KEY' => '',
    'SMS_SENDER_ID' => 'TSF',
];
