<?php
/**
 * index.php — صفحه اصلی پورتال مدرسه علمیه جامعه‌الهدی
 * ساختار بصری غنی و سلسله‌مراتب استاندارد سرمقاله‌ای (RTL):
 * ۱. Hero ویژه با تصویر شاخص و متادیتا
 * ۲. بنر اعلان ویژه (در صورت فعال بودن)
 * ۳. تازه‌ترین اخبار حوزه (School News)
 * ۴. مقالات علمی و یادداشت‌ها (Articles) [بخش کلیدی]
 * ۵. گزارش‌ها و رویدادهای تصویری (Reports) [بخش کلیدی]
 * ۶. موضوعات مهم و اطلس معارف (Key Topics) [بخش کلیدی]
 * ۷. برنامه‌ها و رویدادهای مذهبی (Events)
 * ۸. کتابخانه دیجیتال (Digital Library)
 * ۹. دروس حوزوی و صوت جلسات (Seminary Lessons)
 * ۱۰. نگارخانه چندرسانه‌ای و سخنرانی‌ها (Media & Videos)
 */
$pageTitle = '';
$pageDesc = 'پرتال علمی، آموزشی و پژوهشی جامعة‌الهدی؛ دسترسی به اخبار، مقالات، گزارش‌ها، کتاب‌ها، درس‌ها و موضوعات علوم اسلامی.';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

// ─── Query-URL front controller ────────────────────────────────────────────
// index.php?p=news / index.php?p=topic&slug=x must work even when mod_rewrite
// is unavailable (InfinityFree). This is the same allowlist dispatch router.php
// uses for ?p=, so both entry points converge on one controller per route and
// nothing is ever include()d from user input. No ?p= → fall through to home.
$__p = (isset($_GET['p']) && is_string($_GET['p'])) ? trim($_GET['p']) : '';
if ($__p !== '') {
    $__resolved = jhd_resolve_query($__p, $_GET);
    if ($__resolved === null) {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width"><title>۴۰۴ — صفحه پیدا نشد</title>'
            . '<style>body{font-family:Tahoma,system-ui,sans-serif;background:#f6f7f4;color:#182d39;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center}'
            . 'main{max-width:520px;padding:32px;background:#fff;border:1px solid #e2e6e2;border-radius:16px;text-align:center;line-height:2}'
            . 'a{color:#245c4c}h1{font-size:1.4rem;margin:.4rem 0}</style>'
            . '<main><div style="font-size:3rem;font-weight:900;color:#245c4c">۴۰۴</div>'
            . '<h1>صفحه مورد نظر یافت نشد</h1><p class="text-muted">نشانی وارد شده معتبر نیست.</p>'
            . '<p><a href="' . htmlspecialchars(url(), ENT_QUOTES, 'UTF-8') . '">بازگشت به صفحه اصلی</a></p></main></html>';
        exit;
    }
    foreach ($__resolved['get'] as $__k => $__v) $_GET[$__k] ??= $__v;
    if (!empty($__resolved['expected_type'])) $_GET['expected_type'] = $__resolved['expected_type'];
    if (!empty($__resolved['kind'])) $_GET['kind'] = $__resolved['kind'];
    $_SERVER['JHD_ROUTE_NAME'] = $__p;
    $_SERVER['JHD_ROUTE_PATH'] = BASE_PATH . jhd_route_path($__p, $_GET);
    $__file = realpath(__DIR__ . '/' . $__resolved['file']);
    if ($__file !== false && str_starts_with($__file, realpath(__DIR__) . DIRECTORY_SEPARATOR) && is_file($__file)) {
        require $__file;
        exit;
    }
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>۴۰۴</title>'
        . '<main style="font-family:Tahoma;padding:40px;text-align:center">صفحه پیدا نشد — '
        . '<a href="' . htmlspecialchars(url(), ENT_QUOTES, 'UTF-8') . '">صفحه اصلی</a></main></html>';
    exit;
}

$db = getDB();

// ─── ۱. هیرو محتوایی ──────────────────────────────────────────────────────────
$heroPost = getPosts(['featured' => 1, 'limit' => 1])[0]
    ?? getPosts(['type' => 'news', 'limit' => 1])[0]
    ?? getPosts(['type' => 'article', 'limit' => 1])[0]
    ?? getPosts(['limit' => 1])[0]
    ?? null;

$heroTopic = null;
if ($heroPost) {
    $ht = getTopicsForPost((int)$heroPost['id']);
    $heroTopic = $ht[0] ?? null;
}

// ─── ۲. بخش‌های محتوایی بر اساس پایگاه داده ──────────────────────────────────
// هر بخش فقط محتوای هم‌نوع خود را نمایش می‌دهد؛ محتوای نامرتبط به عنوان خبر/مقاله/گزارش جا زده نمی‌شود.
$latestNews = getPosts(['type' => 'news', 'limit' => 3]);
$latestArticles = getPosts(['type' => 'article', 'limit' => 3]);
$latestReports = getPosts(['type' => 'report', 'limit' => 3]);

// د. موضوعات مهم و کلیدی
$featuredTopics = [];
try {
    $stmt = $db->query("SELECT t.*, COUNT(pt.post_id) as post_count FROM topics t LEFT JOIN post_topics pt ON pt.topic_id = t.id WHERE t.is_active = 1 GROUP BY t.id ORDER BY t.is_featured DESC, t.sort_order ASC, post_count DESC LIMIT 6");
    $featuredTopics = $stmt->fetchAll();
} catch (\Throwable) {
    $featuredTopics = getTopics(['limit' => 6]);
}

// هـ. رویدادها و برنامه‌ها
$latestEvents = array_merge(
    getPosts(['type' => 'program', 'limit' => 3]),
    getPosts(['type' => 'religious', 'limit' => 3])
);
usort($latestEvents, static fn(array $a, array $b): int => strcmp((string)($b['published_at'] ?? $b['created_at'] ?? ''), (string)($a['published_at'] ?? $a['created_at'] ?? '')));
$latestEvents = array_slice($latestEvents, 0, 3);

// و. کتاب‌ها
$latestBooks = getBooks(['limit' => 4]);

// ز. درس‌های حوزوی
$latestLessons = [];
try {
    $stmt = $db->prepare("
        SELECT l.*, c.title AS collection_title, c.slug AS collection_slug
        FROM lessons l
        LEFT JOIN lesson_collections c ON c.id = l.collection_id
        WHERE l.status = 'published'
        ORDER BY l.published_at DESC, l.id DESC
        LIMIT 4
    ");
    $stmt->execute();
    $latestLessons = $stmt->fetchAll();
} catch (\Throwable) {
    $latestLessons = [];
}

// ح. رسانه‌ها (ویدیوها و صوت‌ها)
$latestVideos = [];
try {
    $stmt = $db->prepare("
        SELECT m.*, p.title as post_title, p.slug as post_slug
        FROM media_files m
        LEFT JOIN posts p ON p.id = m.ref_id AND m.ref_type = 'post'
        WHERE m.kind = 'video' AND (p.status = 'published' OR p.status IS NULL)
        ORDER BY m.id DESC LIMIT 3
    ");
    $stmt->execute();
    $latestVideos = $stmt->fetchAll();
} catch (\Throwable) {
    $latestVideos = [];
}

// ط. بنر ویژه
$specialBanner = getActiveBanner();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ─── ۱. هیرو محتوایی اصلی ────────────────────────────────────────────── -->
<?php if (!empty($heroPost)): ?>
<section class="jhd-hero">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <?php if ($heroTopic): ?>
                <a href="<?= topicUrl($heroTopic) ?>" class="jhd-eyebrow">
                    <i class="bi bi-tag ms-1"></i><?= sanitize($heroTopic['name']) ?>
                </a>
                <?php else: ?>
                <span class="jhd-eyebrow"><i class="bi bi-star ms-1"></i><?= !empty($heroPost['is_featured']) ? 'مطلب برگزیده' : 'تازه از جامعة‌الهدی' ?></span>
                <?php endif; ?>

                <h1 class="jhd-hero-title mt-2">
                    <a href="<?= postUrl($heroPost) ?>"><?= sanitize($heroPost['title']) ?></a>
                </h1>

                <?php if (!empty($heroPost['summary'])): ?>
                <p class="jhd-hero-summary"><?= sanitize(excerpt($heroPost['summary'], 190)) ?></p>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="<?= postUrl($heroPost) ?>" class="btn btn-primary">
                        مطالعه کامل مطلب <i class="bi bi-arrow-left ms-1"></i>
                    </a>
                    <span class="text-muted small">
                        <i class="bi bi-calendar3 ms-1"></i><?= persianDate($heroPost['published_at'] ?? $heroPost['created_at']) ?>
                    </span>
                    <?php if (!empty($heroPost['views_count'])): ?>
                    <span class="text-muted small">
                        <i class="bi bi-eye ms-1"></i><?= number_format((int)$heroPost['views_count']) ?> بازدید
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="jhd-hero-card">
                    <a href="<?= postUrl($heroPost) ?>" class="d-block overflow-hidden">
                        <?php if (!empty($heroPost['featured_image'])): ?>
                        <img src="<?= imgUrl($heroPost['featured_image']) ?>" alt="<?= sanitize($heroPost['title']) ?>" class="jhd-hero-img" loading="eager">
                        <?php else: ?>
                        <div class="news-card-placeholder" style="height:280px">
                            <i class="bi bi-journal-bookmark"></i>
                        </div>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<section class="jhd-hero" aria-labelledby="hero-empty-title">
    <div class="container">
        <div class="jhd-hero-empty">
            <span class="jhd-eyebrow"><i class="bi bi-journal-bookmark ms-1"></i>جامعة‌الهدی</span>
            <h1 id="hero-empty-title">مرکز علمی، آموزشی و پژوهشی</h1>
            <p>مطالب و برنامه‌های منتشرشده در این پایگاه، پس از ثبت در سامانه در همین صفحه نمایش داده می‌شوند.</p>
            <a class="btn btn-primary" href="<?= url('topics') ?>">گشت‌وگذار در موضوعات <i class="bi bi-arrow-left ms-1"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ─── ۲. بنر اعلان ویژه ────────────────────────────────────────────────── -->
<?php if (!empty($specialBanner)): ?>
<section class="jhd-banner-special">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-megaphone ms-1"></i>اعلان ویژه</span>
            <span class="fw-bold"><?= sanitize($specialBanner['title']) ?></span>
            <?php if (!empty($specialBanner['content'])): ?>
            <span class="d-none d-md-inline opacity-75">— <?= sanitize(excerpt($specialBanner['content'], 90)) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($specialBanner['link'])): ?>
        <a href="<?= sanitize(safeExternalUrl($specialBanner['link'])) ?>" class="btn btn-sm btn-light fw-bold" target="_blank" rel="noopener">
            مشاهده جزییات <i class="bi bi-arrow-left ms-1"></i>
        </a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ─── ۳. تازه‌ترین اخبار حوزه ─────────────────────────────────────────── -->
<section class="py-5 section-plain" id="news-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">اطلاع‌رسانی و رویدادهای جاری</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-newspaper ms-2 text-gold"></i> اخبار مدرسه علمیه
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('news') ?>" class="btn btn-outline-primary btn-sm">
                مشاهده همه اخبار <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <?php if (empty($latestNews)): ?>
        <div class="jhd-empty-state"><i class="bi bi-newspaper" aria-hidden="true"></i><p>هنوز خبری برای نمایش منتشر نشده است.</p></div>
        <?php else: ?>
        <?= renderCategoryChips(['news'], url('news'), 'همه اخبار') ?>
        <div class="row g-4">
            <?php foreach ($latestNews as $k => $item):
                echo renderPostCard($item, ['featured' => $k === 0, 'cta' => 'ادامه مطلب', 'excerpt' => 110]);
            endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── ۴. مقالات علمی و یادداشت‌های پژوهشی [بخش مهم درخواستی] ────────────── -->
<section class="py-5 section-soft" id="articles-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">اندیشه و پژوهش‌های دینی</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-file-earmark-richtext ms-2 text-gold"></i> مقالات علمی و یادداشت‌ها
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('articles') ?>" class="btn btn-outline-primary btn-sm">
                همه مقالات <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <?php if (empty($latestArticles)): ?>
        <div class="jhd-empty-state"><i class="bi bi-file-text" aria-hidden="true"></i><p>مقاله‌ای برای نمایش در این بخش ثبت نشده است.</p></div>
        <?php else: ?>
        <?= renderCategoryChips(['article'], url('articles'), 'همه مقالات') ?>
        <div class="row g-4">
            <?php foreach ($latestArticles as $k => $art):
                echo renderPostCard($art, ['featured' => $k === 0, 'cta' => 'مطالعه کامل مقاله', 'excerpt' => 120]);
            endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── ۵. گزارش‌های تصویری و میدانی [بخش مهم درخواستی] ──────────────────── -->
<section class="py-5 section-plain" id="reports-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">پوشش میدانی و رخدادها</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-card-text ms-2 text-gold"></i> گزارش‌های حوزه و جامعه
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('reports') ?>" class="btn btn-outline-primary btn-sm">
                همه گزارش‌ها <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <?php if (empty($latestReports)): ?>
        <div class="jhd-empty-state"><i class="bi bi-card-text" aria-hidden="true"></i><p>گزارشی برای نمایش در این بخش ثبت نشده است.</p></div>
        <?php else: ?>
        <?= renderCategoryChips(['report'], url('reports'), 'همه گزارش‌ها') ?>
        <div class="row g-4">
            <?php foreach ($latestReports as $k => $rep):
                echo renderPostCard($rep, ['featured' => $k === 0, 'cta' => 'مشاهده گزارش', 'excerpt' => 100]);
            endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── ۶. موضوعات مهم و ستون فقرات محتوا [بخش مهم درخواستی] ─────────────── -->
<section class="py-5 section-soft" id="topics-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">ستون فقرات معارف اسلامی</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-diagram-3 ms-2 text-gold"></i> موضوعات مهم و محورهای پژوهشی
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('topics') ?>" class="btn btn-outline-primary btn-sm">
                اطلس کامل موضوعات <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <?php if (empty($featuredTopics)): ?>
        <div class="jhd-empty-state"><i class="bi bi-diagram-3" aria-hidden="true"></i><p>موضوعی برای نمایش ثبت نشده است.</p></div>
        <?php else: ?>
        <div class="row g-4">
            <?php
            $icons = ['bi-shield-check', 'bi-journal-bookmark', 'bi-sun', 'bi-book-half', 'bi-compass', 'bi-hourglass-split'];
            foreach ($featuredTopics as $idx => $tp):
                $tUrl = topicUrl($tp);
                $tIcon = $icons[$idx % count($icons)];
                $tCount = (int)($tp['post_count'] ?? 0) . ' مطلب';
            ?>
            <div class="col-sm-6 col-lg-4">
                <div class="topic-card-showcase">
                    <div class="topic-card-icon">
                        <i class="bi <?= $tIcon ?>"></i>
                    </div>
                    <h3 class="topic-card-name">
                        <a href="<?= $tUrl ?>" class="text-reset text-decoration-none"><?= sanitize($tp['name']) ?></a>
                    </h3>
                    <p class="topic-card-desc">
                        <?= sanitize($tp['intro'] ?? $tp['desc'] ?? $tp['description'] ?? 'مطالب ثبت‌شده و پیوندهای مرتبط با این موضوع.') ?>
                    </p>
                    <div class="mt-auto d-flex align-items-center justify-content-between w-100 pt-2 border-top">
                        <span class="small text-muted"><i class="bi bi-layers ms-1"></i><?= $tCount ?></span>
                        <a href="<?= $tUrl ?>" class="topic-card-btn">
                            ورود به موضوع <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── ۷. برنامه‌ها و رویدادهای آینده ──────────────────────────────────── -->
<section class="py-5 section-plain" id="events-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">تقویم حوزه و مناسبت‌ها</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-calendar-event ms-2 text-gold"></i> رویدادها و برنامه‌ها
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('events') ?>" class="btn btn-outline-primary btn-sm">
                همه رویدادها <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <?php if (empty($latestEvents)): ?>
        <div class="jhd-empty-state"><i class="bi bi-calendar-event" aria-hidden="true"></i><p>رویدادی برای نمایش ثبت نشده است.</p></div>
        <?php else: ?>
        <?= renderCategoryChips(['program','religious','announcement'], url('events'), 'همه رویدادها') ?>
        <div class="row g-4">
            <?php foreach ($latestEvents as $ev):
                echo renderPostCard($ev, ['cta' => 'جزییات برنامه', 'excerpt' => 110]);
            endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── ۸. کتابخانه دیجیتال ─────────────────────────────────────────────── -->
<?php if (!empty($latestBooks)): ?>
<section class="py-5 section-soft" id="books-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">مرکز اسناد و نشر آثار</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-book ms-2 text-gold"></i> کتابخانه دیجیتال
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('books') ?>" class="btn btn-outline-primary btn-sm">
                همه کتاب‌ها <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($latestBooks as $b):
                echo renderBookCard($b, ['col' => 'col-6 col-md-3']);
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ─── ۹. درس‌های حوزوی و مدرسه مجازی ──────────────────────────────────── -->
<?php if (!empty($latestLessons)): ?>
<section class="py-5 section-plain" id="lessons-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">مدرسه علمیه و آموزش مجازی</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-mortarboard ms-2 text-gold"></i> درس‌های حوزوی
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('lessons') ?>" class="btn btn-outline-primary btn-sm">
                همه درس‌ها <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($latestLessons as $ls): ?>
            <div class="col-md-6 col-lg-3">
                <div class="lesson-card h-100">
                    <?php if (!empty($ls['collection_title'])): ?>
                    <span class="badge bg-light text-dark align-self-start mb-2 border">
                        <?= sanitize($ls['collection_title']) ?>
                    </span>
                    <?php endif; ?>
                    <h3 class="lesson-card-title">
                        <a href="<?= lessonUrl($ls) ?>"><?= sanitize($ls['title']) ?></a>
                    </h3>
                    <?php if (!empty($ls['teacher'])): ?>
                    <div class="lesson-card-teacher">
                        <i class="bi bi-person-video3"></i>استاد: <?= sanitize($ls['teacher']) ?>
                    </div>
                    <?php endif; ?>
                    <a href="<?= lessonUrl($ls) ?>" class="btn btn-sm btn-outline-primary w-100 mt-auto">
                        جلسات و صوت درس <i class="bi bi-arrow-left ms-1"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ─── ۱۰. چندرسانه‌ای و ویدیوها ───────────────────────────────────────── -->
<?php if (!empty($latestVideos)): ?>
<section class="py-5 section-soft" id="media-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="jhd-eyebrow">نگارخانه صوتی و تصویری</span>
                <h2 class="section-title mb-1">
                    <i class="bi bi-play-circle ms-2 text-gold"></i> چندرسانه‌ای و سخنرانی‌ها
                </h2>
                <div class="section-divider"></div>
            </div>
            <a href="<?= url('media') ?>" class="btn btn-outline-primary btn-sm">
                آرشیو رسانه <i class="bi bi-arrow-left ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($latestVideos as $v):
                $vMediaUrl = mediaUrl('video', (int)$v['id']);
                $vPostUrl = !empty($v['post_slug']) ? postUrl($v['post_slug']) : $vMediaUrl;
            ?>
            <div class="col-md-4">
                <div class="news-card h-100">
                    <div class="news-card-img-wrap" style="aspect-ratio:16/9">
                        <div class="video-thumb h-100 w-100" data-video="<?= url($v['file_path']) ?>">
                            <div class="video-thumb__placeholder h-100 d-flex align-items-center justify-content-center bg-dark text-white">
                                <i class="bi bi-camera-video fs-1 opacity-50"></i>
                            </div>
                            <div class="video-play-overlay">
                                <div class="play-btn-circle"><i class="bi bi-play-fill"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="news-card-body">
                        <h3 class="news-card-title" style="font-size:0.96rem">
                            <a href="<?= $vPostUrl ?>"><?= sanitize($v['title'] ?: ($v['post_title'] ?? 'ویدیو')) ?></a>
                        </h3>
                        <a href="<?= $vPostUrl ?>" class="btn-read-more mt-auto">
                            پخش و دریافت <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Modal پخش‌کننده ویدیو -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white ms-auto me-0" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body p-2 p-md-3">
                <div class="ratio ratio-16x9">
                    <video id="modalVideoPlayer" controls playsinline preload="metadata">
                        مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                    </video>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
