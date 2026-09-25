<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
startSecureSession();

$siteName = getSetting('site_name', SITE_NAME);
if ($siteName === 'مدرسه علمیه جامعه‌الهدی') $siteName = SITE_NAME; // normalize the legacy installation default
$siteSlogan = getSetting('site_slogan', SITE_SLOGAN);
if ($siteSlogan === 'علم، معرفت و تهذیب در پرتو قرآن و عترت') $siteSlogan = SITE_SLOGAN; // normalize the legacy installation default
$currentPath = current_path();
$isLoggedIn = isLoggedIn();

$metaTitle = !empty($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName . ' | ' . $siteSlogan;
$metaDesc = $pageDesc ?? $siteSlogan;
if (mb_strlen($metaDesc, 'UTF-8') > 160) {
    $metaDesc = mb_substr($metaDesc, 0, 157, 'UTF-8') . '...';
}

$canonicalPath = $_SERVER['JHD_ROUTE_PATH'] ?? ($_SERVER['SCRIPT_NAME'] ?? '/');
if (!empty($canonicalOverride)) {
    $canonicalPath = $canonicalOverride;
}
if (basename($canonicalPath) === 'index.php') {
    $canonicalPath = rtrim(dirname($canonicalPath), '/') . '/';
}
$canonicalPath = rawurldecode($canonicalPath);
$canonicalQuery = [];

if (empty($canonicalOverride)) {
    $routeSlug = trim($_GET['slug'] ?? '');
    if ($routeSlug !== '' && !str_ends_with(rtrim($canonicalPath, '/'), '/' . $routeSlug)) {
        $canonicalQuery['slug'] = $routeSlug;
    }
    if ((basename($_SERVER['PHP_SELF'] ?? '') === 'book.php') && !empty($_GET['id']) && !str_contains($canonicalPath, '/' . (int)$_GET['id'])) {
        $canonicalQuery['id'] = (int)$_GET['id'];
    }
    foreach (['collection', 'volume'] as $collectionKey) {
        $collectionValue = trim($_GET[$collectionKey] ?? '');
        if ($collectionValue !== '' && !str_contains($canonicalPath, '/' . $collectionValue)) {
            $canonicalQuery[$collectionKey] = $collectionValue;
        }
    }
    if (!empty($_GET['q'])) {
        $canonicalQuery['q'] = mb_substr($_GET['q'], 0, 80);
    }
}

// Encode path segments
$canonicalSegments = array_map(static fn($s) => $s === '' ? '' : rawurlencode($s), explode('/', $canonicalPath));
$canonicalPath = implode('/', $canonicalSegments);
if ($canonicalQuery) {
    $canonicalPath .= '?' . http_build_query($canonicalQuery);
}
$responseIs404 = http_response_code() === 404;
$canonical = SITE_URL && !$responseIs404 ? absolute_url(ltrim(substr($canonicalPath, strlen(BASE_PATH)), '/')) : '';
$ogType = isset($post) || isset($book) || isset($lesson) ? 'article' : 'website';

// Helper for active navigation link
$isActiveNav = function(string $route) use ($currentPath): bool {
    if ($route === '/') return $currentPath === '/' || $currentPath === '';
    return str_starts_with($currentPath, '/' . trim($route, '/'));
};
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#163b37">
<meta name="plyr-sprite" content="<?= asset('vendor/plyr.svg') ?>">
<meta name="csrf-token" content="<?= sanitize(generateCsrfToken()) ?>">
<title><?= sanitize($metaTitle) ?></title>
<meta name="description" content="<?= sanitize($metaDesc) ?>">
<?php if ($currentPath === '/search' || $responseIs404): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
<?php if ($canonical): ?>
<link rel="canonical" href="<?= sanitize($canonical) ?>">
<meta property="og:url" content="<?= sanitize($canonical) ?>">
<?php endif; ?>
<meta property="og:locale" content="fa_AF">
<meta property="og:type" content="<?= $ogType ?>">
<meta property="og:title" content="<?= sanitize($metaTitle) ?>">
<meta property="og:description" content="<?= sanitize($metaDesc) ?>">
<meta property="og:site_name" content="<?= sanitize($siteName) ?>">
<?php if (!empty($post['published_at'])): ?><meta property="article:published_time" content="<?= sanitize($post['published_at']) ?>"><?php endif; ?>
<?php if (!empty($post['author_name'])): ?><meta property="article:author" content="<?= sanitize($post['author_name']) ?>"><?php endif; ?>
<?php if (!empty($post['featured_image']) || !empty($book['cover_image']) || !empty($lesson['featured_image'])):
    $ogImg = imgUrl($post['featured_image'] ?? $book['cover_image'] ?? $lesson['featured_image'] ?? '');
    if ($ogImg): ?><meta property="og:image" content="<?= sanitize($ogImg) ?>"><?php endif;
endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= sanitize($metaTitle) ?>">
<meta name="twitter:description" content="<?= sanitize($metaDesc) ?>">
<?php if (!empty($ogImg)): ?><meta name="twitter:image" content="<?= sanitize($ogImg) ?>"><?php endif; ?>
<?php if (SITE_URL): ?>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'EducationalOrganization','name'=>$siteName,'url'=>SITE_URL,'description'=>$siteSlogan], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'WebSite','name'=>$siteName,'url'=>SITE_URL,'potentialAction'=>['@type'=>'SearchAction','target'=> SITE_URL.'/search?q={search_term_string}','query-input'=>'required name=search_term_string']], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<?php endif; ?>
<?php if (!empty($breadcrumbsJsonLd)): ?><script type="application/ld+json"><?= $breadcrumbsJsonLd ?></script><?php endif; ?>
<?php if (!empty($articleJsonLd)): ?><script type="application/ld+json"><?= $articleJsonLd ?></script><?php endif; ?>
<?php if (!empty($bookJsonLd)): ?><script type="application/ld+json"><?= $bookJsonLd ?></script><?php endif; ?>
<?php if (!empty($mediaJsonLd)): ?><script type="application/ld+json"><?= $mediaJsonLd ?></script><?php endif; ?>
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<script src="<?= asset('js/theme.js') ?>"></script>
<link rel="preload" href="<?= asset('fonts/Vazirmatn-Regular.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('vendor/bootstrap.rtl.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/plyr.css') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/extra.css') ?>">
<link rel="stylesheet" href="<?= asset('css/legacy-components.css') ?>">
<link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
<script src="<?= asset('js/interface.js') ?>" defer></script>
</head>
<body class="jhd-public-site">
<a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>

<!-- Header اصلی -->
<header class="jhd-header" role="banner">
    <!-- ردیف بالای برند، جستجو و حساب کاربری -->
    <div class="container jhd-header-row">
        <a class="jhd-brand" href="<?= url() ?>" aria-label="<?= sanitize($siteName) ?>، صفحه اصلی">
            <img src="<?= imgUrl(getSetting('site_logo', 'assets/img/logo.jpg')) ?>" width="52" height="52" alt="نشان <?= sanitize($siteName) ?>">
            <span>
                <strong><?= sanitize($siteName) ?></strong>
                <small>مرکز علمی، آموزشی و پژوهشی</small>
            </span>
        </a>

        <!-- فرم جستجوی دسکتاپ -->
        <form class="jhd-search d-none d-lg-flex" action="<?= url('search') ?>" method="get" role="search">
            <label class="visually-hidden" for="header-search">جستجو در مقالات، اخبار، دروس و کتاب‌ها</label>
            <input id="header-search" name="q" type="search" placeholder="جستجو در اخبار، مقالات، کتاب‌ها، دروس..." maxlength="200" value="<?= sanitize($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="جستجو"><i class="bi bi-search"></i></button>
        </form>

        <!-- دکمه‌ها و اکشن‌ها -->
        <div class="jhd-header-actions">
            <a class="jhd-icon-btn jhd-mobile-search d-lg-none" href="<?= url('search') ?>" aria-label="جستجو"><i class="bi bi-search"></i></a>

            <button class="jhd-icon-btn" data-theme-toggle aria-label="تغییر پوسته روشن و تیره" aria-pressed="false"><i class="bi bi-moon"></i></button>

            <?php if ($isLoggedIn): ?>
            <a class="btn btn-sm btn-outline-primary d-none d-md-inline-flex align-items-center gap-1" href="<?= url('admin') ?>">
                <i class="bi bi-speedometer2"></i>
                <span>پنل مدیریت</span>
            </a>
            <?php else: ?>
            <a class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center gap-1" href="<?= url('login') ?>">
                <i class="bi bi-box-arrow-in-left"></i>
                <span>ورود</span>
            </a>
            <?php endif; ?>

            <button type="button" id="menuToggle" class="jhd-icon-btn jhd-menu-toggle" aria-label="باز کردن منو" aria-expanded="false" aria-controls="siteDrawer"><i class="bi bi-list"></i></button>
        </div>
    </div>

    <!-- نوار ناوبری اصلی دسکتاپ -->
    <nav class="jhd-navbar-desktop d-none d-xl-block" aria-label="ناوبری اصلی دسکتاپ">
        <div class="container">
            <ul class="jhd-nav-list mb-0">
                <li><a href="<?= url() ?>" class="jhd-nav-link <?= $isActiveNav('/') ? 'active' : '' ?>" <?= $isActiveNav('/') ? 'aria-current="page"' : '' ?>><i class="bi bi-house-door ms-1"></i>خانه</a></li>
                <li><a href="<?= url('news') ?>" class="jhd-nav-link <?= $isActiveNav('news') ? 'active' : '' ?>" <?= $isActiveNav('news') ? 'aria-current="page"' : '' ?>><i class="bi bi-newspaper ms-1"></i>اخبار</a></li>
                <li><a href="<?= url('articles') ?>" class="jhd-nav-link <?= $isActiveNav('articles') || $isActiveNav('article') ? 'active' : '' ?>" <?= ($isActiveNav('articles') || $isActiveNav('article')) ? 'aria-current="page"' : '' ?>><i class="bi bi-file-text ms-1"></i>مقالات</a></li>
                <li><a href="<?= url('reports') ?>" class="jhd-nav-link <?= $isActiveNav('reports') || $isActiveNav('report') ? 'active' : '' ?>" <?= ($isActiveNav('reports') || $isActiveNav('report')) ? 'aria-current="page"' : '' ?>><i class="bi bi-card-text ms-1"></i>گزارش‌ها</a></li>
                <li><a href="<?= url('events') ?>" class="jhd-nav-link <?= $isActiveNav('events') || $isActiveNav('programs') ? 'active' : '' ?>" <?= ($isActiveNav('events') || $isActiveNav('programs')) ? 'aria-current="page"' : '' ?>><i class="bi bi-calendar-event ms-1"></i>رویدادها</a></li>
                <li><a href="<?= url('books') ?>" class="jhd-nav-link <?= $isActiveNav('books') || $isActiveNav('book') ? 'active' : '' ?>" <?= ($isActiveNav('books') || $isActiveNav('book')) ? 'aria-current="page"' : '' ?>><i class="bi bi-book ms-1"></i>کتاب‌ها</a></li>
                <li><a href="<?= url('lessons') ?>" class="jhd-nav-link <?= $isActiveNav('lessons') || $isActiveNav('lesson') ? 'active' : '' ?>" <?= ($isActiveNav('lessons') || $isActiveNav('lesson')) ? 'aria-current="page"' : '' ?>><i class="bi bi-mortarboard ms-1"></i>درس‌ها</a></li>
                <li><a href="<?= url('research') ?>" class="jhd-nav-link <?= $isActiveNav('research') ? 'active' : '' ?>" <?= $isActiveNav('research') ? 'aria-current="page"' : '' ?>><i class="bi bi-journal-richtext ms-1"></i>پژوهش</a></li>
                <li><a href="<?= url('media') ?>" class="jhd-nav-link <?= $isActiveNav('media') || $isActiveNav('videos') || $isActiveNav('audios') ? 'active' : '' ?>" <?= ($isActiveNav('media') || $isActiveNav('videos') || $isActiveNav('audios')) ? 'aria-current="page"' : '' ?>><i class="bi bi-play-circle ms-1"></i>رسانه</a></li>
                <li><a href="<?= url('topics') ?>" class="jhd-nav-link <?= $isActiveNav('topics') || $isActiveNav('topic') ? 'active' : '' ?>" <?= ($isActiveNav('topics') || $isActiveNav('topic')) ? 'aria-current="page"' : '' ?>><i class="bi bi-diagram-3 ms-1"></i>موضوعات</a></li>
            </ul>
        </div>
    </nav>

    <nav class="jhd-utility-nav d-none d-xl-block" aria-label="پیوندهای تکمیلی">
        <div class="container">
            <ul>
                <li><a href="<?= url('qa') ?>">پرسش و پاسخ</a></li>
                <li><a href="<?= url('about') ?>">درباره ما</a></li>
                <li><a href="<?= url('contact') ?>">ارتباط با ما</a></li>
            </ul>
        </div>
    </nav>
</header>

<!-- پس‌زمینه نیمه‌شفاف منوی همبرگر -->
<div id="drawerOverlay" class="jhd-drawer-overlay" hidden></div>

<!-- منوی کشویی موبایل (Hamburger Drawer) -->
<aside id="siteDrawer" class="jhd-drawer" role="dialog" aria-modal="true" aria-label="منوی اصلی" aria-hidden="true" inert>
    <div class="jhd-drawer-head">
        <span><i class="bi bi-grid ms-2"></i> فهرست بخش‌ها</span>
        <button id="drawerClose" class="jhd-icon-btn" aria-label="بستن منو"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="jhd-drawer-search">
        <form action="<?= url('search') ?>" method="get" role="search">
            <label class="visually-hidden" for="drawer-search">جستجو در محتوا</label>
            <input id="drawer-search" name="q" type="search" placeholder="جستجو در محتوا..." maxlength="200" value="<?= sanitize($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="جستجو"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="jhd-drawer-body">
        <div class="drawer-section">ناوبری اصلی</div>
        <a href="<?= url() ?>" class="drawer-link <?= $isActiveNav('/') ? 'active' : '' ?>"><i class="bi bi-house"></i> خانه</a>
        <a href="<?= url('news') ?>" class="drawer-link <?= $isActiveNav('news') ? 'active' : '' ?>"><i class="bi bi-newspaper"></i> اخبار مدرسه</a>
        <a href="<?= url('articles') ?>" class="drawer-link <?= $isActiveNav('articles') ? 'active' : '' ?>"><i class="bi bi-file-text"></i> مقالات علمی</a>
        <a href="<?= url('reports') ?>" class="drawer-link <?= $isActiveNav('reports') ? 'active' : '' ?>"><i class="bi bi-card-text"></i> گزارش‌ها و مناسبت‌ها</a>
        <a href="<?= url('events') ?>" class="drawer-link <?= $isActiveNav('events') ? 'active' : '' ?>"><i class="bi bi-calendar-event"></i> رویدادها و برنامه‌ها</a>
        <a href="<?= url('books') ?>" class="drawer-link <?= $isActiveNav('books') ? 'active' : '' ?>"><i class="bi bi-book"></i> کتابخانه دیجیتال</a>
        <a href="<?= url('lessons') ?>" class="drawer-link <?= $isActiveNav('lessons') ? 'active' : '' ?>"><i class="bi bi-mortarboard"></i> درس‌های حوزوی</a>
        <a href="<?= url('research') ?>" class="drawer-link <?= $isActiveNav('research') ? 'active' : '' ?>"><i class="bi bi-journal-richtext"></i> پژوهش‌ها</a>
        <a href="<?= url('media') ?>" class="drawer-link <?= $isActiveNav('media') ? 'active' : '' ?>"><i class="bi bi-play-circle"></i> رسانه (ویدیو و صوت)</a>
        <a href="<?= url('topics') ?>" class="drawer-link <?= $isActiveNav('topics') ? 'active' : '' ?>"><i class="bi bi-diagram-3"></i> موضوعات دینی</a>

        <div class="drawer-section">موضوعات منتخب</div>
        <?php
        $drawerTopics = getTopics(['active' => 1, 'limit' => 6]);
        foreach ($drawerTopics as $dt): ?>
        <a href="<?= topicUrl($dt) ?>" class="drawer-link drawer-topic depth-0"><i class="bi bi-tag"></i> <?= sanitize($dt['name']) ?></a>
        <?php endforeach; ?>
        <a href="<?= url('topics') ?>" class="drawer-link drawer-all"><i class="bi bi-grid-3x3-gap"></i> همه موضوعات</a>

        <div class="drawer-section">اطلاعات و تماس</div>
        <a href="<?= url('qa') ?>" class="drawer-link"><i class="bi bi-question-circle"></i> پرسش و پاسخ</a>
        <a href="<?= url('about') ?>" class="drawer-link"><i class="bi bi-info-circle"></i> درباره جامعه‌الهدی</a>
        <a href="<?= url('contact') ?>" class="drawer-link"><i class="bi bi-envelope"></i> ارتباط با ما</a>

        <div class="drawer-section">حساب کاربری</div>
        <?php if ($isLoggedIn): ?>
        <a href="<?= url('admin') ?>" class="drawer-link"><i class="bi bi-speedometer2"></i> پنل مدیریت</a>
        <a href="<?= url('admin/logout') ?>" class="drawer-link text-danger" data-confirm="آیا از خروج از سیستم اطمینان دارید؟"><i class="bi bi-box-arrow-right"></i> خروج از حساب</a>
        <?php else: ?>
        <a href="<?= url('login') ?>" class="drawer-link"><i class="bi bi-box-arrow-in-left"></i> ورود به پنل</a>
        <?php endif; ?>
    </div>
</aside>

<main id="main-content" tabindex="-1">
