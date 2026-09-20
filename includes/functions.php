<?php
/**
 * functions.php - توابع کمکی عمومی — نسخه کامل اصلاح‌شده + سیستم لایک + ویدیو
 */

require_once __DIR__ . '/../config/database.php';

// ─── Security ────────────────────────────────────────────────────────────────

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCsrfToken() . '">';
}

// ─── URL ─────────────────────────────────────────────────────────────────────

function siteUrl(string $path = ''): string {
    if (SITE_URL) {
        $base = rtrim(SITE_URL, '/');
    } else {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = $scheme . '://' . $host;
    }
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function currentUrl(): string {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// ─── Slug ─────────────────────────────────────────────────────────────────────

function makeSlug(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: uniqid('post-');
}

function uniqueSlug(string $table, string $text, int $excludeId = 0): string {
    $db   = getDB();
    $base = makeSlug($text);
    $slug = $base;
    $i    = 1;
    while (true) {
        $sql  = "SELECT COUNT(*) FROM `$table` WHERE slug = ? AND id != ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$slug, $excludeId]);
        if ((int)$stmt->fetchColumn() === 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// ─── Date ─────────────────────────────────────────────────────────────────────

function persianDate(string $datetime): string {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return '—';
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    $monthNames = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    $parts = explode('-', date('Y-m-d', $ts));
    $gy = (int)$parts[0]; $gm = (int)$parts[1]; $gd = (int)$parts[2];
    $leap = ($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0);
    $monthDays = [31,28+($leap?1:0),31,30,31,30,31,31,30,31,30,31];
    $g_d_no = 365*$gy + (int)(($gy+3)/4) - (int)(($gy+99)/100) + (int)(($gy+399)/400);
    for ($i = 0; $i < $gm-1; $i++) $g_d_no += $monthDays[$i];
    $g_d_no += $gd;
    $j_d_no = $g_d_no - 79;
    $j_np   = (int)($j_d_no / 12053); $j_d_no %= 12053;
    $jy     = 979 + 33*$j_np + 4*(int)($j_d_no / 1461); $j_d_no %= 1461;
    if ($j_d_no >= 366) { $jy += (int)(($j_d_no-1)/365); $j_d_no = ($j_d_no-1) % 365; }
    $jMonthDays = [31,31,31,31,31,31,30,30,30,30,30];
    for ($i = 0; $i < 11 && $j_d_no >= $jMonthDays[$i]; $i++) $j_d_no -= $jMonthDays[$i];
    $jm = $i + 1; $jd = $j_d_no + 1;
    return $jd . ' ' . $monthNames[$jm-1] . ' ' . $jy;
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'چند لحظه پیش';
    if ($diff < 3600)    return (int)($diff/60) . ' دقیقه پیش';
    if ($diff < 86400)   return (int)($diff/3600) . ' ساعت پیش';
    if ($diff < 2592000) return (int)($diff/86400) . ' روز پیش';
    return persianDate($datetime);
}

// ─── Upload ───────────────────────────────────────────────────────────────────

function imgUrl(string $path): string {
    if (!$path) return siteUrl('assets/images/placeholder.svg');
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
    return siteUrl(ltrim($path, '/'));
}

function uploadImage(array $file, string $subdir = 'posts'): string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return '';
    if ($file['size'] > MAX_FILE_SIZE) return '';

    // بررسی نوع فایل
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMG, true)) return '';

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed, true)) $ext = 'jpg';

    $dir = rtrim(UPLOAD_DIR, '/') . '/' . trim($subdir, '/');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return '';

    $filename = uniqid('img_', true) . '.' . $ext;
    $dest     = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return '';

    return 'uploads/' . ($subdir ? trim($subdir, '/') . '/' : '') . $filename;
}

function uploadAudio(array $file): string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return '';
    if ($file['size'] > MAX_FILE_SIZE) return '';

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // بعضی سرورها mime audio/mpeg یا application/octet-stream می‌دهند
    $allowedMime = array_merge(ALLOWED_AUDIO, [
        'application/octet-stream',
        'application/x-octet-stream',
        'audio/x-mp3',
        'audio/x-mpeg-3',
        'audio/mpeg3',
    ]);
    $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExt  = ['mp3','ogg','wav','m4a','mp4'];

    // پذیرش بر اساس پسوند یا MIME
    if (!in_array($mimeType, $allowedMime, true) && !in_array($ext, $allowedExt, true)) return '';
    if (!in_array($ext, $allowedExt, true)) $ext = 'mp3';

    $dir = rtrim(UPLOAD_DIR, '/') . '/audio';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return '';

    $filename = uniqid('audio_', true) . '.' . $ext;
    $dest     = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return '';

    return 'uploads/audio/' . $filename;
}

/**
 * آپلود ویدیو شاخص
 */
function uploadFeaturedVideo(array $file): string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return '';

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExt = ['mp4','webm','mov','mkv','ogv','m4v'];

    if (!in_array($ext, $allowedExt, true)) return '';

    $maxVideoSize = 200 * 1024 * 1024;
    if ($file['size'] > $maxVideoSize) return '';

    $dir = rtrim(UPLOAD_DIR, '/') . '/videos';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return '';

    $filename = uniqid('featvid_', true) . '.' . $ext;
    $dest     = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return '';

    return 'uploads/videos/' . $filename;
}

/**
 * رندر پلیر ویدیو شاخص
 */
function renderFeaturedVideo(string $videoPath, string $posterPath = '', string $size = 'card'): string {
    if (!$videoPath) return '';
    $url    = siteUrl($videoPath);
    $poster = $posterPath ? imgUrl($posterPath) : '';

    if ($size === 'card') {
        return sprintf(
            '<button type="button" class="featured-video-play" data-video="%s" data-poster="%s" title="پخش ویدیو">
                <i class="bi bi-play-fill"></i>
            </button>',
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars($poster, ENT_QUOTES)
        );
    }

    // full — پلیر ویدیو کامل
    return sprintf(
        '<div class="featured-video-wrap my-3">
            <video id="featuredPostVideo" controls playsinline preload="none" poster="%s" class="w-100" style="border-radius:12px;background:#000;max-height:500px">
                <source src="%s" type="video/mp4">
                مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
            </video>
        </div>',
        htmlspecialchars($poster, ENT_QUOTES),
        htmlspecialchars($url, ENT_QUOTES)
    );
}

/**
 * ذخیره Thumbnail خودکار از base64
 */
function saveBase64Thumbnail(string $base64Data, string $subdir = 'posts'): string {
    if (empty($base64Data)) return '';
    $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
    $imageData  = base64_decode($base64Data, true);
    if (!$imageData || strlen($imageData) < 100) return '';

    $dir = rtrim(UPLOAD_DIR, '/') . '/' . trim($subdir, '/');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return '';

    $filename = uniqid('thumb_', true) . '.jpg';
    $dest     = $dir . '/' . $filename;

    if (file_put_contents($dest, $imageData) === false) return '';

    return 'uploads/' . trim($subdir, '/') . '/' . $filename;
}

// ─── Posts ────────────────────────────────────────────────────────────────────

/**
 * اطمینان از وجود ستون featured_video در جدول posts
 */
function ensureFeaturedVideoColumn(): void {
    static $done = false;
    if ($done) return;
    try {
        $db = getDB();
        $check = $db->query("SHOW COLUMNS FROM posts LIKE 'featured_video'");
        if ($check->rowCount() === 0) {
            $db->exec("ALTER TABLE posts ADD COLUMN `featured_video` VARCHAR(500) DEFAULT NULL AFTER `featured_image`");
        }
        $done = true;
    } catch (PDOException $e) {
        // بی‌صدا رد شو
    }
}

/**
 * Migration ایمن برای جدول lessons:
 * اضافه کردن ستون‌های summary و page_section اگر وجود ندارند
 */
function ensureLessonsColumns(): void {
    static $done = false;
    if ($done) return;
    try {
        $db = getDB();

        // ستون summary
        $chk = $db->query("SHOW COLUMNS FROM lessons LIKE 'summary'");
        if ($chk->rowCount() === 0) {
            $db->exec("ALTER TABLE lessons ADD COLUMN `summary` TEXT DEFAULT NULL AFTER `content`");
        }

        // ستون page_section
        $chk2 = $db->query("SHOW COLUMNS FROM lessons LIKE 'page_section'");
        if ($chk2->rowCount() === 0) {
            $db->exec("ALTER TABLE lessons ADD COLUMN `page_section` VARCHAR(255) NOT NULL DEFAULT 'home' AFTER `status`");
        }

        // ستون views اگر وجود نداشت
        $chk3 = $db->query("SHOW COLUMNS FROM lessons LIKE 'views'");
        if ($chk3->rowCount() === 0) {
            $db->exec("ALTER TABLE lessons ADD COLUMN `views` INT UNSIGNED NOT NULL DEFAULT 0");
        }

        // ستون level
        $chk4 = $db->query("SHOW COLUMNS FROM lessons LIKE 'level'");
        if ($chk4->rowCount() === 0) {
            $db->exec("ALTER TABLE lessons ADD COLUMN `level` ENUM('beginner','intermediate','advanced') DEFAULT NULL AFTER `page_section`");
        }

        // ستون sort_order
        $chk5 = $db->query("SHOW COLUMNS FROM lessons LIKE 'sort_order'");
        if ($chk5->rowCount() === 0) {
            $db->exec("ALTER TABLE lessons ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `level`");
        }

        // ستون video_file
        $chk6 = $db->query("SHOW COLUMNS FROM lessons LIKE 'video_file'");
        if ($chk6->rowCount() === 0) {
            $db->exec("ALTER TABLE lessons ADD COLUMN `video_file` VARCHAR(500) DEFAULT NULL");
        }

        // ستون pdf_file
        $chk7 = $db->query("SHOW COLUMNS FROM lessons LIKE 'pdf_file'");
        if ($chk7->rowCount() === 0) {
            $db->exec("ALTER TABLE lessons ADD COLUMN `pdf_file` VARCHAR(500) DEFAULT NULL");
        }

        $done = true;
    } catch (PDOException $e) {
        // بی‌صدا رد شو
        error_log('ensureLessonsColumns error: ' . $e->getMessage());
    }
}

/**
 * Migration ایمن برای ستون speaker در جدول posts
 */
function ensureSpeakerColumn(): void {
    static $done = false;
    if ($done) return;
    try {
        $db  = getDB();
        $chk = $db->query("SHOW COLUMNS FROM posts LIKE 'speaker'");
        if ($chk->rowCount() === 0) {
            $db->exec("ALTER TABLE posts ADD COLUMN `speaker` VARCHAR(200) DEFAULT NULL AFTER `summary`");
        }
        $done = true;
    } catch (PDOException $e) {
        error_log('ensureSpeakerColumn error: ' . $e->getMessage());
    }
}

/**
 * شمارش تعداد فایل‌های رسانه‌ای
 */
function countMediaFor(string $refType, int $refId, string $kind = ''): int {
    try {
        $db = getDB();
        if ($kind) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM media_files WHERE ref_type=? AND ref_id=? AND kind=?");
            $stmt->execute([$refType, $refId, $kind]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM media_files WHERE ref_type=? AND ref_id=?");
            $stmt->execute([$refType, $refId]);
        }
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * دریافت لیست پست‌ها با فیلترهای مختلف
 */
function getPosts(array $opts = []): array {
    $db     = getDB();
    ensureFeaturedVideoColumn();
    $where  = ["p.status = 'published'"];
    $params = [];

    if (!empty($opts['type'])) {
        $where[]  = "p.post_type = ?";
        $params[] = $opts['type'];
    }
    if (!empty($opts['search'])) {
        $where[]  = "(p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ?)";
        $s        = '%' . $opts['search'] . '%';
        $params   = array_merge($params, [$s, $s, $s]);
    }
    if (!empty($opts['featured'])) {
        $where[]  = "p.is_featured = 1";
    }
    if (!empty($opts['cat'])) {
        $where[]  = "p.category_id = ?";
        $params[] = (int)$opts['cat'];
    }
    if (!empty($opts['section'])) {
        $where[]  = "FIND_IN_SET(?, REPLACE(REPLACE(p.page_section, ' ', ''), ',,', ','))";
        $params[] = $opts['section'];
    }

    $limit  = isset($opts['limit'])  ? (int)$opts['limit']  : (int)POSTS_PER_PAGE;
    $offset = isset($opts['offset']) ? (int)$opts['offset'] : 0;
    if ($limit  < 1)   $limit  = 1;
    if ($limit  > 100) $limit  = 100;
    if ($offset < 0)   $offset = 0;

    $whereStr = implode(' AND ', $where);
    $sql = "SELECT p.*, c.name AS cat_name, u.full_name AS author_name
            FROM posts p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN users u ON u.id = p.author_id
            WHERE $whereStr
            ORDER BY p.id DESC, p.published_at DESC
            LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countPosts(array $opts = []): int {
    $db     = getDB();
    $where  = ["p.status = 'published'"];
    $params = [];

    if (!empty($opts['type'])) {
        $where[]  = "p.post_type = ?";
        $params[] = $opts['type'];
    }
    if (!empty($opts['search'])) {
        $where[]  = "(p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ?)";
        $s        = '%' . $opts['search'] . '%';
        $params   = array_merge($params, [$s, $s, $s]);
    }
    if (!empty($opts['cat'])) {
        $where[]  = "p.category_id = ?";
        $params[] = (int)$opts['cat'];
    }
    if (!empty($opts['section'])) {
        $where[]  = "FIND_IN_SET(?, REPLACE(REPLACE(p.page_section, ' ', ''), ',,', ','))";
        $params[] = $opts['section'];
    }

    $whereStr = implode(' AND ', $where);
    $stmt = $db->prepare("SELECT COUNT(*) FROM posts p WHERE $whereStr");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function getPost(int $id): ?array {
    $db   = getDB();
    ensureFeaturedVideoColumn();
    $stmt = $db->prepare(
        "SELECT p.*, c.name AS cat_name, u.full_name AS author_name
         FROM posts p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN users u ON u.id = p.author_id
         WHERE p.id = ?
         LIMIT 1"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getPostBySlug(string $slug): ?array {
    $db   = getDB();
    ensureFeaturedVideoColumn();
    $stmt = $db->prepare(
        "SELECT p.*, c.name AS cat_name, u.full_name AS author_name
         FROM posts p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN users u ON u.id = p.author_id
         WHERE p.slug = ? AND p.status = 'published'
         LIMIT 1"
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ─── Lessons ─────────────────────────────────────────────────────────────────

function getLesson(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM lessons WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ─── Categories ───────────────────────────────────────────────────────────────

function getCategories(): array {
    $db = getDB();
    try {
        $stmt = $db->query(
            "SELECT c.*, COUNT(p.id) AS post_count
             FROM categories c
             LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published'
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getCategoryBySlug(string $slug): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ─── Settings ─────────────────────────────────────────────────────────────────

function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $db = getDB();
        try {
            $rows  = $db->query("SELECT `key`, `value` FROM settings")->fetchAll();
            $cache = array_column($rows, 'value', 'key');
        } catch (PDOException $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function paginate(int $total, int $limit, int $current, string $urlPattern): string {
    if ($limit <= 0) $limit = 1;
    $pages = (int)ceil($total / $limit);
    if ($pages <= 1) return '';

    $range = 2;
    $start = max(1, $current - $range);
    $end   = min($pages, $current + $range);

    $html  = '<nav aria-label="صفحه‌بندی"><ul class="pagination justify-content-center flex-wrap">';

    if ($current > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $current - 1) . '">&#8250; قبلی</a></li>';
    }

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, 1) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $current) ? ' active' : '';
        $html  .= '<li class="page-item' . $active . '"><a class="page-link" href="' . sprintf($urlPattern, $i) . '">' . $i . '</a></li>';
    }

    if ($end < $pages) {
        if ($end < $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $pages) . '">' . $pages . '</a></li>';
    }

    if ($current < $pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $current + 1) . '">بعدی &#8249;</a></li>';
    }

    return $html . '</ul></nav>';
}

// ─── Post Type Labels ─────────────────────────────────────────────────────────

function postTypeLabel(string $type): string {
    return match($type) {
        'news'         => 'خبر',
        'article'      => 'مقاله',
        'announcement' => 'اطلاعیه',
        'speech'       => 'سخنرانی',
        'program'      => 'برنامه آموزشی',
        'religious'    => 'فعالیت مذهبی',
        default        => 'مطلب',
    };
}

function postTypeBadge(string $type): string {
    $colors = [
        'news'         => 'success',
        'article'      => 'primary',
        'announcement' => 'warning',
        'speech'       => 'info',
        'program'      => 'secondary',
        'religious'    => 'danger',
    ];
    $color = $colors[$type] ?? 'dark';
    return '<span class="badge bg-' . $color . '">' . postTypeLabel($type) . '</span>';
}

function excerpt(string $text, int $chars = 150): string {
    $text = strip_tags($text);
    if (mb_strlen($text, 'UTF-8') <= $chars) return $text;
    return mb_substr($text, 0, $chars, 'UTF-8') . '...';
}

// ─── Like System ──────────────────────────────────────────────────────────────

/**
 * ایجاد جدول لایک‌ها اگر وجود ندارد
 */
function ensureLikesTable(): void {
    static $done = false;
    if ($done) return;
    try {
        $db = getDB();
        // InnoDB اول، اگر شکست خورد MyISAM امتحان می‌شود
        try {
            $db->exec(
                "CREATE TABLE IF NOT EXISTS `post_likes` (
                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `post_id`    INT UNSIGNED NOT NULL,
                    `ip_hash`    VARCHAR(64)  NOT NULL,
                    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_like` (`post_id`, `ip_hash`),
                    KEY `idx_post` (`post_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (PDOException $e) {
            // فال‌بک به MyISAM (برای هاست‌های قدیمی)
            $db->exec(
                "CREATE TABLE IF NOT EXISTS `post_likes` (
                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `post_id`    INT UNSIGNED NOT NULL,
                    `ip_hash`    VARCHAR(64)  NOT NULL,
                    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_like` (`post_id`, `ip_hash`),
                    KEY `idx_post` (`post_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"
            );
        }
        $done = true;
    } catch (PDOException $e) {
        error_log('ensureLikesTable error: ' . $e->getMessage());
        // بی‌صدا رد شو — toggleLike خودش خطا برمی‌گرداند
    }
}

/**
 * تولید هش یکتا برای کاربر فعلی (IP + User-Agent)
 */
function getUserHash(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_X_REAL_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
    $ip = trim(explode(',', $ip)[0]);
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
    return hash('sha256', $ip . '|' . $ua . '|like_salt_jamiat');
}

/**
 * دریافت تعداد لایک یک پست
 */
function getLikeCount(int $postId): int {
    ensureLikesTable();
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
        $stmt->execute([$postId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * بررسی اینکه آیا کاربر فعلی این پست را لایک کرده است
 */
function hasLiked(int $postId): bool {
    if (isset($_SESSION['liked_posts']) && in_array($postId, $_SESSION['liked_posts'], true)) {
        return true;
    }
    ensureLikesTable();
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ? AND ip_hash = ?");
        $stmt->execute([$postId, getUserHash()]);
        $result = (int)$stmt->fetchColumn() > 0;
        if ($result) {
            $_SESSION['liked_posts'][] = $postId;
        }
        return $result;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * تغییر وضعیت لایک (toggle)
 */
function toggleLike(int $postId): array {
    ensureLikesTable();
    $ipHash = getUserHash();

    try {
        $db        = getDB();
        $checkStmt = $db->prepare("SELECT id FROM post_likes WHERE post_id = ? AND ip_hash = ?");
        $checkStmt->execute([$postId, $ipHash]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $db->prepare("DELETE FROM post_likes WHERE post_id = ? AND ip_hash = ?")->execute([$postId, $ipHash]);
            if (isset($_SESSION['liked_posts'])) {
                $_SESSION['liked_posts'] = array_values(array_diff($_SESSION['liked_posts'], [(int)$postId]));
            }
            $liked = false;
        } else {
            $db->prepare("INSERT IGNORE INTO post_likes (post_id, ip_hash, created_at) VALUES (?, ?, NOW())")
               ->execute([$postId, $ipHash]);
            if (!isset($_SESSION['liked_posts'])) {
                $_SESSION['liked_posts'] = [];
            }
            if (!in_array((int)$postId, $_SESSION['liked_posts'], true)) {
                $_SESSION['liked_posts'][] = (int)$postId;
            }
            $liked = true;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
        $countStmt->execute([$postId]);
        $count = (int)$countStmt->fetchColumn();

        return ['liked' => $liked, 'count' => $count, 'success' => true];

    } catch (PDOException $e) {
        error_log('toggleLike error: ' . $e->getMessage());
        return ['liked' => false, 'count' => 0, 'success' => false, 'error' => 'db_error'];
    }
}

/**
 * دریافت تعداد لایک چندین پست به صورت یک‌جا
 */
function getLikeCountsBulk(array $postIds): array {
    if (empty($postIds)) return [];
    ensureLikesTable();
    try {
        $db           = getDB();
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $stmt         = $db->prepare(
            "SELECT post_id, COUNT(*) AS cnt FROM post_likes WHERE post_id IN ($placeholders) GROUP BY post_id"
        );
        $stmt->execute($postIds);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int)$row['post_id']] = (int)$row['cnt'];
        }
        return $map;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * رندر دکمه لایک با Bootstrap Icons
 */
function renderLikeButton(int $postId, int $count, bool $liked, string $size = 'sm'): string {
    $likedClass = $liked ? 'liked' : '';
    $icon       = $liked
        ? '<i class="bi bi-heart-fill like-icon text-danger"></i>'
        : '<i class="bi bi-heart like-icon"></i>';
    $siteBase   = siteUrl('ajax/like.php');
    return sprintf(
        '<button type="button" class="btn-like %s" data-post-id="%d" data-url="%s" title="%s" aria-label="لایک">
            %s
            <span class="like-count">%s</span>
        </button>',
        $likedClass,
        $postId,
        htmlspecialchars($siteBase, ENT_QUOTES),
        $liked ? 'لایک را بردار' : 'لایک کن',
        $icon,
        $count > 0 ? number_format($count) : ''
    );
}

// ─── Media Table ──────────────────────────────────────────────────────────────
// توجه: اگر includes/media.php قبلاً لود شده باشد، نسخه قوی‌تر آن (با sort_order)
// استفاده می‌شود. در غیر این صورت این نسخه fallback است.
if (!function_exists('ensureMediaTable')) {
    function ensureMediaTable(): void {
        static $done = false;
        if ($done) return;
        try {
            $db = getDB();
            $db->exec(
                "CREATE TABLE IF NOT EXISTS `media_files` (
                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `ref_type`   VARCHAR(30)  NOT NULL DEFAULT 'post',
                    `ref_id`     INT UNSIGNED NOT NULL,
                    `kind`       ENUM('image','video','audio','document') NOT NULL DEFAULT 'image',
                    `file_path`  VARCHAR(500) NOT NULL,
                    `title`      VARCHAR(300) DEFAULT NULL,
                    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_ref` (`ref_type`, `ref_id`),
                    KEY `idx_kind` (`kind`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $done = true;
        } catch (PDOException $e) {
            // بی‌صدا رد شو
        }
    }
}

// ─── Books ────────────────────────────────────────────────────────────────────

/**
 * ایجاد جدول کتاب‌ها
 */
function ensureBooksTable(): void {
    static $done = false;
    if ($done) return;
    try {
        $db = getDB();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS `books` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title`       VARCHAR(400) NOT NULL,
                `description` TEXT             NULL,
                `cover_image` VARCHAR(350)     NULL,
                `pdf_file`    VARCHAR(350)     NULL,
                `word_file`   VARCHAR(350)     NULL,
                `downloads`   INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $done = true;
    } catch (PDOException $e) {
        error_log('ensureBooksTable error: ' . $e->getMessage());
    }
}

/**
 * دریافت لیست کتاب‌ها
 */
function getBooks(array $opts = []): array {
    ensureBooksTable();
    try {
        $db     = getDB();
        $limit  = (int)($opts['limit']  ?? 20);
        $offset = (int)($opts['offset'] ?? 0);
        $search = $opts['search'] ?? '';

        $where  = ['1=1'];
        $params = [];
        if ($search) {
            $where[]  = "(title LIKE ? OR description LIKE ?)";
            $s        = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
        }
        $whereStr = implode(' AND ', $where);

        $stmt = $db->prepare(
            "SELECT * FROM books WHERE $whereStr ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute(array_merge($params, [$limit, $offset]));
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * تعداد کتاب‌ها
 */
function countBooks(array $opts = []): int {
    ensureBooksTable();
    try {
        $db     = getDB();
        $search = $opts['search'] ?? '';
        $where  = ['1=1'];
        $params = [];
        if ($search) {
            $where[]  = "(title LIKE ? OR description LIKE ?)";
            $s        = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
        }
        $whereStr = implode(' AND ', $where);
        $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE $whereStr");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * آپلود فایل کتاب (PDF یا Word)
 */
function uploadBookFile(array $file, string $type = 'pdf'): string {
    $allowedPdf  = ['application/pdf', 'application/x-pdf'];
    $allowedWord = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-word'
    ];
    $allowedExts = ($type === 'pdf') ? ['pdf'] : ['doc', 'docx'];

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = $file['type'] ?? '';

    if (!in_array($ext, $allowedExts, true)) {
        return '';
    }

    // بررسی mime با اندکی انعطاف برای هاست‌های مختلف
    if ($type === 'pdf' && !in_array($mime, $allowedPdf, true) && $ext !== 'pdf') {
        return '';
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return '';
    }

    $uploadDir = UPLOAD_DIR . 'books/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $safeName = uniqid('book_', true) . '.' . $ext;
    $dest     = $uploadDir . $safeName;

    if (@move_uploaded_file($file['tmp_name'], $dest)) {
        return 'uploads/books/' . $safeName;
    }
    return '';
}
