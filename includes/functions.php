<?php
/**
 * functions.php — Jametulhoda Content-Centered helpers
 * Production-oriented: SEO, topics, lesson collections, books, search, sanitization
 * All Like/View systems removed completely per spec 17.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/storage.php';

// ─── Security ────────────────────────────────────────────────────────────────

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string {
    require_once __DIR__ . '/auth.php';
    startSecureSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCsrfToken() . '">';
}

// ─── URL ─────────────────────────────────────────────────────────────────────

/**
 * Central URL helper for the entire application.
 * Safely resolves root or subdirectory paths and handles query strings.
 */
function url(string $path = '', array $query = []): string {
    if (preg_match('~^https?://~i', $path)) {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            if (!empty($query)) {
                $separator = str_contains($path, '?') ? '&' : '?';
                return $path . $separator . http_build_query($query);
            }
            return $path;
        }
        return '';
    }
    if (preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i', $path) || str_contains($path, "\r") || str_contains($path, "\n")) {
        return '';
    }
    $cleanPath = ltrim(str_replace(['"', "'", '<', '>'], ['%22','%27','%3C','%3E'], $path), '/');
    $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
    $result = $base . '/' . $cleanPath;
    if ($cleanPath === '' && $base === '') {
        $result = '/';
    }
    if (!empty($query)) {
        $separator = str_contains($result, '?') ? '&' : '?';
        $result .= $separator . http_build_query($query);
    }
    return $result;
}

/**
 * Backward-compatible alias for url().
 */
function siteUrl(string $path = ''): string {
    return url($path);
}

/**
 * Central asset helper for styles, scripts, fonts, and images.
 */
function asset(string $path): string {
    $clean = ltrim($path, '/');
    if (!str_starts_with($clean, 'assets/') && !str_starts_with($clean, 'uploads/')) {
        $clean = 'assets/' . $clean;
    }
    return url($clean);
}

/**
 * Generate full absolute canonical URL including protocol and host.
 */
function absolute_url(string $path = '', array $query = []): string {
    $relative = url($path, $query);
    if (preg_match('~^https?://~i', $relative)) {
        return $relative;
    }
    $host = '';
    if (defined('SITE_URL') && SITE_URL) {
        $host = rtrim(SITE_URL, '/');
        if (defined('BASE_PATH') && BASE_PATH !== '' && str_ends_with($host, BASE_PATH)) {
            $host = substr($host, 0, -strlen(BASE_PATH));
        }
    } else {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https://' : 'http://';
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'jametulhoda.gt.tc';
        $host = $proto . $httpHost;
    }
    return $host . '/' . ltrim($relative, '/');
}

/**
 * Return current normalized route path without query string and without BASE_PATH.
 */
function current_path(): string {
    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if (defined('BASE_PATH') && BASE_PATH !== '') {
        if ($path === BASE_PATH) return '/';
        if (str_starts_with($path, BASE_PATH . '/')) {
            $path = substr($path, strlen(BASE_PATH));
        }
    }
    return '/' . ltrim($path, '/');
}

function redirect(string $url): void {
    if (strpbrk($url, "\r\n") !== false) throw new InvalidArgumentException('Unsafe redirect');
    $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
    $siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    $isRelative = str_starts_with($url, $base . '/') || $url === ($base ?: '/');
    $isSite = $siteUrl !== '' && (str_starts_with($url, $siteUrl . '/') || $url === $siteUrl);
    if ((!$isRelative && !$isSite) || str_starts_with($url, '//')) {
        throw new InvalidArgumentException('Unsafe redirect');
    }
    header('Location: ' . $url, true, ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? 303 : 302);
    exit;
}

function currentUrl(): string {
    return absolute_url(ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'));
}

// ─── Canonical detail URLs ──────────────────────────────────────────────
// Single source for link generation. Every helper returns a pretty URL that
// is registered in config/routes.php. Legacy query-style URLs
// (/post?slug=X, /book?id=N, ...) keep working forever for bookmarks and
// indexed links, but new links must use these helpers.
/** Detail URL for a post row (typed: /article/X, /news/X, /research/X, /report/X, /speech/X, else /post/X). */
function postUrl(array|string $post, string $fallbackType = 'post'): string {
    if (is_string($post)) {
        if ($post === '') return url('articles');
        return url($fallbackType . '/' . rawurlencode($post));
    }
    $slug = $post['slug'] ?? '';
    if ($slug === '') return url('articles');
    $prefix = match ($post['post_type'] ?? '') {
        'article' => 'article',
        'news' => 'news',
        'research' => 'research',
        'report' => 'report',
        'speech' => 'speech',
        'program', 'religious', 'announcement' => 'event',
        default => 'post',
    };
    return url($prefix . '/' . rawurlencode($slug));
}
/** Detail URL for a speech row (/speech/X). */
function speechUrl(array|string $speech): string {
    $slug = is_array($speech) ? ($speech['slug'] ?? '') : $speech;
    if ($slug === '') return url('speeches');
    return url('speech/' . rawurlencode($slug));
}
/** Detail URL for a book row (/book/slug, or /book/id when it has no slug). */
function bookUrl(array $book): string {
    $slug = trim($book['slug'] ?? '');
    if ($slug !== '') return url('book/' . rawurlencode($slug));
    return url('book/' . (int)($book['id'] ?? 0));
}
/** Detail URL for a lesson row (/lesson/X). */
function lessonUrl(array|string $lesson): string {
    $slug = is_array($lesson) ? ($lesson['slug'] ?? '') : $lesson;
    if ($slug === '') return url('lessons');
    return url('lesson/' . rawurlencode($slug));
}
/** Detail URL for a topic row (/topic/X). */
function topicUrl(array|string $topic): string {
    $slug = is_array($topic) ? ($topic['slug'] ?? '') : $topic;
    if ($slug === '') return url('topics');
    return url('topic/' . rawurlencode($slug));
}
/** Detail URL for a category row (/category/X). */
function categoryUrl(array|string $category): string {
    $slug = is_array($category) ? ($category['slug'] ?? '') : $category;
    if ($slug === '') return url();
    return url('category/' . rawurlencode($slug));
}
/** URL for a lesson collection, optionally with a volume (/lessons/X[/Y]). */
function collectionUrl(array|string $collection, array|string|null $volume = null): string {
    $slug = is_array($collection) ? ($collection['slug'] ?? '') : $collection;
    if ($slug === '') return url('lessons');
    $path = 'lessons/' . rawurlencode($slug);
    $volumeSlug = $volume === null ? '' : (is_array($volume) ? ($volume['slug'] ?? '') : $volume);
    if ($volumeSlug !== '') $path .= '/' . rawurlencode($volumeSlug);
    return url($path);
}
/** Detail URL for a media_files record (/video/{id} or /audio/{id}). */
function mediaUrl(string $kind, int $id): string {
    $kind = $kind === 'audio' ? 'audio' : 'video';
    if ($id < 1) return url($kind === 'audio' ? 'audios' : 'videos');
    return url($kind . '/' . $id);
}

// ─── Slug ─────────────────────────────────────────────────────────────────────

function makeSlug(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: uniqid('post-');
}

function uniqueSlug(string $table, string $text, int $excludeId = 0): string {
    $allowed = ['posts','lessons','categories','topics','lesson_collections','lesson_volumes','books'];
    if (!in_array($table, $allowed, true)) throw new InvalidArgumentException('Invalid slug table');
    $db   = getDB();
    $base = makeSlug($text);
    $slug = $base;
    $i    = 1;
    while (true) {
        $sql  = "SELECT COUNT(*) FROM $table WHERE slug = ? AND id != ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$slug, $excludeId]);
        if ((int)$stmt->fetchColumn() === 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// ─── Date ─────────────────────────────────────────────────────────────────────

function persianDate(string $datetime): string {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return '—';
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    $monthNames = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    $parts = explode('-', date('Y-m-d', $ts));
    $gy = (int)$parts[0] - 1600; $gm = (int)$parts[1]; $gd = (int)$parts[2] - 1;
    $leap = ($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0);
    $monthDays = [31,28+($leap?1:0),31,30,31,30,31,31,30,31,30,31];
    $g_d_no = 365*$gy + (int)(($gy+3)/4) - (int)(($gy+99)/100) + (int)(($gy+399)/400);
    for ($i = 0; $i < $gm-1; $i++) $g_d_no += $monthDays[$i];
    $g_d_no += $gd;
    $j_d_no = $g_d_no - 79;
    $j_np   = (int)($j_d_no / 12053); $j_d_no %= 12053;
    $jy     = 979 + 33*$j_np + 4*(int)($j_d_no / 1461); $j_d_no %= 1461;
    if ($j_d_no >= 366) { $jy += (int)(($j_d_no-1)/365); $j_d_no = ($j_d_no-1) % 365; }
    $jMonthDays = [31,31,31,31,31,31,30,30,30,30,30];
    for ($i = 0; $i < 11 && $j_d_no >= $jMonthDays[$i]; $i++) $j_d_no -= $jMonthDays[$i];
    $jm = $i + 1; $jd = $j_d_no + 1;
    return $jd . ' ' . $monthNames[$jm-1] . ' ' . $jy;
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'چند لحظه پیش';
    if ($diff < 3600)    return (int)($diff/60) . ' دقیقه پیش';
    if ($diff < 86400)   return (int)($diff/3600) . ' ساعت پیش';
    if ($diff < 2592000) return (int)($diff/86400) . ' روز پیش';
    return persianDate($datetime);
}

// ─── Upload ───────────────────────────────────────────────────────────────────

function imgUrl(string $path): string {
    if (!$path) return siteUrl('assets/img/placeholder.svg');
    $key = storageKey($path);
    if ($key) return storageUrl($key);
    return siteUrl($path);
}

function uploadImage(array $file, string $subdir = 'posts'): string {
    return uploadFile($file, 'image', $subdir ?: UPLOAD_IMAGES);
}

function uploadAudio(array $file): string {
    return uploadFile($file, 'audio', UPLOAD_AUDIO);
}

function uploadFeaturedVideo(array $file): string {
    return uploadFile($file, 'video', UPLOAD_VIDEO);
}

function renderFeaturedVideo(string $videoPath, string $posterPath = '', string $size = 'card'): string {
    if (!$videoPath) return '';
    $url    = siteUrl($videoPath);
    $poster = $posterPath ? imgUrl($posterPath) : '';
    if ($size === 'card') {
        return sprintf(
            '<button type="button" class="featured-video-play" data-video="%s" data-poster="%s" title="پخش ویدیو"><i class="bi bi-play-fill"></i></button>',
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars($poster, ENT_QUOTES)
        );
    }
    return sprintf(
        '<div class="featured-video-wrap my-3"><video id="featuredPostVideo" controls playsinline preload="none" poster="%s" class="w-100" style="border-radius:12px;background:#000;max-height:500px"><source src="%s" type="video/mp4">مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.</video></div>',
        htmlspecialchars($poster, ENT_QUOTES),
        htmlspecialchars($url, ENT_QUOTES)
    );
}

function saveBase64Thumbnail(string $base64Data, string $subdir = 'posts'): string {
    if (strlen($base64Data) > MAX_FILE_SIZE * 1.4) return '';
    if (!preg_match('~^data:image/(?:jpeg|png|webp);base64,~', $base64Data)) return '';
    $data = base64_decode(substr($base64Data, strpos($base64Data, ',')+1), true);
    if (!$data) return '';
    $tmp = tempnam(sys_get_temp_dir(), 'jhd-thumb-');
    try { file_put_contents($tmp, $data); return storeValidatedFile($tmp, 'image', $subdir); }
    finally { @unlink($tmp); }
}

// ─── Stubs for legacy migrations (now handled by bin/migrate.php) ─────────────

function ensureFeaturedVideoColumn(): void {}
function ensureLessonsColumns(): void {}
function ensureSpeakerColumn(): void {}

// ─── Media helpers ────────────────────────────────────────────────────────────

function countMediaFor(string $refType, int $refId, string $kind = ''): int {
    try {
        $db = getDB();
        if ($kind) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM media_files WHERE ref_type=? AND ref_id=? AND kind=?");
            $stmt->execute([$refType, $refId, $kind]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM media_files WHERE ref_type=? AND ref_id=?");
            $stmt->execute([$refType, $refId]);
        }
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// ─── Posts ────────────────────────────────────────────────────────────────────

function getPosts(array $opts = []): array {
    $db     = getDB();
    $where  = ["p.status = 'published'"];
    $params = [];
    if (!empty($opts['type'])) {
        $where[]  = "p.post_type = ?";
        $params[] = $opts['type'];
    }
    if (!empty($opts['search'])) {
        $where[]  = "(p.title ILIKE ? OR p.summary ILIKE ? OR p.content ILIKE ?)";
        $s        = '%' . $opts['search'] . '%';
        $params   = array_merge($params, [$s, $s, $s]);
    }
    if (!empty($opts['featured'])) {
        $where[]  = "p.is_featured = 1";
    }
    if (!empty($opts['cat'])) {
        $where[]  = "p.category_id = ?";
        $params[] = (int)$opts['cat'];
    }
    if (!empty($opts['topic'])) {
        // filter by topic via junction
        $where[]  = "EXISTS (SELECT 1 FROM post_topics pt WHERE pt.post_id=p.id AND pt.topic_id=?)";
        $params[] = (int)$opts['topic'];
    }
    if (!empty($opts['section'])) {
        $where[]  = "? = ANY(string_to_array(REPLACE(p.page_section, ' ', ''), ','))";
        $params[] = $opts['section'];
    }
    $limit  = isset($opts['limit'])  ? (int)$opts['limit']  : (int)POSTS_PER_PAGE;
    $offset = isset($opts['offset']) ? (int)$opts['offset'] : 0;
    if ($limit  < 1)   $limit  = 1;
    if ($limit  > 100) $limit  = 100;
    if ($offset < 0)   $offset = 0;
    $whereStr = implode(' AND ', $where);
    $sql = "SELECT p.*, c.name AS cat_name, u.full_name AS author_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN users u ON u.id = p.author_id WHERE $whereStr ORDER BY p.is_featured DESC, p.published_at DESC, p.id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countPosts(array $opts = []): int {
    $db     = getDB();
    $where  = ["p.status = 'published'"];
    $params = [];
    if (!empty($opts['type'])) { $where[]="p.post_type = ?"; $params[]=$opts['type']; }
    if (!empty($opts['search'])) { $where[]="(p.title ILIKE ? OR p.summary ILIKE ? OR p.content ILIKE ?)"; $s='%'.$opts['search'].'%'; $params=array_merge($params,[$s,$s,$s]); }
    if (!empty($opts['cat'])) { $where[]="p.category_id = ?"; $params[]=(int)$opts['cat']; }
    if (!empty($opts['topic'])) { $where[]="EXISTS (SELECT 1 FROM post_topics pt WHERE pt.post_id=p.id AND pt.topic_id=?)"; $params[]=(int)$opts['topic']; }
    if (!empty($opts['section'])) { $where[]="? = ANY(string_to_array(REPLACE(p.page_section, ' ', ''), ','))"; $params[]=$opts['section']; }
    $whereStr = implode(' AND ', $where);
    $stmt = $db->prepare("SELECT COUNT(*) FROM posts p WHERE $whereStr");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function getPost(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, u.full_name AS author_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN users u ON u.id = p.author_id WHERE p.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getPostBySlug(string $slug): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, u.full_name AS author_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN users u ON u.id = p.author_id WHERE p.slug = ? AND p.status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ─── Topics (core) ───────────────────────────────────────────────────────────

function getTopics(array $opts = []): array {
    try {
        $db = getDB();
        $where = ["1=1"]; $params=[];
        if (isset($opts['active'])) { $where[]="is_active=?"; $params[]=(int)$opts['active']; }
        if (isset($opts['featured'])) { $where[]="is_featured=?"; $params[]=(int)$opts['featured']; }
        if (array_key_exists('parent', $opts)) {
            if ($opts['parent'] === null) $where[]="parent_id IS NULL";
            else { $where[]="parent_id=?"; $params[]=(int)$opts['parent']; }
        }
        $whereStr = implode(' AND ', $where);
        $limit = isset($opts['limit']) ? (int)$opts['limit'] : 100;
        if ($limit>200) $limit=200;
        $sql = "SELECT * FROM topics WHERE $whereStr ORDER BY sort_order ASC, name ASC LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function getTopicBySlug(string $slug): ?array {
    try {
        $db=getDB();
        $stmt=$db->prepare("SELECT * FROM topics WHERE slug=? LIMIT 1");
        $stmt->execute([$slug]);
        $row=$stmt->fetch(); return $row ?: null;
    } catch (PDOException $e) { return null; }
}

function getTopicById(int $id): ?array {
    try { $db=getDB(); $stmt=$db->prepare("SELECT * FROM topics WHERE id=?"); $stmt->execute([$id]); $row=$stmt->fetch(); return $row ?: null; } catch(PDOException $e){ return null; }
}

function getTopicTree(): array {
    $all = getTopics(['active'=>1]);
    // map id => children
    $byParent = [];
    foreach ($all as $t) {
        $pid = $t['parent_id'] ?? null;
        $byParent[$pid === null ? 0 : (int)$pid][] = $t;
    }
    $build = function($parentId) use (&$build, $byParent) {
        $out=[];
        $key = $parentId === null ? 0 : $parentId;
        foreach ($byParent[$key] ?? [] as $node) {
            $node['children'] = $build((int)$node['id']);
            $out[]=$node;
        }
        return $out;
    };
    return $build(null);
}

function getTopicChildren(int $parentId): array {
    try {
        $stmt=getDB()->prepare("SELECT * FROM topics WHERE parent_id=? AND is_active=1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$parentId]); return $stmt->fetchAll();
    } catch(PDOException $e){ return []; }
}

function getTopicBreadcrumbs(int $topicId): array {
    $crumbs=[]; $current=getTopicById($topicId);
    $guard=0;
    while($current && $guard<10){
        array_unshift($crumbs,$current);
        if(empty($current['parent_id'])) break;
        $current=getTopicById((int)$current['parent_id']);
        $guard++;
    }
    return $crumbs;
}

function getTopicsForPost(int $postId): array {
    try{
        $db=getDB();
        $stmt=$db->prepare("SELECT t.* FROM topics t JOIN post_topics pt ON pt.topic_id=t.id WHERE pt.post_id=? ORDER BY t.sort_order");
        $stmt->execute([$postId]); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function getTopicsForLesson(int $lessonId): array {
    try{
        $db=getDB();
        $stmt=$db->prepare("SELECT t.* FROM topics t JOIN lesson_topics lt ON lt.topic_id=t.id WHERE lt.lesson_id=? ORDER BY t.sort_order");
        $stmt->execute([$lessonId]); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function getTopicsForBook(int $bookId): array {
    try{
        $db=getDB();
        $stmt=$db->prepare("SELECT t.* FROM topics t JOIN book_topics bt ON bt.topic_id=t.id WHERE bt.book_id=? ORDER BY t.sort_order");
        $stmt->execute([$bookId]); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}

function setPostTopics(int $postId, array $topicIds): void {
    $db=getDB();
    $db->prepare("DELETE FROM post_topics WHERE post_id=?")->execute([$postId]);
    foreach(array_unique(array_filter(array_map('intval',$topicIds))) as $tid){
        $db->prepare("INSERT INTO post_topics (post_id, topic_id) VALUES (?,?) ON CONFLICT DO NOTHING")->execute([$postId,$tid]);
    }
}
function setLessonTopics(int $lessonId, array $topicIds): void {
    $db=getDB();
    $db->prepare("DELETE FROM lesson_topics WHERE lesson_id=?")->execute([$lessonId]);
    foreach(array_unique(array_filter(array_map('intval',$topicIds))) as $tid){
        $db->prepare("INSERT INTO lesson_topics (lesson_id, topic_id) VALUES (?,?) ON CONFLICT DO NOTHING")->execute([$lessonId,$tid]);
    }
}
function setBookTopics(int $bookId, array $topicIds): void {
    $db=getDB();
    $db->prepare("DELETE FROM book_topics WHERE book_id=?")->execute([$bookId]);
    foreach(array_unique(array_filter(array_map('intval',$topicIds))) as $tid){
        $db->prepare("INSERT INTO book_topics (book_id, topic_id) VALUES (?,?) ON CONFLICT DO NOTHING")->execute([$bookId,$tid]);
    }
}


function getTopicIdsForPost(int $postId): array {
    return array_map('intval', array_column(getTopicsForPost($postId),'id'));
}
function getTopicIdsForBook(int $bookId): array {
    return array_map('intval', array_column(getTopicsForBook($bookId),'id'));
}
function getTopicIdsForLesson(int $lessonId): array {
    return array_map('intval', array_column(getTopicsForLesson($lessonId),'id'));
}

function getPostsByTopic(int $topicId, array $opts = []): array {
    $type = $opts['type'] ?? null;
    $limit = (int)($opts['limit'] ?? 6);
    if($limit<1) $limit=6; if($limit>50) $limit=50;
    try{
        $db=getDB();
        $where=["p.status='published'","pt.topic_id=?"];
        $params=[$topicId];
        if($type){ $where[]="p.post_type=?"; $params[]=$type; }
        $whereStr=implode(' AND ',$where);
        $stmt=$db->prepare("SELECT p.*, c.name AS cat_name FROM posts p JOIN post_topics pt ON pt.post_id=p.id LEFT JOIN categories c ON c.id=p.category_id WHERE $whereStr ORDER BY p.published_at DESC LIMIT $limit");
        $stmt->execute($params); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function countPostsByTopic(int $topicId): int {
    try{ $stmt=getDB()->prepare("SELECT COUNT(*) FROM post_topics pt JOIN posts p ON p.id=pt.post_id WHERE pt.topic_id=? AND p.status='published'"); $stmt->execute([$topicId]); return (int)$stmt->fetchColumn(); }catch(PDOException $e){ return 0; }
}
function getLessonsByTopic(int $topicId, int $limit=6): array {
    try{
        $stmt=getDB()->prepare("SELECT l.* FROM lessons l JOIN lesson_topics lt ON lt.lesson_id=l.id WHERE lt.topic_id=? AND l.status='published' ORDER BY l.created_at DESC LIMIT $limit");
        $stmt->execute([$topicId]); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function getBooksByTopic(int $topicId, int $limit=6): array {
    try{
        $stmt=getDB()->prepare("SELECT b.* FROM books b JOIN book_topics bt ON bt.book_id=b.id WHERE bt.topic_id=? AND b.status='published' ORDER BY b.created_at DESC LIMIT $limit");
        $stmt->execute([$topicId]); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}

// ─── Lessons with collections ─────────────────────────────────────────────────

function getLesson(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM lessons WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Published lesson with its collection/volume titles (detail-page resolver). */
function getLessonBySlug(string $slug): ?array {
    if($slug==='') return null;
    try{
        $stmt=getDB()->prepare("SELECT l.*, lc.title AS collection_title, lc.slug AS collection_slug, lv.title AS volume_title, lv.slug AS volume_slug FROM lessons l LEFT JOIN lesson_collections lc ON lc.id=l.collection_id LEFT JOIN lesson_volumes lv ON lv.id=l.volume_id WHERE l.slug=? AND l.status='published' LIMIT 1");
        $stmt->execute([$slug]);
        $row=$stmt->fetch(); return $row ?: null;
    }catch(PDOException $e){ return null; }
}

function getLessonCollections(array $opts=[]): array {
    try{
        $db=getDB();
        $where=["1=1"]; $params=[];
        if(isset($opts['active'])){ $where[]="is_active=?"; $params[]=(int)$opts['active']; }
        if(isset($opts['featured'])){ $where[]="is_featured=?"; $params[]=(int)$opts['featured']; }
        $whereStr=implode(' AND ',$where);
        $stmt=$db->prepare("SELECT * FROM lesson_collections WHERE $whereStr ORDER BY sort_order ASC, title ASC");
        $stmt->execute($params); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function getLessonCollectionBySlug(string $slug): ?array {
    try{ $stmt=getDB()->prepare("SELECT * FROM lesson_collections WHERE slug=? LIMIT 1"); $stmt->execute([$slug]); $row=$stmt->fetch(); return $row ?: null; }catch(PDOException $e){ return null; }
}
function getLessonVolumes(int $collectionId): array {
    try{ $stmt=getDB()->prepare("SELECT * FROM lesson_volumes WHERE collection_id=? ORDER BY sort_order ASC, title ASC"); $stmt->execute([$collectionId]); return $stmt->fetchAll(); }catch(PDOException $e){ return []; }
}
function getLessonVolumeById(int $id): ?array {
    try{ $stmt=getDB()->prepare("SELECT * FROM lesson_volumes WHERE id=?"); $stmt->execute([$id]); $row=$stmt->fetch(); return $row ?: null; }catch(PDOException $e){ return null; }
}
function getLessonsByCollection(int $collectionId, int $volumeId=0, int $limit=100): array {
    try{
        $db=getDB();
        if($volumeId){
            $stmt=$db->prepare("SELECT * FROM lessons WHERE collection_id=? AND volume_id=? AND status='published' ORDER BY lesson_number ASC NULLS LAST, sort_order ASC, id ASC LIMIT $limit");
            $stmt->execute([$collectionId,$volumeId]);
        } else {
            $stmt=$db->prepare("SELECT * FROM lessons WHERE collection_id=? AND status='published' ORDER BY volume_id ASC NULLS FIRST, lesson_number ASC NULLS LAST, sort_order ASC LIMIT $limit");
            $stmt->execute([$collectionId]);
        }
        return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function getAdjacentLesson(array $lesson): array {
    // next / prev within same collection/volume ordered by lesson_number
    try{
        $db=getDB();
        $curNum = $lesson['lesson_number'] ?? null;
        $col = $lesson['collection_id']; $vol = $lesson['volume_id'];
        if(!$col || $curNum===null) return ['prev'=>null,'next'=>null];
        $prev = $db->prepare("SELECT * FROM lessons WHERE collection_id=? AND ".($vol?"volume_id=? AND ":"")." lesson_number < ? AND status='published' ORDER BY lesson_number DESC LIMIT 1");
        $next = $db->prepare("SELECT * FROM lessons WHERE collection_id=? AND ".($vol?"volume_id=? AND ":"")." lesson_number > ? AND status='published' ORDER BY lesson_number ASC LIMIT 1");
        if($vol){ $prev->execute([$col,$vol,$curNum]); $next->execute([$col,$vol,$curNum]);}
        else { $prev->execute([$col,$curNum]); $next->execute([$col,$curNum]);}
        return ['prev'=>$prev->fetch() ?: null, 'next'=>$next->fetch() ?: null];
    }catch(PDOException $e){ return ['prev'=>null,'next'=>null]; }
}

// ─── Categories (legacy) ──────────────────────────────────────────────────────

function getCategories(): array {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT c.*, COUNT(p.id) AS post_count FROM categories c LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published' GROUP BY c.id ORDER BY c.sort_order ASC, c.name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}
function getCategoryBySlug(string $slug): ?array {
    $db=getDB(); $stmt=$db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1"); $stmt->execute([$slug]); $row=$stmt->fetch(); return $row ?: null;
}

// ─── Settings ─────────────────────────────────────────────────────────────────

function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $db = getDB();
        // setting_key: "key" is a reserved word in MySQL.
        try { $rows=$db->query("SELECT setting_key, value FROM settings")->fetchAll(); $cache=array_column($rows,'value','setting_key'); } catch (PDOException $e) { $cache=[]; }
    }
    return $cache[$key] ?? $default;
}
function clearSettingCache(): void { /* for admin save */ }

// ─── Banners ──────────────────────────────────────────────────────────────────

function getActiveBanners(int $limit=3): array {
    // Prefer featured_banners (new dynamic announcement) then fallback to site_banners
    try{
        $db=getDB();
        try{
            $stmt=$db->prepare("SELECT id, title, description AS content, image, link_url, button_text AS link_text, is_active, sort_order, created_at FROM featured_banners WHERE is_active=1 ORDER BY sort_order ASC, id DESC LIMIT $limit");
            $stmt->execute(); $rows=$stmt->fetchAll();
            if($rows) return $rows;
        }catch(PDOException $e){}
        $stmt=$db->prepare("SELECT * FROM site_banners WHERE is_active=1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY sort_order ASC, id DESC LIMIT $limit");
        $stmt->execute(); return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function getActiveBanner(): ?array {
    $banners=getActiveBanners(1);
    return $banners[0] ?? null;
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function paginate(int $total, int $limit, int $current, string $urlPattern): string {
    if ($limit <= 0) $limit = 1;
    $pages = (int)ceil($total / $limit);
    if ($pages <= 1) return '';
    $range = 2;
    $start = max(1, $current - $range);
    $end   = min($pages, $current + $range);
    /**
     * Only the page placeholder is substituted. sprintf() would throw a
     * ValueError on percent-encoded query values that callers embed in the
     * pattern (urlencode('ا') → %D8%A7 → unknown format specifier "D").
     */
    $pageUrl = static function (int $number) use ($urlPattern): string {
        if (str_contains($urlPattern, '%d')) return str_replace('%d', (string)$number, $urlPattern);
        try { return sprintf($urlPattern, $number); } catch (Throwable) { return $urlPattern; }
    };
    $html  = '<nav aria-label="صفحه‌بندی"><ul class="pagination justify-content-center flex-wrap">';
    if ($current > 1) $html .= '<li class="page-item"><a class="page-link" href="' . $pageUrl($current - 1) . '">&#8250; قبلی</a></li>';
    if ($start > 1) { $html .= '<li class="page-item"><a class="page-link" href="' . $pageUrl(1) . '">1</a></li>'; if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
    for ($i=$start;$i<=$end;$i++){ $active=($i===$current)?' active':''; $html.='<li class="page-item'.$active.'"><a class="page-link" href="'.$pageUrl($i).'">'.$i.'</a></li>'; }
    if ($end<$pages){ if($end<$pages-1) $html.='<li class="page-item disabled"><span class="page-link">...</span></li>'; $html.='<li class="page-item"><a class="page-link" href="'.$pageUrl($pages).'">'.$pages.'</a></li>'; }
    if ($current<$pages) $html .= '<li class="page-item"><a class="page-link" href="' . $pageUrl($current + 1) . '">بعدی &#8249;</a></li>';
    return $html . '</ul></nav>';
}

// ─── Post Type Labels ─────────────────────────────────────────────────────────

function postTypeLabel(string $type): string {
    return match($type) {
        'news'         => 'خبر',
        'article'      => 'مقاله',
        'research'     => 'پژوهش',
        'report'       => 'گزارش',
        'announcement' => 'اطلاعیه',
        'speech'       => 'سخنرانی',
        'program'      => 'برنامه آموزشی',
        'religious'    => 'فعالیت مذهبی',
        'qa'           => 'پرسش و پاسخ',
        default        => 'مطلب',
    };
}
function postTypeBadge(string $type): string {
    $colors = ['news'=>'success','article'=>'primary','research'=>'dark','report'=>'warning','announcement'=>'warning','speech'=>'info','program'=>'secondary','religious'=>'danger','qa'=>'primary'];
    $color = $colors[$type] ?? 'dark';
    return '<span class="badge bg-' . $color . '">' . postTypeLabel($type) . '</span>';
}
function excerpt(string $text, int $chars = 150): string {
    $text = strip_tags($text);
    if (mb_strlen($text, 'UTF-8') <= $chars) return $text;
    return mb_substr($text, 0, $chars, 'UTF-8') . '...';
}

// ─── Media Table fallback ─────────────────────────────────────────────────────
if (!function_exists('ensureMediaTable')) {
    function ensureMediaTable(): void {}
}

// ─── Books ────────────────────────────────────────────────────────────────────

function ensureBooksTable(): void {}

function getBooks(array $opts = []): array {
    ensureBooksTable();
    try {
        $db=getDB(); $limit=(int)($opts['limit']??20); $offset=(int)($opts['offset']??0); $search=$opts['search']??''; $topic=$opts['topic']??null; $featured=$opts['featured']??null;
        $where=['b.status=\'published\'']; $params=[];
        if($search){ $where[]="(b.title ILIKE ? OR b.description ILIKE ? OR b.author ILIKE ?)"; $s='%'.$search.'%'; $params=array_merge($params,[$s,$s,$s]); }
        if($topic){ $where[]="EXISTS (SELECT 1 FROM book_topics bt WHERE bt.book_id=b.id AND bt.topic_id=?)"; $params[]=(int)$topic; }
        if($featured){ $where[]="b.is_featured=1"; }
        $whereStr=implode(' AND ',$where);
        $stmt=$db->prepare("SELECT b.* FROM books b WHERE $whereStr ORDER BY b.is_featured DESC, b.created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params,[$limit,$offset])); return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}
function countBooks(array $opts = []): int {
    ensureBooksTable();
    try {
        $db=getDB(); $search=$opts['search']??''; $topic=$opts['topic']??null; $where=['b.status=\'published\'']; $params=[];
        if($search){ $where[]="(b.title ILIKE ? OR b.description ILIKE ? OR b.author ILIKE ?)"; $s='%'.$search.'%'; $params=array_merge($params,[$s,$s,$s]); }
        if($topic){ $where[]="EXISTS (SELECT 1 FROM book_topics bt WHERE bt.book_id=b.id AND bt.topic_id=?)"; $params[]=(int)$topic; }
        $whereStr=implode(' AND ',$where);
        $stmt=$db->prepare("SELECT COUNT(*) FROM books b WHERE $whereStr"); $stmt->execute($params); return (int)$stmt->fetchColumn();
    } catch (PDOException $e) { return 0; }
}
function getBookById(int $id): ?array {
    try{ $stmt=getDB()->prepare("SELECT * FROM books WHERE id=? LIMIT 1"); $stmt->execute([$id]); $row=$stmt->fetch(); return $row?:null; }catch(PDOException $e){ return null; }
}
function getBookBySlug(string $slug): ?array {
    if($slug==='') return null;
    try{ $stmt=getDB()->prepare("SELECT * FROM books WHERE slug=? LIMIT 1"); $stmt->execute([$slug]); $row=$stmt->fetch(); return $row?:null; }catch(PDOException $e){ return null; }
}
/** Published books sharing any topic with the given book (newest first). */
function getRelatedBooks(int $bookId, int $limit=6): array {
    if($bookId<1 || $limit<1) return [];
    try{
        $topics=getTopicsForBook($bookId);
        if(!$topics) return [];
        $ids=array_column($topics,'id');
        $in=implode(',', array_fill(0,count($ids),'?'));
        $stmt=getDB()->prepare("SELECT b.* FROM books b JOIN book_topics bt ON bt.book_id=b.id WHERE bt.topic_id IN ($in) AND b.id<>? AND b.status='published' GROUP BY b.id ORDER BY b.created_at DESC LIMIT $limit");
        $stmt->execute(array_merge($ids,[$bookId]));
        return $stmt->fetchAll();
    }catch(PDOException $e){ return []; }
}
function uploadBookFile(array $file, string $type = 'pdf'): string {
    return uploadFile($file, $type === 'pdf' ? 'pdf' : 'word', UPLOAD_DOCUMENTS);
}

// ─── Search helper (unified) ──────────────────────────────────────────────────

function searchAll(string $q, int $limit=12, int $offset=0): array {
    if(!$q) return ['total'=>0,'results'=>[]];
    $qLike='%'.$q.'%';
    try{
        $db=getDB();
        // topics
        $topicsStmt=$db->prepare("SELECT id, name AS title, slug, description AS summary, intro AS content, '' AS featured_image, 'topic' AS post_type, created_at AS published_at, created_at, 'topic' AS target FROM topics WHERE is_active=1 AND (name ILIKE ? OR description ILIKE ?) LIMIT 5");
        $topicsStmt->execute([$qLike,$qLike]);
        $topics=$topicsStmt->fetchAll();

        $sql="FROM (
            SELECT id,title,slug,summary,content,featured_image,post_type,published_at,created_at,'post' AS target, is_featured FROM posts WHERE status='published'
            UNION ALL
            SELECT id,title,slug,summary,content,featured_image,'lesson',created_at,created_at,'lesson', is_featured FROM lessons WHERE status='published'
            UNION ALL
            SELECT id,title,NULL AS slug, description AS summary, description AS content, cover_image AS featured_image,'book',created_at,created_at,'book', is_featured FROM books WHERE status='published'
        ) results WHERE title ILIKE ? OR summary ILIKE ? OR content ILIKE ?";
        $cntStmt=$db->prepare('SELECT COUNT(*) '.$sql);
        $cntStmt->execute([$qLike,$qLike,$qLike]);
        $total=(int)$cntStmt->fetchColumn() + count($topics);

        $stmt=$db->prepare('SELECT * '.$sql.' ORDER BY is_featured DESC, published_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$qLike,$qLike,$qLike,$limit,$offset]);
        $results=$stmt->fetchAll();
        // prepend topics if offset 0
        if($offset===0) $results=array_merge($topics,$results);
        return ['total'=>$total,'results'=>array_slice($results,0,$limit)];
    }catch(PDOException $e){ return ['total'=>0,'results'=>[]]; }
}

// ─── SEO helpers ──────────────────────────────────────────────────────────────

function canonicalUrl(string $path): string {
    return absolute_url($path);
}
function breadcrumbsJsonLd(array $crumbs): string {
    $list=[]; $pos=1;
    foreach($crumbs as $c){
        $list[]=['@type'=>'ListItem','position'=>$pos++,'name'=>$c['name'],'item'=>$c['url'] ?? null];
    }
    return json_encode(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$list], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
}
function articleJsonLd(array $post): string {
    if(!SITE_URL) return '';
    $canonical = canonicalUrl(postUrl($post));
    $organization = getSetting('site_name', SITE_NAME);
    if ($organization === 'مدرسه علمیه جامعه‌الهدی') $organization = SITE_NAME;
    $author = !empty($post['author_name'])
        ? ['@type' => 'Person', 'name' => $post['author_name']]
        : ['@type' => 'Organization', 'name' => $organization];
    $data=['@context'=>'https://schema.org','@type'=>'Article','headline'=>$post['title'],'mainEntityOfPage'=>['@type'=>'WebPage','@id'=>$canonical],'url'=>$canonical,'datePublished'=>$post['published_at'] ?? $post['created_at'],'dateModified'=>$post['updated_at'] ?? $post['published_at'],'author'=>$author,'publisher'=>['@type'=>'Organization','name'=>$organization,'logo'=>['@type'=>'ImageObject','url'=>canonicalUrl('assets/img/logo.jpg')]]];
    if(!empty($post['featured_image'])) $data['image']=imgUrl($post['featured_image']);
    if(!empty($post['summary'])) $data['description']=excerpt($post['summary'],160);
    return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
}
function bookJsonLd(array $book): string {
    if(!SITE_URL) return '';
    $data=['@context'=>'https://schema.org','@type'=>'Book','name'=>$book['title']];
    if(!empty($book['author'])) $data['author']=['@type'=>'Person','name'=>$book['author']];
    if(!empty($book['description'])) $data['description']=excerpt($book['description'],200);
    if(!empty($book['cover_image'])) $data['image']=imgUrl($book['cover_image']);
    return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
}

// ─── Safe rich text ───────────────────────────────────────────────────────────

function safeRichText(?string $html): string {
    if (!$html) return '';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8\"><div>' . $html . '</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    $allowed = ['p','div','span','strong','b','em','i','u','ul','ol','li','blockquote','h2','h3','h4','h5','br','hr','a','img','table','thead','tbody','tr','th','td'];
    $clean = function (DOMNode $node) use (&$clean,$allowed): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) { $node->removeChild($child); continue; }
            if (!($child instanceof DOMElement)) continue;
            $tag = strtolower($child->tagName);
            if (in_array($tag,['script','style','iframe','object','embed','svg','math','form','input','button','meta','link','base'],true)) { $node->removeChild($child); continue; }
            $clean($child);
            if (!in_array($tag,$allowed,true)) {
                while ($child->firstChild) $node->insertBefore($child->firstChild,$child);
                $node->removeChild($child); continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name=strtolower($attr->name); $value=$attr->value;
                $keep = in_array($name,['title','alt'],true);
                if (($tag==='a' && $name==='href') || ($tag==='img' && $name==='src')) {
                    $keep = safeExternalUrl($value)!=='' || (str_starts_with($value,'/') && !str_starts_with($value,'//') && !str_contains($value,'\\'));
                }
                if (!$keep) $child->removeAttribute($attr->name);
            }
            if ($tag==='a') $child->setAttribute('rel','noopener noreferrer');
            if ($tag==='img') { $child->setAttribute('loading','lazy'); $child->setAttribute('decoding','async'); }
        }
    };
    $body=$dom->getElementsByTagName('body')->item(0);
    if (!$body) return '';
    $clean($body); $out='';
    foreach ($body->childNodes as $child) $out.=$dom->saveHTML($child);
    return $out;
}
function safeExternalUrl(string $url): string {
    return preg_match('~^https://~i', $url) && filter_var($url,FILTER_VALIDATE_URL) && !preg_match('/[<>"\x00-\x20]/', $url) ? $url : '';
}
