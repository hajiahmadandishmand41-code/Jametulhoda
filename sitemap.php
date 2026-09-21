<?php
require_once __DIR__ . '/includes/functions.php';
if (!SITE_URL || !safeExternalUrl(SITE_URL)) { http_response_code(503); exit; }
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=900');

$paths = [
    ''=>'',
    'about.php'=>'about.php',
    'contact.php'=>'contact.php',
    'search.php'=>'search.php',
    'topics.php'=>'topics.php',
    'reports.php'=>'reports.php',
    'articles.php'=>'articles.php',
    'research.php'=>'research.php',
    'qa.php'=>'qa.php',
    'lessons.php'=>'lessons.php',
    'books.php'=>'books.php',
    'speeches.php'=>'speeches.php',
    'news.php'=>'news.php',
    'announcements.php'=>'announcements.php',
];

$db=getDB();
// Topics
try{
    foreach($db->query("SELECT slug, updated_at FROM topics WHERE is_active=1 ORDER BY id LIMIT 10000") as $row){
        $paths['topic:'.$row['slug']] = 'topic.php?slug='.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Posts with lastmod
try{
    foreach($db->query("SELECT slug, post_type, updated_at FROM posts WHERE status='published' ORDER BY id LIMIT 10000") as $row){
        $paths['post:'.$row['slug']] = ($row['post_type']==='speech'?'speech.php':'post.php').'?slug='.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Lessons
try{
    foreach($db->query("SELECT slug, updated_at FROM lessons WHERE status='published' ORDER BY id LIMIT 10000") as $row){
        $paths['lesson:'.$row['slug']] = 'lesson.php?slug='.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Lesson collections
try{
    foreach($db->query("SELECT slug FROM lesson_collections WHERE is_active=1 LIMIT 1000") as $row){
        $paths['coll:'.$row['slug']] = 'lessons.php?collection='.rawurlencode($row['slug']);
    }
}catch(Exception $e){}
// Books (prefer slug, fallback to id)
try{
    foreach($db->query("SELECT id, slug, updated_at FROM books WHERE status='published' ORDER BY id LIMIT 10000") as $row){
        $slug=trim($row['slug']??'');
        $paths['book:'.$row['id']] = $slug ? 'book.php?slug='.rawurlencode($slug) : 'book.php?id='.(int)$row['id'];
    }
}catch(Exception $e){}
// Categories (legacy)
try{
    foreach(getCategories() as $row) $paths['cat:'.$row['slug']]='category.php?slug='.rawurlencode($row['slug']);
}catch(Exception $e){}

echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach($paths as $path){
    if($path==='') $path='';
    echo '<url><loc>'.htmlspecialchars(rtrim(SITE_URL,'/').'/'.ltrim($path,'/'),ENT_XML1|ENT_QUOTES,'UTF-8').'</loc>';
    // priority based on type
    if(str_starts_with($path,'topic.php')) echo '<priority>0.9</priority>';
    elseif(str_starts_with($path,'post.php') || str_starts_with($path,'lesson.php')) echo '<priority>0.8</priority>';
    elseif($path==='') echo '<priority>1.0</priority>';
    echo '</url>';
}
echo '</urlset>';
