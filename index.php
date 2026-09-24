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
$pageDesc = 'مدرسه علمیه جامعه‌الهدی — اخبار مدرسه، مقالات علمی، گزارش‌ها، کتابخانه دیجیتال، دروس حوزوی و موضوعات معارف اسلامی در کابل، افغانستان.';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

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
// الف. اخبار مدرسه
$latestNews = getPosts(['type' => 'news', 'limit' => 3]);
if (empty($latestNews)) {
    $latestNews = getPosts(['limit' => 3]);
}

// ب. مقالات علمی و یادداشت‌ها
$latestArticles = getPosts(['type' => 'article', 'limit' => 3]);
if (empty($latestArticles)) {
    $latestArticles = getPosts(['type' => 'research', 'limit' => 3]);
}
if (empty($latestArticles)) {
    $latestArticles = getPosts(['limit' => 3]);
}

// ج. گزارش‌ها
$latestReports = getPosts(['type' => 'report', 'limit' => 3]);
if (empty($latestReports)) {
    // جستجو برای مطالبی که در عنوانشان «گزارش» دارند
    try {
        $stmt = $db->query("SELECT * FROM posts WHERE status='published' AND (title ILIKE '%گزارش%' OR summary ILIKE '%گزارش%') ORDER BY published_at DESC LIMIT 3");
        $latestReports = $stmt->fetchAll();
    } catch (\Throwable) {
        $latestReports = [];
    }
}
if (empty($latestReports)) {
    $latestReports = getPosts(['type' => 'news', 'limit' => 3]);
}

// د. موضوعات مهم و کلیدی
$featuredTopics = [];
try {
    $stmt = $db->query("SELECT t.*, COUNT(pt.post_id) as post_count FROM topics t LEFT JOIN post_topics pt ON pt.topic_id = t.id WHERE t.is_active = 1 GROUP BY t.id ORDER BY t.is_featured DESC, t.sort_order ASC, post_count DESC LIMIT 6");
    $featuredTopics = $stmt->fetchAll();
} catch (\Throwable) {
    $featuredTopics = getTopics(['limit' => 6]);
}

// اگر موضوعات در دیتابیس هنوز کم باشد، موضوعات استاندارد معارف اسلامی
$defaultTopicBadges = [
    ['name' => 'کلام و عقاید اسلامی', 'slug' => 'kalam-aqaid', 'desc' => 'پژوهش‌های استدلالی در توحید، نبوت، امامت و معاد', 'icon' => 'bi-shield-check'],
    ['name' => 'فقه و اصول استنباط', 'slug' => 'fiqh-usul', 'desc' => 'بررسی احکام فقهی، ادله اربعه و متون اصلی حوزه', 'icon' => 'bi-journal-bookmark'],
    ['name' => 'مهدویت و موعودباوری', 'slug' => 'mahdaviat', 'desc' => 'مباحث امامت، غیبت، انتظار و حکومت جهانی عدل', 'icon' => 'bi-sun'],
    ['name' => 'قرآن و علوم حدیث', 'slug' => 'quran-hadith', 'desc' => 'تفسیر آیات وحی، درایة‌الحدیث و مفاهیم بنیادین', 'icon' => 'bi-book-half'],
    ['name' => 'فلسفه و منطق اسلامی', 'slug' => 'philosophy', 'desc' => 'حکمت متعالیه، مبادی برهان و معرفت‌شناسی دینی', 'icon' => 'bi-compass'],
    ['name' => 'تاریخ و سیره اهل‌بیت', 'slug' => 'tarikh-ahlulbayt', 'desc' => 'تحلیل وقایع صدر اسلام و سیره هدایت‌بخش معصومین(ع)', 'icon' => 'bi-hourglass-split'],
];

// هـ. رویدادها و برنامه‌ها
$latestEvents = getPosts(['type' => 'program', 'limit' => 3]);
if (empty($latestEvents)) {
    $latestEvents = getPosts(['type' => 'announcement', 'limit' => 3]);
}

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
                <span class="jhd-eyebrow"><i class="bi bi-star ms-1"></i>محتوای ویژه روز</span>
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

        <div class="row g-4">
            <?php foreach ($latestNews as $item): $nUrl = postUrl($item); ?>
            <div class="col-md-6 col-lg-4">
                <article class="news-card h-100">
                    <div class="news-card-img-wrap">
                        <a href="<?= $nUrl ?>">
                            <?php if (!empty($item['featured_image'])): ?>
                            <img src="<?= imgUrl($item['featured_image']) ?>" alt="<?= sanitize($item['title']) ?>" class="news-card-img" loading="lazy">
                            <?php else: ?>
                            <div class="news-card-placeholder"><i class="bi bi-newspaper"></i></div>
                            <?php endif; ?>
                        </a>
                        <div class="news-card-badge"><?= postTypeBadge($item['post_type']) ?></div>
                    </div>
                    <div class="news-card-body">
                        <div class="news-card-meta">
                            <span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($item['published_at'] ?? $item['created_at']) ?></span>
                            <?php if (!empty($item['views_count'])): ?>
                            <span>• <i class="bi bi-eye ms-1"></i><?= number_format((int)$item['views_count']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="news-card-title">
                            <a href="<?= $nUrl ?>"><?= sanitize($item['title']) ?></a>
                        </h3>
                        <?php if (!empty($item['summary'])): ?>
                        <p class="news-card-summary"><?= sanitize(excerpt($item['summary'], 110)) ?></p>
                        <?php endif; ?>
                        <div class="news-card-footer">
                            <a href="<?= $nUrl ?>" class="btn-read-more">
                                ادامه مطلب <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
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

        <div class="row g-4">
            <?php foreach ($latestArticles as $art): $aUrl = postUrl($art); ?>
            <div class="col-md-6 col-lg-4">
                <article class="article-card h-100">
                    <div class="article-card-header">
                        <span class="article-card-author">
                            <i class="bi bi-person ms-1"></i><?= sanitize($art['author_name'] ?? 'هیئت علمی حوزه') ?>
                        </span>
                        <span class="article-card-date">
                            <i class="bi bi-calendar3 ms-1"></i><?= persianDate($art['published_at'] ?? $art['created_at']) ?>
                        </span>
                    </div>
                    <h3 class="article-card-title">
                        <a href="<?= $aUrl ?>"><?= sanitize($art['title']) ?></a>
                    </h3>
                    <p class="article-card-summary">
                        <?= sanitize(excerpt($art['summary'] ?? $art['content'], 120)) ?>
                    </p>
                    <div class="article-card-footer">
                        <span class="badge badge-article">مقاله تحلیلی</span>
                        <a href="<?= $aUrl ?>" class="btn-read-more">
                            مطالعه کامل مقاله <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
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

        <div class="row g-4">
            <?php foreach ($latestReports as $rep): $rUrl = postUrl($rep); ?>
            <div class="col-md-6 col-lg-4">
                <article class="news-card report-card h-100">
                    <div class="news-card-img-wrap">
                        <a href="<?= $rUrl ?>">
                            <?php if (!empty($rep['featured_image'])): ?>
                            <img src="<?= imgUrl($rep['featured_image']) ?>" alt="<?= sanitize($rep['title']) ?>" class="news-card-img" loading="lazy">
                            <?php else: ?>
                            <div class="news-card-placeholder"><i class="bi bi-camera"></i></div>
                            <?php endif; ?>
                        </a>
                        <div class="report-card-badge"><i class="bi bi-images ms-1"></i>گزارش</div>
                    </div>
                    <div class="news-card-body">
                        <div class="news-card-meta">
                            <span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($rep['published_at'] ?? $rep['created_at']) ?></span>
                        </div>
                        <h3 class="news-card-title">
                            <a href="<?= $rUrl ?>"><?= sanitize($rep['title']) ?></a>
                        </h3>
                        <p class="news-card-summary">
                            <?= sanitize(excerpt($rep['summary'] ?? '', 100)) ?>
                        </p>
                        <div class="news-card-footer">
                            <a href="<?= $rUrl ?>" class="btn-read-more">
                                مشاهده گزارش <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
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

        <div class="row g-4">
            <?php
            // نمایش موضوعات واقعی یا ترکیب با موضوعات غنی شاخص
            $displayTopics = !empty($featuredTopics) ? $featuredTopics : $defaultTopicBadges;
            $icons = ['bi-shield-check', 'bi-journal-bookmark', 'bi-sun', 'bi-book-half', 'bi-compass', 'bi-hourglass-split'];
            foreach ($displayTopics as $idx => $tp):
                $tUrl = !empty($tp['id']) ? topicUrl($tp) : url('topics');
                $tIcon = $tp['icon'] ?? $icons[$idx % count($icons)];
                $tCount = isset($tp['post_count']) && (int)$tp['post_count'] > 0 ? (int)$tp['post_count'] . ' مطلب' : 'مطالب و پژوهش‌ها';
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
                        <?= sanitize($tp['intro'] ?? $tp['desc'] ?? $tp['description'] ?? 'مجموعه مقالات، اخبار، دروس و کتب تخصصی مرتبط با این حوزه علمی.') ?>
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
    </div>
</section>

<!-- ─── ۷. برنامه‌ها و رویدادهای آینده ──────────────────────────────────── -->
<?php if (!empty($latestEvents)): ?>
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

        <div class="row g-4">
            <?php foreach ($latestEvents as $ev): $evUrl = postUrl($ev); ?>
            <div class="col-md-6 col-lg-4">
                <article class="news-card h-100">
                    <div class="news-card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-secondary"><?= postTypeLabel($ev['post_type']) ?></span>
                            <span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($ev['published_at'] ?? $ev['created_at']) ?></span>
                        </div>
                        <h3 class="news-card-title">
                            <a href="<?= $evUrl ?>"><?= sanitize($ev['title']) ?></a>
                        </h3>
                        <p class="news-card-summary">
                            <?= sanitize(excerpt($ev['summary'] ?? '', 110)) ?>
                        </p>
                        <div class="news-card-footer">
                            <a href="<?= $evUrl ?>" class="btn-read-more">
                                جزییات برنامه <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

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
            <?php foreach ($latestBooks as $b): ?>
            <div class="col-6 col-md-3">
                <div class="book-card h-100">
                    <div class="book-card-cover">
                        <a href="<?= bookUrl($b) ?>">
                            <?php if (!empty($b['cover_image'])): ?>
                            <img src="<?= imgUrl($b['cover_image']) ?>" alt="<?= sanitize($b['title']) ?>" loading="lazy">
                            <?php else: ?>
                            <div class="h-100 d-flex align-items-center justify-content-center text-muted"><i class="bi bi-book fs-1"></i></div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <h3 class="book-card-title">
                        <a href="<?= bookUrl($b) ?>"><?= sanitize($b['title']) ?></a>
                    </h3>
                    <?php if (!empty($b['author'])): ?>
                    <div class="book-card-author"><i class="bi bi-person ms-1"></i><?= sanitize($b['author']) ?></div>
                    <?php endif; ?>
                    <a href="<?= bookUrl($b) ?>" class="btn btn-sm btn-outline-primary w-100 mt-auto">
                        معرفی و دریافت
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
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
