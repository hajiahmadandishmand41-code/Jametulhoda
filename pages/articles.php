<?php
/**
 * articles.php — فهرست و آرشیو مقالات علمی و یادداشت‌های پژوهشی
 */
$pageTitle = 'مقالات علمی';
$pageDesc = 'مجموعه مقالات علمی، کلامی، فقهی و معرفتی اساتید و پژوهشگران مدرسه علمیه جامعه‌الهدی.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$search = trim($_GET['q'] ?? '');
$topicSlug = trim($_GET['topic'] ?? '');
$topicId = null;
if ($topicSlug) {
    $t = getTopicBySlug($topicSlug);
    if ($t) $topicId = (int)$t['id'];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$opts = ['type' => 'article', 'limit' => $limit, 'offset' => $offset];
if ($search) $opts['search'] = $search;
if ($topicId) $opts['topic'] = $topicId;

$posts = getPosts($opts);
$total = countPosts(['type' => 'article'] + ($search ? ['search' => $search] : []) + ($topicId ? ['topic' => $topicId] : []));
$pages = (int)ceil($total / $limit);

$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'مقالات علمی', 'url' => url('articles')]
];
if ($topicId && !empty($t)) {
    $breadcrumbs[] = ['name' => $t['name'], 'url' => url('articles', ['topic' => $t['slug']])];
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
    <!-- Page Header -->
    <div class="jhd-page-heading d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
      <div>
        <span class="jhd-eyebrow">اندیشه و پژوهش‌های دینی</span>
        <h1 class="page-title mb-1">
          <i class="bi bi-file-earmark-richtext ms-2 text-gold"></i> مقالات علمی و یادداشت‌ها
        </h1>
        <div class="section-divider"></div>
        <p class="text-muted mt-2 mb-0">
          پژوهش‌های نوین حوزوی در حوزه‌های فقه، کلام، فلسفه، تفسیر، تاریخ و جامعه‌شناسی دینی
        </p>
      </div>
      <?php if ($total > 0): ?>
      <span class="badge bg-secondary px-3 py-2">
        <i class="bi bi-collection ms-1"></i><?= number_format($total) ?> مقاله
      </span>
      <?php endif; ?>
    </div>

    <!-- Search & Filter Form -->
    <form method="get" class="mb-4" role="search">
    <?= queryKeepFields() ?>
      <div class="row g-2 align-items-center">
        <div class="col-md-6 col-lg-5">
          <div class="input-group">
            <input type="search" name="q" class="form-control" aria-label="جستجو در عنوان یا متن مقالات"
                   placeholder="جستجو در عنوان یا متن مقالات..."
                   value="<?= sanitize($search) ?>">
            <?php if ($topicSlug): ?>
            <input type="hidden" name="topic" value="<?= sanitize($topicSlug) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-search ms-1"></i>جستجو
            </button>
            <?php if ($search || $topicSlug): ?>
            <a href="<?= url('articles') ?>" class="btn btn-outline-secondary" title="حذف فیلترها">
              <i class="bi bi-x-lg"></i>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php if ($search || $topicSlug): ?>
      <div class="mt-2 text-muted small">
        فیلتر فعال:
        <?php if ($search): ?>«<strong><?= sanitize($search) ?></strong>»<?php endif; ?>
        <?php if ($topicSlug && !empty($t)): ?>در موضوع «<strong><?= sanitize($t['name']) ?></strong>»<?php endif; ?>
        — <?= number_format($total) ?> مورد یافت شد
      </div>
      <?php endif; ?>
    </form>

    <?php if (empty($posts)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
      <i class="bi bi-file-text display-1 text-muted opacity-25 d-block mb-3"></i>
      <h4 class="text-muted">
        <?= $search ? 'مقاله‌ای مطابق با جستجوی شما یافت نشد' : 'هنوز مقاله‌ای در این بخش ثبت نشده است' ?>
      </h4>
      <p class="text-muted small">می‌توانید عبارت دیگری را جستجو کرده یا فیلترها را حذف کنید.</p>
      <a href="<?= url('articles') ?>" class="btn btn-outline-primary btn-sm mt-2">
        <i class="bi bi-arrow-right ms-1"></i>مشاهده همه مقالات
      </a>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($posts as $p): $pUrl = postUrl($p); ?>
      <div class="col-md-6 col-lg-4">
        <article class="article-card h-100">
          <div class="article-card-header">
            <?php if (!empty($p['author_name'])): ?>
            <span class="article-card-author"><i class="bi bi-person ms-1"></i><?= sanitize($p['author_name']) ?></span>
            <?php endif; ?>
            <span class="article-card-date">
              <i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?>
            </span>
          </div>

          <h2 class="article-card-title h5">
            <a href="<?= $pUrl ?>"><?= sanitize($p['title']) ?></a>
          </h2>

          <p class="article-card-summary">
            <?= sanitize(excerpt($p['summary'] ?? $p['content'], 120)) ?>
          </p>

          <?php $pt = getTopicsForPost((int)$p['id']); if (!empty($pt)): ?>
          <div class="d-flex flex-wrap gap-1 mb-3">
            <?php foreach (array_slice($pt, 0, 2) as $tp): ?>
            <a href="<?= topicUrl($tp) ?>" class="badge badge-article text-decoration-none">
              #<?= sanitize($tp['name']) ?>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="article-card-footer">
            <span class="badge badge-article">مقاله</span>
            <a href="<?= $pUrl ?>" class="btn-read-more">
              مطالعه مقاله <i class="bi bi-arrow-left"></i>
            </a>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- صفحه‌بندی -->
    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page, url('articles', ['q' => $search, 'topic' => $topicSlug, 'page' => '%d'])) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
