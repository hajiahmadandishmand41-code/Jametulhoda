<?php
require_once __DIR__ . '/includes/functions.php';
if (!SITE_URL || !safeExternalUrl(SITE_URL)) { http_response_code(503); exit; }
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
// Posts with lastmod
try{
    foreach($db->query("SELECT slug, post_type, updated_at FROM posts WHERE status='published' ORDER BY id LIMIT 10000") as $row){
        $paths['post:'.$row['slug']] = ($row['post_type']==='speech'?'speech/':'post/').rawurlencode($row['slug']);
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
    echo '<url><loc>'.htmlspecialchars(rtrim(SITE_URL,'/').'/'.ltrim($path,'/'),ENT_XML1|ENT_QUOTES,'UTF-8').'</loc>';
    // priority based on type
    if(str_starts_with($path,'topic/')) echo '<priority>0.9</priority>';
    elseif(str_starts_with($path,'post/') || str_starts_with($path,'lesson/')) echo '<priority>0.8</priority>';
    elseif($path==='') echo '<priority>1.0</priority>';
    echo '</url>';
}
echo '</urlset>';
