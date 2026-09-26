<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/member-auth.php';
startSecureSession();

$siteName = getSetting('site_name', SITE_NAME);
if ($siteName === 'مدرسه علمیه جامعه‌الهدی') $siteName = SITE_NAME; // normalize the legacy installation default
$siteSlogan = getSetting('site_slogan', SITE_SLOGAN);
if ($siteSlogan === 'علم، معرفت و تهذیب در پرتو قرآن و عترت') $siteSlogan = SITE_SLOGAN; // normalize the legacy installation default
$currentPath = current_path();
$isAdminLoggedIn = isLoggedIn();
$isMemberLoggedIn = isMemberLoggedIn();
$isLoggedIn = $isAdminLoggedIn; // backward compatible for existing templates
$navTopicTree = getTopicTree();

$metaTitle = !empty($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName . ' | ' . $siteSlogan;
$metaDesc = $pageDesc ?? $siteSlogan;
if (mb_strlen($metaDesc, 'UTF-8') > 160) {
    $metaDesc = mb_substr($metaDesc, 0, 157, 'UTF-8') . '...';
}

// Canonical URL — always the single public URL for the page, never a physical
// file (pages/topic.php, router.php, index.php internals). Detail pages set
// $canonicalOverride to a registry-generated URL; listings derive it from the
// resolved route. In Query mode the canonical is the ?p=… form; in Pretty mode
// the /path form — exactly one version, matching what url() generates.
$responseIs404 = http_response_code() === 404;
$canonical = '';
if (SITE_URL && !$responseIs404) {
    if (!empty($canonicalOverride)) {
        $canonical = jhd_absolute_url($canonicalOverride);
    } else {
        $routeName = isset($_SERVER['JHD_ROUTE_NAME']) ? (string)$_SERVER['JHD_ROUTE_NAME'] : '';
        if (JHD_PRETTY_URLS) {
            $routePath = $_SERVER['JHD_ROUTE_PATH'] ?? current_path();
            $canonical = jhd_absolute_url(ltrim(substr($routePath, strlen(BASE_PATH)), '/'));
        } else {
            $canonical = jhd_absolute_url(jhd_query_canonical($routeName));
        }
    }
}
// Search results and 404s stay out of the index (Query or Pretty spelling).
$authRoutes = ['login', 'register', 'logout', 'account', 'profile', 'password-change'];
$routeNameForRobots = isset($_SERVER['JHD_ROUTE_NAME']) ? (string)$_SERVER['JHD_ROUTE_NAME'] : (string)($_GET['p'] ?? '');
$authNoindex = in_array($routeNameForRobots, $authRoutes, true)
    || preg_match('~^/(login|register|logout|account|profile|password-change)(/|$)~', $currentPath);
$noindexSeo = !empty($noindexSeo)
    || $responseIs404
    || $authNoindex
    || $routeNameForRobots === 'search'
    || $currentPath === '/search';
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
<?php if ($noindexSeo): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
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
<link rel="preload" href="<?= asset('fonts/Amiri-Bold.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('vendor/bootstrap.rtl.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/plyr.css') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/extra.css') ?>">
<link rel="stylesheet" href="<?= asset('css/legacy-components.css') ?>">
<link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
<script src="<?= asset('js/interface.js') ?>" defer></script>
</head>
<body class="jhd-public-site<?= !empty($authNoindex) ? ' jhd-login-site' : '' ?>">
<a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>

<!-- نوار بسمله: امضای بصری یک پایگاه دینی -->
<div class="bismillah-bar" aria-hidden="true">
    <span>بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</span>
</div>

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
        <form class="jhd-search d-none d-lg-flex" action="<?= formUrl('search') ?>" method="get" role="search">
            <?= formRouteFields('search') ?>
            <label class="visually-hidden" for="header-search">جستجو در مقالات، اخبار، دروس و کتاب‌ها</label>
            <input id="header-search" name="q" type="search" placeholder="جستجو در اخبار، مقالات، کتاب‌ها، دروس..." maxlength="200" value="<?= sanitize($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="جستجو"><i class="bi bi-search"></i></button>
        </form>

        <!-- دکمه‌ها و اکشن‌ها -->
        <div class="jhd-header-actions">
            <a class="jhd-icon-btn jhd-mobile-search d-lg-none" href="<?= url('search') ?>" aria-label="جستجو"><i class="bi bi-search"></i></a>

            <button class="jhd-icon-btn" data-theme-toggle aria-label="تغییر پوسته روشن و تیره" aria-pressed="false"><i class="bi bi-moon"></i></button>

            <div class="jhd-account-cluster d-none d-md-flex">
            <?php if ($isAdminLoggedIn): ?>
            <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" href="<?= adminDashboardUrl() ?>">
                <i class="bi bi-speedometer2"></i>
                <span>پنل مدیریت</span>
            </a>
            <?php elseif ($isMemberLoggedIn): ?>
            <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" href="<?= accountUrl() ?>">
                <i class="bi bi-person-circle"></i>
                <span>حساب من</span>
            </a>
            <?php else: ?>
            <a class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" href="<?= loginUrl() ?>">
                <i class="bi bi-box-arrow-in-left"></i>
                <span>ورود</span>
            </a>
            <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" href="<?= registerUrl() ?>">
                <i class="bi bi-person-plus"></i>
                <span>ثبت‌نام</span>
            </a>
            <?php endif; ?>
            </div>

            <button type="button" id="menuToggle" class="jhd-icon-btn jhd-menu-toggle" aria-label="باز کردن منو" aria-expanded="false" aria-controls="siteDrawer"><i class="bi bi-list"></i></button>
        </div>
    </div>

    <!-- نوار ناوبری اصلی دسکتاپ -->
    <nav class="jhd-navbar-desktop d-none d-xl-block" aria-label="ناوبری اصلی دسکتاپ">
        <div class="container">
            <ul class="jhd-nav-list mb-0">
                <li><a href="<?= url() ?>" class="jhd-nav-link <?= $isActiveNav('/') ? 'active' : '' ?>" <?= $isActiveNav('/') ? 'aria-current="page"' : '' ?>><i class="bi bi-house-door ms-1"></i>خانه</a></li>
                <?= jhd_render_desktop_nav_item(['route'=>'news','label'=>'اخبار','icon'=>'bi-newspaper','types'=>['news']], $isActiveNav) ?>
                <?= jhd_render_desktop_nav_item(['route'=>'articles','label'=>'مقالات','icon'=>'bi-file-text','types'=>['article']], $isActiveNav, ['article']) ?>
                <?= jhd_render_desktop_nav_item(['route'=>'reports','label'=>'گزارش‌ها','icon'=>'bi-card-text','types'=>['report']], $isActiveNav, ['report']) ?>
                <?= jhd_render_desktop_nav_item(['route'=>'events','label'=>'رویدادها','icon'=>'bi-calendar-event','types'=>['program','religious','announcement']], $isActiveNav, ['programs','announcements','religious-activities']) ?>
                <?= jhd_render_desktop_nav_item(['route'=>'books','label'=>'کتاب‌ها','icon'=>'bi-book','types'=>[]], $isActiveNav, ['book']) ?>
                <?= jhd_render_desktop_nav_item(['route'=>'lessons','label'=>'درس‌ها','icon'=>'bi-mortarboard','types'=>[]], $isActiveNav, ['lesson']) ?>
                <?= jhd_render_desktop_nav_item(['route'=>'research','label'=>'پژوهش','icon'=>'bi-journal-richtext','types'=>['research']], $isActiveNav) ?>
                <?= jhd_render_desktop_nav_item(['route'=>'media','label'=>'رسانه','icon'=>'bi-play-circle','types'=>[]], $isActiveNav, ['videos','audios']) ?>
                <li class="jhd-has-sub">
                    <a href="<?= url('topics') ?>" class="jhd-nav-link <?= $isActiveNav('topics') || $isActiveNav('topic') ? 'active' : '' ?>" <?= ($isActiveNav('topics') || $isActiveNav('topic')) ? 'aria-current="page"' : '' ?> aria-haspopup="true"><i class="bi bi-diagram-3 ms-1"></i>موضوعات</a>
                    <?php if ($navTopicTree): ?>
                    <ul class="jhd-subnav" role="menu">
                        <?= jhd_render_topic_tree_nav($navTopicTree, 'desktop') ?>
                        <li class="jhd-subnav-all"><a href="<?= url('topics') ?>">همه موضوعات</a></li>
                    </ul>
                    <?php endif; ?>
                </li>
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
        <form action="<?= formUrl('search') ?>" method="get" role="search">
            <?= formRouteFields('search') ?>
            <label class="visually-hidden" for="drawer-search">جستجو در محتوا</label>
            <input id="drawer-search" name="q" type="search" placeholder="جستجو در محتوا..." maxlength="200" value="<?= sanitize($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="جستجو"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="jhd-drawer-body">
        <div class="drawer-section">ناوبری اصلی</div>
        <a href="<?= url() ?>" class="drawer-link <?= $isActiveNav('/') ? 'active' : '' ?>"><i class="bi bi-house"></i> خانه</a>
        <?= jhd_render_drawer_nav_item(['route'=>'news','label'=>'اخبار','icon'=>'bi-newspaper','types'=>['news']], $isActiveNav) ?>
        <?= jhd_render_drawer_nav_item(['route'=>'articles','label'=>'مقالات','icon'=>'bi-file-text','types'=>['article']], $isActiveNav) ?>
        <?= jhd_render_drawer_nav_item(['route'=>'reports','label'=>'گزارش‌ها','icon'=>'bi-card-text','types'=>['report']], $isActiveNav) ?>
        <?= jhd_render_drawer_nav_item(['route'=>'events','label'=>'رویدادها','icon'=>'bi-calendar-event','types'=>['program','religious','announcement']], $isActiveNav) ?>
        <?= jhd_render_drawer_nav_item(['route'=>'books','label'=>'کتاب‌ها','icon'=>'bi-book','types'=>[]], $isActiveNav) ?>
        <?= jhd_render_drawer_nav_item(['route'=>'lessons','label'=>'درس‌ها','icon'=>'bi-mortarboard','types'=>[]], $isActiveNav) ?>
        <?= jhd_render_drawer_nav_item(['route'=>'research','label'=>'پژوهش','icon'=>'bi-journal-richtext','types'=>['research']], $isActiveNav) ?>
        <?= jhd_render_drawer_nav_item(['route'=>'media','label'=>'رسانه','icon'=>'bi-play-circle','types'=>[]], $isActiveNav) ?>

        <div class="drawer-section">موضوعات</div>
        <details class="jhd-acc" <?= ($isActiveNav('topics') || $isActiveNav('topic')) ? 'open' : '' ?>>
            <summary><i class="bi bi-diagram-3"></i> موضوعات</summary>
            <div class="jhd-acc-body">
                <a href="<?= url('topics') ?>" class="drawer-link drawer-all"><i class="bi bi-grid-3x3-gap"></i> همه موضوعات</a>
                <?= jhd_render_topic_tree_nav($navTopicTree, 'drawer') ?>
            </div>
        </details>

        <div class="drawer-section">اطلاعات و تماس</div>
        <a href="<?= url('qa') ?>" class="drawer-link"><i class="bi bi-question-circle"></i> پرسش و پاسخ</a>
        <a href="<?= url('about') ?>" class="drawer-link"><i class="bi bi-info-circle"></i> درباره جامعه‌الهدی</a>
        <a href="<?= url('contact') ?>" class="drawer-link"><i class="bi bi-envelope"></i> ارتباط با ما</a>

        <div class="drawer-section">حساب کاربری</div>
        <?php if ($isAdminLoggedIn): ?>
        <a href="<?= adminDashboardUrl() ?>" class="drawer-link"><i class="bi bi-speedometer2"></i> پنل مدیریت</a>
        <a href="<?= adminProfileUrl() ?>" class="drawer-link"><i class="bi bi-person-gear"></i> پروفایل من</a>
        <a href="<?= adminLogoutUrl() ?>" class="drawer-link text-danger"><i class="bi bi-box-arrow-right"></i> خروج از حساب</a>
        <?php elseif ($isMemberLoggedIn): ?>
        <a href="<?= accountUrl() ?>" class="drawer-link"><i class="bi bi-person-circle"></i> حساب کاربری</a>
        <a href="<?= url('password-change') ?>" class="drawer-link"><i class="bi bi-key"></i> تغییر رمز</a>
        <a href="<?= logoutUrl() ?>" class="drawer-link text-danger"><i class="bi bi-box-arrow-right"></i> خروج از حساب</a>
        <?php else: ?>
        <a href="<?= loginUrl() ?>" class="drawer-link"><i class="bi bi-box-arrow-in-left"></i> ورود</a>
        <a href="<?= registerUrl() ?>" class="drawer-link"><i class="bi bi-person-plus"></i> ثبت‌نام</a>
        <?php endif; ?>
    </div>
</aside>

<main id="main-content" tabindex="-1">
