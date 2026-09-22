<?php
/**
 * book.php — صفحه کتاب (لندینگ معرفی + فهرست + دانلود + پیوند موضوعی)
 */
require_once __DIR__.'/../includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);
$book = null;
if($slug){
    try{ $stmt=getDB()->prepare("SELECT * FROM books WHERE slug=? LIMIT 1"); $stmt->execute([$slug]); $book=$stmt->fetch(); }catch(PDOException $e){}
}
if(!$book && $id){
    $book=getBookById($id);
}
if(!$book || ($book['status']??'published')!=='published'){
    http_response_code(404);
    $pageTitle='کتاب یافت نشد';
    require __DIR__.'/../includes/header.php';
    echo '<section class="container py-5"><nav class="breadcrumb-bar mb-4"><a href="'.siteUrl('books').'">کتابخانه</a> / یافت نشد</nav><h1>کتاب مورد نظر یافت نشد.</h1><a class="btn btn-primary mt-3" href="'.siteUrl('books').'">کتابخانه</a></section>';
    require __DIR__.'/../includes/footer.php'; exit;
}

// download redirect (no counter)
if(isset($_GET['download'])){
    $type=$_GET['download'];
    $col = $type==='pdf' ? 'pdf_file' : ($type==='word' ? 'word_file' : null);
    if(!$col || empty($book[$col])){ http_response_code(404); exit('فایل مورد نظر موجود نیست.'); }
    $key=storageKey($book[$col] ?? '');
    if(!$key){ http_response_code(404); exit('فایل موجود نیست.'); }
    header('Location: '.storageUrl($key), true, 302); exit;
}

$pageTitle = $book['title'];
$canonicalOverride = bookUrl($book);
$pageDesc  = excerpt($book['description'] ?? $book['toc'] ?? '', 160);
$canonicalUrl = bookUrl($book);
$ogImage   = !empty($book['cover_image']) ? imgUrl($book['cover_image']) : null;
$ogType    = 'book';
$breadcrumbs = [
  ['name'=>'صفحه اصلی','url'=>siteUrl()],
  ['name'=>'کتابخانه','url'=>siteUrl('books')],
  ['name'=>$book['title'],'url'=>$canonicalUrl],
];
$breadcrumbsJsonLd = json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'BreadcrumbList',
  'itemListElement'=>array_map(function($cr,$i){ return ['@type'=>'ListItem','position'=>$i+1,'name'=>$cr['name'],'item'=>$cr['url']]; }, $breadcrumbs, array_keys($breadcrumbs))
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$bookJsonLd = json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'Book',
  'name'=>$book['title'],
  'description'=>excerpt($book['description'] ?? '', 200),
  'image'=>$ogImage ? siteUrl(ltrim($ogImage,'/')) : null,
  'author'=>!empty($book['author']) ? ['@type'=>'Person','name'=>$book['author']] : null,
  'translator'=>!empty($book['translator']) ? ['@type'=>'Person','name'=>$book['translator']] : null,
  'publisher'=>!empty($book['publisher']) ? $book['publisher'] : 'جامعه‌الهدی',
  'datePublished'=>$book['publish_year'] ?? null,
  'numberOfPages'=>$book['pages'] ?? null,
  'url'=>$canonicalUrl,
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

$topics = getTopicsForBook((int)$book['id']);
$related = [];
if($topics){
    try{
        $ids=array_column($topics,'id');
        $in=implode(',', array_fill(0,count($ids),'?'));
        $stmt=getDB()->prepare("SELECT b.* FROM books b JOIN book_topics bt ON bt.book_id=b.id WHERE bt.topic_id IN ($in) AND b.id<>? AND b.status='published' GROUP BY b.id ORDER BY b.created_at DESC LIMIT 6");
        $stmt->execute(array_merge($ids,[(int)$book['id']]));
        $related=$stmt->fetchAll();
    }catch(PDOException $e){ $related=[]; }
}
require __DIR__.'/../includes/header.php';
?>
<script type="application/ld+json"><?= $breadcrumbsJsonLd ?></script>
<script type="application/ld+json"><?= $bookJsonLd ?></script>

<div class="breadcrumb-bar"><div class="container">
<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
<?php foreach($breadcrumbs as $i=>$cr): if($i===count($breadcrumbs)-1): ?><li class="breadcrumb-item active"><?= sanitize($cr['name']) ?></li><?php else: ?><li class="breadcrumb-item"><a href="<?= sanitize($cr['url']) ?>"><?= sanitize($cr['name']) ?></a></li><?php endif; endforeach; ?>
</ol></nav>
</div></div>

<article class="container py-4 py-md-5">
<div class="row g-4 g-lg-5">
<div class="col-lg-4">
<div style="background:#fafaf7;border:1px solid #e8e6dc;border-radius:20px;padding:16px;text-align:center">
<?php if(!empty($book['cover_image'])): ?>
<img src="<?= imgUrl($book['cover_image']) ?>" alt="جلد <?= sanitize($book['title']) ?>" style="width:100%;max-width:360px;aspect-ratio:3/4;object-fit:cover;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.12)" loading="eager" width="400" height="530">
<?php else: ?>
<div style="aspect-ratio:3/4;display:grid;place-items:center;background:#ede8d8;border-radius:14px"><i class="bi bi-book" style="font-size:4rem;color:#b9ad8e"></i></div>
<?php endif; ?>
<div class="mt-4 d-grid gap-2">
<?php if(!empty($book['pdf_file'])): ?><a href="<?= bookUrl($book).'?download=pdf' ?>" class="btn btn-primary"><i class="bi bi-file-pdf ms-2"></i> دانلود PDF</a><?php endif; ?>
<?php if(!empty($book['word_file'])): ?><a href="<?= bookUrl($book).'?download=word' ?>" class="btn btn-outline-primary"><i class="bi bi-file-word ms-2"></i> دانلود Word</a><?php endif; ?>
</div>
<?php if($topics): ?>
<div class="text-start mt-4">
<div class="small fw-bold mb-2" style="color:var(--jhd-primary)"><i class="bi bi-tags ms-1"></i> موضوعات</div>
<div class="d-flex flex-wrap gap-2">
<?php foreach($topics as $tp): ?><a href="<?= topicUrl($tp) ?>" class="badge rounded-pill" style="background:#f0ece3;color:#5b4a1a;border:1px solid #e8e6dc"><?= sanitize($tp['name']) ?></a><?php endforeach; ?>
</div>
</div>
<?php endif; ?>
</div>

<?php if(!empty($book['author']) || !empty($book['publisher'])): ?>
<div class="mt-4 p-3 rounded-4" style="background:#fff;border:1px solid #eee">
<?php if(!empty($book['author'])): ?><div class="d-flex justify-content-between small py-1"><span class="text-muted">نویسنده</span><strong><?= sanitize($book['author']) ?></strong></div><?php endif; ?>
<?php if(!empty($book['translator'])): ?><div class="d-flex justify-content-between small py-1"><span class="text-muted">مترجم</span><strong><?= sanitize($book['translator']) ?></strong></div><?php endif; ?>
<?php if(!empty($book['publisher'])): ?><div class="d-flex justify-content-between small py-1"><span class="text-muted">ناشر</span><strong><?= sanitize($book['publisher']) ?></strong></div><?php endif; ?>
<?php if(!empty($book['publish_year'])): ?><div class="d-flex justify-content-between small py-1"><span class="text-muted">سال نشر</span><strong><?= sanitize($book['publish_year']) ?></strong></div><?php endif; ?>
<?php if(!empty($book['pages'])): ?><div class="d-flex justify-content-between small py-1"><span class="text-muted">تعداد صفحات</span><strong><?= (int)$book['pages'] ?></strong></div><?php endif; ?>
</div>
<?php endif; ?>
</div>

<div class="col-lg-8">
<span class="jhd-eyebrow">کتابخانه دیجیتال</span>
<h1 style="font-size:1.85rem;font-weight:900;color:var(--jhd-primary);line-height:1.35;margin:10px 0 8px"><?= sanitize($book['title']) ?></h1>
<?php if(!empty($book['author'])): ?><p class="text-muted mb-3"><i class="bi bi-person ms-1"></i> <?= sanitize($book['author']) ?><?= !empty($book['translator']) ? ' — ترجمهٔ '.sanitize($book['translator']) : '' ?></p><?php endif; ?>

<?php if(!empty($book['description'])): ?>
<section class="mb-4">
<h2 class="h6 fw-bold" style="color:var(--jhd-primary)"><i class="bi bi-info-circle ms-2"></i> معرفی کتاب</h2>
<div class="post-content" style="line-height:2;color:#2b2b2b"><?= nl2br(sanitize($book['description'])) ?></div>
</section>
<?php endif; ?>

<?php if(!empty($book['toc'])): ?>
<section class="mb-4 p-3 p-md-4 rounded-4" style="background:#fafaf7;border:1px solid #e8e6dc">
<h2 class="h6 fw-bold mb-3" style="color:var(--jhd-primary)"><i class="bi bi-list-ol ms-2"></i> فهرست مطالب</h2>
<div style="white-space:pre-wrap;line-height:1.9;color:#3a3a3a;font-size:.93rem"><?= sanitize($book['toc']) ?></div>
</section>
<?php endif; ?>

<?php if($topics): ?>
<section class="mb-4">
<h2 class="h6 fw-bold" style="color:var(--jhd-primary)"><i class="bi bi-diagram-3 ms-2"></i> پیوندهای داخلی</h2>
<div class="d-flex flex-wrap gap-2">
<?php foreach($topics as $tp): ?><a href="<?= topicUrl($tp) ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><?= sanitize($tp['name']) ?></a><?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mt-4">
<a href="<?= siteUrl('books') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-right ms-1"></i> بازگشت به کتابخانه</a>
<?php if($topics): $firstTopic=$topics[0]; ?><a href="<?= topicUrl($firstTopic) ?>" class="btn btn-outline-primary">مشاهده در موضوع <?= sanitize($firstTopic['name']) ?></a><?php endif; ?>
</div>
</div>
</div>

<?php if($related): ?>
<section class="mt-5">
<h2 class="h5 fw-bold mb-3" style="color:var(--jhd-primary)"><i class="bi bi-collection ms-2"></i> کتاب‌های مرتبط</h2>
<div class="row g-3">
<?php foreach($related as $rb):
  $rbUrl=bookUrl($rb);
?>
<div class="col-6 col-md-4 col-lg-2">
<a href="<?= $rbUrl ?>" style="text-decoration:none;color:inherit">
<div style="border:1px solid #e8e6dc;border-radius:14px;overflow:hidden;background:#fff">
<?php if(!empty($rb['cover_image'])): ?><img src="<?= imgUrl($rb['cover_image']) ?>" style="width:100%;aspect-ratio:3/4;object-fit:cover;display:block" loading="lazy" alt="<?= sanitize($rb['title']) ?>"><?php endif; ?>
<div class="p-2"><div class="small fw-bold" style="line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= sanitize($rb['title']) ?></div><?php if(!empty($rb['author'])): ?><div class="text-muted" style="font-size:.75rem"><?= sanitize($rb['author']) ?></div><?php endif; ?></div>
</div>
</a>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
</article>

<?php require __DIR__.'/../includes/footer.php'; ?>
