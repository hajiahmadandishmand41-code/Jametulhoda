<?php
$adminTitle = 'تغییر رمز عبور';
require_once __DIR__ . '/includes/header.php';

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی.';
    } else {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $db     = getDB();
        $admin2 = currentAdmin();
        $stmt   = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$admin2['id']]);
        $row    = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            $error = 'رمز عبور فعلی اشتباه است.';
        } elseif (strlen($new) < 8) {
            $error = 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.';
        } elseif ($new !== $confirm) {
            $error = 'تکرار رمز عبور مطابقت ندارد.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $admin2['id']]);
            $_SESSION['flash_msg']  = 'رمز عبور با موفقیت تغییر یافت.';
            $_SESSION['flash_type'] = 'success';
            redirect(siteUrl('admin/'));
        }
    }
}
?>
<div class="row justify-content-center">
<div class="col-md-6">
<div class="admin-card">
    <div class="admin-card-header"><i class="bi bi-key ms-2"></i>تغییر رمز عبور</div>
    <div class="admin-card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
        <form method="post" class="admin-form">
            <?= csrfField() ?>
            <div class="mb-3">
                <label>رمز عبور فعلی</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>رمز عبور جدید</label>
                <input type="password" name="new_password" class="form-control" required minlength="8">
                <div class="form-text">حداقل ۸ کاراکتر</div>
            </div>
            <div class="mb-4">
                <label>تکرار رمز عبور جدید</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle ms-1"></i>ذخیره تغییرات</button>
            <a href="<?= siteUrl('admin/') ?>" class="btn btn-outline-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
