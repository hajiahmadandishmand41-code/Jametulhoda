<?php
/**
 * admin/index.php — داشبورد محتوامحور
 */
$adminTitle = 'داشبورد';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

// آمار کلی — without views/likes
$totalPosts  = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$totalDrafts = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
try{ $totalTopics = (int)$db->query("SELECT COUNT(*) FROM topics WHERE is_active=1")->fetchColumn(); } catch(Exception $e){ $totalTopics=0; }
try{ $totalBooks = (int)$db->query("SELECT COUNT(*) FROM books WHERE status='published'")->fetchColumn(); } catch(Exception $e){ $totalBooks=(int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn(); }
$totalMsgs   = (int)$db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadMsgs  = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
$totalLessons= (int)$db->query("SELECT COUNT(*) FROM lessons WHERE status='published'")->fetchColumn();
try{ $totalCollections = (int)$db->query("SELECT COUNT(*) FROM lesson_collections WHERE is_active=1")->fetchColumn(); } catch(Exception $e){ $totalCollections=0; }

$typeCountsStmt = $db->query("SELECT post_type, COUNT(*) AS cnt FROM posts WHERE status='published' GROUP BY post_type");
$typeCounts = [];
foreach ($typeCountsStmt->fetchAll() as $row) $typeCounts[$row['post_type']] = $row['cnt'];

$recentPosts = $db->query("SELECT p.*, c.name AS cat_name FROM posts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC LIMIT 8")->fetchAll();
$recentMsgs = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#d4edda;color:#28a745"><i class="bi bi-file-check"></i></div><div><div class="stat-value"><?= number_format($totalPosts) ?></div><div class="stat-label">مطالب منتشرشده</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#e8f5ee;color:#245c4c"><i class="bi bi-diagram-3"></i></div><div><div class="stat-value"><?= number_format($totalTopics) ?></div><div class="stat-label">موضوعات فعال</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#cfe2ff;color:#084298"><i class="bi bi-play-circle"></i></div><div><div class="stat-value"><?= number_format($totalLessons) ?></div><div class="stat-label">درس‌های منتشرشده</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#fdf6e3;color:#856404"><i class="bi bi-book"></i></div><div><div class="stat-value"><?= number_format($totalBooks) ?></div><div class="stat-label">کتاب‌ها</div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#fff3cd;color:#856404"><i class="bi bi-collection"></i></div><div><div class="stat-value"><?= number_format($totalCollections) ?></div><div class="stat-label">مجموعه‌های درسی</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#f8d7da;color:#721c24"><i class="bi bi-envelope<?= $unreadMsgs>0?'-fill':'' ?>"></i></div><div><div class="stat-value"><?= $unreadMsgs > 0 ? $unreadMsgs : $totalMsgs ?></div><div class="stat-label"><?= $unreadMsgs > 0 ? 'پیام جدید' : 'کل پیام‌ها' ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#e2e3ff;color:#4b4bff"><i class="bi bi-file-earmark"></i></div><div><div class="stat-value"><?= number_format($totalDrafts) ?></div><div class="stat-label">پیش‌نویس‌ها</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon" style="background:#e8f5ee;color:#1a7a4a"><i class="bi bi-grid"></i></div><div><div class="stat-value"><?= number_format($totalTopics + $totalPosts) ?></div><div class="stat-label">محتوای موضوع‌محور</div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <?php
    $typeConfig = [
        'report'       => ['label'=>'گزارش‌ها',        'icon'=>'bi-newspaper',    'color'=>'#d48806'],
        'article'      => ['label'=>'مقالات',          'icon'=>'bi-file-text',    'color'=>'#0d6efd'],
        'research'     => ['label'=>'پژوهش‌ها',        'icon'=>'bi-journal-richtext','color'=>'#6f42c1'],
        'announcement' => ['label'=>'اطلاعیه‌ها',      'icon'=>'bi-megaphone',    'color'=>'#ffc107'],
        'qa'           => ['label'=>'پرسش و پاسخ',     'icon'=>'bi-question-circle','color'=>'#198754'],
        'news'         => ['label'=>'اخبار',           'icon'=>'bi-newspaper',    'color'=>'#6c757d'],
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

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">عملیات سریع — مدیریت محتوای موضوع‌محور</div>
            <div class="admin-card-body d-flex flex-wrap gap-2">
                <a href="<?= siteUrl('admin/topics/') ?>" class="btn btn-warning"><i class="bi bi-diagram-3 ms-1"></i> مدیریت موضوعات</a>
                <a href="<?= siteUrl('admin/posts/create.php') ?>" class="btn btn-success"><i class="bi bi-plus-circle ms-1"></i> مطلب جدید (گزارش/مقاله/پژوهش)</a>
                <a href="<?= siteUrl('admin/books/create.php') ?>" class="btn btn-outline-warning"><i class="bi bi-book ms-1"></i> کتاب جدید</a>
                <a href="<?= siteUrl('admin/lesson-collections/') ?>" class="btn btn-primary"><i class="bi bi-collection ms-1"></i> مجموعه‌های درسی</a>
                <a href="<?= siteUrl('admin/lessons/create.php') ?>" class="btn btn-outline-primary"><i class="bi bi-plus-square ms-1"></i> درس جدید</a>
                <a href="<?= siteUrl('admin/banners/') ?>" class="btn btn-outline-secondary"><i class="bi bi-megaphone ms-1"></i> بنر ویژه</a>
                <a href="<?= siteUrl('admin/messages.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-envelope ms-1"></i> پیام‌ها <?php if ($unreadMsgs > 0): ?><span class="badge bg-danger"><?= $unreadMsgs ?></span><?php endif; ?></a>
                <a href="<?= siteUrl('admin/media/') ?>" class="btn btn-outline-secondary"><i class="bi bi-images ms-1"></i> رسانه</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="bi bi-clock-history ms-2"></i> آخرین مطالب</span>
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
                                <td><a href="<?= siteUrl('admin/posts/edit.php?id=' . $p['id']) ?>" class="text-decoration-none fw-bold text-dark"><?= sanitize(mb_strimwidth($p['title'],0,40,'...')) ?></a><?php if ($p['cat_name']): ?><div class="text-muted" style="font-size:.75rem"><?= sanitize($p['cat_name']) ?></div><?php endif; ?></td>
                                <td><?= postTypeBadge($p['post_type']) ?></td>
                                <td><span class="badge <?= $p['status']==='published'?'bg-success':'bg-secondary' ?>"><?= $p['status']==='published'?'منتشر':'پیش‌نویس' ?></span></td>
                                <td class="text-muted" style="font-size:.8rem"><?= persianDate($p['created_at']) ?></td>
                                <td><a href="<?= siteUrl('admin/posts/edit.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a><a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2"><i class="bi bi-eye"></i></a></td>
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
                <span><i class="bi bi-envelope ms-2"></i> پیام‌های اخیر</span>
                <a href="<?= siteUrl('admin/messages.php') ?>" class="btn btn-sm btn-outline-primary">همه</a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($recentMsgs)): ?>
                <div class="p-4 text-center text-muted small">پیامی وجود ندارد.</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentMsgs as $msg): ?>
                    <li class="list-group-item py-3 <?= !$msg['is_read']?'bg-light':'' ?>">
                        <div class="fw-bold small"><?= sanitize($msg['name']) ?> <?php if (!$msg['is_read']): ?><span class="badge bg-danger ms-1">جدید</span><?php endif; ?></div>
                        <div class="text-muted" style="font-size:.78rem"><?= sanitize(mb_strimwidth($msg['subject'] ?? '',0,35,'...')) ?></div>
                        <a href="<?= siteUrl('admin/messages.php?id=' . $msg['id']) ?>" class="btn btn-sm btn-link p-0 mt-1" style="font-size:.78rem">مشاهده ›</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
