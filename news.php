<?php
/**
 * news.php — صفحه اخبار + دکمه لایک + نشانه‌گر ویدیو
 */
$pageTitle = 'اخبار';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/media.php';

$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = POSTS_PER_PAGE;
$offset = ($page - 1) * $limit;

$opts = ['type' => 'news', 'section' => 'news', 'limit' => $limit, 'offset' => $offset];
if ($search) $opts['search'] = $search;

$posts = getPosts($opts);
$total = countPosts(array_merge(['type' => 'news', 'section' => 'news'], $search ? ['search' => $search] : []));
$pages = (int)ceil($total / $limit);

// دریافت یک‌جای تعداد لایک و ویدیو برای همه پست‌ها (بدون N+1 query)
$postIds  = array_column($posts, 'id');
$likeMap  = getLikeCountsBulk($postIds);
$videoMap = [];
if (!empty($postIds)) {
    try {
        ensureMediaTable();
        $ph = implode(',', array_fill(0, count($postIds), '?'));
        $vstmt = getDB()->prepare("SELECT ref_id, COUNT(*) cnt FROM media_files WHERE ref_type='post' AND kind='video' AND ref_id IN ($ph) GROUP BY ref_id");
        $vstmt->execute($postIds);
        foreach ($vstmt->fetchAll() as $r) $videoMap[(int)$r['ref_id']] = (int)$r['cnt'];
    } catch (PDOException $e) {}
}
$likedInSession = $_SESSION['liked_posts'] ?? [];
$likeUrl = siteUrl('ajax/like.php');
?>

<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
                <li class="breadcrumb-item active">اخبار</li>
            </ol>
        </nav>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <h1 class="page-title"><i class="bi bi-newspaper ms-2 text-gold"></i>اخبار مدرسه</h1>
            <div class="section-divider"></div>
        </div>

        <!-- جستجو -->
        <form method="get" class="mb-4">
            <div class="input-group" style="max-width:400px">
                <input type="text" name="q" class="form-control" placeholder="جستجو در اخبار..." value="<?= sanitize($search) ?>">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                <?php if ($search): ?>
                <a href="<?= siteUrl('news.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($search): ?>
        <div class="alert alert-info mb-4">
            نتایج جستجو برای «<strong><?= sanitize($search) ?></strong>» — <?= number_format($total) ?> نتیجه
        </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
        <div class="text-center py-5">
            <i class="bi bi-newspaper display-1 text-muted opacity-25 d-block mb-3"></i>
            <h4 class="text-muted">خبری یافت نشد</h4>
            <?php if ($search): ?>
            <a href="<?= siteUrl('news.php') ?>" class="btn btn-primary mt-2">نمایش همه اخبار</a>
            <?php endif; ?>
        </div>
        <?php else: ?>

        <!-- لیست اخبار -->
        <div class="row g-4">
            <?php foreach ($posts as $k => $news):
                $nid      = (int)$news['id'];
                $nCount   = $likeMap[$nid] ?? 0;
                $nLiked   = in_array($nid, $likedInSession, true);
                $hasMediaVideo = !empty($videoMap[$nid]);
                $hasFeatVideo  = !empty($news['featured_video']);
                $hasVideo      = $hasMediaVideo || $hasFeatVideo;
            ?>
            <div class="col-md-6 col-lg-4">
                <article class="news-card h-100">
                    <div class="news-card-img-wrap position-relative">
                        <?php if ($news['featured_image']): ?>
                        <img src="<?= imgUrl($news['featured_image']) ?>" alt="<?= sanitize($news['title']) ?>" class="news-card-img" loading="lazy" decoding="async">
                        <?php else: ?>
                        <div class="news-card-img-placeholder"><i class="bi bi-newspaper"></i></div>
                        <?php endif; ?>
                        <?php if ($hasVideo): ?>
                        <span class="video-badge-card"><i class="bi bi-camera-video-fill"></i> ویدیو</span>
                        <?php endif; ?>
                    </div>
                    <div class="news-card-body">
                        <div class="news-card-meta">
                            <span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($news['published_at'] ?? $news['created_at']) ?></span>
                            <span class="text-muted small"><i class="bi bi-eye ms-1"></i><?= number_format($news['views']) ?></span>
                        </div>
                        <h3 class="news-card-title">
                            <a href="<?= siteUrl('post.php?slug=' . urlencode($news['slug'])) ?>"><?= sanitize($news['title']) ?></a>
                        </h3>
                        <?php if ($news['summary']): ?>
                        <p class="news-card-summary"><?= sanitize(excerpt($news['summary'], 130)) ?></p>
                        <?php endif; ?>
                        <div class="news-card-footer">
                            <a href="<?= siteUrl('post.php?slug=' . urlencode($news['slug'])) ?>" class="btn-read-more">
                                ادامه مطلب <i class="bi bi-arrow-left"></i>
                            </a>
                            <div class="d-flex gap-2 align-items-center">
                                <!-- دکمه لایک -->
                                <button type="button"
                                    class="btn-like <?= $nLiked ? 'liked' : '' ?>"
                                    data-post-id="<?= $nid ?>"
                                    data-url="<?= htmlspecialchars($likeUrl, ENT_QUOTES) ?>"
                                    title="<?= $nLiked ? 'لایک را بردار' : 'لایک کن' ?>">
                                    <span class="like-icon"><?= $nLiked ? '❤️' : '🤍' ?></span>
                                    <span class="like-count"><?= $nCount > 0 ? number_format($nCount) : '' ?></span>
                                </button>
                                <!-- کپی لینک -->
                                <button class="btn-copy-link" title="کپی لینک خبر"
                                    onclick="navigator.clipboard.writeText('<?= siteUrl('post.php?slug=' . urlencode($news['slug'])) ?>').then(function(){this.innerHTML='<i class=\'bi bi-check-circle text-success\'></i>';}.bind(this))">
                                    <i class="bi bi-link-45deg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- صفحه‌بندی -->
        <?php if ($pages > 1): ?>
        <div class="mt-5">
            <?= paginate($total, $limit, $page, siteUrl('news.php') . '?q=' . urlencode($search) . '&page=%d') ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
