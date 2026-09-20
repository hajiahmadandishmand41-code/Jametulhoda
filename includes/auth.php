<?php
/**
 * auth.php - مدیریت احراز هویت مدیران
 */

require_once __DIR__ . '/../config/database.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        if (env_value('SESSION_DRIVER', 'database') === 'database') {
            require_once __DIR__ . '/session.php';
            session_set_save_handler(new DatabaseSessionHandler(), true);
        } elseif (APP_ENV === 'production' || env_value('VERCEL')) {
            throw new RuntimeException('Production requires database sessions.');
        }
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => BASE_PATH . '/',
            'secure'   => APP_ENV === 'production' || env_value('VERCEL') !== '' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Strict',
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
        header('Location: ' . siteUrl('admin/login.php'));
        exit;
    }
}

function loginAdmin(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';
    if (strlen($username)>80 || strlen($password)>4096) return false;
    $db   = getDB();
    // Atomic shared limiter survives cookie resets and concurrent requests.
    $key = hash('sha256', mb_strtolower(trim($username)));
    $limit = $db->prepare("INSERT INTO login_limits (key,attempts,expires_at) VALUES (?,1,NOW()+INTERVAL '15 minutes') ON CONFLICT (key) DO UPDATE SET attempts=CASE WHEN login_limits.expires_at<NOW() THEN 1 ELSE login_limits.attempts+1 END, expires_at=CASE WHEN login_limits.expires_at<NOW() THEN NOW()+INTERVAL '15 minutes' ELSE login_limits.expires_at END RETURNING attempts");
    $limit->execute([hash('sha256', 'ip:' . clientIp())]);
    if ((int)$limit->fetchColumn() > 50) return false;
    $limit->execute([$key]);
    if ((int)$limit->fetchColumn() > 5) return false;
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if ($user && in_array($user['role'], ['superadmin','admin','editor'], true) && password_verify($password, $user['password'])) {
        startSecureSession();
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['last_activity'] = time();
        $_SESSION['auth_version'] = (int)$user['auth_version'];
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_user'] = $user['username'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_role'] = $user['role'];
        $db->prepare('DELETE FROM login_limits WHERE key=?')->execute([$key]);
        // Update last login
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        return true;
    }
    return false;
}

function logoutAdmin(): void {
    startSecureSession();
    $_SESSION = [];
    setcookie(SESSION_NAME, '', ['expires'=>time()-3600, 'path'=>BASE_PATH.'/', 'secure'=>APP_ENV==='production' || env_value('VERCEL')!=='', 'httponly'=>true, 'samesite'=>'Strict']);
    session_destroy();
    header('Location: ' . siteUrl('admin/login.php'));
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
