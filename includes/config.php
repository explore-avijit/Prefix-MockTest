<?php
date_default_timezone_set('Asia/Kolkata');
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prefix_mocktest_db');

// SMTP Configuration (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'avijit.system@gmail.com');
define('SMTP_PASS', 'niakavhsduatpfio');
define('SMTP_PORT', 587);
define('SMTP_FROM', 'noreply@prefix.com');
define('SMTP_FROM_NAME', 'Prefix Mock Test');

// API Settings
// This header might be redundant if specific APIs set it, but good default.
if (!headers_sent()) {
    header('Content-Type: application/json');
}
