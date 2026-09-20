<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
startSecureSession();
$categories = getCategories();
$siteName = getSetting('site_name', SITE_NAME);
$siteSlogan = getSetting('site_slogan', SITE_SLOGAN);
$currentPage = basename($_SERVER['PHP_SELF']);
$nav = [''=>'خانه','about.php'=>'مدرسه ما','lessons.php'=>'دروس','articles.php'=>'پژوهش و مقالات','books.php'=>'کتابخانه','video'=>'ویدیو','audio'=>'صوت','news.php'=>'اخبار','contact.php'=>'تماس'];
$metaTitle = !empty($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName;
$metaDesc = $pageDesc ?? $siteSlogan;
$canonicalPath = $_SERVER['SCRIPT_NAME'] ?? '/';
if (basename($canonicalPath)==='index.php') $canonicalPath=rtrim(dirname($canonicalPath), '/').'/';
if ($currentPage==='book.php' && !empty($_GET['id'])) $canonicalPath .= '?id='.(int)$_GET['id'];
if (!empty($_GET['slug'])) $canonicalPath .= '?slug='.rawurlencode($_GET['slug']);
$canonical = SITE_URL ? rtrim(SITE_URL,'/') . '/' . ltrim(substr($canonicalPath, strlen(BASE_PATH)),'/') : '';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#163b37">
<meta name="plyr-sprite" content="<?= siteUrl('assets/vendor/plyr.svg') ?>">
<meta name="csrf-token" content="<?= sanitize(generateCsrfToken()) ?>">
<title><?= sanitize($metaTitle) ?></title>
<meta name="description" content="<?= sanitize($metaDesc) ?>">
<?php if ($canonical): ?><link rel="canonical" href="<?= sanitize($canonical) ?>"><meta property="og:url" content="<?= sanitize($canonical) ?>"><?php endif; ?>
<meta property="og:locale" content="fa_AF"><meta property="og:type" content="<?= isset($post) ? 'article' : 'website' ?>">
<meta property="og:title" content="<?= sanitize($metaTitle) ?>"><meta property="og:description" content="<?= sanitize($metaDesc) ?>">
<meta name="twitter:card" content="summary"><meta name="twitter:title" content="<?= sanitize($metaTitle) ?>"><meta name="twitter:description" content="<?= sanitize($metaDesc) ?>">
<?php if (SITE_URL): ?><script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'EducationalOrganization','name'=>$siteName,'url'=>SITE_URL], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><?php endif; ?>
<link rel="icon" href="<?= siteUrl('assets/images/favicon.svg') ?>" type="image/svg+xml">
<script src="<?= siteUrl('assets/js/theme.js') ?>"></script>
<link rel="preload" href="<?= siteUrl('assets/fonts/Vazirmatn-Regular.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= siteUrl('assets/vendor/bootstrap.rtl.min.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/vendor/icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/vendor/plyr.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/css/extra.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/css/legacy-components.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/css/design-system.css') ?>">
<script src="<?= siteUrl('assets/js/interface.js') ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>
<div class="jhd-masthead"><div class="container"><span>بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِیمِ</span><span>آموزش، پژوهش و پرورش در پرتو معارف اسلامی</span><a href="<?= siteUrl('contact.php') ?>">همراه شما در مسیر دانایی <i class="bi bi-arrow-up-left"></i></a></div></div>
<header class="jhd-header">
<div class="container jhd-header-row">
<a class="jhd-brand" href="<?= siteUrl() ?>" aria-label="جامعه‌الهدی، صفحه اصلی"><img src="<?= imgUrl(getSetting('site_logo','assets/images/logo.jpg')) ?>" width="56" height="56" alt="نشان مدرسه"><span><strong>جامعه‌الهدی</strong><small>مدرسه علمیه · کابل، افغانستان</small></span></a>
<form class="jhd-search" action="<?= siteUrl('search.php') ?>" role="search"><label class="visually-hidden" for="header-search">جستجو در منابع</label><input id="header-search" name="q" type="search" placeholder="در جستجوی چه دانشی هستید؟" maxlength="200"><button aria-label="جستجو"><i class="bi bi-search"></i></button></form>
<div class="jhd-header-actions"><a class="jhd-icon-btn jhd-mobile-search" href="<?= siteUrl('search.php') ?>" aria-label="جستجو"><i class="bi bi-search"></i></a><button class="jhd-icon-btn" data-theme-toggle aria-label="تغییر پوسته روشن و تاریک" aria-pressed="false"><i class="bi bi-moon"></i></button><a class="jhd-login" href="<?= siteUrl('admin/login.php') ?>"><i class="bi bi-person"></i><span>ورود مدیران</span></a><button id="menuToggle" class="jhd-icon-btn jhd-menu-toggle" aria-label="باز کردن منو" aria-expanded="false" aria-controls="siteNavigation"><i class="bi bi-list"></i></button></div>
</div>
<nav id="siteNavigation" class="jhd-navigation" aria-label="منوی اصلی"><div class="container jhd-nav-inner">
<?php foreach ($nav as $href=>$label): ?><a href="<?= siteUrl($href) ?>" <?= $currentPage===($href ?: 'index.php') ? 'aria-current="page"' : '' ?>><?= $label ?></a><?php endforeach; ?>
<a class="jhd-nav-cta" href="<?= siteUrl('contact.php') ?>">راهنمای پذیرش <i class="bi bi-arrow-up-left"></i></a>
</div></nav>
</header>
<main id="main-content" tabindex="-1">
