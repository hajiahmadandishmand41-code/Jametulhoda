<?php
/**
 * config.php — تنظیمات اصلی وب‌سایت
 * مدرسه علمیه جامعه‌الهدی
 *
 * Secrets are read from environment variables so they are not committed to Git.
 */

function env_value(string $key, string $default = ''): string {
    $value = getenv($key);
    return ($value === false) ? $default : $value;
}

// Database
define('DB_HOST',    env_value('DB_HOST'));
define('DB_PORT',    env_value('DB_PORT', '3306'));
define('DB_NAME',    env_value('DB_NAME'));
define('DB_USER',    env_value('DB_USER'));
define('DB_PASS',    env_value('DB_PASS'));
define('DB_SOCKET',  env_value('DB_SOCKET'));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));

// Site
define('SITE_NAME',   'مدرسه علمیه جامعه‌الهدی');
define('SITE_SLOGAN', 'علم، معرفت و تهذیب در پرتو قرآن و عترت');
define('SITE_URL',    env_value('SITE_URL'));
define('SITE_EMAIL',  env_value('SITE_EMAIL', 'hajiahmads299@gmail.com'));
define('SITE_PHONE',  env_value('SITE_PHONE', '0798228441'));
define('SITE_ADDRESS',env_value('SITE_ADDRESS', 'کابل، افغانستان'));

// Upload
define('UPLOAD_DIR',    __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 20 * 1024 * 1024);
define('MAX_VIDEO_SIZE', 200 * 1024 * 1024);
define('ALLOWED_IMG',   ['image/jpeg','image/png','image/gif','image/webp']);
define('ALLOWED_AUDIO', ['audio/mpeg','audio/mp3','audio/ogg','audio/wav','audio/mp4','audio/x-m4a','audio/x-mpeg']);
define('ALLOWED_VIDEO', ['video/mp4','video/webm','video/ogg','video/quicktime','video/x-matroska']);

// Session
define('SESSION_NAME', 'jamiat_session');
define('SESSION_LIFETIME', 7200);

// Pagination
define('POSTS_PER_PAGE', 12);
define('LESSONS_PER_PAGE', 12);

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Timezone
date_default_timezone_set('Asia/Kabul');
