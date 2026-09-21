<?php
/**
 * lessons.php — فهرست دروس با ساختار مجموعه → جلد → درس
 */
$pageTitle='دروس حوزوی';
$pageDesc='مجموعه دروس حوزوی به‌صورت درس‌به‌درس با جلد و بخش‌ها، همراه با صوت، ویدیو و PDF و موضوعات مرتبط.';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$collectionSlug = trim($_GET['collection'] ?? '');
$volumeSlug = trim($_GET['volume'] ?? '');
$search = trim($_GET['q'] ?? '');
$level = trim($_GET['level'] ?? '');
$page = max(1,(int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page-1)*$limit;

$activeCollection = $collectionSlug ? getLessonCollectionBySlug($collectionSlug) : null;
$activeVolume = null;
if($activeCollection && $volumeSlug){
    foreach(getLessonVolumes((int)$activeCollection['id']) as $v){
        if($v['slug']===$volumeSlug){ $activeVolume=$v; break; }
    }
}

// Breadcrumbs
$breadcrumbs=[
    ['name'=>'صفحه اصلی','url'=>siteUrl()],
    ['name'=>'دروس','url'=>siteUrl('lessons.php')],
];
if($activeCollection){
    $breadcrumbs[]=['name'=>$activeCollection['title'],'url'=>siteUrl('lessons.php?collection='.urlencode($activeCollection['slug']))];
    if($activeVolume) $breadcrumbs[]=['name'=>$activeVolume['title'],'url'=>siteUrl('lessons.php?collection='.urlencode($activeCollection['slug']).'&volume='.urlencode($activeVolume['slug']))];
}
$breadcrumbsJsonLd=breadcrumbsJsonLd($breadcrumbs);
?>
<div class="breadcrumb-bar"><div class="container"><nav><ol class="breadcrumb mb-0">
<?php foreach($breadcrumbs as $i=>$bc): $isLast=$i===count($breadcrumbs)-1; ?>
<li class="breadcrumb-item <?= $isLast?'active':'' ?>" <?= $isLast?'aria-current="page"':'' ?>><?php if(!$isLast): ?><a href="<?= sanitize($bc['url']) ?>"><?= sanitize($bc['name']) ?></a><?php else: ?><?= sanitize($bc['name']) ?><?php endif; ?></li>
<?php endforeach; ?>
</ol></nav></div></div>

<div class="py-5"><div class="container">
<div class="page-header mb-4">
<h1 class="page-title"><i class="bi bi-mortarboard-fill ms-2 text-gold"></i>
<?php if($activeVolume): ?><?= sanitize($activeVolume['title']) ?> — <?= sanitize($activeCollection['title']) ?>
<?php elseif($activeCollection): ?><?= sanitize($activeCollection['title']) ?>
<?php else: ?> دروس حوزوی <?php endif; ?>
</h1>
<?php if($activeCollection && $activeCollection['description']): ?><p class="text-muted"><?= sanitize($activeCollection['description']) ?></p><?php endif; ?>
<div class="section-divider"></div>
</div>

<?php if(!$activeCollection): ?>
<!-- فهرست مجموعه‌های درسی -->
<?php $collections=getLessonCollections(['active'=>1]); if(empty($collections)): ?>
<div class="text-center py-5 text-muted">مجموعه درسی ثبت نشده است.</div>
<?php else: ?>
<div class="row g-4 mb-5">
<?php foreach($collections as $col): $vols=getLessonVolumes((int)$col['id']); $cnt=count(getLessonsByCollection((int)$col['id'])); ?>
<div class="col-md-6 col-lg-4">
<div class="card h-100">
<?php if($col['cover_image']): ?><img src="<?= imgUrl($col['cover_image']) ?>" alt="<?= sanitize($col['title']) ?>" style="height:180px;object-fit:cover" class="card-img-top" loading="lazy"><?php endif; ?>
<div class="card-body">
<h3 class="h5"><a href="<?= siteUrl('lessons.php?collection='.urlencode($col['slug'])) ?>"><?= sanitize($col['title']) ?></a></h3>
<?php if($col['description']): ?><p class="text-muted small"><?= sanitize(excerpt($col['description'],120)) ?></p><?php endif; ?>
<?php if($vols): ?><div class="d-flex flex-wrap gap-1 mb-2"><?php foreach($vols as $v): ?><a href="<?= siteUrl('lessons.php?collection='.urlencode($col['slug']).'&volume='.urlencode($v['slug'])) ?>" class="badge bg-light text-dark border"><?= sanitize($v['title']) ?></a><?php endforeach; ?></div><?php endif; ?>
<span class="text-muted small"><i class="bi bi-collection-play ms-1"></i><?= number_format($cnt) ?> درس</span>
<div class="mt-3"><a href="<?= siteUrl('lessons.php?collection='.urlencode($col['slug'])) ?>" class="btn btn-primary btn-sm w-100">مشاهده دروس <i class="bi bi-arrow-left ms-1"></i></a></div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- آخرین دروس عمومی (اگر مجموعه باز نیست، نمایش همه) -->
<?php
// Search/filter for all lessons
$where=["l.status='published'"]; $params=[];
if($search){ $where[]="(l.title ILIKE ? OR l.content ILIKE ? OR l.summary ILIKE ? OR l.teacher ILIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s,$s,$s]); }
if($level){ $where[]="l.level=?"; $params[]=$level; }
$whereStr=implode(' AND ',$where);
try{
    $cntStmt=getDB()->prepare("SELECT COUNT(*) FROM lessons l WHERE $whereStr");
    $cntStmt->execute($params); $total=(int)$cntStmt->fetchColumn();
    $pages=(int)ceil($total/$limit);
    $stmt=getDB()->prepare("SELECT l.*, lc.title AS collection_title, lv.title AS volume_title FROM lessons l LEFT JOIN lesson_collections lc ON lc.id=l.collection_id LEFT JOIN lesson_volumes lv ON lv.id=l.volume_id WHERE $whereStr ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params,[$limit,$offset]));
    $lessons=$stmt->fetchAll();
}catch(PDOException $e){ $lessons=[]; $total=0; $pages=1; }
?>
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0">آخرین دروس منتشرشده</h2>
<form method="get" class="d-flex gap-2">
<input type="text" name="q" class="form-control form-control-sm" placeholder="جستجو در دروس..." value="<?= sanitize($search) ?>" style="max-width:200px">
<select name="level" class="form-select form-select-sm" style="max-width:130px"><option value="">همه سطوح</option><option value="beginner" <?= $level==='beginner'?'selected':'' ?>>مقدماتی</option><option value="intermediate" <?= $level==='intermediate'?'selected':'' ?>>متوسط</option><option value="advanced" <?= $level==='advanced'?'selected':'' ?>>پیشرفته</option></select>
<button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
</form>
</div>
<?php if(empty($lessons)): ?><div class="text-center py-4 text-muted small">درسی یافت نشد.</div><?php else: ?>
<div class="row g-4">
<?php foreach($lessons as $ls): ?>
<div class="col-md-6 col-lg-3">
<div class="lesson-card h-100">
<div class="lesson-card-img"><?php if($ls['featured_image']): ?><img src="<?= imgUrl($ls['featured_image']) ?>" alt="<?= sanitize($ls['title']) ?>" loading="lazy"><?php else: ?><div class="lesson-img-placeholder"><i class="bi bi-play-circle"></i></div><?php endif; ?><?php if($ls['audio_file']): ?><span class="lesson-audio-badge"><i class="bi bi-headphones"></i> صوت</span><?php endif; ?></div>
<div class="lesson-card-body">
<?php if($ls['collection_title']): ?><span class="lesson-subject"><?= sanitize($ls['collection_title']) ?><?= $ls['volume_title'] ? ' · '.sanitize($ls['volume_title']) : '' ?></span><?php endif; ?>
<h3 class="lesson-card-title"><a href="<?= siteUrl('lesson.php?slug='.urlencode($ls['slug'])) ?>"><?= sanitize($ls['title']) ?></a></h3>
<?php if($ls['teacher']): ?><p class="lesson-teacher"><i class="bi bi-person ms-1"></i><?= sanitize($ls['teacher']) ?> <?php if($ls['lesson_number']): ?>· درس <?= (int)$ls['lesson_number'] ?><?php endif; ?></p><?php endif; ?>
<a href="<?= siteUrl('lesson.php?slug='.urlencode($ls['slug'])) ?>" class="btn btn-sm btn-primary w-100 mt-auto">ورود به درس <i class="bi bi-arrow-left ms-1"></i></a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php if($pages>1): ?><div class="mt-4"><?= paginate($total,$limit,$page, siteUrl('lessons.php?q='.urlencode($search).'&level='.urlencode($level).'&page=%d')) ?></div><?php endif; ?>
<?php endif; ?>

<?php else: ?>
<!-- نمایش یک مجموعه خاص -->
<?php $volumes=getLessonVolumes((int)$activeCollection['id']); if($volumes && !$activeVolume): ?>
<div class="mb-4">
<h2 class="h5">جلدها / بخش‌ها</h2>
<div class="row g-3 mb-4">
<?php foreach($volumes as $vol): $vcount=count(getLessonsByCollection((int)$activeCollection['id'], (int)$vol['id'])); ?>
<div class="col-md-6 col-lg-4">
<div class="card">
<div class="card-body">
<h3 class="h6"><a href="<?= siteUrl('lessons.php?collection='.urlencode($activeCollection['slug']).'&volume='.urlencode($vol['slug'])) ?>"><?= sanitize($vol['title']) ?></a></h3>
<?php if($vol['description']): ?><p class="text-muted small"><?= sanitize(excerpt($vol['description'],100)) ?></p><?php endif; ?>
<span class="text-muted small"><?= $vcount ?> درس</span>
<a href="<?= siteUrl('lessons.php?collection='.urlencode($activeCollection['slug']).'&volume='.urlencode($vol['slug'])) ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">مشاهده درس‌های این بخش</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<?php
// List lessons for this collection/volume
$where=["l.status='published'","l.collection_id=?"]; $params=[(int)$activeCollection['id']];
if($activeVolume){ $where[]="l.volume_id=?"; $params[]=(int)$activeVolume['id']; }
if($search){ $where[]="(l.title ILIKE ? OR l.summary ILIKE ?)"; $s="%$search%"; $params[]=$s; $params[]=$s; }
$whereStr=implode(' AND ',$where);
try{
    $cntStmt=getDB()->prepare("SELECT COUNT(*) FROM lessons l WHERE $whereStr");
    $cntStmt->execute($params); $total=(int)$cntStmt->fetchColumn();
    $pages=(int)ceil($total/$limit);
    $stmt=getDB()->prepare("SELECT l.* FROM lessons l WHERE $whereStr ORDER BY COALESCE(l.lesson_number,9999) ASC, l.sort_order ASC, l.id ASC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params,[$limit,$offset]));
    $lessons=$stmt->fetchAll();
}catch(PDOException $e){ $lessons=[]; $total=0; $pages=1; }
?>
<?php if(empty($lessons)): ?><div class="text-center py-5 text-muted">درسی در این بخش وجود ندارد.</div><?php else: ?>
<div class="row g-4">
<?php foreach($lessons as $ls): ?>
<div class="col-md-6 col-lg-4">
<div class="lesson-card h-100">
<div class="lesson-card-img"><?php if($ls['featured_image']): ?><img src="<?= imgUrl($ls['featured_image']) ?>" alt="<?= sanitize($ls['title']) ?>" loading="lazy"><?php else: ?><div class="lesson-img-placeholder"><i class="bi bi-mortarboard"></i></div><?php endif; ?><?php if($ls['lesson_number']): ?><span class="lesson-level-badge badge bg-dark">درس <?= (int)$ls['lesson_number'] ?></span><?php endif; ?></div>
<div class="lesson-card-body">
<h3 class="lesson-card-title"><a href="<?= siteUrl('lesson.php?slug='.urlencode($ls['slug'])) ?>"><?= sanitize($ls['title']) ?></a></h3>
<?php if($ls['teacher']): ?><p class="lesson-teacher"><i class="bi bi-person ms-1"></i><?= sanitize($ls['teacher']) ?></p><?php endif; ?>
<?php if($ls['summary']): ?><p class="lesson-desc"><?= sanitize(excerpt($ls['summary'],90)) ?></p><?php endif; ?>
<a href="<?= siteUrl('lesson.php?slug='.urlencode($ls['slug'])) ?>" class="btn btn-primary btn-sm w-100 mt-auto">ورود به درس <i class="bi bi-arrow-left ms-1"></i></a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php if($pages>1): ?><div class="mt-4"><?= paginate($total,$limit,$page, siteUrl('lessons.php?collection='.urlencode($activeCollection['slug']).($activeVolume?'&volume='.urlencode($activeVolume['slug']):'').'&page=%d')) ?></div><?php endif; ?>
<?php endif; ?>

<div class="mt-4"><a href="<?= siteUrl('lessons.php') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right ms-1"></i> بازگشت به همه مجموعه‌ها</a></div>

<?php endif; ?>
</div></div>
<?php require_once __DIR__.'/includes/footer.php'; ?>
