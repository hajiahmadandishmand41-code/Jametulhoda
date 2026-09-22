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
    beginContentUploadScope();
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $author      = trim($_POST['author'] ?? '');
        $translator  = trim($_POST['translator'] ?? '');
        $publisher   = trim($_POST['publisher'] ?? '');
        $publish_year= trim($_POST['publish_year'] ?? '');
        $pages       = (int)($_POST['pages'] ?? 0);
        $toc         = trim($_POST['toc'] ?? '');
        $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
        $topicIds    = array_filter(array_map('intval', (array)($_POST['topic_ids'] ?? [])));

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
                    $slug = uniqueSlug('books',$title);
                    $stmt = $db->prepare(
                        "INSERT INTO books (title, slug, description, author, translator, publisher, publish_year, pages, toc, cover_image, pdf_file, word_file, is_featured, status, sort_order, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', 0, NOW(), NOW()) RETURNING id"
                    );
                    $stmt->execute([$title, $slug, $description ?: null, $author ?: null, $translator ?: null, $publisher ?: null, $publish_year ?: null, $pages ?: null, $toc ?: null, $coverImg ?: null, $pdfFile ?: null, $wordFile ?: null, $is_featured]);
                    $bookId = (int)$stmt->fetchColumn();
                    $stmt->closeCursor(); // release the write lock promptly (shutdown journal writes must never block)
                    if($topicIds){ $ins=$db->prepare("INSERT INTO book_topics (book_id, topic_id) VALUES (?,?) ON CONFLICT DO NOTHING"); foreach($topicIds as $tid) $ins->execute([$bookId,$tid]); }

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
                    <div class="row g-3 mt-1">
                        <div class="col-md-6"><label class="form-label">نویسنده</label><input type="text" name="author" class="form-control" value="<?= sanitize($_POST['author'] ?? '') ?>" placeholder="نویسنده"></div>
                        <div class="col-md-6"><label class="form-label">مترجم</label><input type="text" name="translator" class="form-control" value="<?= sanitize($_POST['translator'] ?? '') ?>" placeholder="مترجم (اختیاری)"></div>
                        <div class="col-md-6"><label class="form-label">ناشر</label><input type="text" name="publisher" class="form-control" value="<?= sanitize($_POST['publisher'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">سال نشر</label><input type="text" name="publish_year" class="form-control" value="<?= sanitize($_POST['publish_year'] ?? '') ?>" placeholder="۱۴۰۳"></div>
                        <div class="col-md-3"><label class="form-label">تعداد صفحات</label><input type="number" name="pages" class="form-control" value="<?= sanitize($_POST['pages'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label fw-bold">فهرست مطالب (toc)</label><textarea name="toc" class="form-control" rows="3" placeholder="فهرست فصل‌ها..."><?= sanitize($_POST['toc'] ?? '') ?></textarea></div>
                    <div class="form-check mt-3"><input type="checkbox" name="is_featured" value="1" class="form-check-input" id="bf" <?= !empty($_POST['is_featured'])?'checked':'' ?>><label for="bf" class="form-check-label">ویژه در صفحه اصلی</label></div>
                    <?php $allTopics=getTopics(['limit'=>200]); ?>
                    <div class="mt-3"><label class="form-label fw-bold"><i class="bi bi-diagram-3 ms-1"></i> موضوعات</label>
                    <div style="max-height:180px;overflow:auto;border:1px solid #e8e6dc;border-radius:10px;padding:10px;background:#fafaf7">
                        <?php if(empty($allTopics)): ?><div class="text-muted small">موضوعی نیست.</div><?php else: foreach($allTopics as $tt): ?>
                        <label class="form-check"><input type="checkbox" class="form-check-input" name="topic_ids[]" value="<?= $tt['id'] ?>"> <span class="form-check-label small"><?= sanitize($tt['name']) ?></span></label>
                        <?php endforeach; endif; ?>
                    </div></div>

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
