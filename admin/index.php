<?php
/**
 * admin/index.php — داشبورد مدیریت
 *
 * ساختار: یک کارت خلاصهٔ کوچک (خوش‌آمد، وضعیت، آخرین ورود، شمارنده‌ها) و بعد
 * بلافاصله شاخص‌های کلیدی و میان‌برهای عملیات. هیچ تابلوی بزرگ تزئینی بالای
 * صفحه نیست؛ کاربر از همان ابتدا محتوا و کنترل‌ها را می‌بیند.
 */
$adminTitle = 'داشبورد';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

$count = static function (string $sql): int {
    try { return (int)getDB()->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; }
};

$totalPosts   = $count("SELECT COUNT(*) FROM posts WHERE status='published'");
$totalDrafts  = $count("SELECT COUNT(*) FROM posts WHERE status='draft'");
$totalTopics  = $count("SELECT COUNT(*) FROM topics WHERE is_active=1");
$totalBooks   = $count("SELECT COUNT(*) FROM books");
$totalLessons = $count("SELECT COUNT(*) FROM lessons WHERE status='published'");
$totalCollections = $count("SELECT COUNT(*) FROM lesson_collections WHERE is_active=1");
$totalMedia   = $count("SELECT COUNT(*) FROM media_files");
$totalMsgs    = $count("SELECT COUNT(*) FROM contact_messages");
$unreadMsgs   = $count("SELECT COUNT(*) FROM contact_messages WHERE is_read=0");
$totalContent = $totalPosts + $totalLessons + $totalBooks;
$userCounts   = jhd_count_users();
$totalUsers   = array_sum($userCounts);

$typeCountsStmt = $db->query("SELECT post_type, COUNT(*) AS cnt FROM posts WHERE status='published' GROUP BY post_type");
$typeCounts = [];
foreach ($typeCountsStmt->fetchAll() as $row) $typeCounts[$row['post_type']] = (int)$row['cnt'];

$recentPosts = [];
try { $recentPosts = $db->query("SELECT p.id,p.title,p.post_type,p.status,p.created_at,p.slug, c.name AS cat_name FROM posts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC LIMIT 7")->fetchAll(); } catch (Throwable $e) { }
$recentMsgs = [];
try { $recentMsgs = $db->query("SELECT id,name,subject,is_read,created_at FROM contact_messages ORDER BY created_at DESC LIMIT 4")->fetchAll(); } catch (Throwable $e) { }

$systemOk = true;
$systemNotes = [];
try {
    getDB()->query('SELECT 1');
} catch (Throwable $e) { $systemOk = false; $systemNotes[] = 'اتصال دیتابیس'; }
if (!is_writable(UPLOAD_DIR)) { $systemOk = false; $systemNotes[] = 'پوشهٔ آپلود'; }
if (!is_writable(STORAGE_DIR . '/logs') && !is_dir(STORAGE_DIR . '/logs')) { $systemNotes[] = 'پوشهٔ لاگ'; }
$systemStatusText = $systemOk ? 'فعال' : 'نیازمند بررسی: ' . implode('، ', $systemNotes);
$adminAccountRow = currentUser();
$lastLoginText = ($adminAccountRow['last_login'] ?? '') !== '' ? persianDate((string)$adminAccountRow['last_login']) : '—';
?>

<div class="admin-summary-card mb-4">
    <div class="admin-summary-main">
        <div class="admin-summary-avatar"><?= sanitize(mb_substr($admin['name'] ?: $admin['user'], 0, 1)) ?></div>
        <div>
            <h1 class="admin-summary-title">خوش آمدید، <?= sanitize($admin['name'] ?: $admin['user']) ?></h1>
            <p class="admin-summary-sub">
                <span class="badge <?= $systemOk ? 'bg-success' : 'bg-warning text-dark' ?>"><i class="bi bi-activity ms-1"></i>وضعیت سیستم: <?= sanitize($systemStatusText) ?></span>
                <span class="text-muted"><?= sanitize(jhd_role_label($admin['role'])) ?></span>
                <span class="text-muted">آخرین ورود: <?= sanitize($lastLoginText) ?></span>
            </p>
        </div>
    </div>
    <div class="admin-summary-stats">
        <div><strong><?= number_format($totalContent) ?></strong><span>محتوای منتشرشده</span></div>
        <div><strong><?= number_format($totalUsers) ?></strong><span>کاربران</span></div>
        <div><strong><?= number_format($typeCounts['research'] ?? 0) ?></strong><span>پژوهش‌ها</span></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/content') ?>"><div class="stat-icon" style="background:#d4edda;color:#28a745"><i class="bi bi-file-check"></i></div><div><div class="stat-value"><?= number_format($totalPosts) ?></div><div class="stat-label">مطالب منتشرشده</div></div></a></div>
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/topics') ?>"><div class="stat-icon" style="background:#e8f5ee;color:#245c4c"><i class="bi bi-diagram-3"></i></div><div><div class="stat-value"><?= number_format($totalTopics) ?></div><div class="stat-label">موضوعات فعال</div></div></a></div>
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/courses') ?>"><div class="stat-icon" style="background:#cfe2ff;color:#084298"><i class="bi bi-mortarboard"></i></div><div><div class="stat-value"><?= number_format($totalLessons) ?></div><div class="stat-label">درس‌های منتشرشده</div></div></a></div>
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/books') ?>"><div class="stat-icon" style="background:#fdf6e3;color:#856404"><i class="bi bi-book"></i></div><div><div class="stat-value"><?= number_format($totalBooks) ?></div><div class="stat-label">کتاب‌ها</div></div></a></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/uploads') ?>"><div class="stat-icon" style="background:#e2e3ff;color:#4b4bff"><i class="bi bi-images"></i></div><div><div class="stat-value"><?= number_format($totalMedia) ?></div><div class="stat-label">فایل‌های رسانه</div></div></a></div>
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/messages') ?>"><div class="stat-icon" style="background:#f8d7da;color:#721c24"><i class="bi bi-envelope<?= $unreadMsgs > 0 ? '-fill' : '' ?>"></i></div><div><div class="stat-value"><?= $unreadMsgs > 0 ? number_format($unreadMsgs) : number_format($totalMsgs) ?></div><div class="stat-label"><?= $unreadMsgs > 0 ? 'پیام خوانده‌نشده' : 'کل پیام‌ها' ?></div></div></a></div>
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/content') ?>"><div class="stat-icon" style="background:#fff3cd;color:#856404"><i class="bi bi-file-earmark"></i></div><div><div class="stat-value"><?= number_format($totalDrafts) ?></div><div class="stat-label">پیش‌نویس‌ها</div></div></a></div>
    <div class="col-6 col-lg-3"><a class="stat-card stat-link" href="<?= url('admin/courses') ?>"><div class="stat-icon" style="background:#e8f5ee;color:#1a7a4a"><i class="bi bi-collection"></i></div><div><div class="stat-value"><?= number_format($totalCollections) ?></div><div class="stat-label">مجموعه‌های درسی</div></div></a></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">عملیات سریع</div>
            <div class="admin-card-body d-flex flex-wrap gap-2">
                <a href="<?= url('admin/news/create') ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle ms-1"></i>خبر جدید</a>
                <a href="<?= url('admin/articles/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-file-text ms-1"></i>مقاله جدید</a>
                <a href="<?= url('admin/posts/create', ['post_type' => 'research']) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-journal-richtext ms-1"></i>پژوهش جدید</a>
                <a href="<?= url('admin/books/create') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-book ms-1"></i>کتاب جدید</a>
                <a href="<?= url('admin/courses/create') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-mortarboard ms-1"></i>درس جدید</a>
                <a href="<?= url('admin/topics/create') ?>" class="btn btn-warning btn-sm"><i class="bi bi-diagram-3 ms-1"></i>موضوع جدید</a>
                <a href="<?= url('admin/uploads') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-upload ms-1"></i>آپلود رسانه</a>
                <a href="<?= url('admin/messages') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-envelope ms-1"></i>پیام‌ها <?php if ($unreadMsgs > 0): ?><span class="badge bg-danger"><?= $unreadMsgs ?></span><?php endif; ?></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="bi bi-clock-history ms-2"></i>آخرین مطالب</span>
                <a href="<?= url('admin/content') ?>" class="btn btn-sm btn-outline-primary">همه</a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (!$recentPosts): ?>
                <div class="p-4 text-center text-muted small">هنوز مطلبی ثبت نشده است. از «عملیات سریع» شروع کنید.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>عنوان</th><th>نوع</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($recentPosts as $p): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('admin/posts/edit', ['id' => $p['id']]) ?>" class="text-decoration-none fw-bold text-dark"><?= sanitize(mb_strimwidth((string)$p['title'], 0, 44, '…')) ?></a>
                                    <?php if (!empty($p['cat_name'])): ?><div class="text-muted" style="font-size:.75rem"><?= sanitize((string)$p['cat_name']) ?></div><?php endif; ?>
                                </td>
                                <td><?= postTypeBadge($p['post_type']) ?></td>
                                <td><span class="badge <?= $p['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>"><?= $p['status'] === 'published' ? 'منتشر' : 'پیش‌نویس' ?></span></td>
                                <td class="text-muted" style="font-size:.8rem"><?= persianDate((string)$p['created_at']) ?></td>
                                <td class="text-nowrap">
                                    <a href="<?= url('admin/posts/edit', ['id' => $p['id']]) ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="ویرایش"><i class="bi bi-pencil"></i></a>
                                    <a href="<?= postUrl($p) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success py-0 px-2" title="مشاهده"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="bi bi-envelope ms-2"></i>پیام‌های اخیر</span>
                <a href="<?= url('admin/messages') ?>" class="btn btn-sm btn-outline-primary">همه</a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (!$recentMsgs): ?>
                <div class="p-4 text-center text-muted small">پیامی وجود ندارد.</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentMsgs as $msg): ?>
                    <li class="list-group-item py-3 <?= (int)$msg['is_read'] === 0 ? 'bg-light' : '' ?>">
                        <div class="fw-bold small"><?= sanitize((string)$msg['name']) ?></div>
                        <div class="text-muted" style="font-size:.8rem"><?= sanitize(mb_strimwidth((string)($msg['subject'] ?? ''), 0, 46, '…')) ?></div>
                        <div class="text-muted" style="font-size:.72rem"><?= persianDate((string)$msg['created_at']) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
