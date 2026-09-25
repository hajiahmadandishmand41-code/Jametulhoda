<?php
/**
 * admin/login.php — ورود مدیر
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(adminUrl());
}

$error = '';
$loginAttempts = $_SESSION['login_attempts'] ?? 0;
$lockUntil     = $_SESSION['login_lock_until'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if ($lockUntil > time()) {
        $remaining = ceil(($lockUntil - time()) / 60);
        $error = "تعداد تلاش‌های ناموفق زیاد است. لطفاً $remaining دقیقه صبر کنید.";
    } elseif (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. صفحه را رفرش کنید.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $error = 'نام کاربری و رمز عبور را وارد کنید.';
        } elseif (loginAdmin($username, $password)) {
            $_SESSION['login_attempts'] = 0;
            redirect(adminUrl());
        } else {
            $loginAttempts++;
            $_SESSION['login_attempts'] = $loginAttempts;
            if ($loginAttempts >= 5) {
                $_SESSION['login_lock_until'] = time() + 900; // 15 min
                $error = 'حساب به مدت ۱۵ دقیقه قفل شد.';
            } else {
                $remaining = 5 - $loginAttempts;
                $error = "نام کاربری یا رمز عبور اشتباه است. ($remaining تلاش باقی‌مانده)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود به سامانه مدیریت | <?= sanitize(SITE_NAME) ?></title>
<meta name="description" content="ورود امن مدیران و ویراستاران جامعة‌الهدی به سامانه مدیریت محتوا.">
<meta name="robots" content="noindex, nofollow">
<link rel="canonical" href="<?= sanitize(absolute_url('admin/login')) ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/vendor/bootstrap.rtl.min.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/vendor/icons/bootstrap-icons.min.css') ?>">

<style>
* { box-sizing: border-box; }
body { font-family: 'Vazirmatn', sans-serif; background: linear-gradient(135deg, #0f2317 0%, #2d6a4f 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.login-card { background: #fff; border-radius: 20px; overflow: hidden; max-width: 400px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
.login-header { background: #0f2317; padding: 36px 30px; text-align: center; border-bottom: 3px solid #c9a84c; }
.login-header img { width: 72px; height: 72px; border-radius: 50%; border: 3px solid #c9a84c; object-fit: contain; margin-bottom: 14px; }
.login-header h1 { color: #c9a84c; font-size: 1rem; margin: 0 0 4px; font-weight: 700; }
.login-header p { color: rgba(255,255,255,.6); font-size: .78rem; margin: 0; }
.login-body { padding: 32px 28px; }
.form-label { font-weight: 600; font-size: .88rem; color: #0f2317; margin-bottom: 6px; }
.form-control { border: 2px solid #e0e0e0; border-radius: 10px; padding: 11px 14px; font-family: inherit; font-size: .92rem; transition: border-color .2s; }
.form-control:focus { border-color: #40916c; box-shadow: 0 0 0 3px rgba(64,145,108,.15); outline: none; }
.icon-input { position: relative; }
.icon-input i { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #999; }
.icon-input input { padding-right: 38px; }
.btn-login { width: 100%; background: #0f2317; color: #fff; border: none; border-radius: 10px; padding: 13px; font-family: inherit; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s, transform .2s; }
.btn-login:hover { background: #1a3d27; transform: translateY(-1px); }
.alert { border-radius: 10px; border: none; font-size: .88rem; }
.back-link { text-align: center; margin-top: 16px; font-size: .85rem; }
.back-link a { color: #40916c; }
html[data-theme="dark"] body { background: radial-gradient(circle at 88% 8%, rgba(228,190,107,.12), transparent 20rem), #0e1613; color: #eaf2ed; }
html[data-theme="dark"] .login-card { background: #15221c; border: 1px solid #263930; }
html[data-theme="dark"] .login-header { background: #0b1711; }
html[data-theme="dark"] .form-label { color: #eaf2ed; }
html[data-theme="dark"] .form-control { background: #101a14; color: #eaf2ed; border-color: #34473b; }
html[data-theme="dark"] .form-control::placeholder { color: #9ab1a6; }
html[data-theme="dark"] .back-link a { color: #83e2bf; }
@media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition: none !important; animation: none !important; } }
</style>
<script src="<?= siteUrl('assets/js/theme.js') ?>"></script>
<link rel="stylesheet" href="<?= siteUrl('assets/css/design-system.css') ?>">
<script src="<?= siteUrl('assets/js/interface.js') ?>" defer></script>
</head>
<body class="jhd-login-site"><button style="position:fixed;bottom:20px;left:20px;z-index:1000;background:var(--jhd-surface)" class="jhd-icon-btn" data-theme-toggle aria-label="تغییر پوسته" aria-pressed="false"><i class="bi bi-moon"></i></button>
<div class="login-card">
    <div class="login-header">
        <img src="<?= imgUrl(getSetting('site_logo', 'assets/img/logo.jpg')) ?>" alt="لوگو" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕌</text></svg>'">
        <h1><?= sanitize(SITE_NAME) ?></h1>
        <p>پنل مدیریت</p>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
        <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= adminLoginUrl() ?>" autocomplete="off">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label" for="username">نام کاربری</label>
                <div class="icon-input">
                    <i class="bi bi-person-fill"></i>
                    <input type="text" name="username" id="username" autocomplete="username" class="form-control" placeholder="نام کاربری را وارد کنید" value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">رمز عبور</label>
                <div class="icon-input">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="password" id="password" autocomplete="current-password" class="form-control" placeholder="رمز عبور را وارد کنید" required>
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right ms-2"></i>ورود به پنل</button>
        </form>
        <div class="back-link">
            <a href="<?= url() ?>"><i class="bi bi-arrow-right ms-1"></i>بازگشت به سایت</a>
            <div class="mt-2"><a href="<?= loginUrl() ?>">ورود اعضای سایت</a></div>
        </div>
    </div>
</div>
</body>
</html>
