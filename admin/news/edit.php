<?php
/**
 * admin/news/edit.php — ویرایش خبر
 */
$adminTitle = 'ویرایش خبر';
require_once __DIR__ . '/../includes/header.php';

$id   = (int)($_GET['id'] ?? 0);
$post = $id ? getPost($id) : null;
if (!$post || $post['post_type'] !== 'news') {
    $_SESSION['flash_msg']  = 'خبر یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/news/'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی.';
    } else {
        $title    = trim($_POST['title']   ?? '');
        $summary  = trim($_POST['summary'] ?? '');
        $content  = $_POST['content']      ?? '';
        $status   = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';
        $pub_date = !empty($_POST['published_at'])
                    ? date('Y-m-d H:i:s', strtotime($_POST['published_at']))
                    : ($post['published_at'] ?? date('Y-m-d H:i:s'));

        if (!$title) {
            $error = 'عنوان خبر الزامی است.';
        } else {
            $featImg = $post['featured_image'];

            if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $up = uploadImage($_FILES['featured_image'], 'posts');
                if ($up) {
                    if ($featImg && file_exists(__DIR__ . '/../../' . $featImg)) @unlink(__DIR__ . '/../../' . $featImg);
                    $featImg = $up;
                } else {
                    $error = 'خطا در آپلود تصویر.';
                }
            }

            if (!$error && !empty($_POST['remove_featured'])) {
                if ($featImg && file_exists(__DIR__ . '/../../' . $featImg)) @unlink(__DIR__ . '/../../' . $featImg);
                $featImg = '';
            }

            if (!$error) {
                try {
                    $db   = getDB();
                    $slug = uniqueSlug('posts', $title, $id);
                    $stmt = $db->prepare(
                        "UPDATE posts SET title=?, slug=?, summary=?, content=?, featured_image=?,
                         status=?, published_at=?, updated_at=NOW()
                         WHERE id=? AND post_type='news'"
                    );
                    $stmt->execute([$title, $slug, $summary ?: null, $content ?: null, $featImg ?: null, $status, $pub_date, $id]);
                    $post = getPost($id);

                    $_SESSION['flash_msg']  = 'خبر با موفقیت بروزرسانی شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/news/edit.php?id=' . $id));
                } catch (PDOException $e) {
                    $error = 'خطا در ذخیره: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-pencil-square ms-2"></i>ویرایش: <?= sanitize(mb_strimwidth($post['title'],0,45,'...')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= siteUrl('post.php?slug=' . urlencode($post['slug'])) ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-eye ms-1"></i>مشاهده</a>
        <a href="<?= siteUrl('admin/news/') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right ms-1"></i>بازگشت</a>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">محتوای خبر</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان خبر <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= sanitize($post['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">خلاصه خبر</label>
                        <textarea name="summary" class="form-control" rows="3"><?= sanitize($post['summary'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن کامل خبر</label>
                        <textarea name="content" class="form-control" rows="12"><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-image ms-2"></i>تصویر شاخص</div>
                <div class="admin-card-body">
                    <?php if (!empty($post['featured_image'])): ?>
                    <div class="mb-3">
                        <img src="<?= imgUrl($post['featured_image']) ?>" style="max-width:280px;max-height:180px;border-radius:8px;border:1px solid #eee" alt="تصویر فعلی">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_featured" id="remove_featured" value="1">
                            <label class="form-check-label text-danger small" for="remove_featured">حذف تصویر</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImg(this,'featPreviewEdit')">
                    <div class="form-text">برای تغییر تصویر، فایل جدید انتخاب کنید</div>
                    <img id="featPreviewEdit" src="" style="display:none;max-width:300px;max-height:200px;border-radius:8px;margin-top:8px" alt="">
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card mb-3">
                <div class="admin-card-header">انتشار</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">وضعیت انتشار</label>
                        <select name="status" class="form-select">
                            <option value="draft"     <?= $post['status']==='draft'?'selected':'' ?>>پیش‌نویس</option>
                            <option value="published" <?= $post['status']==='published'?'selected':'' ?>>منتشرشده</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ انتشار</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="<?= date('Y-m-d\TH:i', strtotime($post['published_at'] ?? 'now')) ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-save ms-1"></i>ذخیره تغییرات
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
