<?php
$adminTitle = 'اعضای سایت';
require_once __DIR__ . '/../includes/header.php';
requireRole(['superadmin', 'admin']);
require_once __DIR__ . '/../../includes/member-auth.php';
ensureMembersSchema();

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($id < 1) {
        $error = 'عضو معتبر نیست.';
    } else {
        getDB()->prepare('UPDATE members SET is_active=?, auth_version = COALESCE(auth_version,1)+1 WHERE id=?')->execute([$active, $id]);
        $success = 'وضعیت عضو به‌روز شد.';
    }
}

$members = [];
try {
    $members = getDB()->query('SELECT id, full_name, country, phone, email, is_active, last_login, created_at FROM members ORDER BY id DESC LIMIT 300')->fetchAll();
} catch (Throwable $e) {
    $error = 'جدول اعضای عمومی هنوز در دسترس نیست.';
}
?>
<h1 class="h4">اعضای عمومی سایت</h1>
<p class="text-muted">این فهرست جدا از مدیران است. اعضای عمومی هرگز به پنل مدیریت دسترسی ندارند.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
<div class="table-responsive admin-card">
<table class="table admin-table mb-0">
<thead><tr><th>نام</th><th>کشور</th><th>تلفن</th><th>ایمیل</th><th>وضعیت</th><th>آخرین ورود</th><th></th></tr></thead>
<tbody>
<?php if (!$members): ?>
<tr><td colspan="7" class="text-center text-muted py-4">هنوز عضو عمومی ثبت نشده است.</td></tr>
<?php endif; ?>
<?php foreach ($members as $m): ?>
<tr>
    <td><?= sanitize($m['full_name']) ?></td>
    <td><?= sanitize($m['country']) ?></td>
    <td><?= sanitize($m['phone']) ?></td>
    <td><?= sanitize((string)($m['email'] ?: '—')) ?></td>
    <td><?= ((int)$m['is_active'] === 1) ? 'فعال' : 'غیرفعال' ?></td>
    <td><?= sanitize($m['last_login'] ? persianDate($m['last_login']) : '—') ?></td>
    <td>
        <form method="post" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <?php if ((int)$m['is_active'] === 1): ?>
            <button class="btn btn-sm btn-outline-danger">غیرفعال</button>
            <?php else: ?>
            <input type="hidden" name="is_active" value="1">
            <button class="btn btn-sm btn-outline-success">فعال‌سازی</button>
            <?php endif; ?>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
