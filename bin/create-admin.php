<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$username = env_value('ADMIN_USERNAME');
$password = env_value('ADMIN_PASSWORD');
if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username) || strlen($password) < 14) {
    fwrite(STDERR, "Set ADMIN_USERNAME and a unique ADMIN_PASSWORD of at least 14 characters in your environment.\n"); exit(1);
}
$stmt = getDB()->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'superadmin')");
$stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), env_value('ADMIN_EMAIL'), $username]);
echo "Administrator created; no default credentials are installed.\n";
