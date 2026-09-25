<?php
/**
 * auth.php - مدیریت احراز هویت مدیران
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Secure flag follows the actual request scheme. Forcing Secure whenever
 * APP_ENV=production (as the InfinityFree installer sets it) would break
 * panel login with an endless redirect loop on plain-HTTP visits, because
 * the browser never sends a Secure cookie over http.
 */
function sessionCookieSecure(): bool {
    if (env_value('VERCEL') !== '') return true; // Vercel is always HTTPS.
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function ensureCoreAuthTables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $db = getDB();
        $driver = databaseDriver();
        if ($driver === 'mysql') {
            $db->exec("CREATE TABLE IF NOT EXISTS app_sessions (id VARCHAR(128) PRIMARY KEY, data TEXT NOT NULL, expires_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $db->exec("CREATE TABLE IF NOT EXISTS login_limits (limit_key VARCHAR(64) PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } elseif ($driver === 'sqlite') {
            $db->exec("CREATE TABLE IF NOT EXISTS app_sessions (id TEXT PRIMARY KEY, data TEXT NOT NULL, expires_at TEXT NOT NULL)");
            $db->exec("CREATE TABLE IF NOT EXISTS login_limits (limit_key TEXT PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, expires_at TEXT NOT NULL)");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS app_sessions (id VARCHAR(128) PRIMARY KEY, data TEXT NOT NULL, expires_at TIMESTAMPTZ NOT NULL)");
            $db->exec("CREATE TABLE IF NOT EXISTS login_limits (limit_key VARCHAR(64) PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, expires_at TIMESTAMPTZ NOT NULL)");
        }
    } catch (Throwable $e) {
        error_log('Auth table ensure failed: ' . get_class($e));
    }
}

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        if (env_value('SESSION_DRIVER', 'database') === 'database') {
            ensureCoreAuthTables();
            require_once __DIR__ . '/session.php';
            session_set_save_handler(new DatabaseSessionHandler(), true);
        } elseif (APP_ENV === 'production' || env_value('VERCEL')) {
            throw new RuntimeException('Production requires database sessions.');
        }
        session_name(SESSION_NAME);
        $cookiePath = (defined('BASE_PATH') && BASE_PATH !== '') ? BASE_PATH . '/' : '/';
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => $cookiePath,
            'secure'   => sessionCookieSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_user'])) return false;
    if (($_SESSION['last_activity'] ?? 0) < time() - SESSION_LIFETIME) { $_SESSION = []; return false; }
    $stmt = getDB()->prepare('SELECT role, auth_version FROM users WHERE id=? AND is_active=1');
    $stmt->execute([$_SESSION['admin_id']]);
    $record = $stmt->fetch();
    if (!$record || (int)$record['auth_version'] !== (int)($_SESSION['auth_version'] ?? 0)) { $_SESSION = []; return false; }
    $role = $record['role'];
    if (!in_array($role, ['superadmin','admin','editor'], true)) { $_SESSION = []; return false; }
    $_SESSION['admin_role'] = $role;
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . adminLoginUrl());
        exit;
    }
}

/**
 * Increment the shared login limiter and return the current attempt count.
 * PostgreSQL uses one upsert with RETURNING; MySQL/MariaDB use
 * ON DUPLICATE KEY UPDATE followed by a read, because MySQL has no RETURNING.
 * The column is named limit_key ("key" is a reserved word in MySQL).
 */
function recordLoginAttempt(PDO $db, string $key): int {
    if (databaseDriver() === 'mysql') {
        $db->prepare("INSERT INTO login_limits (limit_key,attempts,expires_at) VALUES (?,1,NOW()+INTERVAL 15 MINUTE) ON DUPLICATE KEY UPDATE attempts=IF(expires_at<NOW(),1,attempts+1), expires_at=IF(expires_at<NOW(),NOW()+INTERVAL 15 MINUTE,expires_at)")->execute([$key]);
        $stmt = $db->prepare('SELECT attempts FROM login_limits WHERE limit_key=?');
        $stmt->execute([$key]);
        return (int)$stmt->fetchColumn();
    }
    if (databaseDriver() === 'sqlite') {
        $limit = $db->prepare("INSERT INTO login_limits (limit_key,attempts,expires_at) VALUES (?,1,datetime('now','+15 minutes')) ON CONFLICT (limit_key) DO UPDATE SET attempts=CASE WHEN login_limits.expires_at<datetime('now') THEN 1 ELSE login_limits.attempts+1 END, expires_at=CASE WHEN login_limits.expires_at<datetime('now') THEN datetime('now','+15 minutes') ELSE login_limits.expires_at END RETURNING attempts");
        $limit->execute([$key]);
        return (int)$limit->fetchColumn();
    }
    $limit = $db->prepare("INSERT INTO login_limits (limit_key,attempts,expires_at) VALUES (?,1,NOW()+INTERVAL '15 minutes') ON CONFLICT (limit_key) DO UPDATE SET attempts=CASE WHEN login_limits.expires_at<NOW() THEN 1 ELSE login_limits.attempts+1 END, expires_at=CASE WHEN login_limits.expires_at<NOW() THEN NOW()+INTERVAL '15 minutes' ELSE login_limits.expires_at END RETURNING attempts");
    $limit->execute([$key]);
    return (int)$limit->fetchColumn();
}

function jhd_password_is_hash(string $stored): bool {
    if ($stored === '' || strlen($stored) < 50) return false;
    $info = password_get_info($stored);
    return !empty($info['algo']);
}

function loginAdmin(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';
    if (strlen($username)>80 || strlen($password)>4096) return false;
    $db   = getDB();
    ensureCoreAuthTables();
    // Atomic shared limiter survives cookie resets and concurrent requests.
    $key = hash('sha256', 'admin:' . mb_strtolower(trim($username)));
    if (recordLoginAttempt($db, hash('sha256', 'ip:' . clientIp())) > 50) return false;
    if (recordLoginAttempt($db, $key) > 5) return false;
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if (!$user) {
        password_verify($password, '$2y$10$usesomesillystringfore7wTCnyKPeogVA6awaz8iYupZHBMjqZ');
        return false;
    }
    if ((int)$user['is_active'] !== 1) {
        error_log('Admin login denied: inactive account id=' . (int)$user['id']);
        return false;
    }
    if (!in_array($user['role'], ['superadmin','admin','editor'], true)) {
        error_log('Admin login denied: invalid role for id=' . (int)$user['id']);
        return false;
    }
    $stored = (string)($user['password'] ?? '');
    if (!jhd_password_is_hash($stored)) {
        error_log('Admin login denied: password is not a password_hash() digest for id=' . (int)$user['id']);
        return false;
    }
    if (!password_verify($password, $stored)) {
        return false;
    }
    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
        try {
            $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        } catch (Throwable $e) {
            error_log('Admin password rehash skipped: ' . get_class($e));
        }
    }
    startSecureSession();
    $keepCsrf = $_SESSION[CSRF_TOKEN_NAME] ?? null;
    $keepMember = [
        'member_id' => $_SESSION['member_id'] ?? null,
        'member_name' => $_SESSION['member_name'] ?? null,
        'member_auth_version' => $_SESSION['member_auth_version'] ?? null,
        'last_member_activity' => $_SESSION['last_member_activity'] ?? null,
    ];
    session_regenerate_id(true);
    $_SESSION = [];
    if (is_string($keepCsrf) && $keepCsrf !== '') $_SESSION[CSRF_TOKEN_NAME] = $keepCsrf;
    foreach ($keepMember as $k => $v) {
        if ($v !== null) $_SESSION[$k] = $v;
    }
    $_SESSION['last_activity'] = time();
    $_SESSION['auth_version'] = (int)($user['auth_version'] ?? 1);
    $_SESSION['admin_id']   = $user['id'];
    $_SESSION['admin_user'] = $user['username'];
    $_SESSION['admin_name'] = $user['full_name'];
    $_SESSION['admin_role'] = $user['role'];
    $db->prepare('DELETE FROM login_limits WHERE limit_key=?')->execute([$key]);
    $nowSql = databaseDriver() === 'sqlite' ? "datetime('now')" : 'NOW()';
    $db->prepare("UPDATE users SET last_login = $nowSql WHERE id = ?")->execute([$user['id']]);
    return true;
}

function logoutAdmin(): void {
    startSecureSession();
    $_SESSION = [];
    $cookiePath = (defined('BASE_PATH') && BASE_PATH !== '') ? BASE_PATH . '/' : '/';
    setcookie(SESSION_NAME, '', ['expires'=>time()-3600, 'path'=>$cookiePath, 'secure'=>sessionCookieSecure(), 'httponly'=>true, 'samesite'=>'Lax']);
    session_destroy();
    header('Location: ' . adminLoginUrl());
    exit;
}

function currentAdmin(): array {
    startSecureSession();
    return [
        'id'   => $_SESSION['admin_id']   ?? 0,
        'user' => $_SESSION['admin_user'] ?? '',
        'name' => $_SESSION['admin_name'] ?? '',
        'role' => $_SESSION['admin_role'] ?? '',
    ];
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['admin_role'], $roles, true)) { http_response_code(403); exit('دسترسی مجاز نیست.'); }
}
function requirePostCsrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); http_response_code(405); exit('Method not allowed'); }
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { http_response_code(403); exit('درخواست نامعتبر است.'); }
}
