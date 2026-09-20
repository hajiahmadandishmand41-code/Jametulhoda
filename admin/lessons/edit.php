<?php
/**
 * admin/lessons/edit.php — ویرایش درس با فایل صوتی
 * اصلاح‌شده: Migration ستون‌های lessons، آپلود صوت بدون خطا
 */
$adminTitle = 'ویرایش درس';
require_once __DIR__ . '/../includes/header.php';

// Migration: اطمینان از وجود ستون‌های summary و page_section
ensureLessonsColumns();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect(siteUrl('admin/lessons/'));
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM lessons WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    $_SESSION['flash_msg']  = 'درس یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/lessons/'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title   = trim($_POST['title']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $teacher = trim($_POST['teacher'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = $_POST['content']      ?? '';
        $status  = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';

        $sections = $_POST['page_section'] ?? ['home'];
        if (!is_array($sections)) $sections = explode(',', $sections);
        $page_section = implode(',', array_filter(array_map('trim', $sections)));
        if (!$page_section) $page_section = 'home';

        if (!$title) {
            $error = 'عنوان درس الزامی است.';
        } else {
            $featImg   = $lesson['featured_image'];
            $audioPath = $lesson['audio_file'];

            // آپلود تصویر شاخص جدید
            if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $up = uploadImage($_FILES['featured_image'], 'lessons');
                if ($up) {
                    if ($featImg) {
                        $old = $featImg;
                        scheduleFileDeletion($old);
                    }
                    $featImg = $up;
                } else {
                    $error = 'خطا در آپلود تصویر.';
                }
            }

            // حذف تصویر شاخص
            if (!$error && !empty($_POST['remove_image'])) {
                if ($featImg) {
                    $old = $featImg;
                    scheduleFileDeletion($old);
                }
                $featImg = '';
            }

            // آپلود فایل صوتی جدید
            if (!$error && !empty($_FILES['audio_file']['name'])) {
                $audioErr = $_FILES['audio_file']['error'];
                if ($audioErr === UPLOAD_ERR_OK) {
                    $audioUp = uploadAudio($_FILES['audio_file']);
                    if ($audioUp) {
                        if ($audioPath) {
                            $old = $audioPath;
                            scheduleFileDeletion($old);
                        }
                        $audioPath = $audioUp;
                    } else {
                        $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, ['mp3','ogg','wav','m4a','mp4'], true)) {
                            $error = 'فرمت فایل صوتی پشتیبانی نمی‌شود. فرمت‌های مجاز: MP3، OGG، WAV، M4A';
                        } else {
                            $error = 'خطا در آپلود فایل صوتی. تنظیمات فضای ذخیره‌سازی و نوع فایل را بررسی کنید.';
                        }
                    }
                } elseif ($audioErr !== UPLOAD_ERR_NO_FILE) {
                    $errCodes = [
                        UPLOAD_ERR_INI_SIZE   => 'حجم فایل از حد مجاز php.ini تجاوز کرده (' . ini_get('upload_max_filesize') . ').',
                        UPLOAD_ERR_FORM_SIZE  => 'حجم فایل از حد مجاز فرم تجاوز کرده.',
                        UPLOAD_ERR_PARTIAL    => 'فایل به‌صورت ناقص آپلود شد.',
                        UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت سرور یافت نشد.',
                        UPLOAD_ERR_CANT_WRITE => 'امکان نوشتن فایل وجود ندارد.',
                    ];
                    $error = $errCodes[$audioErr] ?? 'خطا در آپلود فایل صوتی (کد: ' . $audioErr . ')';
                }
            }

            // حذف فایل صوتی
            if (!$error && !empty($_POST['remove_audio'])) {
                if ($audioPath) {
                    $old = $audioPath;
                    scheduleFileDeletion($old);
                }
                $audioPath = '';
            }

            // آپلود ویدیو (اختیاری)
            $videoPath = $lesson['video_file'] ?? '';
            if (!$error && !empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                ensureFeaturedVideoColumn();
                $upV = uploadFeaturedVideo($_FILES['video_file']);
                if ($upV) {
                    if ($videoPath) scheduleFileDeletion($videoPath);
                    $videoPath = $upV;
                } else {
                    $error = 'خطا در آپلود ویدیو. فرمت‌های مجاز: MP4، WebM، MOV (حداکثر 200MB)';
                }
            }
            if (!$error && !empty($_POST['remove_video'])) {
                if ($videoPath) scheduleFileDeletion($videoPath);
                $videoPath = '';
            }

            // آپلود PDF (اختیاری)
            $pdfPath = $lesson['pdf_file'] ?? '';
            if (!$error && !empty($_FILES['pdf_file']['name']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $upPdf = uploadBookFile($_FILES['pdf_file'], 'pdf');
                if ($upPdf) {
                    if ($pdfPath) scheduleFileDeletion($pdfPath);
                    $pdfPath = $upPdf;
                } else {
                    $error = 'خطا در آپلود PDF.';
                }
            }
            if (!$error && !empty($_POST['remove_pdf'])) {
                if ($pdfPath) scheduleFileDeletion($pdfPath);
                $pdfPath = '';
            }

            if (!$error) {
                try {
                    ensureLessonsColumns(); // اطمینان از وجود video_file و pdf_file
                    $level = trim($_POST['level'] ?? '');
                    if (!in_array($level, ['beginner','intermediate','advanced'], true)) $level = null;

                    $slug = uniqueSlug('lessons', $title, $id);
                    $stmt = $db->prepare(
                        "UPDATE lessons SET
                            title=?, slug=?, subject=?, teacher=?, content=?, summary=?,
                            featured_image=?, audio_file=?, video_file=?, pdf_file=?,
                            status=?, page_section=?, level=?, updated_at=NOW()
                         WHERE id=?"
                    );
                    $stmt->execute([
                        $title, $slug, $subject ?: null, $teacher ?: null,
                        $content, $summary ?: null,
                        $featImg    ?: null,
                        $audioPath  ?: null,
                        $videoPath  ?: null,
                        $pdfPath    ?: null,
                        $status, $page_section, $level,
                        $id
                    ]);

                    $stmt2 = $db->prepare("SELECT * FROM lessons WHERE id = ? LIMIT 1");
                    $stmt2->execute([$id]);
                    $lesson = $stmt2->fetch();

                    $_SESSION['flash_msg']  = 'درس با موفقیت بروزرسانی شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/lessons/edit.php?id=' . $id));

                } catch (PDOException $e) {
                    error_log('Lesson edit error: ' . get_class($e));
                    $error = 'خطا در ذخیره تغییرات: ';
                }
            }
        }
    }
}

$currentSections = !empty($lesson['page_section'])
    ? explode(',', $lesson['page_section'])
    : ['home'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-pencil-square ms-2"></i>ویرایش: <?= sanitize(mb_strimwidth($lesson['title'],0,40,'...')) ?></h5>
    <div class="d-flex gap-2">
        <?php if (!empty($lesson['slug'])): ?>
        <a href="<?= siteUrl('lesson.php?slug=' . urlencode($lesson['slug'])) ?>" target="_blank"
           class="btn btn-sm btn-outline-success">
            <i class="bi bi-eye ms-1"></i>مشاهده
        </a>
        <?php endif; ?>
        <a href="<?= siteUrl('admin/lessons/') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-right ms-1"></i>بازگشت
        </a>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_msg'])): ?>
<div class="alert alert-<?= sanitize($_SESSION['flash_type'] ?? 'info') ?> alert-dismissible">
    <?= sanitize($_SESSION['flash_msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); endif; ?>

<div class="alert alert-info small mb-4">
    <i class="bi bi-info-circle ms-2"></i>
    حداکثر حجم آپلود:
    <strong>upload_max_filesize = <?= ini_get('upload_max_filesize') ?></strong> |
    <strong>post_max_size = <?= ini_get('post_max_size') ?></strong>
</div>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int)(20 * 1024 * 1024) ?>">

    <div class="row g-4">
        <!-- ستون اصلی -->
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">اطلاعات درس</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان درس <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= sanitize($lesson['title']) ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">موضوع / درس</label>
                            <input type="text" name="subject" class="form-control"
                                   value="<?= sanitize($lesson['subject'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">نام استاد</label>
                            <input type="text" name="teacher" class="form-control"
                                   value="<?= sanitize($lesson['teacher'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-bold">خلاصه</label>
                        <textarea name="summary" class="form-control" rows="3"><?= sanitize($lesson['summary'] ?? '') ?></textarea>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-bold">توضیحات کامل</label>
                        <textarea name="content" class="form-control" rows="8"><?= htmlspecialchars($lesson['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ─── فایل صوتی ─── -->
            <div class="admin-card mb-4" style="border:2px solid #198754">
                <div class="admin-card-header" style="background:#d1e7dd;color:#0f5132">
                    <i class="bi bi-headphones ms-2"></i>فایل صوتی
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($lesson['audio_file'])): ?>
                    <div class="current-audio-wrap mb-3 p-3 bg-soft rounded-xl">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>فایل صوتی فعلی:</strong>
                            <span class="text-muted small"><?= sanitize(basename($lesson['audio_file'])) ?></span>
                        </div>
                        <audio controls preload="none" class="w-100" style="border-radius:8px">
                            <source src="<?= siteUrl($lesson['audio_file']) ?>" type="audio/mpeg">
                            مرورگر از پخش پشتیبانی نمی‌کند.
                        </audio>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_audio" id="remove_audio" value="1">
                            <label class="form-check-label text-danger small" for="remove_audio">
                                <i class="bi bi-trash ms-1"></i>حذف فایل صوتی فعلی
                            </label>
                        </div>
                    </div>
                    <p class="text-muted small">برای <strong>جایگزینی</strong>، فایل جدید انتخاب کنید:</p>
                    <?php else: ?>
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle ms-2"></i>
                        هنوز فایل صوتی بارگذاری نشده است.
                    </div>
                    <?php endif; ?>

                    <input type="file" name="audio_file" id="audioFileInputEdit"
                           class="form-control"
                           accept="audio/*,.mp3,.ogg,.wav,.m4a"
                           onchange="previewAudioEdit(this)">
                    <div class="form-text mt-1">
                        فرمت‌های مجاز: <strong>MP3، OGG، WAV، M4A</strong> — حداکثر <?= ini_get('upload_max_filesize') ?>
                    </div>

                    <div id="newAudioPreview" class="mt-3" style="display:none">
                        <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                            <i class="bi bi-upload text-primary fs-5"></i>
                            <div>
                                <strong>فایل جدید:</strong>
                                <span id="newAudioName" class="text-muted small ms-1"></span>
                            </div>
                        </div>
                        <audio id="newAudioPlayer" controls class="w-100" style="border-radius:8px"></audio>
                    </div>
                </div>
            </div>

            <!-- تصویر شاخص -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">تصویر شاخص <span class="text-muted fw-normal small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <?php if (!empty($lesson['featured_image'])): ?>
                    <div class="mb-3">
                        <img src="<?= imgUrl($lesson['featured_image']) ?>"
                             style="max-width:280px;max-height:180px;border-radius:8px;border:1px solid #eee" alt="">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                            <label class="form-check-label text-danger small" for="remove_image">حذف تصویر شاخص</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control"
                           accept="image/*" onchange="previewImg(this,'newFeatImg')">
                    <div class="form-text">برای تغییر تصویر، فایل جدید انتخاب کنید</div>
                    <img id="newFeatImg" src="" style="display:none;max-width:300px;max-height:200px;border-radius:8px;margin-top:8px" alt="">
                </div>
            </div>

            <!-- ویدیو (اختیاری) -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-camera-video ms-2"></i>ویدیو درس <span class="text-muted fw-normal small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <?php if (!empty($lesson['video_file'])): ?>
                    <div class="mb-3">
                        <video src="<?= siteUrl($lesson['video_file']) ?>" controls style="max-width:100%;max-height:180px;border-radius:8px;background:#000"></video>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_video" id="remove_video" value="1">
                            <label class="form-check-label text-danger small" for="remove_video">حذف ویدیو</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="video_file" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv">
                    <div class="form-text">MP4، WebM، MOV — حداکثر 200MB</div>
                </div>
            </div>

            <!-- PDF (اختیاری) -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-file-earmark-pdf ms-2 text-danger"></i>فایل PDF درس <span class="text-muted fw-normal small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <?php if (!empty($lesson['pdf_file'])): ?>
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                        <a href="<?= siteUrl($lesson['pdf_file']) ?>" target="_blank" class="text-decoration-none small">
                            <?= sanitize(basename($lesson['pdf_file'])) ?>
                        </a>
                        <div class="form-check ms-3">
                            <input class="form-check-input" type="checkbox" name="remove_pdf" id="remove_pdf" value="1">
                            <label class="form-check-label text-danger small" for="remove_pdf">حذف PDF</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf">
                    <div class="form-text">فقط فرمت PDF</div>
                </div>
            </div>
        </div>

        <!-- ستون جانبی -->
        <div class="col-lg-4">
            <div class="admin-card mb-3">
                <div class="admin-card-header">انتشار</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">وضعیت</label>
                        <select name="status" class="form-select">
                            <option value="draft"     <?= $lesson['status']==='draft'?'selected':'' ?>>پیش‌نویس</option>
                            <option value="published" <?= $lesson['status']==='published'?'selected':'' ?>>منتشرشده</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save ms-1"></i>ذخیره تغییرات
                        </button>
                        <a href="<?= siteUrl('admin/lessons/delete.php?id=' . $id) ?>"
                           class="btn btn-outline-danger"
                           onclick="return confirm('آیا از حذف این درس اطمینان دارید؟')">
                            <i class="bi bi-trash ms-1"></i>حذف درس
                        </a>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-3">
                <div class="admin-card-header">اطلاعات</div>
                <div class="admin-card-body">
                    <div class="d-flex justify-content-between small mb-2">
                        <span>بازدید:</span>
                        <strong><?= number_format($lesson['views'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span>ایجاد:</span>
                        <strong><?= persianDate($lesson['created_at']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>آخرین ویرایش:</span>
                        <strong><?= persianDate($lesson['updated_at']) ?></strong>
                    </div>
                    <?php if ($lesson['slug']): ?>
                    <hr class="my-2">
                    <div class="small">
                        <strong>اسلاگ:</strong><br>
                        <code style="font-size:.72rem;word-break:break-all"><?= sanitize($lesson['slug']) ?></code>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-card mb-3">
                <div class="admin-card-header">سطح درس</div>
                <div class="admin-card-body">
                    <?php $curLevel = $lesson['level'] ?? ''; ?>
                    <select name="level" class="form-select">
                        <option value="">— سطح مشخص نیست —</option>
                        <option value="beginner"     <?= $curLevel==='beginner'    ?'selected':'' ?>>مقدماتی</option>
                        <option value="intermediate" <?= $curLevel==='intermediate'?'selected':'' ?>>متوسط</option>
                        <option value="advanced"     <?= $curLevel==='advanced'    ?'selected':'' ?>>پیشرفته</option>
                    </select>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">نمایش در صفحات</div>
                <div class="admin-card-body">
                    <?php
                    $sectionOpts = [
                        'home'    => '🏠 صفحه اصلی',
                        'lessons' => '📚 صفحه درس‌ها',
                        'other'   => '📋 سایر',
                    ];
                    foreach ($sectionOpts as $val => $label):
                        $checked = in_array($val, $currentSections) ? 'checked' : '';
                    ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox"
                               name="page_section[]" id="esec_<?= $val ?>"
                               value="<?= $val ?>" <?= $checked ?>>
                        <label class="form-check-label small" for="esec_<?= $val ?>"><?= $label ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function previewAudioEdit(input) {
    var wrap   = document.getElementById('newAudioPreview');
    var audio  = document.getElementById('newAudioPlayer');
    var nameEl = document.getElementById('newAudioName');
    if (!input.files || !input.files[0]) { if (wrap) wrap.style.display = 'none'; return; }
    var file = input.files[0];
    var url  = URL.createObjectURL(file);
    if (nameEl) nameEl.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
    if (audio) { audio.src = url; audio.onloadedmetadata = function () { URL.revokeObjectURL(url); }; }
    if (wrap) wrap.style.display = 'block';
}

function previewImg(input, previewId) {
    var prev = document.getElementById(previewId);
    if (!input.files || !input.files[0] || !prev) return;
    var url = URL.createObjectURL(input.files[0]);
    prev.src = url; prev.style.display = 'block';
    prev.onload = function () { URL.revokeObjectURL(url); };
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
