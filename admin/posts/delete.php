<?php
/**
 * admin/posts/delete.php — حذف مطلب — اصلاح‌شده
 */
require_once __DIR__ . '/../includes/header.php';

if (!verifyCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
    $_SESSION['flash_msg']  = 'خطای امنیتی. دوباره تلاش کنید.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/posts/'));
}

$id   = (int)($_GET['id'] ?? 0);
$post = $id ? getPost($id) : null;

if (!$post) {
    $_SESSION['flash_msg']  = 'مطلب یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/posts/'));
}

$db = getDB();

// حذف تصاویر اضافی
$imgs = $db->prepare("SELECT * FROM post_images WHERE post_id = ?");
$imgs->execute([$id]);
foreach ($imgs->fetchAll() as $img) {
    $fp = __DIR__ . '/../../' . $img['image_path'];
    if (file_exists($fp)) @unlink($fp);
}
$db->prepare("DELETE FROM post_images WHERE post_id = ?")->execute([$id]);

// حذف تصویر شاخص
if ($post['featured_image']) {
    $fp = __DIR__ . '/../../' . $post['featured_image'];
    if (file_exists($fp)) @unlink($fp);
}

// حذف پست
$db->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);

$_SESSION['flash_msg']  = 'مطلب «' . $post['title'] . '» با موفقیت حذف شد.';
$_SESSION['flash_type'] = 'success';
redirect(siteUrl('admin/posts/'));
