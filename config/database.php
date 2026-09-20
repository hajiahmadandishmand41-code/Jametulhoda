<?php
/**
 * database.php — اتصال PDO به دیتابیس
 * بهینه‌شده برای InfinityFree (MySQL 5.x/8.x shared hosting)
 */

require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $socket = defined('DB_SOCKET') ? DB_SOCKET : '';
    if ($socket) {
        $dsn = 'mysql:unix_socket=' . $socket
             . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;
    } else {
        $port = defined('DB_PORT') ? (int) DB_PORT : 3306;
        $dsn  = 'mysql:host=' . DB_HOST
              . ';port=' . $port
              . ';dbname=' . DB_NAME
              . ';charset=' . DB_CHARSET;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // InfinityFree: emulate_prepares باید true باشد
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // جزئیات فنی خطا فقط در لاگ سرور ثبت می‌شود، نه در خروجی عمومی (جلوگیری از افشای اطلاعات حساس)
        error_log('DB Connection Error: ' . $e->getMessage());
        http_response_code(503);
        die('<!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8">
<title>خطای دیتابیس</title>
<style>
body{font-family:Tahoma,sans-serif;background:#fff3f3;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;direction:rtl}
.box{background:#fff;border:2px solid #e74c3c;border-radius:12px;padding:40px;max-width:520px;text-align:center;width:90%}
h2{color:#e74c3c}code{background:#f8f8f8;padding:2px 6px;border-radius:4px;font-size:.85rem}
.steps{text-align:right;margin-top:20px;line-height:2}
</style></head>
<body><div class="box">
<h2>⚠️ خطا در اتصال به پایگاه داده</h2>
<p>در حال حاضر امکان اتصال به پایگاه داده وجود ندارد. لطفاً کمی بعد دوباره تلاش کنید.</p>
<div class="steps">
<strong>برای مدیر سایت:</strong><br>
۱. مطمئن شوید اطلاعات <code>config/config.php</code> (Host، DB_NAME، DB_USER، DB_PASS) صحیح است.<br>
۲. مطمئن شوید Host صحیح است: <code>' . htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8') . '</code><br>
۳. جزئیات فنی خطا در لاگ سرور (error_log) ثبت شده است.
</div>
</div></body></html>');
    }

    return $pdo;
}
