<?php
/**
 * admin/posts/edit.php — ویرایش مطلب + تولید خودکار Thumbnail از ویدیو
 */
$adminTitle = 'ویرایش مطلب';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/media.php';

$id   = (int)($_GET['id'] ?? 0);
$post = $id ? getPost($id) : null;
if (!$post) {
    $_SESSION['flash_msg']  = 'مطلب یافت نشد.';
    $_SESSION['flash_type'] = 'danger';
    redirect(siteUrl('admin/posts/'));
}

$db         = getDB();
$error      = '';
$categories = getCategories();
$imgStmt    = $db->prepare("SELECT * FROM post_images WHERE post_id = ?");
$imgStmt->execute([$id]);
$postImages = $imgStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'خطای امنیتی.';
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
                       : ($post['published_at'] ?? date('Y-m-d H:i:s'));

        // بخش‌های نمایش
        $sections = $_POST['page_section'] ?? [];
        if (!is_array($sections)) $sections = explode(',', $sections);
        $page_section = implode(',', array_filter(array_map('trim', $sections)));
        if (!$page_section) $page_section = 'other';

        if (!$title) {
            $error = 'عنوان مطلب الزامی است.';
        } else {
            $slug    = uniqueSlug('posts', $title, $id);
            $featImg = $post['featured_image'];
            $featVid = $post['featured_video'] ?? '';

            // آپلود تصویر شاخص جدید
            if (!empty($_FILES['featured_image']['name'])) {
                $up = uploadImage($_FILES['featured_image'], 'posts');
                if ($up) {
                    // حذف تصویر قدیمی
                    if ($featImg && file_exists(__DIR__ . '/../../' . $featImg)) @unlink(__DIR__ . '/../../' . $featImg);
                    $featImg = $up;
                } else {
                    $error = 'خطا در آپلود تصویر شاخص.';
                }
            }

            // حذف تصویر شاخص
            if (!$error && !empty($_POST['remove_featured'])) {
                if ($featImg && file_exists(__DIR__ . '/../../' . $featImg)) @unlink(__DIR__ . '/../../' . $featImg);
                $featImg = '';
            }

            // تولید خودکار Thumbnail از ویدیو (اگر هیچ تصویر شاخصی وجود ندارد)
            if (!$error && !$featImg && !empty($_POST['auto_thumbnail'])) {
                $thumbPath = saveBase64Thumbnail($_POST['auto_thumbnail'], 'posts');
                if ($thumbPath) $featImg = $thumbPath;
            }

            // آپلود ویدیو شاخص جدید
            if (!$error && !empty($_FILES['featured_video']['name'])) {
                $upV = uploadFeaturedVideo($_FILES['featured_video']);
                if ($upV) {
                    if ($featVid && file_exists(__DIR__ . '/../../' . $featVid)) @unlink(__DIR__ . '/../../' . $featVid);
                    $featVid = $upV;
                } else {
                    $error = 'خطا در آپلود ویدیو شاخص. فرمت‌های مجاز: MP4، WebM، MOV، MKV (حداکثر 200MB)';
                }
            }

            // حذف ویدیو شاخص
            if (!$error && !empty($_POST['remove_featured_video'])) {
                if ($featVid && file_exists(__DIR__ . '/../../' . $featVid)) @unlink(__DIR__ . '/../../' . $featVid);
                $featVid = '';
            }

            if (!$error) {
                ensureFeaturedVideoColumn();
                $stmt = $db->prepare(
                    "UPDATE posts SET title=?, slug=?, summary=?, content=?, featured_image=?, featured_video=?, post_type=?, page_section=?,
                     category_id=?, status=?, is_featured=?, published_at=?, updated_at=NOW() WHERE id=?"
                );
                $stmt->execute([
                    $title, $slug, $summary, $content, $featImg, $featVid,
                    $post_type, $page_section,
                    $category_id ?: null,
                    $status, $is_featured, $pub_date, $id
                ]);

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
                                   ->execute([$id, $imgPath]);
                            }
                        }
                    }
                }

                // حذف تصاویر اضافی
                if (!empty($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                    foreach ($_POST['delete_images'] as $imgId) {
                        $imgRow = $db->prepare("SELECT image_path FROM post_images WHERE id=? AND post_id=?");
                        $imgRow->execute([(int)$imgId, $id]);
                        $imgData = $imgRow->fetch();
                        if ($imgData && $imgData['image_path']) {
                            $fp = __DIR__ . '/../../' . $imgData['image_path'];
                            if (file_exists($fp)) @unlink($fp);
                        }
                        $db->prepare("DELETE FROM post_images WHERE id=? AND post_id=?")->execute([(int)$imgId, $id]);
                    }
                }

                // آپلود چند فایل صوتی/ویدیویی
                if (!empty($_FILES['audio_files']['name'][0])) {
                    handleMediaUploads('post', $id, $_FILES['audio_files'], 'audio');
                }
                if (!empty($_FILES['video_files']['name'][0])) {
                    handleMediaUploads('post', $id, $_FILES['video_files'], 'video');
                }

                // حذف رسانه‌های انتخاب‌شده
                if (!empty($_POST['delete_media']) && is_array($_POST['delete_media'])) {
                    foreach ($_POST['delete_media'] as $mid) {
                        deleteMediaFile((int)$mid, 'post', $id);
                    }
                }

                // رفرش داده‌های پست
                $post = getPost($id);
                $imgStmt->execute([$id]);
                $postImages = $imgStmt->fetchAll();

                $_SESSION['flash_msg']  = 'مطلب با موفقیت بروزرسانی شد.';
                $_SESSION['flash_type'] = 'success';
                redirect(siteUrl('admin/posts/edit.php?id=' . $id));
            }
        }
    }
}

$currentSections = !empty($post['page_section'])
                 ? explode(',', $post['page_section'])
                 : ['home','news'];
$existingAudio = getMediaFor('post', $id, 'audio');
$existingVideo = getMediaFor('post', $id, 'video');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-pencil-square ms-2"></i>ویرایش: <?= sanitize(mb_strimwidth($post['title'],0,40,'...')) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= siteUrl('post.php?slug=' . urlencode($post['slug'])) ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-eye ms-1"></i>مشاهده</a>
        <a href="<?= siteUrl('admin/posts/') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right ms-1"></i>بازگشت</a>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle ms-2"></i><?= sanitize($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form" id="editPostForm">
    <?= csrfField() ?>
    <!-- فیلد مخفی برای Thumbnail خودکار از ویدیو -->
    <input type="hidden" name="auto_thumbnail" id="autoThumbnailDataEdit">

    <div class="row g-4">
        <!-- ستون اصلی -->
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">محتوای مطلب</div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">عنوان مطلب <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= sanitize($post['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">خلاصه / چکیده</label>
                        <textarea name="summary" class="form-control" rows="3"><?= sanitize($post['summary'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-bold">متن کامل مطلب</label>
                        <textarea name="content" class="form-control" rows="12"><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- تصویر شاخص -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">تصویر شاخص</div>
                <div class="admin-card-body">
                    <?php if ($post['featured_image']): ?>
                    <div class="mb-3">
                        <img src="<?= imgUrl($post['featured_image']) ?>" style="max-width:280px;max-height:180px;border-radius:8px;border:1px solid #eee" alt="تصویر فعلی" id="currentFeatImg">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_featured" id="remove_featured" value="1">
                            <label class="form-check-label text-danger small" for="remove_featured">حذف تصویر شاخص</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" id="featuredImageInputEdit" class="form-control" accept="image/*" onchange="previewImg(this,'featPreviewEdit')">
                    <div class="form-text mb-2">برای تغییر تصویر، فایل جدید انتخاب کنید</div>
                    <img id="featPreviewEdit" src="" style="display:none;max-width:300px;max-height:200px;border-radius:8px;margin-top:8px" alt="">
                    <div id="autoThumbNoticeEdit" class="alert alert-info mt-2 py-2 small" style="display:none">
                        <i class="bi bi-magic ms-1"></i> تصویر بندانگشتی به‌صورت خودکار از ویدیو تولید شد.
                    </div>
                </div>
            </div>

            <!-- ویدیو شاخص (جدا از گالری) -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-camera-video-fill ms-2 text-danger"></i>ویدیو شاخص (اختیاری)</div>
                <div class="admin-card-body">
                    <?php if (!empty($post['featured_video'])): ?>
                    <div class="mb-3">
                        <video src="<?= siteUrl($post['featured_video']) ?>" controls style="max-width:100%;max-height:240px;border-radius:8px;background:#000"></video>
                        <div class="mt-2 small text-muted">فایل فعلی: <code><?= sanitize(basename($post['featured_video'])) ?></code></div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_featured_video" id="remove_featured_video" value="1">
                            <label class="form-check-label text-danger small" for="remove_featured_video">حذف ویدیو شاخص</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_video" id="featuredVideoInputEdit" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv">
                    <div class="form-text mb-2">این ویدیو مستقل از فایل‌های ویدیویی گالری است و در ابتدای مطلب نمایش داده می‌شود. حداکثر 200MB — MP4، WebM، MOV، MKV</div>
                    <video id="featVideoPreviewEdit" controls style="display:none;max-width:100%;max-height:240px;border-radius:8px;margin-top:8px;background:#000"></video>
                </div>
            </div>

            <!-- تصاویر اضافی موجود -->
            <?php if (!empty($postImages)): ?>
            <div class="admin-card mb-4">
                <div class="admin-card-header">تصاویر اضافی موجود</div>
                <div class="admin-card-body">
                    <div class="row g-2">
                        <?php foreach ($postImages as $img): ?>
                        <div class="col-4 col-md-3">
                            <div class="position-relative">
                                <img src="<?= imgUrl($img['image_path']) ?>" style="width:100%;height:100px;object-fit:cover;border-radius:8px" alt="" loading="lazy">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="delete_images[]" id="del_<?= $img['id'] ?>" value="<?= $img['id'] ?>">
                                    <label class="form-check-label text-danger" style="font-size:.75rem" for="del_<?= $img['id'] ?>">حذف</label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- آپلود تصاویر جدید -->
            <div class="admin-card mb-4">
                <div class="admin-card-header">افزودن تصاویر جدید</div>
                <div class="admin-card-body">
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                </div>
            </div>

            <!-- فایل‌های صوتی موجود / افزودن -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><i class="bi bi-mic-fill ms-2"></i>فایل‌های صوتی</div>
                <div class="admin-card-body">
                    <?php if (!empty($existingAudio)): ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($existingAudio as $a): ?>
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delete_media[]" value="<?= (int)$a['id'] ?>" id="dm_<?= (int)$a['id'] ?>">
                                <label class="form-check-label text-danger small" for="dm_<?= (int)$a['id'] ?>">حذف</label>
                            </div>
                            <span class="flex-grow-1 text-truncate"><?= sanitize($a['title'] ?: basename($a['file_path'])) ?></span>
                            <audio controls preload="none" src="<?= siteUrl($a['file_path']) ?>" style="max-width:200px;height:32px"></audio>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <input type="file" name="audio_files[]" class="form-control" accept="audio/*,.mp3,.ogg,.wav,.m4a" multiple>
                    <div class="form-text">افزودن فایل‌های صوتی جدید — MP3، OGG، WAV، M4A (تا 20MB هرکدام).</div>
                </div>
            </div>

            <!-- فایل‌های ویدیویی موجود / افزودن -->
            <div class="admin-card">
                <div class="admin-card-header"><i class="bi bi-camera-video-fill ms-2"></i>فایل‌های ویدیویی</div>
                <div class="admin-card-body">
                    <?php if (!empty($existingVideo)): ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($existingVideo as $v): ?>
                        <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delete_media[]" value="<?= (int)$v['id'] ?>" id="dm_<?= (int)$v['id'] ?>">
                                <label class="form-check-label text-danger small" for="dm_<?= (int)$v['id'] ?>">حذف</label>
                            </div>
                            <span class="flex-grow-1 text-truncate small"><?= sanitize($v['title'] ?: basename($v['file_path'])) ?></span>
                            <div class="d-flex gap-1">
                                <a href="<?= siteUrl($v['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-play-circle"></i> پخش</a>
                                <a href="<?= siteUrl($v['file_path']) ?>" download class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <input type="file" name="video_files[]" id="videoFilesInputEdit" class="form-control" accept="video/*,.mp4,.webm,.mov,.mkv" multiple>
                    <div class="form-text">افزودن ویدیوهای جدید — MP4، WebM، MOV، MKV (تا 200MB هرکدام).</div>
                    <div id="videoThumbProgressEdit" class="mt-2" style="display:none">
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
                            <option value="draft"     <?= $post['status']==='draft'?'selected':'' ?>>پیش‌نویس</option>
                            <option value="published" <?= $post['status']==='published'?'selected':'' ?>>منتشرشده</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ انتشار</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="<?= date('Y-m-d\TH:i', strtotime($post['published_at'] ?? 'now')) ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured_e" value="1"
                               <?= $post['is_featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured_e">نمایش در اسلایدر (برجسته)</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success"><i class="bi bi-save ms-1"></i>ذخیره تغییرات</button>
                        <a href="<?= siteUrl('admin/posts/delete.php?id=' . $id . '&' . CSRF_TOKEN_NAME . '=' . urlencode(generateCsrfToken())) ?>"
                           class="btn btn-outline-danger"
                           data-confirm="آیا از حذف این مطلب اطمینان دارید؟">
                            <i class="bi bi-trash ms-1"></i>حذف مطلب
                        </a>
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
                            <option value="news"         <?= $post['post_type']==='news'?'selected':'' ?>>📰 خبر</option>
                            <option value="article"      <?= $post['post_type']==='article'?'selected':'' ?>>📄 مقاله</option>
                            <option value="announcement" <?= $post['post_type']==='announcement'?'selected':'' ?>>📢 اطلاعیه</option>
                            <option value="speech"       <?= $post['post_type']==='speech'?'selected':'' ?>>🎤 سخنرانی</option>
                            <option value="program"      <?= $post['post_type']==='program'?'selected':'' ?>>📅 برنامه آموزشی</option>
                            <option value="religious"    <?= $post['post_type']==='religious'?'selected':'' ?>>⭐ فعالیت مذهبی</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">دسته‌بندی</label>
                        <select name="category_id" class="form-select">
                            <option value="">— بدون دسته —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $post['category_id']==$cat['id']?'selected':'' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- بخش‌های نمایش -->
            <div class="admin-card mb-3">
                <div class="admin-card-header"><i class="bi bi-layout-text-window ms-2"></i>نمایش در کدام بخش‌ها؟</div>
                <div class="admin-card-body">
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
                        $checked = in_array($val, $currentSections) ? 'checked' : '';
                    ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="page_section[]" id="esec_<?= $val ?>" value="<?= $val ?>" <?= $checked ?>>
                        <label class="form-check-label small" for="esec_<?= $val ?>"><?= $label ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- آمار -->
            <div class="admin-card">
                <div class="admin-card-header">آمار مطلب</div>
                <div class="admin-card-body">
                    <div class="d-flex justify-content-between small mb-2"><span>بازدید:</span><strong><?= number_format($post['views']) ?></strong></div>
                    <div class="d-flex justify-content-between small mb-2"><span>ایجاد:</span><strong><?= persianDate($post['created_at']) ?></strong></div>
                    <div class="d-flex justify-content-between small"><span>ویرایش:</span><strong><?= persianDate($post['updated_at']) ?></strong></div>
                    <?php if ($post['slug']): ?>
                    <hr class="my-2">
                    <div class="small"><strong>اسلاگ:</strong><br><code style="font-size:.72rem;word-break:break-all"><?= sanitize($post['slug']) ?></code></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
/**
 * تولید خودکار Thumbnail در صفحه ویرایش
 * فقط اگر پست تصویر شاخص ندارد یا تصویر قدیمی حذف می‌شود
 */
(function() {
    var videoInput     = document.getElementById('videoFilesInputEdit');
    var imageInput     = document.getElementById('featuredImageInputEdit');
    var removeFeatChk  = document.getElementById('remove_featured');
    var thumbDataInput = document.getElementById('autoThumbnailDataEdit');
    var featPreview    = document.getElementById('featPreviewEdit');
    var currentImg     = document.getElementById('currentFeatImg');
    var thumbNotice    = document.getElementById('autoThumbNoticeEdit');
    var thumbProgress  = document.getElementById('videoThumbProgressEdit');
    var hasFeatImg     = <?= $post['featured_image'] ? 'true' : 'false' ?>;

    if (!videoInput) return;

    function shouldGenerateThumb() {
        // اگر تصویر دستی انتخاب شده، نیازی نیست
        if (imageInput && imageInput.files.length > 0) return false;
        // اگر پست تصویر داشت و حذف نشده، نیازی نیست
        if (hasFeatImg && (!removeFeatChk || !removeFeatChk.checked)) return false;
        return true;
    }

    videoInput.addEventListener('change', function() {
        var file = this.files[0];
        if (!file || !shouldGenerateThumb()) return;

        if (thumbProgress) thumbProgress.style.display = 'block';
        if (thumbNotice)   thumbNotice.style.display   = 'none';

        var objectUrl = URL.createObjectURL(file);
        var videoEl   = document.createElement('video');
        videoEl.muted    = true;
        videoEl.preload  = 'metadata';
        videoEl.playsInline = true;
        videoEl.style.display = 'none';

        videoEl.addEventListener('loadedmetadata', function() {
            var seekTime = Math.min(1, videoEl.duration * 0.1);
            videoEl.currentTime = seekTime;
        });

        videoEl.addEventListener('seeked', function() {
            var w = videoEl.videoWidth  || 640;
            var h = videoEl.videoHeight || 360;
            var maxW = 1280;
            if (w > maxW) { h = Math.round(h * maxW / w); w = maxW; }

            var canvas = document.createElement('canvas');
            canvas.width  = w;
            canvas.height = h;
            canvas.getContext('2d').drawImage(videoEl, 0, 0, w, h);

            var dataUrl = canvas.toDataURL('image/jpeg', 0.82);
            if (thumbDataInput) thumbDataInput.value = dataUrl;

            if (featPreview) {
                featPreview.src           = dataUrl;
                featPreview.style.display = 'block';
            }
            if (thumbProgress) thumbProgress.style.display = 'none';
            if (thumbNotice)   thumbNotice.style.display   = 'flex';

            URL.revokeObjectURL(objectUrl);
            videoEl.remove();
        });

        videoEl.addEventListener('error', function() {
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

    // اگر checkbox حذف تیک زده شد، بررسی مجدد
    if (removeFeatChk) {
        removeFeatChk.addEventListener('change', function() {
            if (thumbDataInput) thumbDataInput.value = '';
            if (thumbNotice)    thumbNotice.style.display = 'none';
        });
    }

    // پیش‌نمایش ویدیو شاخص
    var featVidInput = document.getElementById('featuredVideoInputEdit');
    var featVidPrev  = document.getElementById('featVideoPreviewEdit');
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
