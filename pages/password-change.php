<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member-auth.php';
startSecureSession();
requireMember();

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. صفحه را رفرش کنید.';
    } else {
        $current = (string)($_POST['current_password'] ?? '');
        $next = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');
        if ($next !== $confirm) {
            $error = 'تکرار رمز عبور مطابقت ندارد.';
        } elseif (strlen($next) < 8) {
            $error = 'رمز عبور جدید باید حداقل ۸ نویسه باشد.';
        } elseif (!changeMemberPassword((int)currentMember()['id'], $current, $next)) {
            $error = 'رمز فعلی نادرست است.';
        } else {
            $success = 'رمز عبور با موفقیت تغییر کرد.';
        }
    }
}

$pageTitle = 'تغییر رمز عبور';
$pageDesc = 'تغییر رمز عبور حساب کاربری جامعة‌الهدی.';
$canonicalOverride = url('password-change');
$noindexSeo = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="jhd-auth-page">
    <div class="container">
        <div class="jhd-auth-card">
            <h1>تغییر رمز عبور</h1>
            <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
            <form method="post" action="<?= url('password-change') ?>" class="jhd-auth-form" autocomplete="off">
                <?= csrfField() ?>
                <label for="current_password">رمز فعلی</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
                <label for="new_password">رمز جدید</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                <label for="new_password_confirm">تکرار رمز جدید</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
                <button type="submit" class="jhd-button jhd-button-block">ذخیره رمز جدید</button>
            </form>
            <div class="jhd-auth-links"><a href="<?= accountUrl() ?>">بازگشت به حساب</a></div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
