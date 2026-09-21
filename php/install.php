<?php
/**
 * Browser installer for shared hosting / InfinityFree.
 * Creates a private config/local.php and applies the MySQL schema.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function installerEscape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function installerSplitSql(string $sql): array {
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $out = [];
    $buffer = '';
    $quote = null;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        if ($quote !== null) {
            $buffer .= $ch;
            if ($ch === $quote) {
                if ($next === $quote) {
                    $buffer .= $next;
                    $i++;
                } elseif ($i === 0 || $sql[$i - 1] !== '\\') {
                    $quote = null;
                }
            }
            continue;
        }

        // Skip -- line comments so a semicolon inside a comment never splits
        // a statement mid-comment.
        if ($ch === '-' && $next === '-' && ($i + 2 >= $len || $sql[$i + 2] === ' ' || $sql[$i + 2] === "\t")) {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }
        if ($ch === '#') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }

        if ($ch === "'" || $ch === '"' || $ch === chr(96)) {
            $quote = $ch;
            $buffer .= $ch;
            continue;
        }

        if ($ch === ';') {
            $statement = trim($buffer);
            if ($statement !== '') $out[] = $statement;
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $statement = trim($buffer);
    if ($statement !== '') $out[] = $statement;
    return $out;
}

$localPath = __DIR__ . '/../config/local.php';
$lockPath = __DIR__ . '/../config/install.lock';
$alreadyInstalled = is_file($lockPath);

$defaults = [
    'db_host' => env_value('DB_HOST', 'sql304.infinityfree.com'),
    'db_port' => env_value('DB_PORT', '3306'),
    'db_name' => env_value('DB_NAME', 'if0_42959770_jametulhoda'),
    'db_user' => env_value('DB_USER', 'if0_42959770'),
    'site_url' => env_value('SITE_URL', ''),
    'admin_username' => env_value('ADMIN_USERNAME', 'admin'),
    'admin_email' => env_value('ADMIN_EMAIL', env_value('SITE_EMAIL', 'hajiahmads299@gmail.com')),
];

$error = '';
$success = '';

if (!$alreadyInstalled && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $host = trim((string)($_POST['db_host'] ?? $defaults['db_host']));
    $port = (int)($_POST['db_port'] ?? $defaults['db_port']);
    $name = trim((string)($_POST['db_name'] ?? $defaults['db_name']));
    $user = trim((string)($_POST['db_user'] ?? $defaults['db_user']));
    $pass = (string)($_POST['db_pass'] ?? '');
    $siteUrl = rtrim(trim((string)($_POST['site_url'] ?? '')), '/');
    $adminUsername = trim((string)($_POST['admin_username'] ?? $defaults['admin_username']));
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $adminName = trim((string)($_POST['admin_name'] ?? 'مدیر سایت'));
    $adminEmail = trim((string)($_POST['admin_email'] ?? $defaults['admin_email']));

    try {
        if ($host === '' || !preg_match('/^[a-zA-Z0-9._:-]+$/', $host)) throw new RuntimeException('نام میزبان MySQL معتبر نیست.');
        if ($port < 1 || $port > 65535) throw new RuntimeException('پورت MySQL معتبر نیست.');
        if ($name === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) throw new RuntimeException('نام دیتابیس معتبر نیست.');
        if ($user === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $user)) throw new RuntimeException('نام کاربری دیتابیس معتبر نیست.');
        if ($pass === '') throw new RuntimeException('رمز عبور MySQL را وارد کنید.');
        if ($siteUrl !== '' && !filter_var($siteUrl, FILTER_VALIDATE_URL)) throw new RuntimeException('آدرس سایت معتبر نیست.');
        if ($adminUsername === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $adminUsername)) throw new RuntimeException('نام کاربری مدیر معتبر نیست.');
        if (strlen($adminPassword) < 8) throw new RuntimeException('رمز مدیر باید حداقل ۸ کاراکتر باشد.');
        if ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('ایمیل مدیر معتبر نیست.');

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . rawurlencode($name) . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET NAMES utf8mb4");

        $schemaPath = __DIR__ . '/../database.mysql.sql';
        $schema = file_get_contents($schemaPath);
        if ($schema === false) throw new RuntimeException('فایل schema پیدا نشد.');

        $applied = 0;
        foreach (installerSplitSql($schema) as $statement) {
            if ($statement === '') continue;
            try {
                $pdo->exec($statement);
                $applied++;
            } catch (PDOException $e) {
                $message = $e->getMessage();
                if (preg_match('/already exists|duplicate key name|duplicate key|duplicate entry/i', $message)) continue;
                throw $e;
            }
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check->execute([$adminUsername]);
        $existingAdmin = $check->fetchColumn();

        if (!$existingAdmin) {
            $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, full_name, role, is_active, auth_version) VALUES (?, ?, ?, ?, ?, 1, 1)');
            $stmt->execute([$adminUsername, $adminEmail, $hash, $adminName, 'superadmin']);
        }

        $localConfig = [
            'APP_ENV' => 'production',
            'DB_DRIVER' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => (string)$port,
            'DB_NAME' => $name,
            'DB_USER' => $user,
            'DB_PASS' => $pass,
            'SITE_URL' => $siteUrl,
            'BASE_PATH' => '',
            'SITE_EMAIL' => $adminEmail ?: 'hajiahmads299@gmail.com',
            'UPLOAD_STORAGE' => 'local',
            'UPLOAD_LOCAL_PATH' => __DIR__ . '/../uploads',
            'UPLOAD_BASE_URL' => '/uploads',
        ];

        $php = "<?php\n// Generated by Jametulhoda browser installer. Keep this file outside Git.\nreturn " . var_export($localConfig, true) . ";\n";
        if (file_put_contents($localPath, $php, LOCK_EX) === false) {
            throw new RuntimeException('فایل تنظیمات خصوصی ساخته نشد. دسترسی نوشتن پوشه config را بررسی کنید.');
        }
        $lockValue = 'Installed: ' . date('c') . PHP_EOL . 'Schema statements: ' . $applied . PHP_EOL;
        if (file_put_contents($lockPath, $lockValue, LOCK_EX) === false) {
            throw new RuntimeException('قفل نصب ساخته نشد. دسترسی نوشتن پوشه config را بررسی کنید.');
        }

        $alreadyInstalled = true;
        $success = 'نصب با موفقیت انجام شد.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>نصب Jametulhoda</title>
<style>
body{margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f4f7fb;color:#162033}
.wrap{max-width:760px;margin:32px auto;padding:16px}.card{background:#fff;border:1px solid #e1e7ef;border-radius:18px;padding:24px;box-shadow:0 12px 32px rgba(20,40,70,.08)}
h1{margin-top:0}.muted{color:#64748b}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.full{grid-column:1/-1}label{display:block;font-weight:700;margin-bottom:6px}input{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #cfd8e3;border-radius:10px;font:inherit}button,.btn{display:inline-block;border:0;border-radius:10px;padding:11px 18px;background:#183b70;color:#fff;text-decoration:none;font:inherit;cursor:pointer}.notice{padding:12px 14px;border-radius:10px;margin-bottom:16px}.error{background:#fff1f2;color:#9f1239}.success{background:#ecfdf5;color:#166534}.section{border-top:1px solid #e8edf3;margin-top:22px;padding-top:22px}@media(max-width:640px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style>
</head>
<body>
<div class="wrap"><div class="card">
<h1>نصب Jametulhoda</h1>
<p class="muted">راه‌اندازی MySQL برای InfinityFree و ساخت اولین حساب مدیر.</p>

<?php if ($error): ?><div class="notice error"><?= installerEscape($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="notice success"><?= installerEscape($success) ?></div><?php endif; ?>

<?php if ($alreadyInstalled): ?>
<p>این نصب قبلاً انجام شده و مسیر نصب قفل شده است.</p>
<a class="btn" href="../admin/login">ورود مدیر</a>
<a class="btn" href="../">صفحه اصلی</a>
<?php else: ?>
<form method="post" autocomplete="off">
<div class="grid">
<div><label>MySQL Host</label><input name="db_host" value="<?= installerEscape($defaults['db_host']) ?>" required></div>
<div><label>Port</label><input name="db_port" type="number" value="<?= installerEscape($defaults['db_port']) ?>" required></div>
<div><label>Database Name</label><input name="db_name" value="<?= installerEscape($defaults['db_name']) ?>" required></div>
<div><label>MySQL Username</label><input name="db_user" value="<?= installerEscape($defaults['db_user']) ?>" required></div>
<div class="full"><label>MySQL Password</label><input name="db_pass" type="password" required></div>
<div class="full"><label>Site URL</label><input name="site_url" value="<?= installerEscape($defaults['site_url']) ?>" placeholder="https://jametulhoda.gt.tc"></div>
</div>

<div class="section"><h2>حساب مدیر</h2>
<div class="grid">
<div><label>Username</label><input name="admin_username" value="<?= installerEscape($defaults['admin_username']) ?>" required></div>
<div><label>Email</label><input name="admin_email" type="email" value="<?= installerEscape($defaults['admin_email']) ?>"></div>
<div><label>نام مدیر</label><input name="admin_name" value="مدیر سایت"></div>
<div><label>رمز مدیر</label><input name="admin_password" type="password" minlength="8" required></div>
</div></div>

<div class="section"><button type="submit">شروع نصب</button></div>
</form>
<?php endif; ?>
</div></div>
</body>
</html>