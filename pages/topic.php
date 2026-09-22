<?php
/**
 * topic.php — صفحه موضوع به‌عنوان مرکز محتوایی (spec §4)
 * شامل: معرفی، زیرموضوعات، مقالات، پژوهش‌ها، گزارش‌ها، کتاب‌ها، درس‌ها، ویدیوها، صوت‌ها، سخنرانی‌ها، فایل‌ها
 */
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/media.php';
startSecureSession();

$slug=trim($_GET['slug'] ?? '');
if(!$slug){ header('Location: '.siteUrl('topics')); exit; }
$topic=getTopicBySlug($slug);
if(!$topic || !$topic['is_active']){
    http_response_code(404);
    $pageTitle='موضوع یافت نشد';
    require_once __DIR__.'/../includes/header.php';
    echo '<div class="container py-5 text-center"><h1>موضوع یافت نشد</h1><a href="'.siteUrl('topics').'" class="btn btn-primary mt-3">همه موضوعات</a></div>';
    require_once __DIR__.'/../includes/footer.php'; exit;
}

$children=getTopicChildren((int)$topic['id']);
$parents=getTopicBreadcrumbs((int)$topic['id']);
$pageTitle=$topic['name'];
$pageDesc=$topic['intro'] ?: $topic['description'] ?: 'مطالب مرتبط با موضوع '. $topic['name'] . ' — مقالات، گزارش‌ها، کتاب‌ها، دروس و رسانه‌ها.';

$breadcrumbs=[['name'=>'صفحه اصلی','url'=>SITE_URL? rtrim(SITE_URL,'/').'/': siteUrl()],['name'=>'موضوعات','url'=>siteUrl('topics')]];
foreach($parents as $p){
    if($p['id']==$topic['id']) continue;
    $breadcrumbs[]=['name'=>$p['name'],'url'=>siteUrl('topic?slug='.urlencode($p['slug']))];
}
$breadcrumbs[]=['name'=>$topic['name'],'url'=>canonicalUrl('topic.php?slug='.urlencode($topic['slug']))];
$breadcrumbsJsonLd=breadcrumbsJsonLd($breadcrumbs);

// Fetch contents by topic
$topicPostsArticles = getPostsByTopic((int)$topic['id'], ['limit'=>6]); // general, but filter by type below via separate queries where needed
// More precise per type
try{
    $stmt=getDB()->prepare("SELECT p.* FROM posts p JOIN post_topics pt ON pt.post_id=p.id WHERE pt.topic_id=? AND p.status='published' AND p.post_type='article' ORDER BY p.published_at DESC LIMIT 6");
    $stmt->execute([(int)$topic['id']]); $articles=$stmt->fetchAll();
    $stmt=getDB()->prepare("SELECT p.* FROM posts p JOIN post_topics pt ON pt.post_id=p.id WHERE pt.topic_id=? AND p.status='published' AND p.post_type='research' ORDER BY p.published_at DESC LIMIT 6");
    $stmt->execute([(int)$topic['id']]); $researches=$stmt->fetchAll();
    $stmt=getDB()->prepare("SELECT p.* FROM posts p JOIN post_topics pt ON pt.post_id=p.id WHERE pt.topic_id=? AND p.status='published' AND p.post_type='report' ORDER BY p.published_at DESC LIMIT 6");
    $stmt->execute([(int)$topic['id']]); $reports=$stmt->fetchAll();
    $stmt=getDB()->prepare("SELECT p.* FROM posts p JOIN post_topics pt ON pt.post_id=p.id WHERE pt.topic_id=? AND p.status='published' AND p.post_type='announcement' ORDER BY p.published_at DESC LIMIT 3");
    $stmt->execute([(int)$topic['id']]); $announcementsTopic=$stmt->fetchAll();
}catch(PDOException $e){ $articles=[]; $researches=[]; $reports=[]; $announcementsTopic=[]; }

$books=getBooksByTopic((int)$topic['id'],6);
$lessons=getLessonsByTopic((int)$topic['id'],6);
// Videos/Audios: posts linked to topic that have media
$videos=[]; $audios=[];
try{
    $stmt=getDB()->prepare("SELECT m.* FROM media_files m JOIN post_topics pt ON pt.post_id=m.ref_id AND m.ref_type='post' WHERE pt.topic_id=? AND m.kind='video' ORDER BY m.id DESC LIMIT 4");
    $stmt->execute([(int)$topic['id']]); $videos=$stmt->fetchAll();
    $stmt=getDB()->prepare("SELECT m.* FROM media_files m JOIN lesson_topics lt ON lt.lesson_id=m.ref_id AND m.ref_type='lesson' WHERE lt.topic_id=? AND m.kind='audio' ORDER BY m.id DESC LIMIT 4");
    $stmt->execute([(int)$topic['id']]); $audios=$stmt->fetchAll();
}catch(PDOException $e){}

require_once __DIR__.'/../includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
<?php foreach($breadcrumbs as $i=>$bc): $isLast=$i===count($breadcrumbs)-1; ?>
<li class="breadcrumb-item <?= $isLast?'active':'' ?>" <?= $isLast?'aria-current="page"':'' ?>><?php if(!$isLast): ?><a href="<?= sanitize($bc['url']) ?>"><?= sanitize($bc['name']) ?></a><?php else: ?><?= sanitize($bc['name']) ?><?php endif; ?></li>
<?php endforeach; ?>
</ol></nav></div></div>

<div class="py-5"><div class="container">
<!-- Topic header -->
<div class="row g-4 align-items-start mb-5">
<div class="col-lg-8">
<h1 class="page-title" style="font-size:2rem"><?= sanitize($topic['name']) ?></h1>
<div class="section-divider"></div>
<?php if($topic['intro']): ?><p class="lead" style="font-size:1.05rem;color:var(--jhd-muted)"><?= sanitize($topic['intro']) ?></p><?php endif; ?>
<?php if($topic['description']): ?><p class="text-muted"><?= sanitize($topic['description']) ?></p><?php endif; ?>

<?php if($children): ?>
<h2 class="h6 fw-bold mt-4"><i class="bi bi-diagram-3 ms-1"></i> موضوعات فرعی</h2>
<div class="d-flex flex-wrap gap-2">
<?php foreach($children as $ch): ?>
<a href="<?= siteUrl('topic?slug='.urlencode($ch['slug'])) ?>" class="btn btn-sm" style="background:var(--jhd-surface);border:1px solid var(--jhd-border)"><?= sanitize($ch['name']) ?> <i class="bi bi-arrow-left ms-1"></i></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<div class="col-lg-4">
<?php if($topic['cover_image']): ?><img src="<?= imgUrl($topic['cover_image']) ?>" alt="<?= sanitize($topic['name']) ?>" class="img-fluid rounded" style="max-height:300px;width:100%;object-fit:cover" loading="lazy"><?php endif; ?>
<div class="mt-3 p-3" style="background:var(--jhd-paper);border:1px solid var(--jhd-border);border-radius:10px">
<span class="text-muted small"><i class="bi bi-collection ms-1"></i> آمار این موضوع</span>
<div class="d-flex gap-3 flex-wrap mt-2 small">
<span><strong><?= number_format(count($articles)+count($researches)) ?></strong> مقاله/پژوهش</span>
<span><strong><?= number_format(count($reports)) ?></strong> گزارش</span>
<span><strong><?= number_format(count($books)) ?></strong> کتاب</span>
<span><strong><?= number_format(count($lessons)) ?></strong> درس</span>
</div>
</div>
</div>
</div>

<!-- Content tabs/sections -->
<?php if(!empty($reports)): ?>
<section class="mb-5"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold"><i class="bi bi-newspaper ms-2 text-success"></i> گزارش‌ها</h2><a href="<?= siteUrl('reports?topic='.urlencode($topic['slug'])) ?>" class="btn btn-sm btn-outline-secondary">همه گزارش‌های این موضوع</a></div>
<div class="row g-4"><?php foreach($reports as $r): ?>
<div class="col-md-6 col-lg-4"><article class="news-card h-100">
<div class="news-card-img-wrap"><?php if($r['featured_image']): ?><img src="<?= imgUrl($r['featured_image']) ?>" alt="<?= sanitize($r['title']) ?>" class="news-card-img" loading="lazy"><?php else: ?><div class="news-card-img-placeholder"><i class="bi bi-newspaper"></i></div><?php endif; ?><div class="news-card-badge"><?= postTypeBadge($r['post_type']) ?></div></div>
<div class="news-card-body"><h3 class="news-card-title"><a href="<?= siteUrl('post?slug='.urlencode($r['slug'])) ?>"><?= sanitize($r['title']) ?></a></h3><p class="news-card-summary"><?= sanitize(excerpt($r['summary'] ?? '',90)) ?></p><a href="<?= siteUrl('post?slug='.urlencode($r['slug'])) ?>" class="btn-read-more">ادامه مطلب <i class="bi bi-arrow-left"></i></a></div>
</article></div>
<?php endforeach; ?></div></section>
<?php endif; ?>

<?php if(!empty($articles) || !empty($researches)): ?>
<section class="mb-5"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold"><i class="bi bi-journal-text ms-2 text-primary"></i> مقالات و پژوهش‌ها</h2></div>
<div class="row g-4">
<?php foreach(array_merge($articles,$researches) as $a): ?>
<div class="col-md-6 col-lg-4"><article class="article-card h-100">
<?php if($a['featured_image']): ?><a href="<?= siteUrl('post?slug='.urlencode($a['slug'])) ?>"><img src="<?= imgUrl($a['featured_image']) ?>" alt="<?= sanitize($a['title']) ?>" class="article-card-img" loading="lazy"></a><?php else: ?><div class="article-card-img-placeholder"><i class="bi bi-file-text"></i></div><?php endif; ?>
<div class="article-card-body"><span class="article-date small text-muted"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($a['published_at'] ?? $a['created_at']) ?></span><h3 class="article-card-title"><a href="<?= siteUrl('post?slug='.urlencode($a['slug'])) ?>"><?= sanitize($a['title']) ?></a></h3><p class="article-card-summary"><?= sanitize(excerpt($a['summary'] ?? '',100)) ?></p><a href="<?= siteUrl('post?slug='.urlencode($a['slug'])) ?>" class="btn-read-more">مطالعه <i class="bi bi-arrow-left"></i></a></div>
</article></div>
<?php endforeach; ?>
</div></section>
<?php endif; ?>

<?php if(!empty($books)): ?>
<section class="mb-5"><h2 class="h5 fw-bold mb-3"><i class="bi bi-book ms-2 text-warning"></i> کتاب‌های مرتبط</h2><div class="row g-4"><?php foreach($books as $b): ?>
<div class="col-6 col-md-3"><div class="book-card h-100"><div class="book-card-cover"><?php if($b['cover_image']): ?><img src="<?= imgUrl($b['cover_image']) ?>" alt="<?= sanitize($b['title']) ?>" class="book-cover-img" loading="lazy"><?php else: ?><div class="book-cover-placeholder"><i class="bi bi-book"></i></div><?php endif; ?></div><div class="book-card-body"><h3 class="book-title"><a href="<?= siteUrl('book?id='.(int)$b['id']) ?>"><?= sanitize($b['title']) ?></a></h3><a href="<?= siteUrl('book?id='.(int)$b['id']) ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">معرفی کتاب</a></div></div></div>
<?php endforeach; ?></div></section>
<?php endif; ?>

<?php if(!empty($lessons)): ?>
<section class="mb-5"><h2 class="h5 fw-bold mb-3"><i class="bi bi-mortarboard ms-2 text-success"></i> درس‌های مرتبط</h2><div class="row g-4"><?php foreach($lessons as $ls): ?>
<div class="col-md-6 col-lg-3"><div class="lesson-card h-100"><div class="lesson-card-img"><?php if($ls['featured_image']): ?><img src="<?= imgUrl($ls['featured_image']) ?>" alt="<?= sanitize($ls['title']) ?>" loading="lazy"><?php else: ?><div class="lesson-img-placeholder"><i class="bi bi-mortarboard"></i></div><?php endif; ?></div><div class="lesson-card-body"><h3 class="lesson-card-title"><a href="<?= siteUrl('lesson?slug='.urlencode($ls['slug'])) ?>"><?= sanitize($ls['title']) ?></a></h3><?php if($ls['teacher']): ?><p class="lesson-teacher"><?= sanitize($ls['teacher']) ?></p><?php endif; ?><a href="<?= siteUrl('lesson?slug='.urlencode($ls['slug'])) ?>" class="btn btn-sm btn-primary w-100 mt-auto">ورود به درس</a></div></div></div>
<?php endforeach; ?></div></section>
<?php endif; ?>

<?php if(!empty($videos) || !empty($audios)): ?>
<section class="mb-5"><div class="row g-4">
<div class="col-md-6"><h3 class="h6 fw-bold"><i class="bi bi-camera-video ms-1 text-danger"></i> ویدیوها</h3><?php if(empty($videos)): ?><p class="text-muted small">ویدیویی یافت نشد.</p><?php else: ?><div class="row g-3"><?php foreach($videos as $v): ?><div class="col-6"><div class="news-card"><div class="news-card-img-wrap" style="height:130px"><div class="video-thumb h-100" data-video="<?= siteUrl($v['file_path']) ?>"><div class="video-thumb__placeholder"><i class="bi bi-camera-video"></i></div><div class="video-play-overlay"><div class="play-btn-circle play-btn-circle--sm"><i class="bi bi-play-fill"></i></div></div></div></div></div></div><?php endforeach; ?></div><?php endif; ?></div>
<div class="col-md-6"><h3 class="h6 fw-bold"><i class="bi bi-headphones ms-1 text-success"></i> صوت‌ها</h3><?php if(empty($audios)): ?><p class="text-muted small">صوتی یافت نشد.</p><?php else: ?><div class="list-group"><?php foreach($audios as $a): ?><a href="#" class="list-group-item d-flex gap-2 small"><i class="bi bi-music-note text-success"></i> <?= sanitize($a['title'] ?: 'صوت') ?></a><?php endforeach; ?></div><?php endif; ?></div>
</div></section>
<?php endif; ?>

<?php
// If topic has no content, show helpful message
$totalCount = count($articles)+count($researches)+count($reports)+count($books)+count($lessons)+count($videos);
if($totalCount===0): ?>
<div class="text-center py-5 border rounded" style="background:var(--jhd-paper)"><i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i><p class="text-muted">هنوز محتوایی برای این موضوع ثبت نشده است.</p><a href="<?= siteUrl('topics') ?>" class="btn btn-outline-secondary btn-sm mt-2">مشاهده سایر موضوعات</a></div>
<?php endif; ?>

</div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
