<?php
/**
 * admin/articles/delete.php — حذف مقاله
 */
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id || !verifyCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
    $_SESSION['flash_msg']  = 'درخواست نامعتبر.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/articles/'));
}

$db   = getDB();
$stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND post_type = 'article' LIMIT 1");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    $_SESSION['flash_msg']  = 'مقاله یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/articles/'));
}

$db->prepare("DELETE FROM posts WHERE id = ? AND post_type = 'article'")->execute([$id]);

$_SESSION['flash_msg']  = 'مقاله با موفقیت حذف شد.';
$_SESSION['flash_type'] = 'success';
redirect(siteUrl('admin/articles/'));
