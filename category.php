<?php
/**
 * category.php — صفحه دسته‌بندی
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

$slug = trim($_GET['slug'] ?? '');
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$cat  = $stmt->fetch();

if (!$cat) {
    header('Location: ' . siteUrl());
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = POSTS_PER_PAGE;
$posts  = getPosts(['cat' => $cat['id'], 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
// category شامل همه نوع‌ها می‌شه (به جز پیش‌نویس) — section فیلتر نمی‌کنه چون دسته‌بندی مستقل از صفحه‌ست
$total  = countPosts(['cat' => $cat['id']]);
$pages  = (int)ceil($total / $limit);
$pageTitle = $cat['name'];

require_once __DIR__ . '/includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active"><?= sanitize($cat['name']) ?></li>
</ol></nav></div></div>
<main class="py-5"><div class="container">
    <div class="page-header mb-4">
        <h1 class="page-title"><i class="bi bi-grid ms-2 text-gold"></i><?= sanitize($cat['name']) ?></h1>
        <?php if ($cat['description']): ?><p class="text-mid"><?= sanitize($cat['description']) ?></p><?php endif; ?>
        <div class="section-divider"></div>
        <p class="text-muted small"><?= number_format($total) ?> مطلب در این دسته‌بندی</p>
    </div>
    <?php if (empty($posts)): ?>
    <div class="text-center py-5"><i class="bi bi-folder display-1 text-muted opacity-25 d-block mb-3"></i><h4 class="text-muted">مطلبی یافت نشد</h4></div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($posts as $p): ?>
        <div class="col-md-6 col-lg-4">
            <article class="news-card h-100">
                <div class="news-card-img-wrap">
                    <?php if ($p['featured_image']): ?><img src="<?= imgUrl($p['featured_image']) ?>" alt="<?= sanitize($p['title']) ?>" class="news-card-img" loading="lazy">
                    <?php else: ?><div class="news-card-img-placeholder"><i class="bi bi-file-text"></i></div><?php endif; ?>
                    <div class="news-card-badge"><?= postTypeBadge($p['post_type']) ?></div>
                </div>
                <div class="news-card-body">
                    <div class="news-card-meta"><span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span></div>
                    <h3 class="news-card-title"><a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>"><?= sanitize($p['title']) ?></a></h3>
                    <?php if ($p['summary']): ?><p class="news-card-summary"><?= sanitize(excerpt($p['summary'], 120)) ?></p><?php endif; ?>
                    <div class="news-card-footer"><a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>" class="btn-read-more">ادامه مطلب <i class="bi bi-arrow-left"></i></a></div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, siteUrl('category.php?slug=' . urlencode($slug) . '&page=%d')) ?></div><?php endif; ?>
    <?php endif; ?>
</div></main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
