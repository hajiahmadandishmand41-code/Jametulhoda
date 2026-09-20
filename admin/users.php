<?php
$adminTitle='مدیریت کاربران';
require_once __DIR__.'/includes/header.php';
requireRole(['superadmin']);
$db=getDB(); $error=''; $success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    requirePostCsrf();
    $id=(int)($_POST['id']??0);
    $username=trim($_POST['username']??''); $name=trim($_POST['full_name']??'');
    $email=trim($_POST['email']??''); $password=$_POST['password']??'';
    $role=$_POST['role']??'editor'; $active=isset($_POST['is_active'])?1:0;
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/D',$username) || mb_strlen($name)>120 || strlen($email)>180 || ($email && !filter_var($email,FILTER_VALIDATE_EMAIL))) $error='اطلاعات واردشده معتبر نیست.';
    elseif (!in_array($role,['editor','admin','superadmin'],true)) $error='نقش معتبر نیست.';
    elseif ((!$id || $password!=='') && strlen($password)<14) $error='رمز عبور باید حداقل ۱۴ کاراکتر باشد.';
    elseif ($id===(int)$admin['id'] && (!$active || $role!=='superadmin')) $error='غیرفعال‌سازی یا کاهش دسترسی حساب فعلی مجاز نیست.';
    else {
        try {
            if ($id) {
                $stmt=$db->prepare('UPDATE users SET username=?,full_name=?,email=?,role=?,is_active=? WHERE id=?');
                $stmt->execute([$username,$name,$email,$role,$active,$id]);
                if ($password!=='') $db->prepare('UPDATE users SET password=?,auth_version=auth_version+1 WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
            } else $db->prepare('INSERT INTO users (username,full_name,email,role,is_active,password) VALUES (?,?,?,?,?,?)')->execute([$username,$name,$email,$role,$active,password_hash($password,PASSWORD_DEFAULT)]);
            $success='اطلاعات کاربر ذخیره شد.';
        } catch (PDOException $e) { $error='ذخیره انجام نشد؛ نام کاربری باید یکتا باشد.'; }
    }
}
$edit=null;
if (!empty($_GET['edit'])) { $s=$db->prepare('SELECT id,username,full_name,email,role,is_active FROM users WHERE id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch(); }
$users=$db->query('SELECT id,username,full_name,role,is_active FROM users ORDER BY id DESC LIMIT 200')->fetchAll();
?>
<h1 class="h4">مدیریت کاربران</h1><p class="text-muted">حساب‌های این بخش مخصوص کارکنان هستند. حذف اطلاعات انجام نمی‌شود؛ دسترسی کاربر را می‌توانید غیرفعال کنید.</p>
<?php if($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
<form method="post" class="admin-card p-4 mb-4 admin-form">
<?= csrfField() ?><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="row g-3">
<?php foreach(['username'=>'نام کاربری (لاتین)','full_name'=>'نام کامل','email'=>'ایمیل'] as $field=>$label): ?><div class="col-md-4"><label for="user-<?= $field ?>"><?= $label ?></label><input id="user-<?= $field ?>" class="form-control" name="<?= $field ?>" value="<?= sanitize($edit[$field]??'') ?>" <?= $field==='username'?'required':'' ?>></div><?php endforeach; ?>
<div class="col-md-6"><label for="user-password">رمز عبور <?= $edit?'(خالی بگذارید تا تغییر نکند)':'' ?></label><input id="user-password" class="form-control" type="password" name="password" minlength="14" autocomplete="new-password"></div>
<div class="col-md-6"><label for="user-role">نقش</label><select id="user-role" class="form-select" name="role"><?php foreach(['editor'=>'ویراستار','admin'=>'مدیر','superadmin'=>'مدیر ارشد'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($edit['role']??'editor')===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label><input type="checkbox" name="is_active" <?= ($edit['is_active']??1)?'checked':'' ?>> حساب فعال است</label><button class="btn btn-primary">ذخیره کاربر</button></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr><th>نام کاربری</th><th>نام</th><th>نقش</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($users as $user): ?><tr><td><?= sanitize($user['username']) ?></td><td><?= sanitize($user['full_name']) ?></td><td><?= sanitize($user['role']) ?></td><td><?= $user['is_active']?'فعال':'غیرفعال' ?></td><td><a href="?edit=<?= (int)$user['id'] ?>">ویرایش</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php require __DIR__.'/includes/footer.php'; ?>
