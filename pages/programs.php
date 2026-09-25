<?php
$pageTitle = 'برنامه‌های آموزشی';
$pageDesc = 'برنامه‌ها و دوره‌های آموزشی منتشرشدهٔ جامعة‌الهدی در حوزه علوم اسلامی و آموزش حوزوی.';
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
    <?= renderCategoryChips(['program'], url('programs'), 'همه برنامه‌ها') ?>
    <div class="row g-4">
        <?php foreach ($posts as $p):
            echo renderPostCard($p, ['cta' => 'ادامه مطلب', 'excerpt' => 120]);
        endforeach; ?>
    </div>
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, url('programs', ['page' => '%d'])) ?></div><?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
