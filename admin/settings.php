<?php
$adminTitle = 'تنظیمات سایت';
require_once __DIR__ . '/includes/header.php';

$db   = getDB();
$rows = $db->query("SELECT * FROM settings")->fetchAll();
$sets = array_column($rows, 'value', 'key');

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی.';
    } else {
        $fields = ['site_name','site_slogan','about_short','address','phone','email','social_telegram','social_youtube','social_instagram'];
        foreach ($fields as $field) {
            $val  = trim($_POST[$field] ?? '');
            $stmt = $db->prepare("INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT (key) DO UPDATE SET value=?");
            $stmt->execute([$field, $val, $val]);
        }

        // Logo upload
        if (!empty($_FILES['logo']['name'])) {
            $logoPath = uploadImage($_FILES['logo'], 'site');
            if ($logoPath) {
                $db->prepare('INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value')->execute(['site_logo', $logoPath]);
            }
        }

        $success = 'تنظیمات با موفقیت ذخیره شد.';
        $rows    = $db->query("SELECT * FROM settings")->fetchAll();
        $sets    = array_column($rows, 'value', 'key');
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">تنظیمات سایت</h5>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success alert-auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">اطلاعات سایت</div>
                <div class="admin-card-body">
                    <div class="mb-3"><label>نام سایت</label><input type="text" name="site_name" class="form-control" value="<?= sanitize($sets['site_name'] ?? SITE_NAME) ?>"></div>
                    <div class="mb-3"><label>شعار سایت</label><input type="text" name="site_slogan" class="form-control" value="<?= sanitize($sets['site_slogan'] ?? SITE_SLOGAN) ?>"></div>
                    <div><label>توضیح کوتاه درباره مدرسه</label><textarea name="about_short" class="form-control" rows="3"><?= sanitize($sets['about_short'] ?? '') ?></textarea></div>
                </div>
            </div>
            <div class="admin-card mb-4">
                <div class="admin-card-header">اطلاعات تماس</div>
                <div class="admin-card-body">
                    <div class="mb-3"><label>آدرس</label><input type="text" name="address" class="form-control" value="<?= sanitize($sets['address'] ?? SITE_ADDRESS) ?>"></div>
                    <div class="mb-3"><label>شماره تلفن</label><input type="text" name="phone" class="form-control" value="<?= sanitize($sets['phone'] ?? SITE_PHONE) ?>"></div>
                    <div><label>ایمیل</label><input type="email" name="email" class="form-control" value="<?= sanitize($sets['email'] ?? SITE_EMAIL) ?>"></div>
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header">شبکه‌های اجتماعی</div>
                <div class="admin-card-body">
                    <div class="mb-3"><label><i class="bi bi-telegram ms-2 text-info"></i>لینک تلگرام</label><input type="url" name="social_telegram" class="form-control" placeholder="https://t.me/..." value="<?= sanitize($sets['social_telegram'] ?? '') ?>"></div>
                    <div class="mb-3"><label><i class="bi bi-youtube ms-2 text-danger"></i>لینک یوتیوب</label><input type="url" name="social_youtube" class="form-control" placeholder="https://youtube.com/..." value="<?= sanitize($sets['social_youtube'] ?? '') ?>"></div>
                    <div><label><i class="bi bi-instagram ms-2 text-warning"></i>لینک اینستاگرام</label><input type="url" name="social_instagram" class="form-control" placeholder="https://instagram.com/..." value="<?= sanitize($sets['social_instagram'] ?? '') ?>"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="admin-card mb-4">
                <div class="admin-card-header">لوگوی سایت</div>
                <div class="admin-card-body">
                    <img src="<?= siteUrl('assets/images/logo.jpg') ?>?<?= time() ?>" alt="لوگو" style="max-width:120px;border-radius:8px;margin-bottom:12px">
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <div class="form-text">تصویر جدید جایگزین لوگوی فعلی می‌شود.</div>
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-body">
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle ms-1"></i>ذخیره تنظیمات</button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
