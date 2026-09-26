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
        <?= jhd_page_head([
    'eyebrow' => 'اندیشه و پژوهش',
    'icon' => 'bi-file-earmark-richtext',
    'title' => 'مقالات علمی',
    'lead' => 'مقالات، یادداشت‌های علمی و پژوهش‌های اعضای مدرسه در معارف اسلامی',
]) ?>
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
    <div class="jhd-empty-state">
      <h4>
        <?= $search ? 'مقاله‌ای مطابق با جستجوی شما یافت نشد' : 'هنوز مقاله‌ای در این بخش ثبت نشده است' ?>
      </h4>
      <p class="text-muted small">می‌توانید عبارت دیگری را جستجو کرده یا فیلترها را حذف کنید.</p>
      <a href="<?= url('articles') ?>" class="btn btn-outline-primary btn-sm mt-2">
        <i class="bi bi-arrow-right ms-1"></i>مشاهده همه مقالات
      </a>
    </div>
    <?php else: ?>
    <?= renderCategoryChips(['article'], url('articles'), 'همه مقالات') ?>
    <div class="row g-4">
      <?php foreach ($posts as $k => $p):
        echo renderPostCard($p, ['featured' => $k === 0 && empty($search), 'cta' => 'مطالعه مقاله', 'excerpt' => 120]);
      endforeach; ?>
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
