<?php
/**
 * media.php — صفحه جزئیات یک رسانه (ویدیو یا صوت)
 * Routeها: /video/{id} و /audio/{id} — شناسه رکورد media_files.
 * - kind باید با رکورد مطابق باشد، وگرنه 404
 * - والد (مطلب/درس) باید منتشرشده باشد، وگرنه 404
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$kind = $_GET['kind'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$db = getDB();
$media = null;
if (($kind === 'video' || $kind === 'audio') && $id > 0) {
    $stmt = $db->prepare('SELECT * FROM media_files WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $media = $stmt->fetch();
}
if (!$media || ($media['kind'] ?? '') !== $kind) {
    http_response_code(404);
    $pageTitle = 'رسانه یافت نشد';
    $pageDesc = 'رسانه مورد نظر یافت نشد';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5 text-center"><h1>رسانه یافت نشد</h1><p class="text-muted">ممکن است حذف شده یا آدرس نادرست باشد.</p><a href="' . siteUrl($kind === 'audio' ? 'audios' : 'videos') . '" class="btn btn-primary mt-3">بازگشت</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Published parent (post or lesson) — draft parents must not leak media.
$parent = null;
$parentTopics = [];
if (($media['ref_type'] ?? '') === 'post') {
    $stmt = $db->prepare('SELECT id, title, slug, post_type, featured_image, published_at FROM posts WHERE id=? AND status=? LIMIT 1');
    $stmt->execute([(int)$media['ref_id'], 'published']);
    $parent = $stmt->fetch();
    if ($parent) {
        $parentTopics = getTopicsForPost((int)$parent['id']);
        $parentUrl = postUrl($parent);
    }
} elseif (($media['ref_type'] ?? '') === 'lesson') {
    $stmt = $db->prepare('SELECT id, title, slug, teacher, featured_image, created_at AS published_at FROM lessons WHERE id=? AND status=? LIMIT 1');
    $stmt->execute([(int)$media['ref_id'], 'published']);
    $parent = $stmt->fetch();
    if ($parent) {
        $parentTopics = getTopicsForLesson((int)$parent['id']);
        $parentUrl = lessonUrl($parent);
    }
}
if (!$parent) {
    http_response_code(404);
    $pageTitle = 'رسانه یافت نشد';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5 text-center"><h1>رسانه در دسترس نیست</h1><a href="' . siteUrl($kind === 'audio' ? 'audios' : 'videos') . '" class="btn btn-primary mt-3">بازگشت</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$isAudio = $kind === 'audio';
$mediaTitle = $media['title'] ?: $parent['title'];
$pageTitle = $mediaTitle;
$pageDesc = excerpt(($isAudio ? 'صوت: ' : 'ویدیو: ') . $parent['title'], 160);
$canonicalOverride = mediaUrl($kind, (int)$media['id']);
$fileUrl = siteUrl(ltrim($media['file_path'], '/'));
$poster = !empty($parent['featured_image']) ? imgUrl($parent['featured_image']) : '';

// Related: same parent first, then latest of the same kind.
$related = [];
try {
    $stmt = $db->prepare('SELECT m.*, ' . ($media['ref_type'] === 'post' ? "p.title AS parent_title, p.slug AS parent_slug, p.post_type AS parent_type" : "l.title AS parent_title, l.slug AS parent_slug, 'lesson' AS parent_type")
        . ' FROM media_files m '
        . ($media['ref_type'] === 'post' ? 'JOIN posts p ON p.id=m.ref_id AND m.ref_type=?' : 'JOIN lessons l ON l.id=m.ref_id AND m.ref_type=?')
        . ' WHERE m.kind=? AND m.ref_id=? AND m.id!=? ORDER BY m.sort_order ASC, m.id ASC LIMIT 6');
    $stmt->execute([$media['ref_type'], $kind, (int)$media['ref_id'], $id]);
    $related = $stmt->fetchAll();
    if (count($related) < 6) {
        $stmt = $db->prepare('SELECT m.*, ' . ($media['ref_type'] === 'post' ? "p.title AS parent_title, p.slug AS parent_slug, p.post_type AS parent_type" : "l.title AS parent_title, l.slug AS parent_slug, 'lesson' AS parent_type")
            . ' FROM media_files m '
            . ($media['ref_type'] === 'post' ? 'JOIN posts p ON p.id=m.ref_id AND m.ref_type=? AND p.status=?' : 'JOIN lessons l ON l.id=m.ref_id AND m.ref_type=? AND l.status=?')
            . ' WHERE m.kind=? AND m.id!=? ORDER BY m.id DESC LIMIT 6');
        $stmt->execute([$media['ref_type'], 'published', $kind, $id]);
        $ids = array_column($related, 'id');
        foreach ($stmt->fetchAll() as $row) {
            if (!in_array($row['id'], $ids, true)) $related[] = $row;
            if (count($related) >= 6) break;
        }
    }
} catch (PDOException $e) { $related = []; }

$listUrl = siteUrl($isAudio ? 'audios' : 'videos');
$listLabel = $isAudio ? 'صوت‌ها' : 'ویدیوها';
$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => SITE_URL ? rtrim(SITE_URL, '/') . '/' : siteUrl()],
    ['name' => $listLabel, 'url' => $listUrl],
    ['name' => $mediaTitle, 'url' => canonicalUrl(mediaUrl($kind, (int)$media['id']))],
];
$breadcrumbsJsonLd = breadcrumbsJsonLd($breadcrumbs);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
<li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
<li class="breadcrumb-item"><a href="<?= $listUrl ?>"><?= $listLabel ?></a></li>
<li class="breadcrumb-item active" aria-current="page"><?= sanitize(mb_strimwidth($mediaTitle, 0, 60, '...')) ?></li>
</ol></nav></div></div>

<div class="py-5"><div class="container"><div class="row g-4">
<div class="col-lg-8">
<article>
<div class="d-flex flex-wrap gap-2 mb-2">
<span class="badge <?= $isAudio ? 'bg-success' : 'bg-danger' ?>"><i class="bi bi-<?= $isAudio ? 'headphones' : 'camera-video-fill' ?> ms-1"></i><?= $isAudio ? 'صوت' : 'ویدیو' ?></span>
<?php if ($parentTopics): foreach (array_slice($parentTopics, 0, 3) as $t): ?><a href="<?= topicUrl($t) ?>" class="badge bg-light text-dark border"><?= sanitize($t['name']) ?></a><?php endforeach; endif; ?>
</div>
<h1 class="h3 fw-bold"><?= sanitize($mediaTitle) ?></h1>
<p class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($parent['published_at'] ?? $media['created_at']) ?><?php if (!empty($parent['teacher'])): ?> · <i class="bi bi-person ms-1"></i><?= sanitize($parent['teacher']) ?><?php endif; ?></p>

<div class="media-player-wrap my-4">
<?php if ($isAudio): ?>
<audio controls preload="metadata" class="w-100" aria-label="<?= sanitize($mediaTitle) ?>"><source src="<?= htmlspecialchars($fileUrl, ENT_QUOTES) ?>" type="audio/mpeg">مرورگر شما از پخش صوت پشتیبانی نمی‌کند.</audio>
<?php else: ?>
<video controls playsinline preload="metadata" class="w-100" style="border-radius:12px;background:#000;max-height:520px" <?= $poster ? 'poster="' . htmlspecialchars($poster, ENT_QUOTES) . '"' : '' ?> aria-label="<?= sanitize($mediaTitle) ?>"><source src="<?= htmlspecialchars($fileUrl, ENT_QUOTES) ?>" type="video/mp4">مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.</video>
<?php endif; ?>
<div class="d-flex flex-wrap gap-2 mt-3">
<a href="<?= htmlspecialchars($fileUrl, ENT_QUOTES) ?>" download class="btn btn-outline-success btn-sm"><i class="bi bi-download ms-1"></i>دانلود فایل</a>
<a href="<?= $parentUrl ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-text ms-1"></i><?= $media['ref_type'] === 'post' ? 'مشاهده مطلب کامل' : 'مشاهده درس کامل' ?></a>
</div>
</div>

<div class="card mb-4"><div class="card-body d-flex gap-3 align-items-center">
<?php if ($poster): ?><img src="<?= htmlspecialchars($poster, ENT_QUOTES) ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:10px" loading="lazy"><?php else: ?><div style="width:72px;height:72px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center"><i class="bi bi-<?= $media['ref_type'] === 'post' ? 'file-text' : 'mortarboard' ?> text-muted" style="font-size:1.6rem"></i></div><?php endif; ?>
<div><div class="text-muted small mb-1"><?= $media['ref_type'] === 'post' ? 'مطلب مرتبط' : 'درس مرتبط' ?></div><a href="<?= $parentUrl ?>" class="fw-bold"><?= sanitize($parent['title']) ?></a></div>
</div></div>
</article>
</div>
<div class="col-lg-4">
<div class="sidebar">
<div class="sidebar-widget"><h2 class="sidebar-title"><i class="bi bi-collection-play ms-2 text-gold"></i><?= $isAudio ? 'صوت‌های مرتبط' : 'ویدیوهای مرتبط' ?></h2>
<?php if (empty($related)): ?><p class="text-muted small mb-0">رسانه مرتبط دیگری موجود نیست.</p><?php else: ?>
<div class="related-posts"><?php foreach ($related as $rel): ?><div class="related-item">
<div class="related-thumb-placeholder"><i class="bi bi-<?= $isAudio ? 'headphones' : 'camera-video' ?>"></i></div>
<div class="related-info"><a href="<?= mediaUrl($kind, (int)$rel['id']) ?>" class="related-title"><?= sanitize(mb_strimwidth($rel['title'] ?: ($rel['parent_title'] ?? 'رسانه'), 0, 55, '...')) ?></a><span class="related-date"><?= sanitize(mb_strimwidth($rel['parent_title'] ?? '', 0, 40, '...')) ?></span></div>
</div><?php endforeach; ?></div>
<?php endif; ?>
</div>
<div class="sidebar-widget"><h2 class="sidebar-title"><i class="bi bi-tags ms-2 text-gold"></i>موضوعات</h2><div class="d-flex flex-wrap gap-1"><?php foreach (array_slice($parentTopics ?: getTopics(['active' => 1, 'limit' => 12]), 0, 12) as $ct): ?><a href="<?= topicUrl($ct) ?>" class="badge bg-light text-dark border" style="font-size:.78rem"><?= sanitize($ct['name']) ?></a><?php endforeach; ?></div></div>
</div>
</div>
</div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
