<?php
/**
 * lesson.php — صفحه نمایش درس با پلیر صوتی حرفه‌ای
 * اصلاح‌شده: پخش صوت بدون خطا، پلیر HTML5 + Plyr.js
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

$db   = getDB();
$slug = trim($_GET['slug'] ?? '');

if (!$slug) {
    redirect(siteUrl('lessons.php'));
}

// ─── دریافت درس ──────────────────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT * FROM lessons WHERE slug = ? AND status = 'published' LIMIT 1"
);
$stmt->execute([$slug]);
$lesson = $stmt->fetch();

if (!$lesson) {
    http_response_code(404);
    $pageTitle = 'درس یافت نشد';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center">
        <i class="bi bi-exclamation-circle display-1 text-muted d-block mb-3"></i>
        <h2>درس مورد نظر یافت نشد</h2>
        <a href="' . siteUrl('lessons.php') . '" class="btn btn-primary mt-3">بازگشت به لیست درس‌ها</a>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// افزایش بازدید
try {
    $db->prepare("UPDATE lessons SET views = COALESCE(views,0) + 1 WHERE id = ?")->execute([$lesson['id']]);
} catch (PDOException $e) {}

$pageTitle = $lesson['title'];
$pageDesc  = $lesson['summary'] ? excerpt($lesson['summary'], 200) : '';

// آدرس فایل صوتی
$audioUrl = '';
if (!empty($lesson['audio_file'])) {
    $audioUrl = siteUrl(ltrim($lesson['audio_file'], '/'));
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
                <li class="breadcrumb-item"><a href="<?= siteUrl('lessons.php') ?>">درس‌ها</a></li>
                <?php if ($lesson['subject']): ?>
                <li class="breadcrumb-item"><?= sanitize($lesson['subject']) ?></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= sanitize(mb_strimwidth($lesson['title'], 0, 50, '...')) ?></li>
            </ol>
        </nav>
    </div>
</div>

<main class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- محتوای اصلی -->
            <div class="col-lg-8">
                <article class="single-post lesson-single">

                    <!-- هدر درس -->
                    <header class="single-post-header">
                        <?php if ($lesson['subject']): ?>
                        <span class="badge bg-primary mb-2"><?= sanitize($lesson['subject']) ?></span>
                        <?php endif; ?>
                        <h1 class="single-post-title mt-2"><?= sanitize($lesson['title']) ?></h1>
                        <div class="single-post-meta d-flex flex-wrap align-items-center gap-3 mt-3">
                            <span><i class="bi bi-calendar3 ms-1"></i><?= persianDate($lesson['created_at']) ?></span>
                            <?php if ($lesson['teacher']): ?>
                            <span><i class="bi bi-person-fill ms-1"></i>استاد: <?= sanitize($lesson['teacher']) ?></span>
                            <?php endif; ?>
                            <?php if ($audioUrl): ?>
                            <span class="text-success"><i class="bi bi-headphones ms-1"></i>فایل صوتی موجود</span>
                            <?php endif; ?>
                        </div>
                    </header>

                    <!-- تصویر شاخص -->
                    <?php if (!empty($lesson['featured_image'])): ?>
                    <div class="single-post-img-wrap mt-4">
                        <img src="<?= imgUrl($lesson['featured_image']) ?>"
                             alt="<?= sanitize($lesson['title']) ?>"
                             class="single-post-img img-fluid rounded-xl"
                             loading="lazy" decoding="async">
                    </div>
                    <?php endif; ?>

                    <!-- ─── پلیر صوتی حرفه‌ای ──────────────────────────────── -->
                    <?php if ($audioUrl): ?>
                    <div class="audio-player-wrap my-5 p-4 rounded-xl" style="background:linear-gradient(135deg,#f8f9fa 0%,#e8f4f8 100%);border:2px solid #dee2e6;box-shadow:0 4px 20px rgba(0,0,0,.07)">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="audio-icon-circle" style="width:56px;height:56px;background:linear-gradient(135deg,#1a6b3c,#2ecc71);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-headphones text-white" style="font-size:1.5rem"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size:1.05rem"><?= sanitize($lesson['title']) ?></div>
                                <?php if ($lesson['teacher']): ?>
                                <div class="text-muted small"><i class="bi bi-person ms-1"></i><?= sanitize($lesson['teacher']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- پلیر HTML5 استاندارد -->
                        <audio id="lessonAudio"
                               controls
                               preload="none"
                               class="w-100"
                               style="border-radius:12px;height:54px;outline:none"
                               controlsList="nodownload">
                            <source src="<?= htmlspecialchars($audioUrl, ENT_QUOTES) ?>"
                                    type="audio/<?= in_array(strtolower(pathinfo($lesson['audio_file'], PATHINFO_EXTENSION)), ['mp3','mpeg']) ? 'mpeg' : strtolower(pathinfo($lesson['audio_file'], PATHINFO_EXTENSION)) ?>">
                            مرورگر شما از پخش صوت پشتیبانی نمی‌کند.
                            <a href="<?= htmlspecialchars($audioUrl, ENT_QUOTES) ?>">دانلود فایل صوتی</a>
                        </audio>

                        <!-- دکمه دانلود -->
                        <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                            <a href="<?= htmlspecialchars($audioUrl, ENT_QUOTES) ?>"
                               download
                               class="btn btn-outline-success btn-sm">
                                <i class="bi bi-download ms-1"></i>دانلود فایل صوتی
                            </a>
                            <span class="text-muted small">
                                <i class="bi bi-info-circle ms-1"></i>
                                برای دانلود روی دکمه کلیک کنید
                            </span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning my-4">
                        <i class="bi bi-mic-mute ms-2"></i>
                        فایل صوتی برای این درس بارگذاری نشده است.
                    </div>
                    <?php endif; ?>

                    <!-- خلاصه -->
                    <?php if ($lesson['summary']): ?>
                    <div class="single-post-summary my-4 p-3 bg-soft rounded-xl">
                        <strong><i class="bi bi-card-text ms-2 text-gold"></i>خلاصه درس:</strong>
                        <p class="mb-0 mt-2"><?= sanitize($lesson['summary']) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- محتوای کامل -->
                    <?php if ($lesson['content']): ?>
                    <div class="single-post-content mt-4">
                        <?= $lesson['content'] ?>
                    </div>
                    <?php endif; ?>

                    <!-- اشتراک‌گذاری -->
                    <div class="single-post-share mt-5 p-4 bg-soft rounded-xl">
                        <h5 class="mb-3"><i class="bi bi-share ms-2 text-gold"></i>اشتراک‌گذاری این درس</h5>
                        <?php $lessonUrl = siteUrl('lesson.php?slug=' . urlencode($lesson['slug'])); ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="https://t.me/share/url?url=<?= urlencode($lessonUrl) ?>&text=<?= urlencode($lesson['title']) ?>"
                               target="_blank" class="btn btn-sm" style="background:#2ca5e0;color:#fff">
                                <i class="bi bi-telegram ms-1"></i>تلگرام
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($lesson['title'] . ' - ' . $lessonUrl) ?>"
                               target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff">
                                <i class="bi bi-whatsapp ms-1"></i>واتساپ
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyLessonLink('<?= htmlspecialchars($lessonUrl) ?>')">
                                <i class="bi bi-link-45deg ms-1"></i>کپی لینک
                            </button>
                        </div>
                    </div>

                </article>
            </div>

            <!-- ستون کناری -->
            <div class="col-lg-4">
                <div class="sidebar">
                    <!-- درس‌های مرتبط -->
                    <?php
                    $relLessons = [];
                    try {
                        $rStmt = $db->prepare(
                            "SELECT * FROM lessons WHERE status='published' AND id != ? " .
                            ($lesson['subject'] ? "AND subject = ?" : "") .
                            " ORDER BY created_at DESC LIMIT 5"
                        );
                        $rParams = [$lesson['id']];
                        if ($lesson['subject']) $rParams[] = $lesson['subject'];
                        $rStmt->execute($rParams);
                        $relLessons = $rStmt->fetchAll();
                    } catch (PDOException $e) {}
                    ?>
                    <?php if (!empty($relLessons)): ?>
                    <div class="sidebar-widget">
                        <h5 class="sidebar-title"><i class="bi bi-collection-play ms-2 text-gold"></i>درس‌های مرتبط</h5>
                        <ul class="sidebar-list">
                            <?php foreach ($relLessons as $rl): ?>
                            <li>
                                <a href="<?= siteUrl('lesson.php?slug=' . urlencode($rl['slug'])) ?>">
                                    <?= sanitize(mb_strimwidth($rl['title'], 0, 55, '...')) ?>
                                </a>
                                <?php if ($rl['audio_file']): ?>
                                <span class="badge bg-success ms-1" style="font-size:.65rem"><i class="bi bi-headphones"></i></span>
                                <?php endif; ?>
                                <span class="sidebar-date"><?= persianDate($rl['created_at']) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- جستجو در درس‌ها -->
                    <div class="sidebar-widget">
                        <h5 class="sidebar-title"><i class="bi bi-search ms-2 text-gold"></i>جستجوی درس</h5>
                        <form action="<?= siteUrl('lessons.php') ?>" method="get">
                            <div class="input-group">
                                <input type="text" name="q" class="form-control" placeholder="جستجو..." value="">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                            </div>
                        </form>
                    </div>

                    <div class="sidebar-widget text-center">
                        <a href="<?= siteUrl('lessons.php') ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-collection-play ms-1"></i>همه درس‌ها
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function copyLessonLink(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            if (typeof showToast === 'function') showToast('لینک کپی شد!', 'success');
        });
    } else {
        prompt('لینک درس:', url);
    }
}

// Plyr برای پلیر صوتی اگر موجود باشد
(function() {
    if (typeof Plyr === 'undefined') return;
    var audioEl = document.getElementById('lessonAudio');
    if (!audioEl) return;
    var plyrAudio = new Plyr(audioEl, {
        controls: ['play','progress','current-time','duration','mute','volume'],
        loadSprite: false,
        iconUrl: '',
        i18n: {
            play: 'پخش', pause: 'مکث', mute: 'بی‌صدا',
            unmute: 'صدا', volume: 'صدا'
        }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
