<?php
/**
 * admin/speeches/create.php — ثبت سخنرانی جدید
 * فیلدها: عنوان، نام سخنران، موضوع، متن، فایل صوت (الزامی)، ویدیو (اختیاری)، تصویر (اختیاری)
 */
$adminTitle = 'سخنرانی جدید';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/media.php';

$error = '';

// اطمینان از وجود ستون speaker
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $speaker     = trim($_POST['speaker']     ?? '');
        $summary     = trim($_POST['summary']     ?? '');
        $content     = $_POST['content']          ?? '';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status      = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';

        if (!$title) {
            $error = 'عنوان سخنرانی الزامی است.';
        } else {
            $featImg = '';
            $featVid = '';

            // آپلود تصویر شاخص
            if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $featImg = uploadImage($_FILES['featured_image'], 'posts');
                if (!$featImg) $error = 'خطا در آپلود تصویر.';
            }

            // آپلود ویدیو (اختیاری)
            if (!$error && !empty($_FILES['featured_video']['name']) && $_FILES['featured_video']['error'] === UPLOAD_ERR_OK) {
                ensureFeaturedVideoColumn();
                $featVid = uploadFeaturedVideo($_FILES['featured_video']);
                if (!$featVid) $error = 'خطا در آپلود ویدیو. فرمت‌های مجاز: MP4، WebM، MOV، MKV (حداکثر 200MB)';
            }

            if (!$error) {
                try {
                    $db    = getDB();
                    ensureFeaturedVideoColumn();
                    $admin = currentAdmin();
                    $slug  = uniqueSlug('posts', $title);

                    // ذخیره در posts
                    $stmt  = $db->prepare(
                        "INSERT INTO posts (title, slug, speaker, summary, content, featured_image, featured_video,
                         post_type, page_section, category_id, author_id, status, published_at, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'speech', 'home,speeches', ?, ?, ?, NOW(), NOW(), NOW()) RETURNING id"
                    );
                    $stmt->execute([
                        $title, $slug, $speaker ?: null, $summary ?: null, $content ?: null,
                        $featImg ?: null, $featVid ?: null,
                        $category_id ?: null,
                        $admin['id'], $status
                    ]);
                    $newId = (int)$stmt->fetchColumn();

                    // آپلود فایل صوتی — الزامی
                    if (!empty($_FILES['audio_file']['name']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                        $audioPath = uploadAudio($_FILES['audio_file']);
                        if ($audioPath) {
                            $title_audio = pathinfo($_FILES['audio_file']['name'], PATHINFO_FILENAME);
                            $db->prepare(
                                "INSERT INTO media_files (ref_type, ref_id, kind, file_path, title, created_at)
                                 VALUES ('post', ?, 'audio', ?, ?, NOW())"
                            )->execute([$newId, $audioPath, $title_audio]);
                        }
                    }

                    $_SESSION['flash_msg']  = 'سخنرانی با موفقیت ذخیره شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/speeches/edit.php?id=' . $newId));
                } catch (PDOException $e) {
                    $error = 'خطا در ذخیره: ';
                }
            }
        }
    }
}

$categories = getCategories();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-plus-circle ms-2 text-info"></i>سخنرانی جدید</h5>
    <a href="<?= siteUrl('admin/speeches/') ?>" class="btn btn-outline-secondary btn-sm">
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
            <!-- اطلاعات اصلی -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">اطلاعات سخنرانی</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان سخنرانی <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= sanitize($_POST['title'] ?? '') ?>" required placeholder="عنوان کامل سخنرانی">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">نام سخنران</label>
                            <input type="text" name="speaker" class="form-control" value="<?= sanitize($_POST['speaker'] ?? '') ?>" placeholder="نام استاد / سخنران">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">موضوع</label>
                            <select name="category_id" class="form-select">
                                <option value="">— انتخاب موضوع —</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id']??'')==$cat['id']?'selected':'' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">خلاصه</label>
                        <textarea name="summary" class="form-control" rows="2" placeholder="خلاصه کوتاه..."><?= sanitize($_POST['summary'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن سخنرانی</label>
                        <textarea name="content" class="form-control" rows="10" placeholder="متن کامل سخنرانی..."><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- فایل صوتی — الزامی -->
            <div class="admin-card mb-4" style="border:2px solid #198754;">
                <div class="admin-card-header" style="background:#d1e7dd;color:#0f5132;">
                    <i class="bi bi-headphones ms-2"></i>فایل صوتی
                    <span class="badge bg-success ms-2">الزامی</span>
                </div>
                <div class="admin-card-body">
                    <input type="file" name="audio_file" class="form-control" accept="audio/*,.mp3,.ogg,.wav,.m4a" onchange="previewAudioNew(this)">
                    <div class="form-text mt-1">فرمت‌های مجاز: <strong>MP3، OGG، WAV، M4A</strong> — حداکثر <?= ini_get('upload_max_filesize') ?></div>
                    <div id="audioNewPreview" class="mt-3" style="display:none">
                        <audio id="audioNewEl" controls class="w-100" style="border-radius:8px"></audio>
                    </div>
                </div>
            </div>

            <!-- ویدیو — اختیاری -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-camera-video ms-2"></i>فایل ویدیو <span class="text-muted small">(اختیاری)</span>
                </div>
                <div class="admin-card-body">
                    <input type="file" name="featured_video" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv">
                    <div class="form-text">MP4، WebM، MOV، MKV — حداکثر 200MB</div>
                </div>
            </div>

            <!-- تصویر شاخص — اختیاری -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-image ms-2"></i>تصویر شاخص <span class="text-muted small">(اختیاری)</span>
                </div>
                <div class="admin-card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImg(this,'speechImgPrev')">
                    <div class="form-text">JPG، PNG، WebP — حداکثر 20MB</div>
                    <img id="speechImgPrev" src="" style="display:none;max-width:280px;max-height:180px;border-radius:8px;margin-top:8px" alt="">
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card">
                <div class="admin-card-header">انتشار</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">وضعیت</label>
                        <select name="status" class="form-select">
                            <option value="draft"     <?= ($_POST['status']??'draft')==='draft'?'selected':'' ?>>پیش‌نویس</option>
                            <option value="published" <?= ($_POST['status']??'')==='published'?'selected':'' ?>>منتشرشده</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-info text-white btn-lg">
                            <i class="bi bi-send ms-1"></i>ذخیره سخنرانی
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function previewAudioNew(input) {
    var wrap  = document.getElementById('audioNewPreview');
    var audio = document.getElementById('audioNewEl');
    if (!input.files || !input.files[0]) { if (wrap) wrap.style.display = 'none'; return; }
    var url = URL.createObjectURL(input.files[0]);
    if (audio) { audio.src = url; audio.onloadedmetadata = function(){ URL.revokeObjectURL(url); }; }
    if (wrap) wrap.style.display = 'block';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
