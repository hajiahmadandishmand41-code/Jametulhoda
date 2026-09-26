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
    <div class="jhd-page-heading d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
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
    <?= queryKeepFields() ?>
      <div class="row g-2 align-items-center">
        <div class="col-md-6 col-lg-5">
          <div class="input-group">
            <input type="search" name="q" class="form-control" aria-label="جستجو در کتابخانه"
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
      <?= renderBookCard($b, ['col' => 'col-6 col-md-4 col-lg-3', 'cta' => 'مشاهده و دریافت']) ?>
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
