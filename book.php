<?php
require_once __DIR__.'/includes/functions.php';
$id=(int)($_GET['id']??0);
$stmt=getDB()->prepare('SELECT * FROM books WHERE id=?');$stmt->execute([$id]);$book=$stmt->fetch();
if(!$book){
    http_response_code(404);$pageTitle='کتاب یافت نشد';require __DIR__.'/includes/header.php';
    echo '<section class="container py-5"><h1>کتاب مورد نظر یافت نشد.</h1></section>';
    require __DIR__.'/includes/footer.php';exit;
}
if(isset($_GET['download'])){
    $type=$_GET['download'];
    $column=['pdf'=>'pdf_file','word'=>'word_file'][$type]??null;
    $key=$column?storageKey($book[$column]??''):'';
    if(!$key){http_response_code(404);exit('فایل مورد نظر موجود نیست.');}
    getDB()->prepare('UPDATE books SET downloads=downloads+1 WHERE id=?')->execute([$id]);
    // Only our configured storage origin is allowed; request data never chooses a remote URL.
    header('Location: '.storageUrl($key),true,302);exit;
}
$pageTitle=$book['title'];$pageDesc=excerpt($book['description']??'',160);
require __DIR__.'/includes/header.php';
?>
<article class="container py-5"><nav aria-label="مسیر صفحه" class="mb-4"><a href="<?= siteUrl('books.php') ?>">کتابخانه</a> / <?= sanitize($book['title']) ?></nav>
<div class="row g-5"><div class="col-md-4"><img src="<?= imgUrl($book['cover_image']??'') ?>" class="img-fluid rounded" alt="جلد <?= sanitize($book['title']) ?>" width="400" height="520" style="object-fit:contain;background:var(--jhd-surface)"></div>
<div class="col-md-8"><span class="jhd-eyebrow">کتابخانه دیجیتال جامعه‌الهدی</span><h1 class="h2 my-3"><?= sanitize($book['title']) ?></h1><p class="text-muted"><time datetime="<?= sanitize($book['created_at']) ?>"><?= persianDate($book['created_at']) ?></time> · <?= number_format((int)$book['downloads']) ?> دریافت</p>
<div class="post-content my-4"><?= nl2br(sanitize($book['description']??'')) ?></div>
<div class="d-flex flex-wrap gap-3"><?php foreach(['pdf'=>'دریافت PDF','word'=>'دریافت Word'] as $type=>$label): if(!empty($book[$type.'_file'])): ?><a class="jhd-button" href="<?= siteUrl('book.php?id='.$id.'&download='.$type) ?>"><?= $label ?><i class="bi bi-download"></i></a><?php endif; endforeach; ?></div>
<p class="small text-muted mt-3">فایل را با نرم‌افزار به‌روز و معتبر باز کنید.</p>
</div></div>
</article>
<?php require __DIR__.'/includes/footer.php'; ?>
