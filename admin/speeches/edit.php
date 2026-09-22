<?php
/**
 * admin/speeches/edit.php — ویرایش سخنرانی
 */
$adminTitle = 'ویرایش سخنرانی';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/media.php';

// اطمینان از وجود ستون speaker
$db = getDB();

$id   = (int)($_GET['id'] ?? 0);
$post = $id ? getPost($id) : null;
if (!$post || $post['post_type'] !== 'speech') {
    $_SESSION['flash_msg']  = 'سخنرانی یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/speeches/'));
}

$db         = getDB();
$error      = '';
$categories = getCategories();
$audioList  = getMediaFor('post', $id, 'audio');
$videoList  = getMediaFor('post', $id, 'video');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    beginContentUploadScope();
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $speaker     = trim($_POST['speaker']     ?? '');
        $summary     = trim($_POST['summary']     ?? '');
        $content     = $_POST['content']          ?? '';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status      = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';

        if (!$title) {
            $error = 'عنوان الزامی است.';
        } else {
            $featImg = $post['featured_image'];
            $featVid = $post['featured_video'] ?? '';

            // تصویر جدید
            if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $up = uploadImage($_FILES['featured_image'], 'posts');
                if ($up) {
                    if ($featImg) scheduleFileDeletion($featImg);
                    $featImg = $up;
                } else {
                    $error = 'خطا در آپلود تصویر.';
                }
            }
            if (!$error && !empty($_POST['remove_featured'])) {
                if ($featImg) scheduleFileDeletion($featImg);
                $featImg = '';
            }

            // ویدیو جدید
            if (!$error && !empty($_FILES['featured_video']['name']) && $_FILES['featured_video']['error'] === UPLOAD_ERR_OK) {
                ensureFeaturedVideoColumn();
                $upV = uploadFeaturedVideo($_FILES['featured_video']);
                if ($upV) {
                    if ($featVid) scheduleFileDeletion($featVid);
                    $featVid = $upV;
                } else {
                    $error = 'خطا در آپلود ویدیو.';
                }
            }
            if (!$error && !empty($_POST['remove_featured_video'])) {
                if ($featVid) scheduleFileDeletion($featVid);
                $featVid = '';
            }

            if (!$error) {
                try {
                    ensureFeaturedVideoColumn();
                    $slug = uniqueSlug('posts', $title, $id);
                    $stmt = $db->prepare(
                        "UPDATE posts SET title=?, slug=?, speaker=?, summary=?, content=?,
                         featured_image=?, featured_video=?, category_id=?, status=?,
                         page_section='home,speeches', updated_at=NOW()
                         WHERE id=? AND post_type='speech'"
                    );
                    $stmt->execute([
                        $title, $slug, $speaker ?: null, $summary ?: null, $content ?: null,
                        $featImg ?: null, $featVid ?: null,
                        $category_id ?: null, $status, $id
                    ]);

                    // فایل صوتی جدید
                    if (!empty($_FILES['audio_file']['name']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                        $audioPath = uploadAudio($_FILES['audio_file']);
                        if ($audioPath) {
                            $title_audio = pathinfo($_FILES['audio_file']['name'], PATHINFO_FILENAME);
                            $db->prepare(
                                "INSERT INTO media_files (ref_type, ref_id, kind, file_path, title, created_at)
                                 VALUES ('post', ?, 'audio', ?, ?, NOW())"
                            )->execute([$id, $audioPath, $title_audio]);
                        }
                    }

                    // حذف رسانه‌های انتخاب‌شده
                    if (!empty($_POST['delete_media']) && is_array($_POST['delete_media'])) {
                        foreach ($_POST['delete_media'] as $mid) {
                            deleteMediaFile((int)$mid, 'post', $id);
                        }
                    }

                    $post      = getPost($id);
                    $audioList = getMediaFor('post', $id, 'audio');
                    $videoList = getMediaFor('post', $id, 'video');

                    $_SESSION['flash_msg']  = 'سخنرانی با موفقیت بروزرسانی شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/speeches/edit?id=' . $id));
                } catch (PDOException $e) {
                    $error = 'خطا: ';
                }
            }
        }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-pencil-square ms-2"></i>ویرایش: <?= sanitize(mb_strimwidth($post['title'],0,45,'...')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= siteUrl('speech?slug=' . urlencode($post['slug'])) ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-eye ms-1"></i>مشاهده</a>
        <a href="<?= siteUrl('admin/speeches/') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right ms-1"></i>بازگشت</a>
    </div>
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
                        <label class="form-label fw-bold">عنوان <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= sanitize($post['title']) ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">نام سخنران</label>
                            <input type="text" name="speaker" class="form-control" value="<?= sanitize($post['speaker'] ?? '') ?>" placeholder="نام استاد / سخنران">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">موضوع</label>
                            <select name="category_id" class="form-select">
                                <option value="">— انتخاب موضوع —</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '')==$cat['id']?'selected':'' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">خلاصه</label>
                        <textarea name="summary" class="form-control" rows="2"><?= sanitize($post['summary'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن سخنرانی</label>
                        <textarea name="content" class="form-control" rows="10"><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- فایل‌های صوتی موجود + افزودن -->
            <div class="admin-card mb-4" style="border:2px solid #198754;">
                <div class="admin-card-header" style="background:#d1e7dd;color:#0f5132;">
                    <i class="bi bi-headphones ms-2"></i>فایل‌های صوتی
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($audioList)): ?>
                    <p class="text-muted small mb-2">فایل‌های صوتی موجود:</p>
                    <ul class="list-group mb-3">
                        <?php foreach ($audioList as $a): ?>
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delete_media[]" value="<?= (int)$a['id'] ?>" id="dm_<?= (int)$a['id'] ?>">
                                <label class="form-check-label text-danger small" for="dm_<?= (int)$a['id'] ?>">حذف</label>
                            </div>
                            <span class="flex-grow-1 text-truncate small"><?= sanitize($a['title'] ?: basename($a['file_path'])) ?></span>
                            <audio controls preload="none" src="<?= siteUrl($a['file_path']) ?>" style="max-width:200px;height:32px"></audio>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="alert alert-warning mb-3"><i class="bi bi-exclamation-triangle ms-2"></i>هنوز فایل صوتی بارگذاری نشده است.</div>
                    <?php endif; ?>
                    <label class="form-label fw-bold">افزودن فایل صوتی جدید</label>
                    <input type="file" name="audio_file" class="form-control" accept="audio/*,.mp3,.ogg,.wav,.m4a">
                    <div class="form-text">MP3، OGG، WAV، M4A — حداکثر <?= ini_get('upload_max_filesize') ?></div>
                </div>
            </div>

            <!-- ویدیو اختیاری -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-camera-video ms-2"></i>ویدیو <span class="text-muted small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <?php if (!empty($post['featured_video'])): ?>
                    <div class="mb-3">
                        <video src="<?= siteUrl($post['featured_video']) ?>" controls style="max-width:100%;max-height:200px;border-radius:8px;background:#000"></video>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_featured_video" id="remove_featured_video" value="1">
                            <label class="form-check-label text-danger small" for="remove_featured_video">حذف ویدیو</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_video" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv">
                    <div class="form-text">MP4، WebM، MOV — حداکثر 200MB</div>
                </div>
            </div>

            <!-- تصویر شاخص -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-image ms-2"></i>تصویر شاخص <span class="text-muted small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <?php if (!empty($post['featured_image'])): ?>
                    <div class="mb-3">
                        <img src="<?= imgUrl($post['featured_image']) ?>" style="max-width:250px;max-height:160px;border-radius:8px;border:1px solid #eee" alt="">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_featured" id="remove_featured" value="1">
                            <label class="form-check-label text-danger small" for="remove_featured">حذف تصویر</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImg(this,'speechImgPrevEdit')">
                    <img id="speechImgPrevEdit" src="" style="display:none;max-width:280px;max-height:180px;border-radius:8px;margin-top:8px" alt="">
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
                            <option value="draft"     <?= $post['status']==='draft'?'selected':'' ?>>پیش‌نویس</option>
                            <option value="published" <?= $post['status']==='published'?'selected':'' ?>>منتشرشده</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-info text-white btn-lg">
                            <i class="bi bi-save ms-1"></i>ذخیره تغییرات
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
