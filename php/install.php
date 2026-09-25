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
    'db_host' => env_value('DB_HOST'),
    'db_port' => env_value('DB_PORT', '3306'),
    'db_name' => env_value('DB_NAME'),
    'db_user' => env_value('DB_USER'),
    'site_url' => env_value('SITE_URL', ''),
    'admin_username' => env_value('ADMIN_USERNAME', DEFAULT_ADMIN_USERNAME),
    'admin_email' => env_value('ADMIN_EMAIL', env_value('SITE_EMAIL', 'hajiahmads299@gmail.com')),
];

$error = '';
$success = '';
$siteUrlInsecure = false;

if (!$alreadyInstalled && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $host = trim((string)($_POST['db_host'] ?? $defaults['db_host']));
    $port = (int)($_POST['db_port'] ?? $defaults['db_port']);
    $name = trim((string)($_POST['db_name'] ?? $defaults['db_name']));
    $user = trim((string)($_POST['db_user'] ?? $defaults['db_user']));
    $pass = (string)($_POST['db_pass'] ?? '');
    $siteUrl = rtrim(trim((string)($_POST['site_url'] ?? '')), '/');
    $adminUsername = trim((string)($_POST['admin_username'] ?? $defaults['admin_username']));
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    // A private environment override is supported, but installation must never
    // silently create or reset an account to a published/default password.
    if ($adminPassword === '') $adminPassword = DEFAULT_ADMIN_PASSWORD;
    $adminName = trim((string)($_POST['admin_name'] ?? 'مدیر سایت'));
    $adminEmail = trim((string)($_POST['admin_email'] ?? $defaults['admin_email']));

    try {
        if ($host === '' || !preg_match('/^[a-zA-Z0-9._:-]+$/', $host)) throw new RuntimeException('نام میزبان MySQL معتبر نیست.');
        if ($port < 1 || $port > 65535) throw new RuntimeException('پورت MySQL معتبر نیست.');
        if ($name === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) throw new RuntimeException('نام دیتابیس معتبر نیست.');
        if ($user === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $user)) throw new RuntimeException('نام کاربری دیتابیس معتبر نیست.');
        if ($pass === '') throw new RuntimeException('رمز عبور MySQL را وارد کنید.');
        if ($siteUrl === '') {
            // Shared hosts answer on the real public domain: derive the HTTPS
            // root from the request instead of storing an empty SITE_URL,
            // which would disable robots/sitemap output.
            $requestHost = (string)($_SERVER['HTTP_HOST'] ?? '');
            if (preg_match('/^[a-zA-Z0-9.-]+(:[0-9]+)?$/', $requestHost)) {
                $siteUrl = 'https://' . preg_replace('/:[0-9]+$/', '', $requestHost);
            }
        }
        if ($siteUrl !== '' && !filter_var($siteUrl, FILTER_VALIDATE_URL)) throw new RuntimeException('آدرس سایت معتبر نیست.');
        $siteUrlInsecure = $siteUrl !== '' && !preg_match('~^https://~i', $siteUrl);
        if ($adminUsername === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $adminUsername)) throw new RuntimeException('نام کاربری مدیر معتبر نیست.');
        if (strlen($adminPassword) < 14) throw new RuntimeException('برای مدیر یک رمز یکتا با حداقل ۱۴ نویسه وارد کنید.');
        if ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('ایمیل مدیر معتبر نیست.');

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . rawurlencode($name) . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET NAMES utf8mb4");

        $schemaPath = __DIR__ . '/../database/database.mysql.sql';
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
                if (preg_match('/already exists|duplicate key name|duplicate key|duplicate entry|duplicate column/i', $message)) continue;
                throw $e;
            }
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check->execute([$adminUsername]);
        $existingAdmin = (int)$check->fetchColumn();

        // The password is never stored in plain text: only the password_hash()
        // digest reaches the database.
        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        if (!$existingAdmin) {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, full_name, role, is_active, auth_version) VALUES (?, ?, ?, ?, ?, 1, 1)');
            $stmt->execute([$adminUsername, $adminEmail, $hash, $adminName, 'superadmin']);
            $adminNote = 'حساب مدیر «' . $adminUsername . '» ساخته شد.';
        } else {
            // The account already exists (e.g. the files were uploaded again on a
            // host with an existing database): reset its password securely and
            // invalidate every active session by bumping auth_version.
            $stmt = $pdo->prepare('UPDATE users SET password = ?, email = ?, full_name = ?, role = ?, is_active = 1, auth_version = COALESCE(auth_version, 1) + 1 WHERE id = ?');
            $stmt->execute([$hash, $adminEmail, $adminName, 'superadmin', $existingAdmin]);
            $adminNote = 'حساب مدیر «' . $adminUsername . '» از قبل وجود داشت؛ رمز آن بازنشانی شد و نشست‌های فعال باطل شدند.';
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
            'SESSION_DRIVER' => 'database',
            'JHD_PRETTY_URLS' => 'false',
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
        $success = 'نصب با موفقیت انجام شد. ' . $adminNote;
        if ($siteUrlInsecure) {
            $success .= ' توجه: آدرس سایت با https ذخیره نشد؛ تا زمانی که SSL رایگان را فعال نکنید، robots.txt محدود می‌ماند و sitemap.xml خطا می‌دهد.';
        }
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
@font-face{font-family:Vazirmatn;src:url(<?= htmlspecialchars(BASE_PATH . '/assets/fonts/Vazirmatn-Regular.woff2', ENT_QUOTES, 'UTF-8') ?>) format('woff2');font-style:normal;font-weight:400;font-display:swap}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:'Vazirmatn',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 88% 8%,rgba(179,140,54,.12),transparent 19rem),linear-gradient(135deg,#edf4ef,#f8faf8 56%,#f4f1e8);color:#172820;line-height:1.75}
.wrap{max-width:820px;margin:clamp(16px,5vh,52px) auto;padding:16px}.card{background:rgba(255,255,255,.96);border:1px solid #dbe6df;border-radius:22px;padding:clamp(20px,4vw,38px);box-shadow:0 18px 48px rgba(18,55,38,.11)}
h1{margin:0 0 8px;color:#184f38;font-size:clamp(1.5rem,4vw,2.1rem);line-height:1.45}.muted{color:#52675d}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.full{grid-column:1/-1}label{display:block;font-weight:750;margin-bottom:6px;color:#1d3327}input{width:100%;padding:11px 12px;border:1px solid #cfddd4;border-radius:10px;background:#fff;color:#172820;font:inherit;transition:border-color .16s,box-shadow .16s}input:focus{outline:0;border-color:#1b5e43;box-shadow:0 0 0 4px rgba(27,94,67,.12)}button,.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:10px;padding:11px 20px;background:#1b5e43;color:#fff;text-decoration:none;font:inherit;font-weight:750;cursor:pointer;transition:transform .16s,background .16s}button:hover,.btn:hover{background:#124430;color:#fff;transform:translateY(-1px)}.notice{padding:12px 14px;border-radius:10px;margin-bottom:16px}.error{background:#fff1f2;color:#9f1239}.success{background:#ecfdf5;color:#166534}.section{border-top:1px solid #e3ebe5;margin-top:24px;padding-top:22px}.section h2{color:#184f38;font-size:1.2rem}@media(max-width:640px){.wrap{padding:10px}.card{border-radius:16px}.grid{grid-template-columns:1fr;gap:12px}.full{grid-column:auto}}@media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important;scroll-behavior:auto!important}}
</style>
</head>
<body class="jhd-installer">
<div class="wrap"><div class="card">
<h1>راه‌اندازی جامعة‌الهدی</h1>
<p class="muted">راه‌اندازی MySQL برای InfinityFree و ساخت اولین حساب مدیر.</p>

<?php if ($error): ?><div class="notice error"><?= installerEscape($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="notice success"><?= installerEscape($success) ?></div><?php endif; ?>

<?php if ($alreadyInstalled): ?>
<p>این نصب قبلاً انجام شده و مسیر نصب قفل شده است.</p>
<a class="btn" href="<?= installerEscape((BASE_PATH === '' ? '' : BASE_PATH) . '/admin/login.php') ?>">ورود مدیر</a>
<a class="btn" href="<?= installerEscape(BASE_PATH === '' ? '/' : BASE_PATH . '/') ?>">صفحه اصلی</a>
<?php else: ?>
<form method="post" autocomplete="off">
<div class="grid">
<div><label for="db-host">میزبان MySQL</label><input id="db-host" name="db_host" value="<?= installerEscape($defaults['db_host']) ?>" required></div>
<div><label for="db-port">درگاه اتصال</label><input id="db-port" name="db_port" type="number" value="<?= installerEscape($defaults['db_port']) ?>" required></div>
<div><label for="db-name">نام دیتابیس</label><input id="db-name" name="db_name" value="<?= installerEscape($defaults['db_name']) ?>" required></div>
<div><label for="db-user">نام کاربری MySQL</label><input id="db-user" name="db_user" value="<?= installerEscape($defaults['db_user']) ?>" required></div>
<div class="full"><label for="db-pass">رمز عبور MySQL</label><input id="db-pass" name="db_pass" type="password" required></div>
<div class="full"><label for="site-url">نشانی کامل سایت (HTTPS)</label><input id="site-url" name="site_url" value="<?= installerEscape($defaults['site_url']) ?>" placeholder="https://jametulhoda.gt.tc"></div>
</div>

<div class="section"><h2>حساب مدیر</h2>
<div class="grid">
<div><label for="admin-username">نام کاربری مدیر</label><input id="admin-username" name="admin_username" value="<?= installerEscape($defaults['admin_username']) ?>" required></div>
<div><label for="admin-email">ایمیل مدیر</label><input id="admin-email" name="admin_email" type="email" value="<?= installerEscape($defaults['admin_email']) ?>"></div>
<div><label for="admin-name">نام مدیر</label><input id="admin-name" name="admin_name" value="مدیر سایت"></div>
<div><label for="admin-password">رمز مدیر</label><input id="admin-password" name="admin_password" type="password" minlength="14" autocomplete="new-password" required>
<p class="muted" style="margin:6px 0 0">یک رمز یکتا با حداقل ۱۴ نویسه انتخاب کنید. اگر این حساب از قبل در دیتابیس وجود داشته باشد، رمز آن بازنشانی و نشست‌های فعال باطل می‌شوند.</p></div>
</div></div>

<div class="section"><button type="submit">شروع نصب</button></div>
</form>
<?php endif; ?>
</div></div>
</body>
</html>