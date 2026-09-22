<?php
require_once __DIR__ . '/includes/functions.php';
// Sitemap needs an absolute base URL, but it is valid over plain HTTP too
// (local/staging hosts); only refuse when SITE_URL is missing or malformed.
$__sitemapBase = SITE_URL && filter_var(SITE_URL, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', SITE_URL) ? rtrim(SITE_URL, '/') : '';
if ($__sitemapBase === '') { http_response_code(503); exit; }
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=900');

// نشانی‌های تمیز (pretty URLs) — همان شکل‌هایی که در config/routes.php ثبت شده‌اند.
$paths = [
    ''                      => '',
    'about'                 => 'about',
    'contact'               => 'contact',
    'search'                => 'search',
    'topics'                => 'topics',
    'reports'               => 'reports',
    'articles'              => 'articles',
    'research'              => 'research',
    'qa'                    => 'qa',
    'lessons'               => 'lessons',
    'books'                 => 'books',
    'speeches'              => 'speeches',
    'news'                  => 'news',
    'announcements'         => 'announcements',
    'programs'              => 'programs',
    'religious-activities'  => 'religious-activities',
    'videos'                => 'videos',
    'audios'                => 'audios',
];

$db=getDB();
// Topics
try{
    foreach($db->query("SELECT slug, updated_at FROM topics WHERE is_active=1 ORDER BY id LIMIT 10000") as $row){
        $paths['topic:'.$row['slug']] = 'topic/'.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Posts with lastmod — typed URLs (same mapping as postUrl()).
try{
    foreach($db->query("SELECT slug, post_type, updated_at FROM posts WHERE status='published' ORDER BY id LIMIT 10000") as $row){
        $prefix = match($row['post_type']) {
            'article' => 'article/', 'news' => 'news/', 'research' => 'research/',
            'speech' => 'speech/', default => 'post/',
        };
        $paths['post:'.$row['slug']] = $prefix.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Media detail pages (/video/{id}, /audio/{id}) with published parents.
try{
    foreach($db->query("SELECT m.id, m.kind FROM media_files m LEFT JOIN posts p ON p.id=m.ref_id AND m.ref_type='post' LEFT JOIN lessons l ON l.id=m.ref_id AND m.ref_type='lesson' WHERE (p.status='published' OR l.status='published') ORDER BY m.id LIMIT 10000") as $row){
        $paths['media:'.$row['id']] = ($row['kind']==='audio'?'audio/':'video/').(int)$row['id'];
    }
}catch(Exception $e){}
// Lessons
try{
    foreach($db->query("SELECT slug, updated_at FROM lessons WHERE status='published' ORDER BY id LIMIT 10000") as $row){
        $paths['lesson:'.$row['slug']] = 'lesson/'.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Lesson collections
try{
    foreach($db->query("SELECT slug FROM lesson_collections WHERE is_active=1 LIMIT 1000") as $row){
        $paths['coll:'.$row['slug']] = 'lessons/'.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Books (prefer slug, fallback to id)
try{
    foreach($db->query("SELECT id, slug, updated_at FROM books WHERE status='published' ORDER BY id LIMIT 10000") as $row){
        $slug=trim($row['slug']??'');
        $paths['book:'.$row['id']] = $slug ? 'book/'.rawurlencode($slug) : 'book/'.(int)$row['id'];
    }
}catch(Exception $e){}
// Categories (legacy)
try{
    foreach(getCategories() as $row) $paths['cat:'.$row['slug']]='category/'.rawurlencode($row['slug']);
}catch(Exception $e){}

echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach($paths as $path){
    if($path==='') $path='';
    echo '<url><loc>'.htmlspecialchars($__sitemapBase.'/'.ltrim($path,'/'),ENT_XML1|ENT_QUOTES,'UTF-8').'</loc>';
    // priority based on type
    if(str_starts_with($path,'topic/')) echo '<priority>0.9</priority>';
    elseif(str_starts_with($path,'post/') || str_starts_with($path,'lesson/')) echo '<priority>0.8</priority>';
    elseif($path==='') echo '<priority>1.0</priority>';
    echo '</url>';
}
echo '</urlset>';
