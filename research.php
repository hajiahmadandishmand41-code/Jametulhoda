<?php
/**
 * research.php — پژوهش‌ها (بخش مهم سایت)
 */
$pageTitle='پژوهش‌ها';
$pageDesc='پژوهش‌های علمی و دینی مدرسه جامعه‌الهدی — مطالب تحقیقی با منابع، چکیده و موضوعات مرتبط.';
require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/functions.php';

$search=trim($_GET['q'] ?? '');
$page=max(1,(int)($_GET['page'] ?? 1));
$limit=12; $offset=($page-1)*$limit;
$opts=['type'=>'research','limit'=>$limit,'offset'=>$offset];
if($search) $opts['search']=$search;
$posts=getPosts($opts);
$total=countPosts(array_merge(['type'=>'research'], $search?['search'=>$search]:[]));
$pages=(int)ceil($total/$limit);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
<li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
<li class="breadcrumb-item active">پژوهش‌ها</li>
</ol></nav></div></div>
<div class="py-5"><div class="container">
<div class="page-header mb-4"><h1 class="page-title"><i class="bi bi-journal-richtext ms-2 text-gold"></i> پژوهش‌ها</h1><div class="section-divider"></div><?php if($total>0): ?><p class="text-muted small"><?= number_format($total) ?> پژوهش</p><?php endif; ?></div>
<form method="get" class="mb-4"><div class="input-group" style="max-width:480px"><input type="text" name="q" class="form-control" placeholder="جستجو در پژوهش‌ها..." value="<?= sanitize($search) ?>"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div></form>
<?php if(empty($posts)): ?>
<div class="text-center py-5 text-muted"><i class="bi bi-journal-x display-1 d-block mb-3 opacity-25"></i><h4>پژوهشی یافت نشد</h4></div>
<?php else: ?>
<div class="row g-4"><?php foreach($posts as $p): ?>
<div class="col-md-6 col-lg-4">
<article class="article-card h-100">
<?php if($p['featured_image']): ?><img src="<?= imgUrl($p['featured_image']) ?>" class="article-card-img" loading="lazy" alt="<?= sanitize($p['title']) ?>"><?php else: ?><div class="article-card-img-placeholder"><i class="bi bi-journal-richtext"></i></div><?php endif; ?>
<div class="article-card-body"><div class="article-card-meta"><span class="article-date"><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span></div><h2 class="article-card-title h5"><a href="<?= siteUrl('post.php?slug='.urlencode($p['slug'])) ?>"><?= sanitize($p['title']) ?></a></h2><?php if($p['summary']): ?><p class="article-card-summary"><?= sanitize(excerpt($p['summary'],120)) ?></p><?php endif; ?><a href="<?= siteUrl('post.php?slug='.urlencode($p['slug'])) ?>" class="btn-read-more">مطالعه <i class="bi bi-arrow-left"></i></a></div>
</article>
</div>
<?php endforeach; ?></div>
<?php if($pages>1): ?><div class="mt-5"><?= paginate($total,$limit,$page, siteUrl('research.php?q='.urlencode($search).'&page=%d')) ?></div><?php endif; ?>
<?php endif; ?>
</div></div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
