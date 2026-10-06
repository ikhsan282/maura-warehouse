<?php
define('APP_NAME', 'Maura Warehouse');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/maura-warehouse'); // change for production
define('APP_TIMEZONE', 'Asia/Jakarta');

// Email (PHP mail() — works on shared hosting)
define('MAIL_FROM', 'no-reply@maurawarehouse.com');
define('MAIL_FROM_NAME', 'Maura Warehouse');

// Session
define('SESSION_LIFETIME', 7200); // 2 hours

// Pagination
define('PER_PAGE', 20);

date_default_timezone_set(APP_TIMEZONE);

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false, // set true on HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
