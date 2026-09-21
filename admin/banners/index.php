<?php
/**
 * admin/banners/index.php — مدیریت بنر ویژه اعلان (مدیریت بدون کدنویسی، نمایش در صفحه اصلی)
 */
$adminTitle='بنر ویژه';
require_once __DIR__ . '/../includes/header.php';

$db=getDB();
$error='';

// Ensure table exists (migrate guard)
try{ $db->query("SELECT 1 FROM featured_banners LIMIT 1"); }catch(PDOException $e){
    $db->exec("CREATE TABLE IF NOT EXISTS featured_banners (id SERIAL PRIMARY KEY, title VARCHAR(255), description TEXT, image VARCHAR(512), link_url VARCHAR(512), button_text VARCHAR(100), is_active BOOLEAN DEFAULT TRUE, sort_order INT DEFAULT 0, created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW())");
}

if(isset($_POST['delete'])){
    if(!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) $error='خطای امنیتی.';
    else{ $db->prepare("DELETE FROM featured_banners WHERE id=?")->execute([(int)$_POST['delete']]); $_SESSION['flash_msg']='بنر حذف شد.'; $_SESSION['flash_type']='success'; redirect(siteUrl('admin/banners/')); }
}

if($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['delete'])){
    if(!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) $error='خطای امنیتی.';
    else{
        $title=trim($_POST['title'] ?? '');
        $desc=trim($_POST['description'] ?? '');
        $link=trim($_POST['link_url'] ?? '');
        $btn=trim($_POST['button_text'] ?? '');
        $active=isset($_POST['is_active'])?1:0;
        $sort=(int)($_POST['sort_order'] ?? 0);
        $editId=(int)($_POST['edit_id'] ?? 0);
        $image='';
        if($editId){ $s=$db->prepare("SELECT image FROM featured_banners WHERE id=?"); $s->execute([$editId]); $image=$s->fetchColumn() ?: ''; }
        if(!empty($_FILES['image']['name'])){ $up=uploadImage($_FILES['image'],'banners'); if($up) $image=$up; }
        if(!$title) $error='عنوان بنر الزامی است.';
        if(!$error){
            if($editId){ $db->prepare("UPDATE featured_banners SET title=?, description=?, image=?, link_url=?, button_text=?, is_active=?, sort_order=?, updated_at=NOW() WHERE id=?")->execute([$title,$desc,$image,$link,$btn,$active,$sort,$editId]); }
            else{ $db->prepare("INSERT INTO featured_banners (title,description,image,link_url,button_text,is_active,sort_order) VALUES (?,?,?,?,?,?,?)")->execute([$title,$desc,$image,$link,$btn,$active,$sort]); }
            $_SESSION['flash_msg']='بنر ذخیره شد.'; $_SESSION['flash_type']='success'; redirect(siteUrl('admin/banners/'));
        }
    }
}

$edit=null; if(!empty($_GET['edit'])){ $s=$db->prepare("SELECT * FROM featured_banners WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$banners=$db->query("SELECT * FROM featured_banners ORDER BY sort_order, id DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<h5 class="mb-0"><i class="bi bi-megaphone ms-2"></i> بنر ویژه (اعلان خاص)</h5>
<span class="small text-muted">برای نمایش در صفحهٔ اصلی — بالاترین بنر ویژه پیش از هیرو</span>
</div>
<?php if($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>

<div class="row g-4">
<div class="col-lg-4">
<div class="admin-card">
<div class="admin-card-header"><?= $edit?'<i class="bi bi-pencil ms-2"></i> ویرایش':'<i class="bi bi-plus-circle ms-2"></i> بنر جدید' ?></div>
<div class="admin-card-body">
<form method="post" enctype="multipart/form-data" class="admin-form">
<?= csrfField() ?>
<?php if($edit): ?><input type="hidden" name="edit_id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
<div class="mb-3"><label class="form-label">عنوان <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" value="<?= sanitize($edit['title'] ?? '') ?>" required></div>
<div class="mb-3"><label class="form-label">توضیح</label><textarea name="description" class="form-control" rows="2"><?= sanitize($edit['description'] ?? '') ?></textarea></div>
<div class="mb-3"><label class="form-label">تصویر اختیاری</label><input type="file" name="image" class="form-control" accept="image/*"><?php if($edit && $edit['image']): ?><img src="<?= imgUrl($edit['image']) ?>" style="max-width:120px;border-radius:8px;margin-top:8px"><?php endif; ?></div>
<div class="mb-3"><label class="form-label">لینک (اختیاری)</label><input type="url" name="link_url" class="form-control" value="<?= sanitize($edit['link_url'] ?? '') ?>" placeholder="https://..."></div>
<div class="mb-3"><label class="form-label">متن دکمه</label><input type="text" name="button_text" class="form-control" value="<?= sanitize($edit['button_text'] ?? '') ?>" placeholder="مثلاً: مشاهده"></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="form-label">ترتیب</label><input type="number" name="sort_order" class="form-control" value="<?= $edit['sort_order'] ?? 0 ?>"></div><div class="col-6 d-flex align-items-end"><label class="form-check"><input type="checkbox" name="is_active" class="form-check-input" <?= ($edit['is_active']??1)?'checked':'' ?>> نمایش در سایت</label></div></div>
<div class="d-flex gap-2"><button class="btn btn-success flex-grow-1"><i class="bi bi-<?= $edit?'save':'plus-circle' ?> ms-1"></i> <?= $edit?'ذخیره':'ایجاد' ?></button><?php if($edit): ?><a href="<?= siteUrl('admin/banners/') ?>" class="btn btn-outline-secondary">انصراف</a><?php endif; ?></div>
</form>
</div>
</div>
</div>
<div class="col-lg-8">
<div class="admin-card"><div class="admin-card-body p-0">
<?php if(empty($banners)): ?><div class="text-center py-5 text-muted">بنری وجود ندارد.</div>
<?php else: ?>
<div class="table-responsive"><table class="table admin-table mb-0">
<thead><tr><th>عنوان</th><th>وضعیت</th><th>لینک</th><th>عملیات</th></tr></thead>
<tbody>
<?php foreach($banners as $b): ?>
<tr>
<td><strong><?= sanitize($b['title']) ?></strong><?php if($b['description']): ?><div class="text-muted small"><?= sanitize(mb_strimwidth($b['description'],0,60,'...')) ?></div><?php endif; ?></td>
<td><?= $b['is_active']?'<span class="badge bg-success">فعال</span>':'<span class="badge bg-secondary">غیرفعال</span>' ?></td>
<td class="small"><?= $b['link_url']?'<a href="'.sanitize($b['link_url']).'" target="_blank"><i class="bi bi-link"></i></a>':'—' ?></td>
<td><div class="d-flex gap-1"><a href="<?= siteUrl('admin/banners/?edit='.$b['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a><form method="post" style="display:inline" onsubmit="return confirm('حذف بنر؟')"><input type="hidden" name="delete" value="<?= $b['id'] ?>"><?= csrfField() ?><button class="btn btn-sm btn-outline-danger py-0 px-2"><i class="bi bi-trash"></i></button></form></div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table></div>
<?php endif; ?>
</div></div>
</div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
