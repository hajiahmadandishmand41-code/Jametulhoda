<?php
/**
 * admin/books/edit.php — ویرایش کتاب
 */
$adminTitle = 'ویرایش کتاب';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';

ensureBooksTable();

$id   = (int)($_GET['id'] ?? 0);
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM books WHERE id=?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    $_SESSION['flash_msg']  = 'کتاب یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/books/'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    beginContentUploadScope();
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی.';
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
            $coverImg = $book['cover_image'] ?? '';
            $pdfFile  = $book['pdf_file'] ?? '';
            $wordFile = $book['word_file'] ?? '';

            // آپلود تصویر جلد جدید
            if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $up = uploadImage($_FILES['cover_image'], 'book-covers');
                if ($up) {
                    if ($coverImg) scheduleFileDeletion($coverImg);
                    $coverImg = $up;
                } else {
                    $error = 'خطا در آپلود تصویر جلد.';
                }
            }
            if (!$error && !empty($_POST['remove_cover'])) {
                if ($coverImg) scheduleFileDeletion($coverImg);
                $coverImg = '';
            }

            // آپلود PDF جدید
            if (!$error && !empty($_FILES['pdf_file']['name']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $up = uploadBookFile($_FILES['pdf_file'], 'pdf');
                if ($up) {
                    if ($pdfFile) scheduleFileDeletion($pdfFile);
                    $pdfFile = $up;
                } else {
                    $error = 'خطا در آپلود فایل PDF.';
                }
            }
            if (!$error && !empty($_POST['remove_pdf'])) {
                if ($pdfFile) scheduleFileDeletion($pdfFile);
                $pdfFile = '';
            }

            // آپلود Word جدید
            if (!$error && !empty($_FILES['word_file']['name']) && $_FILES['word_file']['error'] === UPLOAD_ERR_OK) {
                $up = uploadBookFile($_FILES['word_file'], 'word');
                if ($up) {
                    if ($wordFile) scheduleFileDeletion($wordFile);
                    $wordFile = $up;
                } else {
                    $error = 'خطا در آپلود فایل Word.';
                }
            }
            if (!$error && !empty($_POST['remove_word'])) {
                if ($wordFile) scheduleFileDeletion($wordFile);
                $wordFile = '';
            }

            if (!$error) {
                try {
                    $slug = uniqueSlug('books',$title,(int)$id);
                    $upd = $db->prepare(
                        "UPDATE books SET title=?, slug=?, description=?, author=?, translator=?, publisher=?, publish_year=?, pages=?, toc=?, cover_image=?, pdf_file=?, word_file=?, is_featured=?, updated_at=NOW() WHERE id=?"
                    );
                    $upd->execute([$title, $slug, $description ?: null, $author ?: null, $translator ?: null, $publisher ?: null, $publish_year ?: null, $pages ?: null, $toc ?: null, $coverImg ?: null, $pdfFile ?: null, $wordFile ?: null, $is_featured, $id]);
                    $db->prepare("DELETE FROM book_topics WHERE book_id=?")->execute([$id]);
                    if($topicIds){ $ins=$db->prepare("INSERT INTO book_topics (book_id, topic_id) VALUES (?,?) ON CONFLICT DO NOTHING"); foreach($topicIds as $tid) $ins->execute([$id,$tid]); }

                    $_SESSION['flash_msg']  = 'کتاب با موفقیت بروزرسانی شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/books/'));
                } catch (PDOException $e) {
                    error_log('books edit error: ' . get_class($e));
                    $error = 'خطا در ذخیره‌سازی.';
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
                               value="<?= sanitize($book['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">توضیح کوتاه</label>
                        <textarea name="description" class="form-control" rows="4"><?= sanitize($book['description'] ?? '') ?></textarea>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6"><label class="form-label">نویسنده</label><input type="text" name="author" class="form-control" value="<?= sanitize($book['author'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">مترجم</label><input type="text" name="translator" class="form-control" value="<?= sanitize($book['translator'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">ناشر</label><input type="text" name="publisher" class="form-control" value="<?= sanitize($book['publisher'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">سال نشر</label><input type="text" name="publish_year" class="form-control" value="<?= sanitize($book['publish_year'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">تعداد صفحات</label><input type="number" name="pages" class="form-control" value="<?= sanitize($book['pages'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">فهرست مطالب</label><textarea name="toc" class="form-control" rows="3"><?= sanitize($book['toc'] ?? '') ?></textarea></div>
                    <div class="form-check mt-3"><input type="checkbox" name="is_featured" value="1" class="form-check-input" id="bf2" <?= ($book['is_featured']??0)?'checked':'' ?>><label for="bf2" class="form-check-label">ویژه</label></div>
                    <?php $allTopics=getTopics(['limit'=>200]); $curBookTopicIds=getTopicIdsForBook((int)$book['id']); ?>
                    <div class="mt-3"><label class="form-label fw-bold"><i class="bi bi-diagram-3 ms-1"></i> موضوعات</label>
                    <div style="max-height:180px;overflow:auto;border:1px solid #e8e6dc;border-radius:10px;padding:10px;background:#fafaf7">
                        <?php if(empty($allTopics)): ?><div class="text-muted small">موضوعی نیست.</div><?php else: foreach($allTopics as $tt): ?>
                        <label class="form-check"><input type="checkbox" class="form-check-input" name="topic_ids[]" value="<?= $tt['id'] ?>" <?= in_array($tt['id'],$curBookTopicIds)?'checked':'' ?>> <span class="form-check-label small"><?= sanitize($tt['name']) ?></span></label>
                        <?php endforeach; endif; ?>
                    </div></div>

                    </div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-file-earmark-arrow-up ms-2"></i>فایل‌های دانلود</div>
                <div class="admin-card-body">
                    <?php if ($book['pdf_file']): ?>
                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-file-pdf text-danger ms-2"></i> فایل PDF فعلی:
                                <strong><?= sanitize(basename($book['pdf_file'])) ?></strong>
                            </span>
                            <label class="form-check mb-0">
                                <input type="checkbox" name="remove_pdf" value="1" class="form-check-input">
                                <span class="form-check-label text-danger small">حذف</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <?= $book['pdf_file'] ? 'جایگزین PDF' : 'فایل PDF' ?>
                        </label>
                        <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf">
                    </div>

                    <?php if ($book['word_file']): ?>
                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-file-word text-primary ms-2"></i> فایل Word فعلی:
                                <strong><?= sanitize(basename($book['word_file'])) ?></strong>
                            </span>
                            <label class="form-check mb-0">
                                <input type="checkbox" name="remove_word" value="1" class="form-check-input">
                                <span class="form-check-label text-danger small">حذف</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <?= $book['word_file'] ? 'جایگزین Word' : 'فایل Word (اختیاری)' ?>
                        </label>
                        <input type="file" name="word_file" class="form-control"
                               accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-image ms-2"></i>تصویر جلد</div>
                <div class="admin-card-body text-center">
                    <?php if ($book['cover_image']): ?>
                    <img src="<?= imgUrl($book['cover_image']) ?>"
                         alt="جلد کتاب"
                         style="max-width:100%;max-height:200px;border-radius:8px;margin-bottom:12px;border:2px solid #eee">
                    <label class="form-check d-inline-flex align-items-center gap-2 mb-3">
                        <input type="checkbox" name="remove_cover" value="1" class="form-check-input">
                        <span class="text-danger small">حذف تصویر جلد</span>
                    </label>
                    <br>
                    <?php else: ?>
                    <img id="coverPreviewNew" src="" alt=""
                         style="max-width:100%;max-height:200px;border-radius:8px;display:none;margin-bottom:12px">
                    <?php endif; ?>
                    <input type="file" name="cover_image" id="coverImgNew"
                           class="form-control" accept="image/*">
                    <div class="form-text mt-1">تصویر جلد جدید</div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-body">
                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-check-circle ms-2"></i>ذخیره تغییرات
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
document.getElementById('coverImgNew').addEventListener('change', function(){
    var f = this.files[0];
    var prev = document.getElementById('coverPreviewNew');
    if (!prev) return;
    if (!f) { prev.style.display='none'; return; }
    var url = URL.createObjectURL(f);
    prev.src = url; prev.style.display = 'block';
    prev.onload = function(){ URL.revokeObjectURL(url); };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
