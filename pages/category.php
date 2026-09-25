<?php
/**
 * category.php — صفحه دسته‌بندی
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$slug = trim($_GET['slug'] ?? '');
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$cat  = $stmt->fetch();

if (!$cat) {
    if ($slug === '') { header('Location: ' . siteUrl('topics')); exit; }
    http_response_code(404);
    $pageTitle = 'دسته‌بندی یافت نشد';
    $pageDesc = 'دسته‌بندی مورد نظر یافت نشد';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5 text-center"><h1>دسته‌بندی مورد نظر یافت نشد.</h1><a href="'.siteUrl('topics').'" class="btn btn-primary mt-3">مشاهده موضوعات</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = POSTS_PER_PAGE;
$posts  = getPosts(['cat' => $cat['id'], 'limit' => $limit, 'offset' => ($page - 1) * $limit]);
// category شامل همه نوع‌ها می‌شه (به جز پیش‌نویس) — section فیلتر نمی‌کنه چون دسته‌بندی مستقل از صفحه‌ست
$total  = countPosts(['cat' => $cat['id']]);
$pages  = (int)ceil($total / $limit);
$pageTitle = $cat['name'];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active"><?= sanitize($cat['name']) ?></li>
</ol></nav></div></div>
<div class="py-5"><div class="container">
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
        <?php foreach ($posts as $k => $p):
            echo renderPostCard($p, ['featured' => $k === 0, 'cta' => 'ادامه مطلب', 'excerpt' => 120]);
        endforeach; ?>
    </div>
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, categoryUrl($slug) . '?page=%d') ?></div><?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
