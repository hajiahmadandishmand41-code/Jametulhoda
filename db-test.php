<?php
/**
 * db-test.php — تست اتصال دیتابیس
 * ─────────────────────────────────────────────────────────
 * ⚠️  پس از تأیید اتصال این فایل را از سرور حذف کنید!
 */
require_once __DIR__ . '/config/config.php';

$result = [];
$ok     = true;

// ─── ۱. تست اتصال PDO ─────────────────────────────────────────────────────────
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . (int)DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
    $result[] = ['ok', '✅ اتصال به MySQL موفق'];
} catch (PDOException $e) {
    $result[] = ['err', '❌ خطا در اتصال: ' . $e->getMessage()];
    $ok = false;
}

// ─── ۲. تست جداول ─────────────────────────────────────────────────────────────
if ($ok) {
    $required = ['users','categories','posts','lessons','contact_messages','settings'];
    $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($required as $t) {
        if (in_array($t, $existing)) {
            $result[] = ['ok', "✅ جدول <code>$t</code> موجود است"];
        } else {
            $result[] = ['warn', "⚠️ جدول <code>$t</code> وجود ندارد — <a href='install.php'>install.php</a> را اجرا کنید"];
        }
    }
}

// ─── ۳. تست ستون page_section ─────────────────────────────────────────────────
if ($ok && in_array('posts', $existing ?? [])) {
    $cols = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('page_section', $cols)) {
        $result[] = ['ok', '✅ ستون <code>page_section</code> در جدول posts موجود است'];
    } else {
        $result[] = ['warn', '⚠️ ستون <code>page_section</code> موجود نیست — <a href="install.php">install.php</a> را اجرا کنید'];
    }
}

// ─── ۴. تست کاربر ادمین ───────────────────────────────────────────────────────
if ($ok && in_array('users', $existing ?? [])) {
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE username='admin'")->fetchColumn();
    if ($cnt > 0) {
        $result[] = ['ok', '✅ کاربر <code>admin</code> در دیتابیس موجود است'];
    } else {
        $result[] = ['warn', '⚠️ کاربر admin یافت نشد — <a href="install.php">install.php</a> را اجرا کنید'];
    }
}

// ─── ۵. نسخه MySQL ────────────────────────────────────────────────────────────
if ($ok) {
    $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
    $result[] = ['info', "ℹ️ نسخه MySQL: <strong>$ver</strong>"];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تست دیتابیس — جامعه‌الهدی</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
<style>
body { background:#f0f4f0; font-family:Tahoma,sans-serif; padding:30px 15px }
.box { max-width:580px; margin:auto; background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1) }
.box-head { background:#0f2317; color:#c9a84c; padding:22px 28px }
.box-body { padding:24px 28px }
.row-ok   { background:#d4edda; color:#155724; padding:10px 14px; border-radius:8px; margin-bottom:8px }
.row-warn { background:#fff3cd; color:#856404; padding:10px 14px; border-radius:8px; margin-bottom:8px }
.row-err  { background:#f8d7da; color:#721c24; padding:10px 14px; border-radius:8px; margin-bottom:8px }
.row-info { background:#d1ecf1; color:#0c5460; padding:10px 14px; border-radius:8px; margin-bottom:8px }
</style>
</head>
<body>
<div class="box">
  <div class="box-head">
    <h5 class="mb-0">🔌 تست اتصال دیتابیس — مدرسه علمیه جامعه‌الهدی</h5>
    <div class="small opacity-75 mt-1">
      Host: <strong><?= htmlspecialchars(DB_HOST) ?></strong> &nbsp;|&nbsp;
      DB: <strong><?= htmlspecialchars(DB_NAME) ?></strong>
    </div>
  </div>
  <div class="box-body">
    <?php foreach ($result as [$type, $msg]): ?>
    <div class="row-<?= $type ?>"><?= $msg ?></div>
    <?php endforeach; ?>

    <?php if ($ok): ?>
    <div class="alert alert-success mt-4 mb-3">
      <strong>🎉 همه چیز درست است!</strong> پروژه آماده اجرا روی InfinityFree است.
    </div>
    <?php else: ?>
    <div class="alert alert-danger mt-4 mb-3">
      <strong>❌ خطا در اتصال.</strong> اطلاعات <code>config/config.php</code> را بررسی کنید.
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 flex-wrap">
      <a href="index.php" class="btn btn-outline-secondary btn-sm">مشاهده سایت</a>
      <a href="admin/login.php" class="btn btn-success btn-sm">ورود به پنل ادمین</a>
    </div>

    <p class="text-danger small mt-4 mb-0">
      ⚠️ پس از تأیید اتصال این فایل را از سرور حذف کنید.
    </p>
  </div>
</div>
</body>
</html>
