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
      <?= renderTopicCard($top, [
          'col' => 'col-lg-6',
          'cta' => 'ورود به مرکز محتوایی موضوع',
          'counts' => [
              ['icon' => 'bi-file-earmark-text', 'value' => $cnt, 'label' => 'مطلب'],
              ['icon' => 'bi-mortarboard', 'value' => $lessonCnt, 'label' => 'درس'],
              ['icon' => 'bi-book', 'value' => $bookCnt, 'label' => 'کتاب'],
          ],
      ]) ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
