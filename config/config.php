<?php
/**
 * config.php — تنظیمات اصلی وب‌سایت
 * مدرسه علمیه جامعه‌الهدی
 *
 * Secrets are read from environment variables so they are not committed to Git.
 */

function env_value(string $key, string $default = ''): string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

define('APP_ENV', env_value('APP_ENV', env_value('VERCEL') ? 'production' : 'development'));
define('BASE_PATH', rtrim('/' . trim(env_value('BASE_PATH'), '/'), '/'));

// Site
define('SITE_NAME',   'مدرسه علمیه جامعه‌الهدی');
define('SITE_SLOGAN', 'علم، معرفت و تهذیب در پرتو قرآن و عترت');
define('SITE_URL',    env_value('SITE_URL'));
define('SITE_EMAIL',  env_value('SITE_EMAIL', 'hajiahmads299@gmail.com'));
define('SITE_PHONE',  env_value('SITE_PHONE', '0798228441'));
define('SITE_ADDRESS',env_value('SITE_ADDRESS', 'کابل، افغانستان'));

// Upload
define('UPLOAD_STORAGE', env_value('UPLOAD_STORAGE', 'local'));
define('UPLOAD_DIR', rtrim(env_value('UPLOAD_LOCAL_PATH', __DIR__ . '/../uploads'), '/') . '/');
define('UPLOAD_BASE_URL', rtrim(env_value('UPLOAD_BASE_URL', BASE_PATH . '/uploads'), '/'));
define('UPLOAD_IMAGES', 'images');
define('UPLOAD_AUDIO', 'audio');
define('UPLOAD_VIDEO', 'video');
define('UPLOAD_DOCUMENTS', 'documents');
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

// All entrypoints share non-disclosing failures and response hardening.
if (PHP_SAPI !== 'cli') {
    // A pre-existing PHP buffer may auto-flush after 4096 bytes. Keep our own
    // unbounded response buffer until controllers finish redirect/header decisions.
    ob_start(null, 0);
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (APP_ENV === 'production') header('X-Frame-Options: SAMEORIGIN');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    header("Content-Security-Policy: object-src 'none'; base-uri 'self'" . (APP_ENV === 'production' ? "; frame-ancestors 'self'" : ''));
    if (APP_ENV === 'production') header('Strict-Transport-Security: max-age=31536000');
}
set_exception_handler(function (Throwable $e): void {
    $id = bin2hex(random_bytes(6));
    // Do not log DSNs, submitted passwords, SQL values or storage credentials.
    error_log('Application failure ' . $id . ': ' . get_class($e) . ' at ' . basename($e->getFile()) . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "Operation failed; reference: $id\n"); exit(1); }
    while (ob_get_level()) ob_end_clean();
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>سرویس موقتاً در دسترس نیست</title><h1>لطفاً کمی بعد دوباره تلاش کنید.</h1><p>شناسه پیگیری: ' . $id . '</p></html>';
});

/** Trust platform-owned forwarding headers only on Vercel, never arbitrary client headers. */
function clientIp(): string {
    $ip=$_SERVER['REMOTE_ADDR']??'0.0.0.0';
    if (env_value('VERCEL')) {
        $ip=trim(explode(',',$_SERVER['HTTP_X_VERCEL_FORWARDED_FOR']??$_SERVER['HTTP_X_FORWARDED_FOR']??$ip)[0]);
    }
    return filter_var($ip,FILTER_VALIDATE_IP)?$ip:'0.0.0.0';
}
