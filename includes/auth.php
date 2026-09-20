<?php
/**
 * auth.php - مدیریت احراز هویت مدیران
 */

require_once __DIR__ . '/../config/config.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_user']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . siteUrl('admin/login.php'));
        exit;
    }
}

function loginAdmin(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        startSecureSession();
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_user'] = $user['username'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_role'] = $user['role'];
        // Update last login
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        return true;
    }
    return false;
}

function logoutAdmin(): void {
    startSecureSession();
    $_SESSION = [];
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
