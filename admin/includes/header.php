<?php
/**
 * admin/includes/header.php — هدر پنل مدیریت — نسخه ۲.۰
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if (preg_match('~/admin/(settings|messages|media|users)([/.]|$)~', $_SERVER['SCRIPT_NAME'] ?? '')) requireRole(['superadmin','admin']);
require_once __DIR__ . '/../../includes/admin-actions.php';
$admin = currentAdmin();
$currentAdminPage = basename($_SERVER['PHP_SELF']);
$currentAdminDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($adminTitle) ? sanitize($adminTitle) . ' — ' : '' ?>پنل مدیریت | <?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= siteUrl('assets/vendor/bootstrap.rtl.min.css') ?>">
<link rel="stylesheet" href="<?= siteUrl('assets/vendor/icons/bootstrap-icons.min.css') ?>">

<style>
:root {
  --admin-sidebar: #0d1f13;
  --admin-sidebar-hover: #163320;
  --admin-sidebar-active: #1e4a2e;
  --admin-gold: #c9a84c;
  --admin-green: #40916c;
  --admin-bg: #f2f4f6;
  --admin-white: #fff;
  --admin-border: #e8ecef;
  --admin-text: #2c3e50;
  --admin-muted: #6c757d;
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Vazirmatn', sans-serif; background: var(--admin-bg); color: var(--admin-text); margin: 0; }

/* Sidebar */
.admin-sidebar {
  width: 248px; background: var(--admin-sidebar); min-height: 100vh;
  position: fixed; top: 0; right: 0; bottom: 0; z-index: 100;
  display: flex; flex-direction: column;
  transition: transform .3s ease;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.15) transparent;
}
.sidebar-brand { padding: 18px 16px; border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(0,0,0,.15); }
.sidebar-brand img { width: 42px; height: 42px; border-radius: 50%; border: 2px solid var(--admin-gold); object-fit: contain; }
.sidebar-brand .name { color: var(--admin-gold); font-weight: 700; font-size: .85rem; margin-right: 10px; }
.sidebar-brand .sub { color: rgba(255,255,255,.4); font-size: .7rem; display: block; margin-right: 10px; }
.sidebar-nav { flex: 1; padding: 10px 0; }
.sidebar-section {
  padding: 8px 16px 2px;
  font-size: .67rem;
  color: rgba(255,255,255,.3);
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-top: 8px;
  font-weight: 600;
}
.sidebar-link {
  display: flex; align-items: center; gap: 9px;
  padding: 10px 16px;
  color: rgba(255,255,255,.72);
  font-size: .86rem;
  text-decoration: none;
  transition: all .2s;
  border-right: 3px solid transparent;
  position: relative;
}
.sidebar-link i { font-size: .95rem; width: 18px; text-align: center; }
.sidebar-link:hover { background: var(--admin-sidebar-hover); color: #fff; }
.sidebar-link.active { background: var(--admin-sidebar-active); color: var(--admin-gold); border-right-color: var(--admin-gold); font-weight: 600; }
.sidebar-footer { padding: 14px 16px; border-top: 1px solid rgba(255,255,255,.08); }
.sidebar-footer a { color: rgba(255,255,255,.55); font-size: .82rem; }
.sidebar-footer a:hover { color: #fff; }

/* Main content */
.admin-main { margin-right: 248px; min-height: 100vh; display: flex; flex-direction: column; }

/* Topbar */
.admin-topbar {
  background: var(--admin-white);
  border-bottom: 1px solid var(--admin-border);
  padding: 11px 24px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 50;
  box-shadow: 0 1px 6px rgba(0,0,0,.05);
}
.topbar-title { font-weight: 700; font-size: .98rem; color: var(--admin-text); }
.topbar-user { display: flex; align-items: center; gap: 10px; }
.topbar-user .avatar {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, var(--admin-green), #2d6a4f);
  color: #fff; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: .85rem;
}

/* Content */
.admin-content { flex: 1; padding: 24px; }

/* Cards */
.admin-card { background: var(--admin-white); border-radius: 14px; box-shadow: 0 1px 8px rgba(0,0,0,.06); border: 1px solid var(--admin-border); }
.admin-card-header { padding: 15px 20px; border-bottom: 1px solid var(--admin-border); font-weight: 700; font-size: .93rem; display: flex; align-items: center; justify-content: space-between; }
.admin-card-body { padding: 20px; }

/* Table */
.admin-table th { background: #f8f9fa; font-weight: 600; font-size: .82rem; color: var(--admin-muted); border-bottom: 2px solid var(--admin-border); }
.admin-table td { font-size: .86rem; vertical-align: middle; }
.admin-table tr:hover td { background: #f7fffe; }

/* Stats */
.stat-card { background: var(--admin-white); border-radius: 14px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,.06); border: 1px solid var(--admin-border); display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.stat-value { font-size: 1.8rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: .79rem; color: var(--admin-muted); margin-top: 2px; }

/* Form */
.admin-form label { font-weight: 600; font-size: .88rem; color: var(--admin-text); margin-bottom: 5px; display: block; }
.admin-form .form-control, .admin-form .form-select {
  border: 2px solid var(--admin-border); border-radius: 9px;
  padding: 9px 12px; font-family: inherit; font-size: .9rem;
  transition: border-color .2s;
  background: #fdfdfd;
}
.admin-form .form-control:focus, .admin-form .form-select:focus {
  border-color: var(--admin-green);
  box-shadow: 0 0 0 3px rgba(64,145,108,.1);
}
.admin-form textarea { resize: vertical; min-height: 120px; }

/* Badges */
.badge-published { background: #d4edd9; color: #155724; }
.badge-draft     { background: #fff3cd; color: #856404; }

/* Responsive */
#sidebarToggle { display: none; }
@media (max-width: 768px) {
  .admin-sidebar { transform: translateX(248px); }
  .admin-sidebar.open { transform: translateX(0); }
  .admin-main { margin-right: 0; }
  #sidebarToggle { display: flex; }
  .admin-content { padding: 14px; }
}
</style>
<script src="<?= siteUrl('assets/js/theme.js') ?>"></script>
<link rel="stylesheet" href="<?= siteUrl('assets/css/design-system.css') ?>">
<script src="<?= siteUrl('assets/js/interface.js') ?>" defer></script>
</head>
<body><button style="position:fixed;bottom:20px;left:20px;z-index:1000;background:var(--jhd-surface)" class="jhd-icon-btn" data-theme-toggle aria-label="تغییر پوسته" aria-pressed="false"><i class="bi bi-moon"></i></button>

<!-- Sidebar -->
<div class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand d-flex align-items-center">
    <img src="<?= imgUrl(getSetting('site_logo', 'assets/images/logo.jpg')) ?>" alt="لوگو" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕌</text></svg>'">
    <div>
      <span class="name">جامعه‌الهدی</span>
      <span class="sub">پنل مدیریت</span>
    </div>
  </div>

  <nav class="sidebar-nav">
<?php if ($admin['role'] === 'superadmin'): ?><a href="<?= siteUrl('admin/users.php') ?>" class="sidebar-link"><i class="bi bi-people"></i>مدیریت کاربران</a><?php endif; ?>
    <div class="sidebar-section">داشبورد</div>
    <a href="<?= siteUrl('admin/') ?>" class="sidebar-link <?= $currentAdminPage==='index.php' && $currentAdminDir==='admin'?'active':'' ?>">
      <i class="bi bi-speedometer2"></i>داشبورد
    </a>

    <div class="sidebar-section">اخبار</div>
    <a href="<?= siteUrl('admin/news/') ?>" class="sidebar-link <?= $currentAdminDir==='news'?'active':'' ?>">
      <i class="bi bi-newspaper"></i>مدیریت اخبار
    </a>
    <a href="<?= siteUrl('admin/news/create.php') ?>" class="sidebar-link">
      <i class="bi bi-plus-circle"></i>خبر جدید
    </a>

    <div class="sidebar-section">مقالات</div>
    <a href="<?= siteUrl('admin/articles/') ?>" class="sidebar-link <?= $currentAdminDir==='articles'?'active':'' ?>">
      <i class="bi bi-file-text"></i>مدیریت مقالات
    </a>
    <a href="<?= siteUrl('admin/articles/create.php') ?>" class="sidebar-link">
      <i class="bi bi-plus-circle"></i>مقاله جدید
    </a>

    <div class="sidebar-section">سخنرانی‌ها</div>
    <a href="<?= siteUrl('admin/speeches/') ?>" class="sidebar-link <?= $currentAdminDir==='speeches'?'active':'' ?>">
      <i class="bi bi-mic"></i>مدیریت سخنرانی‌ها
    </a>
    <a href="<?= siteUrl('admin/speeches/create.php') ?>" class="sidebar-link">
      <i class="bi bi-plus-circle"></i>سخنرانی جدید
    </a>

    <div class="sidebar-section">دسته‌بندی</div>
    <a href="<?= siteUrl('admin/categories/') ?>" class="sidebar-link <?= $currentAdminDir==='categories'?'active':'' ?>">
      <i class="bi bi-folder"></i>دسته‌بندی‌ها
    </a>

    <div class="sidebar-section">درس‌ها</div>
    <a href="<?= siteUrl('admin/lessons/') ?>" class="sidebar-link <?= $currentAdminDir==='lessons'?'active':'' ?>">
      <i class="bi bi-play-circle"></i>مدیریت درس‌ها
    </a>
    <a href="<?= siteUrl('admin/lessons/create.php') ?>" class="sidebar-link">
      <i class="bi bi-plus-square"></i>درس جدید
    </a>

    <div class="sidebar-section">کتاب‌ها</div>
    <a href="<?= siteUrl('admin/books/') ?>" class="sidebar-link <?= $currentAdminDir==='books'?'active':'' ?>">
      <i class="bi bi-book"></i>مدیریت کتاب‌ها
    </a>
    <a href="<?= siteUrl('admin/books/create.php') ?>" class="sidebar-link">
      <i class="bi bi-plus-square"></i>کتاب جدید
    </a>

    <div class="sidebar-section">رسانه</div>
    <a href="<?= siteUrl('admin/media/') ?>" class="sidebar-link <?= $currentAdminDir==='media'?'active':'' ?>">
      <i class="bi bi-images"></i>مدیریت رسانه
    </a>

    <div class="sidebar-section">ارتباطات</div>
    <?php
    try { $unread = (int)getDB()->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(); } catch (\Throwable) { $unread = 0; }
    ?>
    <a href="<?= siteUrl('admin/messages/') ?>" class="sidebar-link <?= ($currentAdminDir==='messages'||$currentAdminPage==='messages.php')?'active':'' ?>">
      <i class="bi bi-envelope"></i>پیام‌ها
      <?php if ($unread > 0): ?><span class="badge bg-danger ms-auto"><?= $unread ?></span><?php endif; ?>
    </a>

    <div class="sidebar-section">تنظیمات</div>
    <a href="<?= siteUrl('admin/settings.php') ?>" class="sidebar-link <?= $currentAdminPage==='settings.php'?'active':'' ?>">
      <i class="bi bi-gear"></i>تنظیمات سایت
    </a>
    <a href="<?= siteUrl('admin/change-password.php') ?>" class="sidebar-link <?= $currentAdminPage==='change-password.php'?'active':'' ?>">
      <i class="bi bi-key"></i>تغییر رمز عبور
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="d-flex justify-content-between align-items-center">
      <a href="<?= siteUrl() ?>" target="_blank"><i class="bi bi-box-arrow-up-right ms-1"></i>مشاهده سایت</a>
      <a href="<?= siteUrl('admin/logout.php') ?>" class="text-danger"><i class="bi bi-box-arrow-right ms-1"></i>خروج</a>
    </div>
  </div>
</div>

<!-- Main -->
<div class="admin-main">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
        <i class="bi bi-list"></i>
      </button>
      <span class="topbar-title"><?= isset($adminTitle) ? sanitize($adminTitle) : 'پنل مدیریت' ?></span>
    </div>
    <div class="topbar-user">
      <div class="avatar"><?= mb_substr($admin['name'] ?: $admin['user'], 0, 1) ?></div>
      <div class="d-none d-md-block">
        <div class="fw-bold small"><?= sanitize($admin['name'] ?: $admin['user']) ?></div>
        <div class="text-muted" style="font-size:.73rem">مدیر سیستم</div>
      </div>
      <a href="<?= siteUrl('admin/logout.php') ?>" class="btn btn-sm btn-outline-danger" title="خروج"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
  <div class="admin-content">
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'success' ?> alert-auto-dismiss alert-dismissible fade show mb-3">
      <i class="bi bi-<?= ($_SESSION['flash_type']??'success')==='success'?'check-circle':'exclamation-triangle' ?> ms-2"></i>
      <?= sanitize($_SESSION['flash_msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); endif; ?>
