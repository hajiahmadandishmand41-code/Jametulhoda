<?php
/**
 * migrate-sections.php — تنظیم page_section پیش‌فرض برای رکوردهای موجود
 * ─────────────────────────────────────────────────────────────────────
 * - پست‌هایی که page_section خالی یا 'home,news' دارن → بر اساس post_type به مقدار مناسب ست می‌شن
 * - این اسکریپت فقط برای دیتابیس‌های قدیمی لازمه. بعد از اجرا، این فایل را حذف کنید.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

// نگاشت post_type → page_section پیش‌فرض
$defaultSectionByType = [
    'news'         => 'home,news',
    'article'      => 'home,articles',
    'announcement' => 'home,announcements',
    'speech'       => 'speeches',
    'program'      => 'home,programs',
    'religious'    => 'religious',
];

$updated = 0;
$unchanged = 0;

try {
    $rows = $db->query("SELECT id, post_type, page_section FROM posts")->fetchAll();
    foreach ($rows as $row) {
        $current = trim($row['page_section'] ?? '');
        $type    = $row['post_type'];
        $default = $defaultSectionByType[$type] ?? 'other';

        // فقط اگه خالی باشه یا فقط شامل home,news (default قبلی) باشه
        if ($current === '' || $current === 'home,news' || $current === 'other') {
            $stmt = $db->prepare("UPDATE posts SET page_section = ? WHERE id = ?");
            $stmt->execute([$default, $row['id']]);
            $updated++;
            echo "✓ Post #{$row['id']} ({$type}): page_section → '{$default}'<br>";
        } else {
            $unchanged++;
        }
    }
} catch (PDOException $e) {
    die('❌ خطا: ' . htmlspecialchars($e->getMessage()));
}

echo "<hr><strong>خلاصه:</strong> {$updated} رکورد بروزرسانی شد، {$unchanged} رکورد بدون تغییر.<br>";

// ─── درس‌ها: ست کردن page_section پیش‌فرض ───────────────────────────────────
echo "<h4>درس‌ها:</h4>";
$lessonUpdated = 0;
$lessonUnchanged = 0;
try {
    $checkCol = $db->query("SHOW COLUMNS FROM `lessons` LIKE 'page_section'");
    if ($checkCol->rowCount() === 0) {
        $db->exec("ALTER TABLE `lessons` ADD COLUMN `page_section` VARCHAR(300) NOT NULL DEFAULT 'home,lessons' AFTER `status`");
        echo "✓ ستون page_section به جدول lessons اضافه شد.<br>";
    }
    $rows = $db->query("SELECT id, page_section FROM lessons")->fetchAll();
    foreach ($rows as $row) {
        $current = trim($row['page_section'] ?? '');
        if ($current === '' || $current === 'lessons') {
            $db->prepare("UPDATE lessons SET page_section = 'home,lessons' WHERE id = ?")->execute([$row['id']]);
            $lessonUpdated++;
        } else {
            $lessonUnchanged++;
        }
    }
} catch (PDOException $e) {
    echo "❌ خطا در درس‌ها: " . htmlspecialchars($e->getMessage()) . "<br>";
}
echo "✓ {$lessonUpdated} درس بروزرسانی شد، {$lessonUnchanged} درس بدون تغییر.<br>";
echo "<hr><p style='color:red'>⚠️ بعد از اجرا، این فایل را از روی هاست حذف کنید!</p>";
