<?php
/**
 * post.php — صفحه خبر / مطلب جداگانه + پلیر ویدیو حرفه‌ای + دکمه لایک
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
startSecureSession();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: ' . siteUrl('news.php'));
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, u.full_name AS author_name
    FROM posts p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN users u ON u.id = p.author_id
    WHERE p.slug = ? AND p.status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = 'مطلب یافت نشد';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h2>مطلب مورد نظر یافت نشد</h2><a href="' . siteUrl() . '" class="btn btn-primary mt-3">بازگشت به صفحه اصلی</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// سخنرانی‌ها به صفحه اختصاصی هدایت می‌شوند
if (($post['post_type'] ?? '') === 'speech') {
    redirect(siteUrl('speech.php?slug=' . urlencode($post['slug'])));
}

// افزایش بازدید
$db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);

// تصاویر بیشتر
$extraImgs = $db->prepare("SELECT * FROM post_images WHERE post_id = ?");
$extraImgs->execute([$post['id']]);
$extraImages = $extraImgs->fetchAll();

// رسانه‌های این پست (ویدیو + صوت)
$postVideos = getMediaFor('post', (int)$post['id'], 'video');
$postAudios = getMediaFor('post', (int)$post['id'], 'audio');

// مطالب مرتبط
$related = getPosts(['type' => $post['post_type'], 'limit' => 4]);
$related = array_filter($related, fn($r) => $r['id'] != $post['id']);
$related = array_slice($related, 0, 3);

// وضعیت لایک
$likeCount = getLikeCount((int)$post['id']);
$isLiked   = hasLiked((int)$post['id']);
$likeUrl   = siteUrl('ajax/like.php');

$postUrl  = siteUrl('post.php?slug=' . urlencode($post['slug']));
$pageTitle = $post['title'];
$pageDesc  = $post['summary'] ? excerpt($post['summary'], 200) : '';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
                <?php if ($post['post_type'] === 'news'): ?>
                <li class="breadcrumb-item"><a href="<?= siteUrl('news.php') ?>">اخبار</a></li>
                <?php elseif ($post['post_type'] === 'article'): ?>
                <li class="breadcrumb-item"><a href="<?= siteUrl('articles.php') ?>">مقالات</a></li>
                <?php elseif ($post['post_type'] === 'announcement'): ?>
                <li class="breadcrumb-item"><a href="<?= siteUrl('announcements.php') ?>">اطلاعیه‌ها</a></li>
                <?php elseif ($post['post_type'] === 'speech'): ?>
                <li class="breadcrumb-item"><a href="<?= siteUrl('speeches.php') ?>">سخنرانی‌ها</a></li>
                <?php elseif ($post['post_type'] === 'program'): ?>
                <li class="breadcrumb-item"><a href="<?= siteUrl('programs.php') ?>">برنامه‌ها</a></li>
                <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= siteUrl('news.php') ?>">مطالب</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= sanitize(mb_strimwidth($post['title'], 0, 50, '...')) ?></li>
            </ol>
        </nav>
    </div>
</div>

<main class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- محتوای اصلی -->
            <div class="col-lg-8">
                <article class="single-post">
                    <!-- عنوان و متا -->
                    <header class="single-post-header">
                        <?= postTypeBadge($post['post_type']) ?>
                        <?php if ($post['cat_name']): ?>
                        <a href="<?= siteUrl('category.php?slug=' . urlencode($post['cat_slug'])) ?>" class="badge bg-secondary ms-1"><?= sanitize($post['cat_name']) ?></a>
                        <?php endif; ?>
                        <h1 class="single-post-title mt-3"><?= sanitize($post['title']) ?></h1>
                        <div class="single-post-meta d-flex flex-wrap align-items-center gap-3">
                            <span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($post['published_at'] ?? $post['created_at']) ?></span>
                            <?php if ($post['author_name']): ?>
                            <span><i class="bi bi-person ms-1"></i><?= sanitize($post['author_name']) ?></span>
                            <?php endif; ?>
                            <span><i class="bi bi-eye ms-1"></i><?= number_format($post['views']) ?> بازدید</span>
                            <?php if (!empty($postVideos)): ?>
                            <span class="text-danger"><i class="bi bi-camera-video-fill ms-1"></i><?= count($postVideos) ?> ویدیو</span>
                            <?php endif; ?>
                            <!-- دکمه لایک -->
                            <?= renderLikeButton((int)$post['id'], $likeCount, $isLiked) ?>
                        </div>
                    </header>

                    <!-- تصویر شاخص -->
                    <?php if ($post['featured_image']): ?>
                    <div class="single-post-img-wrap">
                        <img src="<?= imgUrl($post['featured_image']) ?>" alt="<?= sanitize($post['title']) ?>" class="single-post-img" loading="lazy" decoding="async">
                    </div>
                    <?php endif; ?>

                    <!-- ─── ویدیو شاخص (اگر جداگانه آپلود شده باشد) ─────────── -->
                    <?php if (!empty($post['featured_video'])): ?>
                    <div class="featured-video-section my-4">
                        <h5 class="mb-3 fw-bold"><i class="bi bi-camera-video-fill ms-2 text-danger"></i>ویدیو شاخص مطلب</h5>
                        <?= renderFeaturedVideo($post['featured_video'], $post['featured_image'] ?? '', 'full') ?>
                    </div>
                    <?php endif; ?>

                    <!-- ─── پلیر ویدیوی حرفه‌ای (Plyr.js + Lazy Loading) ─────────── -->
                    <?php if (!empty($postVideos)): ?>
                    <div class="media-player-wrap my-4" id="videoSection">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-camera-video-fill ms-2 text-danger"></i>ویدیوی مطلب</h5>
                            <?php if (count($postVideos) > 1): ?>
                            <span class="badge bg-dark" id="postVideoCounter">۱ / <?= count($postVideos) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php
                        $firstVideo   = $postVideos[0];
                        $videoPoster  = $post['featured_image'] ? imgUrl($post['featured_image']) : '';
                        $firstVideoUrl = siteUrl($firstVideo['file_path']);
                        ?>

                        <!-- Lazy Video Container — ویدیو فقط پس از کلیک بارگذاری می‌شود -->
                        <div class="video-lazy-container" id="videoLazyWrap" data-src="<?= htmlspecialchars($firstVideoUrl, ENT_QUOTES) ?>">
                            <!-- Poster / Thumbnail -->
                            <div class="video-lazy-poster" id="videoPoster" <?= $videoPoster ? '' : 'style="min-height:250px;background:linear-gradient(135deg,#1a1a2e,#16213e)"' ?>>
                                <?php if ($videoPoster): ?>
                                <img src="<?= htmlspecialchars($videoPoster, ENT_QUOTES) ?>" alt="<?= sanitize($post['title']) ?>" loading="lazy" decoding="async" style="max-height:420px;width:100%;object-fit:cover;">
                                <?php else: ?>
                                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:250px;color:#aaa">
                                    <i class="bi bi-camera-video" style="font-size:4rem;opacity:.4"></i>
                                </div>
                                <?php endif; ?>
                                <div class="video-play-overlay">
                                    <div class="video-play-btn">
                                        <i class="bi bi-play-fill"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- پلیر اصلی — مخفی است تا کلیک شود -->
                            <div id="plyrVideoHolder" style="display:none;">
                                <video id="mainPostVideo" controls playsinline preload="none"
                                    poster="<?= htmlspecialchars($videoPoster, ENT_QUOTES) ?>"
                                    class="w-100">
                                    <source src="" data-src="<?= htmlspecialchars($firstVideoUrl, ENT_QUOTES) ?>" type="video/mp4">
                                    مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                                </video>
                            </div>
                        </div>

                        <!-- Playlist ویدیو (اگر بیش از یک ویدیو باشد) -->
                        <?php if (count($postVideos) > 1): ?>
                        <div class="playlist mt-3" id="postVideoPlaylist">
                            <?php foreach ($postVideos as $vi => $v): ?>
                            <button type="button"
                                class="playlist-item <?= $vi === 0 ? 'active' : '' ?>"
                                data-src="<?= htmlspecialchars(siteUrl($v['file_path']), ENT_QUOTES) ?>"
                                data-index="<?= $vi ?>">
                                <span class="pl-num"><?= $vi + 1 ?></span>
                                <span class="pl-title"><?= sanitize($v['title'] ?: ('ویدیو ' . ($vi + 1))) ?></span>
                                <i class="bi bi-play-circle-fill pl-icon"></i>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="postVideoPrev"><i class="bi bi-skip-end-fill"></i> قبلی</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="postVideoNext">بعدی <i class="bi bi-skip-start-fill"></i></button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ─── پلیر صوتی ─────────────────────────────────────── -->
                    <?php if (!empty($postAudios)): ?>
                    <div class="media-player-wrap my-4 p-4 bg-soft rounded-xl">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0"><i class="bi bi-headphones ms-2 text-gold"></i>فایل‌های صوتی</h5>
                            <span class="badge bg-dark" id="audioCounter">۱ / <?= count($postAudios) ?></span>
                        </div>
                        <audio id="mainAudio" controls preload="none" class="w-100">
                            <source data-src="<?= siteUrl($postAudios[0]['file_path']) ?>" src="" type="audio/mpeg">
                        </audio>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="audioPrevBtn"><i class="bi bi-skip-end-fill"></i> قبلی</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="audioNextBtn">بعدی <i class="bi bi-skip-start-fill"></i></button>
                            <a href="<?= siteUrl($postAudios[0]['file_path']) ?>" download id="audioDownload" class="btn btn-outline-success btn-sm"><i class="bi bi-download ms-1"></i>دانلود</a>
                        </div>
                        <?php if (count($postAudios) > 1): ?>
                        <div class="playlist mt-3" id="audioPlaylist">
                            <?php foreach ($postAudios as $ai => $a): ?>
                            <button type="button" class="playlist-item <?= $ai === 0 ? 'active' : '' ?>" data-src="<?= siteUrl($a['file_path']) ?>" data-index="<?= $ai ?>">
                                <span class="pl-num"><?= $ai + 1 ?></span>
                                <span class="pl-title"><?= sanitize($a['title'] ?: ('صوت ' . ($ai + 1))) ?></span>
                                <i class="bi bi-play-circle-fill pl-icon"></i>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- خلاصه -->
                    <?php if ($post['summary']): ?>
                    <div class="single-post-summary">
                        <p><?= sanitize($post['summary']) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- محتوا -->
                    <div class="single-post-content">
                        <?= $post['content'] ?: '<p class="text-muted">محتوایی وارد نشده است.</p>' ?>
                    </div>

                    <!-- ─── آمار لایک (نمایش جداگانه تعداد قلب) ─────────── -->
                    <div class="post-stats-bar my-4 p-3 d-flex flex-wrap gap-3 align-items-center" style="background:#f8f9fa;border-radius:12px;border-right:4px solid #e74c3c">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:1.5rem">❤️</span>
                            <div>
                                <div class="fw-bold" style="font-size:1.1rem"><?= number_format($likeCount) ?></div>
                                <div class="text-muted small">تعداد لایک</div>
                            </div>
                        </div>
                        <div class="vr"></div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-eye-fill text-info" style="font-size:1.3rem"></i>
                            <div>
                                <div class="fw-bold" style="font-size:1.1rem"><?= number_format($post['views']) ?></div>
                                <div class="text-muted small">بازدید</div>
                            </div>
                        </div>
                        <?php if (!empty($postVideos) || !empty($post['featured_video'])): ?>
                        <div class="vr"></div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-camera-video-fill text-danger" style="font-size:1.3rem"></i>
                            <div>
                                <div class="fw-bold" style="font-size:1.1rem"><?= count($postVideos) + (!empty($post['featured_video']) ? 1 : 0) ?></div>
                                <div class="text-muted small">ویدیو</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- تصاویر بیشتر -->
                    <?php if (!empty($extraImages)): ?>
                    <div class="post-gallery mt-4">
                        <h5 class="mb-3"><i class="bi bi-images ms-2"></i>گالری تصاویر</h5>
                        <div class="row g-2">
                            <?php foreach ($extraImages as $img): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="<?= imgUrl($img['image_path']) ?>" target="_blank">
                                    <img src="<?= imgUrl($img['image_path']) ?>" alt="" class="img-thumbnail w-100" style="height:120px;object-fit:cover" loading="lazy">
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- اشتراک‌گذاری + لایک -->
                    <div class="single-post-share mt-5 p-4 bg-soft rounded-xl">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <h5 class="mb-0"><i class="bi bi-share ms-2 text-gold"></i>اشتراک‌گذاری این مطلب</h5>
                            <!-- دکمه لایک بزرگ -->
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">آیا این مطلب مفید بود؟</span>
                                <button type="button"
                                    class="btn-like btn-like-lg <?= $isLiked ? 'liked' : '' ?>"
                                    id="mainLikeBtn"
                                    data-post-id="<?= (int)$post['id'] ?>"
                                    data-url="<?= htmlspecialchars($likeUrl, ENT_QUOTES) ?>"
                                    title="<?= $isLiked ? 'لایک را بردار' : 'لایک کن' ?>">
                                    <span class="like-icon" style="font-size:1.3rem"><?= $isLiked ? '❤️' : '🤍' ?></span>
                                    <span class="like-count" style="font-size:1rem;font-weight:700" id="mainLikeCount"><?= $likeCount > 0 ? number_format($likeCount) : '' ?></span>
                                    <span class="like-label ms-1 small"><?= $isLiked ? 'لایک شد' : 'لایک' ?></span>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <div class="share-url-box">
                                <input type="text" id="postUrl" class="form-control form-control-sm" value="<?= htmlspecialchars($postUrl) ?>" readonly style="direction:ltr;font-size:.82rem">
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="copyLink()">
                                <i class="bi bi-clipboard ms-1"></i>کپی لینک
                            </button>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="https://t.me/share/url?url=<?= urlencode($postUrl) ?>&text=<?= urlencode($post['title']) ?>"
                               target="_blank" class="btn btn-sm" style="background:#2ca5e0;color:#fff">
                                <i class="bi bi-telegram ms-1"></i>تلگرام
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' - ' . $postUrl) ?>"
                               target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff">
                                <i class="bi bi-whatsapp ms-1"></i>واتساپ
                            </a>
                        </div>
                        <div id="copyMsg" class="text-success small mt-2" style="display:none"><i class="bi bi-check-circle ms-1"></i>لینک کپی شد!</div>
                    </div>
                </article>
            </div>

            <!-- ستون کناری -->
            <div class="col-lg-4">
                <div class="sidebar">
                    <!-- مطالب مرتبط -->
                    <?php if (!empty($related)): ?>
                    <div class="sidebar-widget">
                        <h5 class="sidebar-title"><i class="bi bi-grid ms-2 text-gold"></i>مطالب مرتبط</h5>
                        <div class="related-posts">
                            <?php foreach ($related as $r): ?>
                            <div class="related-item">
                                <?php if ($r['featured_image']): ?>
                                <img src="<?= imgUrl($r['featured_image']) ?>" alt="<?= sanitize($r['title']) ?>" class="related-thumb" loading="lazy">
                                <?php else: ?>
                                <div class="related-thumb-placeholder"><i class="bi bi-file-text"></i></div>
                                <?php endif; ?>
                                <div class="related-info">
                                    <a href="<?= siteUrl('post.php?slug=' . urlencode($r['slug'])) ?>" class="related-title"><?= sanitize(mb_strimwidth($r['title'], 0, 55, '...')) ?></a>
                                    <span class="related-date"><?= persianDate($r['published_at'] ?? $r['created_at']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- آخرین اخبار -->
                    <div class="sidebar-widget">
                        <h5 class="sidebar-title"><i class="bi bi-newspaper ms-2 text-gold"></i>آخرین اخبار</h5>
                        <?php $sideNews = getPosts(['limit' => 5, 'type' => 'news']); ?>
                        <ul class="sidebar-list">
                            <?php foreach ($sideNews as $sn): ?>
                            <li>
                                <a href="<?= siteUrl('post.php?slug=' . urlencode($sn['slug'])) ?>"><?= sanitize(mb_strimwidth($sn['title'], 0, 55, '...')) ?></a>
                                <span class="sidebar-date"><?= persianDate($sn['published_at'] ?? $sn['created_at']) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- دسته‌بندی‌ها -->
                    <div class="sidebar-widget">
                        <h5 class="sidebar-title"><i class="bi bi-grid ms-2 text-gold"></i>دسته‌بندی‌ها</h5>
                        <ul class="sidebar-cats">
                            <?php foreach (getCategories() as $cat): ?>
                            <?php if ($cat['post_count'] > 0): ?>
                            <li>
                                <a href="<?= siteUrl('category.php?slug=' . urlencode($cat['slug'])) ?>">
                                    <?= sanitize($cat['name']) ?>
                                    <span class="badge bg-light text-dark float-start"><?= $cat['post_count'] ?></span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
/* ─── کپی لینک ─────────────────────────────────────── */
function copyLink() {
    var inp = document.getElementById('postUrl');
    inp.select();
    inp.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(inp.value).then(function() { showCopyMsg(); });
    } catch(e) {
        document.execCommand('copy');
        showCopyMsg();
    }
}
function showCopyMsg() {
    var msg = document.getElementById('copyMsg');
    if (msg) { msg.style.display = 'block'; setTimeout(function(){ msg.style.display='none'; }, 3000); }
}

/* ─── Plyr ویدیوپلیر + Lazy Loading ───────────────── */
(function() {
    var lazyWrap   = document.getElementById('videoLazyWrap');
    var poster     = document.getElementById('videoPoster');
    var holder     = document.getElementById('plyrVideoHolder');
    var videoEl    = document.getElementById('mainPostVideo');
    var playlist   = document.getElementById('postVideoPlaylist');
    var prevBtn    = document.getElementById('postVideoPrev');
    var nextBtn    = document.getElementById('postVideoNext');
    var counter    = document.getElementById('postVideoCounter');
    var plyrInstance = null;
    var currentIdx = 0;
    var items      = playlist ? Array.from(playlist.querySelectorAll('.playlist-item')) : [];

    function activatePlayer(src, autoplay) {
        if (poster)  poster.style.display  = 'none';
        if (holder)  holder.style.display  = 'block';

        // بارگذاری منبع
        var source = videoEl ? videoEl.querySelector('source') : null;
        if (source) { source.setAttribute('src', src); }
        if (videoEl)  videoEl.setAttribute('src', src);

        if (!plyrInstance && typeof Plyr !== 'undefined') {
            plyrInstance = new Plyr(videoEl, {
                controls: ['play-large','play','progress','current-time','duration','mute','volume','fullscreen'],
                loadSprite: false,
                iconUrl: '',
                blankVideo: '',
                autoplay: false,
                resetOnEnd: false,
                keyboard: { focused: true, global: false },
                tooltips: { controls: false, seek: true },
                i18n: {
                    play: 'پخش', pause: 'مکث', mute: 'بی‌صدا',
                    volume: 'صدا', enterFullscreen: 'تمام‌صفحه', exitFullscreen: 'خروج',
                },
            });
            if (autoplay) {
                plyrInstance.once('ready', function() {
                    plyrInstance.play().catch(function(){});
                });
            }
        } else if (plyrInstance) {
            plyrInstance.source = { type: 'video', sources: [{ src: src, type: 'video/mp4' }] };
            if (autoplay) plyrInstance.play().catch(function(){});
        } else {
            // Fallback اگر Plyr بارگذاری نشد
            if (videoEl) {
                videoEl.load();
                if (autoplay) videoEl.play().catch(function(){});
            }
        }
    }

    function loadPlaylistItem(idx, autoplay) {
        if (!items.length) return;
        if (idx < 0) idx = items.length - 1;
        if (idx >= items.length) idx = 0;
        currentIdx = idx;
        var src = items[idx].getAttribute('data-src');
        items.forEach(function(b, i) { b.classList.toggle('active', i === idx); });
        if (counter) counter.textContent = (idx + 1) + ' / ' + items.length;
        activatePlayer(src, autoplay);
    }

    // کلیک روی پوستر — Lazy load
    if (poster) {
        poster.addEventListener('click', function() {
            var src = lazyWrap ? lazyWrap.getAttribute('data-src') : (videoEl ? (videoEl.querySelector('source')||{}).getAttribute('data-src') : '');
            activatePlayer(src, true);
        });
    }

    // دکمه‌های playlist
    items.forEach(function(btn, i) {
        btn.addEventListener('click', function() { loadPlaylistItem(i, true); });
    });
    if (prevBtn) prevBtn.addEventListener('click', function() { loadPlaylistItem(currentIdx - 1, true); });
    if (nextBtn) nextBtn.addEventListener('click', function() { loadPlaylistItem(currentIdx + 1, true); });

    // Intersection Observer — پیش‌بارگذاری metadata هنگام ورود به نما
    if ('IntersectionObserver' in window && lazyWrap) {
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && videoEl && videoEl.preload === 'none') {
                    videoEl.preload = 'metadata';
                    io.unobserve(lazyWrap);
                }
            });
        }, { rootMargin: '200px' });
        io.observe(lazyWrap);
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
