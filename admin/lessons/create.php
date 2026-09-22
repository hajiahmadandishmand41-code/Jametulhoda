<?php
/**
 * admin/lessons/create.php — ایجاد درس جدید با فایل صوتی
 * اصلاح‌شده: فقط فایل صوتی، Migration ستون‌های lessons، آپلود بدون خطا
 */
$adminTitle = 'درس جدید';
require_once __DIR__ . '/../includes/header.php';

// Migration: اطمینان از وجود ستون‌های summary و page_section در جدول lessons
ensureLessonsColumns();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    beginContentUploadScope();
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title   = trim($_POST['title']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $teacher = trim($_POST['teacher'] ?? '');
        $sources = trim($_POST['sources'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = $_POST['content']      ?? '';
        $status  = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';

        // بخش‌های نمایش
        $sections = $_POST['page_section'] ?? ['home'];
        if (!is_array($sections)) $sections = [$sections];
        $page_section = implode(',', array_filter(array_map('trim', $sections)));
        if (!$page_section) $page_section = 'home';
        $collection_id = (int)($_POST['collection_id'] ?? 0) ?: null;
        $volume_id     = (int)($_POST['volume_id'] ?? 0) ?: null;
        $lesson_number = (int)($_POST['lesson_number'] ?? 0) ?: null;
        $topicIds      = array_filter(array_map('intval', (array)($_POST['topic_ids'] ?? [])));

        if (!$title) {
            $error = 'عنوان درس الزامی است.';
        } else {
            $db        = getDB();
            $featImg   = '';
            $audioPath = '';

            // آپلود تصویر شاخص
            if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $up = uploadImage($_FILES['featured_image'], 'lessons');
                if ($up) {
                    $featImg = $up;
                } else {
                    $error = 'خطا در آپلود تصویر. فرمت‌های مجاز: JPG، PNG، GIF، WebP (حداکثر 20MB)';
                }
            }

            // آپلود فایل صوتی — بخش اصلی
            if (!$error && !empty($_FILES['audio_file']['name'])) {
                $audioErr = $_FILES['audio_file']['error'];
                if ($audioErr === UPLOAD_ERR_OK) {
                    $audioUp = uploadAudio($_FILES['audio_file']);
                    if ($audioUp) {
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
                        UPLOAD_ERR_PARTIAL    => 'فایل به‌صورت ناقص آپلود شد. اتصال اینترنت را بررسی کنید.',
                        UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت سرور یافت نشد. با پشتیبانی هاست تماس بگیرید.',
                        UPLOAD_ERR_CANT_WRITE => 'امکان نوشتن فایل وجود ندارد. دسترسی‌های پوشه uploads را بررسی کنید.',
                    ];
                    $error = $errCodes[$audioErr] ?? 'خطا در آپلود فایل صوتی (کد خطا: ' . $audioErr . ')';
                }
            }

            if (!$error) {
                try {
                    // اطمینان از وجود جدول lessons با همه ستون‌های لازم
                    /* Schema installed by CLI migration. */

                    // Migration مجدد اطمینان
                    ensureLessonsColumns();

                    $level = trim($_POST['level'] ?? '');
                    if (!in_array($level, ['beginner','intermediate','advanced'], true)) $level = null;

                    // آپلود ویدیو (اختیاری)
                    $videoPath = '';
                    if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                        $upV = uploadFeaturedVideo($_FILES['video_file']);
                        if ($upV) $videoPath = $upV;
                        else $error = 'خطا در آپلود ویدیو. فرمت‌های مجاز: MP4، WebM، MOV (حداکثر 200MB)';
                    }

                    // آپلود PDF (اختیاری)
                    $pdfPath = '';
                    if (!$error && !empty($_FILES['pdf_file']['name']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                        $upPdf = uploadBookFile($_FILES['pdf_file'], 'pdf');
                        if ($upPdf) $pdfPath = $upPdf;
                        else $error = 'خطا در آپلود PDF.';
                    }

                    if (!$error) {
                    $slug = uniqueSlug('lessons', $title);
                    $stmt = $db->prepare(
                        "INSERT INTO lessons (title, slug, subject, teacher, content, summary, sources, featured_image, audio_file, video_file, pdf_file, status, page_section, level, collection_id, volume_id, lesson_number, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) RETURNING id"
                    );
                    $stmt->execute([
                        $title, $slug, $subject ?: null, $teacher ?: null,
                        $content, $summary ?: null, $sources ?: null, $featImg ?: null, $audioPath ?: null,
                        $videoPath ?: null, $pdfPath ?: null,
                        $status, $page_section, $level, $collection_id, $volume_id, $lesson_number
                    ]);
                    $newId = (int)$stmt->fetchColumn();
                    if($topicIds){ $ins=$db->prepare("INSERT INTO lesson_topics (lesson_id, topic_id) VALUES (?,?) ON CONFLICT DO NOTHING"); foreach($topicIds as $tid) $ins->execute([$newId,$tid]); }

                    $_SESSION['flash_msg']  = 'درس با موفقیت ذخیره شد.';
                    $_SESSION['flash_type'] = 'success';
                    redirect(siteUrl('admin/lessons/edit?id=' . $newId));

                    } // end if(!$error) for video/pdf
                } catch (PDOException $e) {
                    error_log('Lesson create error: ' . get_class($e));
                    $error = 'خطا در ذخیره درس: ';
                }
            }
        }
    }
}

$selectedSections = is_array($_POST['page_section'] ?? null)
    ? $_POST['page_section']
    : ['home'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-plus-circle ms-2"></i>درس جدید</h5>
    <a href="<?= siteUrl('admin/lessons/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right ms-1"></i>بازگشت
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
<?php endif; ?>

<div class="alert alert-info small mb-4">
    <i class="bi bi-info-circle ms-2"></i>
    حداکثر حجم آپلود سرور:
    <strong>upload_max_filesize = <?= ini_get('upload_max_filesize') ?></strong>،
    <strong>post_max_size = <?= ini_get('post_max_size') ?></strong>،
    <strong>max_execution_time = <?= ini_get('max_execution_time') ?>s</strong>
</div>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int)(20 * 1024 * 1024) ?>">

    <div class="row g-4">
        <!-- ستون اصلی -->
        <div class="col-lg-8">
            <!-- اطلاعات اصلی -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">اطلاعات درس</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان درس <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= sanitize($_POST['title'] ?? '') ?>"
                               required placeholder="عنوان کامل درس">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">موضوع / درس</label>
                            <input type="text" name="subject" class="form-control"
                                   value="<?= sanitize($_POST['subject'] ?? '') ?>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4"><label class="form-label">مجموعه (Collection)</label>
                            <select name="collection_id" class="form-select">
                                <option value="">— بدون مجموعه —</option>
                                <?php foreach(getLessonCollections(['active'=>null]) as $cc): ?>
                                <option value="<?= $cc['id'] ?>" <?= (($_POST['collection_id']??'')==$cc['id'])?'selected':'' ?>><?= sanitize($cc['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">جلد / بخش (Volume)</label>
                            <select name="volume_id" class="form-select">
                                <option value="">— بدون جلد —</option>
                                <?php foreach(getLessonCollections(['active'=>null]) as $cc2): foreach(getLessonVolumes((int)$cc2['id']) as $vv): ?>
                                <option value="<?= $vv['id'] ?>" <?= (($_POST['volume_id']??'')==$vv['id'])?'selected':'' ?>><?= sanitize($cc2['title'].' — '.$vv['title']) ?></option>
                                <?php endforeach; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">شماره درس</label><input type="number" name="lesson_number" class="form-control" value="<?= sanitize($_POST['lesson_number'] ?? '') ?>" placeholder="مثلاً ۱۲"></div>
                    </div>
                    <?php $allTopics=getTopics(['limit'=>200]); ?>
                    <div class="mt-3"><label class="form-label fw-bold"><i class="bi bi-diagram-3 ms-1"></i> موضوعات مرتبط</label>
                    <div style="max-height:160px;overflow:auto;border:1px solid #e8e6dc;border-radius:10px;padding:10px;background:#fafaf7">
                        <?php if(empty($allTopics)): ?><div class="text-muted small">موضوعی نیست.</div><?php else: foreach($allTopics as $tt): ?>
                        <label class="form-check"><input type="checkbox" class="form-check-input" name="topic_ids[]" value="<?= $tt['id'] ?>"> <span class="form-check-label small"><?= sanitize($tt['name']) ?></span></label>
                        <?php endforeach; endif; ?>
                    </div></div>
"
                                   placeholder="مثلاً: فقه، اصول، تفسیر...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">نام استاد</label>
                            <input type="text" name="teacher" class="form-control"
                                   value="<?= sanitize($_POST['teacher'] ?? '') ?>"
                                   placeholder="نام استاد">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-bold">خلاصه</label>
                        <textarea name="summary" class="form-control" rows="3"
                                  placeholder="توضیح کوتاه درباره این درس..."><?= sanitize($_POST['summary'] ?? '') ?></textarea>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-bold">توضیحات کامل (اختیاری)</label>
                        <textarea name="content" class="form-control" rows="8"
                                  placeholder="متن کامل جلسه درس..."><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea><div class="mt-3"><label class="form-label">منابع درس</label><textarea name="sources" class="form-control" rows="3" placeholder="منابع..."><?= sanitize($_POST['sources'] ?? '') ?></textarea></div>
                    </div>
                </div>
            </div>

            <!-- ─── فایل صوتی ─── -->
            <div class="admin-card mb-4" style="border: 2px solid #198754;">
                <div class="admin-card-header" style="background:#d1e7dd;color:#0f5132">
                    <i class="bi bi-headphones ms-2"></i>فایل صوتی
                    <span class="badge bg-success ms-2">اصلی</span>
                </div>
                <div class="admin-card-body">
                    <div id="audioDropZone" class="upload-drop-zone mb-3"
                         style="border:2px dashed #198754;border-radius:12px;padding:30px;text-align:center;cursor:pointer;transition:all .2s;background:#f8fff9">
                        <i class="bi bi-mic-fill text-success" style="font-size:2.5rem"></i>
                        <p class="mt-2 mb-1 fw-bold">فایل صوتی را اینجا رها کنید یا کلیک کنید</p>
                        <p class="text-muted small mb-0">فرمت‌های مجاز: MP3، OGG، WAV، M4A</p>
                        <p class="text-muted small">حداکثر حجم: <?= ini_get('upload_max_filesize') ?></p>
                    </div>
                    <input type="file" name="audio_file" id="audioFileInput"
                           class="form-control"
                           accept="audio/*,.mp3,.ogg,.wav,.m4a"
                           onchange="previewAudio(this)">
                    <div class="form-text mt-2">
                        <i class="bi bi-info-circle ms-1"></i>
                        فرمت‌های پشتیبانی‌شده: <strong>MP3، OGG، WAV، M4A</strong>
                    </div>

                    <!-- پیش‌نمایش صوت -->
                    <div id="audioPreviewWrap" class="mt-3" style="display:none">
                        <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div>
                                <strong id="audioFileName">—</strong>
                                <span id="audioFileSize" class="text-muted small ms-2"></span>
                            </div>
                        </div>
                        <audio id="audioPreview" controls class="w-100" style="border-radius:8px"></audio>
                    </div>
                </div>
            </div>

            <!-- تصویر شاخص -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">تصویر شاخص <span class="text-muted fw-normal small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <input type="file" name="featured_image" class="form-control"
                           accept="image/*" onchange="previewImg(this,'featImgPrev')">
                    <div class="form-text">JPG، PNG، GIF، WebP — حداکثر 20MB</div>
                    <img id="featImgPrev" src="" style="display:none;max-width:300px;max-height:200px;border-radius:8px;margin-top:8px" alt="">
                </div>
            </div>

            <!-- ویدیو (اختیاری) -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-camera-video ms-2"></i>ویدیو درس <span class="text-muted fw-normal small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <input type="file" name="video_file" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv">
                    <div class="form-text">MP4، WebM، MOV — حداکثر 200MB</div>
                </div>
            </div>

            <!-- PDF (اختیاری) -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-file-earmark-pdf ms-2 text-danger"></i>فایل PDF درس <span class="text-muted fw-normal small">(اختیاری)</span></div>
                <div class="admin-card-body">
                    <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf">
                    <div class="form-text">فقط فرمت PDF</div>
                </div>
            </div>
        </div>

        <!-- ستون جانبی -->
        <div class="col-lg-4">
            <!-- انتشار -->
            <div class="admin-card mb-3">
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
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-send ms-1"></i>ذخیره درس
                        </button>
                    </div>
                </div>
            </div>

            <!-- سطح درس -->
            <div class="admin-card mb-3">
                <div class="admin-card-header">سطح درس</div>
                <div class="admin-card-body">
                    <select name="level" class="form-select">
                        <option value="">— سطح مشخص نیست —</option>
                        <option value="beginner"     <?= ($_POST['level']??'')==='beginner'    ?'selected':'' ?>>مقدماتی</option>
                        <option value="intermediate" <?= ($_POST['level']??'')==='intermediate'?'selected':'' ?>>متوسط</option>
                        <option value="advanced"     <?= ($_POST['level']??'')==='advanced'    ?'selected':'' ?>>پیشرفته</option>
                    </select>
                </div>
            </div>

            <!-- بخش نمایش -->
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
                        $checked = in_array($val, $selectedSections) ? 'checked' : '';
                    ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox"
                               name="page_section[]" id="sec_<?= $val ?>"
                               value="<?= $val ?>" <?= $checked ?>>
                        <label class="form-check-label small" for="sec_<?= $val ?>"><?= $label ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// پیش‌نمایش فایل صوتی
function previewAudio(input) {
    var wrap   = document.getElementById('audioPreviewWrap');
    var audio  = document.getElementById('audioPreview');
    var nameEl = document.getElementById('audioFileName');
    var sizeEl = document.getElementById('audioFileSize');

    if (!input.files || !input.files[0]) {
        if (wrap) wrap.style.display = 'none';
        return;
    }
    var file = input.files[0];
    var url  = URL.createObjectURL(file);

    if (nameEl) nameEl.textContent = file.name;
    if (sizeEl) sizeEl.textContent = '(' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
    if (audio) {
        audio.src = url;
        audio.onloadedmetadata = function () { URL.revokeObjectURL(url); };
    }
    if (wrap) wrap.style.display = 'block';
}

// Drag & Drop
(function () {
    var dropZone  = document.getElementById('audioDropZone');
    var fileInput = document.getElementById('audioFileInput');
    if (!dropZone || !fileInput) return;

    dropZone.addEventListener('click', function () { fileInput.click(); });
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.style.background = '#d1e7dd';
    });
    dropZone.addEventListener('dragleave', function () {
        dropZone.style.background = '#f8fff9';
    });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.style.background = '#f8fff9';
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            previewAudio(fileInput);
        }
    });
})();

function previewImg(input, previewId) {
    var prev = document.getElementById(previewId);
    if (!input.files || !input.files[0] || !prev) return;
    var url = URL.createObjectURL(input.files[0]);
    prev.src = url;
    prev.style.display = 'block';
    prev.onload = function () { URL.revokeObjectURL(url); };
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
