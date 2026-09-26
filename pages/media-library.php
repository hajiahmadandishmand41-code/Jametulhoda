<?php
/**
 * media-library.php — نگارخانه و کتابخانه چندرسانه‌ای (ویدیوها و صوت‌ها)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$kind = ($_GET['kind'] ?? 'video') === 'audio' ? 'audio' : 'video';
$pageTitle = $kind === 'audio' ? 'کتابخانه صوتی و سخنرانی‌ها' : 'نگارخانه ویدیویی';
$pageDesc = $kind === 'audio' ? 'سخنرانی‌ها، صوت جلسات علمی، ادعیه و زیارات مدرسه علمیه جامعه‌الهدی' : 'ویدیوها، نشست‌های تخصصی و کلیپ‌های تصویری مدرسه علمیه جامعه‌الهدی';
$mediaPath = current_path();
if (in_array($mediaPath, ['/audio', '/audios'], true) || ($mediaPath === '/media' && isset($_GET['kind']) && $kind === 'audio')) {
    $canonicalOverride = url('audios');
} elseif (in_array($mediaPath, ['/video', '/videos'], true) || ($mediaPath === '/media' && isset($_GET['kind']) && $kind === 'video')) {
    $canonicalOverride = url('videos');
} else {
    $canonicalOverride = url('media');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;
$column = $kind === 'audio' ? 'audio_file' : 'video_file';

$sql = "SELECT m.id AS media_id, m.file_path AS path, p.title, p.slug, p.post_type, p.featured_image, p.published_at AS date, 'post' AS target
 FROM media_files m JOIN posts p ON p.id=m.ref_id AND m.ref_type='post' WHERE m.kind=? AND p.status='published'
 UNION SELECT m.id, m.file_path, l.title, l.slug, 'lesson', l.featured_image, l.created_at, 'lesson'
 FROM media_files m JOIN lessons l ON l.id=m.ref_id AND m.ref_type='lesson' WHERE m.kind=? AND l.status='published'
 UNION SELECT NULL, l.$column, l.title, l.slug, 'lesson', l.featured_image, l.created_at, 'lesson' FROM lessons l WHERE l.status='published' AND l.$column IS NOT NULL AND l.$column<>''";

if ($kind === 'video') {
    $sql .= " UNION SELECT NULL, p.featured_video, p.title, p.slug, p.post_type, p.featured_image, p.published_at, 'post' FROM posts p WHERE p.status='published' AND p.featured_video IS NOT NULL AND p.featured_video<>''";
}

$db = getDB();
$countStmt = $db->prepare('SELECT COUNT(*) FROM (' . $sql . ') media');
$countStmt->execute([$kind, $kind]);
$total = (int)$countStmt->fetchColumn();

$queryStmt = $db->prepare('SELECT * FROM (' . $sql . ') media ORDER BY date DESC LIMIT ? OFFSET ?');
$queryStmt->execute([$kind, $kind, $limit, $offset]);
$items = $queryStmt->fetchAll();
$pages = (int)ceil($total / $limit);

$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'چندرسانه‌ای', 'url' => url('media')],
    ['name' => $kind === 'audio' ? 'صوت‌ها' : 'ویدیوها', 'url' => url('media', ['kind' => $kind])]
];
$breadcrumbsJsonLd = breadcrumbsJsonLd($breadcrumbs);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <?php foreach ($breadcrumbs as $i => $bc): $isLast = ($i === count($breadcrumbs) - 1); ?>
        <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>>
          <?php if (!$isLast): ?><a href="<?= sanitize($bc['url']) ?>"><?= sanitize($bc['name']) ?></a><?php else: ?><?= sanitize($bc['name']) ?><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5">
  <div class="container">
    <!-- Header -->
    <div class="jhd-page-heading d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
      <?= jhd_page_head([
        'eyebrow' => 'شنیدن، دیدن و آموختن معارف',
        'icon' => $kind === 'audio' ? 'bi-headphones' : 'bi-play-circle',
        'title' => $pageTitle,
        'lead' => 'آرشیو فایل‌های رسانه‌ای منتشرشده جامعه‌الهدی؛ همراه شما در مسیر یادگیری',
      ]) ?>

      <!-- تب‌های سوئیچ نوع رسانه -->
      <div class="btn-group" role="group">
        <a href="<?= url('media', ['kind' => 'video']) ?>" class="btn btn-sm <?= $kind === 'video' ? 'btn-primary' : 'btn-outline-primary' ?>">
          <i class="bi bi-camera-video ms-1"></i>ویدیوها
        </a>
        <a href="<?= url('media', ['kind' => 'audio']) ?>" class="btn btn-sm <?= $kind === 'audio' ? 'btn-primary' : 'btn-outline-primary' ?>">
          <i class="bi bi-headphones ms-1"></i>صوت‌ها
        </a>
      </div>
    </div>

    <?php if (empty($items)): ?>
    <div class="jhd-empty-state"><i class="bi bi-film" aria-hidden="true"></i><p>هنوز فایلی در این بخش منتشر نشده است.</p></div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($items as $item):
          if (!empty($item['media_id'])) {
              $detailUrl = mediaUrl($kind, (int)$item['media_id']);
          } elseif ($item['target'] === 'lesson') {
              $detailUrl = lessonUrl($item['slug']);
          } else {
              $detailUrl = postUrl(['slug' => $item['slug'], 'post_type' => $item['post_type'] ?? 'post']);
          }
      ?>
      <div class="col-md-6 col-lg-4">
        <article class="jhd-card jhd-card--media h-100">
          <?php if ($kind === 'video'): ?>
          <div class="jhd-card-media jhd-media-frame">
            <video controls preload="none" playsinline poster="<?= imgUrl($item['featured_image'] ?? '') ?>"
                   aria-label="<?= sanitize($item['title']) ?>" src="<?= imgUrl($item['path']) ?>"></video>
            <span class="jhd-card-badge">ویدیو</span>
          </div>
          <?php else: ?>
          <div class="jhd-card-media jhd-card-media--audio">
            <i class="bi bi-soundwave jhd-audio-wave" aria-hidden="true"></i>
            <span class="jhd-card-badge">صوت</span>
          </div>
          <?php endif; ?>

          <div class="jhd-card-body">
            <div class="jhd-card-meta">
              <time><i class="bi bi-calendar3 ms-1"></i><?= persianDate($item['date']) ?></time>
            </div>
            <h2 class="jhd-card-title">
              <a href="<?= $detailUrl ?>"><?= sanitize($item['title']) ?></a>
            </h2>

            <?php if ($kind === 'audio'): ?>
            <div class="jhd-audio-player">
              <audio controls preload="none" aria-label="<?= sanitize($item['title']) ?>" src="<?= imgUrl($item['path']) ?>"></audio>
            </div>
            <?php endif; ?>

            <div class="jhd-card-foot">
              <span class="jhd-card-author"><i class="bi bi-<?= $kind === 'audio' ? 'headphones' : 'play-circle' ?> ms-1"></i><?= $kind === 'audio' ? 'فایل صوتی' : 'فیلم' ?></span>
              <a class="btn-read-more" href="<?= $detailUrl ?>">مشاهده <i class="bi bi-arrow-left"></i></a>
            </div>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- صفحه‌بندی -->
    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page, url('media', ['kind' => $kind, 'page' => '%d'])) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
