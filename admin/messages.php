<?php
/**
 * admin/messages.php — مدیریت پیام‌های تماس — اصلاح‌شده
 */
$adminTitle = 'پیام‌های تماس';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// حذف پیام
if (!empty($_POST['delete'])) {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $_SESSION['flash_msg']  = 'خطای امنیتی. دوباره تلاش کنید.';
        $_SESSION['flash_type'] = 'danger';
        redirect(siteUrl('admin/messages.php'));
    }
    $db->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_POST['delete']]);
    $_SESSION['flash_msg']  = 'پیام حذف شد.';
    $_SESSION['flash_type'] = 'success';
    redirect(siteUrl('admin/messages.php'));
}

// مشاهده و علامت‌گذاری به عنوان خوانده‌شده
$viewMsg = null;
if (!empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $viewMsg = $stmt->fetch() ?: null;

}

// لیست پیام‌ها
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$total = (int)$db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$pages = (int)ceil($total / $limit);

$msgs  = $db->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
$msgs->execute([$limit, $offset]);
$messages = $msgs->fetchAll();

$unread = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">
        <i class="bi bi-envelope ms-2"></i>پیام‌های تماس (<?= number_format($total) ?>)
        <?php if ($unread > 0): ?>
        <span class="badge bg-danger"><?= $unread ?> جدید</span>
        <?php endif; ?>
    </h5>
    <?php if ($unread > 0): ?>
    <form method="post" action="">
        <?= csrfField() ?>
        <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-check2-all ms-1"></i>همه را خوانده علامت بزن
        </button>
    </form>
    <?php endif; ?>
</div>

<?php
// علامت همه به عنوان خوانده‌شده
if (!empty($_POST['mark_all_read']) && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $db->query("UPDATE contact_messages SET is_read=1");
    redirect(siteUrl('admin/messages.php'));
}
?>

<div class="row g-4">
    <!-- لیست پیام‌ها -->
    <div class="col-lg-<?= $viewMsg ? '4' : '12' ?>">
        <div class="admin-card">
            <div class="admin-card-body p-0">
                <?php if (empty($messages)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-envelope display-4 d-block mb-3 opacity-25"></i>
                    <p>پیامی دریافت نشده است.</p>
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($messages as $msg): ?>
                    <li class="list-group-item <?= !$msg['is_read']?'bg-light border-start border-4 border-primary':'' ?> py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 ms-2">
                                <div class="fw-bold small">
                                    <?= sanitize($msg['name']) ?>
                                    <?php if (!$msg['is_read']): ?>
                                    <span class="badge bg-danger ms-1 fs-7">جدید</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small"><?= sanitize(mb_strimwidth($msg['subject'] ?: 'بدون موضوع', 0, 40, '...')) ?></div>
                                <div class="text-muted" style="font-size:.75rem">
                                    <i class="bi bi-calendar3 ms-1"></i><?= persianDate($msg['created_at']) ?>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="?id=<?= $msg['id'] ?>" class="btn btn-xs btn-sm btn-outline-primary py-0 px-2" title="مشاهده"><i class="bi bi-eye"></i></a>
                                <a href="?delete=<?= $msg['id'] ?>" class="btn btn-xs btn-sm btn-outline-danger py-0 px-2" title="حذف" data-confirm="حذف این پیام؟"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($pages > 1): ?>
                <div class="p-3">
                    <nav><ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php for ($i=1; $i<=$pages; $i++): ?>
                        <li class="page-item <?= $i===$page?'active':'' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul></nav>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- نمایش پیام -->
    <?php if ($viewMsg): ?>
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-envelope-open ms-2"></i>متن پیام</span>
                <div class="d-flex gap-2">
                    <?php if ($viewMsg['email']): ?>
                    <a href="mailto:<?= sanitize($viewMsg['email']) ?>?subject=پاسخ: <?= urlencode($viewMsg['subject'] ?? '') ?>" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-reply ms-1"></i>پاسخ
                    </a>
                    <?php endif; ?>
                    <a href="?delete=<?= $viewMsg['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="حذف این پیام؟">
                        <i class="bi bi-trash ms-1"></i>حذف
                    </a>
                </div>
            </div>
            <div class="admin-card-body">
                <table class="table table-sm mb-4">
                    <tr><th style="width:130px">فرستنده:</th><td><strong><?= sanitize($viewMsg['name']) ?></strong></td></tr>
                    <?php if ($viewMsg['email']): ?>
                    <tr><th>ایمیل:</th><td><a href="mailto:<?= sanitize($viewMsg['email']) ?>"><?= sanitize($viewMsg['email']) ?></a></td></tr>
                    <?php endif; ?>
                    <?php if ($viewMsg['phone']): ?>
                    <tr><th>تلفن:</th><td><a href="tel:<?= sanitize($viewMsg['phone']) ?>"><?= sanitize($viewMsg['phone']) ?></a></td></tr>
                    <?php endif; ?>
                    <?php if ($viewMsg['subject']): ?>
                    <tr><th>موضوع:</th><td><?= sanitize($viewMsg['subject']) ?></td></tr>
                    <?php endif; ?>
                    <tr><th>تاریخ:</th><td><?= persianDate($viewMsg['created_at']) ?></td></tr>
                </table>
                <div class="bg-light p-4 rounded" style="white-space:pre-line;line-height:1.9;font-size:.95rem">
                    <?= sanitize($viewMsg['message']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
