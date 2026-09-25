<?php
/**
 * events.php — رویدادها، برنامه‌های آموزشی و فعالیت‌های مذهبی جامعه‌الهدی
 */
$pageTitle = 'رویدادها و برنامه‌ها';
$pageDesc = 'رویدادها، مناسبت‌های مذهبی، نشست‌های علمی و دوره‌های آموزشی مدرسه علمیه جامعه‌الهدی';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$search = trim($_GET['q'] ?? '');
$type   = trim($_GET['type'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$db = getDB();
$where = ["p.status = 'published'"];
$params = [];

if ($type && in_array($type, ['program', 'religious', 'announcement'], true)) {
    $where[] = "p.post_type = ?";
    $params[] = $type;
} else {
    $where[] = "p.post_type IN ('program', 'religious', 'announcement')";
}

if ($search) {
    $where[] = "(p.title ILIKE ? OR p.summary ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM posts p WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Fetch
$queryStmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereClause ORDER BY p.published_at DESC LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$queryStmt->execute($params);
$events = $queryStmt->fetchAll();

$pages = (int)ceil($total / $limit);

$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'رویدادها و برنامه‌ها', 'url' => url('events')]
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
        <span class="jhd-eyebrow">تقویم حوزه و مناسبت‌ها</span>
        <h1 class="page-title mb-1">
          <i class="bi bi-calendar-event ms-2 text-gold"></i> رویدادها و برنامه‌ها
        </h1>
        <div class="section-divider"></div>
        <p class="text-muted mt-2 mb-0">
          اطلاع‌رسانی مناسبت‌های مذهبی، جشن‌ها و سوگواری‌ها، نشست‌های تخصصی و ثبت‌نام دوره‌ها
        </p>
      </div>
      <?php if ($total > 0): ?>
      <span class="badge bg-secondary px-3 py-2">
        <i class="bi bi-calendar-check ms-1"></i><?= number_format($total) ?> رویداد
      </span>
      <?php endif; ?>
    </div>

    <!-- فیلترها و جستجو -->
    <form method="get" class="mb-4" role="search">
    <?= queryKeepFields() ?>
      <div class="row g-2 align-items-center">
        <div class="col-md-5">
          <input type="search" name="q" class="form-control" aria-label="جستجو در رویدادها" placeholder="جستجو در رویدادها..." value="<?= sanitize($search) ?>">
        </div>
        <div class="col-md-4">
          <select name="type" class="form-select" aria-label="فیلتر نوع رویداد" onchange="this.form.submit()">
            <option value="">همه انواع برنامه‌ها</option>
            <option value="program" <?= $type === 'program' ? 'selected' : '' ?>>برنامه‌های رسمی حوزه</option>
            <option value="religious" <?= $type === 'religious' ? 'selected' : '' ?>>مناسبت‌ها و فعالیت‌های دینی</option>
            <option value="announcement" <?= $type === 'announcement' ? 'selected' : '' ?>>اطلاعیه‌ها و ثبت‌نام</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search ms-1"></i>اعمال</button>
          <?php if ($search || $type): ?>
          <a href="<?= url('events') ?>" class="btn btn-outline-secondary" title="حذف فیلترها"><i class="bi bi-x-lg"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <?php if (empty($events)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
      <i class="bi bi-calendar-x display-1 text-muted opacity-25 d-block mb-3"></i>
      <h4 class="text-muted">رویدادی مطابق با جستجوی شما یافت نشد.</h4>
      <a href="<?= url('events') ?>" class="btn btn-outline-primary btn-sm mt-2">همه رویدادها</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($events as $ev): $evUrl = postUrl($ev); ?>
      <div class="col-md-6 col-lg-4">
        <article class="news-card h-100">
          <!-- Like every other card list (docs/UI_CONTRACT.md): the image slot is
               always rendered, with an icon placeholder when there is no image. -->
          <div class="news-card-img-wrap position-relative">
            <a href="<?= $evUrl ?>" tabindex="-1" aria-hidden="true">
              <?php if (!empty($ev['featured_image'])): ?>
              <img src="<?= imgUrl($ev['featured_image']) ?>" alt="<?= sanitize($ev['title']) ?>" class="news-card-img" loading="lazy" decoding="async">
              <?php else: ?>
              <div class="news-card-img-placeholder"><i class="bi bi-calendar-event"></i></div>
              <?php endif; ?>
            </a>
            <div class="news-card-badge"><?= postTypeBadge($ev['post_type']) ?></div>
          </div>

          <div class="news-card-body">
            <div class="news-card-meta">
              <span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($ev['published_at'] ?? $ev['created_at']) ?></span>
              <?php if (!empty($ev['category_name'])): ?>
              <span>• <?= sanitize($ev['category_name']) ?></span>
              <?php endif; ?>
            </div>

            <h2 class="news-card-title h5">
              <a href="<?= $evUrl ?>"><?= sanitize($ev['title']) ?></a>
            </h2>

            <?php if (!empty($ev['summary'])): ?>
            <p class="news-card-summary">
              <?= sanitize(excerpt($ev['summary'], 110)) ?>
            </p>
            <?php endif; ?>

            <div class="news-card-footer">
              <a href="<?= $evUrl ?>" class="btn-read-more">
                مشاهده جزییات برنامه <i class="bi bi-arrow-left"></i>
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
      <?= paginate($total, $limit, $page, url('events', ['q' => $search, 'type' => $type, 'page' => '%d'])) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
