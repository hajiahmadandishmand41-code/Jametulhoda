<?php
/**
 * admin/lessons/delete.php — حذف درس — اصلاح‌شده
 */
require_once __DIR__ . '/../includes/header.php';

if (!verifyCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
    $_SESSION['flash_msg']  = 'خطای امنیتی. دوباره تلاش کنید.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/lessons/'));
}

$id     = (int)($_GET['id'] ?? 0);
$lesson = $id ? getLesson($id) : null;

if (!$lesson) {
    $_SESSION['flash_msg']  = 'درس یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/lessons/'));
}

$db = getDB();

// حذف تصویر
if ($lesson['featured_image']) {
    $fp = __DIR__ . '/../../' . $lesson['featured_image'];
    if (file_exists($fp)) @unlink($fp);
}

// حذف فایل صوتی
if ($lesson['audio_file']) {
    $fp = __DIR__ . '/../../' . $lesson['audio_file'];
    if (file_exists($fp)) @unlink($fp);
}

// حذف درس
$db->prepare("DELETE FROM lessons WHERE id = ?")->execute([$id]);

$_SESSION['flash_msg']  = 'درس «' . $lesson['title'] . '» با موفقیت حذف شد.';
$_SESSION['flash_type'] = 'success';
redirect(siteUrl('admin/lessons/'));
