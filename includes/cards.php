<?php
/**
 * Unified editorial cards — one design language for every public listing.
 */
function jhd_card_icon_for(string $type): string {
    return match ($type) {
        'news' => 'bi-newspaper',
        'article' => 'bi-file-text',
        'research' => 'bi-journal-richtext',
        'report' => 'bi-camera',
        'announcement' => 'bi-megaphone',
        'speech' => 'bi-mic',
        'program', 'religious', 'event' => 'bi-calendar-event',
        'qa' => 'bi-question-circle',
        'book' => 'bi-book',
        'lesson' => 'bi-mortarboard',
        'topic' => 'bi-diagram-3',
        'video' => 'bi-play-circle',
        'audio' => 'bi-headphones',
        default => 'bi-file-earmark',
    };
}

function jhd_card_topics(array $post, int $limit = 2): array {
    if (empty($post['id'])) return [];
    try {
        return array_slice(getTopicsForPost((int)$post['id']), 0, $limit);
    } catch (Throwable $e) {
        return [];
    }
}

function renderPostCard(array $post, array $opts = []): string {
    $type = (string)($post['post_type'] ?? ($opts['type'] ?? 'post'));
    $href = $opts['url'] ?? postUrl($post);
    $title = (string)($post['title'] ?? '');
    $summary = excerpt((string)($post['summary'] ?? $post['content'] ?? ''), (int)($opts['excerpt'] ?? 118));
    $image = (string)($post['featured_image'] ?? '');
    $date = persianDate((string)($post['published_at'] ?? $post['created_at'] ?? ''));
    $author = (string)($post['author_name'] ?? $post['speaker'] ?? '');
    $featured = !empty($opts['featured']);
    $topics = $opts['topics'] ?? jhd_card_topics($post);
    $icon = jhd_card_icon_for($type);
    $badge = postTypeLabel($type);
    $cta = $opts['cta'] ?? 'ادامه مطلب';
    $col = $featured ? 'col-12' : ($opts['col'] ?? 'col-md-6 col-lg-4');
    $class = 'jhd-card jhd-card--' . preg_replace('/[^a-z]/', '', $type);
    if ($featured) $class .= ' jhd-card--featured';
    $class .= ' news-card';

    $imgHtml = $image
        ? '<img src="' . sanitize(imgUrl($image)) . '" alt="' . sanitize($title) . '" class="news-card-img jhd-card-img" loading="lazy" decoding="async">'
        : '<div class="news-card-placeholder jhd-card-ph"><i class="bi ' . $icon . '"></i></div>';

    $topicHtml = '';
    foreach ($topics as $tp) {
        $topicHtml .= '<a class="jhd-chip" href="' . sanitize(topicUrl($tp)) . '">' . sanitize((string)$tp['name']) . '</a>';
    }

    $authorHtml = $author !== '' ? '<span class="jhd-card-author"><i class="bi bi-person ms-1"></i>' . sanitize($author) . '</span>' : '';

    return '<div class="' . $col . '">'
        . '<article class="' . $class . ' h-100">'
        . '<a class="news-card-img-wrap jhd-card-media" href="' . sanitize($href) . '" aria-label="' . sanitize($title) . '">'
        . $imgHtml
        . '<span class="jhd-card-badge">' . sanitize($badge) . '</span>'
        . '</a>'
        . '<div class="news-card-body jhd-card-body">'
        . '<div class="jhd-card-meta">' . $topicHtml . '<time>' . sanitize($date) . '</time></div>'
        . '<h3 class="news-card-title jhd-card-title"><a href="' . sanitize($href) . '">' . sanitize($title) . '</a></h3>'
        . ($summary !== '' ? '<p class="news-card-summary jhd-card-summary">' . sanitize($summary) . '</p>' : '')
        . '<div class="news-card-footer jhd-card-foot">' . $authorHtml
        . '<a class="btn-read-more" href="' . sanitize($href) . '">' . sanitize($cta) . ' <i class="bi bi-arrow-left"></i></a>'
        . '</div></div></article></div>';
}

function renderBookCard(array $book, array $opts = []): string {
    $href = bookUrl($book);
    $title = (string)($book['title'] ?? '');
    $cover = (string)($book['cover_image'] ?? '');
    $author = (string)($book['author'] ?? '');
    $col = $opts['col'] ?? 'col-6 col-md-4 col-lg-3';
    $img = $cover
        ? '<img src="' . sanitize(imgUrl($cover)) . '" alt="جلد ' . sanitize($title) . '" loading="lazy">'
        : '<div class="jhd-card-ph jhd-book-ph"><i class="bi bi-book"></i></div>';
    return '<div class="' . $col . '"><article class="book-card jhd-card jhd-card--book h-100">'
        . '<div class="book-card-cover"><a href="' . sanitize($href) . '">' . $img . '</a></div>'
        . '<h3 class="book-card-title"><a href="' . sanitize($href) . '">' . sanitize($title) . '</a></h3>'
        . ($author !== '' ? '<div class="book-card-author"><i class="bi bi-pen ms-1"></i>' . sanitize($author) . '</div>' : '')
        . '<a class="btn-read-more" href="' . sanitize($href) . '">معرفی کتاب <i class="bi bi-arrow-left"></i></a>'
        . '</article></div>';
}

function renderLessonCard(array $lesson, array $opts = []): string {
    $href = lessonUrl($lesson);
    $title = (string)($lesson['title'] ?? '');
    $teacher = (string)($lesson['teacher'] ?? '');
    $col = $opts['col'] ?? 'col-md-6 col-lg-4';
    $image = (string)($lesson['featured_image'] ?? '');
    $img = $image
        ? '<img src="' . sanitize(imgUrl($image)) . '" alt="' . sanitize($title) . '" class="jhd-card-img news-card-img" loading="lazy">'
        : '<div class="jhd-card-ph news-card-placeholder"><i class="bi bi-mortarboard"></i></div>';
    return '<div class="' . $col . '"><article class="lesson-card jhd-card jhd-card--lesson h-100">'
        . '<a class="jhd-card-media news-card-img-wrap" href="' . sanitize($href) . '">' . $img . '<span class="jhd-card-badge">درس</span></a>'
        . '<div class="jhd-card-body news-card-body"><h3 class="jhd-card-title news-card-title"><a href="' . sanitize($href) . '">' . sanitize($title) . '</a></h3>'
        . ($teacher !== '' ? '<p class="jhd-card-summary">' . sanitize($teacher) . '</p>' : '')
        . '<a class="btn-read-more" href="' . sanitize($href) . '">ورود به درس <i class="bi bi-arrow-left"></i></a>'
        . '</div></article></div>';
}

function renderTopicCard(array $topic, array $opts = []): string {
    $href = topicUrl($topic);
    $name = (string)($topic['name'] ?? '');
    $desc = excerpt((string)($topic['intro'] ?? $topic['description'] ?? ''), 110);
    $cover = (string)($topic['cover_image'] ?? '');
    $children = $topic['children'] ?? [];
    $col = $opts['col'] ?? 'col-md-6 col-lg-4';
    $img = $cover
        ? '<img src="' . sanitize(imgUrl($cover)) . '" alt="' . sanitize($name) . '" class="jhd-card-img" loading="lazy">'
        : '<div class="jhd-card-ph"><i class="bi bi-diagram-3"></i></div>';
    $childHtml = '';
    foreach (array_slice($children, 0, 4) as $ch) {
        $childHtml .= '<a class="jhd-chip" href="' . sanitize(topicUrl($ch)) . '">' . sanitize((string)$ch['name']) . '</a>';
    }
    return '<div class="' . $col . '"><article class="topic-card jhd-card jhd-card--topic h-100">'
        . '<a class="jhd-card-media jhd-topic-media" href="' . sanitize($href) . '">' . $img . '</a>'
        . '<div class="jhd-card-body"><h3 class="jhd-card-title"><a href="' . sanitize($href) . '">' . sanitize($name) . '</a></h3>'
        . ($desc !== '' ? '<p class="jhd-card-summary">' . sanitize($desc) . '</p>' : '')
        . ($childHtml !== '' ? '<div class="jhd-card-meta">' . $childHtml . '</div>' : '')
        . '<a class="btn-read-more" href="' . sanitize($href) . '">ورود به موضوع <i class="bi bi-arrow-left"></i></a>'
        . '</div></article></div>';
}

function renderEmptyState(string $icon, string $title, string $ctaUrl = '', string $cta = ''): string {
    $btn = ($ctaUrl !== '' && $cta !== '')
        ? '<a class="btn btn-outline-primary btn-sm mt-2" href="' . sanitize($ctaUrl) . '">' . sanitize($cta) . '</a>'
        : '';
    return '<div class="jhd-empty-state"><i class="bi ' . sanitize($icon) . '" aria-hidden="true"></i><p>' . sanitize($title) . '</p>' . $btn . '</div>';
}

function jhd_nav_sections(): array {
    return [
        ['route' => 'news', 'label' => 'اخبار', 'icon' => 'bi-newspaper', 'types' => ['news']],
        ['route' => 'articles', 'label' => 'مقالات', 'icon' => 'bi-file-text', 'types' => ['article']],
        ['route' => 'reports', 'label' => 'گزارش‌ها', 'icon' => 'bi-card-text', 'types' => ['report']],
        ['route' => 'research', 'label' => 'پژوهش', 'icon' => 'bi-journal-richtext', 'types' => ['research']],
        ['route' => 'events', 'label' => 'رویدادها', 'icon' => 'bi-calendar-event', 'types' => ['program', 'religious', 'announcement']],
        ['route' => 'books', 'label' => 'کتابخانه', 'icon' => 'bi-book', 'types' => []],
        ['route' => 'lessons', 'label' => 'دروس', 'icon' => 'bi-mortarboard', 'types' => []],
        ['route' => 'media', 'label' => 'رسانه', 'icon' => 'bi-play-circle', 'types' => []],
        ['route' => 'topics', 'label' => 'موضوعات', 'icon' => 'bi-diagram-3', 'types' => []],
    ];
}

function getCategoriesForTypes(array $types): array {
    if (!$types) return [];
    $out = [];
    foreach (getCategories() as $c) {
        $pt = (string)($c['post_type'] ?? 'all');
        if (in_array($pt, $types, true)) $out[] = $c;
    }
    return $out;
}

function jhd_section_children(array $section): array {
    $route = (string)($section['route'] ?? '');
    $children = [];
    foreach (getCategoriesForTypes($section['types'] ?? []) as $c) {
        $children[] = ['label' => (string)$c['name'], 'url' => categoryUrl($c)];
    }
    if ($route === 'media') {
        $children[] = ['label' => 'ویدیو', 'url' => url('videos')];
        $children[] = ['label' => 'صوت', 'url' => url('audios')];
    }
    if ($route === 'events') {
        $children[] = ['label' => 'برنامه‌ها', 'url' => url('programs')];
        $children[] = ['label' => 'فعالیت‌های مذهبی', 'url' => url('religious-activities')];
        $children[] = ['label' => 'اطلاعیه‌ها', 'url' => url('announcements')];
    }
    if ($route === 'lessons') {
        foreach (getLessonCollections(['active' => 1]) as $col) {
            $children[] = ['label' => (string)$col['title'], 'url' => collectionUrl($col)];
        }
    }
    return $children;
}

function jhd_render_desktop_nav_item(array $section, callable $isActiveNav, array $extraActive = []): string {
    $route = (string)$section['route'];
    $label = (string)$section['label'];
    $icon = (string)$section['icon'];
    $children = $section['children'] ?? jhd_section_children($section);
    $active = $isActiveNav($route);
    foreach ($extraActive as $r) {
        if ($isActiveNav($r)) $active = true;
    }
    $has = $children ? ' class="jhd-has-sub"' : '';
    $html = '<li' . $has . '>';
    $html .= '<a href="' . sanitize(url($route)) . '" class="jhd-nav-link' . ($active ? ' active' : '') . '"'
        . ($active ? ' aria-current="page"' : '')
        . ($children ? ' aria-haspopup="true"' : '') . '>'
        . '<i class="bi ' . sanitize($icon) . ' ms-1"></i>' . sanitize($label) . '</a>';
    if ($children) {
        $html .= '<ul class="jhd-subnav" role="menu">';
        $html .= '<li><a href="' . sanitize(url($route)) . '">همه ' . sanitize($label) . '</a></li>';
        foreach ($children as $ch) {
            $html .= '<li><a href="' . sanitize((string)$ch['url']) . '">' . sanitize((string)$ch['label']) . '</a></li>';
        }
        $html .= '</ul>';
    }
    return $html . '</li>';
}

function jhd_render_drawer_nav_item(array $section, callable $isActiveNav): string {
    $route = (string)$section['route'];
    $label = (string)$section['label'];
    $icon = (string)$section['icon'];
    $children = $section['children'] ?? jhd_section_children($section);
    $active = $isActiveNav($route) ? ' active' : '';
    $href = sanitize(url($route));
    if (!$children) {
        return '<a href="' . $href . '" class="drawer-link' . $active . '"><i class="bi ' . sanitize($icon) . '"></i> ' . sanitize($label) . '</a>';
    }
    $html = '<details class="jhd-acc"' . ($active ? ' open' : '') . '>';
    $html .= '<summary><i class="bi ' . sanitize($icon) . '"></i> ' . sanitize($label) . '</summary>';
    $html .= '<div class="jhd-acc-body">';
    $html .= '<a class="drawer-link' . $active . '" href="' . $href . '">همه ' . sanitize($label) . '</a>';
    foreach ($children as $ch) {
        $html .= '<a class="drawer-link" href="' . sanitize((string)$ch['url']) . '">' . sanitize((string)$ch['label']) . '</a>';
    }
    $html .= '</div></details>';
    return $html;
}

function renderCategoryChips(array $types, string $allUrl, string $allLabel = 'همه'): string {
    $cats = getCategoriesForTypes($types);
    if (!$cats) return '';
    $html = '<nav class="jhd-cat-strip" aria-label="زیربخش‌ها">';
    $html .= '<a class="jhd-chip jhd-chip--all" href="' . sanitize($allUrl) . '">' . sanitize($allLabel) . '</a>';
    foreach ($cats as $c) {
        $html .= '<a class="jhd-chip" href="' . sanitize(categoryUrl($c)) . '">' . sanitize((string)$c['name']) . '</a>';
    }
    return $html . '</nav>';
}

function jhd_render_topic_tree_nav(array $nodes, string $mode = 'desktop'): string {
    $html = '';
    foreach ($nodes as $node) {
        $children = $node['children'] ?? [];
        $name = sanitize((string)$node['name']);
        $href = sanitize(topicUrl($node));
        if ($children) {
            if ($mode === 'drawer') {
                $html .= '<details class="jhd-acc"><summary>' . $name . '</summary><div class="jhd-acc-body">';
                $html .= '<a class="drawer-link" href="' . $href . '">نمای کلی «' . $name . '»</a>';
                $html .= jhd_render_topic_tree_nav($children, 'drawer');
                $html .= '</div></details>';
            } else {
                $html .= '<li class="jhd-has-sub"><a href="' . $href . '">' . $name . '</a><ul class="jhd-subnav jhd-subnav-nested">';
                foreach ($children as $ch) {
                    $html .= '<li><a href="' . sanitize(topicUrl($ch)) . '">' . sanitize((string)$ch['name']) . '</a></li>';
                }
                $html .= '</ul></li>';
            }
        } else {
            $html .= $mode === 'drawer'
                ? '<a class="drawer-link" href="' . $href . '">' . $name . '</a>'
                : '<li><a href="' . $href . '">' . $name . '</a></li>';
        }
    }
    return $html;
}
