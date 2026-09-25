<?php
$pageTitle = 'اطلاعیه‌ها و اعلانات';
$pageDesc = 'اطلاعیه‌های رسمی، برنامه‌های آموزشی و خبرهای ثبت‌شدهٔ جامعة‌الهدی را در این بخش دنبال کنید.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$opts   = ['type' => 'announcement', 'limit' => $limit, 'offset' => ($page - 1) * $limit];
if ($search) $opts['search'] = $search;

$posts = getPosts($opts);
$total = countPosts(array_merge(['type' => 'announcement'], $search ? ['search' => $search] : []));
$pages = (int)ceil($total / $limit);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active">اطلاعیه‌ها</li>
</ol></nav></div></div>
<div class="py-5"><div class="container">
    <div class="page-header mb-4">
        <h1 class="page-title"><i class="bi bi-megaphone ms-2 text-gold"></i>اطلاعیه‌ها و اعلانات</h1>
        <div class="section-divider"></div>
        <p class="text-muted mt-2">اعلانات رسمی، بخشنامه‌های آموزشی و اطلاعیه‌های ثبت‌نام مدرسه علمیه جامعه‌الهدی</p>
    </div>
    <?php if (empty($posts)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)"><i class="bi bi-megaphone display-1 text-muted opacity-25 d-block mb-3"></i><h4 class="text-muted">اطلاعیه‌ای یافت نشد</h4></div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($posts as $p): $pUrl = postUrl($p); ?>
        <div class="col-12">
            <div class="card p-3 p-md-4">
                <div class="d-flex gap-3 align-items-start">
                    <div class="topic-card-icon" style="width:52px;height:52px;font-size:1.4rem;border-radius:10px;margin-bottom:0">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <h2 class="h5 mb-0 fw-bold"><a href="<?= $pUrl ?>" class="text-reset text-decoration-none"><?= sanitize($p['title']) ?></a></h2>
                            <span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span>
                        </div>
                        <?php if ($p['summary']): ?><p class="text-muted mb-3"><?= sanitize(excerpt($p['summary'], 220)) ?></p><?php endif; ?>
                        <a href="<?= $pUrl ?>" class="btn-read-more">ادامه مطلب و جزییات <i class="bi bi-arrow-left"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, url('announcements', ['page' => '%d'])) ?></div><?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
