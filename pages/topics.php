<?php
/**
 * topics.php — اطلس و فهرست جامع موضوعات (ستون فقرات سامانه معارف جامعه‌الهدی)
 */
$pageTitle = 'اطلس موضوعات';
$pageDesc = 'منظومه و موضوعات معارف اسلامی جامعه‌الهدی — هر موضوع مرکز گردآوری مقالات، اخبار، گزارش‌ها، کتاب‌ها، دروس و رسانه‌های تخصصی است.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$tree = getTopicTree();
$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => url()],
    ['name' => 'موضوعات', 'url' => url('topics')]
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
        <span class="jhd-eyebrow">منظومه فکری و درخت‌واره علوم اسلامی</span>
        <h1 class="page-title mb-1">
          <i class="bi bi-diagram-3 ms-2 text-gold"></i> اطلس جامع موضوعات
        </h1>
        <div class="section-divider"></div>
        <p class="text-muted mt-2 mb-0">
          دسترسی یکپارچه به اخبار، مقالات، گزارش‌ها، کتب، دروس و رسانه‌ها بر پایه شاخه‌های تخصصی
        </p>
      </div>
      <?php if (!empty($tree)): ?>
      <span class="badge bg-secondary px-3 py-2">
        <i class="bi bi-folder2-open ms-1"></i><?= number_format(count($tree)) ?> موضوع اصلی
      </span>
      <?php endif; ?>
    </div>

    <?php if (empty($tree)): ?>
    <div class="text-center py-5 border rounded" style="background:var(--jhd-surface)">
      <i class="bi bi-folder-x display-1 text-muted opacity-25 d-block mb-3"></i>
      <h4 class="text-muted">موضوعی در این بخش ثبت نشده است.</h4>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($tree as $top):
          $children = $top['children'] ?? [];
          $cnt = countPostsByTopic((int)$top['id']);
          $lessonCnt = count(getLessonsByTopic((int)$top['id'], 100));
          $bookCnt = count(getBooksByTopic((int)$top['id'], 100));
          $topUrl = topicUrl($top);
      ?>
      <div class="col-lg-6">
        <div class="card h-100 p-3">
          <div class="d-flex gap-3 align-items-start">
            <?php if (!empty($top['cover_image'])): ?>
            <img src="<?= imgUrl($top['cover_image']) ?>" alt="<?= sanitize($top['name']) ?>"
                 style="width:84px;height:84px;object-fit:cover;border-radius:10px;border:1px solid var(--jhd-border)" loading="lazy">
            <?php else: ?>
            <div class="topic-card-icon" style="width:72px;height:72px;font-size:1.8rem;border-radius:12px;margin-bottom:0">
              <i class="bi bi-folder-fill"></i>
            </div>
            <?php endif; ?>

            <div class="flex-grow-1">
              <h2 class="h5 mb-1 fw-bold">
                <a href="<?= $topUrl ?>" class="text-reset text-decoration-none"><?= sanitize($top['name']) ?></a>
              </h2>
              <?php if ($top['intro'] ?: $top['description']): ?>
              <p class="text-muted small mb-2 line-clamp-2">
                <?= sanitize(excerpt($top['intro'] ?: $top['description'], 110)) ?>
              </p>
              <?php endif; ?>
              <div class="text-muted small d-flex flex-wrap gap-2">
                <span><i class="bi bi-file-earmark-text ms-1"></i><?= number_format($cnt) ?> مطلب</span>
                <span>•</span>
                <span><i class="bi bi-mortarboard ms-1"></i><?= number_format($lessonCnt) ?> درس</span>
                <span>•</span>
                <span><i class="bi bi-book ms-1"></i><?= number_format($bookCnt) ?> کتاب</span>
              </div>
            </div>
          </div>

          <?php if (!empty($children)): ?>
          <div class="mt-3 pt-2 border-top">
            <span class="text-muted" style="font-size:0.75rem">زیرموضوعات:</span>
            <div class="d-flex flex-wrap gap-1 mt-1">
              <?php foreach ($children as $ch): $subcnt = countPostsByTopic((int)$ch['id']); ?>
              <a href="<?= topicUrl($ch) ?>" class="badge badge-article text-decoration-none">
                <?= sanitize($ch['name']) ?> <?php if ($subcnt > 0): ?><span class="opacity-75">(<?= $subcnt ?>)</span><?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="mt-auto pt-3">
            <a href="<?= $topUrl ?>" class="btn btn-outline-primary btn-sm w-100">
              ورود به مرکز محتوایی موضوع <i class="bi bi-arrow-left ms-1"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
