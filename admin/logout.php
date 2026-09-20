<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { requirePostCsrf(); logoutAdmin(); }
?>
<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>خروج</title>
<form method="post"><?= csrfField() ?><p>آیا می‌خواهید از حساب خارج شوید؟</p><button>خروج از حساب</button></form></html>
