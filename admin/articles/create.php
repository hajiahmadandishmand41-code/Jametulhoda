<?php
/**
 * admin/articles/create.php — ثبت مقاله جدید
 * فیلدها: عنوان، موضوع (دسته‌بندی)، متن کامل، وضعیت
 * مقاله نیازی به تصویر، فایل صوت یا ویدیو ندارد
 */
$adminTitle = 'مقاله جدید';
require_once __DIR__ . '/../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $content     = $_POST['content']          ?? '';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status      = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';

        if (!$title) {
            $error = 'عنوان مقاله الزامی است.';
        } else {
            try {
                $db    = getDB();
                $admin = currentAdmin();
                $slug  = uniqueSlug('posts', $title);
                $stmt  = $db->prepare(
                    "INSERT INTO posts (title, slug, content, post_type, page_section, category_id, author_id, status, published_at, created_at, updated_at)
                     VALUES (?, ?, ?, 'article', 'home,articles', ?, ?, ?, NOW(), NOW(), NOW())"
                );
                $stmt->execute([
                    $title, $slug, $content ?: null,
                    $category_id ?: null,
                    $admin['id'], $status
                ]);
                $newId = (int)$db->lastInsertId();

                $_SESSION['flash_msg']  = 'مقاله با موفقیت ذخیره شد.';
                $_SESSION['flash_type'] = 'success';
                redirect(siteUrl('admin/articles/edit.php?id=' . $newId));
            } catch (PDOException $e) {
                $error = 'خطا در ذخیره مقاله: ' . $e->getMessage();
            }
        }
    }
}

$categories = getCategories();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-plus-circle ms-2 text-primary"></i>مقاله جدید</h5>
    <a href="<?= siteUrl('admin/articles/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right ms-1"></i>بازگشت
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
<?php endif; ?>

<div class="alert alert-info small mb-4">
    <i class="bi bi-info-circle ms-2"></i>
    مقاله فقط شامل عنوان، موضوع و متن است. برای سخنرانی‌ها با فایل صوتی از بخش «سخنرانی‌ها» استفاده کنید.
</div>

<form method="post" class="admin-form">
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">محتوای مقاله</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان مقاله <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= sanitize($_POST['title'] ?? '') ?>" required placeholder="عنوان کامل مقاله">
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن کامل مقاله <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="18" placeholder="متن کامل مقاله را بنویسید..."><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
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
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send ms-1"></i>ذخیره مقاله
                        </button>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><i class="bi bi-folder ms-2"></i>موضوع (دسته‌بندی)</div>
                <div class="admin-card-body">
                    <select name="category_id" class="form-select">
                        <option value="">— بدون موضوع —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id']??'')==$cat['id']?'selected':'' ?>>
                            <?= sanitize($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
