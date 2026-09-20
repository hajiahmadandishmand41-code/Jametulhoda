<?php
/**
 * admin/posts/create.php — ایجاد مطلب جدید + تولید خودکار Thumbnail از ویدیو
 */
$adminTitle = 'مطلب جدید';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/media.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی. لطفاً صفحه را رفرش کنید.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $summary     = trim($_POST['summary']     ?? '');
        $content     = $_POST['content']          ?? '';
        $post_type   = in_array($_POST['post_type'] ?? '', ['news','article','announcement','speech','program','religious'])
                       ? $_POST['post_type'] : 'news';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status      = in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft';
        $is_featured = (int)!empty($_POST['is_featured']);
        $pub_date    = !empty($_POST['published_at'])
                       ? date('Y-m-d H:i:s', strtotime($_POST['published_at']))
                       : date('Y-m-d H:i:s');

        // بخش‌های نمایش
        $sections = $_POST['page_section'] ?? [];
        if (!is_array($sections)) $sections = [$sections];
        $page_section = implode(',', array_filter(array_map('trim', $sections)));
        if (!$page_section) $page_section = 'other';

        if (!$title) {
            $error = 'عنوان مطلب الزامی است.';
        } else {
            $admin2  = currentAdmin();
            $slug    = uniqueSlug('posts', $title);
            $featImg = '';
            $featVid = '';

            // آپلود تصویر شاخص دستی
            if (!empty($_FILES['featured_image']['name'])) {
                $featImg = uploadImage($_FILES['featured_image'], 'posts');
                if (!$featImg) $error = 'خطا در آپلود تصویر شاخص. فرمت‌های مجاز: JPG، PNG، GIF، WebP';
            }

            // تولید خودکار Thumbnail از ویدیو (اگر تصویر شاخص آپلود نشد)
            if (!$featImg && !empty($_POST['auto_thumbnail'])) {
                $thumbPath = saveBase64Thumbnail($_POST['auto_thumbnail'], 'posts');
                if ($thumbPath) $featImg = $thumbPath;
            }

            // آپلود ویدیو شاخص (مستقل از فایل‌های ویدیویی گالری)
            if (!$error && !empty($_FILES['featured_video']['name'])) {
                $featVid = uploadFeaturedVideo($_FILES['featured_video']);
                if (!$featVid) $error = 'خطا در آپلود ویدیو شاخص. فرمت‌های مجاز: MP4، WebM، MOV، MKV (حداکثر 200MB)';
            }

            if (!$error) {
                $db   = getDB();
                ensureFeaturedVideoColumn();
                $stmt = $db->prepare(
                    "INSERT INTO posts (title, slug, summary, content, featured_image, featured_video, post_type, page_section, category_id, author_id, status, is_featured, published_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) RETURNING id"
                );
                $stmt->execute([
                    $title, $slug, $summary, $content, $featImg, $featVid,
                    $post_type, $page_section,
                    $category_id ?: null,
                    $admin2['id'],
                    $status, $is_featured, $pub_date
                ]);
                $postId = (int)$stmt->fetchColumn();

                // تصاویر اضافی
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['name'] as $k => $name) {
                        if ($name && $_FILES['images']['error'][$k] === UPLOAD_ERR_OK) {
                            $file = [
                                'name'     => $name,
                                'type'     => $_FILES['images']['type'][$k],
                                'tmp_name' => $_FILES['images']['tmp_name'][$k],
                                'error'    => $_FILES['images']['error'][$k],
                                'size'     => $_FILES['images']['size'][$k],
                            ];
                            $imgPath = uploadImage($file, 'posts');
                            if ($imgPath) {
                                $db->prepare("INSERT INTO post_images (post_id, image_path, created_at) VALUES (?, ?, NOW())")
                                   ->execute([$postId, $imgPath]);
                            }
                        }
                    }
                }

                // آپلود چند فایل صوتی/ویدیویی
                if (!empty($_FILES['audio_files']['name'][0])) {
                    handleMediaUploads('post', $postId, $_FILES['audio_files'], 'audio');
                }
                if (!empty($_FILES['video_files']['name'][0])) {
                    handleMediaUploads('post', $postId, $_FILES['video_files'], 'video');
                }

                $_SESSION['flash_msg']  = 'مطلب با موفقیت ذخیره شد.';
                $_SESSION['flash_type'] = 'success';
                redirect(siteUrl('admin/posts/edit.php?id=' . $postId));
            }
        }
    }
}

$categories      = getCategories();
$selectedSections = is_array($_POST['page_section'] ?? null)
                   ? $_POST['page_section']
                   : explode(',', $_POST['page_section'] ?? 'home,news');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-plus-circle ms-2"></i>مطلب جدید</h5>
    <a href="<?= siteUrl('admin/posts/') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right ms-1"></i>بازگشت</a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form" id="createPostForm">
    <?= csrfField() ?>
    <!-- فیلد مخفی برای Thumbnail خودکار از ویدیو -->
    <input type="hidden" name="auto_thumbnail" id="autoThumbnailData">

    <div class="row g-4">
        <!-- ستون اصلی -->
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">محتوای مطلب</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان مطلب <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= sanitize($_POST['title'] ?? '') ?>" required placeholder="عنوان مطلب را بنویسید">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">خلاصه / چکیده</label>
                        <textarea name="summary" class="form-control" rows="3" placeholder="خلاصه کوتاه مطلب..."><?= sanitize($_POST['summary'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن کامل مطلب</label>
                        <textarea name="content" class="form-control" rows="12" placeholder="متن کامل مطلب را بنویسید..."><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- تصویر شاخص -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">تصویر شاخص</div>
                <div class="admin-card-body">
                    <input type="file" name="featured_image" id="featuredImageInput" class="form-control" accept="image/*" onchange="previewImg(this,'featPreview')">
                    <div class="form-text mb-2">فرمت‌های مجاز: JPG، PNG، GIF، WebP — حداکثر 20MB</div>
                    <img id="featPreview" src="" style="display:none;max-width:300px;max-height:200px;border-radius:8px;margin-top:8px" alt="">
                    <div id="autoThumbNotice" class="alert alert-info mt-2 py-2 small" style="display:none">
                        <i class="bi bi-magic ms-1"></i> تصویر بندانگشتی به‌صورت خودکار از ویدیو تولید شد.
                    </div>
                </div>
            </div>

            <!-- ویدیو شاخص (جدا از گالری) -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-camera-video-fill ms-2 text-danger"></i>ویدیو شاخص (اختیاری)</div>
                <div class="admin-card-body">
                    <input type="file" name="featured_video" id="featuredVideoInput" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv">
                    <div class="form-text mb-2">این ویدیو مستقل از فایل‌های ویدیویی گالری است و در ابتدای مطلب نمایش داده می‌شود. حداکثر 200MB — MP4، WebM، MOV، MKV</div>
                    <video id="featVideoPreview" controls style="display:none;max-width:100%;max-height:240px;border-radius:8px;margin-top:8px;background:#000"></video>
                </div>
            </div>

            <!-- تصاویر اضافی -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">تصاویر اضافی (اختیاری)</div>
                <div class="admin-card-body">
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                    <div class="form-text">می‌توانید چند تصویر را همزمان انتخاب کنید</div>
                </div>
            </div>

            <!-- فایل‌های صوتی چندگانه -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-mic-fill ms-2"></i>فایل‌های صوتی (اختیاری — برای سخنرانی‌ها)</div>
                <div class="admin-card-body">
                    <input type="file" name="audio_files[]" class="form-control" accept="audio/*,.mp3,.ogg,.wav,.m4a" multiple>
                    <div class="form-text">MP3، OGG، WAV، M4A — تا 20MB برای هر فایل. برای Playlist می‌توانید چند فایل انتخاب کنید.</div>
                </div>
            </div>

            <!-- فایل‌های ویدیویی چندگانه -->
            <div class="admin-card">
                <div class="admin-card-header"><i class="bi bi-camera-video-fill ms-2"></i>فایل‌های ویدیویی (اختیاری)</div>
                <div class="admin-card-body">
                    <input type="file" name="video_files[]" id="videoFilesInput" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv" multiple>
                    <div class="form-text">MP4، WebM، MOV، MKV — تا 200MB برای هر فایل.</div>
                    <div id="videoThumbProgress" class="mt-2" style="display:none">
                        <div class="d-flex align-items-center gap-2 text-muted small">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span>در حال تولید تصویر بندانگشتی از ویدیو...</span>
                        </div>
                    </div>
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
                    <div class="mb-3">
                        <label class="form-label">تاریخ انتشار</label>
                        <input type="datetime-local" name="published_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= !empty($_POST['is_featured'])?'checked':'' ?>>
                        <label class="form-check-label" for="is_featured">نمایش در اسلایدر (برجسته)</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success"><i class="bi bi-send ms-1"></i>ذخیره مطلب</button>
                    </div>
                </div>
            </div>

            <!-- نوع و دسته -->
            <div class="admin-card mb-3">
                <div class="admin-card-header">نوع و دسته‌بندی</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">نوع مطلب</label>
                        <select name="post_type" class="form-select">
                            <option value="news"         <?= ($_POST['post_type']??'news')==='news'?'selected':'' ?>>📰 خبر</option>
                            <option value="article"      <?= ($_POST['post_type']??'')==='article'?'selected':'' ?>>📄 مقاله</option>
                            <option value="announcement" <?= ($_POST['post_type']??'')==='announcement'?'selected':'' ?>>📢 اطلاعیه</option>
                            <option value="speech"       <?= ($_POST['post_type']??'')==='speech'?'selected':'' ?>>🎤 سخنرانی</option>
                            <option value="program"      <?= ($_POST['post_type']??'')==='program'?'selected':'' ?>>📅 برنامه آموزشی</option>
                            <option value="religious"    <?= ($_POST['post_type']??'')==='religious'?'selected':'' ?>>⭐ فعالیت مذهبی</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">دسته‌بندی</label>
                        <select name="category_id" class="form-select">
                            <option value="">— بدون دسته —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id']??'')==$cat['id']?'selected':'' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- بخش‌های نمایش -->
            <div class="admin-card">
                <div class="admin-card-header"><i class="bi bi-layout-text-window ms-2"></i>نمایش در کدام بخش‌ها؟</div>
                <div class="admin-card-body">
                    <p class="text-muted small mb-3">این مطلب در کدام صفحات نمایش داده شود؟</p>
                    <?php
                    $sectionOpts = [
                        'home'          => '🏠 صفحه اصلی',
                        'news'          => '📰 صفحه اخبار',
                        'articles'      => '📄 مقالات',
                        'announcements' => '📢 اطلاعیه‌ها',
                        'speeches'      => '🎤 سخنرانی‌ها',
                        'programs'      => '📅 برنامه‌ها',
                        'religious'     => '⭐ فعالیت مذهبی',
                        'other'         => '📋 سایر صفحات',
                    ];
                    foreach ($sectionOpts as $val => $label):
                        $checked = in_array($val, $selectedSections) ? 'checked' : '';
                    ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="page_section[]" id="sec_<?= $val ?>" value="<?= $val ?>" <?= $checked ?>>
                        <label class="form-check-label small" for="sec_<?= $val ?>"><?= $label ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
/**
 * تولید خودکار Thumbnail از اولین فریم ویدیو
 * وقتی ادمین ویدیو انتخاب می‌کند و تصویر شاخصی انتخاب نشده،
 * فریم اول ویدیو به‌صورت canvas گرفته می‌شود و در فیلد مخفی ذخیره می‌شود.
 */
(function() {
    var videoInput     = document.getElementById('videoFilesInput');
    var imageInput     = document.getElementById('featuredImageInput');
    var thumbDataInput = document.getElementById('autoThumbnailData');
    var featPreview    = document.getElementById('featPreview');
    var thumbNotice    = document.getElementById('autoThumbNotice');
    var thumbProgress  = document.getElementById('videoThumbProgress');

    if (!videoInput) return;

    videoInput.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;

        // اگر تصویر شاخص دستی انتخاب شده، نیازی به تولید خودکار نیست
        if (imageInput && imageInput.files.length > 0) return;
        if (thumbDataInput && thumbDataInput.value) return;

        // نمایش progress
        if (thumbProgress) thumbProgress.style.display = 'block';
        if (thumbNotice)   thumbNotice.style.display   = 'none';

        var objectUrl = URL.createObjectURL(file);
        var videoEl   = document.createElement('video');
        videoEl.muted    = true;
        videoEl.preload  = 'metadata';
        videoEl.playsInline = true;
        videoEl.style.display = 'none';

        videoEl.addEventListener('loadedmetadata', function() {
            // Seek به ثانیه ۱ (یا ۱۰٪ طول ویدیو، هر کدام کوچک‌تر)
            var seekTime = Math.min(1, videoEl.duration * 0.1);
            videoEl.currentTime = seekTime;
        });

        videoEl.addEventListener('seeked', function() {
            var w = videoEl.videoWidth  || 640;
            var h = videoEl.videoHeight || 360;

            // حداکثر 1280x720 برای بهینه‌سازی حجم
            var maxW = 1280;
            if (w > maxW) { h = Math.round(h * maxW / w); w = maxW; }

            var canvas = document.createElement('canvas');
            canvas.width  = w;
            canvas.height = h;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(videoEl, 0, 0, w, h);

            var dataUrl = canvas.toDataURL('image/jpeg', 0.82);

            // ذخیره در فیلد مخفی
            if (thumbDataInput) thumbDataInput.value = dataUrl;

            // نمایش پیش‌نمایش
            if (featPreview) {
                featPreview.src           = dataUrl;
                featPreview.style.display = 'block';
            }
            if (thumbProgress) thumbProgress.style.display = 'none';
            if (thumbNotice)   thumbNotice.style.display   = 'flex';

            // آزاد کردن حافظه
            URL.revokeObjectURL(objectUrl);
            videoEl.remove();
        });

        videoEl.addEventListener('error', function() {
            // در صورت خطا، بدون thumbnail ادامه بده
            if (thumbProgress) thumbProgress.style.display = 'none';
            URL.revokeObjectURL(objectUrl);
            videoEl.remove();
        });

        document.body.appendChild(videoEl);
        videoEl.src = objectUrl;
    });

    // اگر تصویر دستی انتخاب شد، thumbnail خودکار را پاک کن
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                if (thumbDataInput) thumbDataInput.value = '';
                if (thumbNotice)    thumbNotice.style.display = 'none';
            }
        });
    }

    // پیش‌نمایش ویدیو شاخص
    var featVidInput = document.getElementById('featuredVideoInput');
    var featVidPrev  = document.getElementById('featVideoPreview');
    if (featVidInput && featVidPrev) {
        featVidInput.addEventListener('change', function() {
            var f = this.files[0];
            if (!f) {
                featVidPrev.style.display = 'none';
                featVidPrev.removeAttribute('src');
                return;
            }
            var u = URL.createObjectURL(f);
            featVidPrev.src           = u;
            featVidPrev.style.display = 'block';
            featVidPrev.onload        = function() { URL.revokeObjectURL(u); };
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
