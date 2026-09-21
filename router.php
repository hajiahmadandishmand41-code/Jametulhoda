<?php
/** Shared front controller for Apache containers and local PHP development. */
require_once __DIR__ . '/config/config.php';
if (env_value('VERCEL') && (int)($_SERVER['CONTENT_LENGTH'] ?? 0)>4*1024*1024) { http_response_code(413); exit('حجم درخواست بیش از حد مجاز است.'); }
foreach ($_GET as $value) { if (!is_string($value)) { http_response_code(400); exit('Invalid query parameter'); } }
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (BASE_PATH) {
    if ($path === BASE_PATH) $path='/';
    elseif (str_starts_with($path, BASE_PATH . '/')) $path=substr($path,strlen(BASE_PATH));
    else { http_response_code(404); exit; }
}
if (str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '\\')) { http_response_code(404); exit; }
if (preg_match('~^/assets/[a-zA-Z0-9_./-]+\.(css|js|svg|png|jpe?g|webp|gif|woff2?)$~D', $path) ||
    (UPLOAD_STORAGE === 'local' && preg_match('~^/uploads/(?:[a-zA-Z0-9_-]+/)+[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|gif|webp|mp3|ogg|wav|m4a|mp4|webm|mov|mkv|pdf|doc|docx)$~D', $path))) {
    $file = str_starts_with($path, '/uploads/') ? UPLOAD_DIR . substr($path,9) : __DIR__ . $path;
    if (is_file($file) && !is_link($file)) {
        $types=['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml','woff'=>'font/woff','woff2'=>'font/woff2'];
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: '.($types[$ext] ?? (new finfo(FILEINFO_MIME_TYPE))->file($file)));
        header('Cache-Control: public, max-age=3600');
        $etag='"'.dechex(filemtime($file)).'-'.dechex(filesize($file)).'"';
        header('ETag: '.$etag);
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
        if (in_array($ext, ['pdf','doc','docx'],true)) header('Content-Disposition: attachment');
        $size = filesize($file); $start=0; $end=$size-1;
        header('Accept-Ranges: bytes');
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (!preg_match('/^bytes=(\d*)-(\d*)$/D', $_SERVER['HTTP_RANGE'], $range) || ($range[1]==='' && $range[2]==='') || ($range[1]!=='' && (int)$range[1] >= $size)) {
                http_response_code(416); header('Content-Range: bytes */'.$size); exit;
            }
            $start=$range[1]==='' ? max(0,$size-(int)$range[2]) : (int)$range[1];
            $end=$range[1]!=='' && $range[2]!=='' ? min((int)$range[2],$end) : $end;
            if ($end<$start) { http_response_code(416); exit; }
            http_response_code(206); header("Content-Range: bytes $start-$end/$size");
        }
        header('Content-Length: '.($end-$start+1));
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
            while (ob_get_level()) ob_end_flush();
            $handle=fopen($file,'rb'); fseek($handle,$start); $remaining=$end-$start+1;
            while ($remaining>0 && !feof($handle)) { $data=fread($handle,min(65536,$remaining)); echo $data; $remaining-=strlen($data); }
            fclose($handle);
        }
        exit;
    }
}
if (in_array($path, ['/audio','/video'], true)) $_GET['kind']=ltrim($path,'/');
$routes = require __DIR__ . '/config/routes.php';
if (preg_match('~^/book/(\d+)/?$~', $path, $bookMatch)) { $path='/book.php'; $_GET['id']=$bookMatch[1]; }
if (preg_match('~^/(post|lesson|speech|category|topic)/([^/]+)/?$~u', $path, $match)) {
    $path='/'.$match[1].'.php'; $_GET['slug']=$match[2];
}
if (preg_match('~^/lessons/([^/]+)/?$~u', $path, $lm)) { $path='/lessons.php'; $_GET['collection']=$lm[1]; }
if (isset($routes[$path])) {
    $_SERVER['SCRIPT_NAME'] = BASE_PATH . '/' . $routes[$path];
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    require __DIR__ . '/' . $routes[$path];
    exit;
}
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>صفحه پیدا نشد</title><main style="font-family:Tahoma;text-align:center;padding:12vh 1rem"><h1>۴۰۴ — صفحه پیدا نشد</h1><p>ممکن است نشانی تغییر کرده باشد.</p><a href="'.htmlspecialchars(BASE_PATH.'/',ENT_QUOTES).'">بازگشت به صفحه اصلی</a></main></html>';
