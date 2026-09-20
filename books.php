<?php
/**
 * books.php — صفحه کتاب‌ها
 */
$pageTitle = 'کتاب‌ها';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

ensureBooksTable();

$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$opts = ['limit' => $limit, 'offset' => $offset];
if ($search) $opts['search'] = $search;

$books = getBooks($opts);
$total = countBooks($search ? ['search' => $search] : []);
$pages = (int)ceil($total / $limit);
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <div class="container">
    <nav><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
      <li class="breadcrumb-item active">کتاب‌ها</li>
    </ol></nav>
  </div>
</div>

<div class="py-5">
  <div class="container">
    <!-- Page Header -->
    <div class="page-header mb-4">
      <h1 class="page-title">
        <i class="bi bi-book-fill ms-2 text-gold"></i>کتاب‌ها
      </h1>
      <div class="section-divider"></div>
      <?php if ($total > 0): ?>
      <p class="text-muted mt-2"><?= number_format($total) ?> کتاب موجود است</p>
      <?php endif; ?>
    </div>

    <!-- Search -->
    <form method="get" class="mb-5">
      <div class="input-group" style="max-width:440px">
        <input type="text" name="q" class="form-control"
               placeholder="جستجو در نام کتاب..."
               value="<?= sanitize($search) ?>">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-search"></i>
        </button>
        <?php if ($search): ?>
        <a href="<?= siteUrl('books.php') ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x"></i> پاک
        </a>
        <?php endif; ?>
      </div>
      <?php if ($search): ?>
      <div class="mt-2 text-muted small">
        نتایج جستجو برای: <strong><?= sanitize($search) ?></strong>
        — <?= number_format($total) ?> کتاب یافت شد
      </div>
      <?php endif; ?>
    </form>

    <?php if (empty($books)): ?>
    <!-- Empty State -->
    <div class="text-center py-5">
      <i class="bi bi-book display-1 text-muted opacity-25 d-block mb-4"></i>
      <h4 class="text-muted">
        <?= $search ? 'کتابی با این عنوان یافت نشد' : 'هنوز کتابی اضافه نشده است' ?>
      </h4>
      <?php if ($search): ?>
      <a href="<?= siteUrl('books.php') ?>" class="btn btn-outline-primary mt-3">
        <i class="bi bi-arrow-right ms-1"></i>همه کتاب‌ها
      </a>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Books Grid -->
    <div class="row g-4">
      <?php foreach ($books as $b): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="book-card h-100">
          <!-- Cover Image -->
          <div class="book-card-cover">
            <?php if ($b['cover_image']): ?>
            <img src="<?= imgUrl($b['cover_image']) ?>"
                 alt="<?= sanitize($b['title']) ?>"
                 loading="lazy" class="book-cover-img">
            <?php else: ?>
            <div class="book-cover-placeholder">
              <i class="bi bi-book"></i>
            </div>
            <?php endif; ?>
          </div>
          <!-- Book Info -->
          <div class="book-card-body">
            <h3 class="book-title"><a href="<?= siteUrl('book.php?id='.(int)$b['id']) ?>"><?= sanitize($b['title']) ?></a></h3>
            <?php if ($b['description']): ?>
            <p class="book-desc"><?= sanitize(excerpt($b['description'], 100)) ?></p>
            <?php endif; ?>
            <!-- Download Buttons -->
            <div class="book-downloads mt-auto">
              <?php if ($b['pdf_file']): ?>
              <a href="<?= siteUrl('book.php?id='.(int)$b['id'].'&download=pdf') ?>"
                 class="btn btn-danger btn-sm w-100 mb-2"
                 download title="دانلود PDF">
                <i class="bi bi-file-pdf ms-1"></i>دانلود PDF
              </a>
              <?php else: ?>
              <button class="btn btn-outline-secondary btn-sm w-100 mb-2" disabled>
                <i class="bi bi-file-pdf ms-1"></i>PDF موجود نیست
              </button>
              <?php endif; ?>
              <?php if ($b['word_file']): ?>
              <a href="<?= siteUrl('book.php?id='.(int)$b['id'].'&download=word') ?>"
                 class="btn btn-primary btn-sm w-100"
                 download title="دانلود Word">
                <i class="bi bi-file-word ms-1"></i>دانلود Word
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page,
          siteUrl('books.php') . '?q=' . urlencode($search) . '&page=%d') ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
