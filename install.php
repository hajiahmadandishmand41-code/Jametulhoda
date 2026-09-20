<?php
/**
 * install.php — نصب و راه‌اندازی دیتابیس مدرسه جامعه‌الهدی
 * ─────────────────────────────────────────────────────────
 * مرحله ۱: دیتابیس را در cPanel > MySQL Databases بسازید
 * مرحله ۲: نام کامل دیتابیس را در config/config.php (DB_NAME) وارد کنید
 * مرحله ۳: این فایل را از مرورگر اجرا کنید
 * مرحله ۴: پس از نصب موفق، این فایل را از سرور حذف کنید
 * ─────────────────────────────────────────────────────────
 * ⚠️ InfinityFree: ابتدا باید دیتابیس را در cPanel بسازید.
 *    این اسکریپت نمی‌تواند دیتابیس جدید بسازد — فقط جداول را ایجاد می‌کند.
 */

require_once __DIR__ . '/config/config.php';

$log    = [];
$errors = [];

function logMsg(string $msg, string $type = 'ok'): void {
    global $log;
    $log[] = ['msg' => $msg, 'type' => $type];
}

// ─── اتصال به دیتابیس ─────────────────────────────────────────────────────────
// InfinityFree: دیتابیس باید از قبل در cPanel ساخته شده باشد
try {
    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
    $dsn  = 'mysql:host=' . DB_HOST
          . ';port=' . $port
          . ';dbname=' . DB_NAME
          . ';charset=utf8mb4';
    $pdo  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    logMsg('✅ اتصال به دیتابیس موفق — ' . DB_NAME . ' @ ' . DB_HOST);
} catch (PDOException $e) {
    $errMsg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    die('<!DOCTYPE html><html lang="fa" dir="rtl">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>خطای نصب</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
</head><body style="background:#f8d7da;padding:30px">
<div class="container" style="max-width:600px">
<div class="alert alert-danger">
<h5>❌ خطا در اتصال به دیتابیس</h5>
<p><strong>پیام خطا:</strong> <code>' . $errMsg . '</code></p>
<hr>
<h6>راه‌حل:</h6>
<ol>
<li>وارد cPanel اکانت InfinityFree خود شوید</li>
<li>به بخش <strong>MySQL Databases</strong> بروید</li>
<li>یک دیتابیس جدید بسازید</li>
<li>نام کامل دیتابیس (مثال: <code>if0_42520579_jamiatalhoda</code>) را در <code>config/config.php</code> در مقدار <strong>DB_NAME</strong> وارد کنید</li>
<li>دوباره این صفحه را بارگذاری کنید</li>
</ol>
<p>اطلاعات فعلی:<br>
Host: <code>' . DB_HOST . '</code><br>
DB: <code>' . DB_NAME . '</code><br>
User: <code>' . DB_USER . '</code></p>
</div>
</div></body></html>');
}

// ─── جدول users ───────────────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
      `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `username`   VARCHAR(80)  NOT NULL,
      `email`      VARCHAR(180) NOT NULL DEFAULT '',
      `password`   VARCHAR(255) NOT NULL,
      `full_name`  VARCHAR(120) NOT NULL DEFAULT '',
      `role`       ENUM('superadmin','admin','editor') NOT NULL DEFAULT 'admin',
      `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
      `last_login` DATETIME         NULL,
      `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    logMsg('✅ جدول users ایجاد/موجود است');
} catch (PDOException $e) {
    logMsg('❌ جدول users: ' . $e->getMessage(), 'error');
}

// ─── جدول categories ──────────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
      `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `name`        VARCHAR(120) NOT NULL,
      `slug`        VARCHAR(160) NOT NULL,
      `description` TEXT             NULL,
      `post_type`   VARCHAR(30)  NOT NULL DEFAULT 'all',
      `sort_order`  INT          NOT NULL DEFAULT 0,
      `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    logMsg('✅ جدول categories ایجاد/موجود است');
} catch (PDOException $e) {
    logMsg('❌ جدول categories: ' . $e->getMessage(), 'error');
}

// ─── جدول posts ───────────────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `posts` (
      `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
      `title`           VARCHAR(400)     NOT NULL,
      `slug`            VARCHAR(450)     NOT NULL,
      `summary`         TEXT                 NULL,
      `content`         LONGTEXT             NULL,
      `featured_image`  VARCHAR(350)         NULL,
      `post_type`       ENUM('news','article','announcement','speech','program','religious') NOT NULL DEFAULT 'news',
      `page_section`    VARCHAR(300)     NOT NULL DEFAULT 'home,news',
      `category_id`     INT UNSIGNED         NULL,
      `author_id`       INT UNSIGNED         NULL,
      `status`          ENUM('published','draft') NOT NULL DEFAULT 'draft',
      `is_featured`     TINYINT(1)       NOT NULL DEFAULT 0,
      `views`           INT UNSIGNED     NOT NULL DEFAULT 0,
      `published_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_slug` (`slug`(191)),
      KEY `idx_status_type` (`status`, `post_type`),
      KEY `idx_category`    (`category_id`),
      KEY `idx_published`   (`published_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    logMsg('✅ جدول posts ایجاد/موجود است');
} catch (PDOException $e) {
    logMsg('❌ جدول posts: ' . $e->getMessage(), 'error');
}

// ─── جدول post_images ─────────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `post_images` (
      `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `post_id`    INT UNSIGNED NOT NULL,
      `image_path` VARCHAR(350) NOT NULL,
      `alt_text`   VARCHAR(200)     NULL,
      `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_post` (`post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    logMsg('✅ جدول post_images ایجاد/موجود است');
} catch (PDOException $e) {
    logMsg('❌ جدول post_images: ' . $e->getMessage(), 'error');
}

// ─── جدول lessons ─────────────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `lessons` (
      `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `title`          VARCHAR(400) NOT NULL,
      `slug`           VARCHAR(450) NOT NULL,
      `description`    TEXT             NULL,
      `content`        LONGTEXT         NULL,
      `audio_file`     VARCHAR(350)     NULL,
      `featured_image` VARCHAR(350)     NULL,
      `teacher`        VARCHAR(150)     NULL,
      `subject`        VARCHAR(150)     NULL,
      `level`          ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
      `status`         ENUM('published','draft') NOT NULL DEFAULT 'draft',
      `page_section`   VARCHAR(300) NOT NULL DEFAULT 'home,lessons',
      `sort_order`     INT          NOT NULL DEFAULT 0,
      `views`          INT UNSIGNED NOT NULL DEFAULT 0,
      `created_by`     INT UNSIGNED     NULL,
      `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_slug` (`slug`(191)),
      KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    logMsg('✅ جدول lessons ایجاد/موجود است');
} catch (PDOException $e) {
    logMsg('❌ جدول lessons: ' . $e->getMessage(), 'error');
}

// ─── Migration: اضافه کردن ستون page_section به جدول lessons اگر وجود ندارد ───
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM `lessons` LIKE 'page_section'");
    if ($checkCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `lessons` ADD COLUMN `page_section` VARCHAR(300) NOT NULL DEFAULT 'home,lessons' AFTER `status`");
        logMsg('✅ ستون page_section به جدول lessons اضافه شد');
    }
} catch (PDOException $e) {
    logMsg('⚠️ Migration lessons.page_section: ' . $e->getMessage(), 'warn');
}

// ─── جدول contact_messages ────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_messages` (
      `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `name`       VARCHAR(200) NOT NULL,
      `email`      VARCHAR(200)     NULL,
      `phone`      VARCHAR(50)      NULL,
      `subject`    VARCHAR(300)     NULL,
      `message`    TEXT         NOT NULL,
      `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
      `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    logMsg('✅ جدول contact_messages ایجاد/موجود است');
} catch (PDOException $e) {
    logMsg('❌ جدول contact_messages: ' . $e->getMessage(), 'error');
}

// ─── جدول settings ────────────────────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
      `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `key`        VARCHAR(100) NOT NULL,
      `value`      TEXT             NULL,
      `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_key` (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    logMsg('✅ جدول settings ایجاد/موجود است');
} catch (PDOException $e) {
    logMsg('❌ جدول settings: ' . $e->getMessage(), 'error');
}

// ─── ایجاد/بروزرسانی مدیر ────────────────────────────────────────────────────
try {
    $adminPassword = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $checkAdmin    = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $checkAdmin->execute();
    if ($checkAdmin->fetch()) {
        $pdo->prepare("UPDATE users SET password=?, is_active=1 WHERE username='admin'")
            ->execute([$adminPassword]);
        logMsg('✅ رمز عبور مدیر admin بروزرسانی شد (Admin@1234)');
    } else {
        $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) VALUES ('admin','admin@jamiatalhoda.af',?,'مدیر سیستم','superadmin',1)")
            ->execute([$adminPassword]);
        logMsg('✅ کاربر admin ایجاد شد — نام کاربری: admin | رمز: Admin@1234');
    }
} catch (PDOException $e) {
    logMsg('❌ کاربر admin: ' . $e->getMessage(), 'error');
}

// ─── تنظیمات پیش‌فرض ──────────────────────────────────────────────────────────
try {
    $defaults = [
        'site_name'       => 'مدرسه علمیه جامعه‌الهدی',
        'site_slogan'     => 'علم، معرفت و تهذیب در پرتو قرآن و عترت',
        'site_email'      => 'hajiahmads299@gmail.com',
        'phone'           => '0798228441',
        'address'         => 'کابل، افغانستان',
        'social_telegram' => '',
        'social_youtube'  => '',
        'social_instagram'=> '',
    ];
    foreach ($defaults as $k => $v) {
        $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")
            ->execute([$k, $v]);
    }
    logMsg('✅ تنظیمات پیش‌فرض ذخیره شد');
} catch (PDOException $e) {
    logMsg('❌ تنظیمات: ' . $e->getMessage(), 'error');
}

// ─── دسته‌بندی‌های پیش‌فرض ────────────────────────────────────────────────────
try {
    $catCount = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount === 0) {
        $cats = [
            ['فقه و اصول',         'fiqh-osul',         'all',      1],
            ['تفسیر و قرآن',       'tafsir-quran',       'all',      2],
            ['فلسفه و کلام',       'falsafe-kalam',      'all',      3],
            ['اخلاق و معرفت',      'akhlaq-marefat',     'all',      4],
            ['اخبار مدرسه',        'akhbar-madrasa',     'news',     5],
            ['مقالات علمی',        'maqalat-elmi',       'article',  6],
            ['برنامه‌های تابستانه','baraname-tabestane', 'program',  7],
            ['فعالیت‌های مذهبی',   'faaliyet-mazhabie',  'religious',8],
        ];
        $ins = $pdo->prepare("INSERT INTO categories (name,slug,post_type,sort_order) VALUES (?,?,?,?)");
        foreach ($cats as $c) $ins->execute($c);
        logMsg('✅ دسته‌بندی‌های پیش‌فرض ایجاد شد (' . count($cats) . ' دسته)');
    } else {
        logMsg('ℹ️ دسته‌بندی‌ها قبلاً موجود است (' . $catCount . ' دسته)', 'info');
    }
} catch (PDOException $e) {
    logMsg('❌ دسته‌بندی‌ها: ' . $e->getMessage(), 'error');
}

// ─── مجوز پوشه uploads ────────────────────────────────────────────────────────
$uploadDirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/posts',
    __DIR__ . '/uploads/lessons',
    __DIR__ . '/uploads/audio',
    __DIR__ . '/uploads/site',
    __DIR__ . '/uploads/media',
    __DIR__ . '/uploads/thumbs',
];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0755, true)) logMsg('✅ پوشه ایجاد شد: uploads/' . basename($dir));
        else logMsg('⚠️ نتوانست پوشه بسازد: uploads/' . basename($dir), 'warn');
    } else {
        logMsg('ℹ️ پوشه موجود: uploads/' . basename($dir), 'info');
    }
}

// فایل .htaccess برای uploads
$htaccess = __DIR__ . '/uploads/.htaccess';
if (!file_exists($htaccess)) {
    $htaccessContent = "Options -Indexes\n"
                     . "<FilesMatch \"\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|htm|html|shtml|sh|cgi)\$\">\n"
                     . "Order Deny,Allow\n"
                     . "Deny from all\n"
                     . "</FilesMatch>\n";
    file_put_contents($htaccess, $htaccessContent);
    logMsg('✅ .htaccess امنیتی برای uploads ایجاد شد');
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب — مدرسه علمیه جامعه‌الهدی</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
<style>
body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f0; padding: 30px 15px; }
.install-box { max-width: 680px; margin: auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,.12); }
.install-header { background: #0f2317; color: #fff; padding: 28px 30px; border-bottom: 3px solid #c9a84c; }
.install-header h2 { color: #c9a84c; margin: 0; font-size: 1.3rem; }
.install-body { padding: 28px 30px; }
.log-item { padding: 8px 14px; border-radius: 8px; margin-bottom: 6px; font-size: .88rem; }
.log-ok   { background: #d4edda; color: #155724; }
.log-info { background: #d1ecf1; color: #0c5460; }
.log-warn { background: #fff3cd; color: #856404; }
.log-error{ background: #f8d7da; color: #721c24; font-weight: bold; }
.cred-box { background: #0f2317; color: #fff; border-radius: 12px; padding: 20px 24px; margin-top: 20px; }
.cred-box code { color: #c9a84c; font-size: 1.05rem; }
</style>
</head>
<body>
<div class="install-box">
    <div class="install-header">
        <h2>🕌 نصب مدرسه علمیه جامعه‌الهدی</h2>
        <p class="mb-0 mt-1 opacity-75 small">
            دیتابیس: <strong><?= htmlspecialchars(DB_NAME) ?></strong> |
            هاست: <strong><?= htmlspecialchars(DB_HOST) ?></strong>
        </p>
    </div>
    <div class="install-body">
        <h6 class="mb-3">📋 گزارش نصب:</h6>
        <?php foreach ($log as $entry): ?>
        <div class="log-item log-<?= $entry['type'] ?>">
            <?= htmlspecialchars($entry['msg']) ?>
        </div>
        <?php endforeach; ?>

        <?php
        $hasError = array_filter($log, fn($e) => $e['type'] === 'error');
        if (!$hasError): ?>
        <div class="cred-box mt-4">
            <h6 class="text-warning mb-3">🔑 اطلاعات ورود به پنل مدیریت:</h6>
            <p class="mb-1">نام کاربری: <code>admin</code></p>
            <p class="mb-0">رمز عبور: <code>Admin@1234</code></p>
        </div>
        <?php endif; ?>

        <div class="alert alert-warning mt-4">
            <strong>⚠️ مهم:</strong> پس از ورود موفق به پنل، فایل‌های
            <code>install.php</code> و <code>db-test.php</code> را از سرور حذف کنید.
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <a href="admin/login.php" class="btn btn-success">ورود به پنل مدیریت</a>
            <a href="index.php" class="btn btn-outline-secondary">مشاهده سایت</a>
            <a href="db-test.php" class="btn btn-outline-info btn-sm">تست اتصال</a>
        </div>
    </div>
</div>
</body>
</html>
