<?php
/**
 * header.php - هدر عمومی سایت — نسخه ۳.۰ (منوی همبرگری چپ + Like System + Plyr)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

$categories = getCategories();
$siteName   = getSetting('site_name', SITE_NAME);
$siteSlogan = getSetting('site_slogan', SITE_SLOGAN);
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="theme-color" content="#0d1f13">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= sanitize($siteName) ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? sanitize($pageDesc) : sanitize($siteSlogan) ?>">
    <link rel="icon" href="<?= siteUrl('assets/images/favicon.svg') ?>" type="image/svg+xml">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Plyr Video Player CSS -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= siteUrl('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= siteUrl('assets/css/extra.css') ?>">

    <style>
    /* ─── Like Button ──────────────────────────────────── */
    .btn-like {
        display:inline-flex;align-items:center;gap:5px;
        background:none;border:1.5px solid #e0e0e0;border-radius:30px;
        padding:4px 12px;cursor:pointer;font-size:.85rem;
        color:#888;transition:all .25s;line-height:1;
        user-select:none;
    }
    .btn-like:hover { border-color:#e74c3c;color:#e74c3c;background:#fff5f5; }
    .btn-like.liked  { border-color:#e74c3c;color:#e74c3c;background:#fff0f0; }
    .btn-like .like-icon { font-size:1rem;transition:transform .2s; }
    .btn-like:active .like-icon,
    .btn-like.like-animate .like-icon { transform:scale(1.4); }
    .btn-like .like-count { font-weight:600;min-width:1ch; }

    /* ─── Video Player Wrapper ─────────────────────────── */
    .video-player-wrap { background:#000;border-radius:12px;overflow:hidden;margin:16px 0; }
    .video-player-wrap .plyr { border-radius:12px; }
    .video-lazy-container { position:relative;cursor:pointer; }
    .video-lazy-poster {
        position:relative;border-radius:12px;overflow:hidden;
        background:#111;display:flex;align-items:center;justify-content:center;
        min-height:200px;
    }
    .video-lazy-poster img { width:100%;height:auto;object-fit:cover;display:block; }
    .video-play-overlay {
        position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
        background:rgba(0,0,0,.35);transition:background .2s;
    }
    .video-lazy-poster:hover .video-play-overlay { background:rgba(0,0,0,.5); }
    .video-play-btn {
        width:68px;height:68px;border-radius:50%;
        background:rgba(255,255,255,.92);display:flex;align-items:center;justify-content:center;
        box-shadow:0 4px 20px rgba(0,0,0,.4);transition:transform .2s;
    }
    .video-lazy-poster:hover .video-play-btn { transform:scale(1.12); }
    .video-play-btn i { font-size:1.8rem;color:#c0392b;margin-right:-3px; }

    /* نشانه‌گر ویدیو روی کارت */
    .video-badge-card {
        position:absolute;bottom:8px;right:8px;
        background:rgba(0,0,0,.72);color:#fff;
        border-radius:6px;padding:3px 8px;font-size:.72rem;
        display:flex;align-items:center;gap:4px;backdrop-filter:blur(4px);
    }

    /* ─── دکمه پخش ویدیو شاخص روی کارت ─────────── */
    .featured-video-play {
        position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
        width:54px;height:54px;border-radius:50%;
        background:rgba(229,57,53,.92);color:#fff;
        border:none;cursor:pointer;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 4px 16px rgba(0,0,0,.4);
        transition:all .25s;z-index:3;
    }
    .featured-video-play i { font-size:1.6rem;margin-left:-2px; }
    .featured-video-play:hover { background:#c0392b;transform:translate(-50%,-50%) scale(1.12); }

    /* ─── Modal پخش ویدیو شاخص از کارت ─────────── */
    .featured-video-modal {
        position:fixed;inset:0;z-index:99999;
        background:rgba(0,0,0,.85);
        display:flex;align-items:center;justify-content:center;
        padding:20px;backdrop-filter:blur(6px);
    }
    .featured-video-modal-inner {
        position:relative;max-width:1000px;width:100%;
        background:#000;border-radius:12px;overflow:hidden;
        box-shadow:0 20px 60px rgba(0,0,0,.6);
    }
    .featured-video-modal video { width:100%;max-height:80vh;display:block; }
    .featured-video-modal-close {
        position:absolute;top:-44px;left:0;
        background:rgba(255,255,255,.1);color:#fff;
        border:1px solid rgba(255,255,255,.2);
        width:36px;height:36px;border-radius:50%;
        font-size:1.2rem;cursor:pointer;
        display:flex;align-items:center;justify-content:center;
    }
    .featured-video-modal-close:hover { background:rgba(255,255,255,.2); }

    /* ─── منوی همبرگری سمت چپ (Offcanvas Mobile) ── */
    .hamburger-box {
        display:none;
        width:44px;height:44px;
        background:var(--primary,#1a7a4a);
        border-radius:10px;
        align-items:center;justify-content:center;
        cursor:pointer;border:none;
        box-shadow:0 3px 12px rgba(26,122,74,.35);
        transition:all .25s;
        flex-shrink:0;
    }
    .hamburger-box:hover {
        background:var(--primary-dark,#0f5c38);
        box-shadow:0 5px 18px rgba(26,122,74,.5);
        transform:translateY(-1px);
    }
    .hamburger-box .hb-line {
        display:block;width:20px;height:2px;
        background:#fff;border-radius:2px;
        transition:all .3s ease;position:relative;
    }
    .hamburger-box .hb-lines {
        display:flex;flex-direction:column;gap:5px;
    }
    .hamburger-box.open .hb-lines .hb-line:nth-child(1) {
        transform:translateY(7px) rotate(45deg);
    }
    .hamburger-box.open .hb-lines .hb-line:nth-child(2) {
        opacity:0;transform:scaleX(0);
    }
    .hamburger-box.open .hb-lines .hb-line:nth-child(3) {
        transform:translateY(-7px) rotate(-45deg);
    }

    /* Mobile Offcanvas Overlay */
    .mobile-nav-overlay {
        display:none;position:fixed;inset:0;
        background:rgba(0,0,0,.55);z-index:9998;
        backdrop-filter:blur(2px);
        opacity:0;transition:opacity .3s;
    }
    .mobile-nav-overlay.active { opacity:1; }

    /* Mobile Nav Drawer */
    .mobile-nav-drawer {
        position:fixed;top:0;left:0;bottom:0;
        width:min(85vw,320px);
        background:#fff;z-index:9999;
        transform:translateX(-100%);
        transition:transform .35s cubic-bezier(.4,0,.2,1);
        overflow-y:auto;
        box-shadow:4px 0 30px rgba(0,0,0,.18);
        display:flex;flex-direction:column;
    }
    .mobile-nav-drawer.open {
        transform:translateX(0);
    }
    .mobile-nav-header {
        background:var(--primary-dark,#0f5c38);
        padding:20px 16px 16px;
        display:flex;align-items:center;gap:12px;
    }
    .mobile-nav-header img {
        width:44px;height:44px;border-radius:50%;
        border:2px solid rgba(255,255,255,.4);object-fit:contain;background:#fff;
    }
    .mobile-nav-header .site-name-sm {
        color:#fff;font-size:.95rem;font-weight:700;
        font-family:'Amiri',serif;line-height:1.3;flex:1;
    }
    .mobile-nav-header .site-slogan-sm {
        color:rgba(255,255,255,.7);font-size:.75rem;
    }
    .mobile-nav-close {
        background:rgba(255,255,255,.15);border:none;
        color:#fff;width:34px;height:34px;border-radius:50%;
        display:flex;align-items:center;justify-content:center;
        cursor:pointer;font-size:1.1rem;flex-shrink:0;
        transition:background .2s;
    }
    .mobile-nav-close:hover { background:rgba(255,255,255,.3); }
    .mobile-nav-body { padding:12px 0;flex:1; }
    .mobile-nav-body a {
        display:flex;align-items:center;gap:10px;
        padding:12px 20px;color:var(--text-dark,#1a1a2e);
        font-size:.95rem;font-weight:500;
        border-bottom:1px solid #f5f5f5;
        text-decoration:none;transition:all .2s;
    }
    .mobile-nav-body a:hover,
    .mobile-nav-body a.active {
        background:var(--primary-soft,#e8f5ee);
        color:var(--primary,#1a7a4a);padding-right:28px;
    }
    .mobile-nav-body a i {
        width:20px;text-align:center;color:var(--primary,#1a7a4a);font-size:1rem;
    }
    .mobile-nav-section-title {
        padding:10px 20px 4px;
        font-size:.75rem;font-weight:700;
        color:var(--text-muted,#9898b0);text-transform:uppercase;
        letter-spacing:.06em;
    }
    .mobile-nav-search {
        padding:12px 16px;
        border-top:1px solid #f0f0f0;
    }
    .mobile-nav-search .form-control {
        border-radius:8px;border:1.5px solid #e0e0e0;
        font-family:inherit;font-size:.9rem;
        padding:8px 14px;
    }
    .mobile-nav-search .form-control:focus {
        border-color:var(--primary,#1a7a4a);
        box-shadow:0 0 0 3px rgba(26,122,74,.1);
    }
    @media(min-width:992px){
        .hamburger-box { display:none !important; }
        .mobile-nav-drawer,.mobile-nav-overlay { display:none !important; }
    }
    @media(max-width:991.98px){
        .hamburger-box { display:flex; }
    }
    </style>
    <!-- متغیر پایه‌ی سایت برای JS -->
    <script>
        window._siteBase = <?= json_encode(rtrim(siteUrl(), '/'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window._imgPlaceholder = <?= json_encode(siteUrl('assets/images/placeholder.svg'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
</head>
<body>

<!-- ─── Bismillah Bar ─────────────────────────────────────────── -->
<div class="bismillah-bar">
    <span>بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</span>
</div>

<!-- ─── Top Bar ───────────────────────────────────────────────── -->
<div class="top-bar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="top-bar-right">
                <span><i class="bi bi-telephone-fill ms-1"></i><?= sanitize(getSetting('phone', SITE_PHONE)) ?></span>
                <span class="mx-2 d-none d-sm-inline">|</span>
                <span class="d-none d-sm-inline"><i class="bi bi-envelope-fill ms-1"></i><?= sanitize(getSetting('email', SITE_EMAIL)) ?></span>
            </div>
            <div class="top-bar-left d-flex align-items-center gap-2 gap-md-3">
                <span class="text-muted small d-none d-lg-inline"><i class="bi bi-calendar3 ms-1"></i><?= persianDate(date('Y-m-d')) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ─── Mobile Nav Overlay ────────────────────────────────────── -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

<!-- ─── Mobile Offcanvas Drawer ───────────────────────────────── -->
<div class="mobile-nav-drawer" id="mobileNavDrawer" role="navigation" aria-label="منوی موبایل">
    <div class="mobile-nav-header">
        <img src="<?= siteUrl('assets/images/logo.jpg') ?>" alt="لوگو" onerror="this.src='<?= siteUrl('assets/images/placeholder.svg') ?>'">
        <div class="flex-1">
            <div class="site-name-sm"><?= sanitize($siteName) ?></div>
            <div class="site-slogan-sm"><?= sanitize(mb_strimwidth($siteSlogan, 0, 42, '...')) ?></div>
        </div>
        <button class="mobile-nav-close" id="mobileNavClose" aria-label="بستن منو">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="mobile-nav-body">
        <div class="mobile-nav-section-title">صفحات اصلی</div>
        <a href="<?= siteUrl() ?>" class="<?= $currentPage==='index.php'?'active':'' ?>">
            <i class="bi bi-house-fill"></i>صفحه اصلی
        </a>
        <a href="<?= siteUrl('about.php') ?>" class="<?= $currentPage==='about.php'?'active':'' ?>">
            <i class="bi bi-info-circle-fill"></i>درباره ما
        </a>
        <a href="<?= siteUrl('religious-activities.php') ?>" class="<?= $currentPage==='religious-activities.php'?'active':'' ?>">
            <i class="bi bi-star-fill"></i>فعالیت‌های مذهبی
        </a>

        <div class="mobile-nav-section-title">محتوا</div>
        <a href="<?= siteUrl('news.php') ?>" class="<?= $currentPage==='news.php'?'active':'' ?>">
            <i class="bi bi-newspaper"></i>اخبار
        </a>
        <a href="<?= siteUrl('articles.php') ?>" class="<?= $currentPage==='articles.php'?'active':'' ?>">
            <i class="bi bi-file-text"></i>مقالات
        </a>
        <a href="<?= siteUrl('announcements.php') ?>" class="<?= $currentPage==='announcements.php'?'active':'' ?>">
            <i class="bi bi-megaphone"></i>اطلاعیه‌ها
        </a>
        <a href="<?= siteUrl('speeches.php') ?>" class="<?= $currentPage==='speeches.php'?'active':'' ?>">
            <i class="bi bi-mic-fill"></i>سخنرانی‌ها
        </a>
        <a href="<?= siteUrl('lessons.php') ?>" class="<?= $currentPage==='lessons.php'?'active':'' ?>">
            <i class="bi bi-play-circle-fill"></i>درس‌ها
        </a>
        <a href="<?= siteUrl('books.php') ?>" class="<?= $currentPage==='books.php'?'active':'' ?>">
            <i class="bi bi-book-fill"></i>کتاب‌ها
        </a>
        <a href="<?= siteUrl('programs.php') ?>" class="<?= $currentPage==='programs.php'?'active':'' ?>">
            <i class="bi bi-calendar-check"></i>برنامه‌های آموزشی
        </a>

        <?php if (!empty($categories)): ?>
        <div class="mobile-nav-section-title">دسته‌بندی‌ها</div>
        <?php foreach ($categories as $cat): ?>
            <?php if ($cat['post_count'] > 0): ?>
            <a href="<?= siteUrl('category.php?slug=' . urlencode($cat['slug'])) ?>">
                <i class="bi bi-tag"></i><?= sanitize($cat['name']) ?>
                <span class="badge bg-secondary ms-auto"><?= (int)$cat['post_count'] ?></span>
            </a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="mobile-nav-section-title">ارتباط</div>
        <a href="<?= siteUrl('contact.php') ?>" class="<?= $currentPage==='contact.php'?'active':'' ?>">
            <i class="bi bi-envelope-fill"></i>تماس با ما
        </a>
    </div>
    <!-- جستجو در موبایل -->
    <div class="mobile-nav-search">
        <form action="<?= siteUrl('search.php') ?>" method="get">
            <div class="input-group">
                <input class="form-control" type="search" name="q" placeholder="جستجو در سایت..." value="<?= sanitize($_GET['q'] ?? '') ?>">
                <button class="btn btn-gold" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Main Header ───────────────────────────────────────────── -->
<header class="main-header">
    <div class="container">
        <div class="row align-items-center py-3">
            <!-- همبرگری (فقط موبایل) — سمت چپ -->
            <div class="col-auto d-lg-none order-3">
                <button class="hamburger-box" id="hamburgerBtn" aria-label="منو" aria-expanded="false" aria-controls="mobileNavDrawer">
                    <div class="hb-lines">
                        <span class="hb-line"></span>
                        <span class="hb-line"></span>
                        <span class="hb-line"></span>
                    </div>
                </button>
            </div>
            <!-- لوگو -->
            <div class="col-auto order-1">
                <a href="<?= siteUrl() ?>">
                    <img src="<?= siteUrl('assets/images/logo.jpg') ?>" alt="لوگوی مدرسه" class="site-logo" onerror="this.src='<?= siteUrl('assets/images/placeholder.svg') ?>'">
                </a>
            </div>
            <!-- نام سایت -->
            <div class="col order-2">
                <div class="site-title-block">
                    <h1 class="site-name">
                        <a href="<?= siteUrl() ?>"><?= sanitize($siteName) ?></a>
                    </h1>
                    <p class="site-slogan d-none d-sm-block"><?= sanitize($siteSlogan) ?></p>
                </div>
            </div>
            <!-- جستجو (دسکتاپ) -->
            <div class="col-auto order-4 d-none d-lg-block">
                <form action="<?= siteUrl('search.php') ?>" method="get" class="search-form-header">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control" placeholder="جستجو در سایت..." value="<?= sanitize($_GET['q'] ?? '') ?>">
                        <button class="btn btn-gold" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- ─── Navigation (Desktop) ─────────────────────────────────── -->
<nav class="main-nav navbar navbar-expand-lg">
    <div class="container">
        <div class="collapse navbar-collapse show d-none d-lg-block" id="mainNav">
            <ul class="navbar-nav me-auto mb-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='index.php'?'active':'' ?>" href="<?= siteUrl() ?>">
                        <i class="bi bi-house-fill"></i> صفحه اصلی
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='about.php'?'active':'' ?>" href="<?= siteUrl('about.php') ?>">
                        <i class="bi bi-info-circle-fill"></i> درباره ما
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentPage,['news.php','articles.php','announcements.php','speeches.php','programs.php'])?'active':'' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-collection-fill"></i> محتوا
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= siteUrl('news.php') ?>"><i class="bi bi-newspaper ms-2"></i>اخبار</a></li>
                        <li><a class="dropdown-item" href="<?= siteUrl('articles.php') ?>"><i class="bi bi-file-text ms-2"></i>مقالات</a></li>
                        <li><a class="dropdown-item" href="<?= siteUrl('announcements.php') ?>"><i class="bi bi-megaphone ms-2"></i>اطلاعیه‌ها</a></li>
                        <li><a class="dropdown-item" href="<?= siteUrl('speeches.php') ?>"><i class="bi bi-mic ms-2"></i>سخنرانی‌ها</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= siteUrl('books.php') ?>"><i class="bi bi-book ms-2"></i>کتاب‌ها</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= siteUrl('programs.php') ?>"><i class="bi bi-calendar-check ms-2"></i>برنامه‌های آموزشی</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='lessons.php'?'active':'' ?>" href="<?= siteUrl('lessons.php') ?>">
                        <i class="bi bi-play-circle-fill"></i> درس‌ها
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='religious-activities.php'?'active':'' ?>" href="<?= siteUrl('religious-activities.php') ?>">
                        <i class="bi bi-star-fill"></i> فعالیت‌های مذهبی
                    </a>
                </li>
                <?php if (!empty($categories)): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-grid-fill"></i> دسته‌بندی‌ها
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($categories as $cat): ?>
                            <?php if ($cat['post_count'] > 0): ?>
                            <li>
                                <a class="dropdown-item" href="<?= siteUrl('category.php?slug=' . urlencode($cat['slug'])) ?>">
                                    <?= sanitize($cat['name']) ?>
                                    <span class="badge bg-secondary float-start"><?= $cat['post_count'] ?></span>
                                </a>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='contact.php'?'active':'' ?>" href="<?= siteUrl('contact.php') ?>">
                        <i class="bi bi-envelope-fill"></i> تماس با ما
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
// ─── منوی همبرگری موبایل ──────────────────────────────────────
(function () {
    var btn     = document.getElementById('hamburgerBtn');
    var drawer  = document.getElementById('mobileNavDrawer');
    var overlay = document.getElementById('mobileNavOverlay');
    var closeBtn = document.getElementById('mobileNavClose');
    if (!btn || !drawer || !overlay) return;

    overlay.style.display = 'block';
    overlay.style.display = '';

    function openMenu() {
        drawer.classList.add('open');
        overlay.classList.add('active');
        overlay.style.display = 'block';
        btn.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    function closeMenu() {
        drawer.classList.remove('open');
        overlay.classList.remove('active');
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        setTimeout(function () {
            if (!drawer.classList.contains('open')) overlay.style.display = '';
        }, 350);
    }

    btn.addEventListener('click', function () {
        drawer.classList.contains('open') ? closeMenu() : openMenu();
    });
    overlay.addEventListener('click', closeMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);

    // بستن با کلید Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('open')) closeMenu();
    });

    // بستن هنگام کلیک روی لینک‌های منو
    drawer.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            // فقط اگر به صفحه دیگری می‌رود (نه # )
            var href = link.getAttribute('href');
            if (href && href !== '#') closeMenu();
        });
    });
})();
</script>
