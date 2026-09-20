<?php
/**
 * ajax/like.php — سیستم لایک/آنلایک با AJAX
 * نسخه اصلاح‌شده: سازگاری کامل، بدون خطای دیتابیس
 */

ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// شروع session امن بدون نیاز به auth.php
if (session_status() === PHP_SESSION_NONE) {
    $sessionParams = [
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ];
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $sessionParams['cookie_secure'] = true;
    }
    session_set_cookie_params($sessionParams);
    @session_start();
}

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache');

// فقط POST قبول می‌شود
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// دریافت post_id از POST یا JSON body
$postId = 0;
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonData = json_decode($rawInput, true);
    if (isset($jsonData['post_id'])) {
        $postId = (int)$jsonData['post_id'];
    }
}
if (!$postId) {
    $postId = (int)($_POST['post_id'] ?? 0);
}

if ($postId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid post_id']);
    exit;
}

// بررسی وجود پست — پست باید published باشد
try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$postId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
} catch (PDOException $e) {
    // اگر جدول posts مشکل داشت، خطا برگردان
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}

// اطمینان از وجود جدول post_likes
try {
    $db = getDB();
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
    // اگر InnoDB کار نکرد، MyISAM امتحان کن
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"
        );
    } catch (PDOException $e2) {
        error_log('like.php: cannot create post_likes: ' . $e2->getMessage());
        echo json_encode(['success' => false, 'error' => 'table_error', 'liked' => false, 'count' => 0]);
        exit;
    }
}

// toggle لایک
try {
    $result = toggleLike($postId);
} catch (\Throwable $e) {
    error_log('like.php unhandled: ' . $e->getMessage());
    $result = ['success' => false, 'liked' => false, 'count' => 0, 'error' => 'server_error'];
}

if (session_status() === PHP_SESSION_ACTIVE) {
    @session_write_close();
}

echo json_encode([
    'success' => $result['success'],
    'liked'   => $result['liked']  ?? false,
    'count'   => $result['count']  ?? 0,
    'error'   => $result['error']  ?? null,
]);
exit;
