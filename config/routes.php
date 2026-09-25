<?php
/**
 * config/routes.php — تنها مرجع مسیردهی سایت (single source of truth)
 *
 * `.htaccess` همه درخواست‌ها را به `router.php` می‌فرستد و `router.php` فقط همین
 * جدول را می‌خواند؛ بنابراین هیچ فایل PHP دیگری از بیرون مستقیماً قابل اجرا نیست.
 *
 * ساختار:
 *   routes   → نشانی اصلی (canonical) ⇒ اسکریپت
 *              مقدار می‌تواند رشته (مسیر فایل) یا آرایه
 *              ['file' => …, 'get' => [پارامترهای پیش‌فرض]] باشد.
 *   aliases  → نشانی قدیمی یا جایگزین ⇒ نشانی اصلی
 *   patterns → مسیرهای پویا (slug / id / actions) با الگوهای منظم
 */

return [
    'routes' => [
        // ─── صفحات عمومی ────────────────────────────────────────────────
        '/'                     => 'index.php',
        '/index.php'            => 'index.php',
        '/about'                => 'pages/about.php',
        '/announcements'        => 'pages/announcements.php',
        '/article'              => 'pages/post.php',
        '/articles'             => 'pages/articles.php',
        '/book'                 => 'pages/book.php',
        '/books'                => 'pages/books.php',
        '/category'             => 'pages/category.php',
        '/contact'              => 'pages/contact.php',
        '/events'               => 'pages/events.php',
        '/lesson'               => 'pages/lesson.php',
        '/lessons'              => 'pages/lessons.php',
        '/media'                => 'pages/media-library.php',
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
        '/report'               => 'pages/post.php',
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
        // تنها نشانی مجاز نصاب `/php/install` است. ریشهٔ `/install.php` عمداً
        // ۴۰۴ می‌ماند (docs/FILE_ROUTE_MAP.md و tests/http.mjs همین را می‌سنجند)
        // تا مسیر قدیمی نصاب روی میزبان‌های اشتراکی قابل اجرا نباشد.
        '/php/install'          => 'php/install.php',
        '/php/install.php'      => 'php/install.php',

        // ─── پنل مدیریت ─────────────────────────────────────────────────
        '/admin'                     => 'admin/index.php',
        '/admin/login'               => 'admin/login.php',
        '/admin/logout'              => 'admin/logout.php',
        '/admin/change-password'     => 'admin/change-password.php',
        '/admin/settings'            => 'admin/settings.php',
        '/admin/content'             => 'admin/posts/index.php',
        '/admin/users'               => 'admin/users/index.php',
        '/admin/users/new'           => 'admin/users/index.php',
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

    // نشانی‌های قدیمی و مترادف
    'aliases' => [
        '/library'   => '/books',
        '/files'     => '/books',
        '/dashboard' => '/admin',
        '/login'     => '/admin/login',
        '/logout'    => '/admin/logout',
        '/event'     => '/events',
        // نشانی کوتاه نصاب. aliasها فقط با همان نوشتار (به‌علاوهٔ اسلش پایانی)
        // پاسخ می‌دهند، پس `/install.php` همچنان ۴۰۴ می‌ماند.
        '/install'   => '/php/install',
    ],

    // مسیرهای پویا: [الگو, اسکریپت (با جای‌گیری $n), نگاشت پارامترها]
    'patterns' => [
        // ─── پنل مدیریت: روت‌های پویا و عملیات محتوا ───────────────────
        ['~^/admin/users/edit/(\d+)/?$~D',                                 'admin/users/index.php',     ['edit' => 1]],
        ['~^/admin/topics/(\d+)/edit/?$~D',                                'admin/topics/edit.php',      ['id' => 1]],
        ['~^/admin/topics/edit/(\d+)/?$~D',                                'admin/topics/edit.php',      ['id' => 1]],
        ['~^/admin/content/(\d+)/(publish|unpublish|archive|delete)/?$~D', 'admin/posts/action.php',     ['id' => 1, 'action' => 2]],

        // ─── کتاب‌ها (شناسه عددی یا اسلاگ / مفرد و جمع) ───────────────
        ['~^/books?/(\d+)/?$~D',                                           'pages/book.php',            ['id' => 1]],
        ['~^/books?/([^/]+)/?$~uD',                                        'pages/book.php',            ['slug' => 1]],

        // ─── انواع مطالب با پیشوند نوع (مفرد و جمع) ────────────────────
        // typed post URLs: news, article(s), research(es), report(s), event(s), announcement(s), program(s)
        ['~^/(article|articles|news|research|researches|report|reports|event|events|announcement|announcements|program|programs)/([^/]+)/?$~uD', 'pages/post.php', ['expected_type' => 1, 'slug' => 2]],

        // ─── رسانه (شناسه عددی ویدیو / صوت / مدیا) ─────────────────────
        ['~^/(video|audio|media)/(\d+)/?$~D',                              'pages/media.php',           ['kind' => 1, 'id' => 2]],

        // ─── موضوعات (مفرد و جمع) ──────────────────────────────────────
        ['~^/topics?/([^/]+)/?$~uD',                                       'pages/topic.php',           ['slug' => 1]],

        // ─── جزئیات درس، سخنرانی، دسته‌بندی و پست متفرقه ───────────────
        ['~^/lesson/([^/]+)/?$~uD',                                        'pages/lesson.php',          ['slug' => 1]],
        ['~^/speech/([^/]+)/?$~uD',                                        'pages/speech.php',          ['slug' => 1]],
        ['~^/category/([^/]+)/?$~uD',                                      'pages/category.php',        ['slug' => 1]],
        ['~^/post/([^/]+)/?$~uD',                                          'pages/post.php',            ['slug' => 1]],

        // ─── مجموعه‌ها و جلدهای درسی ──────────────────────────────────
        ['~^/lessons/([^/]+)/([^/]+)/?$~uD',                               'pages/lessons.php',         ['collection' => 1, 'volume' => 2]],
        ['~^/lessons/([^/]+)/?$~uD',                                       'pages/lessons.php',         ['collection' => 1]],

        // ─── جستجو ─────────────────────────────────────────────────────
        ['~^/search/([^/]+)/?$~uD',                                        'pages/search.php',          ['q' => 1]],
    ],
];
