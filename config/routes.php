<?php
/**
 * config/routes.php — تنها مرجع مسیردهی سایت (single source of truth)
 *
 * `.htaccess` هر درخواستی را به `router.php` می‌فرستد و `router.php` فقط همین
 * جدول را می‌خواند؛ بنابراین هیچ فایل PHP دیگری از بیرون قابل اجرا نیست.
 *
 * ساختار:
 *   routes   → نشانی اصلی (canonical) ⇒ اسکریپت
 *              مقدار می‌تواند رشته (مسیر فایل) یا آرایه
 *              ['file' => …, 'get' => [پارامترهای پیش‌فرض]] باشد.
 *   aliases  → نشانی قدیمی ⇒ نشانی اصلی (همان اسکریپت، بدون ۳۰۱ اجباری)
 *   patterns → مسیرهای پویا (slug / id) با الگوی منظم
 *
 * هر نشانی اصلی به‌صورت خودکار سه شکل دیگر را هم پاسخ می‌دهد (توسط router.php):
 *   /about   /about/   /about.php    و برای پوشه‌ها:  /admin/users/index.php
 * پس نیازی به نوشتن دستی هر چهار شکل نیست.
 */

return [
    'routes' => [
        // ─── صفحات عمومی ────────────────────────────────────────────────
        '/'                     => 'index.php',
        '/index.php'            => 'index.php',
        '/about'                => 'pages/about.php',
        '/announcements'        => 'pages/announcements.php',
        '/articles'             => 'pages/articles.php',
        '/book'                 => 'pages/book.php',
        '/books'                => 'pages/books.php',
        '/category'             => 'pages/category.php',
        '/contact'              => 'pages/contact.php',
        '/lesson'               => 'pages/lesson.php',
        '/lessons'              => 'pages/lessons.php',
        '/media-library'        => 'pages/media-library.php',
        '/audio'                => ['file' => 'pages/media-library.php', 'get' => ['kind' => 'audio']],
        '/audios'               => ['file' => 'pages/media-library.php', 'get' => ['kind' => 'audio']],
        '/video'                => ['file' => 'pages/media-library.php', 'get' => ['kind' => 'video']],
        '/videos'               => ['file' => 'pages/media-library.php', 'get' => ['kind' => 'video']],
        '/news'                 => 'pages/news.php',
        '/post'                 => 'pages/post.php',
        '/programs'             => 'pages/programs.php',
        '/qa'                   => 'pages/qa.php',
        '/religious-activities' => 'pages/religious-activities.php',
        '/reports'              => 'pages/reports.php',
        '/research'             => 'pages/research.php',
        '/search'               => 'pages/search.php',
        '/speech'               => 'pages/speech.php',
        '/speeches'             => 'pages/speeches.php',
        '/topic'                => 'pages/topic.php',
        '/topics'               => 'pages/topics.php',
        '/sitemap.xml'          => 'sitemap.php',
        '/sitemap.php'          => 'sitemap.php',
        '/robots.txt'           => 'robots.php',
        '/robots.php'           => 'robots.php',

        // ─── نصب ────────────────────────────────────────────────────────
        '/php/install'          => 'php/install.php',

        // ─── پنل مدیریت ─────────────────────────────────────────────────
        '/admin'                     => 'admin/index.php',
        '/admin/login'               => 'admin/login.php',
        '/admin/logout'              => 'admin/logout.php',
        '/admin/change-password'     => 'admin/change-password.php',
        '/admin/settings'            => 'admin/settings.php',
        '/admin/users'               => 'admin/users/index.php',
        '/admin/messages'            => 'admin/messages/index.php',
        '/admin/media'               => 'admin/media/index.php',
        '/admin/categories'          => 'admin/categories/index.php',
        '/admin/topics'              => 'admin/topics/index.php',
        '/admin/topics/create'       => 'admin/topics/create.php',
        '/admin/topics/edit'         => 'admin/topics/edit.php',
        '/admin/articles'            => 'admin/articles/index.php',
        '/admin/articles/create'     => 'admin/articles/create.php',
        '/admin/articles/edit'       => 'admin/articles/edit.php',
        '/admin/articles/delete'     => 'admin/articles/delete.php',
        '/admin/books'               => 'admin/books/index.php',
        '/admin/books/create'        => 'admin/books/create.php',
        '/admin/books/edit'          => 'admin/books/edit.php',
        '/admin/books/delete'        => 'admin/books/delete.php',
        '/admin/lessons'             => 'admin/lessons/index.php',
        '/admin/lessons/create'      => 'admin/lessons/create.php',
        '/admin/lessons/edit'        => 'admin/lessons/edit.php',
        '/admin/lessons/delete'      => 'admin/lessons/delete.php',
        '/admin/lesson-collections'  => 'admin/lesson-collections/index.php',
        '/admin/news'                => 'admin/news/index.php',
        '/admin/news/create'         => 'admin/news/create.php',
        '/admin/news/edit'           => 'admin/news/edit.php',
        '/admin/news/delete'         => 'admin/news/delete.php',
        '/admin/posts'               => 'admin/posts/index.php',
        '/admin/posts/create'        => 'admin/posts/create.php',
        '/admin/posts/edit'          => 'admin/posts/edit.php',
        '/admin/posts/delete'        => 'admin/posts/delete.php',
        '/admin/speeches'            => 'admin/speeches/index.php',
        '/admin/speeches/create'     => 'admin/speeches/create.php',
        '/admin/speeches/edit'       => 'admin/speeches/edit.php',
        '/admin/speeches/delete'     => 'admin/speeches/delete.php',
        '/admin/banners'             => 'admin/banners/index.php',
    ],

    // نشانی‌های قدیمی که باید کار کنند
    'aliases' => [
        '/library'   => '/books',
        '/files'     => '/books',
        '/dashboard' => '/admin',
        '/login'     => '/admin/login',
        '/logout'    => '/admin/logout',
    ],

    // مسیرهای پویا: [الگو, اسکریپت (با جای‌گیری $n), نگاشت پارامترها]
    'patterns' => [
        ['~^/book/(\d+)/?$~D',                       'pages/book.php',    ['id' => 1]],
        ['~^/book/([^/]+)/?$~uD',                     'pages/book.php',    ['slug' => 1]],
        // Typed post URLs: /article/X, /news/X, /research/X — post.php renders
        // them and answers 404 when the slug belongs to another post type.
        ['~^/(article|news|research)/([^/]+)/?$~uD', 'pages/post.php',    ['expected_type' => 1, 'slug' => 2]],
        // Media detail pages: /video/{id}, /audio/{id} (media_files record).
        ['~^/(video|audio)/(\d+)/?$~D',               'pages/media.php',   ['kind' => 1, 'id' => 2]],
        ['~^/(post|lesson|speech|category|topic)/([^/]+)/?$~uD', 'pages/$1.php', ['slug' => 2]],
        ['~^/lessons/([^/]+)/([^/]+)/?$~uD',         'pages/lessons.php', ['collection' => 1, 'volume' => 2]],
        ['~^/lessons/([^/]+)/?$~uD',                 'pages/lessons.php', ['collection' => 1]],
        ['~^/search/([^/]+)/?$~uD',                  'pages/search.php',  ['q' => 1]],
    ],
];
