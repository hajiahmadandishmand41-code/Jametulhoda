<?php
/**
 * reports.php — آرشیو گزارش‌های دینی و فعالیت‌های مدرسه (بخش اصلی Home)
 */
$pageTitle='گزارش‌ها';
$pageDesc='گزارش فعالیت‌های علمی، فرهنگی، جلسات، محافل، مراسم و برنامه‌های مدرسه علمیه جامعه‌الهدی.';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/functions.php';

$search=trim($_GET['q'] ?? '');
$topicSlug=trim($_GET['topic'] ?? '');
$topicId=null;
if($topicSlug){ $t=getTopicBySlug($topicSlug); if($t) $topicId=(int)$t['id']; }
$page=max(1,(int)($_GET['page'] ?? 1));
$limit=12; $offset=($page-1)*$limit;
$opts=['type'=>'report','limit'=>$limit,'offset'=>$offset];
if($search) $opts['search']=$search;
if($topicId) $opts['topic']=$topicId;
$posts=getPosts($opts);
$total=countPosts(['type'=>'report'] + ($search?['search'=>$search]:[]) + ($topicId?['topic'=>$topicId]:[]));
$pages=(int)ceil($total/$limit);

$breadcrumbs=[['name'=>'صفحه اصلی','url'=>siteUrl()],['name'=>'گزارش‌ها','url'=>siteUrl('reports')]];
if($topicId && $t) $breadcrumbs[]=['name'=>$t['name'],'url'=>siteUrl('reports?topic='.urlencode($t['slug']))];
$breadcrumbsJsonLd=breadcrumbsJsonLd($breadcrumbs);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
<li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
<li class="breadcrumb-item active">گزارش‌ها</li>
<?php if(!empty($t)): ?><li class="breadcrumb-item active"><?= sanitize($t['name']) ?></li><?php endif; ?>
</ol></nav></div></div>

<div class="py-5"><div class="container">
<div class="page-header mb-4">
<h1 class="page-title"><i class="bi bi-newspaper ms-2 text-gold"></i> گزارش‌ها</h1>
<div class="section-divider"></div>
<p class="text-muted">گزارش فعالیت‌های مدرسه، جلسات، محافل، مراسم، برنامه‌های علمی و مناسبت‌های دینی — هر گزارش دارای صفحه مستقل، URL مستقل، عنوان دقیق، تاریخ، تصویر، خلاصه، متن کامل و موضوعات مرتبط.</p>
<?php if($total>0): ?><p class="text-muted small mt-2"><?= number_format($total) ?> گزارش</p><?php endif; ?>
</div>

<form method="get" class="mb-4"><div class="input-group" style="max-width:480px"><input type="text" name="q" class="form-control" placeholder="جستجو در گزارش‌ها..." value="<?= sanitize($search) ?>"><button class="btn btn-primary"><i class="bi bi-search"></i></button><?php if($search): ?><a href="<?= siteUrl('reports') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?></div></form>

<?php if(empty($posts)): ?>
<div class="text-center py-5"><i class="bi bi-inbox display-1 d-block mb-3 opacity-25 text-muted"></i><h4 class="text-muted">گزارشی یافت نشد</h4></div>
<?php else: ?>
<div class="row g-4">
<?php foreach($posts as $p): ?>
<div class="col-md-6 col-lg-4">
<article class="news-card h-100">
<div class="news-card-img-wrap">
<?php if($p['featured_image']): ?><img src="<?= imgUrl($p['featured_image']) ?>" alt="<?= sanitize($p['title']) ?>" class="news-card-img" loading="lazy"><?php else: ?><div class="news-card-img-placeholder"><i class="bi bi-newspaper"></i></div><?php endif; ?>
<div class="news-card-badge"><?= postTypeBadge($p['post_type']) ?></div>
</div>
<div class="news-card-body">
<div class="news-card-meta"><span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span></div>
<h2 class="news-card-title h5"><a href="<?= postUrl($p) ?>"><?= sanitize($p['title']) ?></a></h2>
<?php if($p['summary']): ?><p class="news-card-summary"><?= sanitize(excerpt($p['summary'],110)) ?></p><?php endif; ?>
<?php $pt=getTopicsForPost((int)$p['id']); if($pt): ?><div class="d-flex flex-wrap gap-1 mt-2"><?php foreach(array_slice($pt,0,2) as $tp): ?><a href="<?= topicUrl($tp) ?>" class="badge bg-light text-dark border" style="font-size:.70rem"><?= sanitize($tp['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
<div class="news-card-footer"><a href="<?= postUrl($p) ?>" class="btn-read-more">مشاهده گزارش <i class="bi bi-arrow-left ms-1"></i></a></div>
</div>
</article>
</div>
<?php endforeach; ?>
</div>
<?php if($pages>1): ?><div class="mt-5"><?= paginate($total,$limit,$page, siteUrl('reports?q='.urlencode($search).'&topic='.urlencode($topicSlug).'&page=%d')) ?></div><?php endif; ?>
<?php endif; ?>
</div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
