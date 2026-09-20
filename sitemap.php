<?php
require_once __DIR__ . '/includes/functions.php';
if (!SITE_URL || !safeExternalUrl(SITE_URL)) { http_response_code(503); exit; }
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=900');
$paths=['audio','video','files','','about.php','contact.php','news.php','articles.php','lessons.php','books.php','speeches.php','programs.php','religious-activities.php','announcements.php'];
$db=getDB();
foreach ($db->query("SELECT slug, post_type FROM posts WHERE status='published' ORDER BY id LIMIT 10000") as $row) $paths[]=($row['post_type']==='speech'?'speech.php':'post.php').'?slug='.rawurlencode($row['slug']);
foreach ($db->query("SELECT slug FROM lessons WHERE status='published' ORDER BY id LIMIT 10000") as $row) $paths[]='lesson.php?slug='.rawurlencode($row['slug']);
foreach ($db->query('SELECT id FROM books ORDER BY id LIMIT 10000') as $row) $paths[]='book.php?id='.(int)$row['id'];
foreach (getCategories() as $row) $paths[]='category.php?slug='.rawurlencode($row['slug']);
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach($paths as $path) echo '<url><loc>'.htmlspecialchars(rtrim(SITE_URL,'/').'/'.$path,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc></url>';
echo '</urlset>';
