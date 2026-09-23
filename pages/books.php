<?php
/**
 * books.php — کتابخانه دیجیتال (مرجع کتب حوزوی و پژوهشی)
 */
$pageTitle = 'کتابخانه دیجیتال';
$pageDesc = 'کتابخانه دیجیتال مدرسه علمیه جامعه‌الهدی — کتب علمی، حوزوی و پژوهشی با دسترسی آزاد، معرفی و دانلود فایل‌های PDF و Word.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$search = trim($_GET['q'] ?? '');
$topicSlug = trim($_GET['topic'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$topic = null;
$topicId = null;
if ($topicSlug) {
    $topic = getTopicBySlug($topicSlug);
    if ($topic) $topicId = (int)$topic['id'];
}

$opts = ['limit' => $limit, 'offset' => $offset];
if ($search) $opts['search'] = $search;
if ($topicId) $opts['topic'] = $topicId;

$books = getBooks($opts);
$total = countBooks(['search' => $search, 'topic' => $topicId]);
$pages = (int)ceil($total / $limit);

$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'کتابخانه', 'url' => url('books')],
];
if ($topic) {
    $breadcrumbs[] = ['name' => $topic['name'], 'url' => url('books', ['topic' => $topic['slug']])];
}
$breadcrumbsJsonLd = breadcrumbsJsonLd($breadcrumbs);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <?php foreach ($breadcrumbs as $i => $cr): $isLast = ($i === count($breadcrumbs) - 1); ?>
        <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>>
          <?php if (!$isLast): ?><a href="<?= sanitize($cr['url']) ?>"><?= sanitize($cr['name']) ?></a><?php else: ?><?= sanitize($cr['name']) ?><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
      <div>
        <span class="jhd-eyebrow">مرجع اسناد و نشر آثار اسلامی</span>
        <h1 class="page-title mb-1">
          <i class="bi bi-book ms-2 text-gold"></i> کتابخانه دیجیتال
        </h1>
        <div class="section-divider"></div>
        <p class="text-muted mt-2 mb-0">
          کتب درسی حوزوی، تألیفات اساتید، آثار پژوهشی و متون کهن با قابلیت دانلود رایگان
        </p>
      </div>
      <?php if ($total > 0): ?>
      <span class="badge bg-secondary px-3 py-2">
        <i class="bi bi-collection ms-1"></i><?= number_format($total) ?> عنوان کتاب
      </span>
      <?php endif; ?>
    </div>

    <!-- Search Form -->
    <form method="get" class="mb-4" role="search">
      <div class="row g-2 align-items-center">
        <div class="col-md-6 col-lg-5">
          <div class="input-group">
            <input type="text" name="q" class="form-control"
                   placeholder="جستجو در عنوان، نویسنده یا توضیحات کتاب..."
                   value="<?= sanitize($search) ?>">
            <?php if ($topicSlug): ?>
            <input type="hidden" name="topic" value="<?= sanitize($topicSlug) ?>">
            <?php endif; ?>
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-search ms-1"></i>جستجو
            </button>
            <?php if ($search || $topicSlug): ?>
            <a href="<?= url('books') ?>" class="btn btn-outline-secondary" title="حذف فیلترها">
              <i class="bi bi-x-lg"></i>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </form>

    <?php if ($topic): ?>
    <div class="mb-4 p-3 rounded" style="background:var(--jhd-surface);border:1px solid var(--jhd-border)">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <span class="small text-muted">فیلتر موضوعی:</span>
          <strong class="ms-1"><?= sanitize($topic['name']) ?></strong>
          <?php if ($topic['description']): ?>
          <span class="text-muted small d-none d-md-inline ms-2">— <?= sanitize(excerpt($topic['description'], 120)) ?></span>
          <?php endif; ?>
        </div>
        <a href="<?= topicUrl($topic) ?>" class="btn btn-sm btn-outline-primary">
          هاب جامع موضوع <i class="bi bi-arrow-left ms-1"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (empty($books)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
      <i class="bi bi-book display-1 text-muted opacity-25 d-block mb-3"></i>
      <h4 class="text-muted"><?= $search || $topicSlug ? 'کتابی با این مشخصات یافت نشد.' : 'هنوز کتابی در این بخش ثبت نشده است.' ?></h4>
      <a href="<?= url('books') ?>" class="btn btn-outline-primary btn-sm mt-2">مشاهده همه کتاب‌ها</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($books as $b):
          $bookUrl = bookUrl($b);
          $cover = $b['cover_image'] ?? '';
      ?>
      <div class="col-6 col-md-4 col-lg-3">
        <article class="book-card h-100">
          <div class="book-card-cover">
            <a href="<?= $bookUrl ?>">
              <?php if ($cover): ?>
              <img src="<?= imgUrl($cover) ?>" alt="جلد <?= sanitize($b['title']) ?>" loading="lazy">
              <?php else: ?>
              <div class="h-100 d-flex align-items-center justify-content-center text-muted"><i class="bi bi-book fs-1"></i></div>
              <?php endif; ?>
            </a>
          </div>

          <h2 class="book-card-title">
            <a href="<?= $bookUrl ?>" class="text-reset text-decoration-none"><?= sanitize($b['title']) ?></a>
          </h2>

          <?php if (!empty($b['author'])): ?>
          <div class="book-card-author">
            <i class="bi bi-person ms-1"></i><?= sanitize($b['author']) ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($b['publish_year']) || !empty($b['pages'])): ?>
          <div class="text-muted d-flex justify-content-center gap-2 mb-2" style="font-size:0.75rem">
            <?php if (!empty($b['publish_year'])): ?><span>سال: <?= sanitize($b['publish_year']) ?></span><?php endif; ?>
            <?php if (!empty($b['pages'])): ?><span>• <?= (int)$b['pages'] ?> صفحه</span><?php endif; ?>
          </div>
          <?php endif; ?>

          <?php $tpcs = getTopicsForBook((int)$b['id']); if (!empty($tpcs)): ?>
          <div class="d-flex justify-content-center flex-wrap gap-1 mb-2">
            <?php foreach (array_slice($tpcs, 0, 2) as $tp): ?>
            <a href="<?= url('books', ['topic' => $tp['slug']]) ?>" class="badge badge-article text-decoration-none" style="font-size:0.68rem">
              #<?= sanitize($tp['name']) ?>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="mt-auto pt-2">
            <a href="<?= $bookUrl ?>" class="btn btn-sm btn-outline-primary w-100">
              مشاهده و دریافت
            </a>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- صفحه‌بندی -->
    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page, url('books', ['q' => $search, 'topic' => $topicSlug, 'page' => '%d'])) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
