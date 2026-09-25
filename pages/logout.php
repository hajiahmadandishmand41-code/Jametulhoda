<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member-auth.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostCsrf();
    logoutMember();
}

$pageTitle = 'خروج از حساب';
$pageDesc = 'خروج از حساب کاربری جامعة‌الهدی.';
$canonicalOverride = logoutUrl();
$noindexSeo = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="jhd-auth-page">
    <div class="container">
        <div class="jhd-auth-card">
            <h1>خروج از حساب کاربری</h1>
            <p class="jhd-auth-lead">آیا می‌خواهید از حساب کاربری خود خارج شوید؟</p>
            <form method="post" action="<?= logoutUrl() ?>">
                <?= csrfField() ?>
                <button type="submit" class="jhd-button">خروج</button>
                <a class="jhd-button jhd-button-ghost" href="<?= url() ?>">انصراف</a>
            </form>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
