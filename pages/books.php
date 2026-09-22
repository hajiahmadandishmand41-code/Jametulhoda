<?php
/**
 * books.php — کتابخانه دیجیتال (مرجع کتاب‌ها)
 * قابلیت: جستجو، فیلتر موضوعی، کارت کتاب با نویسنده/سال، سئو
 */
$pageTitle='کتابخانه';
$pageDesc='کتابخانه دیجیتال مدرسه علمیه جامعه‌الهدی — کتب علمی، حوزوی و پژوهشی با دسترسی آزاد، فهرست، معرفی و دانلود PDF';
require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['q'] ?? '');
$topicSlug = trim($_GET['topic'] ?? '');
$page = max(1, (int)($_GET['page']??1));
$limit=12; $offset=($page-1)*$limit;

$topic=null; $topicId=null;
if($topicSlug){ $topic=getTopicBySlug($topicSlug); if($topic) $topicId=(int)$topic['id']; }

$opts=['limit'=>$limit,'offset'=>$offset];
if($search) $opts['search']=$search;
if($topicId) $opts['topic']=$topicId;

$books = getBooks($opts);
$total = countBooks(['search'=>$search, 'topic'=>$topicId]);
$pages = (int)ceil($total/$limit);
$canonicalUrl = siteUrl('books'.($topicSlug?'?topic='.urlencode($topicSlug):'').($search?($topicSlug?'&':'?').'q='.urlencode($search):''));
$hasNoIndex = $page>1 || $search!=='' || $topicId!==null;
if($hasNoIndex) $pageRobots='noindex, follow';
$breadcrumbs = [
  ['name'=>'صفحه اصلی','url'=>siteUrl()],
  ['name'=>'کتابخانه','url'=>siteUrl('books')],
];
if($topic) $breadcrumbs[]=['name'=>$topic['name'],'url'=>siteUrl('books?topic='.urlencode($topic['slug']))];
$breadcrumbsJsonLd = json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'BreadcrumbList',
  'itemListElement'=>array_map(function($cr,$i){ return ['@type'=>'ListItem','position'=>$i+1,'name'=>$cr['name'],'item'=>$cr['url']]; }, $breadcrumbs, array_keys($breadcrumbs))
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
?>

<div class="breadcrumb-bar"><div class="container">
<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
<?php foreach($breadcrumbs as $i=>$cr): if($i===count($breadcrumbs)-1): ?><li class="breadcrumb-item active"><?= sanitize($cr['name']) ?></li><?php else: ?><li class="breadcrumb-item"><a href="<?= sanitize($cr['url']) ?>"><?= sanitize($cr['name']) ?></a></li><?php endif; endforeach; ?>
</ol></nav>
</div></div>
<script type="application/ld+json"><?= $breadcrumbsJsonLd ?></script>

<div class="py-4 py-md-5"><div class="container">
<header class="mb-4">
<h1 class="h3 fw-bold m-0"><i class="bi bi-book ms-2" style="color:var(--jhd-primary)"></i> کتابخانه</h1>
<div class="section-divider my-3" style="max-width:88px"></div>
<?php if($topic): ?>
<p class="text-muted small">کتاب‌های مرتبط با موضوع <strong><?= sanitize($topic['name']) ?></strong> — <?= number_format($total) ?> کتاب</p>
<?php elseif($search): ?>
<p class="text-muted small">نتایج برای “<?= sanitize($search) ?>” — <?= number_format($total) ?> کتاب</p>
<?php else: ?>
<p class="text-muted small">مرجع کتب علمی و حوزوی جامعه‌الهدی — معرفی، فهرست و دانلود آزاد</p>
<?php endif; ?>
</header>

<form method="get" class="mb-4">
<div class="input-group" style="max-width:520px">
<input type="text" name="q" class="form-control" placeholder="جستجو در عنوان، نویسنده، توضیح..." value="<?= sanitize($search) ?>">
<?php if($topicSlug): ?><input type="hidden" name="topic" value="<?= sanitize($topicSlug) ?>"><?php endif; ?>
<button class="btn btn-primary"><i class="bi bi-search"></i></button>
<?php if($search || $topicSlug): ?><a href="<?= siteUrl('books') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
</div>
</form>

<?php if($topic): ?>
<div class="mb-4 p-3 rounded-4" style="background:#fafaf7;border:1px solid #e8e6dc">
<strong><?= sanitize($topic['name']) ?></strong> <?php if($topic['description']): ?><span class="text-muted small"> — <?= sanitize(mb_strimwidth($topic['description'],0,160,'…')) ?></span><?php endif; ?>
<div class="mt-2"><a href="<?= topicUrl($topic) ?>" class="btn btn-sm btn-outline-primary">صفحه موضوع <i class="bi bi-arrow-left ms-1"></i></a></div>
</div>
<?php endif; ?>

<?php if(empty($books)): ?>
<div class="text-center py-5">
<i class="bi bi-book display-1 text-muted opacity-25 d-block mb-3"></i>
<p class="text-muted"><?= $search||$topicSlug?'کتابی با این مشخصات یافت نشد.':'هنوز کتابی منتشر نشده است.' ?></p>
<?php if($search||$topicSlug): ?><a href="<?= siteUrl('books') ?>" class="btn btn-outline-primary mt-2">همه کتاب‌ها</a><?php endif; ?>
</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($books as $b):
  $bookSlug = $b['slug'] ?: $b['id'];
  $bookUrl = bookUrl($b);
  $cover = $b['cover_image'] ?? '';
?>
<div class="col-6 col-md-4 col-lg-3">
<article class="content-card h-100 d-flex flex-column" style="border-radius:16px;overflow:hidden;border:1px solid #e8e6dc">
<?php if($cover): ?><a href="<?= $bookUrl ?>"><img src="<?= imgUrl($cover) ?>" alt="جلد <?= sanitize($b['title']) ?>" style="width:100%;aspect-ratio:3/4;object-fit:cover;display:block" loading="lazy"></a><?php endif; ?>
<div class="p-3 d-flex flex-column flex-grow-1">
<h2 class="h6 fw-bold mb-1" style="line-height:1.5"><a href="<?= $bookUrl ?>" style="color:inherit;text-decoration:none"><?= sanitize($b['title']) ?></a></h2>
<?php if(!empty($b['author'])): ?><div class="text-muted small mb-1"><i class="bi bi-person ms-1"></i><?= sanitize($b['author']) ?></div><?php endif; ?>
<?php if($b['publish_year'] || $b['pages']): ?><div class="text-muted d-flex gap-2" style="font-size:.75rem"><?php if($b['publish_year']): ?><span><?= sanitize($b['publish_year']) ?></span><?php endif; ?><?php if($b['pages']): ?><span><?= (int)$b['pages'] ?> ص</span><?php endif; ?></div><?php endif; ?>
<?php $tpcs=getTopicsForBook((int)$b['id']); if($tpcs): ?><div class="mt-2 d-flex flex-wrap gap-1"><?php foreach(array_slice($tpcs,0,2) as $tp): ?><a href="<?= siteUrl('books?topic='.urlencode($tp['slug'])) ?>" class="badge rounded-pill" style="background:#f0ece3;color:#7a6a3a;font-size:.68rem;font-weight:600"><?= sanitize($tp['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
<div class="mt-auto pt-2"><a href="<?= $bookUrl ?>" class="btn btn-sm btn-outline-primary w-100">مشاهده و دانلود</a></div>
</div>
</article>
</div>
<?php endforeach; ?>
</div>

<?php if($pages>1): ?>
<nav class="mt-5 d-flex justify-content-center" aria-label="صفحه‌بندی">
<ul class="pagination mb-0" style="gap:6px">
<?php for($i=1;$i<=$pages;$i++): $qs=http_build_query(array_filter(['q'=>$search?:null,'topic'=>$topicSlug?:null,'page'=>$i>1?$i:null])); ?>
<li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link rounded-pill px-3" href="<?= siteUrl('books'.($qs?'?'.$qs:'')) ?>"><?= $i ?></a></li>
<?php endfor; ?>
</ul>
</nav>
<?php endif; ?>
<?php endif; ?>
</div></div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
