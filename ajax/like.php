<?php
/**
 * ajax/like.php — سیستم لایک/آنلایک با AJAX
 * نسخه اصلاح‌شده: سازگاری کامل، بدون خطای دیتابیس
 */

ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

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


if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'csrf']); exit;
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

// toggle لایک
try {
    $result = toggleLike($postId);
} catch (\Throwable $e) {
    error_log('like.php unhandled: ' . get_class($e));
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
