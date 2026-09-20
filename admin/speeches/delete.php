<?php
/**
 * admin/speeches/delete.php — حذف سخنرانی
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/media.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id || !verifyCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
    $_SESSION['flash_msg']  = 'درخواست نامعتبر.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/speeches/'));
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'speech' LIMIT 1");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    $_SESSION['flash_msg']  = 'سخنرانی یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/speeches/'));
}

// حذف تصویر شاخص
if ($post['featured_image'] && file_exists(__DIR__ . '/../../' . $post['featured_image'])) {
    @unlink(__DIR__ . '/../../' . $post['featured_image']);
}

// حذف فایل‌های رسانه‌ای
$mediaList = $db->prepare("SELECT id FROM media_files WHERE ref_type='post' AND ref_id=?");
$mediaList->execute([$id]);
foreach ($mediaList->fetchAll() as $m) {
    deleteMediaFile((int)$m['id'], 'post', $id);
}

$db->prepare("DELETE FROM posts WHERE id = ? AND post_type = 'speech'")->execute([$id]);

$_SESSION['flash_msg']  = 'سخنرانی با موفقیت حذف شد.';
$_SESSION['flash_type'] = 'success';
redirect(siteUrl('admin/speeches/'));
