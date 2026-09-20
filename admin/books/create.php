<?php
/**
 * admin/books/create.php — افزودن کتاب جدید
 */
$adminTitle = 'کتاب جدید';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';

ensureBooksTable();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$title) {
            $error = 'نام کتاب الزامی است.';
        } else {
            $db        = getDB();
            $coverImg  = '';
            $pdfFile   = '';
            $wordFile  = '';

            // آپلود تصویر جلد
            if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $up = uploadImage($_FILES['cover_image'], 'book-covers');
                if ($up) {
                    $coverImg = $up;
                } else {
                    $error = 'خطا در آپلود تصویر جلد. فرمت‌های مجاز: JPG، PNG، GIF، WebP';
                }
            }

            // آپلود PDF
            if (!$error && !empty($_FILES['pdf_file']['name']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $up = uploadBookFile($_FILES['pdf_file'], 'pdf');
                if ($up) {
                    $pdfFile = $up;
                } else {
                    $error = 'خطا در آپلود فایل PDF.';
                }
            }

            // آپلود Word
            if (!$error && !empty($_FILES['word_file']['name']) && $_FILES['word_file']['error'] === UPLOAD_ERR_OK) {
                $up = uploadBookFile($_FILES['word_file'], 'word');
                if ($up) {
                    $wordFile = $up;
                } else {
                    $error = 'خطا در آپلود فایل Word.';
                }
            }

            if (!$error) {
                try {
                    $stmt = $db->prepare(
                        "INSERT INTO books (title, description, cover_image, pdf_file, word_file, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
                    );
                    $stmt->execute([$title, $description ?: null, $coverImg ?: null, $pdfFile ?: null, $wordFile ?: null]);

                    $_SESSION['flash_msg']  = 'کتاب «' . $title . '» با موفقیت افزوده شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/books/'));
                } catch (PDOException $e) {
                    error_log('books create error: ' . get_class($e));
                    $error = 'خطا در ذخیره‌سازی. لطفاً دوباره تلاش کنید.';
                }
            }
        }
    }
}
?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-book ms-2"></i>اطلاعات کتاب</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">نام کتاب <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= sanitize($_POST['title'] ?? '') ?>"
                               required placeholder="نام کتاب را وارد کنید">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">توضیح کوتاه <span class="text-muted small">(اختیاری)</span></label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="توضیح مختصری درباره محتوای کتاب..."><?= sanitize($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-file-earmark-arrow-up ms-2"></i>فایل‌های دانلود</div>
                <div class="admin-card-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">فایل PDF <span class="text-danger">*</span></label>
                        <input type="file" name="pdf_file" id="pdfFileInput"
                               class="form-control" accept=".pdf,application/pdf">
                        <div class="form-text">فرمت مجاز: PDF — حداکثر ۲۰ مگابایت</div>
                        <div id="pdfPreview" class="mt-2" style="display:none">
                            <span class="badge bg-danger fs-6 p-2">
                                <i class="bi bi-file-pdf ms-1"></i>
                                <span id="pdfName"></span>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">فایل Word <span class="text-muted small">(اختیاری)</span></label>
                        <input type="file" name="word_file" id="wordFileInput"
                               class="form-control" accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        <div class="form-text">فرمت مجاز: DOC، DOCX — حداکثر ۲۰ مگابایت</div>
                        <div id="wordPreview" class="mt-2" style="display:none">
                            <span class="badge bg-primary fs-6 p-2">
                                <i class="bi bi-file-word ms-1"></i>
                                <span id="wordName"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-image ms-2"></i>تصویر جلد <span class="text-muted small">(اختیاری)</span></div>
                <div class="admin-card-body text-center">
                    <img id="coverPreview" src="" alt=""
                         style="max-width:100%;max-height:220px;border-radius:8px;display:none;margin-bottom:12px;border:2px solid #eee">
                    <input type="file" name="cover_image" id="coverImageInput"
                           class="form-control" accept="image/*">
                    <div class="form-text mt-1">JPG، PNG، WebP — حداکثر ۵ مگابایت</div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-body">
                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-check-circle ms-2"></i>ذخیره کتاب
                    </button>
                    <a href="<?= siteUrl('admin/books/') ?>" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-right ms-1"></i>بازگشت
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('coverImageInput').addEventListener('change', function(){
    var f = this.files[0];
    var prev = document.getElementById('coverPreview');
    if (!f) { prev.style.display='none'; return; }
    var url = URL.createObjectURL(f);
    prev.src = url; prev.style.display = 'block';
    prev.onload = function(){ URL.revokeObjectURL(url); };
});
document.getElementById('pdfFileInput').addEventListener('change', function(){
    var f = this.files[0];
    var div = document.getElementById('pdfPreview');
    var nm = document.getElementById('pdfName');
    if (!f) { div.style.display='none'; return; }
    nm.textContent = f.name + ' (' + (f.size/1024/1024).toFixed(1) + ' MB)';
    div.style.display = 'block';
});
document.getElementById('wordFileInput').addEventListener('change', function(){
    var f = this.files[0];
    var div = document.getElementById('wordPreview');
    var nm = document.getElementById('wordName');
    if (!f) { div.style.display='none'; return; }
    nm.textContent = f.name + ' (' + (f.size/1024/1024).toFixed(1) + ' MB)';
    div.style.display = 'block';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
