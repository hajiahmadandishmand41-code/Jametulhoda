<?php
$q = trim($_GET['q'] ?? '');
$pageTitle = $q ? 'جستجو: ' . $q : 'جستجو — آرشیو محتوایی';
$pageDesc = $q ? 'نتایج جستجو برای «'.$q.'» در موضوعات، مقالات، گزارش‌ها، کتاب‌ها، دروس و رسانه‌ها.' : 'جستجو در آرشیو محتوایی مدرسه جامعه‌الهدی — موضوعات، مقالات، گزارش‌ها، کتاب‌ها، دروس، ویدیو و صوت.';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = POSTS_PER_PAGE;
$q = mb_substr($q, 0, 200);
$results=[]; $total=0;
if ($q) {
    $offset=($page-1)*$limit;
    $data=searchAll($q,$limit,$offset);
    $results=$data['results'];
    $total=$data['total'];
}
$pages = (int)ceil($total / $limit);
if($q){
    $breadcrumbsJsonLd=breadcrumbsJsonLd([
        ['name'=>'صفحه اصلی','url'=>SITE_URL? rtrim(SITE_URL,'/').'/': siteUrl()],
        ['name'=>'جستجو','url'=>siteUrl('search?q='.urlencode($q))],
    ]);
}
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
    <li class="breadcrumb-item active">جستجو</li>
</ol></nav></div></div>
<div class="py-5"><div class="container">
    <div class="page-header mb-4"><h1 class="page-title"><i class="bi bi-search ms-2 text-gold"></i> جستجو در آرشیو محتوایی</h1><div class="section-divider"></div><p class="text-muted small">جستجو در موضوعات، مقالات، گزارش‌ها، کتاب‌ها، دروس، ویدیو و صوت — نتایج SEO-friendly و سریع</p></div>
    <form method="get" class="mb-5">
        <div class="input-group input-group-lg" style="max-width:600px">
            <input type="text" name="q" class="form-control" placeholder="مثلاً: مهدویت، امام حسین، فقه..." value="<?= sanitize($q) ?>" autofocus>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search ms-1"></i> جستجو</button>
        </div>
    </form>
    <?php if ($q): ?>
    <div class="mb-4">
        <?php if ($total > 0): ?>
        <p class="text-muted"><?= number_format($total) ?> نتیجه برای «<strong><?= sanitize($q) ?></strong>»</p>
        <?php else: ?>
        <div class="text-center py-5"><i class="bi bi-search display-1 text-muted opacity-25 d-block mb-3"></i><h2 class="h4 text-muted">نتیجه‌ای برای «<?= sanitize($q) ?>» یافت نشد</h2><p class="text-muted small">عبارت دیگری را امتحان کنید یا از موضوعات استفاده کنید.</p><a href="<?= siteUrl('topics') ?>" class="btn btn-outline-primary btn-sm mt-2">مرور موضوعات</a></div>
        <?php endif; ?>
    </div>
    <?php if (!empty($results)): ?>
    <div class="row g-4">
        <?php foreach ($results as $p):
            if($p['target']==='topic'){
                $resultUrl = topicUrl($p);
                $badge = '<span class="badge" style="background:#fdf6e3;color:#7a5a1a;border:1px solid #e8d5a3">موضوع</span>';
            } elseif($p['target']==='book'){
                $resultUrl = bookUrl($p);
                $badge = '<span class="badge bg-warning text-dark">کتاب</span>';
            } elseif($p['target']==='lesson'){
                $resultUrl = lessonUrl($p);
                $badge = '<span class="badge bg-success">درس</span>';
            } else {
                $resultUrl = postUrl($p);
                $badge = postTypeBadge($p['post_type']);
            }
        ?>
        <div class="col-md-6 col-lg-4">
            <article class="news-card h-100">
                <div class="news-card-img-wrap">
                    <?php if ($p['featured_image']): ?><img src="<?= imgUrl($p['featured_image']) ?>" alt="<?= sanitize($p['title']) ?>" class="news-card-img" loading="lazy">
                    <?php else: ?><div class="news-card-img-placeholder"><i class="bi <?= $p['target']==='topic'?'bi-diagram-3':($p['target']==='book'?'bi-book':($p['target']==='lesson'?'bi-mortarboard':'bi-file-text')) ?>"></i></div><?php endif; ?>
                    <div class="news-card-badge"><?= $badge ?></div>
                </div>
                <div class="news-card-body">
                    <div class="news-card-meta"><span class="text-muted small"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($p['published_at'] ?? $p['created_at']) ?></span></div>
                    <h2 class="news-card-title h5"><a href="<?= $resultUrl ?>"><?= sanitize($p['title']) ?></a></h2>
                    <?php if ($p['summary']): ?><p class="news-card-summary"><?= sanitize(excerpt($p['summary'], 120)) ?></p><?php endif; ?>
                    <div class="news-card-footer"><a href="<?= $resultUrl ?>" class="btn-read-more">مشاهده <i class="bi bi-arrow-left"></i></a></div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?><div class="mt-5"><?= paginate($total, $limit, $page, siteUrl('search') . '?q=' . urlencode($q) . '&page=%d') ?></div><?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
