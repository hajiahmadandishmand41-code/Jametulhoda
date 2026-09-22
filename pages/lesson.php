<?php
/**
 * lesson.php — صفحه درس حوزوی محتوامحور (مجموعه → جلد → درس)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect(siteUrl('lessons'));

$stmt=getDB()->prepare("SELECT l.*, lc.title AS collection_title, lc.slug AS collection_slug, lv.title AS volume_title, lv.slug AS volume_slug FROM lessons l LEFT JOIN lesson_collections lc ON lc.id=l.collection_id LEFT JOIN lesson_volumes lv ON lv.id=l.volume_id WHERE l.slug=? AND l.status='published' LIMIT 1");
$stmt->execute([$slug]);
$lesson=$stmt->fetch();
if(!$lesson){
    http_response_code(404);
    $pageTitle='درس یافت نشد';
    $pageDesc='درس مورد نظر یافت نشد';
    require_once __DIR__.'/../includes/header.php';
    echo '<div class="container py-5 text-center"><h1>درس یافت نشد</h1><a href="'.siteUrl('lessons').'" class="btn btn-primary mt-3">بازگشت</a></div>';
    require_once __DIR__.'/../includes/footer.php'; exit;
}
$pageTitle=$lesson['title'];
$canonicalOverride=lessonUrl($lesson);
$pageDesc=$lesson['summary'] ? excerpt($lesson['summary'],160) : excerpt(strip_tags($lesson['content'] ?? ''),160);

$audioUrl = $lesson['audio_file'] ? siteUrl(ltrim($lesson['audio_file'],'/')) : '';
$videoUrl = $lesson['video_file'] ? siteUrl(ltrim($lesson['video_file'],'/')) : '';
$pdfUrl   = $lesson['pdf_file'] ? siteUrl(ltrim($lesson['pdf_file'],'/')) : '';
$lessonTopics=getTopicsForLesson((int)$lesson['id']);
$adjacent=getAdjacentLesson($lesson);

// related via same topic or same collection
$relatedLessons=[];
if(!empty($lessonTopics)){
    $relatedLessons=getLessonsByTopic((int)$lessonTopics[0]['id'],4);
    $relatedLessons=array_filter($relatedLessons, fn($r)=>$r['id']!=$lesson['id']);
    $relatedLessons=array_slice($relatedLessons,0,3);
}
if(count($relatedLessons)<3 && $lesson['collection_id']){
    $more=getLessonsByCollection((int)$lesson['collection_id'], (int)($lesson['volume_id'] ?? 0), 10);
    $more=array_filter($more, fn($r)=>$r['id']!=$lesson['id'] && !in_array($r['id'], array_column($relatedLessons,'id')));
    $relatedLessons=array_slice(array_merge($relatedLessons,$more),0,3);
}

// breadcrumbs + jsonld
$breadcrumbs=[
    ['name'=>'صفحه اصلی','url'=>SITE_URL? rtrim(SITE_URL,'/').'/': siteUrl()],
    ['name'=>'دروس','url'=>siteUrl('lessons')],
];
if($lesson['collection_title']){
    $breadcrumbs[]=['name'=>$lesson['collection_title'],'url'=>collectionUrl($lesson['collection_slug'])];
    if($lesson['volume_title']) $breadcrumbs[]=['name'=>$lesson['volume_title'],'url'=>collectionUrl($lesson['collection_slug'], $lesson['volume_slug'])];
} elseif($lesson['subject']){
    $breadcrumbs[]=['name'=>sanitize($lesson['subject']),'url'=>siteUrl('lessons?q='.urlencode($lesson['subject']))];
}
$breadcrumbs[]=['name'=>$lesson['title'],'url'=>canonicalUrl(lessonUrl($lesson))];
$breadcrumbsJsonLd=breadcrumbsJsonLd($breadcrumbs);

require_once __DIR__.'/../includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
<?php foreach($breadcrumbs as $i=>$bc): $isLast=$i===count($breadcrumbs)-1; ?>
<li class="breadcrumb-item <?= $isLast?'active':'' ?>" <?= $isLast?'aria-current="page"':'' ?>><?php if(!$isLast): ?><a href="<?= sanitize($bc['url']) ?>"><?= sanitize($bc['name']) ?></a><?php else: ?><?= sanitize(mb_strimwidth($bc['name'],0,55,'...')) ?><?php endif; ?></li>
<?php endforeach; ?>
</ol></nav></div></div>

<div class="py-5"><div class="container"><div class="row g-4">
<div class="col-lg-8">
<article class="single-post lesson-single" itemscope itemtype="https://schema.org/LearningResource">
<header class="single-post-header">
<div class="d-flex flex-wrap gap-2 mb-2">
<?php if($lesson['collection_title']): ?><a href="<?= collectionUrl($lesson['collection_slug']) ?>" class="badge bg-primary"><?= sanitize($lesson['collection_title']) ?></a><?php endif; ?>
<?php if($lesson['volume_title']): ?><span class="badge bg-secondary"><?= sanitize($lesson['volume_title']) ?></span><?php endif; ?>
<?php if($lesson['subject'] && !$lesson['collection_title']): ?><span class="badge bg-primary"><?= sanitize($lesson['subject']) ?></span><?php endif; ?>
<?php if(!empty($lesson['lesson_number'])): ?><span class="badge bg-warning text-dark">درس <?= (int)$lesson['lesson_number'] ?></span><?php endif; ?>
</div>
<h1 class="single-post-title" itemprop="name"><?= sanitize($lesson['title']) ?></h1>
<div class="single-post-meta d-flex flex-wrap gap-3 mt-3">
<span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($lesson['created_at']) ?></span>
<?php if($lesson['teacher']): ?><span><i class="bi bi-person-fill ms-1"></i> استاد: <?= sanitize($lesson['teacher']) ?></span><?php endif; ?>
<?php if($audioUrl): ?><span class="text-success"><i class="bi bi-headphones ms-1"></i> صوت</span><?php endif; ?>
<?php if($videoUrl): ?><span class="text-danger"><i class="bi bi-camera-video ms-1"></i> ویدیو</span><?php endif; ?>
<?php if($pdfUrl): ?><span><i class="bi bi-file-pdf ms-1"></i> PDF</span><?php endif; ?>
</div>
<?php if($lessonTopics): ?>
<div class="d-flex flex-wrap gap-1 mt-3"><?php foreach($lessonTopics as $t): ?><a href="<?= topicUrl($t) ?>" class="badge bg-light text-dark border"><i class="bi bi-tag ms-1"></i><?= sanitize($t['name']) ?></a><?php endforeach; ?></div>
<?php endif; ?>
</header>

<?php if($lesson['featured_image']): ?>
<div class="single-post-img-wrap mt-4"><img src="<?= imgUrl($lesson['featured_image']) ?>" alt="<?= sanitize($lesson['title']) ?>" class="single-post-img" loading="lazy" decoding="async"></div>
<?php endif; ?>

<?php if($videoUrl): ?>
<div class="my-4"><h2 class="h5 fw-bold"><i class="bi bi-camera-video ms-2 text-danger"></i> ویدیو درس</h2>
<div class="rounded overflow-hidden" style="background:#000"><video controls playsinline preload="none" poster="<?= $lesson['featured_image']?imgUrl($lesson['featured_image']):'' ?>" class="w-100" style="max-height:480px"><source src="<?= htmlspecialchars($videoUrl,ENT_QUOTES) ?>" type="video/mp4">مرورگر از ویدیو پشتیبانی نمی‌کند.</video></div>
</div>
<?php endif; ?>

<?php if($audioUrl): ?>
<div class="audio-player-wrap my-4 p-4 rounded-xl" style="background:linear-gradient(135deg,#f8f9fa 0%,#e8f4f8 100%);border:2px solid #dee2e6">
<div class="d-flex align-items-center gap-3 mb-3"><div style="width:56px;height:56px;background:linear-gradient(135deg,#1a6b3c,#2ecc71);border-radius:50%;display:flex;align-items:center;justify-content:center"><i class="bi bi-headphones text-white" style="font-size:1.5rem"></i></div><div><div class="fw-bold"><?= sanitize($lesson['title']) ?></div><?php if($lesson['teacher']): ?><div class="text-muted small"><?= sanitize($lesson['teacher']) ?></div><?php endif; ?></div></div>
<audio controls preload="none" class="w-100" style="border-radius:12px;height:54px"><source src="<?= htmlspecialchars($audioUrl,ENT_QUOTES) ?>" type="audio/mpeg">مرورگر از صوت پشتیبانی نمی‌کند.</audio>
<div class="d-flex gap-2 mt-3"><a href="<?= htmlspecialchars($audioUrl,ENT_QUOTES) ?>" download class="btn btn-outline-success btn-sm"><i class="bi bi-download ms-1"></i> دانلود صوت</a><?php if($pdfUrl): ?><a href="<?= htmlspecialchars($pdfUrl,ENT_QUOTES) ?>" target="_blank" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf ms-1"></i> دانلود PDF</a><?php endif; ?></div>
</div>
<?php elseif($pdfUrl): ?>
<div class="my-4"><a href="<?= htmlspecialchars($pdfUrl,ENT_QUOTES) ?>" target="_blank" class="btn btn-danger"><i class="bi bi-file-pdf ms-1"></i> دریافت PDF درس</a></div>
<?php endif; ?>

<?php if($lesson['summary']): ?>
<div class="single-post-summary my-4"><strong><i class="bi bi-card-text ms-2"></i> خلاصه:</strong><p class="mb-0 mt-2"><?= sanitize($lesson['summary']) ?></p></div>
<?php endif; ?>

<?php if($lesson['content']): ?>
<div class="single-post-content mt-4" itemprop="description"><?= safeRichText($lesson['content']) ?></div>
<?php endif; ?>
<?php if(!empty($lesson['sources'])): ?>
<div class="mt-4 p-3 rounded-4" style="background:#fafaf7;border:1px solid #e8e6dc">
<h3 class="h6 fw-bold" style="color:var(--jhd-primary)"><i class="bi bi-journal-text ms-2"></i> منابع درس</h3>
<div style="white-space:pre-wrap;line-height:1.9;color:#3a3a3a;font-size:.93rem"><?= sanitize($lesson['sources']) ?></div>
</div>
<?php endif; ?>

<!-- ناوبری درس قبلی/بعدی -->
<?php if($adjacent['prev'] || $adjacent['next']): ?>
<div class="d-flex justify-content-between gap-3 mt-5 p-3" style="background:#f8f7f2;border:1px solid #e9e2c9;border-radius:12px">
<?php if($adjacent['prev']): ?><a href="<?= lessonUrl($adjacent['prev']) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-right ms-1"></i> درس قبلی: <?= sanitize(mb_strimwidth($adjacent['prev']['title'],0,25,'...')) ?></a><?php else: ?><span></span><?php endif; ?>
<?php if($adjacent['next']): ?><a href="<?= lessonUrl($adjacent['next']) ?>" class="btn btn-primary">درس بعدی: <?= sanitize(mb_strimwidth($adjacent['next']['title'],0,25,'...')) ?> <i class="bi bi-arrow-left ms-1"></i></a><?php endif; ?>
</div>
<?php endif; ?>

<?php if($lessonTopics): ?>
<div class="mt-4 p-3" style="background:#fdf6e3;border:1px solid #e8d5a3;border-radius:10px">
<h3 class="h6 fw-bold">موضوعات مرتبط با این درس</h3>
<div class="d-flex flex-wrap gap-2"><?php foreach($lessonTopics as $t): ?><a href="<?= topicUrl($t) ?>" class="badge bg-white text-dark border"><i class="bi bi-tag ms-1"></i><?= sanitize($t['name']) ?></a><?php endforeach; ?></div>
</div>
<?php endif; ?>

<div class="single-post-share mt-5 p-4 bg-soft rounded-xl">
<h2 class="h5 mb-3"><i class="bi bi-share ms-2 text-gold"></i> اشتراک‌گذاری</h2>
<?php $lessonUrl=canonicalUrl(lessonUrl($lesson)); ?>
<div class="d-flex gap-2 flex-wrap"><a href="https://t.me/share/url?url=<?= urlencode($lessonUrl) ?>&text=<?= urlencode($lesson['title']) ?>" target="_blank" class="btn btn-sm" style="background:#2ca5e0;color:#fff"><i class="bi bi-telegram ms-1"></i> تلگرام</a><a href="https://wa.me/?text=<?= urlencode($lesson['title'].' - '.$lessonUrl) ?>" target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff"><i class="bi bi-whatsapp ms-1"></i> واتساپ</a><button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($lessonUrl,ENT_QUOTES) ?>').then(()=>showToast('لینک کپی شد!','success'))"><i class="bi bi-link-45deg ms-1"></i> کپی لینک</button></div>
</div>

</article>
</div>
<div class="col-lg-4">
<div class="sidebar">
<?php if($lesson['collection_id']): $vols=getLessonVolumes((int)$lesson['collection_id']); if($vols): ?>
<div class="sidebar-widget">
<h2 class="sidebar-title"><i class="bi bi-collection ms-2"></i> فهرست <?= sanitize($lesson['collection_title']) ?></h2>
<?php foreach($vols as $vol): $vLessons=getLessonsByCollection((int)$lesson['collection_id'], (int)$vol['id'], 100); ?>
<h3 class="h6 fw-bold mt-3"><?= sanitize($vol['title']) ?></h3>
<ul class="sidebar-list">
<?php foreach($vLessons as $vl): $isCurrent=$vl['id']==$lesson['id']; ?>
<li <?= $isCurrent?'style="background:#eaf3ec;border-radius:6px;padding:4px 6px"':'' ?>><a href="<?= lessonUrl($vl) ?>" style="<?= $isCurrent?'color:#0d5a2b;font-weight:700':'' ?>"><?= sanitize($vl['title']) ?> <?php if($vl['lesson_number']): ?><small class="text-muted">— درس <?= (int)$vl['lesson_number'] ?></small><?php endif; ?></a></li>
<?php endforeach; ?>
</ul>
<?php endforeach; ?>
</div>
<?php endif; endif; ?>

<?php if(!empty($relatedLessons)): ?>
<div class="sidebar-widget"><h2 class="sidebar-title"><i class="bi bi-collection-play ms-2"></i> درس‌های مرتبط</h2><ul class="sidebar-list"><?php foreach($relatedLessons as $rl): ?><li><a href="<?= lessonUrl($rl) ?>"><?= sanitize(mb_strimwidth($rl['title'],0,55,'...')) ?></a></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="sidebar-widget text-center"><a href="<?= siteUrl('lessons') ?>" class="btn btn-outline-primary w-100"><i class="bi bi-collection ms-1"></i> همه دروس</a></div>
</div>
</div>
</div></div></div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
