<?php
/**
 * research.php — پژوهش‌ها و دستاوردهای علمی مدرسه علمیه جامعه‌الهدی
 */
$pageTitle = 'پژوهش‌های علمی';
$pageDesc = 'پژوهش‌های علمی و دینی مدرسه جامعه‌الهدی — مطالب تحقیقی با منابع، چکیده و موضوعات مرتبط.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$opts = ['type' => 'research', 'limit' => $limit, 'offset' => $offset];
if ($search) $opts['search'] = $search;

$posts = getPosts($opts);
$total = countPosts(array_merge(['type' => 'research'], $search ? ['search' => $search] : []));
$pages = (int)ceil($total / $limit);

$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'پژوهش‌های علمی', 'url' => url('research')]
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
      <div>
        <span class="jhd-eyebrow">تحقیقات تخصصی و مستند</span>
        <h1 class="page-title mb-1">
          <i class="bi bi-journal-richtext ms-2 text-gold"></i> پژوهش‌های علمی و دینی
        </h1>
        <div class="section-divider"></div>
        <p class="text-muted mt-2 mb-0">
          تحقیقات بنیادی و کاربردی اساتید و طلاب جامعه‌الهدی با ارجاعات معتبر روایی و عقلی
        </p>
      </div>
      <?php if ($total > 0): ?>
      <span class="badge bg-secondary px-3 py-2">
        <i class="bi bi-journal-check ms-1"></i><?= number_format($total) ?> عنوان پژوهش
      </span>
      <?php endif; ?>
    </div>

    <!-- Search Form -->
    <form method="get" class="mb-4" role="search">
      <div class="input-group" style="max-width:480px">
        <input type="search" name="q" class="form-control" aria-label="جستجو در پژوهش‌ها"
               placeholder="جستجو در عنوان یا متن پژوهش‌ها..."
               value="<?= sanitize($search) ?>">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-search ms-1"></i>جستجو
        </button>
        <?php if ($search): ?>
        <a href="<?= url('research') ?>" class="btn btn-outline-secondary" title="حذف فیلتر">
          <i class="bi bi-x-lg"></i>
        </a>
        <?php endif; ?>
      </div>
    </form>

    <?php if (empty($posts)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
      <i class="bi bi-journal-x display-1 text-muted opacity-25 d-block mb-3"></i>
      <h4 class="text-muted">
        <?= $search ? 'پژوهشی مطابق با عبارت جستجو یافت نشد' : 'هنوز پژوهشی در این بخش منتشر نشده است' ?>
      </h4>
      <a href="<?= url('research') ?>" class="btn btn-outline-primary btn-sm mt-2">
        مشاهده همه پژوهش‌ها
      </a>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($posts as $p): $pUrl = postUrl($p); ?>
      <div class="col-md-6 col-lg-4">
        <article class="article-card h-100">
          <div class="article-card-header">
            <span class="article-card-author">
              <i class="bi bi-person ms-1"></i><?= sanitize($p['author_name'] ?? 'مرکز پژوهش حوزه') ?>
            </span>
            <span class="article-card-date">
              <i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?>
            </span>
          </div>

          <h2 class="article-card-title h5">
            <a href="<?= $pUrl ?>"><?= sanitize($p['title']) ?></a>
          </h2>

          <p class="article-card-summary">
            <?= sanitize(excerpt($p['summary'] ?? $p['content'], 130)) ?>
          </p>

          <div class="article-card-footer">
            <span class="badge badge-article">پژوهش علمی</span>
            <a href="<?= $pUrl ?>" class="btn-read-more">
              مطالعه متن تحقیق <i class="bi bi-arrow-left"></i>
            </a>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- صفحه‌بندی -->
    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page, url('research', ['q' => $search, 'page' => '%d'])) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
