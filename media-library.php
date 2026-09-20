<?php
require_once __DIR__.'/includes/functions.php';
$kind=($_GET['kind']??'video')==='audio'?'audio':'video';
$pageTitle=$kind==='audio'?'کتابخانه صوتی':'کتابخانه ویدیو';
$page=max(1,(int)($_GET['page']??1));$limit=12;
$column=$kind==='audio'?'audio_file':'video_file';
$sql="SELECT m.file_path AS path,p.title,p.slug,p.featured_image,p.published_at AS date,'post' AS target
 FROM media_files m JOIN posts p ON p.id=m.ref_id AND m.ref_type='post' WHERE m.kind=? AND p.status='published'
 UNION SELECT m.file_path,l.title,l.slug,l.featured_image,l.created_at,'lesson'
 FROM media_files m JOIN lessons l ON l.id=m.ref_id AND m.ref_type='lesson' WHERE m.kind=? AND l.status='published'
 UNION SELECT l.$column,l.title,l.slug,l.featured_image,l.created_at,'lesson' FROM lessons l WHERE l.status='published' AND l.$column IS NOT NULL AND l.$column<>''";
if($kind==='video') $sql.=" UNION SELECT p.featured_video,p.title,p.slug,p.featured_image,p.published_at,'post' FROM posts p WHERE p.status='published' AND p.featured_video IS NOT NULL AND p.featured_video<>''";
$stmt=getDB()->prepare('SELECT COUNT(*) FROM ('.$sql.') media');$stmt->execute([$kind,$kind]);$total=(int)$stmt->fetchColumn();
$stmt=getDB()->prepare('SELECT * FROM ('.$sql.') media ORDER BY date DESC LIMIT ? OFFSET ?');$stmt->execute([$kind,$kind,$limit,($page-1)*$limit]);$items=$stmt->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="container py-5"><div class="jhd-section-heading"><div><span class="jhd-eyebrow">شنیدن و آموختن</span><h1 class="h2"><?= $pageTitle ?></h1><p>رسانه‌های منتشرشده مدرسه؛ همراه شما در مسیر یادگیری.</p></div><a href="<?= siteUrl($kind==='audio'?'video':'audio') ?>"><?= $kind==='audio'?'ویدیوها':'صوت‌ها' ?></a></div>
<div class="row g-4">
<?php foreach($items as $item): ?><div class="col-md-6 col-lg-4"><article class="card h-100">
<?php if($kind==='video'): ?><video controls preload="none" playsinline poster="<?= imgUrl($item['featured_image']??'') ?>" aria-label="<?= sanitize($item['title']) ?>" src="<?= imgUrl($item['path']) ?>"></video>
<?php else: ?><img class="news-card-img" src="<?= imgUrl($item['featured_image']??'') ?>" alt="" loading="lazy"><?php endif; ?>
<div class="card-body"><small class="text-muted"><?= sanitize(persianDate($item['date'])) ?></small><h2 class="h5 mt-2"><?= sanitize($item['title']) ?></h2>
<?php if($kind==='audio'): ?><audio controls preload="none" aria-label="<?= sanitize($item['title']) ?>" src="<?= imgUrl($item['path']) ?>"></audio><?php endif; ?>
<a class="jhd-text-link mt-3" href="<?= siteUrl($item['target'].'.php?slug='.urlencode($item['slug'])) ?>">جزئیات و متن جلسه <i class="bi bi-arrow-left"></i></a></div></article></div><?php endforeach; ?>
</div>
<?php if(!$items): ?><p class="text-muted py-5 text-center">هنوز رسانه‌ای در این بخش منتشر نشده است.</p><?php endif; ?>
<div class="mt-4"><?= paginate($total,$limit,$page,siteUrl($kind).'?page=%d') ?></div>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
