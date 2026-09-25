<?php
/**
 * ورود کاربران عمومی — جدا از ورود مدیر
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member-auth.php';
startSecureSession();

if (isMemberLoggedIn()) {
    redirect(accountUrl());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. صفحه را رفرش کنید.';
    } else {
        $identifier = trim((string)($_POST['identifier'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($identifier === '' || $password === '') {
            $error = 'شماره تلفن (یا ایمیل) و رمز عبور را وارد کنید.';
        } elseif (loginMember($identifier, $password)) {
            redirect(accountUrl());
        } else {
            $error = 'اطلاعات ورود نادرست است.';
        }
    }
}

$pageTitle = 'ورود به حساب کاربری';
$pageDesc = 'ورود اعضای جامعة‌الهدی به حساب کاربری برای پیگیری مطالب علمی، آموزشی و پژوهشی.';
$canonicalOverride = loginUrl();
$noindexSeo = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="jhd-auth-page">
    <div class="container">
        <div class="jhd-auth-card">
            <p class="jhd-kicker">حساب کاربری</p>
            <h1>ورود به جامعة‌الهدی</h1>
            <p class="jhd-auth-lead">با شماره تلفن یا ایمیل ثبت‌شده وارد شوید. این ورود مخصوص اعضای سایت است و به پنل مدیریت دسترسی نمی‌دهد.</p>
            <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= sanitize($error) ?></div><?php endif; ?>
            <form method="post" action="<?= loginUrl() ?>" autocomplete="on" class="jhd-auth-form">
                <?= csrfField() ?>
                <label for="identifier">شماره تلفن یا ایمیل</label>
                <input type="text" id="identifier" name="identifier" class="form-control" required maxlength="180" value="<?= sanitize($_POST['identifier'] ?? '') ?>" autocomplete="username">
                <label for="password">رمز عبور</label>
                <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                <button type="submit" class="jhd-button jhd-button-block">ورود</button>
            </form>
            <div class="jhd-auth-links">
                <a href="<?= registerUrl() ?>">حساب ندارید؟ ثبت‌نام کنید</a>
                <a href="<?= adminLoginUrl() ?>">ورود مدیران</a>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
