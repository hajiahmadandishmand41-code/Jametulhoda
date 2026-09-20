<?php
/**
 * admin/news/create.php — ثبت خبر جدید
 * فیلدها: عنوان، خلاصه، متن کامل، تصویر شاخص، وضعیت
 */
$adminTitle = 'خبر جدید';
require_once __DIR__ . '/../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title   = trim($_POST['title']   ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = $_POST['content']      ?? '';
        $status  = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';
        $pub_date = !empty($_POST['published_at'])
                    ? date('Y-m-d H:i:s', strtotime($_POST['published_at']))
                    : date('Y-m-d H:i:s');

        if (!$title) {
            $error = 'عنوان خبر الزامی است.';
        } else {
            $featImg = '';
            if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $featImg = uploadImage($_FILES['featured_image'], 'posts');
                if (!$featImg) $error = 'خطا در آپلود تصویر شاخص. فرمت‌های مجاز: JPG، PNG، GIF، WebP';
            }

            if (!$error) {
                try {
                    $db   = getDB();
                    ensureFeaturedVideoColumn();
                    $admin = currentAdmin();
                    $slug  = uniqueSlug('posts', $title);
                    $stmt  = $db->prepare(
                        "INSERT INTO posts (title, slug, summary, content, featured_image, post_type, page_section, author_id, status, published_at, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, 'news', 'home,news', ?, ?, ?, NOW(), NOW())"
                    );
                    $stmt->execute([$title, $slug, $summary ?: null, $content ?: null, $featImg ?: null, $admin['id'], $status, $pub_date]);
                    $newId = (int)$db->lastInsertId();

                    $_SESSION['flash_msg']  = 'خبر با موفقیت ذخیره شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/news/edit.php?id=' . $newId));
                } catch (PDOException $e) {
                    $error = 'خطا در ذخیره خبر: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-plus-circle ms-2 text-success"></i>خبر جدید</h5>
    <a href="<?= siteUrl('admin/news/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right ms-1"></i>بازگشت
    </a>
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
                        <input type="text" name="title" class="form-control" value="<?= sanitize($_POST['title'] ?? '') ?>" required placeholder="عنوان خبر را بنویسید">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">خلاصه خبر</label>
                        <textarea name="summary" class="form-control" rows="3" placeholder="خلاصه کوتاه خبر..."><?= sanitize($_POST['summary'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن کامل خبر</label>
                        <textarea name="content" class="form-control" rows="12" placeholder="متن کامل خبر را بنویسید..."><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-image ms-2"></i>تصویر شاخص</div>
                <div class="admin-card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImg(this,'featPreview')">
                    <div class="form-text">فرمت‌های مجاز: JPG، PNG، GIF، WebP — حداکثر ۲۰ مگابایت</div>
                    <img id="featPreview" src="" style="display:none;max-width:300px;max-height:200px;border-radius:8px;margin-top:8px" alt="">
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
                            <option value="draft"     <?= ($_POST['status']??'draft')==='draft'?'selected':'' ?>>پیش‌نویس</option>
                            <option value="published" <?= ($_POST['status']??'')==='published'?'selected':'' ?>>منتشرشده</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ انتشار</label>
                        <input type="datetime-local" name="published_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        <div class="form-text">در صورت خالی بودن، تاریخ فعلی ثبت می‌شود.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-send ms-1"></i>ذخیره خبر
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
