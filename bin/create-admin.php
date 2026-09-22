<?php
/**
 * bin/create-admin.php — ساخت یا بازیابی حساب مدیر ارشد از خط فرمان
 *
 * پیش‌فرض: نام کاربری `admin` و رمز `JH@2026#Admin`
 * (DEFAULT_ADMIN_USERNAME / DEFAULT_ADMIN_PASSWORD در config/config.php).
 * با ADMIN_USERNAME و ADMIN_PASSWORD می‌توانید مقدار دیگری بدهید.
 *
 * رمز فقط با password_hash() ذخیره می‌شود. اگر حساب از قبل وجود داشته باشد،
 * رمز آن بازنشانی و با افزایش auth_version همه نشست‌های فعال باطل می‌شوند.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';

$username = env_value('ADMIN_USERNAME', DEFAULT_ADMIN_USERNAME);
$customPassword = env_value('ADMIN_PASSWORD');
$password = $customPassword !== '' ? $customPassword : DEFAULT_ADMIN_PASSWORD;

if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username) || ($customPassword !== '' && strlen($password) < 14)) {
    fwrite(STDERR, "Set ADMIN_USERNAME and a unique ADMIN_PASSWORD of at least 14 characters in your environment.\n");
    exit(1);
}

$db = getDB();
$stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$existingId = (int)$stmt->fetchColumn();
$hash = password_hash($password, PASSWORD_DEFAULT);

if ($existingId) {
    $db->prepare('UPDATE users SET password = ?, is_active = 1, auth_version = COALESCE(auth_version, 1) + 1 WHERE id = ?')
       ->execute([$hash, $existingId]);
    echo "Administrator '{$username}' already existed: password reset and existing sessions invalidated.\n";
} else {
    $db->prepare("INSERT INTO users (username, password, email, full_name, role, is_active, auth_version) VALUES (?, ?, ?, ?, 'superadmin', 1, 1)")
       ->execute([$username, $hash, env_value('ADMIN_EMAIL'), env_value('ADMIN_NAME', $username)]);
    echo "Administrator '{$username}' created; the password is stored as a password_hash() digest only.\n";
}
