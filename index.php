<?php
/**
 * index.php — صفحه اصلی رسانه‌ای جامعه‌الهدی (اسلایس ۱ UI)
 * ترتیب: hero محتوایی → آخرین مطالب → مقالات و اخبار منتخب → پژوهش‌ها →
 * کتاب‌ها → ویدیوها → صوت‌ها → درس‌ها → موضوعات
 * همه بخش‌ها از داده واقعی DB؛ بدون لینک و محتوای جعلی.
 */
$pageTitle = '';
$pageDesc = 'مدرسه علمیه جامعه‌الهدی — آرشیو گزارش‌های دینی، مقالات و پژوهش‌ها، کتابخانه، دروس حوزوی درس‌به‌درس، ویدیو و صوت با محوریت موضوعات دینی.';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

$db = getDB();

// ─── داده‌ها (فقط هلپرهای موجود) ──────────────────────────────────────────
// Hero: منتخب‌ها اول (مقاله، بعد خبر، بعد هر نوع)، وگرنه آخرین مطلب.
$heroPost = getPosts(['type'=>'article','featured'=>1,'limit'=>1])[0]
    ?? getPosts(['type'=>'news','featured'=>1,'limit'=>1])[0]
    ?? getPosts(['featured'=>1,'limit'=>1])[0]
    ?? getPosts(['limit'=>1])[0]
    ?? null;
$heroTopic = null;
if($heroPost){
    $ht = getTopicsForPost((int)$heroPost['id']);
    $heroTopic = $ht[0] ?? null;
}

$latestPosts = getPosts(['limit'=>6]);
$featuredArticles = getPosts(['type'=>'article','featured'=>1,'limit'=>3]);
$featuredNews = getPosts(['type'=>'news','featured'=>1,'limit'=>3]);
$featuredEditorial = array_merge($featuredArticles, $featuredNews);
$latestResearch = getPosts(['type'=>'research','limit'=>6]);

$latestBooks = getBooks(['limit'=>8]);

$recentVideos = [];
$recentAudios = [];
try{
    $stmt=getDB()->prepare("SELECT m.*, p.title AS post_title, p.slug AS post_slug FROM media_files m LEFT JOIN posts p ON p.id=m.ref_id AND m.ref_type='post' WHERE m.kind='video' AND (p.status IS NULL OR p.status='published') ORDER BY m.id DESC LIMIT 4");
    $stmt->execute(); $recentVideos=$stmt->fetchAll();
    $stmt=getDB()->prepare("SELECT m.*, l.title AS lesson_title, l.slug AS lesson_slug FROM media_files m LEFT JOIN lessons l ON l.id=m.ref_id AND m.ref_type='lesson' WHERE m.kind='audio' AND (l.status IS NULL OR l.status='published') ORDER BY m.id DESC LIMIT 4");
    $stmt->execute(); $recentAudios=$stmt->fetchAll();
    // fallback to lessons audio_file
    if(empty($recentAudios)){
        $stmt=getDB()->prepare("SELECT * FROM lessons WHERE status='published' AND audio_file IS NOT NULL AND audio_file<>'' ORDER BY id DESC LIMIT 4");
        $stmt->execute(); $recentAudios=$stmt->fetchAll();
    }
}catch(PDOException $e){}

$latestLessons = [];
try{
    $stmt=getDB()->prepare("SELECT l.*, lc.title AS collection_title, lc.slug AS collection_slug, lv.title AS volume_title FROM lessons l LEFT JOIN lesson_collections lc ON lc.id=l.collection_id LEFT JOIN lesson_volumes lv ON lv.id=l.volume_id WHERE l.status='published' ORDER BY l.created_at DESC LIMIT 6");
    $stmt->execute(); $latestLessons=$stmt->fetchAll();
}catch(PDOException $e){}

$featuredTopics = getTopics(['active'=>1,'featured'=>1,'limit'=>6]);
if(count($featuredTopics)<6){
    // supplement with most used topics (by post_topics count)
    try{
        $stmt=getDB()->query("SELECT t.*, COUNT(pt.post_id) AS cnt FROM topics t LEFT JOIN post_topics pt ON pt.topic_id=t.id WHERE t.is_active=1 GROUP BY t.id ORDER BY cnt DESC, t.sort_order ASC LIMIT 6");
        $sup=$stmt->fetchAll();
        // merge unique
        $ids=array_column($featuredTopics,'id');
        foreach($sup as $s){ if(!in_array($s['id'],$ids)) $featuredTopics[]=$s; if(count($featuredTopics)>=6) break; }
    }catch(PDOException $e){}
}

$specialBanner = getActiveBanner();

// breadcrumbs for home — no need, but for SEO we add Organization already in header
require_once __DIR__ . '/includes/header.php';
require __DIR__ . '/content/home-intro.php';
?>

<?php if($specialBanner): ?>
<section class="jhd-banner-special py-3" style="background:linear-gradient(90deg,#163b37,#245c4c);color:#fff">
<div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
<div><strong><?= sanitize($specialBanner['title']) ?></strong><?php if($specialBanner['content']): ?> <span class="opacity-75 small ms-2"><?= sanitize(excerpt($specialBanner['content'],120)) ?></span><?php endif; ?></div>
<?php if($specialBanner['link_url']): ?><a href="<?= sanitize(safeExternalUrl($specialBanner['link_url']) ?: siteUrl($specialBanner['link_url'])) ?>" class="btn btn-sm" style="background:#ded0aa;color:#163b37;font-weight:700"><?= sanitize($specialBanner['link_text'] ?: 'مشاهده') ?> <i class="bi bi-arrow-left"></i></a><?php endif; ?>
</div>
</section>
<?php endif; ?>

<!-- 1. آخرین مطالب -->
<section class="py-5">
<div class="container">
<div class="d-flex justify-content-between align-items-end mb-4">
<div><h2 class="section-title mb-1"><i class="bi bi-newspaper ms-2 text-gold"></i> آخرین مطالب</h2><div class="section-divider"></div><p class="text-muted small mb-0">تازه‌ترین گزارش‌ها، اخبار، مقالات و پژوهش‌های مدرسه</p></div>
</div>
<?php if(empty($latestPosts)): ?>
<div class="text-center py-5 text-muted"><i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i> مطلبی منتشر نشده است.</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($latestPosts as $r): $hasImg=$r['featured_image']; $hasVid=!empty($r['featured_video']); ?>
<div class="col-md-6 col-lg-4">
<article class="news-card h-100">
<div class="news-card-img-wrap">
<?php if($hasVid): ?><div class="video-thumb" data-video="<?= siteUrl($r['featured_video']) ?>" data-poster="<?= $hasImg?imgUrl($r['featured_image']):'' ?>"><?php if($hasImg): ?><img src="<?= imgUrl($r['featured_image']) ?>" alt="<?= sanitize($r['title']) ?>" class="news-card-img" loading="lazy"><?php else: ?><video class="news-card-img" src="<?= siteUrl($r['featured_video']) ?>" preload="metadata" muted playsinline></video><?php endif; ?><div class="video-play-overlay"><div class="play-btn-circle play-btn-circle--sm"><i class="bi bi-play-fill"></i></div></div><span class="video-badge-card"><i class="bi bi-camera-video-fill"></i> ویدیو</span></div>
<?php elseif($hasImg): ?><img src="<?= imgUrl($r['featured_image']) ?>" alt="<?= sanitize($r['title']) ?>" class="news-card-img" loading="lazy"><?php else: ?><div class="news-card-img-placeholder"><i class="bi bi-newspaper"></i></div><?php endif; ?>
<div class="news-card-badge"><?= postTypeBadge($r['post_type']) ?></div>
</div>
<div class="news-card-body">
<div class="news-card-meta"><span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($r['published_at'] ?? $r['created_at']) ?></span><?php if($r['cat_name']): ?><span class="badge bg-light text-dark border" style="font-size:.70rem"><?= sanitize($r['cat_name']) ?></span><?php endif; ?></div>
<h3 class="news-card-title"><a href="<?= postUrl($r) ?>"><?= sanitize($r['title']) ?></a></h3>
<?php if($r['summary']): ?><p class="news-card-summary"><?= sanitize(excerpt($r['summary'],120)) ?></p><?php endif; ?>
<?php $tpcs=getTopicsForPost((int)$r['id']); if($tpcs): ?><div class="d-flex flex-wrap gap-1 mt-2"><?php foreach(array_slice($tpcs,0,2) as $t): ?><a href="<?= topicUrl($t) ?>" class="badge bg-light text-dark border" style="font-size:.70rem"><?= sanitize($t['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
<div class="news-card-footer"><a href="<?= postUrl($r) ?>" class="btn-read-more">ادامه مطلب <i class="bi bi-arrow-left"></i></a></div>
</div>
</article>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- 2. مقالات و اخبار منتخب -->
<section class="py-5 bg-soft">
<div class="container">
<div class="d-flex justify-content-between align-items-end mb-4">
<div><h2 class="section-title mb-1"><i class="bi bi-journal-text ms-2 text-gold"></i> مقالات و اخبار منتخب</h2><div class="section-divider"></div></div>
<div class="d-flex gap-2"><a href="<?= siteUrl('articles') ?>" class="btn btn-outline-primary btn-sm">مقالات</a><a href="<?= siteUrl('news') ?>" class="btn btn-outline-secondary btn-sm">اخبار</a></div>
</div>
<?php if(empty($featuredEditorial)): ?>
<div class="text-center py-5 text-muted small">مطلب منتخبی ثبت نشده است.</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($featuredEditorial as $a): ?>
<div class="col-md-6 col-lg-4">
<article class="article-card h-100">
<?php if($a['featured_image']): ?><a href="<?= postUrl($a) ?>"><img src="<?= imgUrl($a['featured_image']) ?>" alt="<?= sanitize($a['title']) ?>" class="article-card-img" loading="lazy"></a><?php else: ?><div class="article-card-img-placeholder"><i class="bi bi-file-text"></i></div><?php endif; ?>
<div class="article-card-body">
<div class="article-card-meta"><span class="article-date"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($a['published_at'] ?? $a['created_at']) ?></span><?php if($a['cat_name']): ?><span class="article-cat"><?= sanitize($a['cat_name']) ?></span><?php endif; ?></div>
<h3 class="article-card-title"><a href="<?= postUrl($a) ?>"><?= sanitize($a['title']) ?></a></h3>
<?php if($a['summary']): ?><p class="article-card-summary"><?= sanitize(excerpt($a['summary'],130)) ?></p><?php endif; ?>
<div class="article-card-footer"><a href="<?= postUrl($a) ?>" class="btn-read-more">مطالعه <i class="bi bi-arrow-left"></i></a></div>
</div>
</article>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- 3. پژوهش‌ها -->
<section class="py-5">
<div class="container">
<div class="d-flex justify-content-between align-items-end mb-4">
<div><h2 class="section-title mb-1"><i class="bi bi-search ms-2 text-gold"></i> پژوهش‌ها</h2><div class="section-divider"></div></div>
<a href="<?= siteUrl('research') ?>" class="btn btn-outline-secondary btn-sm">همه پژوهش‌ها <i class="bi bi-arrow-left ms-1"></i></a>
</div>
<?php if(empty($latestResearch)): ?>
<div class="text-center py-5 text-muted small">پژوهشی منتشر نشده است.</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($latestResearch as $a): ?>
<div class="col-md-6 col-lg-4">
<article class="article-card h-100">
<?php if($a['featured_image']): ?><a href="<?= postUrl($a) ?>"><img src="<?= imgUrl($a['featured_image']) ?>" alt="<?= sanitize($a['title']) ?>" class="article-card-img" loading="lazy"></a><?php else: ?><div class="article-card-img-placeholder"><i class="bi bi-journal-richtext"></i></div><?php endif; ?>
<div class="article-card-body">
<div class="article-card-meta"><span class="article-date"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($a['published_at'] ?? $a['created_at']) ?></span></div>
<h3 class="article-card-title"><a href="<?= postUrl($a) ?>"><?= sanitize($a['title']) ?></a></h3>
<?php if($a['summary']): ?><p class="article-card-summary"><?= sanitize(excerpt($a['summary'],120)) ?></p><?php endif; ?>
<div class="article-card-footer"><a href="<?= postUrl($a) ?>" class="btn-read-more">مطالعه <i class="bi bi-arrow-left"></i></a></div>
</div>
</article>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- 4. کتابخانه -->
<section class="py-5 bg-soft">
<div class="container">
<div class="d-flex justify-content-between align-items-end mb-4">
<div><h2 class="section-title mb-1"><i class="bi bi-book ms-2 text-gold"></i> کتابخانه</h2><div class="section-divider"></div></div>
<a href="<?= siteUrl('books') ?>" class="btn btn-outline-secondary btn-sm">همه کتاب‌ها <i class="bi bi-arrow-left ms-1"></i></a>
</div>
<?php if(empty($latestBooks)): ?>
<div class="text-center py-5 text-muted small">کتابی موجود نیست.</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($latestBooks as $b): ?>
<div class="col-6 col-md-3">
<div class="book-card h-100">
<div class="book-card-cover"><?php if($b['cover_image']): ?><img src="<?= imgUrl($b['cover_image']) ?>" alt="<?= sanitize($b['title']) ?>" class="book-cover-img" loading="lazy"><?php else: ?><div class="book-cover-placeholder"><i class="bi bi-book"></i></div><?php endif; ?></div>
<div class="book-card-body">
<h3 class="book-title"><a href="<?= bookUrl($b) ?>"><?= sanitize($b['title']) ?></a></h3>
<?php if($b['author']): ?><small class="text-muted"><i class="bi bi-person ms-1"></i><?= sanitize($b['author']) ?></small><?php endif; ?>
<div class="book-downloads mt-2">
<a href="<?= bookUrl($b) ?>" class="btn btn-outline-primary btn-sm w-100">معرفی و دریافت <i class="bi bi-arrow-left ms-1"></i></a>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- 5. ویدیوها -->
<section class="py-5">
<div class="container">
<div class="jhd-section-heading">
<div><span class="jhd-eyebrow">رسانه</span><h2>ویدیوها</h2><p>ویدیوهای مرتبط با درس‌ها، گزارش‌ها و مقالات</p></div>
<a class="jhd-text-link" href="<?= siteUrl('videos') ?>">همه ویدیوها <i class="bi bi-arrow-left"></i></a>
</div>
<?php if(empty($recentVideos)): ?><div class="text-muted small py-3">ویدیویی یافت نشد.</div><?php else: ?>
<div class="row g-3">
<?php foreach($recentVideos as $v): $vp=$v['file_path'] ?? $v['featured_video'] ?? ''; if(!$vp) continue; $vslug=$v['post_slug'] ?? ''; ?>
<div class="col-6 col-md-3">
<div class="news-card">
<div class="news-card-img-wrap" style="height:140px"><div class="video-thumb h-100" data-video="<?= siteUrl($vp) ?>" data-poster=""><div class="video-thumb__placeholder"><i class="bi bi-camera-video"></i></div><div class="video-play-overlay"><div class="play-btn-circle play-btn-circle--sm"><i class="bi bi-play-fill"></i></div></div></div></div>
<div class="p-2 small fw-bold"><?php if(!empty($v['id']) && $vslug): ?><a href="<?= mediaUrl('video', (int)$v['id']) ?>"><?= sanitize($v['title'] ?? $v['post_title'] ?? 'ویدیو') ?></a><?php elseif($vslug): ?><a href="<?= postUrl($vslug) ?>"><?= sanitize($v['title'] ?? $v['post_title'] ?? 'ویدیو') ?></a><?php else: ?><span><?= sanitize($v['title'] ?? $v['post_title'] ?? 'ویدیو') ?></span><?php endif; ?></div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- 6. صوت‌ها -->
<section class="py-5 bg-soft">
<div class="container">
<div class="jhd-section-heading">
<div><span class="jhd-eyebrow">رسانه</span><h2>صوت‌ها</h2><p>فایل‌های صوتی درس‌ها و سخنرانی‌ها — پخش ساده و سریع</p></div>
<a class="jhd-text-link" href="<?= siteUrl('audios') ?>">همه صوت‌ها <i class="bi bi-arrow-left"></i></a>
</div>
<?php if(empty($recentAudios)): ?><div class="text-muted small py-3">فایل صوتی یافت نشد.</div><?php else: ?>
<div class="list-group">
<?php foreach($recentAudios as $a): $ap=$a['file_path'] ?? $a['audio_file'] ?? ''; $title=$a['title'] ?? $a['lesson_title'] ?? 'صوت'; $slug=$a['lesson_slug'] ?? $a['slug'] ?? ''; $audioUrl=!empty($a['file_path']) && !empty($a['id']) ? mediaUrl('audio', (int)$a['id']) : ($slug ? lessonUrl($slug) : ''); ?>
<?php if($audioUrl): ?><a href="<?= $audioUrl ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
<?php else: ?><span class="list-group-item d-flex align-items-center gap-3">
<?php endif; ?>
<i class="bi bi-headphones text-success" style="font-size:1.2rem"></i><span class="flex-grow-1 fw-bold small"><?= sanitize($title) ?></span><?php if($ap): ?><span class="badge bg-light text-dark border"><i class="bi bi-play"></i> پخش</span><?php endif; ?>
<?php if($audioUrl): ?></a><?php else: ?></span><?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- 7. درس‌ها -->
<section class="py-5">
<div class="container">
<div class="d-flex justify-content-between align-items-end mb-4">
<div><h2 class="section-title mb-1"><i class="bi bi-play-circle ms-2 text-gold"></i> آخرین درس‌ها</h2><div class="section-divider"></div></div>
<a href="<?= siteUrl('lessons') ?>" class="btn btn-outline-secondary btn-sm">همه دروس <i class="bi bi-arrow-left ms-1"></i></a>
</div>
<?php if(empty($latestLessons)): ?>
<div class="text-center py-5 text-muted small">درسی منتشر نشده است.</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($latestLessons as $ls): ?>
<div class="col-md-6 col-lg-3">
<div class="lesson-card h-100">
<div class="lesson-card-img">
<?php if($ls['featured_image']): ?><img src="<?= imgUrl($ls['featured_image']) ?>" alt="<?= sanitize($ls['title']) ?>" loading="lazy"><?php else: ?><div class="lesson-img-placeholder"><i class="bi bi-mortarboard"></i></div><?php endif; ?>
<?php if($ls['audio_file'] || $ls['video_file']): ?><span class="lesson-audio-badge"><i class="bi bi-<?= $ls['video_file']?'camera-video':'headphones' ?>"></i> <?= $ls['video_file']?'ویدیو':'صوت' ?></span><?php endif; ?>
<?php if($ls['is_featured']): ?><span class="lesson-level-badge badge bg-warning text-dark" style="top:8px;right:8px;left:auto">ویژه</span><?php endif; ?>
</div>
<div class="lesson-card-body">
<?php if($ls['collection_title']): ?><span class="lesson-subject"><?= sanitize($ls['collection_title']) ?> <?php if($ls['volume_title']): ?>· <?= sanitize($ls['volume_title']) ?><?php endif; ?></span><?php elseif($ls['subject']): ?><span class="lesson-subject"><?= sanitize($ls['subject']) ?></span><?php endif; ?>
<h4 class="lesson-card-title"><a href="<?= lessonUrl($ls) ?>"><?= sanitize($ls['title']) ?></a></h4>
<?php if($ls['teacher']): ?><p class="lesson-teacher"><i class="bi bi-person ms-1"></i> استاد: <?= sanitize($ls['teacher']) ?> <?php if(!empty($ls['lesson_number'])): ?>· درس <?= (int)$ls['lesson_number'] ?><?php endif; ?></p><?php elseif(!empty($ls['lesson_number'])): ?><p class="lesson-teacher">درس <?= (int)$ls['lesson_number'] ?></p><?php endif; ?>
<a href="<?= lessonUrl($ls) ?>" class="btn btn-sm btn-primary w-100 mt-auto">ورود به درس <i class="bi bi-arrow-left ms-1"></i></a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- 8. موضوعات -->
<section class="py-5" style="background:var(--jhd-surface);border-top:1px solid var(--jhd-border)">
<div class="container">
<div class="jhd-section-heading">
<div><span class="jhd-eyebrow">ستون فقرات محتوا</span><h2>موضوعات</h2><p>هر موضوع، مرکز محتوایی شامل مقالات، گزارش‌ها، کتاب‌ها، دروس و رسانه‌های مرتبط</p></div>
<a class="jhd-text-link" href="<?= siteUrl('topics') ?>">همه موضوعات <i class="bi bi-arrow-left"></i></a>
</div>
<?php if(empty($featuredTopics)): ?>
<div class="text-center py-4 text-muted small">موضوعی ثبت نشده است.</div>
<?php else: ?>
<div class="row g-3 g-md-4">
<?php foreach($featuredTopics as $tp):
    $childCount = count(getTopicChildren((int)$tp['id']));
    $cnt = countPostsByTopic((int)$tp['id']);
?>
<div class="col-6 col-lg-4">
<a href="<?= topicUrl($tp) ?>" class="topic-card h-100 text-decoration-none">
<?php if($tp['cover_image']): ?><img src="<?= imgUrl($tp['cover_image']) ?>" alt="<?= sanitize($tp['name']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:8px" loading="lazy"><?php endif; ?>
<h3><?= sanitize($tp['name']) ?></h3>
<?php if($tp['intro'] ?: $tp['description']): ?><p><?= sanitize(excerpt($tp['intro'] ?: $tp['description'], 110)) ?></p><?php endif; ?>
<span class="topic-meta"><i class="bi bi-collection ms-1"></i><?= number_format($cnt) ?> مطلب <?php if($childCount): ?>· <?= $childCount ?> زیرموضوع<?php endif; ?></span>
</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
