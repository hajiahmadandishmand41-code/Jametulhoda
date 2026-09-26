<?php
/**
 * search.php — موتور جستجوی جامع در آرشیو معارف، مقالات، اخبار، دروس، کتب و موضوعات
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 200) : '';
$pageTitle = $q ? 'جستجو: ' . $q : 'جستجو در آرشیو محتوا';
$pageDesc = $q ? 'نتایج جستجو برای «' . $q . '» در موضوعات، مقالات، گزارش‌ها، کتاب‌ها، دروس و رسانه‌های جامعه‌الهدی.' : 'جستجو در آرشیو محتوایی مدرسه جامعه‌الهدی — موضوعات، مقالات، گزارش‌ها، کتاب‌ها، دروس، ویدیو و صوت.';

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$results = [];
$total = 0;

if ($q) {
    $offset = ($page - 1) * $limit;
    $data = searchAll($q, $limit, $offset);
    $results = $data['results'];
    $total = $data['total'];
}
$pages = (int)ceil($total / $limit);

$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'جستجو', 'url' => url('search')]
];
if ($q) {
    $breadcrumbs[] = ['name' => $q, 'url' => url('search', ['q' => $q])];
}
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
    <div class="page-header mb-4">
      <h1 class="page-title mb-1">
        <i class="bi bi-search ms-2 text-gold"></i> جستجو در آرشیو محتوایی
      </h1>
      <div class="section-divider"></div>
      <p class="text-muted mt-2 mb-0">جستجو در اخبار، مقالات، پژوهش‌ها، کتاب‌ها، درس‌ها، ویدیوها، صوت‌ها و موضوعات</p>
    </div>

    <!-- فرم جستجو -->
    <form method="get" class="mb-5" role="search">
      <?= queryKeepFields() ?>
      <div class="input-group input-group-lg" style="max-width:640px">
        <input type="search" name="q" class="form-control" aria-label="جستجو در آرشیو محتوا" placeholder="مثلاً: مهدویت، فلسفه، اصول فقه، کلام..." value="<?= sanitize($q) ?>" autofocus>
        <button type="submit" class="btn btn-primary"><i class="bi bi-search ms-1"></i>جستجو</button>
      </div>
    </form>

    <?php if ($q): ?>
    <div class="mb-4">
      <?php if ($total > 0): ?>
      <p class="text-muted">
        یافت شد: <strong><?= number_format($total) ?></strong> نتیجه برای عبارت «<strong><?= sanitize($q) ?></strong>»
      </p>
      <?php else: ?>
      <div class="jhd-empty-state">
        <h2>نتیجه‌ای برای «<?= sanitize($q) ?>» یافت نشد.</h2>
        <p class="text-muted small">لطفاً املای کلمات را بررسی کنید یا عبارت دیگری را جستجو فرمایید.</p>
        <a href="<?= url('topics') ?>" class="btn btn-outline-primary btn-sm mt-2">
          مرور اطلس موضوعات
        </a>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($results)): ?>
    <div class="row g-4">
      <?php foreach ($results as $p):
          if ($p['target'] === 'media') {
              $isAudio = ($p['media_kind'] ?? '') === 'audio';
              $resultUrl = mediaUrl($isAudio ? 'audio' : 'video', (int)$p['id']);
              $resultType = $isAudio ? 'audio' : 'video';
          } elseif ($p['target'] === 'topic') {
              $resultUrl = topicUrl($p);
              $resultType = 'topic';
          } elseif ($p['target'] === 'book') {
              $resultUrl = bookUrl($p);
              $resultType = 'book';
          } elseif ($p['target'] === 'lesson') {
              $resultUrl = lessonUrl($p);
              $resultType = 'lesson';
          } else {
              $resultUrl = postUrl($p);
              $resultType = (string)($p['post_type'] ?? 'post');
          }
          echo renderPostCard($p, [
              'type' => $resultType,
              'url' => $resultUrl,
              'excerpt' => 120,
              'cta' => 'مشاهده محتوا',
          ]);
      endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
