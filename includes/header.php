<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
startSecureSession();
$siteName = getSetting('site_name', SITE_NAME);
$siteSlogan = getSetting('site_slogan', SITE_SLOGAN);
$currentPage = basename($_SERVER['PHP_SELF']);
$currentSlug = $_GET['slug'] ?? '';
$isLoggedIn = isLoggedIn();
$topicsTree = getTopics(['active'=>1, 'parent'=>null]);
// fallback: ensure at least root topics for menu
if(empty($topicsTree)){
    try{ $topicsTree = getTopics(['active'=>1]); }catch(Exception $e){ $topicsTree=[]; }
}
$metaTitle = !empty($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName . ' | ' . $siteSlogan;
$metaDesc = $pageDesc ?? $siteSlogan;
if(mb_strlen($metaDesc,'UTF-8')>160) $metaDesc = mb_substr($metaDesc,0,157,'UTF-8').'...';
$canonicalPath = $_SERVER['SCRIPT_NAME'] ?? '/';
if (basename($canonicalPath)==='index.php') $canonicalPath=rtrim(dirname($canonicalPath), '/').'/';
if ($currentPage==='book.php' && !empty($_GET['id'])) $canonicalPath .= '?id='.(int)$_GET['id'];
if (!empty($_GET['slug'])) $canonicalPath .= '?slug='.rawurlencode($_GET['slug']);
if (!empty($_GET['q'])) $canonicalPath .= '?q='.rawurlencode(mb_substr($_GET['q'],0,80));
$canonical = SITE_URL ? rtrim(SITE_URL,'/') . '/' . ltrim(substr($canonicalPath, strlen(BASE_PATH)),'/') : '';
$ogType = isset($post) || isset($book) || isset($lesson) ? 'article' : 'website';
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
<meta property="og:locale" content="fa_AF"><meta property="og:type" content="<?= $ogType ?>">
<meta property="og:title" content="<?= sanitize($metaTitle) ?>"><meta property="og:description" content="<?= sanitize($metaDesc) ?>">
<meta property="og:site_name" content="<?= sanitize($siteName) ?>">
<?php if(!empty($post['featured_image']) || !empty($book['cover_image']) || !empty($lesson['featured_image'])): $ogImg = imgUrl($post['featured_image'] ?? $book['cover_image'] ?? $lesson['featured_image'] ?? ''); if($ogImg): ?><meta property="og:image" content="<?= sanitize($ogImg) ?>"><?php endif; endif; ?>
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?= sanitize($metaTitle) ?>"><meta name="twitter:description" content="<?= sanitize($metaDesc) ?>">
<?php if (SITE_URL): ?>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'EducationalOrganization','name'=>$siteName,'url'=>SITE_URL, 'description'=>$siteSlogan], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'WebSite','name'=>$siteName,'url'=>SITE_URL,'potentialAction'=>['@type'=>'SearchAction','target'=> SITE_URL.'/search.php?q={search_term_string}','query-input'=>'required name=search_term_string']], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<?php endif; ?>
<?php if(!empty($breadcrumbsJsonLd)): ?><script type="application/ld+json"><?= $breadcrumbsJsonLd ?></script><?php endif; ?>
<?php if(!empty($articleJsonLd)): ?><script type="application/ld+json"><?= $articleJsonLd ?></script><?php endif; ?>
<?php if(!empty($bookJsonLd)): ?><script type="application/ld+json"><?= $bookJsonLd ?></script><?php endif; ?>
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
<form class="jhd-search d-none d-md-flex" action="<?= siteUrl('search.php') ?>" role="search"><label class="visually-hidden" for="header-search">جستجو در منابع</label><input id="header-search" name="q" type="search" placeholder="جستجو در مقالات، گزارش‌ها، کتاب‌ها و دروس..." maxlength="200" value="<?= sanitize($_GET['q'] ?? '') ?>"><button aria-label="جستجو"><i class="bi bi-search"></i></button></form>
<div class="jhd-header-actions">
<a class="jhd-icon-btn jhd-mobile-search d-md-none" href="<?= siteUrl('search.php') ?>" aria-label="جستجو"><i class="bi bi-search"></i></a>

<button id="menuToggle" class="jhd-icon-btn jhd-menu-toggle" aria-label="باز کردن منو" aria-expanded="false" aria-controls="siteDrawer"><i class="bi bi-list"></i></button>
</div>
</div>
</header>
<!-- Drawer Overlay -->
<div id="drawerOverlay" class="jhd-drawer-overlay" hidden></div>
<!-- Hamburger Drawer (spec 12) -->
<nav id="siteDrawer" class="jhd-drawer" aria-label="منوی اصلی" aria-hidden="true" inert>
<div class="jhd-drawer-head">
<span><i class="bi bi-grid ms-2"></i> فهرست</span>
<button id="drawerClose" class="jhd-icon-btn" aria-label="بستن منو"><i class="bi bi-x-lg"></i></button>
</div>
<div class="jhd-drawer-search">
<form action="<?= siteUrl('search.php') ?>" role="search"><input name="q" type="search" placeholder="جستجو..." maxlength="200" value="<?= sanitize($_GET['q'] ?? '') ?>"><button aria-label="جستجو"><i class="bi bi-search"></i></button></form>
</div>
<div class="jhd-drawer-body">
<a href="<?= siteUrl() ?>" class="drawer-link <?= $currentPage==='index.php'?'active':'' ?>"><i class="bi bi-house"></i> خانه</a>

<div class="drawer-section">موضوعات</div>
<?php
// Build hierarchical menu from topicsTree (max 3 levels)
$allTopics = getTopics(['active'=>1]);
$byParent = [];
foreach($allTopics as $t){ $pid = $t['parent_id']===null?0:(int)$t['parent_id']; $byParent[$pid][]=$t; }
function renderDrawerTopics($parentId, $byParent, $depth=0){
    $pid = $parentId===null?0:$parentId;
    if(empty($byParent[$pid])) return '';
    $html='';
    foreach($byParent[$pid] as $tp){
        $hasChildren = !empty($byParent[(int)$tp['id']]);
        $html.='<a href="'.siteUrl('topic.php?slug='.urlencode($tp['slug'])).'" class="drawer-link drawer-topic depth-'.$depth.'"><i class="bi bi-'.($depth===0?'folder':'tag').'"></i>'.sanitize($tp['name']);
        if($hasChildren) $html.=' <i class="bi bi-chevron-down ms-auto" style="font-size:.7rem"></i>';
        $html.='</a>';
        if($hasChildren && $depth<2){
            $html.='<div class="drawer-sub">'.renderDrawerTopics((int)$tp['id'],$byParent,$depth+1).'</div>';
        }
    }
    return $html;
}
echo renderDrawerTopics(null,$byParent);
?>
<a href="<?= siteUrl('topics.php') ?>" class="drawer-link drawer-all"><i class="bi bi-grid-3x3-gap"></i> همه موضوعات</a>

<div class="drawer-section">محتوا</div>
<a href="<?= siteUrl('reports.php') ?>" class="drawer-link"><i class="bi bi-newspaper"></i> گزارش‌ها</a>
<a href="<?= siteUrl('articles.php') ?>" class="drawer-link"><i class="bi bi-file-text"></i> مقالات و پژوهش‌ها</a>
<a href="<?= siteUrl('research.php') ?>" class="drawer-link drawer-subtle"><i class="bi bi-journal-richtext"></i> پژوهش‌ها (بایگانی)</a>
<a href="<?= siteUrl('books.php') ?>" class="drawer-link"><i class="bi bi-book"></i> کتابخانه</a>

<div class="drawer-section">دروس حوزه</div>
<?php $cols = getLessonCollections(['active'=>1]); foreach($cols as $col): 
  $cVols = getLessonVolumes((int)$col['id']);
?>
<a href="<?= siteUrl('lessons.php?collection='.urlencode($col['slug'])) ?>" class="drawer-link"><i class="bi bi-mortarboard"></i> <?= sanitize($col['title']) ?></a>
<?php if($cVols): foreach($cVols as $cv): ?>
<a href="<?= siteUrl('lessons.php?collection='.urlencode($col['slug']).'&volume='.urlencode($cv['slug'])) ?>" class="drawer-link drawer-subtle" style="padding-inline-start:22px"><i class="bi bi-journals"></i> <?= sanitize($cv['title']) ?></a>
<?php endforeach; endif; ?>
<?php endforeach; ?>
<a href="<?= siteUrl('lessons.php') ?>" class="drawer-link"><i class="bi bi-play-circle"></i> همه دروس</a>

<div class="drawer-section">رسانه</div>
<a href="<?= siteUrl('media-library.php?kind=video') ?>" class="drawer-link"><i class="bi bi-camera-video"></i> ویدیو</a>
<a href="<?= siteUrl('media-library.php?kind=audio') ?>" class="drawer-link"><i class="bi bi-headphones"></i> صوت</a>
<a href="<?= siteUrl('speeches.php') ?>" class="drawer-link"><i class="bi bi-mic"></i> سخنرانی</a>

<a href="<?= siteUrl('qa.php') ?>" class="drawer-link mt-2"><i class="bi bi-question-circle"></i> پرسش و پاسخ</a>
<a href="<?= siteUrl('about.php') ?>" class="drawer-link"><i class="bi bi-info-circle"></i> درباره مدرسه</a>
<a href="<?= siteUrl('contact.php') ?>" class="drawer-link"><i class="bi bi-envelope"></i> تماس با ما</a>
<a href="<?= siteUrl('search.php') ?>" class="drawer-link"><i class="bi bi-search"></i> جستجو</a>

<div class="drawer-section">حساب</div>
<?php if($isLoggedIn): ?>
<a href="<?= siteUrl('admin/') ?>" class="drawer-link"><i class="bi bi-speedometer2"></i> ورود به پنل مدیریت</a>
<a href="<?= siteUrl('admin/logout.php') ?>" class="drawer-link" data-confirm="خروج؟"><i class="bi bi-box-arrow-right"></i> خروج</a>
<?php else: ?>
<a href="<?= siteUrl('admin/login.php') ?>" class="drawer-link"><i class="bi bi-box-arrow-in-left"></i> ورود</a>
<?php endif; ?>
</div>
</nav>
<script>
// Drawer logic — accessible, no profile display per spec 2
document.addEventListener('DOMContentLoaded', function(){
  const toggle = document.getElementById('menuToggle');
  const drawer = document.getElementById('siteDrawer');
  const overlay = document.getElementById('drawerOverlay');
  const closeBtn = document.getElementById('drawerClose');
  function open(){ drawer.removeAttribute('inert'); drawer.setAttribute('aria-hidden','false'); drawer.classList.add('open'); overlay.hidden=false; overlay.classList.add('show'); toggle.setAttribute('aria-expanded','true'); document.body.style.overflow='hidden'; }
  function close(){ drawer.setAttribute('aria-hidden','true'); drawer.classList.remove('open'); overlay.classList.remove('show'); setTimeout(()=>{overlay.hidden=true; drawer.setAttribute('inert','');},250); toggle.setAttribute('aria-expanded','false'); document.body.style.overflow=''; toggle.focus(); }
  toggle?.addEventListener('click', ()=> drawer.classList.contains('open')?close():open());
  closeBtn?.addEventListener('click', close);
  overlay?.addEventListener('click', close);
  document.addEventListener('keydown', e=>{ if(e.key==='Escape' && drawer.classList.contains('open')) close(); });
});
</script>
<main id="main-content" tabindex="-1">
