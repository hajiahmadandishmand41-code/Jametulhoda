<?php
/**
 * admin/books/delete.php — حذف کتاب
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!verifyCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
    $_SESSION['flash_msg']  = 'خطای امنیتی.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/books/'));
}

$id = (int)($_GET['id'] ?? 0);
$db = getDB();
$stmt = $db->prepare("SELECT * FROM books WHERE id=?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    $_SESSION['flash_msg']  = 'کتاب یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/books/'));
}

// حذف فایل‌ها
foreach (['cover_image','pdf_file','word_file'] as $col) {
    if (!empty($book[$col])) {
        $fp = __DIR__ . '/../../' . $book[$col];
        if (file_exists($fp)) @unlink($fp);
    }
}

$db->prepare("DELETE FROM books WHERE id=?")->execute([$id]);

$_SESSION['flash_msg']  = 'کتاب «' . $book['title'] . '» با موفقیت حذف شد.';
$_SESSION['flash_type'] = 'success';
redirect(siteUrl('admin/books/'));
