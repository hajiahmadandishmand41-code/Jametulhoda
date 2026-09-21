<?php
/**
 * topics.php — فهرست همه موضوعات (ستون فقرات سایت)
 */
$pageTitle='موضوعات';
$pageDesc='موضوعات دینی و علمی مدرسه جامعه‌الهدی — هر موضوع مرکز محتوایی شامل مقالات، گزارش‌ها، کتاب‌ها، دروس و رسانه‌های مرتبط.';
require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/functions.php';

$tree=getTopicTree();
$breadcrumbs=[['name'=>'صفحه اصلی','url'=>siteUrl()],['name'=>'موضوعات','url'=>siteUrl('topics.php')]];
$breadcrumbsJsonLd=breadcrumbsJsonLd($breadcrumbs);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
<li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
<li class="breadcrumb-item active">موضوعات</li>
</ol></nav></div></div>

<div class="py-5"><div class="container">
<div class="page-header mb-4">
<h1 class="page-title"><i class="bi bi-diagram-3 ms-2 text-gold"></i> موضوعات</h1>
<div class="section-divider"></div>
<p class="text-muted">هر موضوع مانند یک مرکز محتوایی عمل می‌کند؛ همه مطالب مرتبط را در یک صفحه جمع می‌کند.</p>
</div>

<?php if(empty($tree)): ?>
<div class="text-center py-5 text-muted">موضوعی ثبت نشده است.</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($tree as $top): $children=$top['children'] ?? []; $cnt=countPostsByTopic((int)$top['id']); $lessonCnt=count(getLessonsByTopic((int)$top['id'],100)); $bookCnt=count(getBooksByTopic((int)$top['id'],100)); ?>
<div class="col-lg-6">
<div class="card h-100">
<div class="card-body">
<div class="d-flex gap-3">
<?php if($top['cover_image']): ?><img src="<?= imgUrl($top['cover_image']) ?>" alt="<?= sanitize($top['name']) ?>" style="width:84px;height:84px;object-fit:cover;border-radius:8px" loading="lazy"><?php else: ?><div style="width:84px;height:84px;background:#f8f7f2;border:1px solid #e9e2c9;border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi bi-folder" style="font-size:1.8rem;color:#b39250"></i></div><?php endif; ?>
<div class="flex-grow-1">
<h2 class="h5 mb-1"><a href="<?= siteUrl('topic.php?slug='.urlencode($top['slug'])) ?>"><?= sanitize($top['name']) ?></a></h2>
<?php if($top['intro'] ?: $top['description']): ?><p class="text-muted small mb-1"><?= sanitize(excerpt($top['intro'] ?: $top['description'],120)) ?></p><?php endif; ?>
<span class="text-muted small"><i class="bi bi-collection ms-1"></i><?= number_format($cnt) ?> مطلب · <?= number_format($lessonCnt) ?> درس · <?= number_format($bookCnt) ?> کتاب</span>
</div>
</div>
<?php if($children): ?>
<div class="d-flex flex-wrap gap-1 mt-3">
<?php foreach($children as $ch): $subcnt=countPostsByTopic((int)$ch['id']); ?>
<a href="<?= siteUrl('topic.php?slug='.urlencode($ch['slug'])) ?>" class="badge bg-light text-dark border"><?= sanitize($ch['name']) ?> <span class="text-muted">(<?= $subcnt ?>)</span></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<a href="<?= siteUrl('topic.php?slug='.urlencode($top['slug'])) ?>" class="btn btn-primary btn-sm w-100 mt-3">ورود به صفحه موضوع <i class="bi bi-arrow-left ms-1"></i></a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
