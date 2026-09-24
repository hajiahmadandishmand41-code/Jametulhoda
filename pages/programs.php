<?php
$pageTitle = 'برنامه‌های آموزشی';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$posts = getPosts(['type' => 'program', 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
$total = countPosts(['type' => 'program']);
$pages = (int)ceil($total / $limit);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active">برنامه‌های آموزشی</li>
</ol></nav></div></div>
<div class="py-5"><div class="container">
    <div class="page-header mb-4">
        <h1 class="page-title"><i class="bi bi-calendar-check ms-2 text-gold"></i>برنامه‌های آموزشی</h1>
        <div class="section-divider"></div>
        <p class="text-muted mt-2">دوره‌ها، کارگاه‌ها و برنامه‌های آموزشی مدرسه علمیه جامعه‌الهدی</p>
    </div>
    <?php if (empty($posts)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)"><i class="bi bi-calendar-check display-1 text-muted opacity-25 d-block mb-3"></i><h4 class="text-muted">برنامه‌ای یافت نشد</h4></div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($posts as $p): $pUrl = postUrl($p); ?>
        <div class="col-md-6 col-lg-4">
            <article class="news-card h-100">
                <div class="news-card-img-wrap">
                    <a href="<?= $pUrl ?>">
                    <?php if ($p['featured_image']): ?><img src="<?= imgUrl($p['featured_image']) ?>" alt="<?= sanitize($p['title']) ?>" class="news-card-img" loading="lazy">
                    <?php else: ?><div class="news-card-placeholder"><i class="bi bi-calendar-check"></i></div><?php endif; ?>
                    </a>
                </div>
                <div class="news-card-body">
                    <div class="news-card-meta"><span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span></div>
                    <h3 class="news-card-title"><a href="<?= $pUrl ?>"><?= sanitize($p['title']) ?></a></h3>
                    <?php if ($p['summary']): ?><p class="news-card-summary"><?= sanitize(excerpt($p['summary'], 120)) ?></p><?php endif; ?>
                    <div class="news-card-footer"><a href="<?= $pUrl ?>" class="btn-read-more">ادامه مطلب <i class="bi bi-arrow-left"></i></a></div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, url('programs', ['page' => '%d'])) ?></div><?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
