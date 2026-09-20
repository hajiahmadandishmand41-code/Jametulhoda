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
        // Schema managed by bin/migrate.php.
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
            "SELECT m.*, f.size FROM media_files m
             LEFT JOIN stored_files f ON f.url=m.file_path
             WHERE m.ref_type = ? AND m.ref_id = ? AND m.kind = ?
             ORDER BY m.sort_order ASC, m.id ASC"
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
            scheduleFileDeletion($row['file_path']);
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
