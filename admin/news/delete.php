<?php
/**
 * admin/news/delete.php — حذف خبر
 */
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id || !verifyCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
    $_SESSION['flash_msg']  = 'درخواست نامعتبر.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/news/'));
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'news' LIMIT 1");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    $_SESSION['flash_msg']  = 'خبر یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/news/'));
}

// حذف تصویر
if ($post['featured_image'] && file_exists(__DIR__ . '/../../' . $post['featured_image'])) {
    @unlink(__DIR__ . '/../../' . $post['featured_image']);
}

$db->prepare("DELETE FROM posts WHERE id = ? AND post_type = 'news'")->execute([$id]);

$_SESSION['flash_msg']  = 'خبر با موفقیت حذف شد.';
$_SESSION['flash_type'] = 'success';
redirect(siteUrl('admin/news/'));
