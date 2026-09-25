<?php
require_once __DIR__ . '/includes/functions.php';
// Sitemap needs an absolute base URL
$__sitemapBase = SITE_URL && filter_var(SITE_URL, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', SITE_URL)
    ? rtrim(SITE_URL, '/')
    : 'https://jametulhoda.gt.tc';
// SITE_URL may intentionally be only an origin while BASE_PATH denotes a
// subdirectory. Include that prefix exactly once in every sitemap location.
if (BASE_PATH !== '' && !str_ends_with($__sitemapBase, BASE_PATH)) {
    $__sitemapBase .= BASE_PATH;
}
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=900');

// نشانی‌های تمیز و استاندارد نقشه سایت
$paths = [
    ''                      => '',
    'news'                  => 'news',
    'articles'              => 'articles',
    'reports'               => 'reports',
    'events'                => 'events',
    'books'                 => 'books',
    'lessons'               => 'lessons',
    'research'              => 'research',
    'media'                 => 'media',
    'topics'                => 'topics',
    'about'                 => 'about',
    'contact'               => 'contact',
    'speeches'              => 'speeches',
    'announcements'         => 'announcements',
    'programs'              => 'programs',
    'religious-activities'  => 'religious-activities',
    'videos'                => 'videos',
    'audios'                => 'audios',
    'qa'                    => 'qa',
];

$db = getDB();
// Topics
try {
    foreach ($db->query("SELECT slug, updated_at FROM topics WHERE is_active=1 ORDER BY id LIMIT 10000") as $row) {
        $paths['topic:' . $row['slug']] = 'topic/' . rawurlencode($row['slug']);
    }
} catch (Exception $e) {}

// Posts with typed URLs (same canonical logic as postUrl())
try {
    foreach ($db->query("SELECT slug, post_type, updated_at FROM posts WHERE status='published' ORDER BY id LIMIT 10000") as $row) {
        $prefix = match($row['post_type']) {
            'article' => 'article/',
            'news'    => 'news/',
            'research'=> 'research/',
            'report'  => 'report/',
            'speech'  => 'speech/',
            'program', 'religious', 'announcement' => 'event/',
            default   => 'post/',
        };
        $paths['post:' . $row['slug']] = $prefix . rawurlencode($row['slug']);
    }
} catch (Exception $e) {}

// Media detail pages (/video/{id}, /audio/{id})
try {
    foreach ($db->query("SELECT m.id, m.kind FROM media_files m LEFT JOIN posts p ON p.id=m.ref_id AND m.ref_type='post' LEFT JOIN lessons l ON l.id=m.ref_id AND m.ref_type='lesson' WHERE (p.status='published' OR l.status='published') ORDER BY m.id LIMIT 10000") as $row) {
        $paths['media:' . $row['id']] = ($row['kind'] === 'audio' ? 'audio/' : 'video/') . (int)$row['id'];
    }
} catch (Exception $e) {}

// Lessons
try {
    foreach ($db->query("SELECT slug, updated_at FROM lessons WHERE status='published' ORDER BY id LIMIT 10000") as $row) {
        $paths['lesson:' . $row['slug']] = 'lesson/' . rawurlencode($row['slug']);
    }
} catch (Exception $e) {}

// Lesson collections
try {
    foreach ($db->query("SELECT slug FROM lesson_collections WHERE is_active=1 LIMIT 1000") as $row) {
        $paths['coll:' . $row['slug']] = 'lessons/' . rawurlencode($row['slug']);
    }
} catch (Exception $e) {}

// Books
try {
    foreach ($db->query("SELECT id, slug, updated_at FROM books WHERE status='published' ORDER BY id LIMIT 10000") as $row) {
        $slug = trim($row['slug'] ?? '');
        $paths['book:' . $row['id']] = $slug ? 'book/' . rawurlencode($slug) : 'book/' . (int)$row['id'];
    }
} catch (Exception $e) {}

// Categories
try {
    foreach (getCategories() as $row) {
        $paths['cat:' . $row['slug']] = 'category/' . rawurlencode($row['slug']);
    }
} catch (Exception $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $path) {
    $loc = rtrim($__sitemapBase, '/') . '/' . ltrim($path, '/');
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>' . "\n";
    if (str_starts_with($path, 'topic/')) {
        echo '    <priority>0.9</priority>' . "\n";
    } elseif (str_starts_with($path, 'post/') || str_starts_with($path, 'news/') || str_starts_with($path, 'article/') || str_starts_with($path, 'lesson/')) {
        echo '    <priority>0.8</priority>' . "\n";
    } elseif ($path === '') {
        echo '    <priority>1.0</priority>' . "\n";
    } else {
        echo '    <priority>0.7</priority>' . "\n";
    }
    echo '  </url>' . "\n";
}
echo '</urlset>' . "\n";
