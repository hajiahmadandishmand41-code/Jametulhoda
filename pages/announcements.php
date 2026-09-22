<?php
$pageTitle = 'اطلاعیه‌ها';
require_once __DIR__ . '/../includes/header.php';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = POSTS_PER_PAGE;
$opts   = ['type' => 'announcement', 'section' => 'announcements', 'limit' => $limit, 'offset' => ($page - 1) * $limit];
if ($search) $opts['search'] = $search;
$posts = getPosts($opts);
$total = countPosts(array_merge(['type' => 'announcement', 'section' => 'announcements'], $search ? ['search' => $search] : []));
$pages = (int)ceil($total / $limit);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active">اطلاعیه‌ها</li>
</ol></nav></div></div>
<div class="py-5"><div class="container">
    <div class="page-header mb-4"><h1 class="page-title"><i class="bi bi-megaphone ms-2 text-gold"></i>اطلاعیه‌ها</h1><div class="section-divider"></div></div>
    <?php if (empty($posts)): ?>
    <div class="text-center py-5"><i class="bi bi-megaphone display-1 text-muted opacity-25 d-block mb-3"></i><h4 class="text-muted">اطلاعیه‌ای یافت نشد</h4></div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($posts as $p): ?>
        <div class="col-12"><div class="announcement-card d-flex gap-4 align-items-start">
            <div class="announcement-icon"><i class="bi bi-megaphone-fill"></i></div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <h4 class="announcement-title mb-1"><a href="<?= siteUrl('post?slug=' . urlencode($p['slug'])) ?>"><?= sanitize($p['title']) ?></a></h4>
                    <span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span>
                </div>
                <?php if ($p['summary']): ?><p class="text-mid mb-2"><?= sanitize(excerpt($p['summary'], 200)) ?></p><?php endif; ?>
                <a href="<?= siteUrl('post?slug=' . urlencode($p['slug'])) ?>" class="btn btn-sm btn-outline-warning">ادامه مطلب <i class="bi bi-arrow-left ms-1"></i></a>
            </div>
        </div></div>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, siteUrl('announcements') . '?page=%d') ?></div><?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
