<?php
$pageTitle='پرسش و پاسخ';
$pageDesc='پرسش و پاسخ‌های دینی و علمی — پاسخ‌های مستند بر اساس قرآن و روایات.';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/functions.php';
$search=trim($_GET['q'] ?? '');
$page=max(1,(int)($_GET['page'] ?? 1));
$limit=12; $offset=($page-1)*$limit;
$opts=['type'=>'qa','limit'=>$limit,'offset'=>$offset];
if($search) $opts['search']=$search;
$posts=getPosts($opts);
$total=countPosts(array_merge(['type'=>'qa'], $search?['search'=>$search]:[]));
$pages=(int)ceil($total/$limit);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li><li class="breadcrumb-item active">پرسش و پاسخ</li></ol></nav></div></div>
<div class="py-5"><div class="container">
<div class="page-header mb-4"><h1 class="page-title"><i class="bi bi-question-circle ms-2 text-gold"></i> پرسش و پاسخ</h1><div class="section-divider"></div></div>
<form method="get" class="mb-4"><div class="input-group" style="max-width:480px"><input type="text" name="q" class="form-control" placeholder="جستجوی پرسش..." value="<?= sanitize($search) ?>"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div></form>
<?php if(empty($posts)): ?><div class="text-center py-5 text-muted"><i class="bi bi-question-circle display-1 d-block mb-3 opacity-25"></i><p>پرسشی ثبت نشده است.</p></div>
<?php else: ?><div class="row g-4"><?php foreach($posts as $p): ?>
<div class="col-md-6"><div class="card h-100 p-3"><span class="badge bg-primary mb-2">پرسش و پاسخ</span><h2 class="h6"><a href="<?= postUrl($p) ?>"><?= sanitize($p['title']) ?></a></h2><p class="text-muted small"><?= sanitize(excerpt($p['summary'] ?? '',120)) ?></p><a href="<?= postUrl($p) ?>" class="btn-read-more">مشاهده پاسخ <i class="bi bi-arrow-left"></i></a></div></div>
<?php endforeach; ?></div>
<?php if($pages>1): ?><div class="mt-4"><?= paginate($total,$limit,$page, siteUrl('qa?q='.urlencode($search).'&page=%d')) ?></div><?php endif; ?>
<?php endif; ?>
</div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
