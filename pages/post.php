<?php
/**
 * post.php — صفحه مطلب (گزارش/مقاله/پژوهش/اطلاعیه/خبر) محتوامحور + SEO
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/media.php';
startSecureSession();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    redirect(url('articles'));
}

// Typed URLs (/article/X, /news/X, /research/X, /report/X, ...) resolve here;
// a slug that belongs to another post type is genuinely "not found" under this prefix.
$expectedType = trim($_GET['expected_type'] ?? '');
$normalizedExpected = match ($expectedType) {
    'articles', 'article' => 'article',
    'news' => 'news',
    'researches', 'research' => 'research',
    'reports', 'report' => 'report',
    'announcements', 'announcement' => 'announcement',
    'programs', 'program', 'events', 'event', 'religious' => 'program',
    'speeches', 'speech' => 'speech',
    default => $expectedType,
};

$db = getDB();
$stmt = $db->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, u.full_name AS author_name FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id WHERE (p.slug=? OR p.slug=? OR p.slug=?) AND p.status='published' LIMIT 1");
$stmt->execute([$slug, rawurlencode($slug), urldecode($slug)]);
$post = $stmt->fetch();

// Event types alias check
$isEventMatch = in_array($normalizedExpected, ['program', 'event'], true) && in_array($post['post_type'] ?? '', ['program', 'religious', 'announcement'], true);

if (!$post || ($normalizedExpected !== '' && ($post['post_type'] ?? '') !== $normalizedExpected && !$isEventMatch)) {
    http_response_code(404);
    $pageTitle = 'مطلب یافت نشد';
    $pageDesc = 'مطلب مورد نظر یافت نشد';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5 text-center"><h1>مطلب مورد نظر یافت نشد</h1><p class="text-muted">ممکن است حذف شده یا نشانی نادرست باشد.</p><a href="' . url() . '" class="btn btn-primary mt-3">بازگشت به صفحه اصلی</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if (($post['post_type'] ?? '') === 'speech') {
    redirect(speechUrl($post));
}

$extraImgs = $db->prepare("SELECT * FROM post_images WHERE post_id=?");
$extraImgs->execute([$post['id']]);
$extraImages = $extraImgs->fetchAll();
$postVideos = getMediaFor('post', (int)$post['id'], 'video');
$postAudios = getMediaFor('post', (int)$post['id'], 'audio');

// Topics for this post
$postTopics = getTopicsForPost((int)$post['id']);
$primaryTopic = $postTopics[0] ?? null;

// Related content via shared topic or same type
$related = [];
if ($primaryTopic) {
    $related = getPostsByTopic((int)$primaryTopic['id'], ['limit' => 4]);
    $related = array_filter($related, fn($r) => $r['id'] != $post['id']);
    $related = array_slice($related, 0, 3);
}
if (count($related) < 3) {
    $more = getPosts(['type' => $post['post_type'], 'limit' => 6]);
    $more = array_filter($more, fn($r) => $r['id'] != $post['id'] && !in_array($r['id'], array_column($related, 'id')));
    $related = array_slice(array_merge($related, $more), 0, 3);
}

// Related books/lessons via same topic
$relatedBooks = $primaryTopic ? getBooksByTopic((int)$primaryTopic['id'], 3) : [];
$relatedLessons = $primaryTopic ? getLessonsByTopic((int)$primaryTopic['id'], 3) : [];

$pageTitle = $post['title'];
$pageDesc = $post['summary'] ? excerpt($post['summary'], 160) : excerpt(strip_tags($post['content'] ?? ''), 160);

// Breadcrumbs + JSON-LD
$breadcrumbs = [
    ['name' => 'صفحه اصلی', 'url' => SITE_URL ? rtrim(SITE_URL, '/') . '/' : url()],
];
$typeMap = [
    'news'         => ['label' => 'اخبار',         'url' => url('news')],
    'article'      => ['label' => 'مقالات',       'url' => url('articles')],
    'research'     => ['label' => 'پژوهش‌ها',      'url' => url('research')],
    'report'       => ['label' => 'گزارش‌ها',      'url' => url('reports')],
    'announcement' => ['label' => 'اطلاعیه‌ها',    'url' => url('announcements')],
    'program'      => ['label' => 'رویدادها',      'url' => url('events')],
    'religious'    => ['label' => 'فعالیت مذهبی',  'url' => url('events')],
    'qa'           => ['label' => 'پرسش و پاسخ',   'url' => url('qa')],
];
if (isset($typeMap[$post['post_type']])) {
    $breadcrumbs[] = ['name' => $typeMap[$post['post_type']]['label'], 'url' => $typeMap[$post['post_type']]['url']];
}
if ($primaryTopic) {
    foreach (getTopicBreadcrumbs((int)$primaryTopic['id']) as $bt) {
        $breadcrumbs[] = ['name' => $bt['name'], 'url' => topicUrl($bt)];
    }
}
$canonicalOverride = postUrl($post);
$breadcrumbs[] = ['name' => $post['title'], 'url' => canonicalUrl(postUrl($post))];
$breadcrumbsJsonLd = breadcrumbsJsonLd($breadcrumbs);
$articleJsonLd = articleJsonLd($post);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb-bar"><div class="container"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
<?php foreach($breadcrumbs as $i=>$bc): $isLast = ($i === count($breadcrumbs)-1); ?>
<li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>><?php if(!$isLast): ?><a href="<?= sanitize($bc['url']) ?>"><?= sanitize($bc['name']) ?></a><?php else: ?><?= sanitize(mb_strimwidth($bc['name'], 0, 60, '...')) ?><?php endif; ?></li>
<?php endforeach; ?>
</ol></nav></div></div>

<div class="py-5"><div class="container"><div class="row g-4">
<div class="col-lg-8">
<article class="single-post" itemscope itemtype="https://schema.org/Article">
<header class="single-post-header">
<div class="d-flex flex-wrap gap-2 mb-2">
<?= postTypeBadge($post['post_type']) ?>
<?php if ($post['cat_name']): ?>
<a href="<?= categoryUrl($post['cat_slug']) ?>" class="badge bg-secondary"><?= sanitize($post['cat_name']) ?></a>
<?php endif; ?>
<?php if ($primaryTopic): ?>
<a href="<?= topicUrl($primaryTopic) ?>" class="badge" style="background:#fdf6e3;color:#7a5a1a;border:1px solid #e8d5a3"><i class="bi bi-tag ms-1"></i><?= sanitize($primaryTopic['name']) ?></a>
<?php endif; ?>
</div>
<h1 class="single-post-title mt-2" itemprop="headline"><?= sanitize($post['title']) ?></h1>
<div class="single-post-meta d-flex flex-wrap align-items-center gap-3 mt-3">
<span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($post['published_at'] ?? $post['created_at']) ?></span>
<?php if ($post['author_name']): ?><span><i class="bi bi-person ms-1"></i><?= sanitize($post['author_name']) ?></span><?php endif; ?>
<?php if (!empty($postVideos)): ?><span class="text-danger"><i class="bi bi-camera-video-fill ms-1"></i><?= count($postVideos) ?> ویدیو</span><?php endif; ?>
<?php if (!empty($postAudios)): ?><span class="text-success"><i class="bi bi-headphones ms-1"></i><?= count($postAudios) ?> صوت</span><?php endif; ?>
</div>
<?php if ($postTopics): ?>
<div class="d-flex flex-wrap gap-1 mt-3">
<?php foreach ($postTopics as $t): ?><a href="<?= topicUrl($t) ?>" class="badge bg-light text-dark border" style="font-size:.78rem"><i class="bi bi-folder ms-1"></i><?= sanitize($t['name']) ?></a><?php endforeach; ?>
</div>
<?php endif; ?>
</header>

<?php if ($post['featured_image']): ?>
<div class="single-post-img-wrap"><img src="<?= imgUrl($post['featured_image']) ?>" alt="<?= sanitize($post['title']) ?>" class="single-post-img" loading="lazy" decoding="async" itemprop="image"></div>
<?php endif; ?>

<?php if (!empty($post['featured_video'])): ?>
<div class="featured-video-section my-4">
<h2 class="h5 fw-bold"><i class="bi bi-camera-video-fill ms-2 text-danger"></i> ویدیو شاخص</h2>
<?= renderFeaturedVideo($post['featured_video'], $post['featured_image'] ?? '', 'full') ?>
</div>
<?php endif; ?>

<?php if (!empty($postVideos)): ?>
<div class="media-player-wrap my-4" id="videoSection">
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0 fw-bold"><i class="bi bi-camera-video-fill ms-2 text-danger"></i> ویدیوهای مرتبط</h2><?php if(count($postVideos)>1): ?><span class="badge bg-dark" id="postVideoCounter">۱ / <?= count($postVideos) ?></span><?php endif; ?></div>
<?php $firstVideo=$postVideos[0]; $videoPoster=$post['featured_image'] ? imgUrl($post['featured_image']) : ''; $firstVideoUrl=url($firstVideo['file_path']); ?>
<div class="video-lazy-container" id="videoLazyWrap" data-src="<?= htmlspecialchars($firstVideoUrl, ENT_QUOTES) ?>">
<div class="video-lazy-poster" id="videoPoster" <?= $videoPoster ? '' : 'style="min-height:250px;background:linear-gradient(135deg,#1a1a2e,#16213e)"' ?>>
<?php if($videoPoster): ?><img src="<?= htmlspecialchars($videoPoster, ENT_QUOTES) ?>" alt="<?= sanitize($post['title']) ?>" loading="lazy" decoding="async" style="max-height:420px;width:100%;object-fit:cover;"><?php else: ?><div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:250px;color:#aaa"><i class="bi bi-camera-video" style="font-size:4rem;opacity:.4"></i></div><?php endif; ?>
<div class="video-play-overlay"><div class="video-play-btn"><i class="bi bi-play-fill"></i></div></div>
</div>
<div id="plyrVideoHolder" style="display:none;"><video id="mainPostVideo" controls playsinline preload="none" poster="<?= htmlspecialchars($videoPoster, ENT_QUOTES) ?>" class="w-100"><source src="" data-src="<?= htmlspecialchars($firstVideoUrl, ENT_QUOTES) ?>" type="video/mp4">مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.</video></div>
</div>
<?php if(count($postVideos)>1): ?>
<div class="playlist mt-3" id="postVideoPlaylist"><?php foreach($postVideos as $vi=>$v): ?><button type="button" class="playlist-item <?= $vi===0?'active':'' ?>" data-src="<?= htmlspecialchars(url($v['file_path']), ENT_QUOTES) ?>" data-index="<?= $vi ?>"><span class="pl-num"><?= $vi+1 ?></span><span class="pl-title"><?= sanitize($v['title'] ?: ('ویدیو '.($vi+1))) ?></span><i class="bi bi-play-circle-fill pl-icon"></i></button><?php endforeach; ?></div>
<div class="d-flex gap-2 mt-2"><button type="button" class="btn btn-outline-primary btn-sm" id="postVideoPrev"><i class="bi bi-skip-end-fill"></i> قبلی</button><button type="button" class="btn btn-outline-primary btn-sm" id="postVideoNext">بعدی <i class="bi bi-skip-start-fill"></i></button></div>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($postAudios)): ?>
<div class="media-player-wrap my-4 p-4 bg-soft rounded-xl">
<div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0"><i class="bi bi-headphones ms-2 text-success"></i> فایل‌های صوتی</h2><span class="badge bg-dark" id="audioCounter">۱ / <?= count($postAudios) ?></span></div>
<audio id="mainAudio" controls preload="none" class="w-100"><source data-src="<?= url($postAudios[0]['file_path']) ?>" src="" type="audio/mpeg"></audio>
<div class="d-flex gap-2 mt-2 flex-wrap"><button type="button" class="btn btn-outline-primary btn-sm" id="audioPrevBtn"><i class="bi bi-skip-end-fill"></i> قبلی</button><button type="button" class="btn btn-outline-primary btn-sm" id="audioNextBtn">بعدی <i class="bi bi-skip-start-fill"></i></button><a href="<?= url($postAudios[0]['file_path']) ?>" download id="audioDownload" class="btn btn-outline-success btn-sm"><i class="bi bi-download ms-1"></i>دانلود</a></div>
<?php if(count($postAudios)>1): ?><div class="playlist mt-3" id="audioPlaylist"><?php foreach($postAudios as $ai=>$a): ?><button type="button" class="playlist-item <?= $ai===0?'active':'' ?>" data-src="<?= url($a['file_path']) ?>" data-index="<?= $ai ?>"><span class="pl-num"><?= $ai+1 ?></span><span class="pl-title"><?= sanitize($a['title'] ?: ('صوت '.($ai+1))) ?></span><i class="bi bi-play-circle-fill pl-icon"></i></button><?php endforeach; ?></div><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($post['summary']): ?><div class="single-post-summary" itemprop="description"><p><?= sanitize($post['summary']) ?></p></div><?php endif; ?>

<div class="single-post-content" itemprop="articleBody"><?= safeRichText($post['content']) ?: '<p class="text-muted">محتوایی ثبت نشده است.</p>' ?></div>
<?php if (!empty($post['sources'])): ?>
<div class="mt-4 p-3 rounded-4" style="background:#fafaf7;border:1px solid #e8e6dc">
<h3 class="h6 fw-bold" style="color:var(--jhd-primary)"><i class="bi bi-journal-text ms-2"></i> منابع و مآخذ</h3>
<div style="white-space:pre-wrap;line-height:1.9;color:#3a3a3a;font-size:.93rem"><?= sanitize($post['sources']) ?></div>
</div>
<?php endif; ?>

<?php if (!empty($extraImages)): ?>
<div class="post-gallery mt-4"><h2 class="h5 mb-3"><i class="bi bi-images ms-2"></i> گالری تصاویر</h2><div class="row g-2"><?php foreach($extraImages as $img): ?><div class="col-6 col-md-4 col-lg-3"><a href="<?= imgUrl($img['image_path']) ?>" target="_blank"><img src="<?= imgUrl($img['image_path']) ?>" alt="" class="img-thumbnail w-100" style="height:120px;object-fit:cover" loading="lazy"></a></div><?php endforeach; ?></div></div>
<?php endif; ?>

<!-- پیوندهای هوشمند موضوعی -->
<?php if ($postTopics): ?>
<div class="mt-5 p-3" style="background:#f8f7f2;border:1px solid #e8e0c8;border-radius:12px">
<h3 class="h6 fw-bold mb-3"><i class="bi bi-diagram-3 ms-2 text-gold"></i> موضوعات مرتبط</h3>
<div class="d-flex flex-wrap gap-2 mb-3"><?php foreach($postTopics as $t): ?><a href="<?= topicUrl($t) ?>" class="btn btn-sm" style="background:var(--jhd-surface);border:1px solid var(--jhd-border)"><i class="bi bi-tag ms-1"></i><?= sanitize($t['name']) ?></a><?php endforeach; ?></div>
<?php if (!empty($relatedBooks)): ?>
<h4 class="h6 fw-bold mt-3">کتاب‌های مرتبط با این موضوع</h4><div class="row g-2 mb-3"><?php foreach($relatedBooks as $b): ?><div class="col-6"><a href="<?= bookUrl($b) ?>" class="d-flex gap-2 p-2 border rounded small text-decoration-none"><i class="bi bi-book text-primary" style="font-size:1.2rem"></i><span><?= sanitize(mb_strimwidth($b['title'],0,35,'...')) ?></span></a></div><?php endforeach; ?></div>
<?php endif; ?>
<?php if (!empty($relatedLessons)): ?>
<h4 class="h6 fw-bold">درس‌های مرتبط با این موضوع</h4><div class="row g-2"><?php foreach($relatedLessons as $ls): ?><div class="col-6"><a href="<?= lessonUrl($ls) ?>" class="d-flex gap-2 p-2 border rounded small text-decoration-none"><i class="bi bi-mortarboard text-success"></i><span><?= sanitize(mb_strimwidth($ls['title'],0,35,'...')) ?></span></a></div><?php endforeach; ?></div>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="single-post-share mt-5 p-4 bg-soft rounded-xl">
<h2 class="h5 mb-3"><i class="bi bi-share ms-2 text-gold"></i> اشتراک‌گذاری</h2>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3"><div class="share-url-box flex-grow-1"><input type="text" id="postUrl" class="form-control form-control-sm" value="<?= htmlspecialchars(canonicalUrl(postUrl($post))) ?>" readonly style="direction:ltr;font-size:.82rem"></div><button class="btn btn-primary btn-sm" onclick="copyLink()"><i class="bi bi-clipboard ms-1"></i> کپی پیوند</button></div>
<div class="d-flex gap-2 flex-wrap"><a href="https://t.me/share/url?url=<?= urlencode(canonicalUrl(postUrl($post))) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="btn btn-sm" style="background:#2ca5e0;color:#fff"><i class="bi bi-telegram ms-1"></i> تلگرام</a><a href="https://wa.me/?text=<?= urlencode($post['title'].' - '.canonicalUrl(postUrl($post))) ?>" target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff"><i class="bi bi-whatsapp ms-1"></i> واتساپ</a></div>
<div id="copyMsg" class="text-success small mt-2" style="display:none"><i class="bi bi-check-circle ms-1"></i> پیوند با موفقیت کپی شد!</div>
</div>

</article>
</div>
<div class="col-lg-4">
<div class="sidebar">
<?php if (!empty($related)): ?>
<div class="sidebar-widget"><h2 class="sidebar-title"><i class="bi bi-grid ms-2 text-gold"></i> مطالب مرتبط</h2><div class="related-posts"><?php foreach($related as $r): ?><div class="related-item"><?php if($r['featured_image']): ?><img src="<?= imgUrl($r['featured_image']) ?>" alt="<?= sanitize($r['title']) ?>" class="related-thumb" loading="lazy"><?php else: ?><div class="related-thumb-placeholder"><i class="bi bi-file-text"></i></div><?php endif; ?><div class="related-info"><a href="<?= postUrl($r) ?>" class="related-title"><?= sanitize(mb_strimwidth($r['title'],0,55,'...')) ?></a><span class="related-date"><?= persianDate($r['published_at'] ?? $r['created_at']) ?></span></div></div><?php endforeach; ?></div></div>
<?php endif; ?>
<div class="sidebar-widget"><h2 class="sidebar-title"><i class="bi bi-newspaper ms-2 text-gold"></i> آخرین گزارش‌ها و اخبار</h2><?php $sideNews=getPosts(['type'=>'report','limit'=>3]); if(empty($sideNews)) $sideNews=getPosts(['type'=>'news','limit'=>3]); $sideLatest=getPosts(['type'=>'news','limit'=>3]); $sideCombined=array_merge($sideNews,$sideLatest); ?><ul class="sidebar-list"><?php foreach(array_slice($sideCombined,0,5) as $sn): ?><li><a href="<?= postUrl($sn) ?>"><?= sanitize(mb_strimwidth($sn['title'],0,60,'...')) ?></a><span class="sidebar-date"><?= persianDate($sn['published_at'] ?? $sn['created_at']) ?></span></li><?php endforeach; ?></ul></div>
<div class="sidebar-widget"><h2 class="sidebar-title"><i class="bi bi-tags ms-2 text-gold"></i> موضوعات</h2><div class="d-flex flex-wrap gap-1"><?php foreach(getTopics(['active'=>1,'limit'=>12]) as $ct): ?><a href="<?= topicUrl($ct) ?>" class="badge bg-light text-dark border" style="font-size:.78rem"><?= sanitize($ct['name']) ?></a><?php endforeach; ?></div></div>
</div>
</div>
</div></div></div>

<script>
function copyLink(){var inp=document.getElementById('postUrl'); inp.select(); inp.setSelectionRange(0,99999); try{navigator.clipboard.writeText(inp.value).then(function(){showCopyMsg();});}catch(e){document.execCommand('copy'); showCopyMsg();}}
function showCopyMsg(){var msg=document.getElementById('copyMsg'); if(msg){msg.style.display='block'; setTimeout(function(){msg.style.display='none';},3000);}}
(function(){
    var lazyWrap=document.getElementById('videoLazyWrap'); var poster=document.getElementById('videoPoster'); var holder=document.getElementById('plyrVideoHolder'); var videoEl=document.getElementById('mainPostVideo'); var playlist=document.getElementById('postVideoPlaylist'); var prevBtn=document.getElementById('postVideoPrev'); var nextBtn=document.getElementById('postVideoNext'); var counter=document.getElementById('postVideoCounter'); var plyrInstance=null; var currentIdx=0; var items=playlist?Array.from(playlist.querySelectorAll('.playlist-item')):[];
    function activatePlayer(src, autoplay){
        if(poster) poster.style.display='none'; if(holder) holder.style.display='block';
        var source=videoEl?videoEl.querySelector('source'):null; if(source) source.setAttribute('src',src); if(videoEl) videoEl.setAttribute('src',src);
        if(!plyrInstance && typeof Plyr!=='undefined'){
            plyrInstance=new Plyr(videoEl,{controls:['play-large','play','progress','current-time','duration','mute','volume','fullscreen'],loadSprite:true,iconUrl:document.querySelector('meta[name=plyr-sprite]')?.content,blankVideo:'',autoplay:false,keyboard:{focused:true,global:false},tooltips:{controls:false,seek:true},i18n:{play:'پخش',pause:'مکث',mute:'بی‌صدا',volume:'صدا',enterFullscreen:'تمام‌صفحه',exitFullscreen:'خروج'}});
            if(autoplay) plyrInstance.once('ready', function(){ plyrInstance.play().catch(function(){}); });
        } else if(plyrInstance){ plyrInstance.source={type:'video',sources:[{src:src,type:'video/mp4'}]}; if(autoplay) plyrInstance.play().catch(function(){}); }
        else { if(videoEl){ videoEl.load(); if(autoplay) videoEl.play().catch(function(){}); } }
    }
    function loadPlaylistItem(idx, autoplay){
        if(!items.length) return; if(idx<0) idx=items.length-1; if(idx>=items.length) idx=0; currentIdx=idx; var src=items[idx].getAttribute('data-src'); items.forEach(function(b,i){b.classList.toggle('active',i===idx);}); if(counter) counter.textContent=(idx+1)+' / '+items.length; activatePlayer(src, autoplay);
    }
    if(poster){ poster.addEventListener('click', function(){ var src=lazyWrap?lazyWrap.getAttribute('data-src'):(videoEl?(videoEl.querySelector('source')||{}).getAttribute('data-src'):''); activatePlayer(src,true); }); }
    items.forEach(function(btn,i){ btn.addEventListener('click', function(){ loadPlaylistItem(i,true); }); });
    if(prevBtn) prevBtn.addEventListener('click', function(){ loadPlaylistItem(currentIdx-1,true); });
    if(nextBtn) nextBtn.addEventListener('click', function(){ loadPlaylistItem(currentIdx+1,true); });
})();
</script>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
