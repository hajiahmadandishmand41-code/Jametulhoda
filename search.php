<?php
$q = trim($_GET['q'] ?? '');
$pageTitle = $q ? 'جستجو: ' . $q : 'جستجو';
require_once __DIR__ . '/includes/header.php';
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = POSTS_PER_PAGE;
$posts = $q ? getPosts(['search' => $q, 'limit' => $limit, 'offset' => ($page - 1) * $limit]) : [];
// جستجو در همه پست‌های منتشرشده بدون فیلتر section — کاربر هر چی پیدا کرد ببینه
$total = $q ? countPosts(['search' => $q]) : 0;
$pages = (int)ceil($total / $limit);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active">جستجو</li>
</ol></nav></div></div>
<main class="py-5"><div class="container">
    <div class="page-header mb-4"><h1 class="page-title"><i class="bi bi-search ms-2 text-gold"></i>جستجو در سایت</h1><div class="section-divider"></div></div>
    <form method="get" class="mb-5">
        <div class="input-group input-group-lg" style="max-width:600px">
            <input type="text" name="q" class="form-control" placeholder="جستجو..." value="<?= sanitize($q) ?>" autofocus>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search ms-1"></i>جستجو</button>
        </div>
    </form>
    <?php if ($q): ?>
    <div class="mb-4">
        <?php if ($total > 0): ?>
        <p class="text-muted"><?= number_format($total) ?> نتیجه برای «<strong><?= sanitize($q) ?></strong>»</p>
        <?php else: ?>
        <div class="text-center py-5"><i class="bi bi-search display-1 text-muted opacity-25 d-block mb-3"></i><h4 class="text-muted">نتیجه‌ای برای «<?= sanitize($q) ?>» یافت نشد</h4></div>
        <?php endif; ?>
    </div>
    <?php if (!empty($posts)): ?>
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
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, siteUrl('search.php') . '?q=' . urlencode($q) . '&page=%d') ?></div><?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div></main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
