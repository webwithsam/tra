<?php
define('FLW_SECRET_KEY', getenv('FLW_SECRET_KEY'));
define('FLW_PUBLIC_KEY', getenv('FLW_PUBLIC_KEY'));
define('FLW_WEBHOOK_HASH', getenv('FLW_WEBHOOK_HASH'));

define('APP_BASE_URL', 'https://yourdomain.co.tz/tra-revenue-hub');
define('PAYMENT_SUCCESS_URL', APP_BASE_URL . '/payment_success.php');

define('DB_HOST', 'localhost');
define('DB_NAME', 'tra_revenue');
define('DB_USER', 'root');
define('DB_PASS', '');
