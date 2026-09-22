<?php
/**
 * admin/topics/index.php — مدیریت موضوعات (ستون فقرات سایت)
 * قابلیت: ایجاد موضوع اصلی، زیرموضوع، زیرِ زیرموضوع، ویرایش، ترتیب، فعال/غیرفعال، تصویر کاور، توضیح
 */
$adminTitle='مدیریت موضوعات';
require_once __DIR__ . '/../includes/header.php';

$db=getDB();
$error=''; $success='';

// حذف
if(isset($_POST['delete'])){
    if(!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) $error='خطای امنیتی.';
    else{
        $delId=(int)$_POST['delete'];
        try{
            // check has children or linked content
            $cntChild=$db->prepare("SELECT COUNT(*) FROM topics WHERE parent_id=?");
            $cntChild->execute([$delId]); $hasChild=(int)$cntChild->fetchColumn();
            if($hasChild>0) $error='این موضوع دارای زیرموضوع است. ابتدا زیرموضوع‌ها را حذف یا منتقل کنید.';
            else{
                // check linked posts
                $linked=$db->prepare("SELECT COUNT(*) FROM post_topics WHERE topic_id=?"); $linked->execute([$delId]); $c=(int)$linked->fetchColumn();
                if($c>0) $error="این موضوع به $c مطلب متصل است و قابل حذف نیست. ابتدا اتصال‌ها را حذف کنید.";
                else{
                    $db->prepare("DELETE FROM topics WHERE id=?")->execute([$delId]);
                    $success='موضوع حذف شد.';
                    $_SESSION['flash_msg']=$success; $_SESSION['flash_type']='success';
                    redirect(siteUrl('admin/topics/'));
                }
            }
        }catch(PDOException $e){ $error='خطا در حذف.'; error_log($e->getMessage()); }
    }
}

// ذخیره (ایجاد/ویرایش)
if($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['delete'])){
    if(!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) $error='خطای امنیتی.';
    else{
        $name=trim($_POST['name'] ?? '');
        $desc=trim($_POST['description'] ?? '');
        $intro=trim($_POST['intro'] ?? '');
        // parent_id is optional: an absent/empty/zero value means "root topic".
        $parent_id = (int)($_POST['parent_id'] ?? 0) > 0 ? (int)$_POST['parent_id'] : null;
        $sort=(int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $editId=(int)($_POST['edit_id'] ?? 0);
        if(!$name) $error='نام موضوع الزامی است.';
        else{
            // validate parent not self descendant
            if($editId && $parent_id===$editId) $error='موضوع نمی‌تواند والد خودش باشد.';
            else{
                $slug=uniqueSlug('topics',$name,$editId);
                $cover=$editId ? (getTopicById($editId)['cover_image'] ?? '') : '';
                if(!empty($_FILES['cover_image']['name'])){
                    $up=uploadImage($_FILES['cover_image'],'topics');
                    if($up) $cover=$up; else $error='خطا در آپلود کاور. فرمت‌های مجاز: JPG، PNG، WebP';
                }
                if(!$error){
                    if($editId){
                        $db->prepare("UPDATE topics SET parent_id=?, name=?, slug=?, description=?, intro=?, cover_image=?, sort_order=?, is_active=?, is_featured=?, updated_at=NOW() WHERE id=?")
                           ->execute([$parent_id,$name,$slug,$desc,$intro,$cover,$sort,$is_active,$is_featured,$editId]);
                        $success='موضوع ویرایش شد.';
                    } else {
                        $db->prepare("INSERT INTO topics (parent_id,name,slug,description,intro,cover_image,sort_order,is_active,is_featured) VALUES (?,?,?,?,?,?,?,?,?)")
                           ->execute([$parent_id,$name,$slug,$desc,$intro,$cover,$sort,$is_active,$is_featured]);
                        $success='موضوع جدید ایجاد شد.';
                    }
                    $_SESSION['flash_msg']=$success; $_SESSION['flash_type']='success';
                    redirect(siteUrl('admin/topics/'));
                }
            }
        }
    }
}

$editTopic=null;
if(!empty($_GET['edit'])){
    $editTopic=getTopicById((int)$_GET['edit']);
}
$allTopics=getTopics(['limit'=>200]);
$tree=getTopicTree();

// For parent select list flatten with depth
function flattenTopics($nodes,$depth=0,&$out=[]){
    foreach($nodes as $n){
        $out[]=['id'=>$n['id'],'name'=>str_repeat('— ',$depth).$n['name'],'depth'=>$depth];
        if(!empty($n['children'])) flattenTopics($n['children'],$depth+1,$out);
    }
    return $out;
}
$flat=[];
flattenTopics($tree,0,$flat);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<h5 class="mb-0"><i class="bi bi-diagram-3 ms-2"></i> موضوعات (<?= count($allTopics) ?>)</h5>
<a href="<?= siteUrl('topics') ?>" target="_blank" class="btn btn-sm btn-outline-primary">مشاهده در سایت <i class="bi bi-box-arrow-up-left ms-1"></i></a>
</div>

<?php if($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>

<div class="row g-4">
<div class="col-lg-4">
<div class="admin-card">
<div class="admin-card-header"><?= $editTopic?'<i class="bi bi-pencil ms-2"></i> ویرایش موضوع':'<i class="bi bi-plus-circle ms-2"></i> موضوع جدید' ?></div>
<div class="admin-card-body">
<form method="post" enctype="multipart/form-data" class="admin-form">
<?= csrfField() ?>
<?php if($editTopic): ?><input type="hidden" name="edit_id" value="<?= (int)$editTopic['id'] ?>"><?php endif; ?>
<div class="mb-3"><label class="form-label">نام موضوع <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" value="<?= sanitize($editTopic['name'] ?? '') ?>" required placeholder="مثلاً: مهدویت"></div>
<div class="mb-3"><label class="form-label">والد (برای زیرموضوع)</label>
<select name="parent_id" class="form-select"><option value="">— بدون والد (موضوع اصلی) —</option>
<?php foreach($flat as $f): if($editTopic && $f['id']==$editTopic['id']) continue; ?>
<option value="<?= $f['id'] ?>" <?= ($editTopic['parent_id']??'')==$f['id']?'selected':'' ?>><?= sanitize($f['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3"><label class="form-label">معرفی کوتاه (intro)</label><textarea name="intro" class="form-control" rows="2" placeholder="توضیح کوتاه برای کارت موضوع"><?= sanitize($editTopic['intro'] ?? '') ?></textarea></div>
<div class="mb-3"><label class="form-label">توضیح کامل</label><textarea name="description" class="form-control" rows="3" placeholder="معرفی موضوع برای صفحه لندینگ"><?= sanitize($editTopic['description'] ?? '') ?></textarea></div>
<div class="mb-3"><label class="form-label">کاور / تصویر موضوع</label><input type="file" name="cover_image" class="form-control" accept="image/*"><div class="form-text">JPG/PNG/WebP — حداکثر 20MB</div>
<?php if($editTopic && $editTopic['cover_image']): ?><img src="<?= imgUrl($editTopic['cover_image']) ?>" style="max-width:120px;border-radius:8px;margin-top:8px" loading="lazy"><?php endif; ?></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="form-label">ترتیب نمایش</label><input type="number" name="sort_order" class="form-control" value="<?= $editTopic['sort_order'] ?? 0 ?>"></div>
<div class="col-6 d-flex flex-column gap-2 justify-content-end">
<label class="form-check"><input type="checkbox" name="is_active" class="form-check-input" <?= ($editTopic['is_active']??1)?'checked':'' ?>> فعال</label>
<label class="form-check"><input type="checkbox" name="is_featured" class="form-check-input" <?= ($editTopic['is_featured']??0)?'checked':'' ?>> ویژه در صفحه اصلی</label>
</div>
</div>
<div class="d-flex gap-2">
<button class="btn btn-success flex-grow-1"><i class="bi bi-<?= $editTopic?'save':'plus-circle' ?> ms-1"></i> <?= $editTopic?'ذخیره':'ایجاد' ?></button>
<?php if($editTopic): ?><a href="<?= siteUrl('admin/topics/') ?>" class="btn btn-outline-secondary">انصراف</a><?php endif; ?>
</div>
</form>
</div>
</div>
</div>

<div class="col-lg-8">
<div class="admin-card"><div class="admin-card-body p-0">
<?php if(empty($tree)): ?><div class="text-center py-5 text-muted">موضوعی وجود ندارد.</div>
<?php else: ?>
<div class="table-responsive"><table class="table admin-table mb-0">
<thead><tr><th>نام</th><th>والد</th><th>وضعیت</th><th>ترتیب</th><th>مطالب</th><th>عملیات</th></tr></thead>
<tbody>
<?php
function renderRows($nodes,$depth=0){
    foreach($nodes as $n){
        $cnt=countPostsByTopic((int)$n['id']);
        $childCnt=count($n['children'] ?? []);
        echo '<tr'.($depth?' style="background:'.($depth===1?'#fdfdfd':'#f8f7f2').'"':'').'>';
        echo '<td style="padding-inline-start:'.(12+$depth*18).'px"><strong>'.sanitize($n['name']).'</strong>';
        if($n['is_featured']) echo ' <span class="badge bg-warning text-dark" style="font-size:.65rem">ویژه</span>';
        if($n['cover_image']) echo ' <i class="bi bi-image text-success" title="دارای کاور"></i>';
        echo '<br><code style="font-size:.68rem;color:#888">'.sanitize($n['slug']).'</code>';
        if($n['intro']) echo '<div class="text-muted" style="font-size:.75rem">'.sanitize(mb_strimwidth($n['intro'],0,60,'...')).'</div>';
        echo '</td>';
        echo '<td class="text-muted small">'.($depth===0?'—': sanitize($GLOBALS['parentNames'][$n['parent_id']] ?? '')).'</td>';
        echo '<td>'.($n['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>').'</td>';
        echo '<td>'.$n['sort_order'].'</td>';
        echo '<td><span class="badge bg-light text-dark border">'.$cnt.'</span></td>';
        echo '<td><div class="d-flex gap-1">';
        echo '<a href="'.siteUrl('admin/topics/?edit='. $n['id']).'" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>';
        echo '<a href="'.siteUrl('topic?slug='.urlencode($n['slug'])).'" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2"><i class="bi bi-eye"></i></a>';
        echo '<form method="post" style="display:inline" onsubmit="return confirm(\'حذف موضوع؟\')"><input type="hidden" name="delete" value="'.$n['id'].'">'.csrfField().'<button class="btn btn-sm btn-outline-danger py-0 px-2"><i class="bi bi-trash"></i></button></form>';
        echo '</div></td>';
        echo '</tr>';
        if(!empty($n['children'])) renderRows($n['children'],$depth+1);
    }
}
$parentNames=[]; foreach($allTopics as $t) $parentNames[$t['id']]=$t['name'];
$GLOBALS['parentNames']=$parentNames;
renderRows($tree);
?>
</tbody>
</table></div>
<?php endif; ?>
</div></div>
</div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
