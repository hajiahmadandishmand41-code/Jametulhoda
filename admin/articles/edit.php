<?php
/**
 * admin/articles/edit.php — ویرایش مقاله
 */
$adminTitle = 'ویرایش مقاله';
require_once __DIR__ . '/../includes/header.php';

$id   = (int)($_GET['id'] ?? 0);
$post = $id ? getPost($id) : null;
if (!$post || $post['post_type'] !== 'article') {
    $_SESSION['flash_msg']  = 'مقاله یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/articles/'));
}

$error      = '';
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $content     = $_POST['content']          ?? '';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status      = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';

        if (!$title) {
            $error = 'عنوان مقاله الزامی است.';
        } else {
            try {
                $db   = getDB();
                $slug = uniqueSlug('posts', $title, $id);
                $stmt = $db->prepare(
                    "UPDATE posts SET title=?, slug=?, content=?, post_type='article',
                     page_section='home,articles', category_id=?, status=?, updated_at=NOW()
                     WHERE id=? AND post_type='article'"
                );
                $stmt->execute([$title, $slug, $content ?: null, $category_id ?: null, $status, $id]);
                $post = getPost($id);

                $_SESSION['flash_msg']  = 'مقاله با موفقیت بروزرسانی شد.';
                $_SESSION['flash_type'] = 'success';
                redirect(siteUrl('admin/articles/edit?id=' . $id));
            } catch (PDOException $e) {
                $error = 'خطا در ذخیره تغییرات: ';
            }
        }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-pencil-square ms-2"></i>ویرایش: <?= sanitize(mb_strimwidth($post['title'],0,45,'...')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= postUrl($post) ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-eye ms-1"></i>مشاهده</a>
        <a href="<?= siteUrl('admin/articles/') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right ms-1"></i>بازگشت</a>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">محتوای مقاله</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان مقاله <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= sanitize($post['title']) ?>" required>
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن کامل مقاله</label>
                        <textarea name="content" class="form-control" rows="18"><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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
                            <option value="draft"     <?= $post['status']==='draft'?'selected':'' ?>>پیش‌نویس</option>
                            <option value="published" <?= $post['status']==='published'?'selected':'' ?>>منتشرشده</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save ms-1"></i>ذخیره تغییرات
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
                        <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '')==$cat['id']?'selected':'' ?>>
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
