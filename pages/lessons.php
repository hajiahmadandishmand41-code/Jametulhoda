<?php
/**
 * lessons.php — فهرست دروس با ساختار مجموعه → جلد → درس
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$collectionSlug = trim($_GET['collection'] ?? '');
$volumeSlug = trim($_GET['volume'] ?? '');
$search = trim($_GET['q'] ?? '');
$level = trim($_GET['level'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$activeCollection = $collectionSlug ? getLessonCollectionBySlug($collectionSlug) : null;
$activeVolume = null;
if ($activeCollection && $volumeSlug) {
    foreach (getLessonVolumes((int)$activeCollection['id']) as $v) {
        if ($v['slug'] === $volumeSlug) { $activeVolume = $v; break; }
    }
}

if (($collectionSlug !== '' && !$activeCollection) || ($activeCollection && $volumeSlug !== '' && !$activeVolume)) {
    http_response_code(404);
    $pageTitle = 'مجموعه یافت نشد';
    $pageDesc = 'مجموعه یا بخش درسی مورد نظر یافت نشد';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5 text-center"><h1 class="h3 fw-bold">مجموعه یا بخش درسی یافت نشد</h1><p class="text-muted">ممکن است حذف شده یا نشانی نادرست باشد.</p><a href="' . url('lessons') . '" class="btn btn-primary mt-3">مشاهده همه دروس</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = $activeCollection ? $activeCollection['title'] : 'دروس حوزوی';
$pageDesc = $activeCollection && $activeCollection['description'] ? excerpt($activeCollection['description'], 160) : 'مجموعه دروس حوزوی به‌صورت درس‌به‌درس با جلسات صوتی و موضوعات مرتبط.';
if ($activeCollection) {
    $canonicalOverride = collectionUrl($activeCollection, $activeVolume ?: null);
}

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'دروس حوزوی', 'url' => url('lessons')],
];
if ($activeCollection) {
    $breadcrumbs[] = ['name' => $activeCollection['title'], 'url' => collectionUrl($activeCollection)];
    if ($activeVolume) {
        $breadcrumbs[] = ['name' => $activeVolume['title'], 'url' => collectionUrl($activeCollection, $activeVolume)];
    }
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
    <div class="jhd-page-heading d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
      <div>
        <span class="jhd-eyebrow">مدرسه علمیه و آموزش مجازی معارف</span>
        <h1 class="page-title mb-1">
          <i class="bi bi-mortarboard-fill ms-2 text-gold"></i>
          <?php if ($activeVolume): ?>
            <?= sanitize($activeVolume['title']) ?> — <?= sanitize($activeCollection['title']) ?>
          <?php elseif ($activeCollection): ?>
            <?= sanitize($activeCollection['title']) ?>
          <?php else: ?>
            دروس و دوره‌های حوزوی
          <?php endif; ?>
        </h1>
        <div class="section-divider"></div>
        <?php if ($activeCollection && $activeCollection['description']): ?>
        <p class="text-muted mt-2 mb-0"><?= sanitize($activeCollection['description']) ?></p>
        <?php else: ?>
        <p class="text-muted mt-2 mb-0">دروس سطح مقدمات، سطوح عالی و خارج در رشته‌های فقه، اصول، کلام، منطق و عقاید</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$activeCollection): ?>
    <!-- ۱. فهرست مجموعه‌های درسی -->
    <?php $collections = getLessonCollections(['active' => 1]); if (!empty($collections)): ?>
    <div class="mb-5">
      <h2 class="h5 fw-bold mb-3"><i class="bi bi-journals ms-1 text-gold"></i> دوره‌ها و مجموعه‌های درسی</h2>
      <div class="row g-4">
        <?php foreach ($collections as $col):
            $vols = getLessonVolumes((int)$col['id']);
            $cnt = count(getLessonsByCollection((int)$col['id']));
            $colUrl = collectionUrl($col);
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-3">
            <?php if (!empty($col['cover_image'])): ?>
            <img src="<?= imgUrl($col['cover_image']) ?>" alt="<?= sanitize($col['title']) ?>" style="height:170px;object-fit:cover;border-radius:8px" class="w-100 mb-3" loading="lazy">
            <?php endif; ?>
            <h3 class="h5 fw-bold mb-2">
              <a href="<?= $colUrl ?>" class="text-reset text-decoration-none"><?= sanitize($col['title']) ?></a>
            </h3>
            <?php if (!empty($col['description'])): ?>
            <p class="text-muted small mb-3"><?= sanitize(excerpt($col['description'], 110)) ?></p>
            <?php endif; ?>

            <?php if (!empty($vols)): ?>
            <div class="d-flex flex-wrap gap-1 mb-3">
              <?php foreach ($vols as $v): ?>
              <a href="<?= collectionUrl($col, $v) ?>" class="badge badge-article text-decoration-none" style="font-size:0.72rem">
                <?= sanitize($v['title']) ?>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="mt-auto d-flex align-items-center justify-content-between pt-2 border-top">
              <span class="text-muted small"><i class="bi bi-collection-play ms-1"></i><?= number_format($cnt) ?> درس</span>
              <a href="<?= $colUrl ?>" class="btn btn-outline-primary btn-sm">
                مشاهده دوره <i class="bi bi-arrow-left ms-1"></i>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ۲. آخرین دروس منتشرشده عمومی -->
    <?php
    $where = ["l.status = 'published'"];
    $params = [];
    if ($search) {
        $where[] = "(l.title ILIKE ? OR l.content ILIKE ? OR l.summary ILIKE ? OR l.teacher ILIKE ?)";
        $s = "%$search%";
        $params = array_merge($params, [$s, $s, $s, $s]);
    }
    if ($level) {
        $where[] = "l.level = ?";
        $params[] = $level;
    }
    $whereStr = implode(' AND ', $where);
    try {
        $cntStmt = getDB()->prepare("SELECT COUNT(*) FROM lessons l WHERE $whereStr");
        $cntStmt->execute($params);
        $total = (int)$cntStmt->fetchColumn();
        $pages = (int)ceil($total / $limit);

        $stmt = getDB()->prepare("SELECT l.*, lc.title AS collection_title, lv.title AS volume_title FROM lessons l LEFT JOIN lesson_collections lc ON lc.id = l.collection_id LEFT JOIN lesson_volumes lv ON lv.id = l.volume_id WHERE $whereStr ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $lessons = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $lessons = [];
        $total = 0;
        $pages = 1;
    }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h2 class="h5 fw-bold mb-0">جدیدترین جلسات درسی</h2>
      <form method="get" class="d-flex gap-2 flex-wrap">
        <input type="search" name="q" class="form-control form-control-sm" aria-label="جستجو در درس‌ها" placeholder="جستجو در درس‌ها..." value="<?= sanitize($search) ?>" style="max-width:200px">
        <select name="level" class="form-select form-select-sm" aria-label="فیلتر سطح درس" style="max-width:130px">
          <option value="">همه سطوح</option>
          <option value="beginner" <?= $level === 'beginner' ? 'selected' : '' ?>>مقدماتی</option>
          <option value="intermediate" <?= $level === 'intermediate' ? 'selected' : '' ?>>متوسط</option>
          <option value="advanced" <?= $level === 'advanced' ? 'selected' : '' ?>>پیشرفته</option>
        </select>
        <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <?php if (empty($lessons)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
      <i class="bi bi-mortarboard display-1 text-muted opacity-25 d-block mb-3"></i>
      <h4 class="text-muted">درسی مطابق با مشخصات واردشده یافت نشد.</h4>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($lessons as $ls): $lsUrl = lessonUrl($ls); ?>
      <div class="col-md-6 col-lg-3">
        <div class="lesson-card h-100">
          <?php if (!empty($ls['collection_title'])): ?>
          <span class="badge bg-secondary align-self-start mb-2" style="font-size:0.72rem">
            <?= sanitize($ls['collection_title']) ?>
          </span>
          <?php endif; ?>

          <h3 class="lesson-card-title">
            <a href="<?= $lsUrl ?>"><?= sanitize($ls['title']) ?></a>
          </h3>

          <?php if (!empty($ls['teacher'])): ?>
          <div class="lesson-card-teacher">
            <i class="bi bi-person-video3"></i>استاد: <?= sanitize($ls['teacher']) ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($ls['summary'])): ?>
          <p class="text-muted small mb-3"><?= sanitize(excerpt($ls['summary'], 80)) ?></p>
          <?php endif; ?>

          <a href="<?= $lsUrl ?>" class="btn btn-sm btn-outline-primary w-100 mt-auto">
            جلسات و صوت درس <i class="bi bi-arrow-left ms-1"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page, url('lessons', ['q' => $search, 'level' => $level, 'page' => '%d'])) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php else: ?>
    <!-- ۳. نمایش یک دوره یا مجموعه خاص -->
    <?php
    $volumes = getLessonVolumes((int)$activeCollection['id']);
    if (!empty($volumes) && !$activeVolume):
    ?>
    <div class="mb-5">
      <h2 class="h5 fw-bold mb-3">بخش‌ها و جلدهای این دوره</h2>
      <div class="row g-3">
        <?php foreach ($volumes as $vol):
            $vcount = count(getLessonsByCollection((int)$activeCollection['id'], (int)$vol['id']));
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 p-3">
            <h3 class="h6 fw-bold mb-1">
              <a href="<?= collectionUrl($activeCollection, $vol) ?>" class="text-reset text-decoration-none"><?= sanitize($vol['title']) ?></a>
            </h3>
            <?php if (!empty($vol['description'])): ?>
            <p class="text-muted small mb-2"><?= sanitize(excerpt($vol['description'], 90)) ?></p>
            <?php endif; ?>
            <div class="mt-auto d-flex align-items-center justify-content-between pt-2 border-top">
              <span class="text-muted small"><?= $vcount ?> جلسه</span>
              <a href="<?= collectionUrl($activeCollection, $vol) ?>" class="btn btn-sm btn-outline-primary">
                مشاهده جلسات <i class="bi bi-arrow-left ms-1"></i>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php
    $where = ["l.status = 'published'", "l.collection_id = ?"];
    $params = [(int)$activeCollection['id']];
    if ($activeVolume) {
        $where[] = "l.volume_id = ?";
        $params[] = (int)$activeVolume['id'];
    }
    if ($search) {
        $where[] = "(l.title ILIKE ? OR l.summary ILIKE ?)";
        $s = "%$search%";
        $params[] = $s;
        $params[] = $s;
    }
    $whereStr = implode(' AND ', $where);
    try {
        $cntStmt = getDB()->prepare("SELECT COUNT(*) FROM lessons l WHERE $whereStr");
        $cntStmt->execute($params);
        $total = (int)$cntStmt->fetchColumn();
        $pages = (int)ceil($total / $limit);

        $stmt = getDB()->prepare("SELECT l.* FROM lessons l WHERE $whereStr ORDER BY COALESCE(l.lesson_number, 9999) ASC, l.sort_order ASC, l.id ASC LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $lessons = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $lessons = [];
        $total = 0;
        $pages = 1;
    }
    ?>

    <?php if (empty($lessons)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
      <p class="text-muted mb-0">درسی در این بخش یافت نشد.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($lessons as $ls): $lsUrl = lessonUrl($ls); ?>
      <div class="col-md-6 col-lg-4">
        <div class="lesson-card h-100">
          <?php if (!empty($ls['lesson_number'])): ?>
          <span class="badge bg-secondary align-self-start mb-2">جلسه <?= (int)$ls['lesson_number'] ?></span>
          <?php endif; ?>

          <h3 class="lesson-card-title">
            <a href="<?= $lsUrl ?>"><?= sanitize($ls['title']) ?></a>
          </h3>

          <?php if (!empty($ls['teacher'])): ?>
          <div class="lesson-card-teacher">
            <i class="bi bi-person ms-1"></i>استاد: <?= sanitize($ls['teacher']) ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($ls['summary'])): ?>
          <p class="text-muted small mb-3"><?= sanitize(excerpt($ls['summary'], 100)) ?></p>
          <?php endif; ?>

          <a href="<?= $lsUrl ?>" class="btn btn-outline-primary btn-sm w-100 mt-auto">
            ورود به درس و صوت <i class="bi bi-arrow-left ms-1"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="mt-4">
      <?= paginate($total, $limit, $page, collectionUrl($activeCollection, $activeVolume ?: null) . '?page=%d') ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="mt-4">
      <a href="<?= url('lessons') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right ms-1"></i> بازگشت به همه مجموعه‌ها
      </a>
    </div>

    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
