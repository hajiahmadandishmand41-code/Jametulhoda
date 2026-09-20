<?php
$pageTitle = 'فعالیت‌های مذهبی';
require_once __DIR__ . '/includes/header.php';
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = POSTS_PER_PAGE;
$posts = getPosts(['type' => 'religious', 'section' => 'religious', 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
$total = countPosts(['type' => 'religious', 'section' => 'religious']);
$pages = (int)ceil($total / $limit);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active">فعالیت‌های مذهبی</li>
</ol></nav></div></div>
<div class="py-5"><div class="container">
    <div class="page-header mb-4"><h1 class="page-title"><i class="bi bi-star-fill ms-2 text-gold"></i>فعالیت‌های مذهبی</h1><div class="section-divider"></div></div>
    <?php if (empty($posts)): ?>
    <div class="text-center py-5"><i class="bi bi-star display-1 text-muted opacity-25 d-block mb-3"></i><h4 class="text-muted">فعالیتی یافت نشد</h4></div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($posts as $p): ?>
        <div class="col-md-6 col-lg-4">
            <article class="news-card h-100">
                <div class="news-card-img-wrap">
                    <?php if ($p['featured_image']): ?><img src="<?= imgUrl($p['featured_image']) ?>" alt="<?= sanitize($p['title']) ?>" class="news-card-img" loading="lazy">
                    <?php else: ?><div class="news-card-img-placeholder" style="background:#fce4ec"><i class="bi bi-star text-danger" style="font-size:3rem"></i></div><?php endif; ?>
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
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, siteUrl('religious-activities.php') . '?page=%d') ?></div><?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
