<?php
/**
 * lessons.php — صفحه درس‌ها
 */
$pageTitle = 'درس‌ها';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Migration: اطمینان از وجود همه ستون‌های لازم در جدول lessons
ensureLessonsColumns();

$search = trim($_GET['q'] ?? '');
$level  = trim($_GET['level'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = LESSONS_PER_PAGE;
$offset = ($page - 1) * $limit;

// نمایش همه درس‌های منتشرشده (بدون فیلتر page_section)
$where  = ["status = 'published'"];
$params = [];
if ($search) {
    $where[]  = "(title LIKE ? OR content LIKE ? OR summary LIKE ? OR teacher LIKE ?)";
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($level)  { $where[] = "level = ?"; $params[] = $level; }
$whereStr = implode(' AND ', $where);

$total = 0;
$pages = 1;
$lessons = [];
try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM lessons WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pages = $total > 0 ? (int)ceil($total / $limit) : 1;

    $stmt = $db->prepare("SELECT * FROM lessons WHERE $whereStr ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $lessons = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('lessons.php query error: ' . $e->getMessage());
}

function levelLabel2(string $l): string {
    return match($l) { 'beginner' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته', default => $l };
}
?>

<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
                <li class="breadcrumb-item active">درس‌ها</li>
            </ol>
        </nav>
    </div>
</div>

<main class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <h1 class="page-title"><i class="bi bi-play-circle-fill ms-2 text-gold"></i>درس‌های آموزشی</h1>
            <div class="section-divider"></div>
        </div>

        <!-- فیلتر -->
        <form method="get" class="mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control" placeholder="جستجو در درس‌ها..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-4">
                    <select name="level" class="form-select">
                        <option value="">همه سطوح</option>
                        <option value="beginner" <?= $level==='beginner'?'selected':'' ?>>مقدماتی</option>
                        <option value="intermediate" <?= $level==='intermediate'?'selected':'' ?>>متوسط</option>
                        <option value="advanced" <?= $level==='advanced'?'selected':'' ?>>پیشرفته</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search ms-1"></i>جستجو</button>
                </div>
            </div>
        </form>

        <?php if (empty($lessons)): ?>
        <div class="text-center py-5">
            <i class="bi bi-play-circle display-1 text-muted opacity-25 d-block mb-3"></i>
            <h4 class="text-muted">درسی یافت نشد</h4>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($lessons as $lesson): ?>
            <div class="col-md-6 col-lg-3">
                <div class="lesson-card h-100">
                    <div class="lesson-card-img">
                        <?php if ($lesson['featured_image']): ?>
                        <img src="<?= imgUrl($lesson['featured_image']) ?>" alt="<?= sanitize($lesson['title']) ?>" loading="lazy">
                        <?php else: ?>
                        <div class="lesson-img-placeholder"><i class="bi bi-play-circle"></i></div>
                        <?php endif; ?>
                        <?php if ($lesson['audio_file']): ?>
                        <span class="lesson-audio-badge"><i class="bi bi-headphones"></i> صوتی</span>
                        <?php endif; ?>
                        <?php if (!empty($lesson['level'])): ?>
                        <span class="lesson-level-badge badge bg-<?= $lesson['level']==='beginner'?'success':($lesson['level']==='intermediate'?'warning':'danger') ?>"><?= levelLabel2($lesson['level']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-card-body">
                        <?php if ($lesson['subject']): ?>
                        <span class="lesson-subject"><?= sanitize($lesson['subject']) ?></span>
                        <?php endif; ?>
                        <h4 class="lesson-card-title">
                            <a href="<?= siteUrl('lesson.php?slug=' . urlencode($lesson['slug'])) ?>"><?= sanitize($lesson['title']) ?></a>
                        </h4>
                        <?php
                        $lessonDesc = $lesson['summary'] ?? $lesson['content'] ?? '';
                        if ($lessonDesc): ?>
                        <p class="lesson-desc"><?= sanitize(excerpt($lessonDesc, 90)) ?></p>
                        <?php endif; ?>
                        <?php if ($lesson['teacher']): ?>
                        <p class="lesson-teacher"><i class="bi bi-person-fill ms-1"></i><?= sanitize($lesson['teacher']) ?></p>
                        <?php endif; ?>
                        <a href="<?= siteUrl('lesson.php?slug=' . urlencode($lesson['slug'])) ?>" class="btn btn-sm btn-primary w-100 mt-auto">مشاهده درس <i class="bi bi-arrow-left ms-1"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
        <div class="mt-5">
            <?= paginate($total, $limit, $page, siteUrl('lessons.php') . '?q=' . urlencode($search) . '&level=' . urlencode($level) . '&page=%d') ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
