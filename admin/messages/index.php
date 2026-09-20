<?php
/**
 * admin/messages/index.php — مشاهده پیام‌های تماس با مدیر
 * اصلاح‌شده: از is_read استفاده می‌کند (سازگار با ساختار اصلی دیتابیس)
 */
$adminTitle = 'پیام‌های تماس';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// اطمینان از وجود جدول (با is_read)
$db->exec(
    "CREATE TABLE IF NOT EXISTS `contact_messages` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name`       VARCHAR(200) NOT NULL,
        `email`      VARCHAR(200) DEFAULT NULL,
        `phone`      VARCHAR(50)  DEFAULT NULL,
        `subject`    VARCHAR(300) DEFAULT NULL,
        `message`    TEXT         NOT NULL,
        `ip_address` VARCHAR(45)  DEFAULT NULL,
        `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_is_read` (`is_read`),
        KEY `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

// Migration: اگر ستون is_read وجود نداشت اضافه کن
try {
    $chk = $db->query("SHOW COLUMNS FROM contact_messages LIKE 'is_read'");
    if ($chk->rowCount() === 0) {
        $db->exec("ALTER TABLE contact_messages ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (\Throwable $e) {}

// علامت‌گذاری به‌عنوان خوانده‌شده
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $db->prepare("UPDATE contact_messages SET is_read=1 WHERE id=? AND is_read=0")
       ->execute([(int)$_GET['read']]);
    header('Location: ' . siteUrl('admin/messages/'));
    exit;
}

// حذف پیام
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (verifyCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        $db->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['delete']]);
        $_SESSION['flash_msg']  = 'پیام حذف شد.';
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: ' . siteUrl('admin/messages/'));
    exit;
}

// فیلتر وضعیت خواندن
$filter = in_array($_GET['filter'] ?? '', ['unread','read','all']) ? ($_GET['filter'] ?? 'all') : 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = '1=1';
$params = [];
if ($filter === 'unread') { $where = 'is_read = 0'; }
if ($filter === 'read')   { $where = 'is_read = 1'; }

$cstmt = $db->prepare("SELECT COUNT(*) FROM contact_messages WHERE $where");
$cstmt->execute($params);
$total = (int)$cstmt->fetchColumn();

$mstmt = $db->prepare("SELECT * FROM contact_messages WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$mstmt->execute($params);
$messages = $mstmt->fetchAll();

$newCount = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">
        <i class="bi bi-envelope-fill ms-2 text-primary"></i>پیام‌های تماس با مدیر
        <?php if ($newCount > 0): ?>
        <span class="badge bg-danger ms-2"><?= $newCount ?> جدید</span>
        <?php endif; ?>
    </h5>
</div>

<?php if (!empty($_SESSION['flash_msg'])): ?>
<div class="alert alert-<?= sanitize($_SESSION['flash_type'] ?? 'info') ?> alert-dismissible">
    <?= sanitize($_SESSION['flash_msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); endif; ?>

<!-- فیلتر -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <?php
    $filterOpts = ['all' => 'همه', 'unread' => 'خوانده‌نشده', 'read' => 'خوانده‌شده'];
    foreach ($filterOpts as $fv => $fl):
    ?>
    <a href="?filter=<?= $fv ?>"
       class="btn btn-sm <?= $filter===$fv ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= $fl ?>
        <?php if ($fv==='unread' && $newCount>0): ?>
        <span class="badge bg-danger ms-1"><?= $newCount ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($messages)): ?>
<div class="text-center py-5">
    <i class="bi bi-inbox display-1 text-muted opacity-25 d-block mb-3"></i>
    <h5 class="text-muted">پیامی یافت نشد</h5>
</div>
<?php else: ?>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>فرستنده</th>
                <th>موضوع</th>
                <th>تاریخ</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr class="<?= !$msg['is_read'] ? 'table-warning fw-bold' : '' ?>">
                <td>
                    <div><?= sanitize($msg['name']) ?></div>
                    <?php if ($msg['email']): ?>
                    <div class="text-muted small"><?= sanitize($msg['email']) ?></div>
                    <?php endif; ?>
                    <?php if ($msg['phone']): ?>
                    <div class="text-muted small"><i class="bi bi-telephone ms-1"></i><?= sanitize($msg['phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div><?= sanitize(mb_strimwidth($msg['subject'] ?? '(بدون موضوع)', 0, 60, '...')) ?></div>
                    <div class="text-muted small mt-1"><?= sanitize(excerpt($msg['message'], 80)) ?></div>
                </td>
                <td class="small text-muted"><?= persianDate($msg['created_at']) ?></td>
                <td>
                    <?php if (!$msg['is_read']): ?>
                    <span class="badge bg-danger">جدید</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">خوانده</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#msgModal_<?= $msg['id'] ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php if (!$msg['is_read']): ?>
                        <a href="?read=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-success" title="علامت خوانده">
                            <i class="bi bi-check2"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($msg['email']): ?>
                        <a href="mailto:<?= sanitize($msg['email']) ?>?subject=پاسخ: <?= urlencode($msg['subject'] ?? '') ?>"
                           class="btn btn-sm btn-outline-info" title="پاسخ ایمیل">
                            <i class="bi bi-reply-fill"></i>
                        </a>
                        <?php endif; ?>
                        <a href="?delete=<?= $msg['id'] ?>&<?= CSRF_TOKEN_NAME ?>=<?= urlencode(generateCsrfToken()) ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('حذف این پیام؟')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>

            <!-- Modal پیام کامل -->
            <div class="modal fade" id="msgModal_<?= $msg['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= sanitize($msg['subject'] ?? '(بدون موضوع)') ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4"><strong>فرستنده:</strong> <?= sanitize($msg['name']) ?></div>
                                <?php if ($msg['email']): ?>
                                <div class="col-md-4"><strong>ایمیل:</strong> <a href="mailto:<?= sanitize($msg['email']) ?>"><?= sanitize($msg['email']) ?></a></div>
                                <?php endif; ?>
                                <?php if ($msg['phone']): ?>
                                <div class="col-md-4"><strong>تلفن:</strong> <?= sanitize($msg['phone']) ?></div>
                                <?php endif; ?>
                                <div class="col-12"><strong>تاریخ:</strong> <?= persianDate($msg['created_at']) ?></div>
                            </div>
                            <hr>
                            <div class="p-3 bg-light rounded" style="white-space:pre-wrap"><?= sanitize($msg['message']) ?></div>
                        </div>
                        <div class="modal-footer">
                            <?php if ($msg['email']): ?>
                            <a href="mailto:<?= sanitize($msg['email']) ?>?subject=پاسخ: <?= urlencode($msg['subject'] ?? '') ?>"
                               class="btn btn-success">
                                <i class="bi bi-reply-fill ms-1"></i>پاسخ با ایمیل
                            </a>
                            <?php endif; ?>
                            <?php if (!$msg['is_read']): ?>
                            <a href="?read=<?= $msg['id'] ?>" class="btn btn-outline-primary">
                                <i class="bi bi-check2 ms-1"></i>علامت‌گذاری خوانده‌شده
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- صفحه‌بندی -->
<?php if ($total > $limit): ?>
<div class="mt-4">
    <?= paginate($total, $limit, $page, siteUrl('admin/messages/') . '?filter=' . $filter . '&page=%d') ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
