<?php
/**
 * admin/posts/index.php — مدیریت مطالب — اصلاح‌شده
 */
$adminTitle = 'مدیریت مطالب';
require_once __DIR__ . '/../includes/header.php';

$type   = trim($_GET['type']   ?? '');
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['q']      ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$db     = getDB();
$where  = ['1=1'];
$params = [];

if ($type)   { $where[] = "p.post_type = ?"; $params[] = $type; }
if ($status) { $where[] = "p.status = ?";    $params[] = $status; }
if ($search) { $where[] = "p.title LIKE ?";  $params[] = "%$search%"; }

$whereStr = implode(' AND ', $where);

// تعداد کل
$countStmt = $db->prepare("SELECT COUNT(*) FROM posts p WHERE $whereStr");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// لیست مطالب
$stmt = $db->prepare(
    "SELECT p.*, c.name AS cat_name, u.full_name AS author_name
     FROM posts p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN users u ON u.id = p.author_id
     WHERE $whereStr
     ORDER BY p.created_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$limit, $offset]));
$posts = $stmt->fetchAll();

$pages   = (int)ceil($total / $limit);
$urlBase = '?type=' . urlencode($type) . '&status=' . urlencode($status) . '&q=' . urlencode($search) . '&page=';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">مطالب (<?= number_format($total) ?>)</h5>
    <a href="<?= siteUrl('admin/posts/create.php') ?>" class="btn btn-success"><i class="bi bi-plus-circle ms-1"></i>مطلب جدید</a>
</div>

<!-- فیلترها -->
<form method="get" class="admin-card mb-4">
    <div class="admin-card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="جستجو در عنوان..." value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">همه انواع</option>
                    <?php foreach (['news'=>'اخبار','article'=>'مقالات','announcement'=>'اطلاعیه‌ها','speech'=>'سخنرانی‌ها','program'=>'برنامه‌ها','religious'=>'فعالیت مذهبی'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $type===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="published" <?= $status==='published'?'selected':'' ?>>منتشرشده</option>
                    <option value="draft"     <?= $status==='draft'?'selected':'' ?>>پیش‌نویس</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search ms-1"></i>فیلتر</button>
            </div>
        </div>
    </div>
</form>

<!-- جدول مطالب -->
<div class="admin-card">
    <div class="admin-card-body p-0">
        <?php if (empty($posts)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-file-text display-4 d-block mb-3 opacity-25"></i>
            <p>مطلبی یافت نشد.</p>
            <a href="<?= siteUrl('admin/posts/create.php') ?>" class="btn btn-success">مطلب جدید ایجاد کنید</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>عنوان</th>
                        <th>نوع</th>
                        <th>دسته‌بندی</th>
                        <th>وضعیت</th>
                        <th>بازدید</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $i => $p): ?>
                    <tr>
                        <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                        <td>
                            <?php if ($p['featured_image']): ?>
                            <img src="<?= imgUrl($p['featured_image']) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:6px;margin-left:8px" alt="" loading="lazy">
                            <?php endif; ?>
                            <a href="<?= siteUrl('admin/posts/edit.php?id=' . $p['id']) ?>" class="fw-bold text-dark text-decoration-none">
                                <?= sanitize(mb_strimwidth($p['title'], 0, 50, '...')) ?>
                            </a>
                        </td>
                        <td><?= postTypeBadge($p['post_type']) ?></td>
                        <td class="text-muted small"><?= sanitize($p['cat_name'] ?: '—') ?></td>
                        <td>
                            <span class="badge <?= $p['status']==='published'?'bg-success':'bg-secondary' ?>">
                                <?= $p['status']==='published'?'منتشر':'پیش‌نویس' ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= number_format($p['views']) ?></td>
                        <td class="text-muted" style="font-size:.78rem;white-space:nowrap"><?= persianDate($p['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= siteUrl('admin/posts/edit.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="ویرایش"><i class="bi bi-pencil"></i></a>
                                <a href="<?= siteUrl('post.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="مشاهده"><i class="bi bi-eye"></i></a>
                                <a href="<?= siteUrl('admin/posts/delete.php?id=' . $p['id'] . '&' . CSRF_TOKEN_NAME . '=' . urlencode(generateCsrfToken())) ?>"
                                   class="btn btn-sm btn-outline-danger py-0 px-2"
                                   title="حذف"
                                   data-confirm="آیا از حذف «<?= sanitize($p['title']) ?>» اطمینان دارید؟">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- صفحه‌بندی -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="<?= $urlBase . $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
