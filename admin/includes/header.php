<?php
/**
 * admin/includes/header.php — هدر و سایدبار پنل مدیریت جامعه‌الهدی
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if (preg_match('~/admin/(settings|messages|media|users)([/.]|$)~', $_SERVER['SCRIPT_NAME'] ?? '')) {
    requireRole(['superadmin','admin']);
}
require_once __DIR__ . '/../../includes/admin-actions.php';
$admin = currentAdmin();
$currentAdminPage = basename($_SERVER['PHP_SELF']);
$currentAdminDir  = basename(dirname($_SERVER['PHP_SELF']));
$currentRoutePath = current_path();
$isSuperAdmin = ($admin['role'] ?? '') === 'superadmin';
$isAdminRole = in_array($admin['role'] ?? '', ['superadmin', 'admin'], true);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($adminTitle) ? sanitize($adminTitle) . ' — ' : '' ?>پنل مدیریت | <?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= asset('vendor/bootstrap.rtl.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">

<style>
:root {
  --admin-sidebar: #0f241a;
  --admin-sidebar-hover: #183d2c;
  --admin-sidebar-active: #23543d;
  --admin-gold: #c9a84c;
  --admin-green: #2d6a4f;
  --admin-bg: #f4f6f5;
  --admin-white: #ffffff;
  --admin-border: #e2e8e5;
  --admin-text: #1b2e24;
  --admin-muted: #6b7d74;
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Vazirmatn', sans-serif; background: var(--admin-bg); color: var(--admin-text); margin: 0; }
/* Guard: the panel must never scroll sideways on phones. `clip` (not `hidden`)
   keeps the fixed sidebar and sticky topbar working. */
html, body { max-width: 100%; }
body { overflow-x: clip; }

/* Sidebar */
.admin-sidebar {
  width: 256px; background: var(--admin-sidebar); min-height: 100vh;
  position: fixed; top: 0; right: 0; bottom: 0; z-index: 100;
  display: flex; flex-direction: column;
  transition: transform .3s ease;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.15) transparent;
}
.sidebar-brand { padding: 18px 16px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(0,0,0,.15); }
.sidebar-brand img { width: 42px; height: 42px; border-radius: 50%; border: 2px solid var(--admin-gold); object-fit: contain; }
.sidebar-brand .name { color: var(--admin-gold); font-weight: 700; font-size: .88rem; margin-right: 10px; }
.sidebar-brand .sub { color: rgba(255,255,255,.45); font-size: .72rem; display: block; margin-right: 10px; }
.sidebar-nav { flex: 1; padding: 10px 0; }
.sidebar-section {
  padding: 10px 16px 4px;
  font-size: .68rem;
  color: rgba(255,255,255,.4);
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-top: 6px;
  font-weight: 700;
}
.sidebar-link {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 16px;
  color: rgba(255,255,255,.76);
  font-size: .86rem;
  text-decoration: none;
  transition: all .18s;
  border-right: 3px solid transparent;
  position: relative;
}
.sidebar-link i { font-size: .96rem; width: 20px; text-align: center; color: var(--admin-gold); }
.sidebar-link:hover { background: var(--admin-sidebar-hover); color: #fff; }
.sidebar-link.active { background: var(--admin-sidebar-active); color: #fff; border-right-color: var(--admin-gold); font-weight: 700; }
.sidebar-footer { padding: 14px 16px; border-top: 1px solid rgba(255,255,255,.08); }
.sidebar-footer a { color: rgba(255,255,255,.65); font-size: .82rem; text-decoration: none; }
.sidebar-footer a:hover { color: #fff; }

/* Main content */
.admin-main { margin-right: 256px; min-height: 100vh; display: flex; flex-direction: column; }

/* Topbar */
.admin-topbar {
  background: var(--admin-white);
  border-bottom: 1px solid var(--admin-border);
  padding: 12px 24px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 50;
  box-shadow: 0 1px 6px rgba(0,0,0,.03);
}
.topbar-title { font-weight: 700; font-size: 1rem; color: var(--admin-text); }
.topbar-user { display: flex; align-items: center; gap: 12px; }
.topbar-user .avatar {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--admin-green), #1b4332);
  color: #fff; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: .88rem;
}

/* Content */
.admin-content { flex: 1; padding: 24px; }

/* Cards */
.admin-card { background: var(--admin-white); border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.04); border: 1px solid var(--admin-border); }
.admin-card-header { padding: 14px 20px; border-bottom: 1px solid var(--admin-border); font-weight: 700; font-size: .92rem; display: flex; align-items: center; justify-content: space-between; }
.admin-card-body { padding: 20px; }

/* Table */
.admin-table th { background: #f8faf9; font-weight: 600; font-size: .82rem; color: var(--admin-muted); border-bottom: 2px solid var(--admin-border); }
.admin-table td { font-size: .86rem; vertical-align: middle; }
.admin-table tr:hover td { background: #f6fcf8; }

/* Responsive */
#sidebarToggle { display: none; }
@media (max-width: 991px) {
  .admin-sidebar { transform: translateX(100%); display: flex; }
  .admin-sidebar.open { transform: translateX(0); }
  .admin-main { margin-right: 0; }
  #sidebarToggle { display: inline-flex; }
  .admin-content { padding: 16px; }
}
/* Shared finishing touches for every administration screen. */
.admin-content h1, .admin-content h2, .admin-content h3, .admin-content h4 { color: var(--admin-text); font-weight: 800; letter-spacing: -.02em; }
.admin-card { border-radius: 16px; box-shadow: 0 8px 28px rgba(18, 43, 29, .055); transition: border-color .18s ease, box-shadow .18s ease; }
.admin-card-header { min-height: 52px; background: linear-gradient(100deg, rgba(45,106,79,.045), transparent 75%); }
.admin-table { --bs-table-color: var(--admin-text); --bs-table-bg: var(--admin-white); --bs-table-border-color: var(--admin-border); }
.admin-table th { white-space: nowrap; }
.admin-table td { line-height: 1.65; }
.admin-content .form-control, .admin-content .form-select { min-height: 42px; border-radius: 9px; border-color: var(--admin-border); }
.admin-content textarea.form-control { min-height: 120px; }
.admin-content .form-control:focus, .admin-content .form-select:focus { border-color: var(--admin-green); box-shadow: 0 0 0 4px rgba(45,106,79,.12); }
.admin-content .btn { border-radius: 9px; font-weight: 650; }
.admin-content .alert { border: 0; border-radius: 12px; box-shadow: 0 3px 12px rgba(18,43,29,.06); }
.admin-content .badge { font-weight: 650; letter-spacing: .01em; }
.admin-content :focus-visible, .admin-sidebar :focus-visible { outline: 3px solid var(--admin-gold); outline-offset: 2px; }
.admin-sidebar { scrollbar-gutter: stable; }
.admin-topbar { min-height: 64px; backdrop-filter: blur(14px); }
html[data-theme="dark"] {
  --admin-sidebar: #0b1711;
  --admin-sidebar-hover: #183327;
  --admin-sidebar-active: #234d38;
  --admin-bg: #101814;
  --admin-white: #17231c;
  --admin-border: #2b3b31;
  --admin-text: #e8f0eb;
  --admin-muted: #a0b2a8;
}
html[data-theme="dark"] body { background: var(--admin-bg); color: var(--admin-text); }
html[data-theme="dark"] .admin-topbar,
html[data-theme="dark"] .admin-card { background: var(--admin-white); color: var(--admin-text); border-color: var(--admin-border); }
html[data-theme="dark"] .admin-card-header { border-color: var(--admin-border); background: linear-gradient(100deg, rgba(92,203,163,.08), transparent 75%); }
html[data-theme="dark"] .admin-table th { background: #1d2c23; color: var(--admin-muted); }
html[data-theme="dark"] .admin-table td { background: var(--admin-white); color: var(--admin-text); border-color: var(--admin-border); }
html[data-theme="dark"] .admin-table tr:hover td { background: #1c2d23; }
html[data-theme="dark"] .admin-content .form-control,
html[data-theme="dark"] .admin-content .form-select { background-color: #111c16; color: var(--admin-text); border-color: var(--admin-border); }
html[data-theme="dark"] .admin-content .form-control::placeholder { color: var(--admin-muted); }
html[data-theme="dark"] .admin-content .text-muted { color: var(--admin-muted) !important; }
@media (max-width: 575.98px) {
  .admin-topbar { padding: 10px 14px; }
  .admin-content { padding: 12px; }
  .admin-card-body { padding: 15px; }
}
</style>
<script src="<?= asset('js/theme.js') ?>"></script>
<script src="<?= asset('js/interface.js') ?>" defer></script>
</head>
<body class="jhd-admin-site">

<!-- سایدبار مدیریت -->
<div class="admin-sidebar" id="adminSidebar" role="navigation" aria-label="منوی مدیریت">
  <div class="sidebar-brand d-flex align-items-center">
    <img src="<?= imgUrl(getSetting('site_logo', 'assets/img/logo.jpg')) ?>" alt="لوگو" onerror="this.src='<?= asset('img/placeholder.svg') ?>'">
    <div>
      <span class="name">جامعه‌الهدی</span>
      <span class="sub">سامانه جامع مدیریت</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">داشبورد</div>
    <a href="<?= url('admin') ?>" class="sidebar-link <?= ($currentAdminPage === 'index.php' && $currentAdminDir === 'admin') ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i>داشبورد
    </a>

    <div class="sidebar-section">مدیریت محتوا</div>
    <a href="<?= url('admin/content') ?>" class="sidebar-link <?= ($currentAdminDir === 'posts' && empty($_GET['type'])) ? 'active' : '' ?>">
      <i class="bi bi-collection"></i>کل محتوا (Content)
    </a>
    <a href="<?= url('admin/news') ?>" class="sidebar-link <?= ($currentAdminDir === 'news' || ($_GET['type'] ?? '') === 'news') ? 'active' : '' ?>">
      <i class="bi bi-newspaper"></i>اخبار مدرسه
    </a>
    <a href="<?= url('admin/articles') ?>" class="sidebar-link <?= ($currentAdminDir === 'articles' || ($_GET['type'] ?? '') === 'article') ? 'active' : '' ?>">
      <i class="bi bi-file-text"></i>مقالات علمی
    </a>
    <a href="<?= url('admin/content', ['type' => 'report']) ?>" class="sidebar-link <?= (($_GET['type'] ?? '') === 'report') ? 'active' : '' ?>">
      <i class="bi bi-card-text"></i>گزارش‌ها
    </a>
    <a href="<?= url('admin/content', ['type' => 'program']) ?>" class="sidebar-link <?= (in_array(($_GET['type'] ?? ''), ['program', 'religious', 'announcement'], true)) ? 'active' : '' ?>">
      <i class="bi bi-calendar-event"></i>رویدادها و برنامه‌ها
    </a>
    <a href="<?= url('admin/content', ['type' => 'research']) ?>" class="sidebar-link <?= (($_GET['type'] ?? '') === 'research') ? 'active' : '' ?>">
      <i class="bi bi-journal-richtext"></i>پژوهش‌ها
    </a>

    <div class="sidebar-section">کتابخانه و درس‌ها</div>
    <a href="<?= url('admin/books') ?>" class="sidebar-link <?= ($currentAdminDir === 'books') ? 'active' : '' ?>">
      <i class="bi bi-book"></i>کتاب‌ها
    </a>
    <a href="<?= url('admin/lessons') ?>" class="sidebar-link <?= ($currentAdminDir === 'lessons') ? 'active' : '' ?>">
      <i class="bi bi-mortarboard"></i>درس‌های حوزوی
    </a>
    <a href="<?= url('admin/lesson-collections') ?>" class="sidebar-link <?= ($currentAdminDir === 'lesson-collections') ? 'active' : '' ?>">
      <i class="bi bi-journals"></i>مجموعه‌های درسی
    </a>

    <div class="sidebar-section">رسانه و سخنرانی</div>
    <a href="<?= url('admin/media') ?>" class="sidebar-link <?= ($currentAdminDir === 'media') ? 'active' : '' ?>">
      <i class="bi bi-images"></i>مدیریت رسانه
    </a>
    <a href="<?= url('admin/speeches') ?>" class="sidebar-link <?= ($currentAdminDir === 'speeches' || ($_GET['type'] ?? '') === 'speech') ? 'active' : '' ?>">
      <i class="bi bi-mic"></i>سخنرانی‌ها
    </a>

    <div class="sidebar-section">طبقه‌بندی</div>
    <a href="<?= url('admin/topics') ?>" class="sidebar-link <?= ($currentAdminDir === 'topics') ? 'active' : '' ?>">
      <i class="bi bi-diagram-3"></i>موضوعات (ستون فقرات)
    </a>
    <a href="<?= url('admin/categories') ?>" class="sidebar-link <?= ($currentAdminDir === 'categories') ? 'active' : '' ?>">
      <i class="bi bi-folder"></i>دسته‌بندی‌ها
    </a>

    <div class="sidebar-section">تعامل و اعلان</div>
    <?php
    try { $unread = (int)getDB()->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(); } catch (\Throwable) { $unread = 0; }
    ?>
    <a href="<?= url('admin/messages') ?>" class="sidebar-link <?= ($currentAdminDir === 'messages' || $currentAdminPage === 'messages.php') ? 'active' : '' ?>">
      <i class="bi bi-envelope"></i>پیام‌های تماس
      <?php if ($unread > 0): ?><span class="badge bg-danger ms-auto"><?= $unread ?></span><?php endif; ?>
    </a>
    <a href="<?= url('admin/banners') ?>" class="sidebar-link <?= ($currentAdminDir === 'banners') ? 'active' : '' ?>">
      <i class="bi bi-megaphone"></i>بنر و اعلان ویژه
    </a>

    <div class="sidebar-section">سیستم و دسترسی</div>
    <?php if ($isSuperAdmin): ?>
    <a href="<?= url('admin/users') ?>" class="sidebar-link <?= ($currentAdminDir === 'users') ? 'active' : '' ?>">
      <i class="bi bi-people"></i>مدیران و ویراستاران
    </a>
    <?php endif; ?>
    <?php if ($isAdminRole): ?>
    <a href="<?= url('admin/members') ?>" class="sidebar-link <?= ($currentAdminDir === 'members') ? 'active' : '' ?>">
      <i class="bi bi-person-badge"></i>اعضای سایت
    </a>
    <a href="<?= url('admin/diagnostics') ?>" class="sidebar-link <?= ($currentAdminPage === 'diagnostics.php') ? 'active' : '' ?>">
      <i class="bi bi-heart-pulse"></i>وضعیت سامانه
    </a>
    <?php endif; ?>
    <?php if ($isAdminRole): ?>
    <a href="<?= url('admin/settings') ?>" class="sidebar-link <?= ($currentAdminPage === 'settings.php') ? 'active' : '' ?>">
      <i class="bi bi-gear"></i>تنظیمات سایت
    </a>
    <?php endif; ?>
    <a href="<?= url('admin/change-password') ?>" class="sidebar-link <?= ($currentAdminPage === 'change-password.php') ? 'active' : '' ?>">
      <i class="bi bi-key"></i>تغییر رمز عبور
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="d-flex justify-content-between align-items-center">
      <a href="<?= url() ?>" target="_blank"><i class="bi bi-box-arrow-up-right ms-1"></i>مشاهده سایت</a>
      <a href="<?= url('admin/logout') ?>" class="text-danger"><i class="bi bi-box-arrow-right ms-1"></i>خروج</a>
    </div>
  </div>
</div>

<!-- Main -->
<div class="admin-main">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary" aria-controls="adminSidebar" aria-expanded="false" aria-label="منوی مدیریت">
        <i class="bi bi-list"></i>
      </button>
      <span class="topbar-title"><?= isset($adminTitle) ? sanitize($adminTitle) : 'پنل مدیریت' ?></span>
    </div>
    <div class="topbar-user">
      <button class="jhd-icon-btn" data-theme-toggle aria-label="تغییر پوسته" aria-pressed="false" style="width:34px;height:34px;font-size:15px"><i class="bi bi-moon"></i></button>
      <div class="avatar"><?= mb_substr($admin['name'] ?: $admin['user'], 0, 1) ?></div>
      <div class="d-none d-md-block">
        <div class="fw-bold small"><?= sanitize($admin['name'] ?: $admin['user']) ?></div>
        <div class="text-muted" style="font-size:.73rem"><?= sanitize($admin['role']) ?></div>
      </div>
      <a href="<?= url('admin/logout') ?>" class="btn btn-sm btn-outline-danger" title="خروج"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
  <div class="admin-content">
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'success' ?> alert-auto-dismiss alert-dismissible fade show mb-3">
      <i class="bi bi-<?= ($_SESSION['flash_type'] ?? 'success') === 'success' ? 'check-circle' : 'exclamation-triangle' ?> ms-2"></i>
      <?= sanitize($_SESSION['flash_msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); endif; ?>
