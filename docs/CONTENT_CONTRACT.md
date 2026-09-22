# قرارداد داده‌ای انواع محتوا (Content Contract)

تثبیت‌شده در مرحله «معماری محتوایی» — baseline: تگ `stage2-baseline`.
تغییر هر سطر این جدول باید با به‌روزرسانی تست‌های `route-status` و `http` همراه باشد.

## ماتریس هشت نوع محتوا

| نوع | جدول | slug | روت جزئیات | Resolver | روت فهرست | صفحه‌بندی | رابطه موضوعی | دسته‌بندی | مرتبط |
|---|---|---|---|---|---|---|---|---|---|
| مقاله | `posts` (`article`) | UNIQUE | `/article/{slug}` | `getPostBySlug` + `expected_type` | `/articles` | `getPosts`/`countPosts` | `post_topics` | `category_id` | هم‌موضوع ← هم‌نوع + کتاب + درس |
| خبر | `posts` (`news`) | UNIQUE | `/news/{slug}` | همان | `/news` (+`section=news`) | همان | همان | همان | همان |
| پژوهش | `posts` (`research`) | UNIQUE | `/research/{slug}` | همان | `/research` | همان | همان | همان | همان |
| کتاب | `books` | UNIQUE (nullable) | `/book/{slug\|id}` | `getBookBySlug` / `getBookById` | `/books` | `getBooks`/`countBooks` | `book_topics` | — | `getRelatedBooks` (هم‌موضوع) |
| ویدیو | `media_files` (`video`) | — (با id) | `/video/{id}` | `getMediaById` + تطبیق kind | `/videos` | UNION + count | از والد | — | هم‌والد ← تازه‌ترین هم‌نوع |
| صوت | `media_files` (`audio`) | — (با id) | `/audio/{id}` | همان | `/audios` | همان | از والد | — | همان |
| درس | `lessons` | UNIQUE | `/lesson/{slug}` | `getLessonBySlug` (+join مجموعه/جلد) | `/lessons` (+`collection`/`volume`) | count سفارشی | `lesson_topics` | — | هم‌موضوع ← هم‌مجموعه + درس قبلی/بعدی |
| موضوع | `topics` | UNIQUE | `/topic/{slug}` | `getTopicBySlug` + `is_active` | `/topics` (درخت کامل) | — (کم‌تعداد) | والد/فرزند | — | صفحه هاب همه انواع |

## نامتغیرها (Invariants)

1. **slug یکتاست** در `posts` / `lessons` / `books` / `topics` / `categories` / `lesson_collections` و `(collection_id, slug)` در `lesson_volumes`. تولید با `uniqueSlug()` و نرمال‌سازی با `makeSlug()` (یونیکد فارسی حفظ می‌شود).
2. **جزئیات منتشرنشده 404 می‌دهد**، نه 200 خالی و نه ریدایرکت ساکت: پیش‌نویس پست/درس/کتاب، kind اشتباه مدیا (`/audio/1` وقتی 1 ویدیوست)، والد پیش‌نویس مدیا، موضوع غیرفعال، مجموعه/جلد/دسته ناموجود.
3. **canonical جزئیات** از هلپر خودش می‌آید (`postUrl` / `bookUrl` / `lessonUrl` / `topicUrl` / `collectionUrl` / `mediaUrl`) و `$canonicalOverride` مقتدرانه است (بدون چسباندن query).
4. **دسته‌بندی (`categories`) فقط مال پست‌هاست.** کتاب و درس فقط با موضوع (`topics`) رابطه دارند.
5. **صفحه‌بندی** همیشه `page` را با `max(1,(int))` نرمال می‌کند؛ صفحه‌های بیرون از بازه 200 برمی‌گردانند (خالی یا clamped)، نه خطا.
6. **ورودی‌های resolver** در برابر آرایه (`?slug[]=x`) مقاوم‌اند: 404 تمیز، نه TypeError.
7. **کرسرهای `RETURNING`** بلافاصله `closeCursor()` می‌شوند تا journal‌نویسی shutdown (تک‌اتصاله) هیچ‌وقت روی قفل نماند.

## بیرون از اسکوپ این مرحله (یادداشت برای مراحل بعد)

- بهینه‌سازی N+1 در `topics.php` (شمارش به‌ازای هر موضوع) و برچسب‌های موضوعی داخل حلقه فهرست‌ها ← مرحله ۲۱.
- اخطار `Undefined array key "id"` در `includes/admin-actions.php` روی GET صفحات delete بدون id (بی‌خطر، فقط stderr).
