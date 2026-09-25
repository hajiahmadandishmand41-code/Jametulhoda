<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member-auth.php';
startSecureSession();
requireMember();

ensureMembersSchema();
$memberId = (int)currentMember()['id'];
$stmt = getDB()->prepare('SELECT full_name, country, phone, email, created_at, last_login FROM members WHERE id=?');
$stmt->execute([$memberId]);
$member = $stmt->fetch() ?: [];

$pageTitle = 'حساب کاربری';
$pageDesc = 'مدیریت حساب کاربری در جامعة‌الهدی.';
$canonicalOverride = accountUrl();
$noindexSeo = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="jhd-auth-page">
    <div class="container">
        <div class="jhd-auth-card jhd-auth-card-wide">
            <p class="jhd-kicker">پروفایل</p>
            <h1>حساب کاربری</h1>
            <p class="jhd-auth-lead">این حساب مخصوص اعضای سایت است و به سامانه مدیریت محتوا دسترسی ندارد.</p>
            <dl class="jhd-account-dl">
                <div><dt>نام</dt><dd><?= sanitize((string)($member['full_name'] ?? '')) ?></dd></div>
                <div><dt>کشور</dt><dd><?= sanitize((string)($member['country'] ?? '')) ?></dd></div>
                <div><dt>تلفن</dt><dd><?= sanitize((string)($member['phone'] ?? '')) ?></dd></div>
                <div><dt>ایمیل</dt><dd><?= sanitize((string)($member['email'] ?? '—')) ?></dd></div>
                <div><dt>عضویت</dt><dd><?= sanitize(persianDate((string)($member['created_at'] ?? ''))) ?></dd></div>
            </dl>
            <div class="jhd-auth-links">
                <a class="jhd-button" href="<?= url('password-change') ?>">تغییر رمز عبور</a>
                <a class="jhd-button jhd-button-ghost" href="<?= logoutUrl() ?>">خروج</a>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
