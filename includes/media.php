<?php
/**
 * media.php — مدیریت فایل‌های رسانه‌ای (صوت + ویدیو) برای پست‌ها
 * این فایل باید بعد از functions.php بارگذاری شود.
 */

/**
 * ایجاد جدول media_files اگر وجود نداشته باشد
 * اگر قبلاً در functions.php تعریف شده باشد، دوباره تعریف نمی‌شود.
 */
if (!function_exists('ensureMediaTable')) {
    function ensureMediaTable(): void {
        static $done = false;
        if ($done) return;
        try {
            $db = getDB();
            $db->exec(
                "CREATE TABLE IF NOT EXISTS `media_files` (
                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `ref_type`   VARCHAR(20)  NOT NULL DEFAULT 'post',
                    `ref_id`     INT UNSIGNED NOT NULL,
                    `kind`       ENUM('audio','video') NOT NULL DEFAULT 'audio',
                    `file_path`  VARCHAR(500) NOT NULL,
                    `title`      VARCHAR(255) DEFAULT NULL,
                    `sort_order` INT          NOT NULL DEFAULT 0,
                    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_ref` (`ref_type`, `ref_id`, `kind`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $done = true;
        } catch (PDOException $e) {
            // بی‌صدا رد شو
        }
    }
}

/**
 * دریافت تمام فایل‌های رسانه‌ای برای یک آیتم
 */
function getMediaFor(string $refType, int $refId, string $kind): array {
    ensureMediaTable();
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM media_files
             WHERE ref_type = ? AND ref_id = ? AND kind = ?
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([$refType, $refId, $kind]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * آپلود چند فایل صوتی یا ویدیویی و ذخیره در دیتابیس
 */
function handleMediaUploads(string $refType, int $refId, array $files, string $kind): void {
    ensureMediaTable();
    $db        = getDB();
    $names     = $files['name']     ?? [];
    $errors    = $files['error']    ?? [];
    $tmpNames  = $files['tmp_name'] ?? [];
    $fileTypes = $files['type']     ?? [];
    $sizes     = $files['size']     ?? [];

    foreach ($names as $k => $name) {
        if (empty($name) || ($errors[$k] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $file = [
            'name'     => $name,
            'type'     => $fileTypes[$k] ?? '',
            'tmp_name' => $tmpNames[$k]  ?? '',
            'error'    => $errors[$k]    ?? 1,
            'size'     => $sizes[$k]     ?? 0,
        ];

        $path = '';
        if ($kind === 'audio') {
            $path = uploadAudio($file);
        } elseif ($kind === 'video') {
            $path = uploadFeaturedVideo($file);
        }

        if ($path) {
            $title = pathinfo($name, PATHINFO_FILENAME);
            $db->prepare(
                "INSERT INTO media_files (ref_type, ref_id, kind, file_path, title, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            )->execute([$refType, $refId, $kind, $path, $title]);
        }
    }
}

/**
 * حذف یک فایل رسانه‌ای از دیتابیس و دیسک
 */
function deleteMediaFile(int $mediaId, string $refType, int $refId): bool {
    ensureMediaTable();
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT file_path FROM media_files WHERE id = ? AND ref_type = ? AND ref_id = ?"
        );
        $stmt->execute([$mediaId, $refType, $refId]);
        $row = $stmt->fetch();

        if ($row && !empty($row['file_path'])) {
            // file_path نسبی مثل uploads/audio/file.mp3 است
            // UPLOAD_DIR مسیر کامل uploads است
            $siteRoot = rtrim(dirname(rtrim(UPLOAD_DIR, '/')), '/');
            $fullPath = $siteRoot . '/' . ltrim($row['file_path'], '/');
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        $db->prepare(
            "DELETE FROM media_files WHERE id = ? AND ref_type = ? AND ref_id = ?"
        )->execute([$mediaId, $refType, $refId]);

        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * دریافت تعداد فایل‌های رسانه‌ای یک آیتم
 */
function countMediaFor(string $refType, int $refId, string $kind = ''): int {
    ensureMediaTable();
    try {
        $db  = getDB();
        $sql = "SELECT COUNT(*) FROM media_files WHERE ref_type = ? AND ref_id = ?";
        $par = [$refType, $refId];
        if ($kind) {
            $sql  .= " AND kind = ?";
            $par[] = $kind;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($par);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
