<?php
/**
 * admin/index.php — داشبورد پنل مدیریت — اصلاح‌شده
 */
$adminTitle = 'داشبورد';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// آمار کلی
$totalPosts  = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$totalDrafts = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
$totalCats   = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalMsgs   = (int)$db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadMsgs  = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
$totalViews  = (int)$db->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn();
$totalLessons= (int)$db->query("SELECT COUNT(*) FROM lessons WHERE status='published'")->fetchColumn();

// تعداد به تفکیک نوع
$typeCountsStmt = $db->query("SELECT post_type, COUNT(*) AS cnt FROM posts WHERE status='published' GROUP BY post_type");
$typeCounts = [];
foreach ($typeCountsStmt->fetchAll() as $row) $typeCounts[$row['post_type']] = $row['cnt'];

// آخرین مطالب
$recentPosts = $db->query(
    "SELECT p.*, c.name AS cat_name FROM posts p
     LEFT JOIN categories c ON c.id=p.category_id
     ORDER BY p.created_at DESC LIMIT 8"
)->fetchAll();

// آخرین پیام‌ها
$recentMsgs = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!-- آمار کلی -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d4edda;color:#28a745"><i class="bi bi-file-check"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalPosts) ?></div>
                <div class="stat-label">مطالب منتشرشده</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#cfe2ff;color:#084298"><i class="bi bi-play-circle"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalLessons) ?></div>
                <div class="stat-label">درس‌های منتشرشده</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff3cd;color:#856404"><i class="bi bi-eye"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalViews) ?></div>
                <div class="stat-label">کل بازدیدها</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f8d7da;color:#721c24">
                <i class="bi bi-envelope<?= $unreadMsgs>0?'-fill':'' ?>"></i>
            </div>
            <div>
                <div class="stat-value"><?= $unreadMsgs > 0 ? $unreadMsgs : $totalMsgs ?></div>
                <div class="stat-label"><?= $unreadMsgs > 0 ? 'پیام جدید' : 'کل پیام‌ها' ?></div>
            </div>
        </div>
    </div>
</div>

<!-- آمار به تفکیک نوع -->
<div class="row g-3 mb-4">
    <?php
    $typeConfig = [
        'news'         => ['label'=>'اخبار',          'icon'=>'bi-newspaper',    'color'=>'#198754'],
        'article'      => ['label'=>'مقالات',          'icon'=>'bi-file-text',    'color'=>'#0d6efd'],
        'announcement' => ['label'=>'اطلاعیه‌ها',      'icon'=>'bi-megaphone',    'color'=>'#ffc107'],
        'speech'       => ['label'=>'سخنرانی‌ها',      'icon'=>'bi-mic',          'color'=>'#0dcaf0'],
        'program'      => ['label'=>'برنامه‌ها',        'icon'=>'bi-calendar-check','color'=>'#6f42c1'],
        'religious'    => ['label'=>'فعالیت مذهبی',    'icon'=>'bi-star',         'color'=>'#dc3545'],
    ];
    foreach ($typeConfig as $type => $conf):
        $cnt = $typeCounts[$type] ?? 0;
    ?>
    <div class="col-6 col-md-4 col-lg-2">
        <a href="<?= siteUrl('admin/posts/?type=' . $type) ?>" class="text-decoration-none">
            <div class="admin-card text-center p-3 h-100 hover-shadow">
                <i class="bi <?= $conf['icon'] ?>" style="font-size:1.6rem;color:<?= $conf['color'] ?>"></i>
                <div class="fw-bold mt-2" style="font-size:1.2rem"><?= $cnt ?></div>
                <div class="text-muted small"><?= $conf['label'] ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- دکمه‌های سریع -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">عملیات سریع</div>
            <div class="admin-card-body d-flex flex-wrap gap-2">
                <a href="<?= siteUrl('admin/posts/create.php') ?>" class="btn btn-success"><i class="bi bi-plus-circle ms-1"></i>مطلب جدید</a>
                <a href="<?= siteUrl('admin/lessons/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-square ms-1"></i>درس جدید</a>
                <a href="<?= siteUrl('admin/posts/') ?>" class="btn btn-outline-secondary"><i class="bi bi-list ms-1"></i>همه مطالب</a>
                <a href="<?= siteUrl('admin/lessons/') ?>" class="btn btn-outline-secondary"><i class="bi bi-play-circle ms-1"></i>همه درس‌ها</a>
                <a href="<?= siteUrl('admin/categories/') ?>" class="btn btn-outline-secondary"><i class="bi bi-grid ms-1"></i>دسته‌بندی‌ها</a>
                <a href="<?= siteUrl('admin/messages.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-envelope ms-1"></i>پیام‌ها
                    <?php if ($unreadMsgs > 0): ?><span class="badge bg-danger"><?= $unreadMsgs ?></span><?php endif; ?>
                </a>
                <a href="<?= siteUrl('admin/media/') ?>" class="btn btn-outline-secondary"><i class="bi bi-images ms-1"></i>رسانه</a>
                <a href="<?= siteUrl('admin/settings.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-gear ms-1"></i>تنظیمات</a>
            </div>
        </div>
    </div>
</div>

<!-- آخرین مطالب و پیام‌ها -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="bi bi-clock-history ms-2"></i>آخرین مطالب</span>
                <a href="<?= siteUrl('admin/posts/') ?>" class="btn btn-sm btn-outline-primary">همه</a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($recentPosts)): ?>
                <div class="p-4 text-center text-muted small">مطلبی وجود ندارد.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>عنوان</th><th>نوع</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($recentPosts as $p): ?>
                            <tr>
                                <td>
                                    <a href="<?= siteUrl('admin/posts/edit.php?id=' . $p['id']) ?>" class="text-decoration-none fw-bold text-dark">
                                        <?= sanitize(mb_strimwidth($p['title'],0,40,'...')) ?>
                                    </a>
                                    <?php if ($p['cat_name']): ?>
                                    <div class="text-muted" style="font-size:.75rem"><?= sanitize($p['cat_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= postTypeBadge($p['post_type']) ?></td>
                                <td>
                                    <span class="badge <?= $p['status']==='published'?'bg-success':'bg-secondary' ?>">
                                        <?= $p['status']==='published'?'منتشر':'پیش‌نویس' ?>
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size:.8rem"><?= persianDate($p['created_at']) ?></td>
                                <td>
                                    <a href="<?= siteUrl('admin/posts/edit.php?id=' . $p['id']) ?>" class="btn btn-xs btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
                                    <a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="btn btn-xs btn-sm btn-outline-success py-0 px-2"><i class="bi bi-eye"></i></a>
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
                <a href="<?= siteUrl('admin/messages.php') ?>" class="btn btn-sm btn-outline-primary">همه</a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($recentMsgs)): ?>
                <div class="p-4 text-center text-muted small">پیامی وجود ندارد.</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentMsgs as $msg): ?>
                    <li class="list-group-item py-3 <?= !$msg['is_read']?'bg-light':'' ?>">
                        <div class="fw-bold small">
                            <?= sanitize($msg['name']) ?>
                            <?php if (!$msg['is_read']): ?><span class="badge bg-danger ms-1">جدید</span><?php endif; ?>
                        </div>
                        <div class="text-muted" style="font-size:.78rem"><?= sanitize(mb_strimwidth($msg['subject'] ?? '',0,35,'...')) ?></div>
                        <a href="<?= siteUrl('admin/messages.php?id=' . $msg['id']) ?>" class="btn btn-xs btn-sm btn-link p-0 mt-1" style="font-size:.78rem">مشاهده ›</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
