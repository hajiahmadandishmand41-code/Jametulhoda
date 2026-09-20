<?php
/**
 * index.php — صفحه اصلی جامعه‌الهدی
 * بازنویسی‌شده: معرفی مدرسه + اخبار + مقالات + سخنرانی‌ها + درس‌ها + کتاب‌ها
 */
$pageTitle = '';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

startSecureSession();

$db = getDB();
ensureSpeakerColumn();

// ─── داده‌ها ──────────────────────────────────────────────────────────────────

// اخبار برجسته برای اسلایدر
$featured = getPosts(['limit' => 5, 'featured' => 1, 'section' => 'home']);

// آخرین اخبار
$latestNews = getPosts(['limit' => 5, 'type' => 'news', 'section' => 'home']);

// آخرین مقالات
$latestArticles = getPosts(['limit' => 5, 'type' => 'article', 'section' => 'home']);

// آخرین سخنرانی‌ها
$latestSpeeches = getPosts(['limit' => 5, 'type' => 'speech', 'section' => 'home']);

// آخرین درس‌ها
$latestLessons = [];
try {
    ensureLessonsColumns();
    $lStmt = $db->prepare(
        "SELECT * FROM lessons
         WHERE status='published'
           AND (page_section IS NULL OR page_section = '' OR FIND_IN_SET('home', REPLACE(REPLACE(page_section,' ',''),',,',',')))
         ORDER BY id DESC LIMIT 5"
    );
    $lStmt->execute();
    $latestLessons = $lStmt->fetchAll();
} catch (PDOException $e) {}

// آخرین کتاب‌ها
$latestBooks = [];
try {
    ensureBooksTable();
    $bStmt = $db->prepare("SELECT * FROM books ORDER BY created_at DESC LIMIT 5");
    $bStmt->execute();
    $latestBooks = $bStmt->fetchAll();
} catch (PDOException $e) {}

// لایک‌ها (bulk — بدون N+1)
$newsLikeMap    = getLikeCountsBulk(array_column($latestNews,     'id'));
$artLikeMap     = getLikeCountsBulk(array_column($latestArticles, 'id'));
$speechLikeMap  = getLikeCountsBulk(array_column($latestSpeeches, 'id'));
$featLikeMap    = getLikeCountsBulk(array_column($featured,       'id'));

$likedInSession = $_SESSION['liked_posts'] ?? [];
$likeUrl        = siteUrl('ajax/like.php');

// ─── تابع رندر تصویر/ویدیو کارت ──────────────────────────────────────────────
function renderCardMedia(array $post, string $size = 'card'): string {
    $hasImg  = !empty($post['featured_image']);
    $hasVid  = !empty($post['featured_video']);
    $imgUrl  = $hasImg ? imgUrl($post['featured_image']) : '';
    $vidUrl  = $hasVid ? siteUrl($post['featured_video']) : '';
    $altText = sanitize($post['title']);

    if ($hasVid) {
        $posterAttr = $imgUrl ? ' poster="'.htmlspecialchars($imgUrl,ENT_QUOTES).'"' : '';
        $inner = $hasImg
            ? '<img src="'.htmlspecialchars($imgUrl,ENT_QUOTES).'" alt="'.$altText.'" class="video-thumb__poster" loading="lazy">'
            : '<video class="video-thumb__native" src="'.htmlspecialchars($vidUrl,ENT_QUOTES).'" preload="metadata" muted playsinline'.$posterAttr.'></video>';
        $btnSize  = $size === 'hero' ? '' : ' play-btn-circle--sm';
        $sizeClass = $size === 'hero' ? 'hero-media-wrap' : 'video-thumb';
        return sprintf(
            '<div class="%s" data-video="%s" data-poster="%s" role="button" tabindex="0" title="پخش ویدیو">%s<div class="video-play-overlay"><div class="play-btn-circle%s"><i class="bi bi-play-fill"></i></div></div><span class="video-badge-card"><i class="bi bi-camera-video-fill"></i> ویدیو</span></div>',
            $sizeClass, htmlspecialchars($vidUrl,ENT_QUOTES), htmlspecialchars($imgUrl,ENT_QUOTES), $inner, $btnSize
        );
    }
    if ($hasImg) {
        return sprintf('<img src="%s" alt="%s" class="%s" loading="lazy" decoding="async">',
            htmlspecialchars($imgUrl,ENT_QUOTES), $altText,
            $size === 'hero' ? 'd-block w-100 hero-img' : 'news-card-img');
    }
    $icon = match($post['post_type'] ?? 'news') {
        'article' => 'bi-file-text', 'speech' => 'bi-mic',
        'announcement' => 'bi-megaphone', default => 'bi-newspaper',
    };
    return sprintf('<div class="%s"><i class="bi %s"></i></div>',
        $size === 'hero' ? 'hero-placeholder' : 'news-card-img-placeholder', $icon);
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ─── اسلایدر اخبار برجسته ─────────────────────────────────────────────── -->
<?php if (!empty($featured)): ?>
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5500">
        <div class="carousel-indicators">
            <?php foreach ($featured as $i => $item): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>"
                    <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="اسلاید <?= $i+1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($featured as $i => $item):
                $fLiked = in_array((int)$item['id'], $likedInSession, true);
                $fCount = $featLikeMap[(int)$item['id']] ?? 0;
                $hasVid = !empty($item['featured_video']);
                $vidUrl = $hasVid ? siteUrl($item['featured_video']) : '';
                $poster = !empty($item['featured_image']) ? imgUrl($item['featured_image']) : '';
                $fIcon  = $fLiked ? '<i class="bi bi-heart-fill like-icon text-danger"></i>' : '<i class="bi bi-heart like-icon"></i>';
            ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <?php if ($hasVid && $i === 0): ?>
                <div class="hero-autoplay-wrap" style="position:relative;overflow:hidden;">
                    <video class="d-block w-100 hero-img" id="heroAutoVideo" src="<?= htmlspecialchars($vidUrl,ENT_QUOTES) ?>"
                           autoplay muted playsinline preload="auto" loop
                           <?= $poster ? 'poster="'.htmlspecialchars($poster,ENT_QUOTES).'"' : '' ?> style="object-fit:cover;"></video>
                    <span class="hero-video-badge"><i class="bi bi-camera-video-fill"></i> ویدیو</span>
                </div>
                <?php elseif ($hasVid): ?>
                <div class="hero-media-wrap" data-video="<?= htmlspecialchars($vidUrl,ENT_QUOTES) ?>" data-poster="<?= htmlspecialchars($poster,ENT_QUOTES) ?>" role="button" tabindex="0">
                    <?= $poster ? '<img src="'.$poster.'" class="d-block w-100 hero-img" alt="'.sanitize($item['title']).'" loading="lazy">' : '<video class="d-block w-100 hero-img" src="'.htmlspecialchars($vidUrl,ENT_QUOTES).'" preload="metadata" muted playsinline></video>' ?>
                    <div class="video-play-overlay"><div class="play-btn-circle"><i class="bi bi-play-fill"></i></div></div>
                    <span class="hero-video-badge"><i class="bi bi-camera-video-fill"></i> ویدیو</span>
                </div>
                <?php elseif (!empty($item['featured_image'])): ?>
                <img src="<?= imgUrl($item['featured_image']) ?>" class="d-block w-100 hero-img" alt="<?= sanitize($item['title']) ?>" loading="<?= $i===0?'eager':'lazy' ?>">
                <?php else: ?>
                <div class="hero-placeholder d-flex align-items-center justify-content-center"><i class="bi bi-newspaper text-white" style="font-size:5rem;opacity:.25"></i></div>
                <?php endif; ?>
                <div class="carousel-caption">
                    <div class="caption-inner">
                        <?= postTypeBadge($item['post_type']) ?>
                        <h2 class="caption-title"><?= sanitize($item['title']) ?></h2>
                        <?php if (!empty($item['summary'])): ?>
                        <p class="caption-summary d-none d-md-block"><?= sanitize(excerpt($item['summary'], 120)) ?></p>
                        <?php endif; ?>
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mt-3">
                            <a href="<?= siteUrl('post.php?slug=' . urlencode($item['slug'])) ?>" class="btn btn-gold">
                                <i class="bi bi-arrow-left ms-2"></i>ادامه مطلب
                            </a>
                            <button type="button" class="btn-like <?= $fLiked ? 'liked' : '' ?>"
                                    data-post-id="<?= (int)$item['id'] ?>" data-url="<?= htmlspecialchars($likeUrl,ENT_QUOTES) ?>"
                                    style="background:rgba(255,255,255,.95)">
                                <?= $fIcon ?><span class="like-count"><?= $fCount > 0 ? number_format($fCount) : '' ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span><span class="visually-hidden">قبلی</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span><span class="visually-hidden">بعدی</span>
        </button>
    </div>
</section>
<?php else: ?>
<section class="hero-plain py-5 text-center" style="background:linear-gradient(135deg,#1a3a2a,#0d2018)">
    <div class="container">
        <h1 class="text-white fw-bold"><?= sanitize(getSetting('site_name', SITE_NAME)) ?></h1>
        <p class="text-light opacity-75 mt-2"><?= sanitize(getSetting('site_slogan', SITE_SLOGAN)) ?></p>
        <a href="<?= siteUrl('about.php') ?>" class="btn btn-gold mt-3">درباره ما <i class="bi bi-arrow-left ms-1"></i></a>
    </div>
</section>
<?php endif; ?>


<!-- ─── معرفی مدرسه ────────────────────────────────────────────────────────── -->
<?php
$aboutTitle = getSetting('about_title', 'درباره مدرسه علمیه جامعه‌الهدی');
$aboutText  = getSetting('about_text',  'مدرسه علمیه جامعه‌الهدی یکی از مراکز معتبر آموزش علوم اسلامی در افغانستان است که با هدف تربیت عالمان دینی و ترویج معارف اهل‌بیت (ع) فعالیت می‌نماید.');
$aboutImg   = getSetting('about_image', '');
?>
<section class="section-about py-5" style="background:#fff;">
    <div class="container">
        <div class="row g-4 align-items-center">
            <?php if ($aboutImg): ?>
            <div class="col-lg-4">
                <img src="<?= imgUrl($aboutImg) ?>" alt="مدرسه جامعه‌الهدی" class="img-fluid rounded-4 shadow" style="max-height:300px;width:100%;object-fit:cover">
            </div>
            <?php endif; ?>
            <div class="<?= $aboutImg ? 'col-lg-8' : 'col-12 text-center' ?>">
                <h2 class="section-title mb-3"><i class="bi bi-building ms-2 text-gold"></i><?= sanitize($aboutTitle) ?></h2>
                <div class="section-divider mb-3"></div>
                <p class="text-secondary lh-lg" style="font-size:1.05rem"><?= nl2br(sanitize($aboutText)) ?></p>
                <a href="<?= siteUrl('about.php') ?>" class="btn btn-outline-primary mt-2">
                    بیشتر بدانید <i class="bi bi-arrow-left ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ─── آخرین اخبار ──────────────────────────────────────────────────────── -->
<?php if (!empty($latestNews)): ?>
<section class="section-news py-5 bg-soft">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title"><i class="bi bi-newspaper ms-2 text-gold"></i>آخرین اخبار</h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= siteUrl('news.php') ?>" class="btn btn-outline-primary btn-sm">
                همه اخبار <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php foreach ($latestNews as $news):
                $nid    = (int)$news['id'];
                $nCount = $newsLikeMap[$nid] ?? 0;
                $nLiked = in_array($nid, $likedInSession, true);
                $nIcon  = $nLiked ? '<i class="bi bi-heart-fill like-icon text-danger"></i>' : '<i class="bi bi-heart like-icon"></i>';
            ?>
            <div class="col-md-6 col-lg-4">
                <article class="news-card h-100">
                    <div class="news-card-img-wrap position-relative">
                        <?= renderCardMedia($news, 'card') ?>
                        <div class="news-card-badge"><?= postTypeBadge($news['post_type']) ?></div>
                    </div>
                    <div class="news-card-body">
                        <div class="news-card-meta">
                            <span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($news['published_at'] ?? $news['created_at']) ?></span>
                        </div>
                        <h3 class="news-card-title">
                            <a href="<?= siteUrl('post.php?slug=' . urlencode($news['slug'])) ?>"><?= sanitize($news['title']) ?></a>
                        </h3>
                        <?php if (!empty($news['summary'])): ?>
                        <p class="news-card-summary"><?= sanitize(excerpt($news['summary'], 120)) ?></p>
                        <?php endif; ?>
                        <div class="news-card-footer">
                            <a href="<?= siteUrl('post.php?slug=' . urlencode($news['slug'])) ?>" class="btn-read-more">ادامه مطلب <i class="bi bi-arrow-left"></i></a>
                            <button type="button" class="btn-like <?= $nLiked ? 'liked' : '' ?>"
                                    data-post-id="<?= $nid ?>" data-url="<?= htmlspecialchars($likeUrl,ENT_QUOTES) ?>">
                                <?= $nIcon ?><span class="like-count"><?= $nCount > 0 ? number_format($nCount) : '' ?></span>
                            </button>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- ─── آخرین مقالات ──────────────────────────────────────────────────────── -->
<?php if (!empty($latestArticles)): ?>
<section class="section-articles py-5">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title"><i class="bi bi-file-text ms-2 text-gold"></i>آخرین مقالات</h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= siteUrl('articles.php') ?>" class="btn btn-outline-primary btn-sm">
                همه مقالات <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>
        <div class="list-posts">
            <?php foreach ($latestArticles as $art):
                $aid    = (int)$art['id'];
                $aCount = $artLikeMap[$aid] ?? 0;
                $aLiked = in_array($aid, $likedInSession, true);
                $aIcon  = $aLiked ? '<i class="bi bi-heart-fill like-icon text-danger"></i>' : '<i class="bi bi-heart like-icon"></i>';
            ?>
            <div class="list-post-item">
                <div class="list-post-thumb-wrap" style="flex-shrink:0">
                    <div class="list-post-thumb-placeholder"><i class="bi bi-file-text"></i></div>
                </div>
                <div class="list-post-info">
                    <a href="<?= siteUrl('post.php?slug=' . urlencode($art['slug'])) ?>" class="list-post-title"><?= sanitize($art['title']) ?></a>
                    <?php if (!empty($art['cat_name'])): ?>
                    <span class="badge bg-primary-subtle text-primary small ms-2"><?= sanitize($art['cat_name']) ?></span>
                    <?php endif; ?>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                        <span class="list-post-date"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($art['published_at'] ?? $art['created_at']) ?></span>
                        <button type="button" class="btn-like <?= $aLiked ? 'liked' : '' ?>"
                                data-post-id="<?= $aid ?>" data-url="<?= htmlspecialchars($likeUrl,ENT_QUOTES) ?>">
                            <?= $aIcon ?><span class="like-count"><?= $aCount > 0 ? number_format($aCount) : '' ?></span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- ─── آخرین سخنرانی‌ها ──────────────────────────────────────────────────── -->
<?php if (!empty($latestSpeeches)): ?>
<section class="section-speeches py-5 bg-soft">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title"><i class="bi bi-mic ms-2 text-gold"></i>آخرین سخنرانی‌ها</h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= siteUrl('speeches.php') ?>" class="btn btn-outline-primary btn-sm">
                همه سخنرانی‌ها <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php foreach ($latestSpeeches as $speech):
                $sid    = (int)$speech['id'];
                $sCount = $speechLikeMap[$sid] ?? 0;
                $sLiked = in_array($sid, $likedInSession, true);
                $sIcon  = $sLiked ? '<i class="bi bi-heart-fill like-icon text-danger"></i>' : '<i class="bi bi-heart like-icon"></i>';
            ?>
            <div class="col-md-6 col-lg-4">
                <article class="news-card h-100">
                    <div class="news-card-img-wrap position-relative">
                        <?= renderCardMedia($speech, 'card') ?>
                        <div class="news-card-badge"><span class="badge bg-info text-white"><i class="bi bi-mic"></i> سخنرانی</span></div>
                    </div>
                    <div class="news-card-body">
                        <h3 class="news-card-title">
                            <a href="<?= siteUrl('speech.php?slug=' . urlencode($speech['slug'])) ?>"><?= sanitize($speech['title']) ?></a>
                        </h3>
                        <?php if (!empty($speech['speaker'])): ?>
                        <p class="text-muted small mb-2"><i class="bi bi-person-fill ms-1"></i><?= sanitize($speech['speaker']) ?></p>
                        <?php endif; ?>
                        <div class="news-card-footer">
                            <a href="<?= siteUrl('speech.php?slug=' . urlencode($speech['slug'])) ?>" class="btn-read-more">مشاهده <i class="bi bi-arrow-left"></i></a>
                            <button type="button" class="btn-like <?= $sLiked ? 'liked' : '' ?>"
                                    data-post-id="<?= $sid ?>" data-url="<?= htmlspecialchars($likeUrl,ENT_QUOTES) ?>">
                                <?= $sIcon ?><span class="like-count"><?= $sCount > 0 ? number_format($sCount) : '' ?></span>
                            </button>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- ─── آخرین درس‌ها ─────────────────────────────────────────────────────── -->
<?php if (!empty($latestLessons)): ?>
<section class="section-lessons py-5">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title"><i class="bi bi-play-circle-fill ms-2 text-gold"></i>آخرین درس‌ها</h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= siteUrl('lessons.php') ?>" class="btn btn-outline-primary btn-sm">
                همه درس‌ها <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php foreach ($latestLessons as $lesson): ?>
            <div class="col-md-6 col-lg-4">
                <div class="lesson-card h-100">
                    <div class="lesson-card-img">
                        <?php if (!empty($lesson['featured_image'])): ?>
                        <img src="<?= imgUrl($lesson['featured_image']) ?>" alt="<?= sanitize($lesson['title']) ?>" class="lesson-card-img-el" loading="lazy">
                        <?php else: ?>
                        <div class="lesson-img-placeholder d-flex align-items-center justify-content-center">
                            <i class="bi bi-play-circle" style="font-size:3rem;color:rgba(255,255,255,.3)"></i>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($lesson['audio_file'])): ?>
                        <span class="lesson-audio-badge"><i class="bi bi-headphones"></i> صوتی</span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-card-body">
                        <?php if (!empty($lesson['subject'])): ?>
                        <span class="lesson-subject"><?= sanitize($lesson['subject']) ?></span>
                        <?php endif; ?>
                        <h4 class="lesson-card-title">
                            <a href="<?= siteUrl('lesson.php?slug=' . urlencode($lesson['slug'])) ?>"><?= sanitize($lesson['title']) ?></a>
                        </h4>
                        <?php if (!empty($lesson['teacher'])): ?>
                        <p class="lesson-teacher"><i class="bi bi-person-fill ms-1"></i><?= sanitize($lesson['teacher']) ?></p>
                        <?php endif; ?>
                        <a href="<?= siteUrl('lesson.php?slug=' . urlencode($lesson['slug'])) ?>" class="btn btn-sm btn-primary w-100">مشاهده درس</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- ─── آخرین کتاب‌ها ────────────────────────────────────────────────────── -->
<?php if (!empty($latestBooks)): ?>
<section class="section-books py-5 bg-soft">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title"><i class="bi bi-book ms-2 text-gold"></i>آخرین کتاب‌ها</h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= siteUrl('books.php') ?>" class="btn btn-outline-primary btn-sm">
                همه کتاب‌ها <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php foreach ($latestBooks as $book): ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="book-card h-100 text-center">
                    <?php if (!empty($book['cover_image'])): ?>
                    <a href="<?= siteUrl('book.php?id=' . (int)$book['id']) ?>">
                        <img src="<?= imgUrl($book['cover_image']) ?>" alt="<?= sanitize($book['title']) ?>"
                             class="book-card-cover" loading="lazy">
                    </a>
                    <?php else: ?>
                    <div class="book-cover-placeholder"><i class="bi bi-book" style="font-size:2rem;opacity:.3"></i></div>
                    <?php endif; ?>
                    <div class="book-card-body p-2">
                        <h5 class="book-card-title">
                            <a href="<?= siteUrl('book.php?id=' . (int)$book['id']) ?>"><?= sanitize(mb_strimwidth($book['title'], 0, 50, '...')) ?></a>
                        </h5>
                        <?php if (!empty($book['pdf_file'])): ?>
                        <a href="<?= siteUrl('book.php?id=' . (int)$book['id'] . '&download=pdf') ?>" class="btn btn-sm btn-outline-success w-100 mt-1">
                            <i class="bi bi-download ms-1"></i>دانلود PDF
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
