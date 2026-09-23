<?php
/**
 * search.php — موتور جستجوی جامع در آرشیو معارف، مقالات، اخبار، دروس، کتب و موضوعات
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$q = trim($_GET['q'] ?? '');
$pageTitle = $q ? 'جستجو: ' . $q : 'جستجو در آرشیو محتوا';
$pageDesc = $q ? 'نتایج جستجو برای «' . $q . '» در موضوعات، مقالات، گزارش‌ها، کتاب‌ها، دروس و رسانه‌های جامعه‌الهدی.' : 'جستجو در آرشیو محتوایی مدرسه جامعه‌الهدی — موضوعات، مقالات، گزارش‌ها، کتاب‌ها، دروس، ویدیو و صوت.';

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$q = mb_substr($q, 0, 200);
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
      <p class="text-muted mt-2 mb-0">جستجو در اخبار، مقالات، گزارش‌ها، کتب دیجیتال، جلسات درسی و موضوعات حوزوی</p>
    </div>

    <!-- فرم جستجو -->
    <form method="get" class="mb-5" role="search">
      <div class="input-group input-group-lg" style="max-width:640px">
        <input type="text" name="q" class="form-control" placeholder="مثلاً: مهدویت، فلسفه، اصول فقه، کلام..." value="<?= sanitize($q) ?>" autofocus>
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
      <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
        <i class="bi bi-search display-1 text-muted opacity-25 d-block mb-3"></i>
        <h2 class="h4 text-muted">نتیجه‌ای برای «<?= sanitize($q) ?>» یافت نشد.</h2>
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
          if ($p['target'] === 'topic') {
              $resultUrl = topicUrl($p);
              $badge = '<span class="badge badge-article">موضوع</span>';
          } elseif ($p['target'] === 'book') {
              $resultUrl = bookUrl($p);
              $badge = '<span class="badge bg-warning text-dark">کتاب</span>';
          } elseif ($p['target'] === 'lesson') {
              $resultUrl = lessonUrl($p);
              $badge = '<span class="badge bg-success">درس</span>';
          } else {
              $resultUrl = postUrl($p);
              $badge = postTypeBadge($p['post_type']);
          }
      ?>
      <div class="col-md-6 col-lg-4">
        <article class="news-card h-100">
          <div class="news-card-img-wrap">
            <a href="<?= $resultUrl ?>">
              <?php if (!empty($p['featured_image'])): ?>
              <img src="<?= imgUrl($p['featured_image']) ?>" alt="<?= sanitize($p['title']) ?>" class="news-card-img" loading="lazy">
              <?php else: ?>
              <div class="news-card-placeholder">
                <i class="bi <?= $p['target'] === 'topic' ? 'bi-diagram-3' : ($p['target'] === 'book' ? 'bi-book' : ($p['target'] === 'lesson' ? 'bi-mortarboard' : 'bi-file-text')) ?>"></i>
              </div>
              <?php endif; ?>
            </a>
            <div class="news-card-badge"><?= $badge ?></div>
          </div>

          <div class="news-card-body">
            <div class="news-card-meta">
              <span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span>
            </div>

            <h2 class="news-card-title h5">
              <a href="<?= $resultUrl ?>"><?= sanitize($p['title']) ?></a>
            </h2>

            <?php if (!empty($p['summary'])): ?>
            <p class="news-card-summary">
              <?= sanitize(excerpt($p['summary'], 110)) ?>
            </p>
            <?php endif; ?>

            <div class="news-card-footer">
              <a href="<?= $resultUrl ?>" class="btn-read-more">
                مشاهده محتوا <i class="bi bi-arrow-left"></i>
              </a>
            </div>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- صفحه‌بندی -->
    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page, url('search', ['q' => $q, 'page' => '%d'])) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
