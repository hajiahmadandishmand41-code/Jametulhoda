<?php
/**
 * articles.php — مقالات — طراحی بهبودیافته
 */
$pageTitle = 'مقالات';
require_once __DIR__ . '/includes/header.php';

$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = POSTS_PER_PAGE;
$opts   = ['type' => 'article', 'limit' => $limit, 'offset' => ($page - 1) * $limit];
if ($search) $opts['search'] = $search;
$posts = getPosts($opts);
$total = countPosts(array_merge(['type' => 'article'], $search ? ['search' => $search] : []));
$pages = (int)ceil($total / $limit);
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <div class="container">
    <nav><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
      <li class="breadcrumb-item active">مقالات</li>
    </ol></nav>
  </div>
</div>

<main class="py-5">
  <div class="container">
    <!-- Page Header -->
    <div class="page-header mb-4">
      <h1 class="page-title">
        <i class="bi bi-file-text-fill ms-2 text-gold"></i>مقالات
      </h1>
      <div class="section-divider"></div>
      <?php if ($total > 0): ?>
      <p class="text-muted mt-2"><?= number_format($total) ?> مقاله موجود است</p>
      <?php endif; ?>
    </div>

    <!-- Search -->
    <form method="get" class="mb-5">
      <div class="input-group" style="max-width:440px">
        <input type="text" name="q" class="form-control"
               placeholder="جستجو در مقالات..."
               value="<?= sanitize($search) ?>">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-search"></i>
        </button>
        <?php if ($search): ?>
        <a href="<?= siteUrl('articles.php') ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x"></i>
        </a>
        <?php endif; ?>
      </div>
      <?php if ($search): ?>
      <div class="mt-2 text-muted small">
        نتایج جستجو برای: <strong><?= sanitize($search) ?></strong>
        — <?= number_format($total) ?> مقاله
      </div>
      <?php endif; ?>
    </form>

    <?php if (empty($posts)): ?>
    <div class="text-center py-5">
      <i class="bi bi-file-text display-1 text-muted opacity-25 d-block mb-4"></i>
      <h4 class="text-muted">
        <?= $search ? 'مقاله‌ای با این عنوان یافت نشد' : 'هنوز مقاله‌ای منتشر نشده است' ?>
      </h4>
      <?php if ($search): ?>
      <a href="<?= siteUrl('articles.php') ?>" class="btn btn-outline-primary mt-3">
        <i class="bi bi-arrow-right ms-1"></i>همه مقالات
      </a>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($posts as $p): ?>
      <div class="col-md-6 col-lg-4">
        <article class="article-card h-100">
          <!-- Featured Image -->
          <a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>" class="article-card-img-link">
            <?php if ($p['featured_image']): ?>
            <img src="<?= imgUrl($p['featured_image']) ?>"
                 alt="<?= sanitize($p['title']) ?>"
                 class="article-card-img" loading="lazy">
            <?php else: ?>
            <div class="article-card-img-placeholder">
              <i class="bi bi-file-text"></i>
            </div>
            <?php endif; ?>
          </a>
          <!-- Body -->
          <div class="article-card-body">
            <!-- Meta -->
            <div class="article-card-meta">
              <span class="article-date">
                <i class="bi bi-calendar3 ms-1"></i>
                <?= persianDate($p['published_at'] ?? $p['created_at']) ?>
              </span>
              <?php if (!empty($p['cat_name'])): ?>
              <span class="article-cat badge"><?= sanitize($p['cat_name']) ?></span>
              <?php endif; ?>
            </div>
            <!-- Title -->
            <h3 class="article-card-title">
              <a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>">
                <?= sanitize($p['title']) ?>
              </a>
            </h3>
            <!-- Summary -->
            <?php if ($p['summary']): ?>
            <p class="article-card-summary">
              <?= sanitize(excerpt($p['summary'], 130)) ?>
            </p>
            <?php endif; ?>
            <!-- Footer -->
            <div class="article-card-footer">
              <a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>"
                 class="btn-read-more">
                ادامه مطلب <i class="bi bi-arrow-left ms-1"></i>
              </a>
            </div>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="mt-5">
      <?= paginate($total, $limit, $page,
          siteUrl('articles.php') . '?q=' . urlencode($search) . '&page=%d') ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
